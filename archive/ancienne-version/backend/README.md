# Accueil Immo CI - Backend API

Backend PHP complet pour le site immobilier Accueil Immo CI.

## 🚀 Installation

### Prérequis
- PHP 8.0+
- MySQL 5.7+ ou MariaDB 10.2+
- Apache ou Nginx
- Extensions PHP: PDO, JSON, GD, cURL, mbstring

### Configuration

1. **Cloner le projet**
```bash
git clone <repository-url>
cd prodesticprojet/backend
```

2. **Configurer la base de données**
```bash
# Éditer le fichier config/config.php
# Modifier les constantes de base de données:
const DB_HOST = 'localhost';
const DB_NAME = 'accueil_immo';
const DB_USER = 'root';
const DB_PASS = 'votre_mot_de_passe';
```

3. **Créer la base de données**
```bash
# Via l'API (recommandé)
curl -X POST http://localhost/prodesticprojet/backend/setup/database

# Ou manuellement via phpMyAdmin
# Importer le fichier SQL généré par le script d'installation
```

4. **Insérer les données de test**
```bash
curl -X POST http://localhost/prodesticprojet/backend/setup/test-data
```

5. **Vérifier l'installation**
```bash
curl -X GET http://localhost/prodesticprojet/backend/setup/check
```

## 📁 Structure des fichiers

```
backend/
├── config/
│   ├── database.php      # Configuration et création de la base de données
│   └── config.php        # Configuration générale de l'application
├── models/
│   ├── User.php          # Modèle utilisateur
│   ├── Property.php      # Modèle bien immobilier
│   ├── Rental.php        # Modèle location
│   ├── Payment.php       # Modèle paiement
│   └── Contact.php       # Modèle demande de contact
├── controllers/
│   ├── AuthController.php        # Authentification
│   ├── PropertyController.php    # Gestion des biens
│   ├── DashboardController.php   # Tableau de bord
│   ├── ContactController.php     # Formulaire de contact
│   ├── ConciergeController.php   # Services conciergerie
│   └── NotificationController.php # Notifications
├── utils/
│   └── utils.php         # Fonctions utilitaires
├── setup/
│   └── setup.php         # Script d'installation
├── uploads/
│   ├── properties/       # Photos des biens
│   └── profiles/         # Photos de profil
├── logs/                 # Fichiers de log
└── index.php            # Point d'entrée API
```

## 🔐 API Endpoints

### Authentification
- `POST /auth/register` - Inscription
- `POST /auth/login` - Connexion
- `POST /auth/logout` - Déconnexion
- `GET /auth/check` - Vérifier l'authentification
- `PUT /auth/update` - Mettre à jour le profil
- `PUT /auth/password` - Changer le mot de passe
- `POST /auth/upload` - Télécharger photo de profil

### Biens Immobiliers
- `POST /properties` - Créer un bien
- `GET /properties` - Lister les biens (avec filtres)
- `GET /properties/search` - Rechercher des biens
- `GET /properties/featured` - Biens en vedette
- `GET /properties/my` - Mes biens
- `GET /properties/stats` - Statistiques
- `POST /properties/upload` - Télécharger images
- `GET /properties/{id}` - Détails d'un bien
- `PUT /properties/{id}` - Mettre à jour un bien
- `DELETE /properties/{id}` - Supprimer un bien
- `PUT /properties/{id}/occupancy` - Mettre à jour statut occupation
- `PUT /properties/{id}/toggle-featured` - Ajouter/retirer featured

### Dashboard
- `GET /dashboard/stats` - Statistiques générales
- `GET /dashboard/recent` - Activités récentes
- `GET /dashboard/revenue` - Revenus

### Contact
- `POST /contact` - Envoyer une demande
- `GET /contact` - Lister les demandes

### Conciergerie
- `GET /concierge/services` - Services disponibles
- `POST /concierge/book` - Réserver un service
- `GET /concierge/bookings` - Mes réservations

### Notifications
- `GET /notifications` - Lister les notifications
- `PUT /notifications/{id}/read` - Marquer comme lue

## 🔧 Configuration

### Base de données
```php
// config/config.php
const DB_HOST = 'localhost';
const DB_NAME = 'accueil_immo';
const DB_USER = 'root';
const DB_PASS = 'password';
```

### Email
```php
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USERNAME = 'contact@accueilimmo.ci';
const SMTP_PASSWORD = 'votre_mot_de_passe';
```

### WhatsApp
```php
const WHATSAPP_API_URL = 'https://graph.facebook.com/v18.0/';
const WHATSAPP_PHONE_ID = 'votre_phone_id';
const WHATSAPP_ACCESS_TOKEN = 'votre_access_token';
```

## 📊 Exemples d'utilisation

### Inscription d'un utilisateur
```bash
curl -X POST http://localhost/prodesticprojet/backend/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Koné",
    "prenom": "Mamadou",
    "email": "mamadou@example.com",
    "password": "password123",
    "telephone": "+2250777777777",
    "type_utilisateur": "proprietaire"
  }'
```

### Connexion
```bash
curl -X POST http://localhost/prodesticprojet/backend/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "mamadou@example.com",
    "password": "password123"
  }'
```

### Créer un bien
```bash
curl -X POST http://localhost/prodesticprojet/backend/properties \
  -H "Content-Type: application/json" \
  -d '{
    "titre": "Villa moderne à Cocody",
    "description": "Belle villa avec piscine",
    "type_bien": "villa",
    "statut_bien": "location",
    "prix": 250000,
    "surface": 250,
    "nombre_pieces": 5,
    "nombre_chambres": 4,
    "adresse": "Riviera 3, Abidjan",
    "commune": "cocody",
    "meuble": true,
    "climatisation": true,
    "piscine": true
  }'
```

### Rechercher des biens
```bash
curl -X GET "http://localhost/prodesticprojet/backend/properties?commune=cocody&statut_bien=location&prix_min=100000&prix_max=500000"
```

## 🛡️ Sécurité

- **Validation des entrées** avec nettoyage XSS
- **Hashage des mots de passe** avec Argon2ID
- **Tokens JWT** pour l'authentification
- **Protection CSRF** avec tokens
- **Validation des types de fichiers** pour les uploads
- **Limitation de taille** des fichiers uploadés
- **Logs d'erreurs** et d'activités

## 📝 Logs

Les logs sont stockés dans `backend/logs/`:
- `error.log` - Erreurs système
- `info.log` - Informations générales

## 🚀 Déploiement

### Apache
```apache
# .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Nginx
```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/prodesticprojet/backend;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 🔄 Maintenance

### Sauvegarde
```bash
# Sauvegarde de la base de données
mysqldump -u root -p accueil_immo > backup_$(date +%Y%m%d).sql

# Sauvegarde des fichiers
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

### Nettoyage
```bash
# Nettoyer les anciens logs (plus de 30 jours)
find logs/ -name "*.log" -mtime +30 -delete

# Nettoyer les uploads orphelins
php scripts/cleanup_uploads.php
```

## 🐛 Débogage

Activer le mode debug:
```php
// Dans index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Vérifier les logs d'erreurs:
```bash
tail -f logs/error.log
```

## 📞 Support

Pour toute question ou problème technique:
- Email: support@accueilimmo.ci
- Téléphone: +225 07 00 00 00 00

---

**© 2024 Accueil Immo CI - Tous droits réservés**
