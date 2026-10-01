<?php
/**
 * Configuration principale de l'application
 */

class Config {
    // Configuration base de données
    const DB_HOST = 'localhost';
    const DB_NAME = 'accueil_immo';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';
    
    // Configuration application
    const APP_NAME = 'Accueil Immo CI';
    const APP_URL = 'http://localhost/prodesticprojet';
    const APP_VERSION = '1.0.0';
    
    // Configuration sécurité
    const JWT_SECRET = 'votre_cle_secrete_jwt_tres_longue_et_securisee_2024';
    const JWT_EXPIRE = 86400; // 24 heures
    const PASSWORD_MIN_LENGTH = 8;
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOGIN_TIMEOUT = 900; // 15 minutes
    
    // Configuration fichiers
    const UPLOAD_PATH = 'backend/uploads/';
    const MAX_FILE_SIZE = 5242880; // 5MB
    const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp'];
    
    // Configuration email
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'contact@accueilimmo.ci';
    const SMTP_PASSWORD = 'votre_mot_de_passe_email';
    const FROM_EMAIL = 'contact@accueilimmo.ci';
    const FROM_NAME = 'Accueil Immo CI';
    
    // Configuration WhatsApp
    const WHATSAPP_API_URL = 'https://graph.facebook.com/v18.0/';
    const WHATSAPP_PHONE_ID = 'votre_phone_id';
    const WHATSAPP_ACCESS_TOKEN = 'votre_access_token';
    
    // Configuration pagination
    const DEFAULT_PAGE_SIZE = 12;
    const MAX_PAGE_SIZE = 50;
    
    // Configuration monnaie
    const DEFAULT_CURRENCY = 'XOF';
    const CURRENCY_SYMBOL = 'CFA';
    
    // Configuration pays
    const DEFAULT_COUNTRY = 'Côte d\'Ivoire';
    const AVAILABLE_CITIES = [
        'Abidjan' => ['cocody', 'marcory', 'plateau', 'riviera', 'bingerville', 'yopougon', 'treichville'],
        'Assinie',
        'Yamoussoukro',
        'San Pedro',
        'Bouaké'
    ];
    
    // Configuration types de biens
    const PROPERTY_TYPES = [
        'appartement' => 'Appartement',
        'villa' => 'Villa',
        'studio' => 'Studio',
        'duplex' => 'Duplex',
        'terrain' => 'Terrain',
        'bureau' => 'Bureau / Commerce',
        'commerce' => 'Local Commercial'
    ];
    
    // Configuration statuts
    const PROPERTY_STATUS = [
        'location' => 'Location',
        'vente' => 'Vente',
        'gestion' => 'Gestion'
    ];
    
    const USER_TYPES = [
        'proprietaire' => 'Propriétaire',
        'locataire' => 'Locataire',
        'agent' => 'Agent Immobilier',
        'admin' => 'Administrateur'
    ];
    
    // Configuration notifications
    const NOTIFICATION_TYPES = [
        'paiement' => 'Paiement',
        'location' => 'Location',
        'visite' => 'Visite',
        'message' => 'Message',
        'systeme' => 'Système'
    ];
    
    /**
     * Retourne la configuration de la base de données
     */
    public static function getDatabaseConfig() {
        return [
            'host' => self::DB_HOST,
            'name' => self::DB_NAME,
            'user' => self::DB_USER,
            'pass' => self::DB_PASS,
            'charset' => self::DB_CHARSET
        ];
    }
    
    /**
     * Retourne l'URL de base de l'application
     */
    public static function getBaseUrl() {
        return self::APP_URL;
    }
    
    /**
     * Retourne le chemin d'upload
     */
    public static function getUploadPath() {
        return self::UPLOAD_PATH;
    }
    
    /**
     * Vérifie si un type de fichier est autorisé
     */
    public static function isAllowedFileType($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, self::ALLOWED_IMAGE_TYPES);
    }
    
    /**
     * Formate un montant en monnaie locale
     */
    public static function formatMoney($amount) {
        return number_format($amount, 0, ',', ' ') . ' ' . self::CURRENCY_SYMBOL;
    }
    
    /**
     * Génère une URL sécurisée
     */
    public static function generateSecureUrl($path) {
        return self::APP_URL . '/' . ltrim($path, '/');
    }
    
    /**
     * Nettoie une entrée utilisateur
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valide un email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Génère un token CSRF
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérifie un token CSRF
     */
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Envoie un email (utilise PHP mail par défaut)
     */
    public static function sendEmail($to, $subject, $message, $headers = []) {
        $default_headers = [
            'From: ' . self::FROM_NAME . ' <' . self::FROM_EMAIL . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        $headers = array_merge($default_headers, $headers);
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Journalise une erreur
     */
    public static function logError($message, $context = []) {
        $log_file = __DIR__ . '/../logs/error.log';
        $timestamp = date('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $log_entry = "[{$timestamp}] ERROR: {$message}{$context_str}\n";
        
        error_log($log_entry, 3, $log_file);
    }
    
    /**
     * Journalise une information
     */
    public static function logInfo($message, $context = []) {
        $log_file = __DIR__ . '/../logs/info.log';
        $timestamp = date('Y-m-d H:i:s');
        $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $log_entry = "[{$timestamp}] INFO: {$message}{$context_str}\n";
        
        error_log($log_entry, 3, $log_file);
    }
}
?>
