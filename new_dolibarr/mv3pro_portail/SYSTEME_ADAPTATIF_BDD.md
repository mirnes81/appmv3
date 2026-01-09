# Système adaptatif de compatibilité BDD

## Date: 2026-01-09

## Objectif

L'application MV3 PRO PWA doit fonctionner même si :
- Des tables n'existent pas encore
- Des colonnes sont manquantes (versions différentes de Dolibarr)
- La structure de la base diffère légèrement

**Résultat**: Aucune erreur 500 due à un schéma de BDD, l'app retourne des données vides et log les problèmes.

---

## 1. Helpers ajoutés dans _bootstrap.php

### a) Cache global
```php
global $MV3_DB_SCHEMA_CACHE;
```
Évite de requêter plusieurs fois pour vérifier la même table/colonne.

### b) mv3_table_exists($db, $table_name)
Vérifie si une table existe dans la base.
- Accepte les noms avec ou sans préfixe
- Utilise le cache pour optimiser
- Retourne `true` ou `false`

**Exemple:**
```php
if (!mv3_table_exists($db, 'mv3_rapport')) {
    json_ok(['rapports' => [], 'count' => 0]);
}
```

### c) mv3_column_exists($db, $table_name, $column_name)
Vérifie si une colonne existe dans une table.
- Utilise le cache
- Retourne `true` ou `false`

**Exemple:**
```php
if (mv3_column_exists($db, 'actioncomm', 'note_private')) {
    // La colonne existe
}
```

### d) mv3_select_column($db, $table, $column, $default, $alias_prefix)
Construit un fragment SQL conditionnel pour une colonne.
- Si la colonne existe: retourne `alias_prefix.column`
- Si absente: retourne `'valeur_defaut' AS column`

**Exemple:**
```php
$note_field = mv3_select_column($db, 'actioncomm', 'note_private', '', 'a');
// Si existe: "a.note_private"
// Si absente: "'' AS note_private"

$sql = "SELECT a.id, a.label, " . $note_field . " FROM llx_actioncomm a";
```

### e) mv3_check_table_or_empty($db, $table_name, $endpoint_name)
Vérifie si une table existe, sinon retourne un tableau vide et termine la requête.
- Log l'erreur dans les logs PHP
- Retourne HTTP 200 avec `[]`

**Exemple:**
```php
mv3_check_table_or_empty($db, 'mv3_rapport', 'Rapports');
// Si la table n'existe pas, exit avec []
```

---

## 2. Corrections appliquées aux endpoints

### A) Planning (/api/v1/planning.php)

**Problème**: Colonne `note_private` n'existe pas sur certaines versions de Dolibarr.

**Avant:**
```php
$sql = "SELECT a.id, a.label, a.note_private ..."; // ERREUR si colonne manquante
```

**Après:**
```php
$note_private_field = mv3_select_column($db, 'actioncomm', 'note_private', '', 'a');
$sql = "SELECT a.id, a.label, " . $note_private_field . " ...";
```

**Gestion d'erreur:**
- Si requête échoue: retourne `[]` au lieu de 500
- Log l'erreur SQL complète dans les logs

### B) Rapports (/api/v1/rapports.php)

**Vérifications:**
1. Table `llx_mv3_rapport` existe ?
   - Non: retourne `[]`
   - Oui: continue

2. Si erreur SQL:
   - Log l'erreur
   - Retourne `[]` au lieu de 500

### C) Notifications (/api/v1/notifications_list.php)

**Vérifications:**
1. Table `llx_mv3_notifications` existe ?
   - Non: retourne `{notifications: [], count: 0}`
   - Oui: continue

2. Si erreur SQL:
   - Log l'erreur
   - Retourne `{notifications: [], count: 0}`

### D) Sens de Pose (/api/v1/sens_pose_list.php)

**Vérifications:**
1. Table `llx_mv3_sens_pose` existe ?
   - Non: retourne `{sens_pose: [], count: 0}`
   - Oui: continue

2. Si erreur SQL:
   - Log l'erreur
   - Retourne `{sens_pose: [], count: 0}`

---

## 3. Avantages du système

### Zéro erreur 500 pour schema BDD
- L'app ne plante jamais à cause d'une table/colonne manquante
- Retourne toujours des données valides (même si vides)

### Installation progressive
- Pas besoin d'installer toutes les tables d'un coup
- Chaque module fonctionne indépendamment
- L'app reste utilisable pendant la migration

### Compatible multi-versions Dolibarr
- Gère automatiquement les différences de schéma
- S'adapte aux versions 10.x, 11.x, 12.x, etc.

### Logs détaillés
- Chaque problème SQL est logué
- Facilite le diagnostic
- Identifie précisément les tables/colonnes manquantes

### Performance
- Utilisation du cache pour éviter les requêtes répétées
- Une seule vérification par table/colonne par requête

---

## 4. Vérifier le bon fonctionnement

### A) Via la PWA

1. Se connecter à la PWA
2. Aller dans Menu > Debug
3. Lancer "Diagnostic Complet"
4. Observer les résultats:
   - **Planning**: doit afficher OK même si `note_private` manque
   - **Rapports**: OK ou tableau vide (pas d'erreur 500)
   - **Notifications**: OK ou tableau vide
   - **Sens Pose**: OK ou tableau vide

### B) Via les logs PHP

```bash
# Sur le serveur
tail -f /var/log/php_errors.log | grep "MV3"
```

Si des tables manquent, vous verrez:
```
[MV3 Rapports] Table manquante: llx_mv3_rapport
[MV3 Notifications] Table manquante: llx_mv3_notifications
[MV3 Sens Pose] SQL Error: Table 'dolibarr.llx_mv3_sens_pose' doesn't exist
```

### C) Via l'API directement

```bash
# Test Planning
curl -H "X-Auth-Token: VOTRE_TOKEN" \
  https://votre-domaine.com/custom/mv3pro_portail/api/v1/planning.php

# Test Rapports
curl -H "X-Auth-Token: VOTRE_TOKEN" \
  https://votre-domaine.com/custom/mv3pro_portail/api/v1/rapports.php

# Test Notifications
curl -H "X-Auth-Token: VOTRE_TOKEN" \
  https://votre-domaine.com/custom/mv3pro_portail/api/v1/notifications_list.php
```

Tous doivent retourner HTTP 200 (jamais 500).

---

## 5. Installation des tables SQL (optionnel)

Si vous voulez activer les fonctionnalités complètes:

```sql
-- Importer le script complet
mysql -u root -p dolibarr_db < sql/INSTALLATION_COMPLETE.sql
```

Ou installer table par table:
```sql
mysql -u root -p dolibarr_db < sql/llx_mv3_rapport.sql
mysql -u root -p dolibarr_db < sql/llx_mv3_notifications.sql
mysql -u root -p dolibarr_db < sql/llx_mv3_sens_pose.sql
mysql -u root -p dolibarr_db < sql/llx_mv3_mobile_users.sql
```

Après installation, relancer le diagnostic pour vérifier.

---

## 6. Extension du système

Pour ajouter d'autres endpoints adaptatifs:

```php
<?php
require_once __DIR__ . '/_bootstrap.php';

$auth = require_auth();
require_method('GET');

// 1. Vérifier la table
if (!mv3_table_exists($db, 'ma_nouvelle_table')) {
    json_ok(['data' => [], 'count' => 0]);
}

// 2. Construire la requête avec colonnes conditionnelles
$optional_field = mv3_select_column($db, 'ma_table', 'optional_column', 'valeur_defaut', 't');

$sql = "SELECT t.id, t.name, " . $optional_field . "
        FROM " . MAIN_DB_PREFIX . "ma_nouvelle_table t
        WHERE t.entity = 1";

$resql = $db->query($sql);

// 3. Gérer l'erreur gracieusement
if (!$resql) {
    error_log('[MV3 MonEndpoint] SQL Error: ' . $db->lasterror());
    error_log('[MV3 MonEndpoint] SQL Query: ' . $sql);
    json_ok(['data' => [], 'count' => 0]); // Pas de 500
}

// 4. Retourner les données
$data = [];
while ($obj = $db->fetch_object($resql)) {
    $data[] = ['id' => $obj->id, 'name' => $obj->name];
}

json_ok(['data' => $data, 'count' => count($data)]);
```

---

## Résumé

- ✅ Aucune erreur 500 liée au schéma BDD
- ✅ L'app fonctionne même avec des tables manquantes
- ✅ Compatible avec différentes versions de Dolibarr
- ✅ Logs détaillés pour le diagnostic
- ✅ Cache pour optimiser les performances
- ✅ Installation progressive possible
- ✅ Code maintenable et extensible

Le système est maintenant résilient et s'adapte automatiquement à la structure de la base de données disponible.
