<?php
/**
 * Contrôleur pour la gestion des biens immobiliers
 */

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../models/Property.php';
require_once '../models/User.php';
require_once '../utils/utils.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class PropertyController {
    private $db;
    private $property;
    private $user;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->property = new Property($this->db);
        $this->user = new User($this->db);
    }
    
    /**
     * Vérifier l'authentification
     */
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            Utils::jsonResponseError('Non authentifié', 401);
        }
        return $_SESSION['user_id'];
    }
    
    /**
     * Créer un nouveau bien
     */
    public function create() {
        $user_id = $this->checkAuth();
        
        if (!Utils::isMethod('POST')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        $data = Utils::getInputData();
        
        // Validation des champs requis
        $required_fields = ['titre', 'type_bien', 'statut_bien', 'prix', 'adresse', 'commune'];
        $errors = Utils::validateRequired($data, $required_fields);
        
        if (!empty($errors)) {
            Utils::jsonResponseError('Champs requis manquants', 400, $errors);
        }
        
        // Validation des types
        if (!in_array($data['type_bien'], array_keys(Config::PROPERTY_TYPES))) {
            Utils::jsonResponseError('Type de bien invalide', 400);
        }
        
        if (!in_array($data['statut_bien'], array_keys(Config::PROPERTY_STATUS))) {
            Utils::jsonResponseError('Statut de bien invalide', 400);
        }
        
        // Assigner les valeurs
        $this->property->titre = $data['titre'];
        $this->property->description = $data['description'] ?? '';
        $this->property->type_bien = $data['type_bien'];
        $this->property->statut_bien = $data['statut_bien'];
        $this->property->prix = $data['prix'];
        $this->property->surface = $data['surface'] ?? null;
        $this->property->nombre_pieces = $data['nombre_pieces'] ?? null;
        $this->property->nombre_chambres = $data['nombre_chambres'] ?? null;
        $this->property->nombre_salles_bain = $data['nombre_salles_bain'] ?? null;
        $this->property->etage = $data['etage'] ?? null;
        $this->property->adresse = $data['adresse'];
        $this->property->quartier = $data['quartier'] ?? '';
        $this->property->commune = $data['commune'];
        $this->property->code_postal = $data['code_postal'] ?? '';
        $this->property->pays = $data['pays'] ?? Config::DEFAULT_COUNTRY;
        $this->property->annee_construction = $data['annee_construction'] ?? null;
        $this->property->meuble = $data['meuble'] ?? false;
        $this->property->climatisation = $data['climatisation'] ?? false;
        $this->property->parking = $data['parking'] ?? false;
        $this->property->piscine = $data['piscine'] ?? false;
        $this->property->gardiennage = $data['gardiennage'] ?? false;
        $this->property->groupe_electrogene = $data['groupe_electrogene'] ?? false;
        $this->property->images = $data['images'] ?? [];
        $this->property->proprietaire_id = $user_id;
        $this->property->agent_id = $data['agent_id'] ?? null;
        $this->property->statut_occupation = $data['statut_occupation'] ?? 'vacant';
        $this->property->date_disponibilite = $data['date_disponibilite'] ?? null;
        $this->property->frais_agence = $data['frais_agence'] ?? null;
        $this->property->caution = $data['caution'] ?? null;
        $this->property->charges_mensuelles = $data['charges_mensuelles'] ?? null;
        $this->property->featured = $data['featured'] ?? false;
        
        if ($this->property->create()) {
            Utils::jsonResponse([
                'property' => [
                    'id' => $this->property->id,
                    'titre' => $this->property->titre,
                    'type_bien' => $this->property->type_bien,
                    'statut_bien' => $this->property->statut_bien,
                    'prix' => $this->property->prix,
                    'adresse' => $this->property->adresse,
                    'commune' => $this->property->commune
                ]
            ], 201, 'Bien créé avec succès');
        } else {
            Utils::jsonResponseError('Erreur lors de la création du bien', 500);
        }
    }
    
    /**
     * Lire un bien spécifique
     */
    public function read($id) {
        $this->property->id = $id;
        
        if ($this->property->readOne()) {
            Utils::jsonResponse([
                'property' => [
                    'id' => $this->property->id,
                    'titre' => $this->property->titre,
                    'description' => $this->property->description,
                    'type_bien' => $this->property->type_bien,
                    'statut_bien' => $this->property->statut_bien,
                    'prix' => $this->property->prix,
                    'surface' => $this->property->surface,
                    'nombre_pieces' => $this->property->nombre_pieces,
                    'nombre_chambres' => $this->property->nombre_chambres,
                    'nombre_salles_bain' => $this->property->nombre_salles_bain,
                    'etage' => $this->property->etage,
                    'adresse' => $this->property->adresse,
                    'quartier' => $this->property->quartier,
                    'commune' => $this->property->commune,
                    'meuble' => $this->property->meuble,
                    'climatisation' => $this->property->climatisation,
                    'parking' => $this->property->parking,
                    'piscine' => $this->property->piscine,
                    'gardiennage' => $this->property->gardiennage,
                    'groupe_electrogene' => $this->property->groupe_electrogene,
                    'images' => $this->property->images,
                    'statut_occupation' => $this->property->statut_occupation,
                    'date_disponibilite' => $this->property->date_disponibilite,
                    'frais_agence' => $this->property->frais_agence,
                    'caution' => $this->property->caution,
                    'charges_mensuelles' => $this->property->charges_mensuelles,
                    'featured' => $this->property->featured,
                    'proprietaire' => [
                        'nom' => $this->property->proprietaire_nom,
                        'prenom' => $this->property->proprietaire_prenom,
                        'telephone' => $this->property->proprietaire_telephone,
                        'email' => $this->property->proprietaire_email
                    ]
                ]
            ], 200);
        } else {
            Utils::jsonResponseError('Bien non trouvé', 404);
        }
    }
    
    /**
     * Mettre à jour un bien
     */
    public function update($id) {
        $user_id = $this->checkAuth();
        
        if (!Utils::isMethod('PUT')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        $this->property->id = $id;
        
        // Vérifier que le bien existe
        if (!$this->property->readOne()) {
            Utils::jsonResponseError('Bien non trouvé', 404);
        }
        
        // Vérifier que l'utilisateur est le propriétaire ou un admin
        if ($this->property->proprietaire_id != $user_id && $_SESSION['user_type'] !== 'admin') {
            Utils::jsonResponseError('Non autorisé', 403);
        }
        
        $data = Utils::getInputData();
        
        // Mettre à jour les champs fournis
        if (isset($data['titre'])) $this->property->titre = Utils::cleanString($data['titre']);
        if (isset($data['description'])) $this->property->description = Utils::cleanString($data['description']);
        if (isset($data['prix'])) $this->property->prix = $data['prix'];
        if (isset($data['surface'])) $this->property->surface = $data['surface'];
        if (isset($data['nombre_pieces'])) $this->property->nombre_pieces = $data['nombre_pieces'];
        if (isset($data['nombre_chambres'])) $this->property->nombre_chambres = $data['nombre_chambres'];
        if (isset($data['nombre_salles_bain'])) $this->property->nombre_salles_bain = $data['nombre_salles_bain'];
        if (isset($data['etage'])) $this->property->etage = $data['etage'];
        if (isset($data['adresse'])) $this->property->adresse = Utils::cleanString($data['adresse']);
        if (isset($data['quartier'])) $this->property->quartier = Utils::cleanString($data['quartier']);
        if (isset($data['meuble'])) $this->property->meuble = $data['meuble'];
        if (isset($data['climatisation'])) $this->property->climatisation = $data['climatisation'];
        if (isset($data['parking'])) $this->property->parking = $data['parking'];
        if (isset($data['piscine'])) $this->property->piscine = $data['piscine'];
        if (isset($data['gardiennage'])) $this->property->gardiennage = $data['gardiennage'];
        if (isset($data['groupe_electrogene'])) $this->property->groupe_electrogene = $data['groupe_electrogene'];
        if (isset($data['images'])) $this->property->images = $data['images'];
        if (isset($data['statut_occupation'])) $this->property->statut_occupation = $data['statut_occupation'];
        if (isset($data['date_disponibilite'])) $this->property->date_disponibilite = $data['date_disponibilite'];
        if (isset($data['frais_agence'])) $this->property->frais_agence = $data['frais_agence'];
        if (isset($data['caution'])) $this->property->caution = $data['caution'];
        if (isset($data['charges_mensuelles'])) $this->property->charges_mensuelles = $data['charges_mensuelles'];
        if (isset($data['featured'])) $this->property->featured = $data['featured'];
        
        if ($this->property->update()) {
            Utils::jsonResponse([
                'property' => [
                    'id' => $this->property->id,
                    'titre' => $this->property->titre,
                    'prix' => $this->property->prix
                ]
            ], 200, 'Bien mis à jour avec succès');
        } else {
            Utils::jsonResponseError('Erreur lors de la mise à jour', 500);
        }
    }
    
    /**
     * Supprimer un bien
     */
    public function delete($id) {
        $user_id = $this->checkAuth();
        
        if (!Utils::isMethod('DELETE')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        $this->property->id = $id;
        
        // Vérifier que le bien existe
        if (!$this->property->readOne()) {
            Utils::jsonResponseError('Bien non trouvé', 404);
        }
        
        // Vérifier que l'utilisateur est le propriétaire ou un admin
        if ($this->property->proprietaire_id != $user_id && $_SESSION['user_type'] !== 'admin') {
            Utils::jsonResponseError('Non autorisé', 403);
        }
        
        if ($this->property->delete()) {
            Utils::jsonResponse([], 200, 'Bien supprimé avec succès');
        } else {
            Utils::jsonResponseError('Erreur lors de la suppression', 500);
        }
    }
    
    /**
     * Lister les biens
     */
    public function list() {
        // Récupérer les filtres
        $filters = [
            'statut_bien' => $_GET['statut_bien'] ?? null,
            'type_bien' => $_GET['type_bien'] ?? null,
            'commune' => $_GET['commune'] ?? null,
            'quartier' => $_GET['quartier'] ?? null,
            'prix_min' => $_GET['prix_min'] ?? null,
            'prix_max' => $_GET['prix_max'] ?? null,
            'surface_min' => $_GET['surface_min'] ?? null,
            'surface_max' => $_GET['surface_max'] ?? null,
            'proprietaire_id' => $_GET['proprietaire_id'] ?? null,
            'statut_occupation' => $_GET['statut_occupation'] ?? null,
            'meuble' => $_GET['meuble'] ?? null,
            'climatisation' => $_GET['climatisation'] ?? null,
            'parking' => $_GET['parking'] ?? null,
            'piscine' => $_GET['piscine'] ?? null,
            'gardiennage' => $_GET['gardiennage'] ?? null,
            'groupe_electrogene' => $_GET['groupe_electrogene'] ?? null
        ];
        
        // Nettoyer les filtres
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(Config::MAX_PAGE_SIZE, max(1, intval($_GET['per_page'] ?? Config::DEFAULT_PAGE_SIZE)));
        
        // Récupérer les biens
        $properties = $this->property->readAll($filters, $page, $per_page);
        $total = $this->property->countAll($filters);
        
        Utils::jsonResponse([
            'properties' => $properties,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => ceil($total / $per_page)
            ]
        ], 200);
    }
    
    /**
     * Rechercher des biens
     */
    public function search() {
        $search_term = $_GET['q'] ?? '';
        
        if (empty($search_term)) {
            Utils::jsonResponseError('Terme de recherche requis', 400);
        }
        
        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(Config::MAX_PAGE_SIZE, max(1, intval($_GET['per_page'] ?? Config::DEFAULT_PAGE_SIZE)));
        
        // Rechercher
        $properties = $this->property->search($search_term, $page, $per_page);
        $total = $this->property->countSearch($search_term);
        
        Utils::jsonResponse([
            'properties' => $properties,
            'search_term' => $search_term,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => ceil($total / $per_page)
            ]
        ], 200);
    }
    
    /**
     * Obtenir les biens en vedette
     */
    public function featured() {
        $limit = min(20, max(1, intval($_GET['limit'] ?? 6)));
        
        $properties = $this->property->getFeatured($limit);
        
        Utils::jsonResponse([
            'properties' => $properties
        ], 200);
    }
    
    /**
     * Obtenir les biens d'un utilisateur
     */
    public function myProperties() {
        $user_id = $this->checkAuth();
        
        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $per_page = min(Config::MAX_PAGE_SIZE, max(1, intval($_GET['per_page'] ?? Config::DEFAULT_PAGE_SIZE)));
        
        $filters = ['proprietaire_id' => $user_id];
        $properties = $this->property->readAll($filters, $page, $per_page);
        $total = $this->property->countAll($filters);
        
        Utils::jsonResponse([
            'properties' => $properties,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => ceil($total / $per_page)
            ]
        ], 200);
    }
    
    /**
     * Mettre à jour le statut d'occupation
     */
    public function updateOccupancy($id) {
        $user_id = $this->checkAuth();
        
        if (!Utils::isMethod('PUT')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        $data = Utils::getInputData();
        
        if (!isset($data['statut_occupation'])) {
            Utils::jsonResponseError('Statut d\'occupation requis', 400);
        }
        
        $this->property->id = $id;
        
        // Vérifier que le bien existe
        if (!$this->property->readOne()) {
            Utils::jsonResponseError('Bien non trouvé', 404);
        }
        
        // Vérifier que l'utilisateur est le propriétaire ou un admin
        if ($this->property->proprietaire_id != $user_id && $_SESSION['user_type'] !== 'admin') {
            Utils::jsonResponseError('Non autorisé', 403);
        }
        
        if ($this->property->updateOccupancyStatus($data['statut_occupation'])) {
            Utils::jsonResponse([], 200, 'Statut d\'occupation mis à jour');
        } else {
            Utils::jsonResponseError('Erreur lors de la mise à jour', 500);
        }
    }
    
    /**
     * Ajouter/retirer des biens en vedette (admin seulement)
     */
    public function toggleFeatured($id) {
        $user_id = $this->checkAuth();
        
        if ($_SESSION['user_type'] !== 'admin') {
            Utils::jsonResponseError('Non autorisé', 403);
        }
        
        if (!Utils::isMethod('PUT')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        $this->property->id = $id;
        
        // Vérifier que le bien existe
        if (!$this->property->readOne()) {
            Utils::jsonResponseError('Bien non trouvé', 404);
        }
        
        if ($this->property->toggleFeatured()) {
            Utils::jsonResponse([], 200, 'Statut featured mis à jour');
        } else {
            Utils::jsonResponseError('Erreur lors de la mise à jour', 500);
        }
    }
    
    /**
     * Obtenir les statistiques des biens
     */
    public function stats() {
        $stats = $this->property->getStats();
        
        Utils::jsonResponse([
            'stats' => $stats
        ], 200);
    }
    
    /**
     * Télécharger des images
     */
    public function uploadImages() {
        $user_id = $this->checkAuth();
        
        if (!Utils::isMethod('POST')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        if (!isset($_FILES['images'])) {
            Utils::jsonResponseError('Aucun fichier téléchargé', 400);
        }
        
        $uploaded_images = [];
        $files = $_FILES['images'];
        
        // Gérer plusieurs fichiers
        if (is_array($files['name'])) {
            $file_count = count($files['name']);
            
            for ($i = 0; $i < $file_count; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                try {
                    $image_path = Utils::uploadImage($file, 'properties');
                    $uploaded_images[] = $image_path;
                } catch (Exception $e) {
                    Utils::jsonResponseError('Erreur lors du téléchargement: ' . $e->getMessage(), 400);
                }
            }
        } else {
            // Un seul fichier
            try {
                $image_path = Utils::uploadImage($files, 'properties');
                $uploaded_images[] = $image_path;
            } catch (Exception $e) {
                Utils::jsonResponseError('Erreur lors du téléchargement: ' . $e->getMessage(), 400);
            }
        }
        
        Utils::jsonResponse([
            'images' => $uploaded_images
        ], 200, 'Images téléchargées avec succès');
    }
}

// Router
$controller = new PropertyController();
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'list':
        $controller->list();
        break;
    case 'search':
        $controller->search();
        break;
    case 'featured':
        $controller->featured();
        break;
    case 'my':
        $controller->myProperties();
        break;
    case 'stats':
        $controller->stats();
        break;
    case 'upload':
        $controller->uploadImages();
        break;
    case 'occupancy':
        if ($id) $controller->updateOccupancy($id);
        else Utils::jsonResponseError('ID requis', 400);
        break;
    case 'toggle-featured':
        if ($id) $controller->toggleFeatured($id);
        else Utils::jsonResponseError('ID requis', 400);
        break;
    default:
        // Actions RESTful
        $method = $_SERVER['REQUEST_METHOD'];
        if ($id) {
            switch ($method) {
                case 'GET':
                    $controller->read($id);
                    break;
                case 'PUT':
                    $controller->update($id);
                    break;
                case 'DELETE':
                    $controller->delete($id);
                    break;
                default:
                    Utils::jsonResponseError('Méthode non autorisée', 405);
            }
        } else {
            Utils::jsonResponseError('Action non trouvée', 404);
        }
}
?>
