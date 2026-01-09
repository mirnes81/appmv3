# Fix : Erreur #1054 - Champ 'name' inconnu dans field list

## Description de l'erreur

```
MySQL a répondu :
#1054 - Champ 'name' inconnu dans field list
```

Cette erreur apparaît lors de l'insertion des valeurs par défaut dans `llx_mv3_config`.

---

## Cause

La table `llx_mv3_config` existe déjà dans votre base de données mais avec une **structure différente** de celle attendue.

Possible raisons :
1. Une ancienne version de la table existe
2. La table a été créée manuellement avec un schéma différent
3. Un script d'installation précédent a créé une structure incorrecte

---

## Solution 1 : Vérifier la structure actuelle

### Étape 1 : Voir si la table existe

Dans phpMyAdmin, exécutez :

```sql
SELECT COUNT(*) as table_exists
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name = 'llx_mv3_config';
```

**Résultat attendu** :
- `table_exists = 1` → La table existe
- `table_exists = 0` → La table n'existe pas (utiliser Solution 2)

### Étape 2 : Voir la structure actuelle

```sql
DESCRIBE llx_mv3_config;
```

**Structure attendue** :
```
rowid               INTEGER AUTO_INCREMENT PRIMARY KEY
name                VARCHAR(100) NOT NULL UNIQUE
value               TEXT
description         TEXT
type                VARCHAR(20) DEFAULT 'string'
date_creation       DATETIME
date_modification   DATETIME
```

### Étape 3 : Comparer avec votre structure

Si votre structure est **différente** (colonnes manquantes, noms différents), passez à la Solution 3.

Si votre structure est **identique**, passez à la Solution 4.

---

## Solution 2 : La table n'existe pas (Création simple)

Si la table n'existe pas (`table_exists = 0`), créez-la :

```sql
CREATE TABLE llx_mv3_config (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    description TEXT,
    type VARCHAR(20) DEFAULT 'string',
    date_creation DATETIME,
    date_modification DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Puis insérez les valeurs (voir Solution 5).

---

## Solution 3 : Structure incorrecte (Supprimer et recréer)

⚠️ **ATTENTION** : Cette solution **supprime toutes les données** de configuration existantes.

### Étape 1 : Sauvegarder les données existantes (optionnel)

```sql
SELECT * FROM llx_mv3_config;
```

Copiez le résultat dans un fichier texte pour sauvegarder les valeurs.

### Étape 2 : Supprimer l'ancienne table

```sql
DROP TABLE llx_mv3_config;
```

### Étape 3 : Créer la nouvelle table

```sql
CREATE TABLE llx_mv3_config (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    description TEXT,
    type VARCHAR(20) DEFAULT 'string',
    date_creation DATETIME,
    date_modification DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Étape 4 : Insérer les valeurs

Voir Solution 5.

---

## Solution 4 : Structure correcte mais erreur persiste

Si la structure est correcte mais l'erreur persiste :

### Vérifier le préfixe de table

Votre Dolibarr utilise peut-être un **préfixe différent** de `llx_`.

**Vérifier le préfixe** :

1. Ouvrir `/custom/mv3pro_portail/class/mv3_config.class.php`
2. Chercher `MAIN_DB_PREFIX`
3. Vérifier que les requêtes utilisent bien `MAIN_DB_PREFIX.'mv3_config'`

**Exemple** :

```php
$sql = "SELECT name, value FROM ".MAIN_DB_PREFIX."mv3_config";
```

Si votre préfixe est différent (ex: `dol_`), la table s'appelle `dol_mv3_config` et non `llx_mv3_config`.

**Solution** : Utiliser le bon préfixe dans toutes les requêtes SQL.

---

## Solution 5 : Insérer les valeurs par défaut (Méthode sûre)

Exécutez ces requêtes **UNE PAR UNE** dans phpMyAdmin :

```sql
INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('API_BASE_URL', '/custom/mv3pro_portail/api/v1/', 'URL de base de l''API', 'string', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('PWA_BASE_URL', '/custom/mv3pro_portail/pwa_dist/', 'URL de base de la PWA', 'string', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('DEV_MODE_ENABLED', '0', 'Activer le mode développement (1=ON, 0=OFF)', 'boolean', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('DEBUG_CONSOLE_ENABLED', '0', 'Activer les logs console dans la PWA', 'boolean', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('SERVICE_WORKER_CACHE_ENABLED', '1', 'Activer le cache du service worker', 'boolean', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('PLANNING_ACCESS_POLICY', 'employee_own_only', 'Politique d''accès au planning', 'select', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('ERROR_LOG_RETENTION_DAYS', '30', 'Nombre de jours de rétention des logs', 'number', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();
```

---

## Solution 6 : Utiliser le fichier SAFE

Un fichier SQL sécurisé a été créé avec toutes les étapes séparées :

**Fichier** : `/custom/mv3pro_portail/sql/llx_mv3_config_SAFE.sql`

Ce fichier contient :
1. Vérification de l'existence de la table
2. Création de la table si nécessaire
3. Insertion des valeurs une par une
4. Vérification finale

**Utilisation** :

1. Ouvrir le fichier dans un éditeur
2. Copier chaque requête **une par une**
3. Exécuter dans phpMyAdmin
4. Vérifier le résultat avant de passer à la suivante

---

## Vérification finale

Après avoir appliqué l'une des solutions, vérifiez que tout fonctionne :

```sql
SELECT * FROM llx_mv3_config;
```

**Résultat attendu** : 7 lignes avec les paramètres de configuration

```
rowid | name                          | value                              | type
------|-------------------------------|------------------------------------|---------
1     | API_BASE_URL                  | /custom/mv3pro_portail/api/v1/     | string
2     | PWA_BASE_URL                  | /custom/mv3pro_portail/pwa_dist/   | string
3     | DEV_MODE_ENABLED              | 0                                  | boolean
4     | DEBUG_CONSOLE_ENABLED         | 0                                  | boolean
5     | SERVICE_WORKER_CACHE_ENABLED  | 1                                  | boolean
6     | PLANNING_ACCESS_POLICY        | employee_own_only                  | select
7     | ERROR_LOG_RETENTION_DAYS      | 30                                 | number
```

---

## Test de la page Configuration

1. Se connecter à Dolibarr en tant qu'admin
2. Aller dans **Configuration > Modules/Applications > MV3 PRO Portail > Configuration**
3. Vérifier que la page charge sans erreur
4. Vérifier que les 7 paramètres sont affichés

---

## Cas particulier : Erreur persiste après tout

Si l'erreur persiste même après avoir appliqué toutes les solutions :

### Vérifier les logs PHP

1. Ouvrir le fichier de logs PHP de votre serveur
2. Chercher les erreurs liées à `mv3_config`
3. Vérifier les permissions sur les fichiers

### Vérifier les permissions MySQL

```sql
SHOW GRANTS;
```

Vérifier que l'utilisateur MySQL a les droits :
- `SELECT`
- `INSERT`
- `UPDATE`
- `CREATE`

### Contacter le support

Si rien ne fonctionne, contactez le support avec :
1. La structure actuelle de votre table (`DESCRIBE llx_mv3_config`)
2. Le résultat de `SELECT * FROM llx_mv3_config`
3. Le message d'erreur complet
4. La version de MySQL/MariaDB (`SELECT VERSION()`)

---

## Résumé des fichiers SQL disponibles

1. **llx_mv3_config.sql** : Installation normale (peut échouer si structure existe)
2. **llx_mv3_config_SAFE.sql** : Installation sécurisée étape par étape
3. **DIAGNOSTIC_TABLE_CONFIG.sql** : Diagnostic de la structure actuelle
4. **FIX_ERREUR_1054.md** : Ce guide (vous êtes ici)

---

**Date** : 2026-01-09
**Version** : 1.0.0
**Support** : Voir documentation complète
