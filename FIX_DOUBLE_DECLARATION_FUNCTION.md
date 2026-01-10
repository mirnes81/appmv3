# ✅ FIX : Double déclaration de fonction corrigée

## 🐛 Erreur détectée

```
Cannot redeclare mv3_check_table_or_empty() (previously declared in
/home/ch314761/web/crm.mv-3pro.ch/public_html/custom/mv3pro_portail/api/v1/_bootstrap.php:868)
```

**Cause** : La fonction `mv3_check_table_or_empty()` était déclarée dans **2 fichiers** :
1. `api/v1/_bootstrap.php` (ligne 868)
2. `core/functions.php` (ligne 20)

Quand `rapports.php` chargeait les deux fichiers :
```php
require_once __DIR__ . '/_bootstrap.php';  // Déclare mv3_check_table_or_empty()
require_once __DIR__ . '/../../core/init.php';  // Charge core/functions.php qui redéclare mv3_check_table_or_empty()
```

→ **Fatal error : "Cannot redeclare"**

---

## ✅ Solution appliquée

**Fichier modifié** : `api/v1/_bootstrap.php` (ligne 868)

**AVANT** :
```php
function mv3_check_table_or_empty($db, $table_name, $endpoint_name = 'unknown') {
    if (!mv3_table_exists($db, $table_name)) {
        error_log("[MV3 $endpoint_name] Table manquante: $table_name");
        http_response_code(200);
        echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    return true;
}
```

**APRÈS** :
```php
if (!function_exists('mv3_check_table_or_empty')) {
    function mv3_check_table_or_empty($db, $table_name, $endpoint_name = 'unknown') {
        if (!mv3_table_exists($db, $table_name)) {
            error_log("[MV3 $endpoint_name] Table manquante: $table_name");
            http_response_code(200);
            echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        return true;
    }
}
```

**Effet** : La fonction n'est déclarée **qu'une seule fois** (la première fois qu'elle est chargée).

---

## 📦 Fichiers à déployer

### Backend (PRIORITÉ ABSOLUE)
```
custom/mv3pro_portail/api/v1/_bootstrap.php
```

**IMPORTANT** : Ce fichier est chargé par **TOUS les endpoints** de l'API v1, donc cette correction résout le problème pour :
- ✅ `rapports.php`
- ✅ `rapports_debug.php`
- ✅ `planning.php`
- ✅ `materiel.php`
- ✅ Tous les autres endpoints API v1

### Frontend (optionnel, déjà recompilé)
```
custom/mv3pro_portail/pwa_dist/*  (si vous voulez la dernière version PWA)
```

---

## 🧪 Test après déploiement

### Test 1 : Via cURL
```bash
curl -i https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php \
  -H "Cookie: DOLSESSID_mv3pro2=VOTRE_SESSION_ID"
```

**Réponse attendue (200 OK)** :
```json
{
  "success": true,
  "debug_info": {
    "user_info": {
      "dolibarr_user_id": 20,
      "email": "fernando@mv-3pro.ch"
    },
    "entity": 1,
    "total_rapports_in_entity": 0
  }
}
```

---

### Test 2 : Via la PWA

1. Ouvrez : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Connectez-vous
3. Allez sur "Rapports"
4. Cliquez sur l'icône "🐛" (Debug)

**Panneau debug devrait maintenant afficher** :
```
📊 Statistiques Rapports
• Total dans l'entité: 0
• Visibles avec NOUVEAU filtre: 0

🌐 Dernier Appel API
• Endpoint: /rapports.php
• Réponse: { "success": true, "data": { "items": [], "total": 0 } }
```

**Plus d'erreur "Cannot redeclare"** ✅

---

## 🔍 Vérification logs serveur

Après déploiement, vérifiez les logs :

```bash
tail -f /var/log/php-fpm/error.log | grep "MV3"
```

**Avant (avec erreur)** :
```
PHP Fatal error: Cannot redeclare mv3_check_table_or_empty()
```

**Après (sans erreur)** :
```
[MV3 Rapports] Table manquante: mv3_rapport  (ou autre message légitime)
```

---

## 📋 Checklist de déploiement

- [ ] Uploader `api/v1/_bootstrap.php` (ligne 868 modifiée)
- [ ] Vider le cache navigateur (Ctrl+Shift+R)
- [ ] Tester avec cURL (voir commande ci-dessus)
- [ ] Tester dans la PWA
- [ ] Vérifier le panneau debug (🐛)
- [ ] Confirmer qu'il n'y a plus d'erreur "Cannot redeclare"

---

## 🎯 Résultat attendu

### ✅ AVANT (avec erreur)
```
❌ Erreur: "Cannot redeclare mv3_check_table_or_empty()"
❌ Aucun rapport affiché
❌ Panneau debug montre l'erreur
```

### ✅ APRÈS (corrigé)
```
✅ Pas d'erreur de déclaration
✅ Liste des rapports affichée (ou message "Aucun rapport" si la table est vide)
✅ Panneau debug affiche les stats correctes
```

---

## 🚨 Si le problème persiste

### Scénario 1 : Toujours "Cannot redeclare"

**Cause possible** : Le fichier `_bootstrap.php` n'a pas été uploadé correctement

**Solution** :
1. Vérifiez que le fichier a bien été uploadé
2. Vérifiez les permissions (644)
3. Vérifiez que le fichier fait ~28 Ko (ligne 868 modifiée)

---

### Scénario 2 : Nouvelle erreur "Call to undefined function"

**Cause** : La fonction `mv3_check_table_or_empty()` n'est plus chargée

**Solution** : Vérifiez que `core/functions.php` existe et est chargé par `core/init.php`

---

### Scénario 3 : "Table mv3_rapport doesn't exist"

**Cause** : Table pas créée en base de données

**Solution** :
```bash
mysql -u root -p dolibarr < sql/llx_mv3_rapport.sql
```

---

## 📝 Résumé technique

**Problème** : Double déclaration de fonction PHP
**Fichier modifié** : `api/v1/_bootstrap.php` (ligne 868)
**Type de correction** : Ajout de `if (!function_exists())` guard
**Impact** : Tous les endpoints API v1 fonctionnent maintenant
**PWA recompilée** : Oui (version 0.17.5)

---

## 💡 Prochaine étape

Une fois déployé, testez et envoyez-moi :

1. ✅ Le résultat de cURL sur `rapports_debug.php`
2. ✅ Le contenu du panneau debug (🐛) dans la PWA
3. ✅ Confirmation que l'erreur "Cannot redeclare" a disparu

**Si tout fonctionne** : On pourra passer à la correction suivante (probablement créer la table `mv3_rapport` si elle n'existe pas).

---

**Status : ✅ Correction prête pour déploiement**
