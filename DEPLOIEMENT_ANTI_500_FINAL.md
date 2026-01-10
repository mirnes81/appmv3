# 🚀 Déploiement Anti-500 - Guide Final

## ✅ Ce qui a été fait

### 1. Gestionnaires anti-500 installés

**Fichiers modifiés** :
- ✅ `api/v1/rapports.php` (lignes 17-43)
- ✅ `api/v1/rapports_debug.php` (lignes 8-34)

**Fonctionnalité** :
```php
// Capture TOUTES les erreurs PHP et retourne JSON au lieu de HTML 500
set_exception_handler()        // Exceptions
register_shutdown_function()   // Fatal errors (E_ERROR, E_PARSE, etc.)
header('Content-Type: application/json')  // Force JSON
```

---

### 2. PWA recompilée

**Version** : PWA v0.17.5
**Date** : 10/01/2026

**Fichiers générés** :
```
pwa_dist/index.html
pwa_dist/assets/index-CPmEceR_.js  (289.12 kB)
pwa_dist/assets/index-BQiQB-1j.css (3.68 kB)
pwa_dist/sw.js
pwa_dist/workbox-d4f8be5c.js
```

---

## 📦 Fichiers à déployer sur crm.mv-3pro.ch

### Backend PHP (PRIORITÉ 1)
```
custom/mv3pro_portail/api/v1/rapports.php
custom/mv3pro_portail/api/v1/rapports_debug.php
```

### Frontend PWA (PRIORITÉ 2)
```
custom/mv3pro_portail/pwa_dist/index.html
custom/mv3pro_portail/pwa_dist/assets/
custom/mv3pro_portail/pwa_dist/sw.js
custom/mv3pro_portail/pwa_dist/workbox-d4f8be5c.js
custom/mv3pro_portail/pwa_dist/registerSW.js
custom/mv3pro_portail/pwa_dist/manifest.webmanifest
```

**OU** (plus simple) :
```
custom/mv3pro_portail/pwa_dist/*  (tout remplacer)
```

---

## 🛠️ Commandes de déploiement

### Option 1 : Via SCP (SSH)
```bash
# Backend
scp new_dolibarr/mv3pro_portail/api/v1/rapports.php \
    new_dolibarr/mv3pro_portail/api/v1/rapports_debug.php \
  user@crm.mv-3pro.ch:/var/www/dolibarr/custom/mv3pro_portail/api/v1/

# Frontend
scp -r new_dolibarr/mv3pro_portail/pwa_dist/* \
  user@crm.mv-3pro.ch:/var/www/dolibarr/custom/mv3pro_portail/pwa_dist/
```

### Option 2 : Via SFTP/FTP
1. Connectez-vous à votre serveur FTP
2. Allez dans `/custom/mv3pro_portail/`
3. Uploadez :
   - `api/v1/rapports.php`
   - `api/v1/rapports_debug.php`
4. Allez dans `/custom/mv3pro_portail/pwa_dist/`
5. Uploadez tout le contenu de `pwa_dist/` (remplacer les fichiers existants)

### Option 3 : Via rsync (recommandé)
```bash
# Backend
rsync -avz new_dolibarr/mv3pro_portail/api/v1/rapports*.php \
  user@crm.mv-3pro.ch:/var/www/dolibarr/custom/mv3pro_portail/api/v1/

# Frontend
rsync -avz --delete new_dolibarr/mv3pro_portail/pwa_dist/ \
  user@crm.mv-3pro.ch:/var/www/dolibarr/custom/mv3pro_portail/pwa_dist/
```

---

## 🧪 Tests après déploiement

### Étape 1 : Vider le cache navigateur
```
Chrome/Edge : Ctrl + Shift + Delete → Tout effacer
Firefox : Ctrl + Shift + Delete → Tout effacer
```

Ou forcer le rechargement :
```
Ctrl + Shift + R (Windows/Linux)
Cmd + Shift + R (Mac)
```

---

### Étape 2 : Test rapports_debug.php

**A. Via cURL (depuis votre terminal)** :
```bash
curl -i https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php \
  -H "Cookie: DOLSESSID_mv3pro2=VOTRE_SESSION_ID"
```

**Comment obtenir votre session ID** :
1. Connectez-vous à Dolibarr
2. Ouvrez DevTools (F12)
3. Onglet "Application" → Cookies → Copiez `DOLSESSID_mv3pro2`

**B. Réponse attendue (si OK)** :
```json
{
  "success": true,
  "debug_info": {
    "user_info": {
      "dolibarr_user_id": 20,
      "email": "fernando@example.com",
      "name": "Fernando Test"
    },
    "entity": 1,
    "total_rapports_in_entity": 5,
    "rapports_with_NEW_filter": 2
  },
  "recommendation": "✅ 2 rapport(s) visible(s) pour cet utilisateur."
}
```

**C. Réponse si erreur (AVEC le gestionnaire anti-500)** :
```json
{
  "success": false,
  "error": "fatal_error",
  "message": "Call to undefined function mv3_get_dolibarr_user_id()",
  "file": "rapports_debug.php",
  "line": 20
}
```

**👉 C'EST CE MESSAGE QUE NOUS VOULONS !**

---

### Étape 3 : Test rapports.php

```bash
curl -i https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports.php \
  -H "Cookie: DOLSESSID_mv3pro2=VOTRE_SESSION_ID"
```

**Réponse attendue (si OK)** :
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "ref": "RAP2401-001",
        "date_rapport": "2024-01-10"
      }
    ],
    "total": 2,
    "page": 1,
    "limit": 20
  }
}
```

**Réponse si erreur** :
```json
{
  "success": false,
  "error": "fatal_error",
  "message": "Table 'dolibarr.llx_mv3_rapport' doesn't exist",
  "file": "rapports.php",
  "line": 115
}
```

---

### Étape 4 : Test via la PWA (Interface graphique)

1. Ouvrez : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Connectez-vous
3. Allez sur "Rapports"
4. Cliquez sur l'icône "🐛" (Debug) en haut à droite

**Panneau debug affichera** :
```
👤 Informations Utilisateur
• Nom: Fernando Test
• Email: fernando@example.com
• Dolibarr User ID: 20
• Mode: dolibarr_session

🌐 Dernier Appel API
• Endpoint: /rapports.php
• Timestamp: 10/01/2026 16:45:30
• Params: { "limit": 20, "page": 1 }
• Réponse: { ... }
```

**Si erreur, vous verrez maintenant** :
```
🌐 Dernier Appel API
• Endpoint: /rapports.php
• Réponse: {
    "status": "error",
    "error": "fatal_error",
    "message": "Call to undefined function mv3_test()",
    "file": "rapports.php",
    "line": 67
  }
```

---

## 🔍 Obtenir le message d'erreur exact

### Méthode 1 : Logs serveur (RECOMMANDÉ)

**SSH vers votre serveur** :
```bash
ssh user@crm.mv-3pro.ch
```

**Apache** :
```bash
tail -f /var/log/apache2/error.log | grep "MV3"
```

**PHP-FPM** :
```bash
tail -f /var/log/php-fpm/error.log | grep "MV3"
```

**Ce que vous verrez** :
```
[10-Jan-2026 16:45:30] [MV3 FATAL rapports.php] Call to undefined function mv3_get_dolibarr_user_id() at /var/www/dolibarr/custom/mv3pro_portail/api/v1/rapports.php:65
```

---

### Méthode 2 : Panneau Debug PWA (PLUS SIMPLE)

Pas besoin de SSH, pas besoin de cURL !

1. Ouvrez la PWA
2. Allez sur Rapports
3. Cliquez sur 🐛
4. **Copiez le message d'erreur affiché**
5. Envoyez-moi ce message

---

## 🎯 Quelle erreur chercher ?

Les erreurs les plus courantes :

### 1. Fonction manquante
```json
{
  "error": "fatal_error",
  "message": "Call to undefined function mv3_get_dolibarr_user_id()",
  "file": "rapports.php",
  "line": 65
}
```

**Cause** : Fichier `core/auth.php` ou `core/init.php` manquant ou pas chargé

**Solution** : Uploader aussi `core/init.php` et `core/auth.php`

---

### 2. Variable non définie
```json
{
  "error": "fatal_error",
  "message": "Undefined variable $user",
  "file": "rapports.php",
  "line": 48
}
```

**Cause** : `global $user;` manquant

**Solution** : Ajouter `global $db, $conf, $user;` ligne 48

---

### 3. Table inexistante
```json
{
  "error": "exception",
  "message": "Table 'dolibarr.llx_mv3_rapport' doesn't exist",
  "file": "rapports.php",
  "line": 115
}
```

**Cause** : Table pas créée en base

**Solution** : Créer la table avec `sql/llx_mv3_rapport.sql`

---

### 4. Session expirée
```json
{
  "error": "not_authenticated",
  "message": "Utilisateur non authentifié ou non lié à Dolibarr"
}
```

**Cause** : Pas de session Dolibarr valide

**Solution** : Se reconnecter à Dolibarr dans un autre onglet

---

## 📋 Checklist complète

**Avant de me contacter avec une erreur, vérifiez** :

- [ ] Les fichiers ont bien été uploadés sur le serveur
- [ ] Les permissions sont correctes (644 pour .php)
- [ ] Le cache navigateur a été vidé (Ctrl+Shift+R)
- [ ] Vous êtes connecté à Dolibarr dans un autre onglet
- [ ] Vous avez testé avec cURL (voir commande ci-dessus)
- [ ] Vous avez regardé les logs avec `tail -f ... | grep MV3`
- [ ] Vous avez activé le mode debug dans la PWA

**Si tout est OK, envoyez-moi** :
1. ✅ Le message JSON d'erreur complet (depuis cURL, logs, ou PWA debug)
2. ✅ Le timestamp de l'erreur
3. ✅ L'endpoint qui pose problème (`rapports.php` ou `rapports_debug.php`)

---

## 💡 Exemple de message à m'envoyer

```
📍 Endpoint : /api/v1/rapports.php
🕐 Timestamp : 10/01/2026 16:45:30
❌ Erreur :

{
  "success": false,
  "error": "fatal_error",
  "message": "Call to undefined function mv3_get_dolibarr_user_id()",
  "file": "rapports.php",
  "line": 65
}

📝 Log serveur :
[MV3 FATAL rapports.php] Call to undefined function mv3_get_dolibarr_user_id() at /var/www/dolibarr/custom/mv3pro_portail/api/v1/rapports.php:65
```

---

## 🔧 Diagnostic rapide selon l'erreur

| Message d'erreur | Cause probable | Solution |
|------------------|----------------|----------|
| `Call to undefined function mv3_get_dolibarr_user_id()` | `core/auth.php` pas chargé | Uploader `core/init.php` + `core/auth.php` |
| `Call to undefined function mv3_is_admin()` | `core/permissions.php` manquant | Uploader `core/permissions.php` |
| `Undefined variable $user` | `global $user;` manquant | Ajouter `global $db, $conf, $user;` |
| `Table doesn't exist` | Table SQL pas créée | Exécuter `sql/llx_mv3_rapport.sql` |
| `not_authenticated` | Session expirée | Se reconnecter à Dolibarr |
| `Division by zero` | Bug dans le code | Corriger la ligne indiquée |

---

## ⚡ Test rapide sans SSH

Script bash à sauvegarder dans `test_api.sh` :

```bash
#!/bin/bash

# Remplacer par votre session ID
SESSION="votre_DOLSESSID_ici"
BASE_URL="https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 Test rapports_debug.php"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s "${BASE_URL}/rapports_debug.php" \
  -H "Cookie: DOLSESSID_mv3pro2=${SESSION}" | jq .

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔍 Test rapports.php"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
curl -s "${BASE_URL}/rapports.php?limit=5" \
  -H "Cookie: DOLSESSID_mv3pro2=${SESSION}" | jq .
```

Usage :
```bash
chmod +x test_api.sh
./test_api.sh
```

---

## 📚 Documentation créée

1. **FIX_ERREURS_500_ANTI_CRASH.md** - Explication technique du gestionnaire
2. **DIAGNOSTIC_ERREURS_500.md** - Guide complet pour obtenir les erreurs
3. **DEPLOIEMENT_ANTI_500_FINAL.md** - Ce document (guide de déploiement)

---

## ✅ Résumé

**Ce qui est prêt** :
- ✅ Gestionnaire anti-500 dans rapports.php
- ✅ Gestionnaire anti-500 dans rapports_debug.php
- ✅ PWA compilée avec panneau debug amélioré
- ✅ Logs serveur avec préfixe [MV3 FATAL] et [MV3 EXCEPTION]
- ✅ Documentation complète pour le diagnostic

**Ce qu'il faut faire** :
1. ⚡ Uploader les fichiers PHP sur le serveur
2. ⚡ Uploader la PWA compilée
3. ⚡ Tester avec cURL ou PWA debug
4. ⚡ M'envoyer le message d'erreur JSON exact

**Vous verrez maintenant** :
```json
{
  "success": false,
  "error": "fatal_error",
  "message": "Call to undefined function mv3_test()",
  "file": "rapports.php",
  "line": 67
}
```

Au lieu de :
```html
<html><head><title>500 Internal Server Error</title></head>...</html>
```

---

**Status : ✅ Prêt pour déploiement et diagnostic**

**Prochaine étape : Déployez et envoyez-moi le message d'erreur JSON !**
