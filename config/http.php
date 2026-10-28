<?php

/**
 * Variables d'environnement du processus parent (le lanceur Node de vercel-php).
 * Selon la version du runtime, le serveur PHP ne reçoit pas les variables du projet :
 * elles restent visibles dans /proc/<pid du parent>/environ.
 */
function env_parent(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    $status = @file_get_contents('/proc/self/status');
    if ($status && preg_match('/^PPid:\s+(\d+)/m', $status, $m)) {
        $brut = @file_get_contents('/proc/' . $m[1] . '/environ');
        foreach ($brut ? explode("\0", $brut) : [] as $ligne) {
            $pos = strpos($ligne, '=');
            if ($pos > 0) {
                $cache[substr($ligne, 0, $pos)] = substr($ligne, $pos + 1);
            }
        }
    }
    return $cache;
}

/**
 * Lit une variable d'environnement : getenv(), puis $_ENV, $_SERVER,
 * puis l'environnement du processus parent (cas du runtime vercel-php).
 */
function env(string $nom)
{
    $valeur = getenv($nom);
    if ($valeur !== false && $valeur !== '') {
        return $valeur;
    }
    foreach ([$_ENV, $_SERVER, env_parent()] as $source) {
        if (isset($source[$nom]) && $source[$nom] !== '') {
            return $source[$nom];
        }
    }
    return false;
}

/** Diagnostic sans aucune valeur : où chaque variable est-elle visible ? */
function diagnostic_env(array $noms): string
{
    $parent = env_parent();
    $morceaux = [];
    foreach ($noms as $nom) {
        $ou = [];
        if (getenv($nom) !== false) { $ou[] = 'getenv'; }
        if (isset($_ENV[$nom])) { $ou[] = 'ENV'; }
        if (isset($_SERVER[$nom])) { $ou[] = 'SERVER'; }
        if (isset($parent[$nom])) { $ou[] = 'parent'; }
        $morceaux[] = $nom . '=' . ($ou ? implode('+', $ou) : 'absente');
    }
    return implode(', ', $morceaux) . ' | nb getenv=' . count((array) getenv()) . ', nb parent=' . count($parent);
}

/** Envoie une réponse JSON et arrête le script. */
function repondre(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Exception dont le message peut être montré à l'utilisateur (configuration manquante, etc.). */
class ErreurConfiguration extends Exception
{
}

/** Transforme une exception en message lisible, sans exposer de détails techniques sensibles. */
function messageErreur(Throwable $e): string
{
    if ($e instanceof ErreurConfiguration) {
        return $e->getMessage();
    }
    if ($e instanceof PDOException) {
        return 'Erreur MySQL : vérifiez les variables DB_*, le certificat certs/ca.pem et la table stagiaires (détails dans les logs Vercel).';
    }
    return 'Erreur serveur (détails dans les logs Vercel).';
}
