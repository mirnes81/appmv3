# 🔧 MV3 PRO Portail - Core Library

Bibliothèque centralisée pour réduire les duplications de code dans le module MV3 PRO Portail.

---

## 📁 Structure

```
core/
├── init.php           → Bootstrap commun (chargement Dolibarr + modules)
├── functions.php      → Fonctions JSON, paramètres, validation
├── auth.php           → Authentification centralisée (3 modes)
├── permissions.php    → Logique admin/employé
└── README.md          → Ce fichier
```

---

## 🚀 Usage rapide

### 1. Dans vos fichiers API

```php
<?php
// Charger bootstrap API (existant)
require_once __DIR__ . '/_bootstrap.php';

// Charger core library (NOUVEAU)
require_once __DIR__ . '/../../core/init.php';

// Authentification
$auth = require_auth(true);

// Récupérer ID utilisateur et statut admin
$dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
$is_admin = mv3_is_admin($auth);

// Vérifier admin obligatoire
mv3_require_admin($auth);

// Construire filtre SQL admin/employé
$user_filter = mv3_get_user_filter_sql($auth, 'fk_user', $filter_user_id);
if (!empty($user_filter)) {
    $where[] = $user_filter;
}

// Réponses JSON
json_ok(['data' => $result]);
json_error('Erreur', 'CODE', 400);
```

### 2. Dans vos fichiers admin

```php
<?php
// Charger Dolibarr (méthode standard)
require '../../main.inc.php';

// Charger core library
require_once __DIR__ . '/../core/init.php';

// Vérifier admin
$auth = mv3_get_auth_info();
if (!mv3_is_admin($auth)) {
    accessforbidden();
}
```

### 3. Dans mobile_app

```php
<?php
// Charger Dolibarr
require_once __DIR__ . '/includes/dolibarr_bootstrap.php';

// Charger core library
require_once __DIR__ . '/../core/init.php';

// Authentification
$auth = require_auth(true);
$dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
```

---

## 📚 Fonctions disponibles

### 🔐 Authentification (`auth.php`)

#### `mv3_get_auth_info()`
Récupère les informations d'authentification de l'utilisateur courant.

**Supporte 3 modes** :
- Session Dolibarr (admin/chef connecté)
- Token mobile (Bearer Authorization)
- Token API ancien (X-Auth-Token)

**Retour** :
```php
[
    'mode' => 'dolibarr_session|mobile_token|mobile_token_legacy',
    'user_id' => 123,
    'mobile_user_id' => 456,
    'dolibarr_user_id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'dolibarr_user' => User, // Objet User Dolibarr
    'is_unlinked' => false
]
```

#### `require_auth($required = true)`
Authentification obligatoire.

**Paramètres** :
- `$required` (bool) : Si true, erreur 401 si non authentifié

**Retour** : Array d'infos utilisateur ou null

**Exemple** :
```php
$auth = require_auth(true); // Obligatoire
$auth = require_auth(false); // Optionnel
```

#### `mv3_get_dolibarr_user_id($auth)`
Récupère le vrai ID utilisateur Dolibarr depuis l'authentification.

**Retour** : int (ID Dolibarr ou 0 si non disponible)

**Exemple** :
```php
$auth = require_auth(true);
$dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
// 0 si compte non lié, > 0 sinon
```

#### `mv3_is_admin($auth)`
Vérifie si l'utilisateur est admin.

**Retour** : bool

**Exemple** :
```php
$auth = require_auth(true);
if (mv3_is_admin($auth)) {
    // Code admin
}
```

---

### 🔒 Permissions (`permissions.php`)

#### `mv3_require_admin($auth)`
Vérifie que l'utilisateur est admin, sinon erreur 403.

**Exemple** :
```php
$auth = require_auth(true);
mv3_require_admin($auth); // Erreur 403 si pas admin
```

#### `mv3_get_user_filter_sql($auth, $user_field = 'fk_user', $override_user_id = null)`
Génère le filtre SQL pour restreindre les données par utilisateur.

**Logique** :
- **Admin** : voit tout (retourne '')
- **Admin avec override_user_id** : filtre sur cet utilisateur
- **Employé** : voit uniquement ses données (retourne 'fk_user = X')

**Paramètres** :
- `$auth` : Résultat de `require_auth()`
- `$user_field` : Nom du champ utilisateur (défaut: 'fk_user')
- `$override_user_id` : ID utilisateur pour filtrage admin (optionnel)

**Retour** : string (clause WHERE SQL)

**Exemples** :
```php
// Cas 1: Employé
$auth = require_auth(true); // user_id=123, admin=false
$filter = mv3_get_user_filter_sql($auth, 'r.fk_user');
// Retourne: "r.fk_user = 123"

// Cas 2: Admin sans filtre
$auth = require_auth(true); // user_id=456, admin=true
$filter = mv3_get_user_filter_sql($auth, 'r.fk_user');
// Retourne: "" (voit tout)

// Cas 3: Admin avec filtre sur employé 789
$auth = require_auth(true); // user_id=456, admin=true
$filter_user_id = 789;
$filter = mv3_get_user_filter_sql($auth, 'r.fk_user', $filter_user_id);
// Retourne: "r.fk_user = 789"

// Usage dans SQL
$sql = "SELECT * FROM llx_mv3_rapport r WHERE r.entity = 1";
$user_filter = mv3_get_user_filter_sql($auth, 'r.fk_user', $filter_user_id);
if (!empty($user_filter)) {
    $sql .= " AND " . $user_filter;
}
```

#### `mv3_can_access_resource($auth, $resource_user_id)`
Vérifie si l'utilisateur peut accéder à une ressource.

**Retour** : bool

**Exemple** :
```php
$auth = require_auth(true);
if (mv3_can_access_resource($auth, $rapport->fk_user)) {
    // Accès autorisé
}
```

#### `mv3_require_resource_access($auth, $resource_user_id, $resource_name = 'ressource')`
Vérifie l'accès à une ressource, sinon erreur 403/404.

**Exemple** :
```php
$auth = require_auth(true);
mv3_require_resource_access($auth, $rapport->fk_user, 'rapport');
// Erreur 404 si pas accès
```

---

### 📤 Réponses JSON (`functions.php`)

#### `json_ok($data, $code = 200)`
Retourne une réponse JSON de succès.

**Exemples** :
```php
json_ok(['users' => $users]);
// {"success": true, "users": [...]}

json_ok(['message' => 'OK'], 201);
// HTTP 201, {"success": true, "message": "OK"}
```

#### `json_error($message, $code = 'ERROR', $http_code = 400, $extra_data = [])`
Retourne une réponse JSON d'erreur.

**Exemples** :
```php
json_error('Utilisateur introuvable', 'USER_NOT_FOUND', 404);
// HTTP 404, {"success": false, "error": "...", "code": "USER_NOT_FOUND", ...}

json_error('Accès refusé', 'FORBIDDEN', 403, [
    'hint' => 'Vous devez être admin'
]);
```

---

### 🔍 Validation (`functions.php`)

#### `require_method($methods)`
Vérifie que la méthode HTTP est correcte.

**Exemples** :
```php
require_method('GET');
require_method(['GET', 'POST']);
```

#### `require_param($value, $name)`
Vérifie qu'un paramètre est présent et non vide.

**Exemple** :
```php
$id = (int)get_param('id', 0);
require_param($id, 'id'); // Erreur 400 si id=0
```

#### `get_param($name, $default = '', $method = 'ANY')`
Récupère un paramètre de manière sécurisée.

**Exemples** :
```php
$limit = (int)get_param('limit', 20);
$search = get_param('search', '', 'GET');
$data = get_param('data', '', 'POST');
```

#### `get_json_body($required = false)`
Récupère le body JSON de la requête.

**Exemple** :
```php
$data = get_json_body(true); // Erreur 400 si JSON invalide
```

---

### 🗄️ Base de données (`functions.php`)

#### `mv3_table_exists($db, $table_name)`
Vérifie si une table existe.

**Exemple** :
```php
if (!mv3_table_exists($db, 'mv3_rapport')) {
    json_error('Table introuvable', 'TABLE_NOT_FOUND', 404);
}
```

#### `mv3_check_table_or_empty($db, $table_name, $resource_name = 'Ressources')`
Vérifie si une table existe, sinon retourne une liste vide en JSON.

**Exemple** :
```php
mv3_check_table_or_empty($db, 'mv3_rapport', 'Rapports');
// Si table absente : {"success": true, "data": {"items": [], "total": 0}, ...}
```

---

## 🎯 Cas d'usage

### Cas 1 : Endpoint API avec filtre admin/employé

```php
<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../core/init.php';

require_method('GET');
$auth = require_auth(true);

// Paramètres
$limit = (int)get_param('limit', 20);
$filter_user_id = get_param('user_id', null); // Admin uniquement

// Construction requête
$sql = "SELECT * FROM llx_mv3_rapport r WHERE r.entity = 1";

// Filtre admin/employé centralisé
$user_filter = mv3_get_user_filter_sql($auth, 'r.fk_user', $filter_user_id);
if (!empty($user_filter)) {
    $sql .= " AND " . $user_filter;
}

$sql .= " LIMIT " . $limit;

$resql = $db->query($sql);
// ...
json_ok(['rapports' => $rapports]);
```

### Cas 2 : Page admin Dolibarr

```php
<?php
require '../../main.inc.php';
require_once __DIR__ . '/../core/init.php';

// Vérifier admin
$auth = mv3_get_auth_info();
if (!mv3_is_admin($auth)) {
    accessforbidden();
}

// Afficher page admin
llxHeader('', 'Diagnostic');
// ... HTML ...
llxFooter();
```

### Cas 3 : Vérifier accès à une ressource

```php
<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../core/init.php';

require_method('GET');
$auth = require_auth(true);

$rapport_id = (int)get_param('id', 0);
require_param($rapport_id, 'id');

// Charger rapport
$sql = "SELECT * FROM llx_mv3_rapport WHERE rowid = ".$rapport_id;
$resql = $db->query($sql);
$rapport = $db->fetch_object($resql);

if (!$rapport) {
    json_error('Rapport introuvable', 'NOT_FOUND', 404);
}

// Vérifier accès (admin ou propriétaire)
mv3_require_resource_access($auth, $rapport->fk_user, 'rapport');

// OK, retourner rapport
json_ok(['rapport' => $rapport]);
```

---

## ✅ Avantages

### 1. **Moins de duplication**
- Code auth/permissions répété 20+ fois → 1 seule version
- Réduction de **80-85%** de la duplication

### 2. **Maintenabilité**
- Une seule source de vérité
- Corrections appliquées partout automatiquement

### 3. **Sécurité**
- Logique centralisée = moins de bugs
- Pas d'oubli de vérification admin

### 4. **Lisibilité**
```php
// AVANT (20 lignes dupliquées)
$dolibarr_user_id = (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->id))
    ? (int)$auth['dolibarr_user']->id : 0;
$is_admin = (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->admin));
if ($is_admin) {
    if ($filter_user_id) {
        $where[] = "r.fk_user = ".(int)$filter_user_id;
    }
} else {
    if ($dolibarr_user_id > 0) {
        $where[] = "r.fk_user = ".$dolibarr_user_id;
    } else {
        $where[] = "1 = 0";
    }
}

// APRÈS (4 lignes, intention claire)
$user_filter = mv3_get_user_filter_sql($auth, 'r.fk_user', $filter_user_id);
if (!empty($user_filter)) {
    $where[] = $user_filter;
}
```

---

## 📝 Migration des fichiers existants

### Étape 1 : Ajouter require_once

```php
// En haut du fichier, après _bootstrap.php ou main.inc.php
require_once __DIR__ . '/../../core/init.php';
```

### Étape 2 : Remplacer logique auth manuelle

```php
// AVANT
$dolibarr_user_id = (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->id))
    ? (int)$auth['dolibarr_user']->id : 0;
$is_admin = (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->admin));

// APRÈS
$dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
$is_admin = mv3_is_admin($auth);
```

### Étape 3 : Remplacer vérification admin manuelle

```php
// AVANT
$is_admin = (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->admin));
if (!$is_admin) {
    json_error('Accès réservé aux administrateurs', 'FORBIDDEN', 403);
}

// APRÈS
mv3_require_admin($auth);
```

### Étape 4 : Remplacer logique filtre admin/employé

```php
// AVANT
if ($is_admin) {
    if ($filter_user_id) {
        $where[] = "r.fk_user = ".(int)$filter_user_id;
    }
} else {
    if ($dolibarr_user_id > 0) {
        $where[] = "r.fk_user = ".$dolibarr_user_id;
    } else {
        $where[] = "1 = 0";
    }
}

// APRÈS
$user_filter = mv3_get_user_filter_sql($auth, 'r.fk_user', $filter_user_id);
if (!empty($user_filter)) {
    $where[] = $user_filter;
}
```

---

## 🚀 Fichiers déjà refactorisés

### API v1 (✅ Fait)
- ✅ `rapports.php`
- ✅ `rapports_view.php`
- ✅ `rapports_debug.php`
- ✅ `users.php`
- ✅ `materiel.php`
- ✅ `regie.php`
- ✅ `sens_pose.php`
- ✅ `notifications.php`

### À faire
- 🔲 `admin/*.php`
- 🔲 `mobile_app/includes/*.php`

---

## 📖 Documentation complète

Voir : `REFACTORING_SONARQUBE.md`

---

**Version** : 1.0
**Date** : 2026-01-10
**Auteur** : MV3 PRO Portail Team
