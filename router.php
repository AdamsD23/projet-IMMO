<?php
/**
 * Routeur pour le serveur PHP intégré (développement local) :
 *   php -S localhost:8000 router.php
 * Les URL /api/... vont vers l'API ; le reste est servi comme fichiers du site.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (str_starts_with($path, '/api')) {
    require __DIR__ . '/api/index.php';
    return true;
}

// Ne jamais servir le code serveur, la configuration ou les fichiers cachés
if (preg_match('#^/(api|tests|node_modules|archive)(/|$)|/\.|\.(php|sql|md|json|lock)$#i', $path)) {
    http_response_code(404);
    echo 'Introuvable';
    return true;
}

if ($path === '/') {
    require __DIR__ . '/index.html';
    return true;
}

return false;
