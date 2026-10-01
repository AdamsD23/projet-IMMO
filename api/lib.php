<?php
/**
 * Fonctions partagées : base de données, réponses JSON, validation, jetons de session (JWT).
 */

require_once __DIR__ . '/config.php';

class ApiError extends Exception {
    public int $status;
    public array $details;

    public function __construct(string $message, int $status = 400, array $details = []) {
        parent::__construct($message);
        $this->status = $status;
        $this->details = $details;
    }
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];
    if (DB_SSL) {
        if (DB_SSL_CA) {
            // Le certificat est fourni en texte (variable d'environnement) : on l'écrit dans un fichier temporaire
            $caFile = sys_get_temp_dir() . '/db-ca-' . md5(DB_SSL_CA) . '.pem';
            if (!is_file($caFile)) file_put_contents($caFile, str_replace('\n', "\n", DB_SSL_CA));
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caFile;
        }
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (bool) DB_SSL_CA;
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function input(): array {
    static $data = null;
    if ($data !== null) return $data;
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode($raw, true) : [];
    if ($raw && !is_array($data)) throw new ApiError('Requête invalide (JSON mal formé)');
    return $data ?: [];
}

/**
 * Valide des données selon un schéma simple et renvoie les valeurs nettoyées.
 * Règles : type (string|int|number|email|phone|enum|bool|date), required, min, max, values, label
 */
function validate(array $data, array $schema): array {
    $clean = [];
    $errors = [];
    foreach ($schema as $field => $rule) {
        $label = $rule['label'] ?? $field;
        $value = $data[$field] ?? null;
        $empty = $value === null || (is_string($value) && trim($value) === '');

        if ($empty) {
            if (!empty($rule['required'])) $errors[] = "$label est obligatoire";
            $clean[$field] = $rule['default'] ?? null;
            continue;
        }

        switch ($rule['type'] ?? 'string') {
            case 'int':
            case 'number':
                if (!is_numeric($value)) { $errors[] = "$label doit être un nombre"; break; }
                $value = ($rule['type'] === 'int') ? (int) $value : (float) $value;
                if (isset($rule['min']) && $value < $rule['min']) $errors[] = "$label doit être au moins {$rule['min']}";
                if (isset($rule['max']) && $value > $rule['max']) $errors[] = "$label doit être au plus {$rule['max']}";
                break;
            case 'bool':
                $value = in_array($value, [true, 1, '1', 'true', 'on', 'oui'], true);
                break;
            case 'enum':
                if (!in_array($value, $rule['values'], true)) $errors[] = "$label : valeur non autorisée";
                break;
            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) $errors[] = "$label : date invalide";
                break;
            default:
                $value = trim(strip_tags((string) $value));
                $len = mb_strlen($value);
                if (isset($rule['max']) && $len > $rule['max']) $errors[] = "$label : {$rule['max']} caractères maximum";
                if (isset($rule['min']) && $len < $rule['min']) $errors[] = "$label : {$rule['min']} caractères minimum";
                if (($rule['type'] ?? '') === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) $errors[] = "$label invalide";
                if (($rule['type'] ?? '') === 'phone' && !preg_match('/^[0-9 +().-]{8,20}$/', $value)) $errors[] = "$label invalide";
        }
        $clean[$field] = $value;
    }
    if ($errors) throw new ApiError(implode(' • ', $errors), 400, $errors);
    return $clean;
}

// ---------- Jetons de session (JWT HS256) ----------

function b64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_create(array $payload): string {
    $header = b64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['exp'] = time() + JWT_TTL;
    $body = b64url_encode(json_encode($payload));
    $signature = b64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$signature";
}

function jwt_verify(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $body, $signature] = $parts;
    $expected = b64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    if (!hash_equals($expected, $signature)) return null;
    $payload = json_decode(b64url_decode($body), true);
    if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) return null;
    return $payload;
}

/**
 * Utilisateur connecté (ou null). Le compte est relu en base : un compte suspendu perd l'accès immédiatement.
 */
function current_user(): ?array {
    static $user = false;
    if ($user !== false) return $user;
    $user = null;

    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return null;
    $payload = jwt_verify($m[1]);
    if (!$payload) return null;

    $row = query('SELECT id, nom, prenom, email, telephone, type_utilisateur, statut, created_at FROM users WHERE id = ?', [$payload['sub']])->fetch();
    if ($row && $row['statut'] === 'actif') $user = $row;
    return $user;
}

function require_user(): array {
    $user = current_user();
    if (!$user) throw new ApiError('Connexion requise', 401);
    return $user;
}

function is_admin(array $user): bool {
    return $user['type_utilisateur'] === 'admin';
}

function public_user(array $u): array {
    return [
        'id' => (int) $u['id'],
        'nom' => $u['nom'],
        'prenom' => $u['prenom'],
        'email' => $u['email'],
        'telephone' => $u['telephone'],
        'type_utilisateur' => $u['type_utilisateur'],
        'created_at' => $u['created_at'] ?? null,
    ];
}

function client_ip(): string {
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    return $forwarded ? trim(explode(',', $forwarded)[0]) : ($_SERVER['REMOTE_ADDR'] ?? 'inconnue');
}
