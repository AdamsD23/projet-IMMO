<?php
/**
 * Script d'installation de la base de données
 */

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../utils/utils.php';

header('Content-Type: application/json');

class Setup {
    private $database;
    
    public function __construct() {
        $this->database = new Database();
    }
    
    /**
     * Créer la base de données et les tables
     */
    public function createDatabase() {
        try {
            // Créer la base de données si elle n'existe pas
            $conn = new PDO("mysql:host=" . Config::DB_HOST, Config::DB_USER, Config::DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "CREATE DATABASE IF NOT EXISTS " . Config::DB_NAME . " 
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $conn->exec($sql);
            
            // Se connecter à la base de données
            $this->database->getConnection();
            
            // Créer les tables
            $this->database->createTables();
            
            // Créer les dossiers nécessaires
            $this->createDirectories();
            
            Utils::jsonResponse([
                'message' => 'Base de données créée avec succès',
                'database' => Config::DB_NAME,
                'tables_created' => true
            ], 200);
            
        } catch (Exception $e) {
            Utils::jsonResponseError('Erreur lors de la création: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Insérer des données de test
     */
    public function insertTestData() {
        try {
            $this->database->getConnection();
            $this->database->insertTestData();
            
            Utils::jsonResponse([
                'message' => 'Données de test insérées avec succès'
            ], 200);
            
        } catch (Exception $e) {
            Utils::jsonResponseError('Erreur lors de l\'insertion: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Créer les dossiers nécessaires
     */
    private function createDirectories() {
        $directories = [
            '../uploads',
            '../uploads/properties',
            '../uploads/profiles',
            '../logs'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Vérifier l'installation
     */
    public function checkInstallation() {
        try {
            $conn = $this->database->getConnection();
            
            // Vérifier si les tables existent
            $tables = ['users', 'properties', 'rentals', 'payments', 'contact_requests'];
            $existing_tables = [];
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->rowCount() > 0) {
                    $existing_tables[] = $table;
                }
            }
            
            $is_installed = count($existing_tables) === count($tables);
            
            Utils::jsonResponse([
                'installed' => $is_installed,
                'tables' => $existing_tables,
                'total_tables' => count($tables),
                'database' => Config::DB_NAME
            ], 200);
            
        } catch (Exception $e) {
            Utils::jsonResponseError('Erreur lors de la vérification: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Réinitialiser la base de données
     */
    public function resetDatabase() {
        try {
            $conn = $this->database->getConnection();
            
            // Supprimer toutes les tables
            $tables = ['users', 'properties', 'rentals', 'payments', 'contact_requests', 
                      'concierge_services', 'concierge_bookings', 'notifications'];
            
            foreach ($tables as $table) {
                $conn->exec("DROP TABLE IF EXISTS $table");
            }
            
            // Recréer les tables
            $this->database->createTables();
            $this->database->insertTestData();
            
            Utils::jsonResponse([
                'message' => 'Base de données réinitialisée avec succès'
            ], 200);
            
        } catch (Exception $e) {
            Utils::jsonResponseError('Erreur lors de la réinitialisation: ' . $e->getMessage(), 500);
        }
    }
}

// Router
$setup = new Setup();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'database':
        $setup->createDatabase();
        break;
    case 'test-data':
        $setup->insertTestData();
        break;
    case 'check':
        $setup->checkInstallation();
        break;
    case 'reset':
        $setup->resetDatabase();
        break;
    default:
        Utils::jsonResponseError('Action non trouvée', 404);
}
?>
