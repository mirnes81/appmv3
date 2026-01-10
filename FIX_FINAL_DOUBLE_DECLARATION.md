# 🎯 FIX COMPLET - Double Déclaration de Fonctions

## 🔍 DIAGNOSTIC

**Erreur détectée** :
```
Fatal error: Cannot redeclare mv3_check_table_or_empty()
(previously declared in .../api/v1/_bootstrap.php:905)
```

**Cause racine identifiée** :

La fonction `mv3_check_table_or_empty()` était déclarée dans **2 fichiers** :

1. ❌ `api/v1/_bootstrap.php` (ligne 905) - **NON protégée**
2. ❌ `core/functions.php` (ligne 20) - **NON protégée**

Même si tous les endpoints utilisaient `require_once`, la double déclaration créait un conflit.

---

## ✅ SOLUTION APPLIQUÉE

### Protection complète de TOUTES les fonctions

**16 fonctions protégées** dans `api/v1/_bootstrap.php` :
- `log_debug()`
- `log_error()`
- `json_ok()`
- `json_error()`
- `require_method()`
- `get_param()`
- `get_json_body()`
- `require_auth()`
- `require_rights()`
- `check_dev_mode()`
- `require_param()`
- `log_api_call()`
- `mv3_table_exists()`
- `mv3_column_exists()`
- `mv3_select_column()`
- `mv3_check_table_or_empty()`

**9 fonctions protégées** dans `core/functions.php` :
- `mv3_check_table_or_empty()` ← **CONFLIT RÉSOLU**
- `mv3_format_date()`
- `mv3_format_time()`
- `mv3_calculate_duration()`
- `mv3_get_statut_label()`
- `mv3_sql_escape()`
- `mv3_log_error()`
- `mv3_log_info()`
- `mv3_require_param()`

**Pattern appliqué** :
```php
if (!function_exists('nom_fonction')) {
    function nom_fonction(...) {
        // code
    }
}
```

---

## 📦 FICHIERS À DÉPLOYER

### **2 FICHIERS** (CRITIQUES)

```
1. custom/mv3pro_portail/api/v1/_bootstrap.php    (31 Ko, 914 lignes)
2. custom/mv3pro_portail/core/functions.php       (5 Ko, 198 lignes)
```

---

## 🚀 DÉPLOIEMENT RAPIDE (5 minutes)

### Via FTP (FileZilla / WinSCP)

#### Étape 1 : Backup
```
Naviguer vers : custom/mv3pro_portail/

1. Renommer api/v1/_bootstrap.php → _bootstrap.php.OLD
2. Renommer core/functions.php → functions.php.OLD
```

#### Étape 2 : Upload
```
1. Uploader : new_dolibarr/mv3pro_portail/api/v1/_bootstrap.php
   Vers    : custom/mv3pro_portail/api/v1/_bootstrap.php

2. Uploader : new_dolibarr/mv3pro_portail/core/functions.php
   Vers    : custom/mv3pro_portail/core/functions.php
```

#### Étape 3 : Test immédiat
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php
```

**AVANT** : `Fatal error: Cannot redeclare...`
**APRÈS** : `{"success": true, "debug_info": {...}}`

---

## ✅ CHECKLIST RAPIDE

- [ ] `_bootstrap.php` uploadé (31 Ko)
- [ ] `functions.php` uploadé (5 Ko)
- [ ] Test rapports_debug.php → 200 OK
- [ ] Test rapports.php → 200 OK
- [ ] PWA : Plus d'erreur dans le panneau debug
- [ ] Cache vidé : Ctrl+Shift+R

---

## 🎯 RÉSULTAT

**AVANT** :
```
❌ Fatal error: Cannot redeclare mv3_check_table_or_empty()
❌ Aucun rapport affiché
```

**APRÈS** :
```
✅ Plus d'erreur de déclaration
✅ Rapports affichés (ou "Aucun rapport" si vide)
✅ Toutes les APIs fonctionnent
```

---

**Status : ✅ PRÊT À DÉPLOYER**
**Impact : CRITIQUE**
**Durée : 5 minutes**
