<?php
/**
 * Configuration de la base de données pour Accueil Immo
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'accueil_immo';
    private $username = 'root';
    private $password = '123456789';
    private $charset = 'utf8mb4';
    
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8mb4");
            echo "✅ Connexion à la base de données réussie<br>";
        } catch(PDOException $exception) {
            echo "❌ Erreur de connexion: " . $exception->getMessage() . "<br>";
            echo "Détails de l'erreur:<br>";
            echo "Host: " . $this->host . "<br>";
            echo "Database: " . $this->db_name . "<br>";
            echo "Username: " . $this->username . "<br>";
            echo "Vérifiez que MySQL/XAMPP est démarré et que les identifiants sont corrects.<br>";
        }
        
        return $this->conn;
    }
    
    /**
     * Créer les tables de la base de données
     */
    public function createTables() {
        $conn = $this->getConnection();
        
        // Table utilisateurs
        $sql_users = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            telephone VARCHAR(20),
            type_utilisateur ENUM('proprietaire', 'locataire', 'agent', 'admin') DEFAULT 'proprietaire',
            photo_profil VARCHAR(255),
            date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Table biens immobiliers
        $sql_properties = "
        CREATE TABLE IF NOT EXISTS properties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titre VARCHAR(200) NOT NULL,
            description TEXT,
            type_bien ENUM('appartement', 'villa', 'studio', 'duplex', 'terrain', 'bureau', 'commerce') NOT NULL,
            statut_bien ENUM('location', 'vente', 'gestion') NOT NULL,
            prix DECIMAL(12,2) NOT NULL,
            surface DECIMAL(8,2),
            nombre_pieces INT,
            nombre_chambres INT,
            nombre_salles_bain INT,
            etage INT,
            adresse TEXT NOT NULL,
            quartier VARCHAR(100),
            commune ENUM('cocody', 'marcory', 'plateau', 'riviera', 'bingerville', 'assinie', 'yopougon', 'treichville', 'autre') NOT NULL,
            code_postal VARCHAR(10),
            pays VARCHAR(50) DEFAULT 'Côte d\'Ivoire',
            annee_construction INT,
            meuble BOOLEAN DEFAULT FALSE,
            climatisation BOOLEAN DEFAULT FALSE,
            parking BOOLEAN DEFAULT FALSE,
            piscine BOOLEAN DEFAULT FALSE,
            gardiennage BOOLEAN DEFAULT FALSE,
            groupe_electrogene BOOLEAN DEFAULT FALSE,
            images JSON,
            proprietaire_id INT NOT NULL,
            agent_id INT,
            statut_occupation ENUM('occupe', 'vacant', 'en_vente') DEFAULT 'vacant',
            date_disponibilite DATE,
            frais_agence DECIMAL(8,2),
            caution DECIMAL(10,2),
            charges_mensuelles DECIMAL(8,2),
            featured BOOLEAN DEFAULT FALSE,
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (proprietaire_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Table locations
        $sql_rentals = "
        CREATE TABLE IF NOT EXISTS rentals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            property_id INT NOT NULL,
            locataire_id INT NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            loyer_mensuel DECIMAL(10,2) NOT NULL,
            caution DECIMAL(10,2),
            charges_mensuelles DECIMAL(8,2),
            mode_paiement ENUM('virement', 'espece', 'cheque', 'mobile_money') DEFAULT 'virement',
            frequence_paiement ENUM('mensuel', 'trimestriel', 'semestriel', 'annuel') DEFAULT 'mensuel',
            statut_contrat ENUM('actif', 'termine', 'suspendu', 'resilie') DEFAULT 'actif',
            conditions_particulieres TEXT,
            documents JSON,
            date_signature DATE,
            renouvellement_auto BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            FOREIGN KEY (locataire_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Table paiements
        $sql_payments = "
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rental_id INT NOT NULL,
            locataire_id INT NOT NULL,
            montant DECIMAL(10,2) NOT NULL,
            type_paiement ENUM('loyer', 'caution', 'charges', 'frais_agence', 'penalite', 'entretien') NOT NULL,
            date_paiement DATE NOT NULL,
            date_echeance DATE NOT NULL,
            mode_paiement ENUM('virement', 'espece', 'cheque', 'mobile_money') NOT NULL,
            statut_paiement ENUM('en_attente', 'effectue', 'en_retard', 'partiel') DEFAULT 'en_attente',
            reference_paiement VARCHAR(100),
            preuve_paiement VARCHAR(255),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
            FOREIGN KEY (locataire_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Table demandes de contact
        $sql_contacts = "
        CREATE TABLE IF NOT EXISTS contact_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            telephone VARCHAR(20),
            sujet VARCHAR(200),
            message TEXT NOT NULL,
            property_id INT NULL,
            type_demande ENUM('information', 'visite', 'devis', 'conciergerie', 'autre') DEFAULT 'information',
            statut ENUM('nouveau', 'en_cours', 'traite', 'archive') DEFAULT 'nouveau',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Table services conciergerie
        $sql_concierge = "
        CREATE TABLE IF NOT EXISTS concierge_services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom_service VARCHAR(100) NOT NULL,
            description TEXT,
            prix_base DECIMAL(8,2),
            type_service ENUM('transfert', 'entretien', 'assistance', 'evenement', 'shopping') NOT NULL,
            disponible BOOLEAN DEFAULT TRUE,
            images JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Table réservations conciergerie
        $sql_concierge_bookings = "
        CREATE TABLE IF NOT EXISTS concierge_bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_id INT NOT NULL,
            client_id INT NOT NULL,
            date_reservation DATETIME NOT NULL,
            statut_reservation ENUM('en_attente', 'confirme', 'en_cours', 'termine', 'annule') DEFAULT 'en_attente',
            prix_total DECIMAL(10,2) NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES concierge_services(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Table notifications
        $sql_notifications = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            utilisateur_id INT NOT NULL,
            titre VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type_notification ENUM('paiement', 'location', 'visite', 'message', 'systeme') NOT NULL,
            lue BOOLEAN DEFAULT FALSE,
            data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        try {
            $conn->exec($sql_users);
            $conn->exec($sql_properties);
            $conn->exec($sql_rentals);
            $conn->exec($sql_payments);
            $conn->exec($sql_contacts);
            $conn->exec($sql_concierge);
            $conn->exec($sql_concierge_bookings);
            $conn->exec($sql_notifications);
            
            echo "Tables créées avec succès!";
        } catch(PDOException $exception) {
            echo "Erreur lors de la création des tables: " . $exception->getMessage();
        }
    }
    
    /**
     * Insérer des données de test
     */
    public function insertTestData() {
        $conn = $this->getConnection();
        
        // Utilisateur admin
        $sql_admin = "
        INSERT INTO users (nom, prenom, email, password, telephone, type_utilisateur, statut) 
        VALUES ('Admin', 'Accueil', 'admin@accueilimmo.ci', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+2250777777777', 'admin', 'actif')
        ON DUPLICATE KEY UPDATE email = email;
        ";
        
        // Services conciergerie
        $sql_services = "
        INSERT INTO concierge_services (nom_service, description, prix_base, type_service) VALUES
        ('Transfert Aéroport VIP', 'Transfert depuis l\'aéroport Félix-Houphouët-Boigny vers votre destination', 25000.00, 'transfert'),
        ('Entretien Villa', 'Service complet de nettoyage et maintenance pour villa', 50000.00, 'entretien'),
        ('Assistant Personnel', 'Aide quotidienne et gestion des tâches personnelles', 75000.00, 'assistance'),
        ('Organisation Événement', 'Planification et coordination d\'événements privés', 150000.00, 'evenement'),
        ('Personal Shopping', 'Accompagnement pour achats personnalisés', 35000.00, 'shopping')
        ON DUPLICATE KEY UPDATE nom_service = nom_service;
        ";
        
        try {
            $conn->exec($sql_admin);
            $conn->exec($sql_services);
            echo "Données de test insérées avec succès!";
        } catch(PDOException $exception) {
            echo "Erreur lors de l'insertion des données: " . $exception->getMessage();
        }
    }
}
?>
