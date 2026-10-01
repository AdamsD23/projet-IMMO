# 🚀 GUIDE DE LANCEMENT RAPIDE

## ÉTAPE 1: Démarrer votre serveur local

### Avec XAMPP:
1. Ouvrir **XAMPP Control Panel**
2. Démarrer **Apache** 
3. Démarrer **MySQL**
4. Vérifier que les deux services sont verts ✅

### Avec WAMP:
1. Ouvrir **WAMP Server**
2. Démarrer les services (icône vert)

## ÉTAPE 2: Configurer la base de données

1. Ouvrir **phpMyAdmin** (http://localhost/phpmyadmin)
2. Créer une base de données nommée: `accueil_immo`
3. Importer le fichier: `backend/database.sql`

## ÉTAPE 3: Configurer le backend

Éditer `backend/config/config.php`:
```php
const DB_HOST = 'localhost';
const DB_NAME = 'accueil_immo';
const DB_USER = 'root';
const DB_PASS = ''; // Mettre votre mot de passe si nécessaire
```

## ÉTAPE 4: Lancer le site

### Méthode 1: Navigateur direct
Ouvrez votre navigateur et accédez à:
- 🏠 **Accueil**: http://localhost/prodesticprojet/acceuil2.html
- 🏢 **Locations**: http://localhost/prodesticprojet/pagelocation.html
- 💰 **Ventes**: http://localhost/prodesticprojet/vente.html
- 🛎️ **Conciergerie**: http://localhost/prodesticprojet/conciergerie.html
- 📝 **Déposer annonce**: http://localhost/prodesticprojet/deposeannonce.html
- 🔐 **Connexion**: http://localhost/prodesticprojet/seconnecter.html
- 📊 **Tableau bord**: http://localhost/prodesticprojet/gestion.html

### Méthode 2: Page d'installation
Ouvrez: http://localhost/prodesticprojet/backend/install.html

## ÉTAPE 5: Tester les comptes

Utilisez ces comptes pour tester:

| Type | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@accueilimmo.ci | password123 |
| Propriétaire | mamadou@example.com | password123 |
| Locataire | amina@example.com | password123 |
| Agent | yves@example.com | password123 |

## 🎯 Vérification rapide

### 1. Test API Backend
Ouvrez: http://localhost/prodesticprojet/backend/properties
Vous devriez voir une réponse JSON avec les biens.

### 2. Test Frontend
- La page d'accueil doit s'afficher avec les images
- Les filtres de recherche doivent fonctionner
- Le formulaire de connexion doit être opérationnel

### 3. Test Connexion
1. Allez sur la page de connexion
2. Entrez: admin@accueilimmo.ci / password123
3. Vous devriez être redirigé vers le tableau de bord

## 🔧 Si ça ne marche pas

### Problème: "Database connection failed"
- Vérifiez que MySQL est démarré
- Vérifiez les identifiants dans config.php
- Vérifiez que la base de données `accueil_immo` existe

### Problème: "404 Not Found"
- Vérifiez que vous utilisez le bon URL
- Vérifiez que les fichiers sont dans le bon dossier

### Problème: "Permission denied"
- Vérifiez les permissions des dossiers uploads/
- Créez les dossiers manuellement si nécessaire

## 📱 Test Mobile

Ouvrez votre site sur mobile en utilisant:
- Sur le même ordinateur: http://192.168.1.XXX/prodesticprojet/ (remplacez XXX par votre IP)
- Testez le responsive design

## 🎉 C'est prêt !

Votre site immobilier complet est maintenant en ligne avec:
- ✅ Frontend moderne et responsive
- ✅ Backend PHP complet
- ✅ Base de données MySQL
- ✅ API RESTful
- ✅ Système d'authentification
- ✅ Gestion des biens
- ✅ Formulaire de contact
- ✅ Services conciergerie

**Félicitations ! Votre site immobilier est opérationnel ! 🏠**
