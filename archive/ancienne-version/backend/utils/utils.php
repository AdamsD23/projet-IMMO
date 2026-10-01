<?php
/**
 * Fonctions utilitaires pour l'application
 */

class Utils {
    /**
     * Génère une réponse JSON
     */
    public static function jsonResponse($data, $status = 200, $message = '') {
        header('Content-Type: application/json');
        http_response_code($status);
        
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Génère une réponse d'erreur
     */
    public static function jsonResponseError($message, $status = 400, $errors = []) {
        header('Content-Type: application/json');
        http_response_code($status);
        
        $response = [
            'status' => $status,
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Vérifie si la requête est une requête AJAX
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Vérifie la méthode HTTP
     */
    public static function isMethod($method) {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }
    
    /**
     * Récupère les données d'une requête POST/PUT/PATCH
     */
    public static function getInputData() {
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($content_type, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            return json_decode($json, true) ?: [];
        }
        
        return $_POST;
    }
    
    /**
     * Valide les données requises
     */
    public static function validateRequired($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[$field] = "Le champ {$field} est requis";
            }
        }
        
        return $errors;
    }
    
    /**
     * Valide un email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valide un numéro de téléphone ivoirien
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Formats ivoiriens: +225XXXXXXXX, 225XXXXXXXX, ou 0XXXXXXXX
        if (preg_match('/^(\+225|225)?0?[1-9]\d{7}$/', $phone)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Formate un numéro de téléphone
     */
    public static function formatPhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Ajouter l'indicatif si absent
        if (strlen($phone) === 8 && substr($phone, 0, 1) === '0') {
            return '+225' . $phone;
        }
        
        if (strlen($phone) === 8) {
            return '+2250' . $phone;
        }
        
        if (strlen($phone) === 10 && substr($phone, 0, 3) === '225') {
            return '+225' . substr($phone, 3);
        }
        
        return $phone;
    }
    
    /**
     * Génère un token aléatoire
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hache un mot de passe
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    /**
     * Vérifie un mot de passe
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Génère une slug à partir d'une chaîne
     */
    public static function slugify($text) {
        // Convertir en minuscules et remplacer les caractères spéciaux
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Télécharge et traite un fichier image
     */
    public static function uploadImage($file, $subfolder = 'properties') {
        $upload_dir = Config::getUploadPath() . $subfolder . '/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Vérifier le fichier
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("Fichier invalide");
        }
        
        // Vérifier la taille
        if ($file['size'] > Config::MAX_FILE_SIZE) {
            throw new Exception("Fichier trop volumineux");
        }
        
        // Vérifier le type
        if (!Config::isAllowedFileType($file['name'])) {
            throw new Exception("Type de fichier non autorisé");
        }
        
        // Générer un nom de fichier unique
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('img_') . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Erreur lors du téléchargement");
        }
        
        // Retourner le chemin relatif
        return $subfolder . '/' . $filename;
    }
    
    /**
     * Redimensionne une image
     */
    public static function resizeImage($source_path, $dest_path, $max_width = 800, $max_height = 600) {
        list($width, $height, $type) = getimagesize($source_path);
        
        // Calculer les nouvelles dimensions
        $ratio = $width / $height;
        
        if ($width > $height) {
            $new_width = min($max_width, $width);
            $new_height = $new_width / $ratio;
        } else {
            $new_height = min($max_height, $height);
            $new_width = $new_height * $ratio;
        }
        
        // Créer l'image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($source_path);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($source_path);
                break;
            default:
                return false;
        }
        
        $dest = imagecreatetruecolor($new_width, $new_height);
        
        // Conserver la transparence pour PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
        }
        
        imagecopyresampled($dest, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Sauvegarder
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($dest, $dest_path, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($dest, $dest_path, 8);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($dest, $dest_path, 85);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($dest);
        
        return true;
    }
    
    /**
     * Formate une date
     */
    public static function formatDate($date, $format = 'd/m/Y') {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
    
    /**
     * Calcule le nombre de jours entre deux dates
     */
    public static function daysBetween($date1, $date2) {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        return $interval->days;
    }
    
    /**
     * Génère un PDF de contrat (simplifié)
     */
    public static function generateContractPDF($data) {
        // Ceci est une implémentation simplifiée
        // En production, utilisez une bibliothèque comme TCPDF ou DomPDF
        
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 24px; font-weight: bold; }
                .section { margin: 20px 0; }
                .field { margin: 10px 0; }
                .signature { margin-top: 50px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='title'>CONTRAT DE LOCATION</div>
                <div>Accueil Immo CI</div>
            </div>
            
            <div class='section'>
                <div class='field'><strong>Propriétaire:</strong> {$data['proprietaire']}</div>
                <div class='field'><strong>Locataire:</strong> {$data['locataire']}</div>
                <div class='field'><strong>Bien:</strong> {$data['bien']}</div>
                <div class='field'><strong>Adresse:</strong> {$data['adresse']}</div>
                <div class='field'><strong>Loyer mensuel:</strong> " . Config::formatMoney($data['loyer']) . "</div>
                <div class='field'><strong>Caution:</strong> " . Config::formatMoney($data['caution']) . "</div>
                <div class='field'><strong>Date début:</strong> " . self::formatDate($data['date_debut']) . "</div>
                <div class='field'><strong>Date fin:</strong> " . self::formatDate($data['date_fin']) . "</div>
            </div>
            
            <div class='signature'>
                <div style='float: left; width: 45%;'>
                    <div>Signature Propriétaire:</div>
                    <div style='border-bottom: 1px solid #000; height: 50px; margin-top: 50px;'></div>
                </div>
                <div style='float: right; width: 45%;'>
                    <div>Signature Locataire:</div>
                    <div style='border-bottom: 1px solid #000; height: 50px; margin-top: 50px;'></div>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Envoie une notification WhatsApp
     */
    public static function sendWhatsApp($to, $message) {
        // Implémentation avec l'API WhatsApp Business
        $url = Config::WHATSAPP_API_URL . Config::WHATSAPP_PHONE_ID . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . Config::WHATSAPP_ACCESS_TOKEN,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $http_code === 200;
    }
    
    /**
     * Crée un thumbnail d'image
     */
    public static function createThumbnail($source_path, $dest_path, $width = 300, $height = 200) {
        return self::resizeImage($source_path, $dest_path, $width, $height);
    }
    
    /**
     * Nettoie une chaîne de caractères
     */
    public static function cleanString($string) {
        return trim(strip_tags($string));
    }
    
    /**
     * Tronque un texte
     */
    public static function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
}
?>
