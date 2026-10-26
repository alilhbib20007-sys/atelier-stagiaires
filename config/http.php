<?php

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
