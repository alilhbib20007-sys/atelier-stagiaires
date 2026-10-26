<?php
// Étape 7 : lire les stagiaires depuis MySQL (Aiven) et les renvoyer en JSON

require dirname(__DIR__) . '/config/http.php';
require dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    repondre(405, ['succes' => false, 'message' => 'Méthode non autorisée : utilisez GET.']);
}

try {
    $pdo = getDatabase();
    $stagiaires = $pdo
        ->query('SELECT id, nom, prenom, ville, photo, date_inscription FROM stagiaires ORDER BY date_inscription DESC, id DESC')
        ->fetchAll();

    repondre(200, [
        'succes'     => true,
        'total'      => count($stagiaires),
        'stagiaires' => $stagiaires,
    ]);
} catch (Throwable $e) {
    error_log('[liste] ' . $e->getMessage());
    repondre(500, ['succes' => false, 'message' => 'Impossible de lire les stagiaires. ' . messageErreur($e)]);
}
