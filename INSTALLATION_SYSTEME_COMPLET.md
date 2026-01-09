# Installation du système de configuration et diagnostic complet - MV3 PRO Portail

## Résumé des fonctionnalités

✅ **Page de configuration complète** avec tous les liens rapides et paramètres
✅ **Mode DEV sécurisé** qui bloque les non-admins automatiquement
✅ **Journal d'erreurs** avec debug_id unique et détails SQL complets
✅ **Diagnostic système** évolutif testant toutes les pages et endpoints
✅ **Protection backend** vérifiant le mode DEV sur chaque requête API
✅ **Page maintenance** pour les employés en mode DEV
✅ **Système de logging** automatique des erreurs avec stack trace

---

## Fichiers à uploader sur le serveur

### 1. SQL - Créer les nouvelles tables

**Fichiers** :
- `/sql/llx_mv3_config.sql` (nouveau)
- `/sql/llx_mv3_error_log.sql` (nouveau)

**Action** : Exécuter ces scripts SQL dans la base de données Dolibarr

```bash
mysql -u user -p database_name < llx_mv3_config.sql
mysql -u user -p database_name < llx_mv3_error_log.sql
```

Ou via phpMyAdmin : Importer > Sélectionner les fichiers > Exécuter

---

### 2. Classes PHP - Nouvelles classes de gestion

**Répertoire cible** : `/htdocs/custom/mv3pro_portail/class/`

**Fichiers** :
- `mv3_config.class.php` (nouveau)
- `mv3_error_logger.class.php` (nouveau)

**Permissions** : 644 (rw-r--r--)

---

### 3. Pages Admin - Configuration et diagnostic

**Répertoire cible** : `/htdocs/custom/mv3pro_portail/admin/`

**Fichiers** :
- `setup.php` (⚠️ remplace l'ancien)
- `errors.php` (nouveau)
- `diagnostic.php` (nouveau)

**Permissions** : 644 (rw-r--r--)

---

### 4. API Backend - Protection mode DEV

**Répertoire cible** : `/htdocs/custom/mv3pro_portail/api/v1/`

**Fichiers** :
- `_bootstrap.php` (⚠️ remplace l'ancien - ajout fonction check_dev_mode)
- `planning_view.php` (⚠️ remplace l'ancien)
- `planning_file.php` (nouveau)

**Permissions** : 644 (rw-r--r--)

---

### 5. PWA Frontend - Page maintenance et build complet

**Répertoire cible** : `/htdocs/custom/mv3pro_portail/pwa_dist/`

**Action** : ⚠️ **SUPPRIMER l'ancien répertoire `pwa_dist/` et uploader le nouveau complet**

**Contient** :
- `index.html`
- `manifest.webmanifest`
- `registerSW.js`
- `sw.js`
- `workbox-1d305bb8.js`
- `assets/index-BQiQB-1j.css`
- `assets/index-BauNu93U.js` (⚠️ nouveau build avec page Maintenance)
- `icon-192.png`
- `icon-512.png`

**Permissions** :
- Répertoires : 755 (rwxr-xr-x)
- Fichiers : 644 (rw-r--r--)

---

## Instructions d'upload via FileZilla

### Étape 1 : SQL (Tables)

1. Ouvrir phpMyAdmin ou terminal MySQL
2. Sélectionner la base Dolibarr
3. Exécuter `/sql/llx_mv3_config.sql`
4. Exécuter `/sql/llx_mv3_error_log.sql`
5. Vérifier que les tables sont créées : `SHOW TABLES LIKE 'llx_mv3_%'`

### Étape 2 : Classes PHP

1. Ouvrir FileZilla
2. Se connecter au serveur
3. Naviguer vers `/htdocs/custom/mv3pro_portail/class/`
4. Uploader :
   - `mv3_config.class.php`
   - `mv3_error_logger.class.php`

### Étape 3 : Pages Admin

1. Naviguer vers `/htdocs/custom/mv3pro_portail/admin/`
2. Uploader (remplacer si existant) :
   - `setup.php`
   - `errors.php`
   - `diagnostic.php`

### Étape 4 : API Backend

1. Naviguer vers `/htdocs/custom/mv3pro_portail/api/v1/`
2. Uploader (remplacer si existant) :
   - `_bootstrap.php`
   - `planning_view.php`
   - `planning_file.php`

### Étape 5 : PWA

1. Naviguer vers `/htdocs/custom/mv3pro_portail/`
2. **Renommer** `pwa_dist/` en `pwa_dist_old/` (backup)
3. **Uploader** le nouveau répertoire `pwa_dist/` complet
4. Vérifier que tous les fichiers sont présents

---

## Vérification après installation

### 1. Vérifier les tables SQL

Dans phpMyAdmin ou MySQL :

```sql
SHOW TABLES LIKE 'llx_mv3_config';
SHOW TABLES LIKE 'llx_mv3_error_log';

SELECT * FROM llx_mv3_config;
```

**Résultat attendu** : 7 lignes de configuration par défaut

### 2. Vérifier la page de configuration

1. Se connecter à Dolibarr en tant qu'admin
2. Aller dans : **Configuration > Modules/Applications**
3. Chercher **MV3 PRO Portail**
4. Cliquer sur **Setup**

**Résultat attendu** :
- Page complète avec liens rapides
- Section Mode DEV avec toggle
- Statistiques des erreurs
- Informations système

### 3. Tester le mode DEV

1. Dans la page Setup, cocher **"Activer le mode DEV"**
2. Sauvegarder
3. Se déconnecter de Dolibarr
4. Se connecter avec un compte employé (non-admin)
5. Ouvrir la PWA : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`

**Résultat attendu** : Page "Application en maintenance" s'affiche

6. Se reconnecter en tant qu'admin
7. Ouvrir la PWA

**Résultat attendu** : Accès normal à la PWA

8. Désactiver le mode DEV

### 4. Vérifier le journal d'erreurs

1. Aller dans : Configuration > MV3 PRO Portail > **Journal d'erreurs**
2. Vérifier que la page s'affiche

**Résultat attendu** : Liste vide (0 erreurs) ou erreurs existantes

### 5. Vérifier le diagnostic

1. Aller dans : Configuration > MV3 PRO Portail > **Diagnostic système**
2. Cliquer sur **"Lancer le diagnostic complet"**

**Résultat attendu** :
- Tous les tests s'exécutent
- Statistiques affichées (OK / WARNING / ERROR)
- Détails de chaque test

### 6. Tester les fichiers du planning

1. Se connecter à la PWA en tant qu'admin
2. Aller dans **Planning**
3. Cliquer sur un rendez-vous qui a des fichiers joints
4. Cliquer sur **"Ouvrir"** à côté d'un fichier

**Résultat attendu** : Le fichier s'ouvre dans un nouvel onglet

---

## Fonctionnement du mode DEV

### Quand Mode DEV = OFF (Production)

✅ Admins : Accès complet
✅ Employés : Accès complet
✅ API : Fonctionne normalement
✅ PWA : Accessible à tous

### Quand Mode DEV = ON (Développement)

✅ Admins : Accès complet (tests, debug, config)
❌ Employés : Voient page "Maintenance"
❌ API : Bloque les endpoints pour non-admins (retour 503)
⚠️ PWA : Accessible uniquement aux admins

**Sécurité** : Les employés NE PEUVENT PAS accéder à la PWA en mode DEV, même avec un token valide.

---

## Utilisation du journal d'erreurs

### Scénario 1 : Employé reporte un problème

1. Employé : "Le planning ne charge pas"
2. Admin va dans : **Journal d'erreurs**
3. Voit l'erreur récente : `SQL_ERROR` sur `/planning.php`
4. Clique sur **"Détails"**
5. Voit l'erreur SQL complète : `Table 'llx_mv3_planning' doesn't exist`
6. Identifie et corrige le problème

### Scénario 2 : Erreur 500 mystérieuse

1. Admin lance le **Diagnostic système**
2. Voit que `/rapports_create.php` échoue (ERROR)
3. Va dans **Journal d'erreurs**
4. Cherche le `debug_id` correspondant
5. Voit la stack trace complète
6. Identifie la ligne de code problématique

---

## Ajout de nouveaux tests au diagnostic

Pour ajouter un nouveau test, éditer `/admin/diagnostic.php` :

### Test d'une page PWA

```php
$tests_config['frontend_pages'][] = [
    'name' => '📱 PWA - Ma nouvelle page',
    'url' => $full_pwa_url.'#/ma-page',
    'method' => 'GET'
];
```

### Test d'un endpoint API

```php
$tests_config['backend_api'][] = [
    'name' => '🔌 API - Mon endpoint',
    'url' => $full_api_url.'mon_endpoint.php',
    'method' => 'GET',
    'requires_auth' => true
];
```

### Test d'une table BDD

```php
$tests_config['database_tables'][] = [
    'name' => '🗄️ Table - ma_table',
    'table' => MAIN_DB_PREFIX.'ma_table'
];
```

### Test d'un fichier

```php
$tests_config['files_structure'][] = [
    'name' => '📁 Fichier - mon fichier',
    'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/mon_fichier.php'
];
```

**C'est tout !** Le diagnostic exécutera automatiquement ces nouveaux tests.

---

## Dépannage

### Erreur "Table 'llx_mv3_config' doesn't exist"

**Cause** : Les tables SQL n'ont pas été créées

**Solution** :
```sql
source /path/to/llx_mv3_config.sql
source /path/to/llx_mv3_error_log.sql
```

### Erreur "Class 'Mv3Config' not found"

**Cause** : Les classes PHP n'ont pas été uploadées

**Solution** : Uploader `/class/mv3_config.class.php` et `/class/mv3_error_logger.class.php`

### Page Setup ne s'affiche pas

**Cause** : Fichier `setup.php` non uploadé ou permissions incorrectes

**Solution** :
```bash
chmod 644 /htdocs/custom/mv3pro_portail/admin/setup.php
```

### Mode DEV ne bloque pas les employés

**Cause** : Le fichier `_bootstrap.php` n'a pas été remplacé

**Solution** : Uploader la nouvelle version de `/api/v1/_bootstrap.php`

### Page Maintenance ne s'affiche pas

**Cause** : Le nouveau build PWA n'a pas été uploadé

**Solution** : Supprimer `pwa_dist/` et uploader le nouveau complet

---

## Checklist complète d'installation

- [ ] SQL : Tables `llx_mv3_config` créée
- [ ] SQL : Tables `llx_mv3_error_log` créée
- [ ] Classes : `mv3_config.class.php` uploadé
- [ ] Classes : `mv3_error_logger.class.php` uploadé
- [ ] Admin : `setup.php` uploadé et remplace l'ancien
- [ ] Admin : `errors.php` uploadé
- [ ] Admin : `diagnostic.php` uploadé
- [ ] API : `_bootstrap.php` uploadé et remplace l'ancien
- [ ] API : `planning_view.php` uploadé
- [ ] API : `planning_file.php` uploadé
- [ ] PWA : Répertoire `pwa_dist/` complet uploadé
- [ ] Test : Page Setup s'affiche correctement
- [ ] Test : Journal d'erreurs s'affiche
- [ ] Test : Diagnostic s'exécute
- [ ] Test : Mode DEV bloque les employés
- [ ] Test : Mode DEV permet l'accès aux admins
- [ ] Test : Fichiers planning s'ouvrent dans le navigateur

---

## Fichiers de documentation créés

1. **SYSTEME_CONFIG_DIAGNOSTIC_COMPLET.md** : Documentation complète du système
2. **INSTALLATION_SYSTEME_COMPLET.md** : Ce fichier - guide d'installation
3. **GUIDE_FICHIERS_SECURISES.md** : Guide ouverture fichiers sécurisée

---

## Support

En cas de problème :

1. Vérifier le **Journal d'erreurs** avec le debug_id
2. Lancer le **Diagnostic système** pour identifier ce qui ne fonctionne pas
3. Vérifier les permissions des fichiers (644 pour PHP, 755 pour répertoires)
4. Vérifier que toutes les tables SQL sont créées
5. Vider le cache du navigateur (Ctrl+Shift+R)

---

**Installation complétée avec succès !**

Le module MV3 PRO Portail dispose maintenant d'un système complet de configuration, monitoring et diagnostic évolutif.

**Date** : 2026-01-09
**Version** : 2.0.0
