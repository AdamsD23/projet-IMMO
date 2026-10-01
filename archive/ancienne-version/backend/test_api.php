<?php
/**
 * Script de test de l'API
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'models/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$database = new Database();
$conn = $database->getConnection();

echo "<h2>Test de l'API Accueil Immo</h2>";

try {
    // Test de connexion à la base
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Connexion à la base réussie</p>";
    echo "<p>📊 Nombre d'utilisateurs: " . $result['count'] . "</p>";
    
    // Test d'inscription manuelle
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_register'])) {
        $test_user = new User($conn);
        $test_user->nom = 'Test';
        $test_user->prenom = 'User';
        $test_user->email = 'test' . time() . '@test.com';
        $test_user->password = 'test123456';
        $test_user->telephone = '+2250700000000';
        $test_user->type_utilisateur = 'proprietaire';
        
        if ($test_user->create()) {
            echo "<p>✅ Test d'inscription réussie pour: " . $test_user->email . "</p>";
            echo "<p>ID créé: " . $test_user->id . "</p>";
        } else {
            echo "<p>❌ Test d'inscription échoué</p>";
        }
    }
    
    echo "<h3>🔧 Actions disponibles:</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='test_register' value='1'>";
    echo "<button type='submit'>Tester l'inscription</button>";
    echo "</form>";
    
    echo "<p><a href='../acceuil2.html'>🌐 Tester l'inscription frontend</a></p>";
    
} catch(PDOException $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>";
}
?>
