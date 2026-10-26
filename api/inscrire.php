<?php
// Étape 6 : enregistrer un stagiaire (écriture dans MySQL + photo sur Cloudinary)

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/http.php';
require dirname(__DIR__) . '/config/db.php';

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    repondre(405, ['succes' => false, 'message' => 'Méthode non autorisée : utilisez POST.']);
}

// 1. Recevoir les données du formulaire
$nom    = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$ville  = trim($_POST['ville'] ?? '');

// 2. Vérifier les données et le fichier image
$erreurs = [];
foreach (['nom' => $nom, 'prénom' => $prenom, 'ville' => $ville] as $champ => $valeur) {
    if ($valeur === '') {
        $erreurs[] = "Le champ $champ est obligatoire.";
    } elseif (mb_strlen($valeur) > 100) {
        $erreurs[] = "Le champ $champ ne doit pas dépasser 100 caractères.";
    }
}

$photo = $_FILES['photo'] ?? null;
if (!$photo || ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $erreurs[] = 'La photo est obligatoire.';
} elseif ($photo['error'] !== UPLOAD_ERR_OK) {
    $erreurs[] = "Erreur lors de l'envoi de la photo (code {$photo['error']}).";
} else {
    $typesAutorises = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $type = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
    if (!in_array($type, $typesAutorises, true)) {
        $erreurs[] = 'La photo doit être une image JPG, PNG, WEBP ou GIF.';
    }
    if ($photo['size'] > 4 * 1024 * 1024) {
        $erreurs[] = 'La photo ne doit pas dépasser 4 Mo.';
    }
}

if ($erreurs) {
    repondre(422, ['succes' => false, 'message' => implode(' ', $erreurs)]);
}

try {
    // 3. Envoyer la photo à Cloudinary (CLOUDINARY_URL = cloudinary://API_KEY:API_SECRET@CLOUD_NAME)
    $cloudinaryUrl = getenv('CLOUDINARY_URL');
    if (!$cloudinaryUrl) {
        throw new ErreurConfiguration("Variable d'environnement CLOUDINARY_URL manquante.");
    }
    Configuration::instance($cloudinaryUrl);

    $resultat = (new UploadApi())->upload($photo['tmp_name'], [
        'folder'        => 'stagiaires',
        'resource_type' => 'image',
    ]);

    // 4. Récupérer l'URL sécurisée (https)
    $urlPhoto = $resultat['secure_url'];

    // 5. Insérer les informations dans MySQL (requête préparée : protection contre l'injection SQL)
    $pdo = getDatabase();
    $requete = $pdo->prepare(
        'INSERT INTO stagiaires (nom, prenom, ville, photo) VALUES (:nom, :prenom, :ville, :photo)'
    );
    $requete->execute([
        ':nom'    => $nom,
        ':prenom' => $prenom,
        ':ville'  => $ville,
        ':photo'  => $urlPhoto,
    ]);

    // 6. Renvoyer une réponse JSON au navigateur
    repondre(201, [
        'succes'  => true,
        'message' => "Le stagiaire $prenom $nom a bien été inscrit.",
        'id'      => (int) $pdo->lastInsertId(),
        'photo'   => $urlPhoto,
    ]);
} catch (Throwable $e) {
    error_log('[inscrire] ' . $e->getMessage());
    repondre(500, ['succes' => false, 'message' => "L'inscription a échoué. " . messageErreur($e)]);
}
