# GUIDE - CONFIGURER LA CONNEXION BASE DE DONNÉES

## ❌ ERREUR ACTUELLE
```json
{"error":"Database connection failed"}
```

## 🔧 SOLUTION

Vous devez modifier le fichier `config.php` avec les vrais identifiants de votre base de données Dolibarr.

### ÉTAPE 1: Trouver les identifiants Dolibarr

Les identifiants sont dans le fichier de configuration de Dolibarr:

**Chemin:** `/var/www/dolibarr/htdocs/conf/conf.php`

Téléchargez ce fichier via FTP et ouvrez-le. Cherchez ces lignes:

```php
$dolibarr_main_db_host='localhost';
$dolibarr_main_db_name='dolibarr';           // ← NOM DE LA BASE
$dolibarr_main_db_user='dolibarr_user';      // ← UTILISATEUR
$dolibarr_main_db_pass='mot_de_passe_ici';   // ← MOT DE PASSE
```

### ÉTAPE 2: Modifier config.php

Téléchargez le fichier via FTP:
```
/var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/config.php
```

Modifiez ces lignes avec les valeurs trouvées à l'étape 1:

```php
define('DOLIBARR_DB_HOST', 'localhost');                    // ← Généralement 'localhost'
define('DOLIBARR_DB_NAME', 'dolibarr');                     // ← Copiez depuis conf.php
define('DOLIBARR_DB_USER', 'dolibarr_user');                // ← Copiez depuis conf.php
define('DOLIBARR_DB_PASS', 'votre_mot_de_passe_reel');      // ← Copiez depuis conf.php

define('JWT_SECRET', 'CHANGEZ_MOI_123456789');              // ← Mettez n'importe quelle chaîne aléatoire
```

### ÉTAPE 3: Sauvegarder et re-uploader

1. Sauvegardez le fichier `config.php`
2. Re-uploadez-le via FTP au même emplacement
3. Testez à nouveau: `https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/test.php`

## ✅ RÉSULTAT ATTENDU

Après modification, vous devriez voir:

```json
{
    "status": "ok",
    "message": "API MV3 Pro Mobile fonctionne",
    "timestamp": "2024-12-23T20:30:00+01:00",
    "database": "connected",
    "active_users": 5
}
```

## 🔒 SÉCURITÉ JWT_SECRET

La clé JWT_SECRET sert à sécuriser les tokens de connexion. Mettez n'importe quelle chaîne aléatoire longue:

**Exemples:**
```php
define('JWT_SECRET', 'MV3Pro2024!SecretKey#9876');
define('JWT_SECRET', 'aB3$dE5fG7&hI9jK0lM2nO4');
define('JWT_SECRET', 'MonSuperSecretQuiEstLong123456789');
```

Plus c'est long et complexe, mieux c'est.

## 📋 EXEMPLE COMPLET

Voici un exemple de `config.php` correctement configuré:

```php
<?php
define('DOLIBARR_DB_HOST', 'localhost');
define('DOLIBARR_DB_NAME', 'dolibarr');
define('DOLIBARR_DB_USER', 'dolibarr_user');
define('DOLIBARR_DB_PASS', 'MonMotDePasse123');

define('JWT_SECRET', 'MV3Pro2024SecretKey987654321');
define('JWT_EXPIRATION', 86400 * 7);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ... reste du fichier ...
```

## 🆘 SI VOUS NE TROUVEZ PAS conf.php

### Solution alternative: Demandez à votre hébergeur

Si vous n'avez pas accès au fichier `conf.php`, contactez votre hébergeur et demandez:

> "Bonjour, j'ai besoin des identifiants de connexion à ma base de données MySQL pour mon installation Dolibarr. Pouvez-vous me communiquer:
> - Le nom de la base de données
> - L'utilisateur MySQL
> - Le mot de passe MySQL
>
> Merci"

### Solution alternative 2: Vérifier dans le panneau d'hébergement

Si vous avez un panneau de contrôle (cPanel, Plesk, etc.):

1. Allez dans "Bases de données MySQL"
2. Notez le nom de la base Dolibarr
3. Notez l'utilisateur associé
4. Le mot de passe est celui que vous avez défini lors de la création

## 📞 APRÈS MODIFICATION

Une fois `config.php` modifié et uploadé:

1. ✅ Testez test.php → devrait afficher "ok"
2. ✅ Testez la connexion sur https://app.mv-3pro.ch/pro/
3. ✅ Utilisez vos identifiants Dolibarr (email + mot de passe)

Si vous avez encore des erreurs, envoyez-moi une capture d'écran de test.php
