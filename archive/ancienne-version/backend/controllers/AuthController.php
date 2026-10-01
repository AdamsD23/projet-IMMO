<?php
/**
 * Contrôleur Authentification
 */

require_once '../config/database.php';
require_once '../config/config.php';
require_once '../models/User.php';
require_once '../utils/utils.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController {
    private $db;
    private $user;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }
    
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register() {
        if (!Utils::isMethod('POST')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        $data = Utils::getInputData();
        
        // Validation des champs requis
        $required_fields = ['nom', 'prenom', 'email', 'password', 'telephone'];
        $errors = Utils::validateRequired($data, $required_fields);
        
        if (!empty($errors)) {
            Utils::jsonResponseError('Champs requis manquants', 400, $errors);
        }
        
        // Validation email
        if (!Utils::validateEmail($data['email'])) {
            Utils::jsonResponseError('Email invalide', 400);
        }
        
        // Validation téléphone
        if (!Utils::validatePhone($data['telephone'])) {
            Utils::jsonResponseError('Numéro de téléphone invalide', 400);
        }
        
        // Validation mot de passe
        if (strlen($data['password']) < Config::PASSWORD_MIN_LENGTH) {
            Utils::jsonResponseError('Le mot de passe doit contenir au moins ' . Config::PASSWORD_MIN_LENGTH . ' caractères', 400);
        }
        
        // Vérifier si l'email existe déjà
        if ($this->user->emailExists($data['email'])) {
            Utils::jsonResponseError('Cet email est déjà utilisé', 409);
        }
        
        // Assigner les valeurs
        $this->user->nom = $data['nom'];
        $this->user->prenom = $data['prenom'];
        $this->user->email = $data['email'];
        $this->user->password = $data['password'];
        $this->user->telephone = $data['telephone'];
        $this->user->type_utilisateur = $data['type_utilisateur'] ?? 'proprietaire';
        $this->user->photo_profil = $data['photo_profil'] ?? null;
        
        // Créer l'utilisateur
        if ($this->user->create()) {
            // Envoyer un email de bienvenue
            $this->sendWelcomeEmail($this->user->email, $this->user->nom, $this->user->prenom);
            
            Utils::jsonResponse([
                'success' => true,
                'message' => 'Inscription réussie ! Bienvenue sur Accueil Immo CI.',
                'user' => [
                    'id' => $this->user->id,
                    'nom' => $this->user->nom,
                    'prenom' => $this->user->prenom,
                    'email' => $this->user->email,
                    'telephone' => $this->user->telephone,
                    'type_utilisateur' => $this->user->type_utilisateur,
                    'photo_profil' => $this->user->photo_profil
                ]
            ], 201, 'Inscription réussie');
        } else {
            Utils::jsonResponseError('Erreur lors de l\'inscription', 500);
        }
    }
    
    /**
     * Connexion utilisateur
     */
    public function login() {
        if (!Utils::isMethod('POST')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        $data = Utils::getInputData();
        
        // Validation des champs requis
        $required_fields = ['email', 'password'];
        $errors = Utils::validateRequired($data, $required_fields);
        
        if (!empty($errors)) {
            Utils::jsonResponseError('Champs requis manquants', 400, $errors);
        }
        
        // Tentative de connexion
        if ($this->user->login($data['email'], $data['password'])) {
            // Créer le token JWT (simplifié pour cet exemple)
            $token = $this->generateToken($this->user);
            
            // Stocker en session
            $_SESSION['user_id'] = $this->user->id;
            $_SESSION['user_email'] = $this->user->email;
            $_SESSION['user_type'] = $this->user->type_utilisateur;
            $_SESSION['token'] = $token;
            
            Utils::jsonResponse([
                'user' => [
                    'id' => $this->user->id,
                    'nom' => $this->user->nom,
                    'prenom' => $this->user->prenom,
                    'email' => $this->user->email,
                    'telephone' => $this->user->telephone,
                    'type_utilisateur' => $this->user->type_utilisateur,
                    'photo_profil' => $this->user->photo_profil,
                    'last_login' => $this->user->last_login
                ],
                'token' => $token
            ], 200, 'Connexion réussie');
        } else {
            Utils::jsonResponseError('Email ou mot de passe incorrect', 401);
        }
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        // Détruire la session
        session_destroy();
        
        Utils::jsonResponse([], 200, 'Déconnexion réussie');
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function checkAuth() {
        if (isset($_SESSION['user_id'])) {
            $this->user->id = $_SESSION['user_id'];
            
            if ($this->user->readOne()) {
                Utils::jsonResponse([
                    'authenticated' => true,
                    'user' => [
                        'id' => $this->user->id,
                        'nom' => $this->user->nom,
                        'prenom' => $this->user->prenom,
                        'email' => $this->user->email,
                        'telephone' => $this->user->telephone,
                        'type_utilisateur' => $this->user->type_utilisateur,
                        'photo_profil' => $this->user->photo_profil
                    ]
                ], 200);
            }
        }
        
        Utils::jsonResponse(['authenticated' => false], 200);
    }
    
    /**
     * Mettre à jour le profil
     */
    public function updateProfile() {
        if (!Utils::isMethod('PUT')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        // Vérifier l'authentification
        if (!isset($_SESSION['user_id'])) {
            Utils::jsonResponseError('Non authentifié', 401);
        }
        
        $data = Utils::getInputData();
        
        // Assigner l'ID utilisateur
        $this->user->id = $_SESSION['user_id'];
        
        // Lire les données actuelles
        if (!$this->user->readOne()) {
            Utils::jsonResponseError('Utilisateur non trouvé', 404);
        }
        
        // Mettre à jour les champs
        if (isset($data['nom'])) $this->user->nom = Utils::cleanString($data['nom']);
        if (isset($data['prenom'])) $this->user->prenom = Utils::cleanString($data['prenom']);
        if (isset($data['telephone'])) {
            if (!Utils::validatePhone($data['telephone'])) {
                Utils::jsonResponseError('Numéro de téléphone invalide', 400);
            }
            $this->user->telephone = Utils::formatPhone($data['telephone']);
        }
        if (isset($data['photo_profil'])) $this->user->photo_profil = $data['photo_profil'];
        
        // Mettre à jour
        if ($this->user->update()) {
            Utils::jsonResponse([
                'user' => [
                    'id' => $this->user->id,
                    'nom' => $this->user->nom,
                    'prenom' => $this->user->prenom,
                    'email' => $this->user->email,
                    'telephone' => $this->user->telephone,
                    'type_utilisateur' => $this->user->type_utilisateur,
                    'photo_profil' => $this->user->photo_profil
                ]
            ], 200, 'Profil mis à jour');
        } else {
            Utils::jsonResponseError('Erreur lors de la mise à jour', 500);
        }
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword() {
        if (!Utils::isMethod('PUT')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        // Vérifier l'authentification
        if (!isset($_SESSION['user_id'])) {
            Utils::jsonResponseError('Non authentifié', 401);
        }
        
        $data = Utils::getInputData();
        
        // Validation des champs requis
        $required_fields = ['current_password', 'new_password'];
        $errors = Utils::validateRequired($data, $required_fields);
        
        if (!empty($errors)) {
            Utils::jsonResponseError('Champs requis manquants', 400, $errors);
        }
        
        // Validation nouveau mot de passe
        if (strlen($data['new_password']) < Config::PASSWORD_MIN_LENGTH) {
            Utils::jsonResponseError('Le nouveau mot de passe doit contenir au moins ' . Config::PASSWORD_MIN_LENGTH . ' caractères', 400);
        }
        
        // Assigner l'ID utilisateur
        $this->user->id = $_SESSION['user_id'];
        
        // Lire les données actuelles
        if (!$this->user->readOne()) {
            Utils::jsonResponseError('Utilisateur non trouvé', 404);
        }
        
        // Vérifier le mot de passe actuel
        if (!$this->user->login($this->user->email, $data['current_password'])) {
            Utils::jsonResponseError('Mot de passe actuel incorrect', 401);
        }
        
        // Mettre à jour le mot de passe
        if ($this->user->updatePassword($data['new_password'])) {
            Utils::jsonResponse([], 200, 'Mot de passe changé avec succès');
        } else {
            Utils::jsonResponseError('Erreur lors du changement de mot de passe', 500);
        }
    }
    
    /**
     * Télécharger la photo de profil
     */
    public function uploadProfilePhoto() {
        if (!Utils::isMethod('POST')) {
            Utils::jsonResponseError('Méthode non autorisée', 405);
        }
        
        // Vérifier l'authentification
        if (!isset($_SESSION['user_id'])) {
            Utils::jsonResponseError('Non authentifié', 401);
        }
        
        if (!isset($_FILES['photo'])) {
            Utils::jsonResponseError('Aucun fichier téléchargé', 400);
        }
        
        try {
            // Télécharger l'image
            $photo_path = Utils::uploadImage($_FILES['photo'], 'profiles');
            
            // Mettre à jour l'utilisateur
            $this->user->id = $_SESSION['user_id'];
            $this->user->readOne();
            $this->user->photo_profil = $photo_path;
            
            if ($this->user->update()) {
                Utils::jsonResponse([
                    'photo_profil' => $photo_path
                ], 200, 'Photo de profil mise à jour');
            } else {
                Utils::jsonResponseError('Erreur lors de la mise à jour', 500);
            }
            
        } catch (Exception $e) {
            Utils::jsonResponseError($e->getMessage(), 400);
        }
    }
    
    /**
     * Envoyer un email de bienvenue
     */
    private function sendWelcomeEmail($email, $nom, $prenom) {
        $subject = 'Bienvenue sur Accueil Immo CI';
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { background-color: #064E3B; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Bienvenue sur Accueil Immo CI</h1>
            </div>
            <div class='content'>
                <p>Bonjour $prenom $nom,</p>
                <p>Nous sommes ravis de vous accueillir sur notre plateforme immobilière.</p>
                <p>Votre compte a été créé avec succès. Vous pouvez maintenant:</p>
                <ul>
                    <li>Déposer des annonces de biens</li>
                    <li>Rechercher des propriétés</li>
                    <li>Gérer vos locations</li>
                    <li>Bénéficier de nos services de conciergerie</li>
                </ul>
                <p>Pour toute question, n'hésitez pas à nous contacter.</p>
                <p>Cordialement,<br>L'équipe Accueil Immo CI</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 Accueil Immo CI. Tous droits réservés.</p>
            </div>
        </body>
        </html>";
        
        Config::sendEmail($email, $subject, $message);
    }
    
    /**
     * Générer un token JWT (simplifié)
     */
    private function generateToken($user) {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'user_id' => $user->id,
            'email' => $user->email,
            'type' => $user->type_utilisateur,
            'exp' => time() + Config::JWT_EXPIRE
        ]));
        $signature = hash_hmac('sha256', "$header.$payload", Config::JWT_SECRET, true);
        $signature = base64_encode($signature);
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Vérifier un token JWT (simplifié)
     */
    public function verifyToken($token) {
        if (empty($token)) {
            return false;
        }
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        $header = base64_decode($parts[0]);
        $payload = base64_decode($parts[1]);
        $signature = $parts[2];
        
        $payload_data = json_decode($payload, true);
        
        // Vérifier l'expiration
        if (isset($payload_data['exp']) && $payload_data['exp'] < time()) {
            return false;
        }
        
        // Vérifier la signature
        $expected_signature = hash_hmac('sha256', "$parts[0].$parts[1]", Config::JWT_SECRET, true);
        $expected_signature = base64_encode($expected_signature);
        
        return hash_equals($signature, $expected_signature);
    }
}

// Router
$auth = new AuthController();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        $auth->register();
        break;
    case 'login':
        $auth->login();
        break;
    case 'logout':
        $auth->logout();
        break;
    case 'check':
        $auth->checkAuth();
        break;
    case 'update':
        $auth->updateProfile();
        break;
    case 'password':
        $auth->changePassword();
        break;
    case 'upload':
        $auth->uploadProfilePhoto();
        break;
    default:
        Utils::jsonResponseError('Action non trouvée', 404);
}
?>
