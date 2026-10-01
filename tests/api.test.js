/**
 * Tests d'intégration de l'API PHP d'Accueil Immo.
 * Lance le serveur PHP intégré sur une base dédiée (vidée au début) :
 *   DB_NAME=immo_tests npm test
 * Variables utiles : PHP_BIN (chemin de php), DB_HOST, DB_PORT, DB_USER, DB_PASSWORD.
 */

const { test, before, after } = require('node:test');
const assert = require('node:assert/strict');
const { spawn, execFileSync } = require('node:child_process');
const path = require('node:path');

const ROOT = path.join(__dirname, '..');
const PHP = process.env.PHP_BIN || 'php';
const PORT = 8765;
const BASE = `http://127.0.0.1:${PORT}`;
const env = { ...process.env, DB_NAME: process.env.DB_NAME || 'immo_tests', SEED_DEMO: 'true', ADMIN_PASSWORD: 'admin-test-123', APP_ENV: 'test' };

if (!/test/i.test(env.DB_NAME)) throw new Error('Par sécurité, la base de test doit contenir "test" dans son nom');

let server;
const tokens = {};

async function api(method, url, { token, body, form } = {}) {
    const res = await fetch(BASE + url, {
        method,
        headers: { ...(body && { 'Content-Type': 'application/json' }), ...(token && { Authorization: `Bearer ${token}` }) },
        body: form || (body ? JSON.stringify(body) : undefined),
        redirect: 'manual'
    });
    const text = await res.text();
    let json; try { json = JSON.parse(text); } catch { json = text; }
    return { status: res.status, body: json, headers: res.headers };
}

before(async () => {
    // Base neuve : on supprime puis on recrée tout via le script d'installation
    const sql = `DROP DATABASE IF EXISTS \`${env.DB_NAME}\`;`;
    execFileSync(PHP, ['-r', `$p = new PDO('mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: 3306), getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: ''); $p->exec("${sql}");`], { env });
    execFileSync(PHP, [path.join(ROOT, 'api', 'setup.php')], { env });

    server = spawn(PHP, ['-S', `127.0.0.1:${PORT}`, 'router.php'], { cwd: ROOT, env, stdio: 'ignore' });
    for (let i = 0; i < 50; i++) {
        try { if ((await fetch(`${BASE}/api/health`)).ok) return; } catch { /* pas encore prêt */ }
        await new Promise(r => setTimeout(r, 100));
    }
    throw new Error('Le serveur PHP ne démarre pas');
});

after(() => server?.kill());

test('le code serveur et la configuration ne sont jamais servis', async () => {
    for (const file of ['/api/config.php', '/api/lib.php', '/api/setup.php', '/router.php', '/.env', '/package.json', '/tests/api.test.js']) {
        const res = await fetch(BASE + file);
        const text = await res.text();
        assert.ok(!text.includes('<?php') && !text.includes('DB_PASSWORD') && !text.includes('require('), `${file} ne doit pas être exposé`);
    }
});

test('les annonces de démonstration sont publiques et filtrables', async () => {
    const all = await api('GET', '/api/properties');
    assert.equal(all.status, 200);
    assert.equal(all.body.total, 10);

    const locations = await api('GET', '/api/properties?statut=location&tri=prix_asc');
    assert.ok(locations.body.items.every(p => p.statut_bien === 'location'));
    const prices = locations.body.items.map(p => p.prix);
    assert.deepEqual(prices, [...prices].sort((a, b) => a - b), 'tri par prix croissant');

    const piscine = await api('GET', '/api/properties?equipements=piscine');
    assert.ok(piscine.body.total > 0 && piscine.body.items.every(p => p.piscine === true));
});

test('le détail d\'une annonce compte les vues et propose des biens similaires', async () => {
    const first = (await api('GET', '/api/properties/1')).body;
    const second = (await api('GET', '/api/properties/1')).body;
    assert.equal(second.vues, first.vues + 1);
    assert.ok(first.contact.telephone);
    assert.ok(first.similaires.length > 0);
    assert.equal((await api('GET', '/api/properties/99999')).status, 404);
});

test('inscription, connexion et protection des routes privées', async () => {
    assert.equal((await api('GET', '/api/my/properties')).status, 401);

    const weak = await api('POST', '/api/auth/register', { body: { nom: 'Test', prenom: 'A', email: 'a@test.ci', telephone: '0700000000', password: '123' } });
    assert.equal(weak.status, 400);

    const owner = await api('POST', '/api/auth/register', { body: { nom: 'Diallo', prenom: 'Awa', email: 'awa@test.ci', telephone: '07 00 00 00 01', password: 'motdepasse1', type_utilisateur: 'proprietaire' } });
    assert.equal(owner.status, 201);
    assert.equal(owner.body.user.password, undefined, 'le mot de passe ne doit jamais être renvoyé');
    tokens.owner = owner.body.token;

    const duplicate = await api('POST', '/api/auth/register', { body: { nom: 'X', prenom: 'Y', email: 'AWA@test.ci', telephone: '0700000002', password: 'motdepasse1' } });
    assert.equal(duplicate.status, 409, 'email déjà utilisé (sans tenir compte des majuscules)');

    const tenant = await api('POST', '/api/auth/register', { body: { nom: 'Kouassi', prenom: 'Jean', email: 'jean@test.ci', telephone: '0700000003', password: 'motdepasse1', type_utilisateur: 'locataire' } });
    tokens.tenant = tenant.body.token;

    const bad = await api('POST', '/api/auth/login', { body: { email: 'awa@test.ci', password: 'mauvais' } });
    assert.equal(bad.status, 401);

    const admin = await api('POST', '/api/auth/login', { body: { email: 'admin@accueilimmo.ci', password: 'admin-test-123' } });
    assert.equal(admin.status, 200);
    tokens.admin = admin.body.token;

    const forged = await api('GET', '/api/auth/me', { token: tokens.owner.slice(0, -3) + 'abc' });
    assert.equal(forged.status, 401, 'un jeton falsifié est refusé');
});

test('publication d\'une annonce avec photo, droits du propriétaire', async () => {
    // Petite image PNG valide (1x1 pixel)
    const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64');
    const form = new FormData();
    form.append('image', new Blob([png], { type: 'image/png' }), 'photo.png');
    const upload = await api('POST', '/api/images', { token: tokens.owner, form });
    assert.equal(upload.status, 201, JSON.stringify(upload.body));

    const fake = new FormData();
    fake.append('image', new Blob(['<?php echo 1;'], { type: 'image/png' }), 'pirate.png');
    assert.equal((await api('POST', '/api/images', { token: tokens.owner, form: fake })).status, 400, 'un faux fichier image est refusé');

    const tenantTry = await api('POST', '/api/properties', { token: tokens.tenant, body: { titre: 'Test', type_bien: 'villa', statut_bien: 'vente', prix: 100000, commune: 'cocody' } });
    assert.equal(tenantTry.status, 403, 'un locataire ne peut pas publier');

    const invalid = await api('POST', '/api/properties', { token: tokens.owner, body: { titre: 'Ok', type_bien: 'chateau', statut_bien: 'location', prix: -5, commune: 'cocody' } });
    assert.equal(invalid.status, 400);

    const created = await api('POST', '/api/properties', { token: tokens.owner, body: {
        titre: 'Studio neuf à Treichville', type_bien: 'studio', statut_bien: 'location', prix: 150000, commune: 'treichville',
        chambres: 1, climatisation: true, image_ids: [upload.body.id]
    } });
    assert.equal(created.status, 201, JSON.stringify(created.body));
    assert.equal(created.body.images.length, 1);
    assert.deepEqual(created.body.equipements, ['climatisation']);
    tokens.propertyId = created.body.id;

    const image = await fetch(`${BASE}/${created.body.images[0].url}`);
    assert.equal(image.headers.get('content-type'), 'image/png');

    const otherOwner = await api('PUT', `/api/properties/1`, { token: tokens.owner, body: { titre: 'Piratage', type_bien: 'villa', statut_bien: 'vente', prix: 1000, commune: 'cocody' } });
    assert.equal(otherOwner.status, 403, 'on ne peut pas modifier l\'annonce d\'un autre');

    const hidden = await api('PATCH', `/api/properties/${tokens.propertyId}/active`, { token: tokens.owner, body: { active: false } });
    assert.equal(hidden.body.active, false);
    assert.equal((await api('GET', `/api/properties/${tokens.propertyId}`)).status, 404, 'une annonce masquée est invisible au public');
    assert.equal((await api('GET', `/api/properties/${tokens.propertyId}`, { token: tokens.owner })).status, 200, 'mais visible par son propriétaire');
    await api('PATCH', `/api/properties/${tokens.propertyId}/active`, { token: tokens.owner, body: { active: true } });
});

test('une demande de visite n\'est visible que par le propriétaire du bien', async () => {
    const sent = await api('POST', '/api/requests', { body: {
        type_demande: 'visite', property_id: tokens.propertyId, nom: 'Moussa Traoré', telephone: '0102030405', message: 'Disponible samedi ?'
    } });
    assert.equal(sent.status, 201);

    const mine = await api('GET', '/api/my/requests', { token: tokens.owner });
    assert.equal(mine.body.length, 1);
    assert.equal(mine.body[0].nom, 'Moussa Traoré');

    const tenantView = await api('GET', '/api/my/requests', { token: tokens.tenant });
    assert.equal(tenantView.body.length, 0, 'un autre utilisateur ne voit pas la demande');

    const forbidden = await api('PATCH', `/api/requests/${mine.body[0].id}`, { token: tokens.tenant, body: { statut: 'traite' } });
    assert.equal(forbidden.status, 403);
    const ok = await api('PATCH', `/api/requests/${mine.body[0].id}`, { token: tokens.owner, body: { statut: 'traite' } });
    assert.equal(ok.status, 200);

    const stats = await api('GET', '/api/my/stats', { token: tokens.owner });
    assert.equal(stats.body.annonces, 1);
    assert.equal(stats.body.demandes, 1);

    const concierge = await api('POST', '/api/requests', { body: { type_demande: 'conciergerie', service_id: 1, nom: 'Client', telephone: '0707070707' } });
    assert.equal(concierge.status, 201);
    const adminView = await api('GET', '/api/my/requests', { token: tokens.admin });
    assert.ok(adminView.body.some(r => r.type_demande === 'conciergerie'), 'l\'admin voit les demandes de conciergerie');
});

test('suppression d\'une annonce par son propriétaire', async () => {
    assert.equal((await api('DELETE', `/api/properties/${tokens.propertyId}`, { token: tokens.tenant })).status, 403);
    assert.equal((await api('DELETE', `/api/properties/${tokens.propertyId}`, { token: tokens.owner })).status, 200);
    assert.equal((await api('GET', `/api/properties/${tokens.propertyId}`, { token: tokens.owner })).status, 404);
});

test('trop de tentatives de connexion sont bloquées', async () => {
    let last;
    for (let i = 0; i < 12; i++) {
        last = await api('POST', '/api/auth/login', { body: { email: 'inconnu@test.ci', password: 'x' } });
    }
    assert.equal(last.status, 429);
});
