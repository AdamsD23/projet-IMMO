<?php
/**
 * Création des tables et données de démonstration.
 * Lancement manuel : php api/setup.php
 * L'API l'exécute aussi automatiquement une fois par démarrage du serveur.
 */

require_once __DIR__ . '/lib.php';

function ensure_schema(): void {
    // Un fichier témoin évite de refaire la vérification à chaque requête
    $flag = sys_get_temp_dir() . '/accueil-immo-schema-' . md5(DB_HOST . DB_NAME) . '-v2';
    if (is_file($flag)) return;

    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        telephone VARCHAR(20),
        type_utilisateur ENUM('proprietaire','agent','locataire','admin') NOT NULL DEFAULT 'proprietaire',
        statut ENUM('actif','suspendu') NOT NULL DEFAULT 'actif',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS properties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(150) NOT NULL,
        description TEXT,
        type_bien VARCHAR(20) NOT NULL,
        statut_bien VARCHAR(10) NOT NULL,
        prix BIGINT NOT NULL,
        surface INT,
        chambres TINYINT,
        salles_bain TINYINT,
        commune VARCHAR(30) NOT NULL,
        quartier VARCHAR(100),
        meuble BOOLEAN NOT NULL DEFAULT FALSE,
        climatisation BOOLEAN NOT NULL DEFAULT FALSE,
        parking BOOLEAN NOT NULL DEFAULT FALSE,
        piscine BOOLEAN NOT NULL DEFAULT FALSE,
        gardiennage BOOLEAN NOT NULL DEFAULT FALSE,
        groupe_electrogene BOOLEAN NOT NULL DEFAULT FALSE,
        featured BOOLEAN NOT NULL DEFAULT FALSE,
        active BOOLEAN NOT NULL DEFAULT TRUE,
        vues INT NOT NULL DEFAULT 0,
        proprietaire_id INT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_recherche (statut_bien, commune, type_bien, prix),
        CONSTRAINT fk_prop_owner FOREIGN KEY (proprietaire_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Images : soit un fichier fourni avec le site (chemin), soit une image envoyée (stockée en base)
    $pdo->exec("CREATE TABLE IF NOT EXISTS images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NULL,
        owner_id INT NOT NULL,
        chemin VARCHAR(255) NULL,
        mime VARCHAR(30) NULL,
        data MEDIUMBLOB NULL,
        position TINYINT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_property (property_id),
        CONSTRAINT fk_img_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
        CONSTRAINT fk_img_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS concierge_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        description TEXT,
        prix_base INT,
        icone VARCHAR(40) NOT NULL DEFAULT 'room_service'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Demandes : visite ou information sur un bien, réservation de conciergerie, message général
    $pdo->exec("CREATE TABLE IF NOT EXISTS requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_demande ENUM('visite','information','conciergerie','contact') NOT NULL,
        property_id INT NULL,
        service_id INT NULL,
        destinataire_id INT NULL,
        nom VARCHAR(150) NOT NULL,
        telephone VARCHAR(20) NOT NULL,
        email VARCHAR(150),
        message TEXT,
        date_souhaitee DATE NULL,
        statut ENUM('nouveau','en_cours','traite') NOT NULL DEFAULT 'nouveau',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_dest (destinataire_id, statut),
        CONSTRAINT fk_req_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
        CONSTRAINT fk_req_service FOREIGN KEY (service_id) REFERENCES concierge_services(id) ON DELETE SET NULL,
        CONSTRAINT fk_req_dest FOREIGN KEY (destinataire_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_ip (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    seed_base();
    if (SEED_DEMO) seed_demo();

    @file_put_contents($flag, date('c'));
}

function seed_base(): void {
    $exists = query("SELECT id FROM users WHERE email = 'admin@accueilimmo.ci'")->fetch();
    if (!$exists) {
        query('INSERT INTO users (nom, prenom, email, password, telephone, type_utilisateur) VALUES (?, ?, ?, ?, ?, ?)', [
            'Admin', 'Accueil Immo', 'admin@accueilimmo.ci',
            password_hash(env('ADMIN_PASSWORD', 'admin123'), PASSWORD_DEFAULT), '+225 07 67 41 87 01', 'admin',
        ]);
    }

    $count = (int) query('SELECT COUNT(*) FROM concierge_services')->fetchColumn();
    if ($count === 0) {
        $services = [
            ['Transfert aéroport', 'Accueil à l\'aéroport Félix-Houphouët-Boigny et transfert jusqu\'à votre logement.', 25000, 'flight_land'],
            ['Ménage & entretien', 'Nettoyage complet et entretien régulier de votre villa ou appartement.', 30000, 'cleaning_services'],
            ['Gestion locative', 'Recherche de locataires, encaissement des loyers et suivi des travaux pendant votre absence.', 50000, 'real_estate_agent'],
            ['Déménagement', 'Organisation de votre emménagement : transport, montage et installation.', 75000, 'local_shipping'],
            ['Assistance administrative', 'Accompagnement pour les démarches : CIE, SODECI, internet, contrats.', 20000, 'assignment'],
        ];
        foreach ($services as $s) {
            query('INSERT INTO concierge_services (nom, description, prix_base, icone) VALUES (?, ?, ?, ?)', $s);
        }
    }
}

function seed_demo(): void {
    if ((int) query('SELECT COUNT(*) FROM properties')->fetchColumn() > 0) return;

    $pwd = password_hash('demo12345', PASSWORD_DEFAULT);
    $owners = [
        ['Koné', 'Mamadou', 'proprietaire@demo.ci', '+225 07 12 34 56 78', 'proprietaire'],
        ['Bamba', 'Yves', 'agent@demo.ci', '+225 05 65 43 21 09', 'agent'],
    ];
    $ownerIds = [];
    foreach ($owners as $o) {
        query('INSERT IGNORE INTO users (nom, prenom, email, password, telephone, type_utilisateur) VALUES (?, ?, ?, ?, ?, ?)',
            [$o[0], $o[1], $o[2], $pwd, $o[3], $o[4]]);
        $ownerIds[] = (int) query('SELECT id FROM users WHERE email = ?', [$o[2]])->fetchColumn();
    }

    // titre, description, type, statut, prix, surface, chambres, sdb, commune, quartier, équipements, vedette, images, propriétaire
    $biens = [
        ['Villa avec piscine à la Riviera Golf', 'Superbe villa de standing dans une résidence sécurisée, grand jardin arboré et piscine privée. Cuisine équipée, quatre chambres avec salle de bain, dépendance pour le personnel.', 'villa', 'location', 1500000, 450, 4, 4, 'riviera', 'Riviera Golf', ['climatisation', 'parking', 'piscine', 'gardiennage', 'groupe_electrogene'], true, ['villa-piscine', 'appartement-salon'], 0],
        ['Appartement meublé lumineux – Cocody Ambassades', 'Bel appartement entièrement meublé au 3e étage avec ascenseur, idéal pour expatriés. Proche des ambassades et des commerces.', 'appartement', 'location', 650000, 120, 2, 2, 'cocody', 'Ambassades', ['meuble', 'climatisation', 'parking', 'gardiennage'], true, ['appartement-lumineux', 'appartement-salon'], 1],
        ['Villa contemporaine 5 pièces – Cocody Angré', 'Villa neuve aux finitions modernes : grandes baies vitrées, séjour double, terrasse et garage pour deux voitures.', 'villa', 'vente', 185000000, 380, 4, 3, 'cocody', 'Angré 8e tranche', ['climatisation', 'parking', 'gardiennage'], true, ['villa-moderne', 'appartement-lumineux'], 0],
        ['Studio moderne – Plateau', 'Studio rénové en plein cœur du Plateau, à deux pas des bureaux et des banques. Parfait pour un jeune actif.', 'studio', 'location', 250000, 35, 1, 1, 'plateau', 'Centre des affaires', ['meuble', 'climatisation'], false, ['appartement-salon'], 1],
        ['Villa les pieds dans l\'eau – Assinie', 'Villa de vacances avec paillote, piscine et accès direct à la plage. Idéale pour la location saisonnière.', 'villa', 'vente', 250000000, 520, 5, 5, 'assinie', 'Assinie Mafia', ['meuble', 'climatisation', 'piscine', 'gardiennage', 'groupe_electrogene'], true, ['villa-paillote', 'villa-piscine'], 0],
        ['Duplex 4 pièces – Marcory Zone 4', 'Duplex spacieux dans une petite résidence calme de la Zone 4, proche des restaurants et des écoles internationales.', 'duplex', 'location', 900000, 200, 3, 3, 'marcory', 'Zone 4C', ['climatisation', 'parking', 'gardiennage'], false, ['appartement-lumineux', 'villa-moderne'], 1],
        ['Appartement 3 pièces – Riviera Palmeraie', 'Appartement familial bien entretenu, quartier résidentiel, accès facile au boulevard Mitterrand.', 'appartement', 'vente', 65000000, 110, 2, 2, 'riviera', 'Palmeraie', ['parking', 'gardiennage'], false, ['appartement-salon', 'appartement-lumineux'], 0],
        ['Terrain viabilisé 600 m² – Bingerville', 'Terrain avec ACD, viabilisé (eau, électricité), dans un lotissement en plein développement.', 'terrain', 'vente', 30000000, 600, null, null, 'bingerville', 'Cité Feh Kessé', [], false, ['abidjan-skyline'], 1],
        ['Bureau 80 m² – Plateau', 'Plateau de bureaux climatisé avec vue sur la lagune, open space et salle de réunion. Groupe électrogène de l\'immeuble.', 'bureau', 'location', 1200000, 80, null, 1, 'plateau', 'Avenue Chardy', ['climatisation', 'parking', 'gardiennage', 'groupe_electrogene'], false, ['abidjan-skyline', 'appartement-lumineux'], 1],
        ['Villa 4 pièces avec jardin – Yopougon', 'Villa basse avec jardin et garage dans un quartier calme de Yopougon, proche des commodités.', 'villa', 'location', 400000, 220, 3, 2, 'yopougon', 'Selmer', ['parking'], false, ['villa-moderne'], 0],
    ];

    foreach ($biens as $i => $b) {
        [$titre, $desc, $type, $statut, $prix, $surface, $chambres, $sdb, $commune, $quartier, $equip, $featured, $imgs, $ownerIndex] = $b;
        $flags = array_map(fn($e) => in_array($e, $equip, true) ? 1 : 0, EQUIPEMENTS);
        query('INSERT INTO properties (titre, description, type_bien, statut_bien, prix, surface, chambres, salles_bain, commune, quartier,
                meuble, climatisation, parking, piscine, gardiennage, groupe_electrogene, featured, vues, proprietaire_id, created_at)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))',
            array_merge([$titre, $desc, $type, $statut, $prix, $surface, $chambres, $sdb, $commune, $quartier], $flags,
                [$featured ? 1 : 0, 40 + $i * 17, $ownerIds[$ownerIndex], $i * 3]));
        $propertyId = (int) db()->lastInsertId();
        foreach ($imgs as $pos => $img) {
            query('INSERT INTO images (property_id, owner_id, chemin, position) VALUES (?, ?, ?, ?)',
                [$propertyId, $ownerIds[$ownerIndex], "assets/img/$img.jpg", $pos]);
        }
    }

    // Quelques demandes reçues pour que l'espace propriétaire ne soit pas vide
    $first = (int) query('SELECT MIN(id) FROM properties')->fetchColumn();
    $demandes = [
        ['visite', $first, $ownerIds[0], 'Awa Touré', '+225 07 98 76 54 32', 'awa.toure@email.ci', 'Bonjour, je souhaiterais visiter la villa ce samedi matin.', '+3 day', 'nouveau'],
        ['information', $first + 1, $ownerIds[1], 'Jean-Marc Kouadio', '+225 05 11 22 33 44', null, 'Le loyer inclut-il les charges ? Merci.', null, 'en_cours'],
        ['visite', $first + 2, $ownerIds[0], 'Fatou Diallo', '+225 01 23 45 67 89', 'fatou.d@email.ci', 'Intéressée par l\'achat, possible de visiter en semaine ?', '+5 day', 'nouveau'],
    ];
    foreach ($demandes as $d) {
        query('INSERT INTO requests (type_demande, property_id, destinataire_id, nom, telephone, email, message, date_souhaitee, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], $d[7] ? date('Y-m-d', strtotime($d[7])) : null, $d[8]]);
    }
}

// Lancement en ligne de commande : php api/setup.php
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        // Crée la base si le compte en a le droit (en local)
        try {
            $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT), DB_USER, DB_PASSWORD);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (Throwable $e) {
            echo "Création de la base ignorée : {$e->getMessage()}\n";
        }
        @unlink(sys_get_temp_dir() . '/accueil-immo-schema-' . md5(DB_HOST . DB_NAME) . '-v2');
        ensure_schema();
        echo "✅ Base prête (" . DB_NAME . ")\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "❌ " . $e->getMessage() . "\n");
        exit(1);
    }
}
