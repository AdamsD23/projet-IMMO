# Accueil Immo — plateforme immobilière à Abidjan

Site de location et de vente de biens immobiliers (Abidjan, Assinie, Bingerville…) avec espace propriétaire et conciergerie.

**Stack** : PHP 8 (API REST sans framework) · MySQL · JavaScript · Tailwind CSS

## Fonctionnalités

**Visiteurs**
- Recherche par location/vente, commune, type de bien, budget, chambres, équipements (piscine, groupe électrogène…) et mots-clés ; tri par prix, date ou popularité ; pagination
- Fiche détaillée : galerie photo, équipements, biens similaires, contact WhatsApp ou téléphone
- Demande de visite ou question envoyée directement au propriétaire
- Favoris (enregistrés sur l'appareil)
- Conciergerie : transfert aéroport, ménage, gestion locative… avec formulaire de réservation
- Assistant d'orientation et lien WhatsApp sur toutes les pages

**Propriétaires et agents**
- Inscription / connexion (sessions JWT)
- Publication d'annonces avec jusqu'à 6 photos, modification, masquage, suppression
- Tableau de bord : vues, demandes reçues (statut nouveau / en cours / traité), réponse en un clic sur WhatsApp
- Profil et changement de mot de passe

**Administrateur** : voit et gère toutes les annonces et toutes les demandes (dont la conciergerie).

## Sécurité
- Mots de passe hachés (`password_hash`), jetons signés HMAC-SHA256 avec expiration, compte suspendu = accès coupé
- Requêtes SQL préparées, validation de toutes les entrées, vérification du vrai type des images envoyées
- Un utilisateur ne peut modifier que ses annonces et ne voit que les demandes qui le concernent
- Limitation à 10 tentatives de connexion par IP sur 15 minutes
- Le code PHP, la configuration et les tests ne sont jamais servis (`.htaccess` et `router.php`)

## Lancer en local (XAMPP)

```bash
cp .env.example .env        # adapter la connexion MySQL si besoin
php api/setup.php           # crée la base, les tables et les données de démo
php -S localhost:8000 router.php
```

Ouvrir http://localhost:8000. Comptes de démonstration (mot de passe `demo12345`) : `proprietaire@demo.ci`, `agent@demo.ci`.
Administrateur : `admin@accueilimmo.ci` avec le mot de passe défini par `ADMIN_PASSWORD` (`admin123` par défaut).

Avec Apache (XAMPP), placer le dossier dans `htdocs` : le fichier `.htaccess` gère les URL `/api/...`.

## Tests

Tests d'intégration (Node 18+) sur une base dédiée, recréée à chaque lancement :

```bash
DB_NAME=immo_tests npm test
```

## Mise en ligne (Render + Aiven)

Le fichier `render.yaml` déploie l'image Docker (PHP 8.2 + Apache). Sur Render : *New → Blueprint* → ce dépôt, puis renseigner
`DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_SSL_CA` (certificat Aiven) et `ADMIN_PASSWORD`.
Une base `accueil_immo` est créée automatiquement au démarrage.

## Structure

```
index.html, annonces.html, annonce.html   Pages publiques
conciergerie.html, connexion.html         Services et comptes
espace.html, publier.html                 Espace propriétaire
assets/js/app.js                          Fonctions partagées (API, en-tête, cartes, favoris…)
api/index.php                             API REST (routes)
api/lib.php, api/config.php               Base de données, validation, JWT, configuration
api/setup.php                             Tables et données de démonstration
tests/api.test.js                         Tests d'intégration
archive/                                  Ancienne version (maquettes v1), non utilisée
```

## API (résumé)

| Méthode | Route | Accès |
|---|---|---|
| GET | `/api/properties` (`statut, commune, type, prix_min, prix_max, chambres_min, equipements, q, tri, page`) | public |
| GET | `/api/properties/featured`, `/api/properties/{id}` | public |
| POST / PUT / DELETE | `/api/properties[/{id}]`, PATCH `/api/properties/{id}/active` | propriétaire de l'annonce |
| POST | `/api/images` (multipart `image`) | connecté |
| POST | `/api/requests` (visite, information, conciergerie, contact) | public |
| GET / PATCH | `/api/my/requests`, `/api/requests/{id}` | destinataire |
| GET | `/api/my/properties`, `/api/my/stats` | connecté |
| POST | `/api/auth/register`, `/api/auth/login` · GET/PUT `/api/auth/me` · PUT `/api/auth/password` | — |
| GET | `/api/concierge/services`, `/api/meta`, `/api/health` | public |

---
Projet réalisé par **Adams Diarra** — Abidjan, Côte d'Ivoire.
