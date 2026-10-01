-- Base de données Accueil Immo CI
-- Créée le: 2024-02-14
-- Version: 1.0.0

SET FOREIGN_KEY_CHECKS=0;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `type_utilisateur` enum('proprietaire','locataire','agent','admin') NOT NULL DEFAULT 'proprietaire',
  `photo_profil` varchar(255) DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('actif','inactif','suspendu') NOT NULL DEFAULT 'actif',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_type_utilisateur` (`type_utilisateur`),
  KEY `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des biens immobiliers
CREATE TABLE IF NOT EXISTS `properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `description` text,
  `type_bien` enum('appartement','villa','studio','duplex','terrain','bureau','commerce') NOT NULL,
  `statut_bien` enum('location','vente','gestion') NOT NULL,
  `prix` decimal(12,2) NOT NULL,
  `surface` decimal(8,2) DEFAULT NULL,
  `nombre_pieces` int(11) DEFAULT NULL,
  `nombre_chambres` int(11) DEFAULT NULL,
  `nombre_salles_bain` int(11) DEFAULT NULL,
  `etage` int(11) DEFAULT NULL,
  `adresse` text NOT NULL,
  `quartier` varchar(100) DEFAULT NULL,
  `commune` enum('cocody','marcory','plateau','riviera','bingerville','assinie','yopougon','treichville','autre') NOT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `pays` varchar(50) NOT NULL DEFAULT 'Côte d\'Ivoire',
  `annee_construction` int(4) DEFAULT NULL,
  `meuble` tinyint(1) NOT NULL DEFAULT 0,
  `climatisation` tinyint(1) NOT NULL DEFAULT 0,
  `parking` tinyint(1) NOT NULL DEFAULT 0,
  `piscine` tinyint(1) NOT NULL DEFAULT 0,
  `gardiennage` tinyint(1) NOT NULL DEFAULT 0,
  `groupe_electrogene` tinyint(1) NOT NULL DEFAULT 0,
  `images` json DEFAULT NULL,
  `proprietaire_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `statut_occupation` enum('occupe','vacant','en_vente') NOT NULL DEFAULT 'vacant',
  `date_disponibilite` date DEFAULT NULL,
  `frais_agence` decimal(8,2) DEFAULT NULL,
  `caution` decimal(10,2) DEFAULT NULL,
  `charges_mensuelles` decimal(8,2) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_proprietaire` (`proprietaire_id`),
  KEY `idx_agent` (`agent_id`),
  KEY `idx_type_bien` (`type_bien`),
  KEY `idx_statut_bien` (`statut_bien`),
  KEY `idx_commune` (`commune`),
  KEY `idx_prix` (`prix`),
  KEY `idx_featured` (`featured`),
  KEY `idx_active` (`active`),
  KEY `idx_statut_occupation` (`statut_occupation`),
  CONSTRAINT `fk_properties_proprietaire` FOREIGN KEY (`proprietaire_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_properties_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des locations
CREATE TABLE IF NOT EXISTS `rentals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `locataire_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `loyer_mensuel` decimal(10,2) NOT NULL,
  `caution` decimal(10,2) DEFAULT NULL,
  `charges_mensuelles` decimal(8,2) DEFAULT NULL,
  `mode_paiement` enum('virement','espece','cheque','mobile_money') NOT NULL DEFAULT 'virement',
  `frequence_paiement` enum('mensuel','trimestriel','semestriel','annuel') NOT NULL DEFAULT 'mensuel',
  `statut_contrat` enum('actif','termine','suspendu','resilie') NOT NULL DEFAULT 'actif',
  `conditions_particulieres` text,
  `documents` json DEFAULT NULL,
  `date_signature` date DEFAULT NULL,
  `renouvellement_auto` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_property` (`property_id`),
  KEY `idx_locataire` (`locataire_id`),
  KEY `idx_statut_contrat` (`statut_contrat`),
  KEY `idx_date_fin` (`date_fin`),
  CONSTRAINT `fk_rentals_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rentals_locataire` FOREIGN KEY (`locataire_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paiements
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rental_id` int(11) NOT NULL,
  `locataire_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `type_paiement` enum('loyer','caution','charges','frais_agence','penalite','entretien') NOT NULL,
  `date_paiement` date NOT NULL,
  `date_echeance` date NOT NULL,
  `mode_paiement` enum('virement','espece','cheque','mobile_money') NOT NULL,
  `statut_paiement` enum('en_attente','effectue','en_retard','partiel') NOT NULL DEFAULT 'en_attente',
  `reference_paiement` varchar(100) DEFAULT NULL,
  `preuve_paiement` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rental` (`rental_id`),
  KEY `idx_locataire` (`locataire_id`),
  KEY `idx_date_echeance` (`date_echeance`),
  KEY `idx_statut_paiement` (`statut_paiement`),
  CONSTRAINT `fk_payments_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_locataire` FOREIGN KEY (`locataire_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des demandes de contact
CREATE TABLE IF NOT EXISTS `contact_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `sujet` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `type_demande` enum('information','visite','devis','conciergerie','autre') NOT NULL DEFAULT 'information',
  `statut` enum('nouveau','en_cours','traite','archive') NOT NULL DEFAULT 'nouveau',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_property` (`property_id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_type_demande` (`type_demande`),
  CONSTRAINT `fk_contact_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des services conciergerie
CREATE TABLE IF NOT EXISTS `concierge_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_service` varchar(100) NOT NULL,
  `description` text,
  `prix_base` decimal(8,2) DEFAULT NULL,
  `type_service` enum('transfert','entretien','assistance','evenement','shopping') NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `images` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_service` (`type_service`),
  KEY `idx_disponible` (`disponible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des réservations conciergerie
CREATE TABLE IF NOT EXISTS `concierge_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `date_reservation` datetime NOT NULL,
  `statut_reservation` enum('en_attente','confirme','en_cours','termine','annule') NOT NULL DEFAULT 'en_attente',
  `prix_total` decimal(10,2) NOT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_statut_reservation` (`statut_reservation`),
  KEY `idx_date_reservation` (`date_reservation`),
  CONSTRAINT `fk_booking_service` FOREIGN KEY (`service_id`) REFERENCES `concierge_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type_notification` enum('paiement','location','visite','message','systeme') NOT NULL,
  `lue` tinyint(1) NOT NULL DEFAULT 0,
  `data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_utilisateur` (`utilisateur_id`),
  KEY `idx_type_notification` (`type_notification`),
  KEY `idx_lue` (`lue`),
  CONSTRAINT `fk_notification_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- Insertion des données de test
INSERT INTO `users` (`nom`, `prenom`, `email`, `password`, `telephone`, `type_utilisateur`, `statut`) VALUES
('Admin', 'Accueil', 'admin@accueilimmo.ci', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+2250777777777', 'admin', 'actif'),
('Koné', 'Mamadou', 'mamadou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+2250712345678', 'proprietaire', 'actif'),
('Touré', 'Amina', 'amina@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+2250798765432', 'locataire', 'actif'),
('Bamba', 'Yves', 'yves@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+2250765432109', 'agent', 'actif')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO `concierge_services` (`nom_service`, `description`, `prix_base`, `type_service`) VALUES
('Transfert Aéroport VIP', 'Transfert depuis l\'aéroport Félix-Houphouët-Boigny vers votre destination en véhicule de luxe', 25000.00, 'transfert'),
('Entretien Villa', 'Service complet de nettoyage et maintenance pour villa et appartement', 50000.00, 'entretien'),
('Assistant Personnel', 'Aide quotidienne et gestion des tâches personnelles et administratives', 75000.00, 'assistance'),
('Organisation Événement', 'Planification et coordination d\'événements privés et professionnels', 150000.00, 'evenement'),
('Personal Shopping', 'Accompagnement pour achats personnalisés dans les meilleurs commerces d\'Abidjan', 35000.00, 'shopping')
ON DUPLICATE KEY UPDATE nom_service = nom_service;
