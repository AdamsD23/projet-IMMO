<?php
/**
 * API REST Accueil Immo — point d'entrée unique (toutes les URL /api/...).
 */

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/setup.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

ini_set('display_errors', '0');
error_reporting(E_ALL);

const PROPERTY_COLUMNS = 'p.id, p.titre, p.description, p.type_bien, p.statut_bien, p.prix, p.surface, p.chambres, p.salles_bain,
    p.commune, p.quartier, p.meuble, p.climatisation, p.parking, p.piscine, p.gardiennage, p.groupe_electrogene,
    p.featured, p.active, p.vues, p.proprietaire_id, p.created_at, p.updated_at';

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(preg_replace('#^.*?/api#', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');

try {
    ensure_schema();
    route($method, $path);
    throw new ApiError('Route API introuvable', 404);
} catch (ApiError $e) {
    json_response(array_filter(['error' => $e->getMessage(), 'details' => $e->details ?: null]), $e->status);
} catch (PDOException $e) {
    error_log('[Accueil Immo] ' . $e->getMessage());
    $status = $e->errorInfo[1] ?? null;
    if ($status === 1062) json_response(['error' => 'Cet élément existe déjà'], 409);
    json_response(['error' => 'Erreur de base de données'], 500);
} catch (Throwable $e) {
    error_log('[Accueil Immo] ' . $e->getMessage());
    json_response(['error' => 'Erreur serveur interne'], 500);
}

// =====================================================================

function route(string $method, string $path): void {
    $seg = $path === '' ? [''] : explode('/', $path);
    $id = isset($seg[1]) && ctype_digit($seg[1]) ? (int) $seg[1] : null;

    switch (true) {
        case $method === 'GET' && $path === 'health':
            json_response(['status' => 'ok', 'demo' => SEED_DEMO]);

        case $method === 'GET' && $path === 'meta':
            json_response(meta());

        // ----- Comptes -----
        case $method === 'POST' && $path === 'auth/register': register();
        case $method === 'POST' && $path === 'auth/login': login();
        case $method === 'GET' && $path === 'auth/me': json_response(public_user(require_user()));
        case $method === 'PUT' && $path === 'auth/me': update_me();
        case $method === 'PUT' && $path === 'auth/password': change_password();

        // ----- Annonces -----
        case $method === 'GET' && $path === 'properties': list_properties();
        case $method === 'GET' && $path === 'properties/featured': featured_properties();
        case $method === 'GET' && $seg[0] === 'properties' && $id && count($seg) === 2: show_property($id);
        case $method === 'POST' && $path === 'properties': save_property(null);
        case $method === 'PUT' && $seg[0] === 'properties' && $id && count($seg) === 2: save_property($id);
        case $method === 'PATCH' && $seg[0] === 'properties' && $id && ($seg[2] ?? '') === 'active': toggle_property($id);
        case $method === 'DELETE' && $seg[0] === 'properties' && $id: delete_property($id);
        case $method === 'GET' && $path === 'my/properties': my_properties();

        // ----- Images -----
        case $method === 'POST' && $path === 'images': upload_image();
        case $method === 'GET' && $seg[0] === 'images' && $id: serve_image($id);
        case $method === 'DELETE' && $seg[0] === 'images' && $id: delete_image($id);

        // ----- Demandes (visites, informations, conciergerie) -----
        case $method === 'POST' && $path === 'requests': create_request();
        case $method === 'GET' && $path === 'my/requests': my_requests();
        case $method === 'PATCH' && $seg[0] === 'requests' && $id: update_request($id);

        // ----- Conciergerie et statistiques -----
        case $method === 'GET' && $path === 'concierge/services':
            json_response(array_map('cast_row', query('SELECT * FROM concierge_services ORDER BY id')->fetchAll()));
        case $method === 'GET' && $path === 'my/stats': my_stats();
    }
}

function meta(): array {
    return [
        'communes' => COMMUNES,
        'types' => TYPES_BIEN,
        'statuts' => STATUTS_BIEN,
        'equipements' => EQUIPEMENTS,
        'whatsapp' => env('CONTACT_WHATSAPP', '2250767418701'),
    ];
}

// =====================================================================
// Comptes
// =====================================================================

function register(): void {
    $d = validate(input(), [
        'nom' => ['required' => true, 'max' => 100, 'label' => 'Nom'],
        'prenom' => ['required' => true, 'max' => 100, 'label' => 'Prénom'],
        'email' => ['type' => 'email', 'required' => true, 'max' => 150, 'label' => 'Email'],
        'telephone' => ['type' => 'phone', 'required' => true, 'label' => 'Téléphone'],
        'password' => ['required' => true, 'min' => 8, 'max' => 200, 'label' => 'Mot de passe'],
        'type_utilisateur' => ['type' => 'enum', 'values' => ['proprietaire', 'agent', 'locataire'], 'default' => 'proprietaire', 'label' => 'Profil'],
    ]);
    $d['email'] = mb_strtolower($d['email']);
    if (query('SELECT id FROM users WHERE email = ?', [$d['email']])->fetch()) {
        throw new ApiError('Un compte existe déjà avec cet email', 409);
    }
    query('INSERT INTO users (nom, prenom, email, password, telephone, type_utilisateur) VALUES (?, ?, ?, ?, ?, ?)',
        [$d['nom'], $d['prenom'], $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), $d['telephone'], $d['type_utilisateur']]);
    $user = query('SELECT * FROM users WHERE id = ?', [db()->lastInsertId()])->fetch();
    json_response(['token' => jwt_create(['sub' => (int) $user['id']]), 'user' => public_user($user)], 201);
}

function login(): void {
    // Au plus 10 tentatives par adresse IP sur 15 minutes
    $ip = client_ip();
    query('DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $attempts = (int) query('SELECT COUNT(*) FROM login_attempts WHERE ip = ?', [$ip])->fetchColumn();
    if ($attempts >= 10) throw new ApiError('Trop de tentatives. Réessayez dans 15 minutes.', 429);

    $d = validate(input(), [
        'email' => ['required' => true, 'max' => 150, 'label' => 'Email'],
        'password' => ['required' => true, 'max' => 200, 'label' => 'Mot de passe'],
    ]);
    $user = query('SELECT * FROM users WHERE email = ?', [mb_strtolower($d['email'])])->fetch();
    if (!$user || !password_verify($d['password'], $user['password'])) {
        query('INSERT INTO login_attempts (ip) VALUES (?)', [$ip]);
        throw new ApiError('Email ou mot de passe incorrect', 401);
    }
    if ($user['statut'] !== 'actif') throw new ApiError('Ce compte est suspendu', 403);

    json_response(['token' => jwt_create(['sub' => (int) $user['id']]), 'user' => public_user($user)]);
}

function update_me(): void {
    $user = require_user();
    $d = validate(input(), [
        'nom' => ['required' => true, 'max' => 100, 'label' => 'Nom'],
        'prenom' => ['required' => true, 'max' => 100, 'label' => 'Prénom'],
        'telephone' => ['type' => 'phone', 'required' => true, 'label' => 'Téléphone'],
    ]);
    query('UPDATE users SET nom = ?, prenom = ?, telephone = ? WHERE id = ?', [$d['nom'], $d['prenom'], $d['telephone'], $user['id']]);
    json_response(public_user(query('SELECT * FROM users WHERE id = ?', [$user['id']])->fetch()));
}

function change_password(): void {
    $user = require_user();
    $d = validate(input(), [
        'ancien' => ['required' => true, 'label' => 'Mot de passe actuel'],
        'nouveau' => ['required' => true, 'min' => 8, 'max' => 200, 'label' => 'Nouveau mot de passe'],
    ]);
    $hash = query('SELECT password FROM users WHERE id = ?', [$user['id']])->fetchColumn();
    if (!password_verify($d['ancien'], $hash)) throw new ApiError('Mot de passe actuel incorrect');
    query('UPDATE users SET password = ? WHERE id = ?', [password_hash($d['nouveau'], PASSWORD_DEFAULT), $user['id']]);
    json_response(['message' => 'Mot de passe modifié']);
}

// =====================================================================
// Annonces
// =====================================================================

function cast_row(array $row): array {
    foreach ($row as $k => $v) {
        if ($v === null) continue;
        if (in_array($k, ['id', 'prix', 'surface', 'chambres', 'salles_bain', 'vues', 'proprietaire_id', 'prix_base', 'property_id', 'service_id', 'nb_demandes', 'nb_images'], true)) {
            $row[$k] = (int) $v;
        } elseif (in_array($k, array_merge(EQUIPEMENTS, ['featured', 'active']), true)) {
            $row[$k] = (bool) $v;
        }
    }
    return $row;
}

function image_url(array $img): string {
    return $img['chemin'] ?: 'api/images/' . $img['id'];
}

/** Ajoute images et liste d'équipements aux annonces */
function hydrate(array $rows): array {
    if (!$rows) return [];
    $ids = array_column($rows, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $images = [];
    foreach (query("SELECT id, property_id, chemin FROM images WHERE property_id IN ($in) ORDER BY position, id", $ids)->fetchAll() as $img) {
        $images[$img['property_id']][] = ['id' => (int) $img['id'], 'url' => image_url($img)];
    }
    return array_map(function ($row) use ($images) {
        $row = cast_row($row);
        $row['images'] = $images[$row['id']] ?? [];
        $row['equipements'] = array_values(array_filter(EQUIPEMENTS, fn($e) => !empty($row[$e])));
        return $row;
    }, $rows);
}

function list_properties(): void {
    $where = ['p.active = 1'];
    $params = [];
    $q = $_GET;

    if (in_array($q['statut'] ?? '', STATUTS_BIEN, true)) { $where[] = 'p.statut_bien = ?'; $params[] = $q['statut']; }
    if (in_array($q['commune'] ?? '', COMMUNES, true)) { $where[] = 'p.commune = ?'; $params[] = $q['commune']; }
    if (in_array($q['type'] ?? '', TYPES_BIEN, true)) { $where[] = 'p.type_bien = ?'; $params[] = $q['type']; }
    if (is_numeric($q['prix_min'] ?? null)) { $where[] = 'p.prix >= ?'; $params[] = (int) $q['prix_min']; }
    if (is_numeric($q['prix_max'] ?? null)) { $where[] = 'p.prix <= ?'; $params[] = (int) $q['prix_max']; }
    if (is_numeric($q['chambres_min'] ?? null)) { $where[] = 'p.chambres >= ?'; $params[] = (int) $q['chambres_min']; }
    if (!empty($q['q'])) {
        $where[] = '(p.titre LIKE ? OR p.quartier LIKE ? OR p.commune LIKE ? OR p.description LIKE ?)';
        $like = '%' . mb_substr($q['q'], 0, 80) . '%';
        array_push($params, $like, $like, $like, $like);
    }
    foreach (array_intersect(explode(',', $q['equipements'] ?? ''), EQUIPEMENTS) as $equip) {
        $where[] = "p.$equip = 1";
    }

    $order = [
        'prix_asc' => 'p.prix ASC',
        'prix_desc' => 'p.prix DESC',
        'populaire' => 'p.vues DESC',
        'recent' => 'p.created_at DESC',
    ][$q['tri'] ?? ''] ?? 'p.featured DESC, p.created_at DESC';

    $perPage = min(max((int) ($q['par_page'] ?? 12), 1), 48);
    $page = max((int) ($q['page'] ?? 1), 1);
    $offset = ($page - 1) * $perPage;
    $whereSql = implode(' AND ', $where);

    $total = (int) query("SELECT COUNT(*) FROM properties p WHERE $whereSql", $params)->fetchColumn();
    $rows = query("SELECT " . PROPERTY_COLUMNS . " FROM properties p WHERE $whereSql ORDER BY $order LIMIT $perPage OFFSET $offset", $params)->fetchAll();

    json_response(['items' => hydrate($rows), 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / $perPage)]);
}

function featured_properties(): void {
    $limit = min(max((int) ($_GET['limit'] ?? 6), 1), 12);
    $rows = query("SELECT " . PROPERTY_COLUMNS . " FROM properties p WHERE p.active = 1 ORDER BY p.featured DESC, p.vues DESC LIMIT $limit")->fetchAll();
    json_response(hydrate($rows));
}

function find_property(int $id): array {
    $row = query("SELECT " . PROPERTY_COLUMNS . " FROM properties p WHERE p.id = ?", [$id])->fetch();
    if (!$row) throw new ApiError('Annonce introuvable', 404);
    return $row;
}

function show_property(int $id): void {
    $row = find_property($id);
    $user = current_user();
    $canSeeInactive = $user && ($user['id'] == $row['proprietaire_id'] || is_admin($user));
    if (!$row['active'] && !$canSeeInactive) throw new ApiError('Annonce introuvable', 404);

    if (!$canSeeInactive) query('UPDATE properties SET vues = vues + 1 WHERE id = ?', [$id]);

    $property = hydrate([$row])[0];
    $owner = query('SELECT prenom, nom, telephone, type_utilisateur FROM users WHERE id = ?', [$row['proprietaire_id']])->fetch();
    $property['contact'] = $owner ? [
        'nom' => $owner['prenom'] . ' ' . mb_substr($owner['nom'], 0, 1) . '.',
        'telephone' => $owner['telephone'],
        'type' => $owner['type_utilisateur'],
    ] : null;

    $similar = query("SELECT " . PROPERTY_COLUMNS . " FROM properties p WHERE p.active = 1 AND p.id <> ? AND p.statut_bien = ?
                      ORDER BY (p.commune = ?) DESC, ABS(p.prix - ?) ASC LIMIT 3",
        [$id, $row['statut_bien'], $row['commune'], $row['prix']])->fetchAll();
    $property['similaires'] = hydrate($similar);

    json_response($property);
}

function property_schema(): array {
    $schema = [
        'titre' => ['required' => true, 'min' => 5, 'max' => 150, 'label' => 'Titre'],
        'description' => ['max' => 3000, 'label' => 'Description'],
        'type_bien' => ['type' => 'enum', 'values' => TYPES_BIEN, 'required' => true, 'label' => 'Type de bien'],
        'statut_bien' => ['type' => 'enum', 'values' => STATUTS_BIEN, 'required' => true, 'label' => 'Location ou vente'],
        'prix' => ['type' => 'int', 'required' => true, 'min' => 1000, 'max' => 100000000000, 'label' => 'Prix'],
        'surface' => ['type' => 'int', 'min' => 1, 'max' => 1000000, 'label' => 'Surface'],
        'chambres' => ['type' => 'int', 'min' => 0, 'max' => 50, 'label' => 'Chambres'],
        'salles_bain' => ['type' => 'int', 'min' => 0, 'max' => 50, 'label' => 'Salles de bain'],
        'commune' => ['type' => 'enum', 'values' => COMMUNES, 'required' => true, 'label' => 'Commune'],
        'quartier' => ['max' => 100, 'label' => 'Quartier'],
    ];
    foreach (EQUIPEMENTS as $e) $schema[$e] = ['type' => 'bool', 'default' => false];
    return $schema;
}

function can_edit(array $user, array $property): bool {
    return is_admin($user) || (int) $property['proprietaire_id'] === (int) $user['id'];
}

function save_property(?int $id): void {
    $user = require_user();
    if ($user['type_utilisateur'] === 'locataire') throw new ApiError('Un compte locataire ne peut pas publier d\'annonce', 403);
    if ($id) {
        $existing = find_property($id);
        if (!can_edit($user, $existing)) throw new ApiError('Vous ne pouvez modifier que vos annonces', 403);
    }

    $body = input();
    $d = validate($body, property_schema());
    $imageIds = array_values(array_unique(array_map('intval', array_slice($body['image_ids'] ?? [], 0, MAX_IMAGES_PER_PROPERTY))));

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $cols = array_keys($d);
        $values = array_map(fn($v) => is_bool($v) ? (int) $v : $v, array_values($d));
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", $cols));
            query("UPDATE properties SET $set WHERE id = ?", array_merge($values, [$id]));
        } else {
            $placeholders = implode(', ', array_fill(0, count($cols) + 1, '?'));
            query('INSERT INTO properties (' . implode(', ', $cols) . ", proprietaire_id) VALUES ($placeholders)", array_merge($values, [$user['id']]));
            $id = (int) $pdo->lastInsertId();
        }

        // Rattache les images envoyées (seulement celles de l'utilisateur) dans l'ordre choisi
        if (array_key_exists('image_ids', $body)) {
            $ownerCheck = is_admin($user) ? '' : ' AND owner_id = ' . (int) $user['id'];
            // Détache les images retirées, puis rattache la liste reçue dans l'ordre
            $keep = $imageIds ? ' AND id NOT IN (' . implode(',', $imageIds) . ')' : '';
            query("UPDATE images SET property_id = NULL WHERE property_id = ?$keep", [$id]);
            foreach ($imageIds as $pos => $imageId) {
                query("UPDATE images SET property_id = ?, position = ? WHERE id = ? AND (property_id IS NULL OR property_id = ?)$ownerCheck",
                    [$id, $pos, $imageId, $id]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    json_response(hydrate([find_property($id)])[0], isset($existing) ? 200 : 201);
}

function toggle_property(int $id): void {
    $user = require_user();
    $property = find_property($id);
    if (!can_edit($user, $property)) throw new ApiError('Action non autorisée', 403);
    $d = validate(input(), ['active' => ['type' => 'bool', 'required' => true]]);
    query('UPDATE properties SET active = ? WHERE id = ?', [(int) $d['active'], $id]);
    json_response(hydrate([find_property($id)])[0]);
}

function delete_property(int $id): void {
    $user = require_user();
    $property = find_property($id);
    if (!can_edit($user, $property)) throw new ApiError('Action non autorisée', 403);
    query('DELETE FROM properties WHERE id = ?', [$id]);
    json_response(['message' => 'Annonce supprimée']);
}

function my_properties(): void {
    $user = require_user();
    $where = is_admin($user) ? '1 = 1' : 'p.proprietaire_id = ?';
    $params = is_admin($user) ? [] : [$user['id']];
    $rows = query("SELECT " . PROPERTY_COLUMNS . ",
                   (SELECT COUNT(*) FROM requests r WHERE r.property_id = p.id) AS nb_demandes
                   FROM properties p WHERE $where ORDER BY p.created_at DESC", $params)->fetchAll();
    json_response(hydrate($rows));
}

// =====================================================================
// Images (stockées en base pour survivre aux redémarrages de l'hébergeur)
// =====================================================================

function upload_image(): void {
    $user = require_user();
    $file = $_FILES['image'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) throw new ApiError('Aucune image reçue');
    if ($file['size'] > MAX_IMAGE_BYTES) throw new ApiError('Image trop lourde (2 Mo maximum)');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new ApiError('Format accepté : JPG, PNG ou WebP');

    // Nettoie les images envoyées mais jamais rattachées à une annonce depuis plus d'un jour
    query('DELETE FROM images WHERE property_id IS NULL AND chemin IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    query('INSERT INTO images (owner_id, mime, data) VALUES (?, ?, ?)', [$user['id'], $mime, file_get_contents($file['tmp_name'])]);
    $id = (int) db()->lastInsertId();
    json_response(['id' => $id, 'url' => "api/images/$id"], 201);
}

function serve_image(int $id): void {
    $img = query('SELECT chemin, mime, data FROM images WHERE id = ?', [$id])->fetch();
    if (!$img) throw new ApiError('Image introuvable', 404);
    if ($img['chemin']) {
        header('Location: ../../' . $img['chemin'], true, 302);
        exit;
    }
    header('Content-Type: ' . $img['mime']);
    header('Cache-Control: public, max-age=31536000, immutable');
    echo $img['data'];
    exit;
}

function delete_image(int $id): void {
    $user = require_user();
    $img = query('SELECT owner_id FROM images WHERE id = ?', [$id])->fetch();
    if (!$img) throw new ApiError('Image introuvable', 404);
    if ((int) $img['owner_id'] !== (int) $user['id'] && !is_admin($user)) throw new ApiError('Action non autorisée', 403);
    query('DELETE FROM images WHERE id = ?', [$id]);
    json_response(['message' => 'Image supprimée']);
}

// =====================================================================
// Demandes
// =====================================================================

function create_request(): void {
    $d = validate(input(), [
        'type_demande' => ['type' => 'enum', 'values' => ['visite', 'information', 'conciergerie', 'contact'], 'required' => true, 'label' => 'Type de demande'],
        'property_id' => ['type' => 'int', 'min' => 1],
        'service_id' => ['type' => 'int', 'min' => 1],
        'nom' => ['required' => true, 'max' => 150, 'label' => 'Nom'],
        'telephone' => ['type' => 'phone', 'required' => true, 'label' => 'Téléphone'],
        'email' => ['type' => 'email', 'max' => 150, 'label' => 'Email'],
        'message' => ['max' => 2000, 'label' => 'Message'],
        'date_souhaitee' => ['type' => 'date', 'label' => 'Date souhaitée'],
    ]);

    $destinataire = null;
    if ($d['property_id']) {
        $property = find_property($d['property_id']);
        $destinataire = (int) $property['proprietaire_id'];
    } elseif (in_array($d['type_demande'], ['visite', 'information'], true)) {
        throw new ApiError('Annonce manquante pour cette demande');
    }
    if ($d['type_demande'] === 'conciergerie') {
        if (!$d['service_id'] || !query('SELECT id FROM concierge_services WHERE id = ?', [$d['service_id']])->fetch()) {
            throw new ApiError('Choisissez un service de conciergerie');
        }
    }

    query('INSERT INTO requests (type_demande, property_id, service_id, destinataire_id, nom, telephone, email, message, date_souhaitee)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$d['type_demande'], $d['property_id'], $d['service_id'], $destinataire, $d['nom'], $d['telephone'], $d['email'], $d['message'], $d['date_souhaitee']]);
    json_response(['message' => 'Demande envoyée', 'id' => (int) db()->lastInsertId()], 201);
}

function my_requests(): void {
    $user = require_user();
    // L'admin voit tout ; les autres voient les demandes sur leurs annonces
    $where = is_admin($user) ? '1 = 1' : 'r.destinataire_id = ?';
    $params = is_admin($user) ? [] : [$user['id']];
    $rows = query("SELECT r.*, p.titre AS property_titre, s.nom AS service_nom
                   FROM requests r
                   LEFT JOIN properties p ON p.id = r.property_id
                   LEFT JOIN concierge_services s ON s.id = r.service_id
                   WHERE $where ORDER BY r.statut = 'traite', r.created_at DESC LIMIT 200", $params)->fetchAll();
    json_response(array_map('cast_row', $rows));
}

function update_request(int $id): void {
    $user = require_user();
    $request = query('SELECT destinataire_id FROM requests WHERE id = ?', [$id])->fetch();
    if (!$request) throw new ApiError('Demande introuvable', 404);
    if ((int) $request['destinataire_id'] !== (int) $user['id'] && !is_admin($user)) throw new ApiError('Action non autorisée', 403);
    $d = validate(input(), ['statut' => ['type' => 'enum', 'values' => ['nouveau', 'en_cours', 'traite'], 'required' => true, 'label' => 'Statut']]);
    query('UPDATE requests SET statut = ? WHERE id = ?', [$d['statut'], $id]);
    json_response(['message' => 'Demande mise à jour']);
}

function my_stats(): void {
    $user = require_user();
    $admin = is_admin($user);
    $owner = $admin ? '1 = 1' : 'proprietaire_id = ' . (int) $user['id'];
    $dest = $admin ? '1 = 1' : 'destinataire_id = ' . (int) $user['id'];

    $p = query("SELECT COUNT(*) AS annonces, COALESCE(SUM(active), 0) AS actives, COALESCE(SUM(vues), 0) AS vues,
                COALESCE(SUM(statut_bien = 'location'), 0) AS locations, COALESCE(SUM(statut_bien = 'vente'), 0) AS ventes
                FROM properties WHERE $owner")->fetch();
    $r = query("SELECT COUNT(*) AS demandes, COALESCE(SUM(statut = 'nouveau'), 0) AS nouvelles FROM requests WHERE $dest")->fetch();
    $stats = array_map('intval', array_merge($p, $r));
    if ($admin) $stats['utilisateurs'] = (int) query('SELECT COUNT(*) FROM users')->fetchColumn();
    json_response($stats);
}
