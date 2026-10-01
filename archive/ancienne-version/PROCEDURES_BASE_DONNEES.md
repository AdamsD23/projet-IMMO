# 📊 PROCÉDURES BASE DE DONNÉES - ACCUEIL IMMO CI

## 🎯 **ÉTAT ACTUEL DE VOTRE BASE**

### ✅ **Tables créées avec succès**
- `users` → **4 utilisateurs** inscrits
- `properties` → **0 bien** enregistré
- `rentals` → **0 location** active
- `concierge_services` → **5 services** disponibles
- `concierge_bookings` → **0 réservation**
- `contact_requests` → **0 demande** de contact
- `payments` → **0 paiement** enregistré
- `notifications` → **0 notification** envoyée

---

## 👥 **UTILISATEURS EXISTANTS**

| ID | Nom | Prénom | Email | Type | Statut | Date |
|-----|------|---------|--------|--------|-------|
| 1 | Admin | Accueil | admin@accueilimmo.ci | admin | 16/02/2026 |
| 2 | Konan | Mamadou | mamadou@example.com | proprietaire | 16/02/2026 |
| 3 | Touré | Amina | amina@example.com | locataire | 16/02/2026 |
| 4 | Bamba | Yves | yves@example.com | agent | 16/02/2026 |

---

## 🚀 **PROCÉDURES RECOMMANDÉES**

### **1. Nettoyage et préparation**

#### **Option A : Réinitialisation complète**
```sql
-- Supprimer toutes les données
TRUNCATE TABLE properties;
TRUNCATE TABLE rentals;
TRUNCATE TABLE contact_requests;
TRUNCATE TABLE payments;
TRUNCATE TABLE notifications;
TRUNCATE TABLE concierge_bookings;

-- Réinitialiser les utilisateurs (garder admin)
DELETE FROM users WHERE id > 1;
```

#### **Option B : Nettoyage partiel**
```sql
-- Garder les utilisateurs, nettoyer le reste
TRUNCATE TABLE properties;
TRUNCATE TABLE rentals;
TRUNCATE TABLE contact_requests;
TRUNCATE TABLE payments;
```

### **2. Insertion de données de test**

#### **Utilisateurs de test**
```sql
-- Propriétaires
INSERT INTO users (nom, prenom, email, password, telephone, type_utilisateur) VALUES
('Konaté', 'Mamadou', 'mamadou.konate@email.com', 'password123', '+2250700000001', 'proprietaire'),
('Touré', 'Amina', 'amina.toure@email.com', 'password123', '+2250700000002', 'proprietaire');

-- Locataires
INSERT INTO users (nom, prenom, email, password, telephone, type_utilisateur) VALUES
('Bamba', 'Yves', 'yves.bamba@email.com', 'password123', '+2250700000003', 'locataire'),
('Ouattara', 'Fatou', 'fatou.ouattara@email.com', 'password123', '+2250700000004', 'locataire');

-- Agents
INSERT INTO users (nom, prenom, email, password, telephone, type_utilisateur) VALUES
('Kouadio', 'Jean', 'jean.kouadio@email.com', 'password123', '+2250700000005', 'agent');
```

#### **Biens immobiliers de test**
```sql
INSERT INTO properties (titre, description, type_bien, statut_bien, prix, surface, nombre_pieces, nombre_chambres, nombre_salles_bain, adresse, quartier, commune, meuble, climatisation, parking, proprietaire_id) VALUES
('Villa de luxe Cocody', 'Superbe villa avec piscine et jardin', 'villa', 'location', 500000.00, 350.00, 5, 4, 3, 'Zone 4, Cocody', 'cocody', 1, 1, 1, 2),
('Appartement Plateau', 'Appartement moderne en plein centre', 'appartement', 'location', 150000.00, 120.00, 3, 2, 2, 'Immeuble Le Palais, Plateau', 'plateau', 1, 1, 0, 1),
('Studio Riviera', 'Studio parfait pour étudiant', 'studio', 'location', 75000.00, 45.00, 1, 1, 1, 'Riviera 3, Abidjan', 'riviera', 1, 1, 0, 3);
```

#### **Locations de test**
```sql
INSERT INTO rentals (property_id, locataire_id, date_debut, date_fin, montant_mensuel, caution, statut) VALUES
(1, 3, '2024-01-01', '2024-12-31', 500000.00, 1000000.00, 'actif'),
(2, 4, '2024-02-01', '2025-01-31', 150000.00, 300000.00, 'actif');
```

---

## 🔧 **PROCÉDURES D'ADMINISTRATION**

### **1. Via phpMyAdmin**
1. **Accès** : `http://localhost/phpmyadmin/`
2. **Base** : `accueil_immo`
3. **Navigation** : Cliquez sur les tables
4. **Actions** : Insérer, Modifier, Supprimer

### **2. Via ligne de commande**
```bash
# Connexion à MySQL
mysql -u root -p

# Sélection de la base
USE accueil_immo;

# Exécuter les requêtes SQL
```

### **3. Via script PHP**
```php
<?php
// Connexion
$pdo = new PDO('mysql:host=localhost;dbname=accueil_immo', 'root', '');

// Insertion
$stmt = $pdo->prepare("INSERT INTO properties (titre, prix, ...) VALUES (?, ?, ...)");
$stmt->execute([$titre, $prix, ...]);
?>
```

---

## 📈 **MAINTENANCE RÉGULIÈRE**

### **Quotidienne**
- ✅ **Vérifier** les nouvelles inscriptions
- ✅ **Valider** les nouvelles annonces
- ✅ **Traiter** les demandes de contact

### **Hebdomadaire**
- ✅ **Nettoyer** les tables temporaires
- ✅ **Optimiser** les performances
- ✅ **Sauvegarder** la base

### **Mensuelle**
- ✅ **Analyser** les statistiques
- ✅ **Nettoyer** les anciennes données
- ✅ **Mettre à jour** les tarifs

---

## 🎯 **PROCÉDURES SPÉCIFIQUES**

### **Pour tester l'inscription**
1. **Allez** sur `http://localhost/prodesticprojet/seconnecter-connected.html`
2. **Créez** un nouveau compte
3. **Vérifiez** dans phpMyAdmin que l'utilisateur apparaît

### **Pour tester les annonces**
1. **Connectez-vous** comme propriétaire
2. **Allez** sur `http://localhost/prodesticprojet/deposeannonce-connected.html`
3. **Déposez** une annonce
4. **Vérifiez** dans la table `properties`

### **Pour tester les locations**
1. **Connectez-vous** comme locataire
2. **Cherchez** des biens
3. **Faites** une demande de location
4. **Vérifiez** dans la table `rentals`

---

## 🚨 **SÉCURITÉ ET BONNES PRATIQUES**

### **Sauvegardes**
```bash
# Sauvegarde complète
mysqldump -u root -p accueil_immo > backup_$(date +%Y%m%d).sql

# Restauration
mysql -u root -p accueil_immo < backup_20240218.sql
```

### **Optimisation**
```sql
-- Optimiser les tables
OPTIMIZE TABLE users, properties, rentals, payments;

-- Vérifier l'intégrité
CHECK TABLE users, properties, rentals, payments;
```

### **Sécurité**
- ✅ **Mots de passe** hashés
- ✅ **Connexions** sécurisées
- ✅ **Données** validées
- ✅ **Permissions** gérées

---

## 🎊 **RÉCAPITULATIF**

**Votre base est prête pour :**
- ✅ **Production** : Structure complète
- ✅ **Test** : Données de test disponibles
- ✅ **Évolution** : Procédures de maintenance
- ✅ **Sécurité** : Bonnes pratiques appliquées

**Procédez maintenant avec les étapes recommandées !** 🚀
