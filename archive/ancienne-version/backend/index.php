<?php
/**
 * Point d'entrée principal de l'API
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'utils/utils.php';

// Activer le rapport d'erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Créer les dossiers de logs s'ils n'existent pas
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Router API
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Nettoyer l'URI pour obtenir le chemin
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/prodesticprojet/backend', '', $path); // Adapter selon votre configuration
$path = trim($path, '/');

// Log de la requête
Config::logInfo("API Request: {$request_method} {$path}", [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Routes API
$routes = [
    // Authentification
    'GET|POST /auth/register' => 'controllers/AuthController.php?action=register',
    'GET|POST /auth/login' => 'controllers/AuthController.php?action=login',
    'POST /auth/logout' => 'controllers/AuthController.php?action=logout',
    'GET /auth/check' => 'controllers/AuthController.php?action=check',
    'PUT /auth/update' => 'controllers/AuthController.php?action=update',
    'PUT /auth/password' => 'controllers/AuthController.php?action=password',
    'POST /auth/upload' => 'controllers/AuthController.php?action=upload',
    
    // Biens immobiliers
    'POST /properties' => 'controllers/PropertyController.php?action=create',
    'GET /properties' => 'controllers/PropertyController.php?action=list',
    'GET /properties/search' => 'controllers/PropertyController.php?action=search',
    'GET /properties/featured' => 'controllers/PropertyController.php?action=featured',
    'GET /properties/my' => 'controllers/PropertyController.php?action=myProperties',
    'GET /properties/stats' => 'controllers/PropertyController.php?action=stats',
    'POST /properties/upload' => 'controllers/PropertyController.php?action=upload',
    'PUT /properties/(\d+)/occupancy' => 'controllers/PropertyController.php?action=occupancy&id=$1',
    'PUT /properties/(\d+)/toggle-featured' => 'controllers/PropertyController.php?action=toggle-featured&id=$1',
    'GET|PUT|DELETE /properties/(\d+)' => 'controllers/PropertyController.php?id=$1',
    
    // Dashboard
    'GET /dashboard/stats' => 'controllers/DashboardController.php?action=stats',
    'GET /dashboard/recent' => 'controllers/DashboardController.php?action=recent',
    'GET /dashboard/revenue' => 'controllers/DashboardController.php?action=revenue',
    
    // Contact
    'POST /contact' => 'controllers/ContactController.php?action=submit',
    'GET /contact' => 'controllers/ContactController.php?action=list',
    
    // Conciergerie
    'GET /concierge/services' => 'controllers/ConciergeController.php?action=services',
    'POST /concierge/book' => 'controllers/ConciergeController.php?action=book',
    'GET /concierge/bookings' => 'controllers/ConciergeController.php?action=bookings',
    
    // Notifications
    'GET /notifications' => 'controllers/NotificationController.php?action=list',
    'PUT /notifications/(\d+)/read' => 'controllers/NotificationController.php?action=read&id=$1',
    
    // Setup (création des tables)
    'POST /setup/database' => 'setup/setup.php?action=database',
    'POST /setup/test-data' => 'setup/setup.php?action=test-data'
];

// Trouver la route correspondante
$matched_route = null;
$params = [];

foreach ($routes as $route => $handler) {
    list($methods, $pattern) = explode(' ', $route, 2);
    
    // Vérifier la méthode HTTP
    if (!in_array($request_method, explode('|', $methods))) {
        continue;
    }
    
    // Convertir le pattern en regex
    $regex = '#^' . preg_replace('#\(\d+\)#', '(\d+)', $pattern) . '$#';
    
    if (preg_match($regex, $path, $matches)) {
        $matched_route = $handler;
        
        // Extraire les paramètres
        for ($i = 1; $i < count($matches); $i++) {
            $params[] = $matches[$i];
        }
        break;
    }
}

// Exécuter la route ou retourner une erreur
if ($matched_route) {
    try {
        // Remplacer les paramètres dans le handler
        $handler = $matched_route;
        for ($i = 0; $i < count($params); $i++) {
            $handler = str_replace('$' . ($i + 1), $params[$i], $handler);
        }
        
        // Parser le handler
        parse_str(parse_url($handler, PHP_URL_QUERY), $query_params);
        $file = parse_url($handler, PHP_URL_PATH);
        
        // Inclure et exécuter le fichier
        if (file_exists(__DIR__ . '/' . $file)) {
            $_GET = array_merge($_GET, $query_params);
            require_once __DIR__ . '/' . $file;
        } else {
            Utils::jsonResponseError('Fichier de contrôleur non trouvé', 500);
        }
        
    } catch (Exception $e) {
        Config::logError('API Error: ' . $e->getMessage(), [
            'path' => $path,
            'method' => $request_method,
            'trace' => $e->getTraceAsString()
        ]);
        
        Utils::jsonResponseError('Erreur interne du serveur', 500);
    }
} else {
    // Route non trouvée
    Utils::jsonResponseError('Endpoint non trouvé', 404, [
        'available_endpoints' => array_keys($routes),
        'requested' => $request_method . ' ' . $path
    ]);
}
?>
