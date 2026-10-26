<?php

/**
 * Connexion PDO à la base MySQL hébergée sur Aiven.
 * Les identifiants viennent des variables d'environnement (Vercel → Settings → Environment Variables).
 */
function getDatabase(): PDO
{
    $host = env('DB_HOST');
    $port = env('DB_PORT');
    $name = env('DB_NAME');
    $user = env('DB_USER');
    $password = env('DB_PASSWORD');

    if (!$host || !$port || !$name || !$user || $password === false) {
        throw new ErreurConfiguration("Variables d'environnement DB_* manquantes.");
    }

    $caPath = dirname(__DIR__) . '/certs/ca.pem';
    if (!is_file($caPath)) {
        throw new ErreurConfiguration('Certificat CA introuvable : ajoutez certs/ca.pem (téléchargé depuis Aiven).');
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

    // PHP 8.4+ : constantes \Pdo\Mysql::... ; versions précédentes : PDO::MYSQL_ATTR_...
    $sslCa = defined('Pdo\Mysql::ATTR_SSL_CA') ? \Pdo\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA;
    $sslVerify = defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
        ? \Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT
        : PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        $sslCa => $caPath,
        $sslVerify => true,
    ];

    return new PDO($dsn, $user, $password, $options);
}
