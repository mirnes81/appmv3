# GUIDE D'INSTALLATION VIA FTP

## Problème CORS - Installation sans SSH

Vous devez uploader ces fichiers via FTP vers votre serveur.

## 📁 Fichiers à uploader

### Emplacement sur le serveur:
`/var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/`

### Fichiers depuis votre ordinateur:
Uploadez tous les fichiers du dossier `deploy_api_php/api_mobile/`

## 🚀 PROCÉDURE AVEC FILEZILLA (ou autre client FTP)

### 1. Connexion FTP
- Hôte: `crm.mv-3pro.ch`
- Protocole: SFTP ou FTP
- Port: 21 (FTP) ou 22 (SFTP)
- Utilisateur: votre utilisateur FTP
- Mot de passe: votre mot de passe FTP

### 2. Navigation
1. Connectez-vous
2. Naviguez vers: `/var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/`
3. Si le dossier `api_mobile` n'existe pas, créez-le

### 3. Upload des fichiers

**IMPORTANT:** Uploadez ces fichiers en mode **ASCII/TEXT** (pas BINAIRE):

#### Fichier prioritaire n°1: `.htaccess`
```
Source: deploy_api_php/api_mobile/.htaccess
Destination: /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/.htaccess
```
Ce fichier est CRUCIAL - il force les headers CORS.

**Note:** Dans FileZilla, activez "Afficher les fichiers cachés" pour voir le .htaccess

#### Fichier prioritaire n°2: `test.php`
```
Source: deploy_api_php/api_mobile/test.php
Destination: /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/test.php
```

#### Fichier prioritaire n°3: `config.php`
```
Source: deploy_api_php/api_mobile/config.php
Destination: /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/config.php
```

**ATTENTION:** Modifiez config.php avec vos identifiants de base de données:
```php
define('DOLIBARR_DB_HOST', 'localhost');
define('DOLIBARR_DB_NAME', 'votre_base_dolibarr');     // ← MODIFIER
define('DOLIBARR_DB_USER', 'votre_utilisateur');        // ← MODIFIER
define('DOLIBARR_DB_PASS', 'votre_mot_de_passe');      // ← MODIFIER
define('JWT_SECRET', 'CHANGEZ_CETTE_CLE');              // ← MODIFIER
```

#### Tous les autres fichiers:
```
deploy_api_php/api_mobile/auth/login.php
deploy_api_php/api_mobile/auth/logout.php
deploy_api_php/api_mobile/auth/verify.php
deploy_api_php/api_mobile/dashboard/stats.php
deploy_api_php/api_mobile/reports/create.php
deploy_api_php/api_mobile/reports/list.php
deploy_api_php/api_mobile/weather/current.php
```

### 4. Vérifier les permissions (si possible via FTP)

Si votre client FTP permet de modifier les permissions:
- Fichiers PHP: 644 (rw-r--r--)
- .htaccess: 644 (rw-r--r--)
- Dossiers: 755 (rwxr-xr-x)

## ✅ VÉRIFICATION

### Étape 1: Tester que l'API répond
Ouvrez dans votre navigateur:
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/test.php
```

**Réponse attendue:**
```json
{
    "status": "ok",
    "message": "API MV3 Pro Mobile fonctionne",
    "database": "connected",
    "active_users": 5
}
```

### Étape 2: Vérifier les headers CORS
Ouvrez la console développeur de votre navigateur (F12) et allez sur:
```
https://app.mv-3pro.ch/pro/
```

Essayez de vous connecter. Dans l'onglet "Réseau/Network", vérifiez que la requête vers `login.php` contient ces headers dans la réponse:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
```

## 🔧 CONTENU DU FICHIER .htaccess

Si le .htaccess ne fonctionne pas, voici son contenu à copier-coller:

```apache
# Configuration CORS pour l'API Mobile MV3 Pro
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    Header always set Access-Control-Max-Age "3600"

    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

<Files "config.php">
    Order allow,deny
    Deny from all
</Files>
```

## 🆘 SI ÇA NE FONCTIONNE TOUJOURS PAS

### Option 1: Ajouter les headers dans chaque fichier PHP

Si le .htaccess n'est pas pris en compte par Apache, ajoutez ces lignes **au tout début** de chaque fichier PHP (après `<?php`):

```php
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
```

Les fichiers à modifier:
- `auth/login.php` (déjà fait dans config.php)
- `auth/logout.php`
- `auth/verify.php`
- `dashboard/stats.php`
- `reports/create.php`
- `reports/list.php`
- `weather/current.php`

### Option 2: Contacter votre hébergeur

Si rien ne fonctionne, contactez votre hébergeur et demandez:

1. "Pouvez-vous activer mod_headers et mod_rewrite pour mon site ?"
2. "Pouvez-vous autoriser les fichiers .htaccess dans /custom/mv3pro_portail/api_mobile/ ?"
3. "Mon application a besoin des headers CORS pour fonctionner"

## 📋 CHECKLIST

- [ ] Tous les fichiers uploadés via FTP
- [ ] .htaccess uploadé (fichier caché, vérifier qu'il est bien là)
- [ ] config.php modifié avec les bons identifiants DB
- [ ] test.php accessible et retourne "ok"
- [ ] Tentative de connexion sur https://app.mv-3pro.ch/pro/
- [ ] Console développeur sans erreur CORS

## 📞 BESOIN D'AIDE ?

Si après avoir suivi ces étapes vous avez toujours des erreurs:

1. Ouvrez test.php dans votre navigateur
2. Faites une capture d'écran
3. Ouvrez F12 > Réseau, essayez de vous connecter
4. Faites une capture d'écran des erreurs
5. Envoyez-moi ces captures

Je pourrai vous aider davantage avec ces informations.
