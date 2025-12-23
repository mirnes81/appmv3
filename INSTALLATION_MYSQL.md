# Installation MV3 Pro PWA avec MySQL

Ce guide vous explique comment installer la PWA MV3 Pro en utilisant **votre base MySQL de Dolibarr** (pas Supabase).

## Prérequis

- Dolibarr installé avec accès phpMyAdmin
- Accès FTP ou SSH à votre serveur
- Un serveur web (Apache/Nginx) avec PHP 7.4+

## Étape 1 : Créer les tables MySQL

1. **Ouvrez phpMyAdmin**
2. **Sélectionnez votre base Dolibarr** (généralement `dolibarr`)
3. **Cliquez sur l'onglet "SQL"**
4. **Copiez-collez tout le contenu** du fichier `sql_mysql_pwa.sql`
5. **Cliquez sur "Exécuter"**

Cela va créer 8 nouvelles tables :
- `llx_mv3_mobile_users` - Utilisateurs de l'app mobile
- `llx_mv3_mobile_sessions` - Sessions d'authentification
- `llx_mv3_report_drafts` - Brouillons de rapports
- `llx_mv3_report_templates` - Templates de rapports
- `llx_mv3_sync_queue` - File de synchronisation
- `llx_mv3_offline_cache` - Cache hors-ligne
- `llx_mv3_photo_backups` - Backup des photos
- `llx_mv3_voice_notes` - Notes vocales

## Étape 2 : Installer l'API PHP

1. **Copiez le dossier `api_pwa`** dans votre installation Dolibarr :
   ```
   /var/www/html/dolibarr/custom/mv3pro_portail/api_pwa/
   ```

2. **Vérifiez les permissions** :
   ```bash
   chmod 755 /var/www/html/dolibarr/custom/mv3pro_portail/api_pwa
   chmod 644 /var/www/html/dolibarr/custom/mv3pro_portail/api_pwa/*.php
   ```

3. **Testez l'API** en ouvrant dans votre navigateur :
   ```
   https://votre-domaine.com/dolibarr/custom/mv3pro_portail/api_pwa/auth.php?action=verify
   ```

   Vous devez voir : `{"error": "Token manquant"}` (c'est normal)

## Étape 3 : Configurer l'application React

1. **Ouvrez le fichier `.env`** à la racine du projet
2. **Modifiez l'URL de l'API** :
   ```env
   VITE_API_URL=https://votre-domaine.com/dolibarr/custom/mv3pro_portail/api_pwa
   ```

## Étape 4 : Compiler l'application

1. **Installez les dépendances** :
   ```bash
   npm install
   ```

2. **Compilez pour la production** :
   ```bash
   npm run build
   ```

3. **Le résultat se trouve dans le dossier `dist/`**

## Étape 5 : Déployer l'application

### Option A : Déploiement dans Dolibarr

1. **Copiez le contenu du dossier `dist/`** vers :
   ```
   /var/www/html/dolibarr/custom/mv3pro_portail/pwa_app/
   ```

2. **Accédez à l'application** :
   ```
   https://votre-domaine.com/dolibarr/custom/mv3pro_portail/pwa_app/
   ```

### Option B : Déploiement sur un sous-domaine

1. **Créez un sous-domaine** (ex: `app.mv-3pro.ch`)
2. **Pointez-le vers le dossier `dist/`**
3. **Configurez SSL** (obligatoire pour les PWA)

## Étape 6 : Créer votre premier utilisateur

### Via phpMyAdmin :

1. **Ouvrez phpMyAdmin**
2. **Table `llx_mv3_mobile_users`**
3. **Insérez un nouvel utilisateur** :

```sql
INSERT INTO llx_mv3_mobile_users (dolibarr_user_id, email, password_hash)
VALUES (
  1, -- ID de votre utilisateur Dolibarr
  'votre@email.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' -- mot de passe: test123
);
```

### Générer un nouveau mot de passe :

Utilisez ce script PHP :
```php
<?php
echo password_hash('votre_mot_de_passe', PASSWORD_BCRYPT);
?>
```

## Étape 7 : Se connecter

1. **Ouvrez l'application** dans votre navigateur
2. **Mode de connexion** : Choisissez "Email / Mot de passe"
3. **Connectez-vous** avec :
   - Email : votre@email.com
   - Mot de passe : test123 (ou le mot de passe que vous avez créé)

## Compte de test

Un compte de test est déjà créé par le script SQL :
- **Email** : test@mv3pro.com
- **Mot de passe** : test123

## Vérifications

### L'API fonctionne-t-elle ?

Testez dans votre navigateur :
```
https://votre-domaine.com/dolibarr/custom/mv3pro_portail/api_pwa/auth.php?action=verify
```

### La connexion échoue ?

1. **Vérifiez les logs Apache/Nginx** :
   ```bash
   tail -f /var/log/apache2/error.log
   ```

2. **Vérifiez la config PHP** dans `api_pwa/config.php` :
   - Les identifiants de base de données sont-ils corrects ?

3. **Vérifiez CORS** :
   - Ouvrez la console du navigateur (F12)
   - Cherchez des erreurs CORS

### Debug de la connexion MySQL

Créez un fichier `test_db.php` :
```php
<?php
require_once 'api_pwa/config.php';
$db = getDB();
echo "Connexion réussie!";
?>
```

## Structure finale

```
/var/www/html/dolibarr/
├── custom/
│   └── mv3pro_portail/
│       ├── api_pwa/           ← API PHP
│       │   ├── config.php
│       │   ├── auth.php
│       │   ├── reports.php
│       │   └── materiel.php
│       └── pwa_app/           ← Application React compilée
│           ├── index.html
│           ├── assets/
│           └── ...
```

## Fonctionnalités disponibles

- ✅ Connexion avec email/mot de passe
- ✅ Mode hors-ligne avec cache intelligent
- ✅ Auto-sauvegarde des brouillons
- ✅ Synchronisation intelligente
- ✅ Photos optimisées avec compression
- ✅ Notes vocales avec transcription
- ✅ Géolocalisation GPS
- ✅ Mode sombre/clair
- ✅ PWA installable

## Support

Pour toute question, vérifiez :
1. Les logs PHP/Apache
2. La console du navigateur (F12)
3. Les permissions des fichiers
4. La configuration de la base de données

## Mise à jour

Pour mettre à jour l'application :
1. Recompilez avec `npm run build`
2. Copiez le nouveau contenu de `dist/` vers `pwa_app/`
3. Videz le cache du navigateur (Ctrl+Shift+R)
