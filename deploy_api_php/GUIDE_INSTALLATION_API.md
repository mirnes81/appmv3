# GUIDE D'INSTALLATION - API MOBILE MV3 PRO

## Vue d'ensemble

Cette API PHP permet à votre application mobile PWA de communiquer avec votre système Dolibarr.

**Serveur Dolibarr**: https://crm.mv-3pro.ch
**URL API finale**: https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile
**Application PWA**: https://app.mv-3pro.ch/pro/

---

## ÉTAPE 1: Upload de l'API sur le serveur Dolibarr

### 1.1 Connexion SSH au serveur
```bash
ssh votre_utilisateur@crm.mv-3pro.ch
```

### 1.2 Créer le dossier de l'API
```bash
cd /var/www/dolibarr/htdocs/custom/mv3pro_portail
mkdir -p api_mobile
```

### 1.3 Upload des fichiers
Depuis votre ordinateur local, uploadez le dossier `api_mobile`:

**Option A - Avec SCP:**
```bash
scp -r api_mobile/* utilisateur@crm.mv-3pro.ch:/var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/
```

**Option B - Avec SFTP:**
```bash
sftp utilisateur@crm.mv-3pro.ch
cd /var/www/dolibarr/htdocs/custom/mv3pro_portail
put -r api_mobile
```

**Option C - Avec Rsync:**
```bash
rsync -avz api_mobile/ utilisateur@crm.mv-3pro.ch:/var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/
```

### 1.4 Définir les permissions
```bash
ssh utilisateur@crm.mv-3pro.ch
cd /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile
chmod 755 .
chmod 644 *.php
chmod 755 auth/ reports/ dashboard/ weather/
chmod 644 auth/*.php reports/*.php dashboard/*.php weather/*.php
```

---

## ÉTAPE 2: Configuration de la base de données

### 2.1 Éditer le fichier config.php
```bash
nano /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/config.php
```

### 2.2 Modifier les paramètres de connexion
```php
define('DOLIBARR_DB_HOST', 'localhost');  // ou l'IP de votre serveur MySQL
define('DOLIBARR_DB_NAME', 'nom_de_votre_base_dolibarr');
define('DOLIBARR_DB_USER', 'utilisateur_mysql');
define('DOLIBARR_DB_PASS', 'mot_de_passe_mysql');

// IMPORTANT: Générez une clé secrète unique
define('JWT_SECRET', 'CHANGEZ_CETTE_CLE_SECRETE_PAR_UNE_VALEUR_ALEATOIRE');
```

**Pour générer une clé secrète aléatoire:**
```bash
openssl rand -base64 32
```

### 2.3 Sauvegarder et quitter
- Appuyez sur `Ctrl+O` pour sauvegarder
- Appuyez sur `Ctrl+X` pour quitter

---

## ÉTAPE 3: Créer les tables nécessaires

### 3.1 Se connecter à MySQL
```bash
mysql -u utilisateur_mysql -p nom_de_votre_base_dolibarr
```

### 3.2 Exécuter les requêtes SQL
```sql
-- Table pour les photos des rapports
CREATE TABLE IF NOT EXISTS llx_mv3_rapport_photos (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    rapport_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size INT DEFAULT 0,
    uploaded_at DATETIME NOT NULL,
    INDEX idx_rapport_id (rapport_id)
);
```

---

## ÉTAPE 4: Test de l'API

### 4.1 Tester l'endpoint de login
```bash
curl -X POST https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"votre@email.com","password":"votre_mot_de_passe"}'
```

**Réponse attendue si succès:**
```json
{
  "user": {
    "id": "user_...",
    "email": "votre@email.com",
    "name": "Prénom Nom",
    "phone": "...",
    "biometric_enabled": false,
    "preferences": {...},
    "last_sync": "2024-01-01T12:00:00+00:00"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### 4.2 Tester avec le token
```bash
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/verify.php \
  -H "Authorization: Bearer VOTRE_TOKEN_ICI"
```

---

## ÉTAPE 5: Se connecter depuis l'application mobile

### 5.1 Accéder à l'application
Ouvrez: **https://app.mv-3pro.ch/pro/**

### 5.2 Se connecter
- **Email**: Votre adresse email enregistrée dans Dolibarr
- **Mot de passe**: Votre mot de passe Dolibarr

**IMPORTANT**: Vous devez utiliser les identifiants d'un utilisateur Dolibarr actif (statut = 1).

---

## DÉPANNAGE

### Erreur "Database connection failed"
- Vérifiez les identifiants MySQL dans `config.php`
- Testez la connexion MySQL:
  ```bash
  mysql -u utilisateur -p -h localhost nom_base
  ```

### Erreur "Invalid credentials"
- Vérifiez que l'utilisateur existe dans Dolibarr
- Vérifiez que l'utilisateur est actif (statut = 1)
- Vérifiez que l'email est correct dans Dolibarr

### Erreur CORS
- Vérifiez que les headers CORS sont bien configurés dans `config.php`
- Vérifiez qu'Apache/Nginx ne bloque pas les headers

### Erreur 404
- Vérifiez que le dossier `api_mobile` est au bon endroit
- Vérifiez les permissions des fichiers
- Vérifiez la configuration Apache/Nginx

### Vérifier les logs
```bash
# Logs Apache
tail -f /var/log/apache2/error.log

# Logs PHP
tail -f /var/log/php/error.log

# Logs Nginx (si applicable)
tail -f /var/log/nginx/error.log
```

---

## Structure finale sur le serveur

```
/var/www/dolibarr/htdocs/custom/mv3pro_portail/
├── api_mobile/
│   ├── config.php              ← Configuration principale
│   ├── auth/
│   │   ├── login.php          ← Authentification
│   │   ├── logout.php
│   │   └── verify.php
│   ├── reports/
│   │   ├── create.php         ← Création de rapports
│   │   └── list.php
│   ├── dashboard/
│   │   └── stats.php          ← Statistiques
│   └── weather/
│       └── current.php        ← Météo
```

---

## Sécurité

✅ **Sécurité mise en place:**
- CORS configuré pour l'application
- JWT avec expiration (7 jours par défaut)
- Protection contre les injections SQL (PDO prepared statements)
- Authentification requise sur toutes les routes protégées
- Validation des données d'entrée

⚠️ **Recommandations:**
- Utilisez HTTPS (obligatoire)
- Changez la clé JWT_SECRET régulièrement
- Surveillez les logs d'accès
- Limitez les tentatives de connexion échouées

---

## Support

En cas de problème, vérifiez dans l'ordre:
1. Les logs d'erreur du serveur
2. La configuration de la base de données
3. Les permissions des fichiers
4. La configuration CORS
5. Que le module mv3pro_portail est activé dans Dolibarr
