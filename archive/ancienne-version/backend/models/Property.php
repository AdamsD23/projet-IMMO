<?php
/**
 * Modèle Bien Immobilier
 */

class Property {
    private $conn;
    private $table_name = "properties";
    
    public $id;
    public $titre;
    public $description;
    public $type_bien;
    public $statut_bien;
    public $prix;
    public $surface;
    public $nombre_pieces;
    public $nombre_chambres;
    public $nombre_salles_bain;
    public $etage;
    public $adresse;
    public $quartier;
    public $commune;
    public $code_postal;
    public $pays;
    public $annee_construction;
    public $meuble;
    public $climatisation;
    public $parking;
    public $piscine;
    public $gardiennage;
    public $groupe_electrogene;
    public $images;
    public $proprietaire_id;
    public $agent_id;
    public $statut_occupation;
    public $date_disponibilite;
    public $frais_agence;
    public $caution;
    public $charges_mensuelles;
    public $featured;
    public $active;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Créer un nouveau bien
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET titre = :titre, 
                    description = :description, 
                    type_bien = :type_bien, 
                    statut_bien = :statut_bien, 
                    prix = :prix, 
                    surface = :surface, 
                    nombre_pieces = :nombre_pieces, 
                    nombre_chambres = :nombre_chambres, 
                    nombre_salles_bain = :nombre_salles_bain, 
                    etage = :etage, 
                    adresse = :adresse, 
                    quartier = :quartier, 
                    commune = :commune, 
                    code_postal = :code_postal, 
                    pays = :pays, 
                    annee_construction = :annee_construction, 
                    meuble = :meuble, 
                    climatisation = :climatisation, 
                    parking = :parking, 
                    piscine = :piscine, 
                    gardiennage = :gardiennage, 
                    groupe_electrogene = :groupe_electrogene, 
                    images = :images, 
                    proprietaire_id = :proprietaire_id, 
                    agent_id = :agent_id, 
                    statut_occupation = :statut_occupation, 
                    date_disponibilite = :date_disponibilite, 
                    frais_agence = :frais_agence, 
                    caution = :caution, 
                    charges_mensuelles = :charges_mensuelles, 
                    featured = :featured";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyage et conversion
        $this->titre = Utils::cleanString($this->titre);
        $this->description = Utils::cleanString($this->description);
        $this->adresse = Utils::cleanString($this->adresse);
        $this->quartier = Utils::cleanString($this->quartier);
        $this->code_postal = Utils::cleanString($this->code_postal);
        $this->pays = Utils::cleanString($this->pays);
        $this->images = is_array($this->images) ? json_encode($this->images) : $this->images;
        
        // Conversion des booléens
        $this->meuble = $this->meuble ? 1 : 0;
        $this->climatisation = $this->climatisation ? 1 : 0;
        $this->parking = $this->parking ? 1 : 0;
        $this->piscine = $this->piscine ? 1 : 0;
        $this->gardiennage = $this->gardiennage ? 1 : 0;
        $this->groupe_electrogene = $this->groupe_electrogene ? 1 : 0;
        $this->featured = $this->featured ? 1 : 0;
        
        // Liaison des paramètres
        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":type_bien", $this->type_bien);
        $stmt->bindParam(":statut_bien", $this->statut_bien);
        $stmt->bindParam(":prix", $this->prix);
        $stmt->bindParam(":surface", $this->surface);
        $stmt->bindParam(":nombre_pieces", $this->nombre_pieces);
        $stmt->bindParam(":nombre_chambres", $this->nombre_chambres);
        $stmt->bindParam(":nombre_salles_bain", $this->nombre_salles_bain);
        $stmt->bindParam(":etage", $this->etage);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":quartier", $this->quartier);
        $stmt->bindParam(":commune", $this->commune);
        $stmt->bindParam(":code_postal", $this->code_postal);
        $stmt->bindParam(":pays", $this->pays);
        $stmt->bindParam(":annee_construction", $this->annee_construction);
        $stmt->bindParam(":meuble", $this->meuble, PDO::PARAM_INT);
        $stmt->bindParam(":climatisation", $this->climatisation, PDO::PARAM_INT);
        $stmt->bindParam(":parking", $this->parking, PDO::PARAM_INT);
        $stmt->bindParam(":piscine", $this->piscine, PDO::PARAM_INT);
        $stmt->bindParam(":gardiennage", $this->gardiennage, PDO::PARAM_INT);
        $stmt->bindParam(":groupe_electrogene", $this->groupe_electrogene, PDO::PARAM_INT);
        $stmt->bindParam(":images", $this->images);
        $stmt->bindParam(":proprietaire_id", $this->proprietaire_id);
        $stmt->bindParam(":agent_id", $this->agent_id);
        $stmt->bindParam(":statut_occupation", $this->statut_occupation);
        $stmt->bindParam(":date_disponibilite", $this->date_disponibilite);
        $stmt->bindParam(":frais_agence", $this->frais_agence);
        $stmt->bindParam(":caution", $this->caution);
        $stmt->bindParam(":charges_mensuelles", $this->charges_mensuelles);
        $stmt->bindParam(":featured", $this->featured, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Lire un bien par ID
     */
    public function readOne() {
        $query = "SELECT p.*, u.nom as proprietaire_nom, u.prenom as proprietaire_prenom,
                         u.telephone as proprietaire_telephone, u.email as proprietaire_email
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.proprietaire_id = u.id
                WHERE p.id = :id AND p.active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Décoder les images JSON
            if(!empty($row['images'])) {
                $row['images'] = json_decode($row['images'], true);
            }
            
            foreach($row as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour un bien
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET titre = :titre, 
                    description = :description, 
                    type_bien = :type_bien, 
                    statut_bien = :statut_bien, 
                    prix = :prix, 
                    surface = :surface, 
                    nombre_pieces = :nombre_pieces, 
                    nombre_chambres = :nombre_chambres, 
                    nombre_salles_bain = :nombre_salles_bain, 
                    etage = :etage, 
                    adresse = :adresse, 
                    quartier = :quartier, 
                    commune = :commune, 
                    code_postal = :code_postal, 
                    annee_construction = :annee_construction, 
                    meuble = :meuble, 
                    climatisation = :climatisation, 
                    parking = :parking, 
                    piscine = :piscine, 
                    gardiennage = :gardiennage, 
                    groupe_electrogene = :groupe_electrogene, 
                    images = :images, 
                    statut_occupation = :statut_occupation, 
                    date_disponibilite = :date_disponibilite, 
                    frais_agence = :frais_agence, 
                    caution = :caution, 
                    charges_mensuelles = :charges_mensuelles, 
                    featured = :featured
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Préparation des données
        $this->titre = Utils::cleanString($this->titre);
        $this->description = Utils::cleanString($this->description);
        $this->adresse = Utils::cleanString($this->adresse);
        $this->quartier = Utils::cleanString($this->quartier);
        $this->code_postal = Utils::cleanString($this->code_postal);
        $this->images = is_array($this->images) ? json_encode($this->images) : $this->images;
        
        // Conversion des booléens
        $this->meuble = $this->meuble ? 1 : 0;
        $this->climatisation = $this->climatisation ? 1 : 0;
        $this->parking = $this->parking ? 1 : 0;
        $this->piscine = $this->piscine ? 1 : 0;
        $this->gardiennage = $this->gardiennage ? 1 : 0;
        $this->groupe_electrogene = $this->groupe_electrogene ? 1 : 0;
        $this->featured = $this->featured ? 1 : 0;
        
        // Liaison des paramètres
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":titre", $this->titre);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":type_bien", $this->type_bien);
        $stmt->bindParam(":statut_bien", $this->statut_bien);
        $stmt->bindParam(":prix", $this->prix);
        $stmt->bindParam(":surface", $this->surface);
        $stmt->bindParam(":nombre_pieces", $this->nombre_pieces);
        $stmt->bindParam(":nombre_chambres", $this->nombre_chambres);
        $stmt->bindParam(":nombre_salles_bain", $this->nombre_salles_bain);
        $stmt->bindParam(":etage", $this->etage);
        $stmt->bindParam(":adresse", $this->adresse);
        $stmt->bindParam(":quartier", $this->quartier);
        $stmt->bindParam(":commune", $this->commune);
        $stmt->bindParam(":code_postal", $this->code_postal);
        $stmt->bindParam(":annee_construction", $this->annee_construction);
        $stmt->bindParam(":meuble", $this->meuble, PDO::PARAM_INT);
        $stmt->bindParam(":climatisation", $this->climatisation, PDO::PARAM_INT);
        $stmt->bindParam(":parking", $this->parking, PDO::PARAM_INT);
        $stmt->bindParam(":piscine", $this->piscine, PDO::PARAM_INT);
        $stmt->bindParam(":gardiennage", $this->gardiennage, PDO::PARAM_INT);
        $stmt->bindParam(":groupe_electrogene", $this->groupe_electrogene, PDO::PARAM_INT);
        $stmt->bindParam(":images", $this->images);
        $stmt->bindParam(":statut_occupation", $this->statut_occupation);
        $stmt->bindParam(":date_disponibilite", $this->date_disponibilite);
        $stmt->bindParam(":frais_agence", $this->frais_agence);
        $stmt->bindParam(":caution", $this->caution);
        $stmt->bindParam(":charges_mensuelles", $this->charges_mensuelles);
        $stmt->bindParam(":featured", $this->featured, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer un bien (désactiver)
     */
    public function delete() {
        $query = "UPDATE " . $this->table_name . " SET active = 0 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Lister les biens avec filtres
     */
    public function readAll($filters = [], $page = 1, $per_page = 12) {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT p.*, u.nom as proprietaire_nom, u.prenom as proprietaire_prenom
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.proprietaire_id = u.id
                WHERE p.active = 1";
        
        $params = [];
        
        // Filtres
        if(!empty($filters['statut_bien'])) {
            $query .= " AND p.statut_bien = :statut_bien";
            $params[':statut_bien'] = $filters['statut_bien'];
        }
        
        if(!empty($filters['type_bien'])) {
            $query .= " AND p.type_bien = :type_bien";
            $params[':type_bien'] = $filters['type_bien'];
        }
        
        if(!empty($filters['commune'])) {
            $query .= " AND p.commune = :commune";
            $params[':commune'] = $filters['commune'];
        }
        
        if(!empty($filters['quartier'])) {
            $query .= " AND p.quartier LIKE :quartier";
            $params[':quartier'] = '%' . $filters['quartier'] . '%';
        }
        
        if(!empty($filters['prix_min'])) {
            $query .= " AND p.prix >= :prix_min";
            $params[':prix_min'] = $filters['prix_min'];
        }
        
        if(!empty($filters['prix_max'])) {
            $query .= " AND p.prix <= :prix_max";
            $params[':prix_max'] = $filters['prix_max'];
        }
        
        if(!empty($filters['surface_min'])) {
            $query .= " AND p.surface >= :surface_min";
            $params[':surface_min'] = $filters['surface_min'];
        }
        
        if(!empty($filters['surface_max'])) {
            $query .= " AND p.surface <= :surface_max";
            $params[':surface_max'] = $filters['surface_max'];
        }
        
        if(!empty($filters['proprietaire_id'])) {
            $query .= " AND p.proprietaire_id = :proprietaire_id";
            $params[':proprietaire_id'] = $filters['proprietaire_id'];
        }
        
        if(!empty($filters['statut_occupation'])) {
            $query .= " AND p.statut_occupation = :statut_occupation";
            $params[':statut_occupation'] = $filters['statut_occupation'];
        }
        
        // Filtres par équipements
        if(!empty($filters['meuble'])) {
            $query .= " AND p.meuble = 1";
        }
        
        if(!empty($filters['climatisation'])) {
            $query .= " AND p.climatisation = 1";
        }
        
        if(!empty($filters['parking'])) {
            $query .= " AND p.parking = 1";
        }
        
        if(!empty($filters['piscine'])) {
            $query .= " AND p.piscine = 1";
        }
        
        if(!empty($filters['gardiennage'])) {
            $query .= " AND p.gardiennage = 1";
        }
        
        if(!empty($filters['groupe_electrogene'])) {
            $query .= " AND p.groupe_electrogene = 1";
        }
        
        // Featured properties first
        $query .= " ORDER BY p.featured DESC, p.created_at DESC";
        
        // Pagination
        $query .= " LIMIT :offset, :per_page";
        
        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":per_page", $per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder les images JSON
        foreach($properties as &$property) {
            if(!empty($property['images'])) {
                $property['images'] = json_decode($property['images'], true);
            }
        }
        
        return $properties;
    }
    
    /**
     * Compter le nombre total de biens
     */
    public function countAll($filters = []) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE active = 1";
        
        $params = [];
        
        // Appliquer les mêmes filtres que readAll
        if(!empty($filters['statut_bien'])) {
            $query .= " AND statut_bien = :statut_bien";
            $params[':statut_bien'] = $filters['statut_bien'];
        }
        
        if(!empty($filters['type_bien'])) {
            $query .= " AND type_bien = :type_bien";
            $params[':type_bien'] = $filters['type_bien'];
        }
        
        if(!empty($filters['commune'])) {
            $query .= " AND commune = :commune";
            $params[':commune'] = $filters['commune'];
        }
        
        if(!empty($filters['prix_min'])) {
            $query .= " AND prix >= :prix_min";
            $params[':prix_min'] = $filters['prix_min'];
        }
        
        if(!empty($filters['prix_max'])) {
            $query .= " AND prix <= :prix_max";
            $params[':prix_max'] = $filters['prix_max'];
        }
        
        if(!empty($filters['proprietaire_id'])) {
            $query .= " AND proprietaire_id = :proprietaire_id";
            $params[':proprietaire_id'] = $filters['proprietaire_id'];
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    /**
     * Obtenir les biens en vedette
     */
    public function getFeatured($limit = 6) {
        $query = "SELECT p.*, u.nom as proprietaire_nom, u.prenom as proprietaire_prenom
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.proprietaire_id = u.id
                WHERE p.active = 1 AND p.featured = 1
                ORDER BY p.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($properties as &$property) {
            if(!empty($property['images'])) {
                $property['images'] = json_decode($property['images'], true);
            }
        }
        
        return $properties;
    }
    
    /**
     * Rechercher des biens
     */
    public function search($search_term, $page = 1, $per_page = 12) {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT p.*, u.nom as proprietaire_nom, u.prenom as proprietaire_prenom
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.proprietaire_id = u.id
                WHERE p.active = 1 AND (
                    p.titre LIKE :search OR 
                    p.description LIKE :search OR 
                    p.adresse LIKE :search OR 
                    p.quartier LIKE :search OR 
                    p.commune LIKE :search
                )
                ORDER BY p.featured DESC, p.created_at DESC
                LIMIT :offset, :per_page";
        
        $stmt = $this->conn->prepare($query);
        
        $search_param = "%{$search_term}%";
        $stmt->bindParam(":search", $search_param);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":per_page", $per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($properties as &$property) {
            if(!empty($property['images'])) {
                $property['images'] = json_decode($property['images'], true);
            }
        }
        
        return $properties;
    }
    
    /**
     * Compter les résultats de recherche
     */
    public function countSearch($search_term) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . "
                WHERE active = 1 AND (
                    titre LIKE :search OR 
                    description LIKE :search OR 
                    adresse LIKE :search OR 
                    quartier LIKE :search OR 
                    commune LIKE :search
                )";
        
        $stmt = $this->conn->prepare($query);
        
        $search_param = "%{$search_term}%";
        $stmt->bindParam(":search", $search_param);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    /**
     * Mettre à jour le statut d'occupation
     */
    public function updateOccupancyStatus($statut) {
        $query = "UPDATE " . $this->table_name . "
                SET statut_occupation = :statut_occupation
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":statut_occupation", $statut);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Ajouter/retirer des biens en vedette
     */
    public function toggleFeatured() {
        $query = "UPDATE " . $this->table_name . "
                SET featured = NOT featured
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Obtenir les statistiques des biens
     */
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total_properties,
                    COUNT(CASE WHEN statut_bien = 'location' THEN 1 END) as rental_properties,
                    COUNT(CASE WHEN statut_bien = 'vente' THEN 1 END) => sale_properties,
                    COUNT(CASE WHEN statut_bien = 'gestion' THEN 1 END) as managed_properties,
                    COUNT(CASE WHEN statut_occupation = 'occupe' THEN 1 END) as occupied_properties,
                    COUNT(CASE WHEN statut_occupation = 'vacant' THEN 1 END) as vacant_properties,
                    COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_properties,
                    AVG(prix) as avg_price,
                    AVG(surface) as avg_surface
                FROM " . $this->table_name . " 
                WHERE active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtenir les biens par commune
     */
    public function getByCommune($commune, $limit = 10) {
        $query = "SELECT p.*, u.nom as proprietaire_nom, u.prenom as proprietaire_prenom
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.proprietaire_id = u.id
                WHERE p.active = 1 AND p.commune = :commune
                ORDER BY p.featured DESC, p.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":commune", $commune);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach($properties as &$property) {
            if(!empty($property['images'])) {
                $property['images'] = json_decode($property['images'], true);
            }
        }
        
        return $properties;
    }
}
?>
