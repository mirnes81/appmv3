# 🚀 Installation PWA MV3 PRO - Dolibarr

## Supabase a été supprimé

L'application fonctionne maintenant **100% avec Dolibarr** via votre module `mv3pro_portail`.

## Architecture

```
PWA (https://app.mv-3pro.ch/pro/)
  ↓ (appels API)
Proxy PHP (/pro/api/index.php)
  ↓ (forward)
API Dolibarr (https://crm.mv-3pro.ch/custom/mv3pro_portail/api/)
  ↓
Base MySQL Dolibarr
```

## 📦 Fichiers à installer

### 1. Proxy API (sur app.mv-3pro.ch)

Déployez ces fichiers dans `/pro/api/` :

```
/pro/api/
├── index.php      ← Proxy qui forward vers Dolibarr
└── .htaccess      ← Config URL rewriting
```

**Installation via FTP :**

```bash
# Connectez-vous à app.mv-3pro.ch via FTP/SFTP
# Allez dans /public_html/pro/
# Créez le dossier api/ s'il n'existe pas
mkdir api
cd api

# Uploadez les 2 fichiers :
- index.php
- .htaccess
```

**Permissions :**

```bash
chmod 755 /pro/api
chmod 644 /pro/api/index.php
chmod 644 /pro/api/.htaccess
```

### 2. API Dolibarr (sur crm.mv-3pro.ch)

Déployez ces fichiers dans `/custom/mv3pro_portail/api/` :

```
/custom/mv3pro_portail/api/
├── auth_login.php          ← POST /auth/login
├── auth_me.php             ← GET /auth/me
├── auth_logout.php         ← POST /auth/logout
├── auth_helper.php         ← Helper auth (requis)
├── forms_list.php          ← GET /forms/list
├── forms_get.php           ← GET /forms/get/{id}
├── forms_create.php        ← POST /forms/create
├── forms_upload.php        ← POST /forms/upload
├── forms_pdf.php           ← GET /forms/pdf/{id}
├── forms_send_email.php    ← POST /forms/send_email
├── mobile_get_projects.php ← GET /mobile_get_projects
└── cors_config.php         ← Config CORS (existant)
```

**Installation via FTP :**

```bash
# Connectez-vous à crm.mv-3pro.ch via FTP/SFTP
cd /var/www/html/dolibarr/custom/mv3pro_portail/api/

# Uploadez tous les fichiers listés ci-dessus
```

**Permissions :**

```bash
chmod 644 /var/www/html/dolibarr/custom/mv3pro_portail/api/*.php
chown www-data:www-data /var/www/html/dolibarr/custom/mv3pro_portail/api/*.php
```

### 3. PWA Frontend (sur app.mv-3pro.ch)

Déployez le contenu du dossier `pro/` :

```bash
# Via FTP/SFTP
cd /public_html/pro/

# Uploadez tout le contenu du dossier pro/ :
- index.html
- manifest.json
- sw.js
- assets/*
- api/* (déjà fait à l'étape 1)
```

## 🔧 Configuration

### 1. Vérifier .env (déjà configuré)

```env
VITE_DEMO_MODE=false
VITE_API_BASE=https://app.mv-3pro.ch/pro/api
VITE_DOLIBARR_URL=https://crm.mv-3pro.ch
```

### 2. Vérifier base de données

Les tables `llx_mv3_rapport` et `llx_mv3_rapport_photo` doivent exister.

Vérifiez :

```sql
-- Via phpMyAdmin ou MySQL CLI
SHOW TABLES LIKE 'llx_mv3_rapport%';

-- Colonnes GPS et météo
SHOW COLUMNS FROM llx_mv3_rapport LIKE 'gps_%';
SHOW COLUMNS FROM llx_mv3_rapport LIKE 'meteo_%';
```

Si colonnes manquantes, appliquez :

```bash
mysql -u root -p dolibarr < new_dolibarr/mv3pro_portail/sql/llx_mv3_rapport_add_features.sql
```

### 3. Créer dossier uploads

```bash
# Sur crm.mv-3pro.ch
mkdir -p /var/www/dolibarr_documents/mv3pro_portail/rapports
mkdir -p /var/www/dolibarr_documents/mv3pro_portail/pdf
chmod 755 /var/www/dolibarr_documents/mv3pro_portail/rapports
chmod 755 /var/www/dolibarr_documents/mv3pro_portail/pdf
chown -R www-data:www-data /var/www/dolibarr_documents/mv3pro_portail/
```

## 🧪 Tests

### Test 1 : Proxy API

```bash
curl https://app.mv-3pro.ch/pro/api/mobile_get_projects.php
```

**Résultat attendu :**

```json
{
  "error": "Token requis"
}
```

C'est normal, ça prouve que le proxy fonctionne.

### Test 2 : Login

```bash
curl -X POST "https://app.mv-3pro.ch/pro/api/auth_login.php" \
  -H "Content-Type: application/json" \
  -d '{"login": "admin", "password": "MOT_DE_PASSE"}'
```

**Résultat attendu :**

```json
{
  "success": true,
  "token": "eyJ1c2VyX2lk...",
  "user": {
    "id": "1",
    "login": "admin",
    "firstname": "John",
    "lastname": "Doe"
  }
}
```

### Test 3 : Récupérer l'utilisateur

```bash
TOKEN="<token du test précédent>"

curl "https://app.mv-3pro.ch/pro/api/auth_me.php" \
  -H "X-Auth-Token: $TOKEN"
```

**Résultat attendu :**

```json
{
  "success": true,
  "user": {...}
}
```

### Test 4 : Liste des projets

```bash
curl "https://app.mv-3pro.ch/pro/api/mobile_get_projects.php?limit=10" \
  -H "X-Auth-Token: $TOKEN"
```

### Test 5 : Liste des rapports

```bash
curl "https://app.mv-3pro.ch/pro/api/forms_list.php?type=rapport&limit=10" \
  -H "X-Auth-Token: $TOKEN"
```

### Test 6 : PWA Frontend

Ouvrez dans votre navigateur :

```
https://app.mv-3pro.ch/pro/
```

**Login :**
- Email : votre login Dolibarr
- Password : votre mot de passe Dolibarr

## 🎯 Fonctionnalités disponibles

### Authentification

- Login par email/password (comptes Dolibarr)
- Token JWT (expire après 30 jours)
- Logout

### Rapports

- Liste des rapports
- Création de rapport avec :
  - Client, description, observations
  - Horaires (début/fin)
  - GPS (latitude/longitude)
  - Météo (température, conditions)
  - Matériaux utilisés
  - Photos (upload base64)
- Génération PDF professionnelle
- Envoi par email

### Projets

- Liste des projets Dolibarr
- Filtrage par statut

## 📱 Utilisation

### 1. Connexion

L'utilisateur se connecte avec son login et mot de passe Dolibarr.

### 2. Dashboard

Affiche les stats :
- Rapports du jour
- Rapports de la semaine
- Total des rapports

### 3. Nouveau rapport

L'utilisateur remplit :
- Date
- Client
- Description
- Observations
- Horaires
- Photos (caméra/galerie)

Les données GPS et météo sont automatiques.

### 4. Générer PDF

Génère un PDF professionnel avec :
- En-tête avec logo
- Infos client
- Détails du rapport
- Photos (max 4)
- Conditions météo

### 5. Envoyer par email

Envoie le PDF par email via le SMTP configuré dans Dolibarr.

## 🐛 Dépannage

### Erreur : "Token requis"

**Cause :** L'utilisateur n'est pas connecté ou token expiré

**Solution :** Se reconnecter

### Erreur : "Identifiants invalides"

**Cause :** Login ou mot de passe incorrect

**Solution :**
1. Vérifier le compte dans Dolibarr
2. Vérifier que le compte est actif (`statut = 1`)
3. Tester en se connectant sur crm.mv-3pro.ch

### Erreur : "Formulaire non trouvé"

**Cause :** L'ID du rapport n'existe pas

**Solution :** Vérifier :

```sql
SELECT * FROM llx_mv3_rapport WHERE rowid = 123;
```

### Erreur : "Erreur proxy"

**Cause :** Le proxy ne peut pas contacter Dolibarr

**Solution :**
1. Vérifier que crm.mv-3pro.ch est accessible
2. Vérifier les logs Apache :

```bash
tail -f /var/log/apache2/error.log
```

### Photos ne s'uploadent pas

**Cause :** Permissions dossier

**Solution :**

```bash
chmod 755 /var/www/dolibarr_documents/mv3pro_portail/rapports
chown www-data:www-data /var/www/dolibarr_documents/mv3pro_portail/rapports
```

### PDF ne se génère pas

**Cause :** Extension TCPDF manquante

**Solution :**

```bash
# Vérifier que TCPDF est présent
ls /var/www/html/dolibarr/includes/tecnickcom/tcpdf/

# Si absent, réinstaller Dolibarr ou le module PDF
```

### Email ne s'envoie pas

**Cause :** SMTP non configuré dans Dolibarr

**Solution :**

Dans Dolibarr :
1. Accueil → Configuration → Emails
2. Configurer SMTP
3. Tester l'envoi

## 🔒 Sécurité

### API

- Toutes les routes (sauf login) nécessitent un token
- Token expire après 30 jours
- Validation côté serveur
- SQL échappé avec `$db->escape()`

### Fichiers

- Upload uniquement images
- Base64 décodé et vérifié
- Stockage dans dossier sécurisé

### CORS

- Headers configurés dans `cors_config.php`
- OPTIONS preflight géré

## 📊 Base de données

### Tables utilisées

```sql
-- Rapports
llx_mv3_rapport
llx_mv3_rapport_photo

-- Projets (lecture seule)
llx_projet
llx_societe

-- Utilisateurs (auth)
llx_user
```

### Champs requis dans llx_mv3_rapport

```sql
- rowid (PK)
- entity
- fk_user
- date_rapport
- zone_travail
- description
- observations
- heures_debut
- heures_fin
- temps_total
- travaux_realises
- gps_latitude
- gps_longitude
- meteo_temperature
- meteo_condition
- statut
- date_creation
- date_modification
```

## 🆘 Support

### Logs à vérifier

**Apache (crm.mv-3pro.ch) :**

```bash
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log
```

**PHP :**

```bash
tail -f /var/log/php/error.log
```

**Console navigateur :**

```
F12 → Console
```

### Commandes utiles

```bash
# Tester connexion MySQL
mysql -u root -p dolibarr

# Vérifier tables
SHOW TABLES LIKE 'llx_mv3_%';

# Derniers rapports
SELECT * FROM llx_mv3_rapport ORDER BY date_creation DESC LIMIT 5;

# Utilisateurs avec API key
SELECT login, api_key FROM llx_user WHERE api_key IS NOT NULL;
```

## ✅ Checklist finale

- [ ] Proxy déployé dans `/pro/api/`
- [ ] API Dolibarr déployées dans `/custom/mv3pro_portail/api/`
- [ ] PWA déployée dans `/pro/`
- [ ] Tables `llx_mv3_rapport*` existent
- [ ] Colonnes GPS/météo ajoutées
- [ ] Dossiers uploads créés et permissions OK
- [ ] Test login réussi
- [ ] Test création rapport réussi
- [ ] Test génération PDF réussi
- [ ] Test envoi email réussi

---

**Version :** 1.0.0
**Date :** 26 Décembre 2024
**Module :** mv3pro_portail
**Supabase :** SUPPRIMÉ
