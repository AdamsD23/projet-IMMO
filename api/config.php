<?php
/**
 * Configuration — lue depuis les variables d'environnement (valeurs par défaut pour XAMPP en local).
 */

function env(string $key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

// Fichier .env facultatif en local (KEY=valeur par ligne)
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if (getenv($k) === false) putenv("$k=" . trim($v, "\"'"));
    }
}

define('APP_ENV', env('APP_ENV', 'development'));
define('IS_PRODUCTION', APP_ENV === 'production');

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env('DB_PORT', 3306));
define('DB_NAME', env('DB_NAME', 'accueil_immo'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));
define('DB_SSL', in_array(strtolower((string) env('DB_SSL', '')), ['1', 'true', 'yes'], true));
define('DB_SSL_CA', env('DB_SSL_CA'));

$secret = env('JWT_SECRET');
if (!$secret && IS_PRODUCTION) {
    http_response_code(500);
    exit(json_encode(['error' => 'JWT_SECRET manquant']));
}
define('JWT_SECRET', $secret ?: 'dev-secret-a-changer');
define('JWT_TTL', 60 * 60 * 12);

// Données de démonstration ajoutées si la base est vide
define('SEED_DEMO', in_array(strtolower((string) env('SEED_DEMO', IS_PRODUCTION ? 'false' : 'true')), ['1', 'true', 'yes'], true));

// Images envoyées par les utilisateurs
define('MAX_IMAGE_BYTES', 2 * 1024 * 1024);
define('MAX_IMAGES_PER_PROPERTY', 6);

const COMMUNES = ['cocody', 'plateau', 'marcory', 'riviera', 'yopougon', 'treichville', 'bingerville', 'assinie', 'grand-bassam', 'autre'];
const TYPES_BIEN = ['appartement', 'villa', 'studio', 'duplex', 'terrain', 'bureau'];
const STATUTS_BIEN = ['location', 'vente'];
const EQUIPEMENTS = ['meuble', 'climatisation', 'parking', 'piscine', 'gardiennage', 'groupe_electrogene'];
