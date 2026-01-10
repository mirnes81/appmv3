# Problèmes de Fiabilité (Reliability) - SonarQube

## Statut Actuel
- **Rating New Code**: A (0 bugs)
- **Rating Overall Code**: C (89 bugs)
- **Remediation Effort**: 7h 33min

---

## 🔴 PROBLÈME #1: Retours de requêtes SQL non vérifiés

### Occurrences identifiées: ~30+ fichiers

**Impact**: Erreurs SQL silencieuses, comportements imprévisibles, données incohérentes

### Exemples:

#### ❌ `api/v1/auth/login.php` - Ligne 141
```php
// MAUVAIS: Le retour n'est pas vérifié
$sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_mobile_users
               SET login_attempts = ".$attempts."
               WHERE rowid = ".(int)$mobile_user->rowid;
$db->query($sql_update);  // ❌ Ignore les erreurs!
```

**Correction:**
```php
// BON: Vérifier le retour
if (!$db->query($sql_update)) {
    error_log("Erreur mise à jour tentatives: " . $db->lasterror());
    // En contexte API, optionnel de notifier l'utilisateur
}
```

#### ❌ `api/v1/auth/login.php` - Ligne 227
```php
// MAUVAIS: Génération d'API key sans vérification
$sql_update = "UPDATE ".MAIN_DB_PREFIX."user
               SET api_key = '".$db->escape($api_key)."'
               WHERE rowid = ".(int)$user_obj->rowid;
$db->query($sql_update);  // ❌ Si ça échoue, $api_key reste vide/invalide!
```

**Correction:**
```php
// BON: Critical operation must be verified
if (!$db->query($sql_update)) {
    error_log("CRITICAL: Failed to set API key for user " . $user_obj->rowid . ": " . $db->lasterror());
    json_error('Erreur lors de la génération du token', 'API_KEY_ERROR', 500);
}
```

#### ❌ `api/v1/auth/logout.php` - Ligne 26
```php
// MAUVAIS
$sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_sessions WHERE session_token = '".$db->escape($token)."'";
$db->query($sql);  // ❌ Pas de vérification
```

**Correction:**
```php
// BON
if (!$db->query($sql)) {
    error_log("Erreur suppression session: " . $db->lasterror());
    // La déconnexion côté client se fera quand même
}
```

---

## 🔴 PROBLÈME #2: Ressources DB jamais libérées

### Statistiques:
- **`$db->free()` trouvés**: 0 ❌
- **`fetch_object()`/`fetch_array()` trouvés**: 68 ✅
- **Ratio**: 0% de libération des ressources!

**Impact**:
- Fuite mémoire progressive
- Curseurs DB non fermés
- Performance dégradée en charge
- Épuisement possible des connexions DB

### Exemples:

#### ❌ `api/v1/_bootstrap.php` - Ligne 431-434
```php
// MAUVAIS: Ressource jamais libérée
$resql = $db->query($sql);

if ($resql && $db->num_rows($resql) > 0) {
    $session = $db->fetch_object($resql);
    // ... utilisation de $session ...
}
// ❌ Manque: $db->free($resql);
```

**Correction:**
```php
// BON: Toujours libérer les ressources
$resql = $db->query($sql);

if ($resql && $db->num_rows($resql) > 0) {
    $session = $db->fetch_object($resql);
    $db->free($resql);  // ✅ Libération immédiate après fetch
    // ... utilisation de $session ...
} elseif ($resql) {
    $db->free($resql);  // ✅ Libérer même si vide
}
```

#### ❌ `mobile_app/admin/manage_users.php` - Ligne 186
```php
// MAUVAIS: Boucle sans libération
if ($resql && $db->num_rows($resql) > 0) {
    while ($obj = $db->fetch_object($resql)) {
        // ... traitement ...
    }
}
// ❌ Manque: $db->free($resql);
```

**Correction:**
```php
// BON
if ($resql && $db->num_rows($resql) > 0) {
    while ($obj = $db->fetch_object($resql)) {
        // ... traitement ...
    }
    $db->free($resql);  // ✅ Après la boucle
} elseif ($resql) {
    $db->free($resql);  // ✅ Même si vide
}
```

---

## 🟡 PROBLÈME #3: Variables potentiellement non définies

### Exemples:

#### ⚠️ `mobile_app/admin/manage_users.php` - Ligne 333
```php
// Variable $user_edit peut être undefined si $user_id invalide
$selected = ($dol_user->rowid == $user_edit->dolibarr_user_id) ? 'selected' : '';
```

**Correction:**
```php
// Vérifier l'existence
$selected = (isset($user_edit) && $dol_user->rowid == $user_edit->dolibarr_user_id) ? 'selected' : '';
```

---

## 📋 Plan de Correction Priorisé

### Priorité CRITIQUE (Impact sécurité/données)

#### 1. Vérifier les UPDATE/DELETE non vérifiés
**Fichiers critiques:**
- `api/v1/auth/login.php` (lignes 141, 227)
- `api/v1/auth/logout.php` (ligne 26)
- `mobile_app/admin/manage_users.php` (lignes 103, 123, 134)
- `mobile_app/admin/create_mobile_user.php` (ligne 191)

**Temps estimé:** 2h
**Impact:** -20 à -30 bugs

---

### Priorité HAUTE (Performance/Stabilité)

#### 2. Ajouter `$db->free()` après TOUS les fetch
**Stratégie:**
1. Pattern de base:
```php
$resql = $db->query($sql);
if ($resql) {
    // ... fetch_object / fetch_array ...
    $db->free($resql);  // ✅ Toujours ajouter
}
```

2. Pattern boucle:
```php
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        // ...
    }
    $db->free($resql);  // ✅ Après la boucle
}
```

**Fichiers concernés:** ~37 fichiers API
**Temps estimé:** 4h
**Impact:** -40 à -50 bugs

---

### Priorité MOYENNE (Code Quality)

#### 3. Vérifier tous les SELECT
**Non critique** mais bonne pratique:
```php
$resql = $db->query($sql);
if (!$resql) {
    error_log("Erreur SELECT: " . $db->lasterror());
    // Gérer l'erreur selon le contexte
}
```

**Temps estimé:** 1h 30min
**Impact:** -10 à -15 bugs

---

## 🔧 Script de Correction Automatique (Pattern)

### Fonction helper pour simplifier
```php
/**
 * Execute query et libère automatiquement les ressources
 * @return object|false Résultat unique ou false
 */
function db_query_single($db, $sql) {
    $resql = $db->query($sql);
    if (!$resql) {
        error_log("SQL Error: " . $db->lasterror());
        return false;
    }

    $result = false;
    if ($db->num_rows($resql) > 0) {
        $result = $db->fetch_object($resql);
    }

    $db->free($resql);  // ✅ Toujours libéré
    return $result;
}

/**
 * Execute query et retourne un tableau de résultats
 * @return array
 */
function db_query_list($db, $sql) {
    $resql = $db->query($sql);
    if (!$resql) {
        error_log("SQL Error: " . $db->lasterror());
        return [];
    }

    $results = [];
    while ($obj = $db->fetch_object($resql)) {
        $results[] = $obj;
    }

    $db->free($resql);  // ✅ Toujours libéré
    return $results;
}
```

**Usage:**
```php
// Au lieu de:
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    $obj = $db->fetch_object($resql);
}

// Utiliser:
$obj = db_query_single($db, $sql);
if ($obj) {
    // ... traitement ...
}
```

---

## 📊 Impact Estimé des Corrections

| Correction | Bugs résolus | Temps | Priorité |
|------------|--------------|-------|----------|
| Vérifier UPDATE/DELETE critiques | 20-30 | 2h | CRITIQUE |
| Ajouter $db->free() partout | 40-50 | 4h | HAUTE |
| Vérifier SELECT | 10-15 | 1h30 | MOYENNE |
| Variables undefined | 5-10 | 1h | MOYENNE |
| **TOTAL** | **75-105** | **8h30** | - |

**Objectif:** Réduire de **89 bugs → 0-15 bugs** (Rating C → A)

---

## ✅ Checklist de Vérification

### Pour chaque fichier PHP avec requêtes SQL:

- [ ] Tous les `$db->query()` avec UPDATE/INSERT/DELETE sont vérifiés
- [ ] Tous les `$resql` sont suivis d'un `$db->free($resql)`
- [ ] Les variables utilisées dans les conditions sont vérifiées avec `isset()`
- [ ] Les erreurs critiques sont loggées avec `error_log()`
- [ ] Les erreurs utilisateur sont gérées gracieusement (API: json_error, UI: message)

---

## 🎯 Quick Wins (Corrections rapides)

### Top 5 fichiers à corriger en priorité:

1. **`api/v1/auth/login.php`** - Auth critique, 3+ bugs
2. **`api/v1/_bootstrap.php`** - Utilisé partout, 2+ bugs
3. **`mobile_app/admin/manage_users.php`** - Admin, 5+ bugs
4. **`api/v1/rapports.php`** - Très utilisé, 2+ bugs
5. **`api/v1/regie.php`** - Très utilisé, 2+ bugs

**Temps total Quick Wins:** 1h30
**Impact estimé:** -25 bugs

---

## 📝 Notes Importantes

### Pattern Dolibarr Standard
Dolibarr recommande:
```php
$resql = $db->query($sql);
if ($resql) {
    // ... fetch ...
    $db->free($resql);  // OBLIGATOIRE
} else {
    dol_print_error($db);  // Logging Dolibarr
}
```

### Exceptions
Certains `$db->query()` peuvent ne pas nécessiter de vérification stricte:
- Logs non critiques
- Statistiques
- Opérations "best effort"

**MAIS:** Ils doivent TOUJOURS libérer les ressources si un fetch est fait!

---

## 🚀 Prochaines Étapes

### Étape 1: Quick Wins (Immédiat)
Corriger les 5 fichiers prioritaires

### Étape 2: Corrections systématiques (Cette semaine)
- Ajouter `$db->free()` dans TOUS les fichiers API v1/
- Ajouter vérifications UPDATE/DELETE critiques

### Étape 3: Refactoring (Optionnel)
- Créer helpers `db_query_single()` et `db_query_list()`
- Utiliser dans les nouveaux développements

### Étape 4: Tests
- Test de charge pour vérifier la réduction des fuites mémoire
- Monitoring des connexions DB

---

## 📈 Métriques de Succès

- [ ] Rating Reliability: C → A
- [ ] Bugs Overall Code: 89 → <10
- [ ] Aucun `fetch_object()` sans `$db->free()` correspondant
- [ ] Tous les UPDATE/DELETE critiques vérifiés
- [ ] Quality Gate: Passed
