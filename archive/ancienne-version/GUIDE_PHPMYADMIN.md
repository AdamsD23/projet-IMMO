# 📊 GUIDE PHPMYADMIN - ACCUEIL IMMO

## 🔧 ÉTAPES POUR VÉRIFIER LES INSCRIPTIONS

### 1️⃣ ACCÈS À PHPMYADMIN
- **URL**: `http://localhost/phpmyadmin/`
- **Serveur**: `localhost` ou `127.0.0.1`
- **Utilisateur**: `root`
- **Mot de passe**: `123456789` (vérifier votre configuration XAMPP)

---

### 2️⃣ VÉRIFICATION DE LA BASE DE DONNÉES

#### 🗄️ Base de données à sélectionner
```
Nom de la base: accueil_immo
```

#### 📋 Table à vérifier
```
Table: users
```

---

### 3️⃣ STRUCTURE DE LA TABLE USERS

| Champ | Type | Description |
|-------|------|-------------|
| id | INT AUTO_INCREMENT | ID unique |
| nom | VARCHAR(100) | Nom de famille |
| prenom | VARCHAR(100) | Prénom |
| email | VARCHAR(150) UNIQUE | Email unique |
| password | VARCHAR(255) | Mot de passe hashé |
| telephone | VARCHAR(20) | Téléphone |
| type_utilisateur | ENUM | Type d'utilisateur |
| photo_profil | VARCHAR(255) | Photo de profil |
| statut | ENUM | Statut du compte |
| date_inscription | TIMESTAMP | Date d'inscription |
| last_login | TIMESTAMP | Dernière connexion |
| created_at | TIMESTAMP | Date de création |
| updated_at | TIMESTAMP | Date de mise à jour |

---

### 4️⃣ COMMENT VÉRIFIER LES INSCRIPTIONS

#### 🔍 **Méthode 1: Requête SQL**
```sql
SELECT * FROM users ORDER BY date_inscription DESC;
```

#### 🔍 **Méthode 2: Navigation**
1. Sélectionner la base `accueil_immo`
2. Cliquer sur la table `users`
3. Parcourir les enregistrements

#### 🔍 **Méthode 3: Filtres**
- Utiliser les filtres de phpMyAdmin pour rechercher par email
- Trier par date d'inscription pour voir les derniers
- Vérifier les champs email, nom, prenom, telephone

---

### 5️⃣ UTILISATEUR ADMIN PAR DÉFAUT

Si vous avez exécuté le script `setup_database.php`:

```
Email: admin@accueilimmo.ci
Mot de passe: admin123
Type: admin
Statut: actif
```

---

### 6️⃣ TEST D'INSCRIPTION EN DIRECT

#### 🧪 **Test via phpMyAdmin**
1. Aller dans la table `users`
2. Cliquer sur "Insérer"
3. Remplir les champs:
   ```sql
   nom: 'Test'
   prenom: 'User'
   email: 'test@example.com'
   password: 'test123456' (sera hashé automatiquement)
   telephone: '+2250700000000'
   type_utilisateur: 'proprietaire'
   statut: 'actif'
   ```
4. Exécuter

#### 🧪 **Test via formulaire web**
1. Aller sur: `http://localhost/prodesticprojet/acceuil2.html`
2. Cliquer sur "S'INSCRIRE"
3. Remplir le formulaire
4. Soumettre et vérifier dans phpMyAdmin

---

### 7️⃣ DÉBOGAGE SI PROBLÈMES

#### ❌ **Erreurs communes**
- **Base non trouvée**: Vérifier que MySQL/XAMPP est démarré
- **Connexion refusée**: Vérifier identifiants MySQL
- **Table vide**: Exécuter `setup_database.php`
- **Champs manquants**: Vérifier la structure de la table

#### 🔧 **Actions correctives**
```sql
-- Recréer la table si nécessaire
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    type_utilisateur ENUM('proprietaire', 'locataire', 'agent', 'admin') DEFAULT 'proprietaire',
    photo_profil VARCHAR(255),
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 8️⃣ VÉRIFICATION FINALE

#### ✅ **Points de contrôle**
- [ ] Base `accueil_immo` existe
- [ ] Table `users` existe avec tous les champs
- [ ] Utilisateur admin par défaut présent
- [ ] Test d'inscription via formulaire fonctionne
- [ ] Données bien enregistrées dans phpMyAdmin

#### 🎯 **Objectif final**
Vérifier que chaque inscription via le formulaire web apparaît bien dans la table `users` de phpMyAdmin avec toutes les informations correctes.

---

## 🚀 **RACCOURCIS UTILES**

### Liens rapides
- **phpMyAdmin**: `http://localhost/phpmyadmin/`
- **Site web**: `http://localhost/prodesticprojet/acceuil2.html`
- **Setup BDD**: `http://localhost/prodesticprojet/backend/setup_database.php`
- **Test API**: `http://localhost/prodesticprojet/backend/test_api.php`

### Requêtes SQL utiles
```sql
-- Voir tous les utilisateurs
SELECT * FROM users;

-- Voir les 5 dernières inscriptions
SELECT * FROM users ORDER BY date_inscription DESC LIMIT 5;

-- Vérifier un email spécifique
SELECT * FROM users WHERE email = 'votre@email.com';

-- Compter par type d'utilisateur
SELECT type_utilisateur, COUNT(*) as count FROM users GROUP BY type_utilisateur;
```

---

📝 **Note**: Gardez ce guide ouvert pendant vos tests pour référence rapide.
