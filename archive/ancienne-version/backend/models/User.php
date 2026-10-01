<?php
/**
 * Modèle Utilisateur
 */

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $password;
    public $telephone;
    public $type_utilisateur;
    public $photo_profil;
    public $statut;
    public $date_inscription;
    public $last_login;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Créer un nouvel utilisateur
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET nom = :nom, 
                    prenom = :prenom, 
                    email = :email, 
                    password = :password, 
                    telephone = :telephone, 
                    type_utilisateur = :type_utilisateur,
                    photo_profil = :photo_profil";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyage et sécurisation
        $this->nom = Utils::cleanString($this->nom);
        $this->prenom = Utils::cleanString($this->prenom);
        $this->email = Utils::cleanString($this->email);
        $this->password = Utils::hashPassword($this->password);
        $this->telephone = Utils::formatPhone($this->telephone);
        $this->type_utilisateur = Utils::cleanString($this->type_utilisateur);
        
        // Liaison des paramètres
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":prenom", $this->prenom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":type_utilisateur", $this->type_utilisateur);
        $stmt->bindParam(":photo_profil", $this->photo_profil);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    /**
     * Connexion utilisateur
     */
    public function login($email, $password) {
        $query = "SELECT id, nom, prenom, email, password, telephone, type_utilisateur, 
                         photo_profil, statut, last_login
                FROM " . $this->table_name . " 
                WHERE email = :email AND statut = 'actif'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row && Utils::verifyPassword($password, $row['password'])) {
            // Mettre à jour last_login
            $this->updateLastLogin($row['id']);
            
            // Assigner les propriétés
            foreach($row as $key => $value) {
                $this->$key = $value;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour la dernière connexion
     */
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                SET last_login = CURRENT_TIMESTAMP 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
    }
    
    /**
     * Lire un utilisateur par ID
     */
    public function readOne() {
        $query = "SELECT id, nom, prenom, email, telephone, type_utilisateur, 
                         photo_profil, statut, date_inscription, last_login
                FROM " . $this->table_name . " 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            foreach($row as $key => $value) {
                $this->$key = $value;
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET nom = :nom, 
                    prenom = :prenom, 
                    email = :email, 
                    telephone = :telephone, 
                    type_utilisateur = :type_utilisateur,
                    photo_profil = :photo_profil
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyage
        $this->nom = Utils::cleanString($this->nom);
        $this->prenom = Utils::cleanString($this->prenom);
        $this->email = Utils::cleanString($this->email);
        $this->telephone = Utils::formatPhone($this->telephone);
        $this->type_utilisateur = Utils::cleanString($this->type_utilisateur);
        
        $stmt->bindParam(":nom", $this->nom);
        $stmt->bindParam(":prenom", $this->prenom);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":type_utilisateur", $this->type_utilisateur);
        $stmt->bindParam(":photo_profil", $this->photo_profil);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword($new_password) {
        $query = "UPDATE " . $this->table_name . "
                SET password = :password 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = Utils::hashPassword($new_password);
        
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Vérifier si l'email existe déjà
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Lister les utilisateurs
     */
    public function readAll($page = 1, $per_page = 20, $search = '') {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT id, nom, prenom, email, telephone, type_utilisateur, 
                         photo_profil, statut, date_inscription, last_login
                FROM " . $this->table_name;
        
        if(!empty($search)) {
            $query .= " WHERE nom LIKE :search OR prenom LIKE :search OR email LIKE :search";
        }
        
        $query .= " ORDER BY date_inscription DESC LIMIT :offset, :per_page";
        
        $stmt = $this->conn->prepare($query);
        
        if(!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(":search", $search_param);
        }
        
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":per_page", $per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compter le nombre total d'utilisateurs
     */
    public function countAll($search = '') {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        if(!empty($search)) {
            $query .= " WHERE nom LIKE :search OR prenom LIKE :search OR email LIKE :search";
        }
        
        $stmt = $this->conn->prepare($query);
        
        if(!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(":search", $search_param);
        }
        
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleStatus($statut) {
        $query = "UPDATE " . $this->table_name . "
                SET statut = :statut 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":statut", $statut);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Obtenir les propriétés d'un utilisateur
     */
    public function getProperties() {
        $query = "SELECT p.* FROM properties p 
                WHERE p.proprietaire_id = :id 
                ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtenir les locations d'un utilisateur (en tant que locataire)
     */
    public function getRentals() {
        $query = "SELECT r.*, p.titre, p.adresse, p.commune 
                FROM rentals r 
                JOIN properties p ON r.property_id = p.id 
                WHERE r.locataire_id = :id 
                ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtenir les statistiques d'un utilisateur propriétaire
     */
    public function getOwnerStats() {
        $query = "SELECT 
                    COUNT(DISTINCT p.id) as total_properties,
                    COUNT(DISTINCT CASE WHEN p.statut_occupation = 'occupe' THEN p.id END) as occupied_properties,
                    COUNT(DISTINCT CASE WHEN p.statut_occupation = 'vacant' THEN p.id END) as vacant_properties,
                    COALESCE(SUM(CASE WHEN r.statut_contrat = 'actif' THEN r.loyer_mensuel END), 0) as monthly_revenue,
                    COALESCE(SUM(p.prix), 0) as total_property_value
                FROM properties p 
                LEFT JOIN rentals r ON p.id = r.property_id AND r.statut_contrat = 'actif'
                WHERE p.proprietaire_id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Rechercher des utilisateurs par type
     */
    public function findByType($type, $limit = 10) {
        $query = "SELECT id, nom, prenom, email, telephone, photo_profil
                FROM " . $this->table_name . " 
                WHERE type_utilisateur = :type AND statut = 'actif'
                ORDER BY nom, prenom
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtenir les agents immobiliers
     */
    public function getAgents() {
        return $this->findByType('agent', 50);
    }
    
    /**
     * Obtenir les propriétaires
     */
    public function getOwners($limit = 20) {
        return $this->findByType('proprietaire', $limit);
    }
    
    /**
     * Obtenir les locataires
     */
    public function getTenants($limit = 20) {
        return $this->findByType('locataire', $limit);
    }
}
?>
