# 🔍 Diagnostic Erreurs 500 - Guide Complet

## ✅ Gestionnaires Anti-500 installés

Les gestionnaires d'erreurs sont maintenant actifs dans :
- ✅ `api/v1/rapports.php` (lignes 17-43)
- ✅ `api/v1/rapports_debug.php` (lignes 8-34)

**Effet** : Même en cas d'erreur fatale PHP, les endpoints retourneront toujours du JSON exploitable au lieu d'une page HTML 500.

---

## 📋 Comment obtenir le message d'erreur exact

Voici 4 méthodes pour diagnostiquer les erreurs 500 :

### Méthode 1 : Via les logs PHP (RECOMMANDÉ)

#### Sur serveur Apache :
```bash
# Logs en temps réel
tail -f /var/log/apache2/error.log | grep "MV3"

# Voir les 100 dernières lignes
tail -100 /var/log/apache2/error.log | grep "MV3"

# Sur Ubuntu/Debian
tail -f /var/log/apache2/error.log

# Sur CentOS/RHEL
tail -f /var/log/httpd/error_log
```

#### Sur serveur PHP-FPM :
```bash
# Logs PHP-FPM
tail -f /var/log/php-fpm/error.log | grep "MV3"

# Ou selon votre config
tail -f /var/log/php8.1-fpm.log | grep "MV3"
tail -f /var/log/php/error.log | grep "MV3"
```

**Ce que vous verrez** :
```
[MV3 FATAL rapports.php] Call to undefined function mv3_get_dolibarr_user_id() at /var/www/dolibarr/custom/mv3pro_portail/api/v1/rapports.php:65
[MV3 EXCEPTION rapports_debug.php] Division by zero at /var/www/dolibarr/custom/mv3pro_portail/api/v1/rapports_debug.php:42
```

---

### Méthode 2 : Test avec cURL (depuis votre terminal)

#### A. Test rapports.php
```bash
curl -i https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports.php \
  -H "Cookie: DOLSESSID_mv3pro2=votre_session_id_ici" \
  -H "Accept: application/json"
```

#### B. Test rapports_debug.php
```bash
curl -i https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php \
  -H "Cookie: DOLSESSID_mv3pro2=votre_session_id_ici" \
  -H "Accept: application/json"
```

**Comment obtenir votre DOLSESSID** :
1. Ouvrez Chrome/Firefox DevTools (F12)
2. Onglet "Application" (Chrome) ou "Stockage" (Firefox)
3. Section "Cookies"
4. Copiez la valeur de `DOLSESSID_mv3pro2`

**Réponse attendue en cas d'erreur** :
```json
{
  "success": false,
  "error": "fatal_error",
  "message": "Call to undefined function mv3_get_dolibarr_user_id()",
  "file": "rapports.php",
  "line": 65
}
```

ou

```json
{
  "success": false,
  "error": "exception",
  "message": "Division by zero",
  "file": "rapports_debug.php",
  "line": 42
}
```

---

### Méthode 3 : Via le panneau Debug de la PWA (PLUS SIMPLE)

1. Connectez-vous à la PWA : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Allez sur la page "Rapports"
3. Cliquez sur l'icône "Bug" (🐛) en haut à droite
4. Le panneau debug s'ouvre et affiche **automatiquement** :

```
🌐 Dernier Appel API

• Endpoint: /rapports.php
• Timestamp: 10/01/2026 16:30:45
• Params: {
    "limit": 20,
    "page": 1
  }
• Réponse: {
    "status": "error",
    "error": "fatal_error",
    "message": "Call to undefined function mv3_get_dolibarr_user_id()",
    "file": "rapports.php",
    "line": 65
  }
```

**Avantages** :
- ✅ Pas besoin d'accès SSH
- ✅ Pas besoin de cURL
- ✅ Message visible immédiatement
- ✅ Historique des appels API
- ✅ Copier/coller facile

---

### Méthode 4 : Via Chrome DevTools Network

1. Ouvrez Chrome DevTools (F12)
2. Onglet "Network"
3. Rechargez la page Rapports
4. Cherchez la requête `rapports.php` ou `rapports_debug.php`
5. Cliquez dessus
6. Onglet "Response"

**Vous verrez maintenant du JSON au lieu de HTML** :
```json
{
  "success": false,
  "error": "fatal_error",
  "message": "Call to undefined function mv3_test()",
  "file": "rapports.php",
  "line": 67
}
```

---

## 🔍 Types d'erreurs capturées

### 1. Fonction inexistante
**Erreur** : `Call to undefined function mv3_test()`

**Cause possible** :
- Fonction pas importée (oubli de `require_once`)
- Typo dans le nom de la fonction
- Fichier de classe manquant

**Solution** :
```php
// Vérifier que core/init.php est chargé
require_once __DIR__ . '/../../core/init.php';

// Vérifier que la fonction existe dans core/auth.php
```

---

### 2. Variable non définie
**Erreur** : `Undefined variable $user`

**Cause possible** :
- Variable globale non déclarée
- `global $db, $conf, $user;` manquant

**Solution** :
```php
global $db, $conf, $user;

if (!$user || !$user->id) {
    json_fail(401, 'not_authenticated');
}
```

---

### 3. Erreur SQL
**Erreur** : `Table 'dolibarr.llx_mv3_rapport' doesn't exist`

**Cause possible** :
- Table pas créée
- Préfixe de table incorrect
- Base de données incorrecte

**Solution** :
```bash
# Vérifier les tables
mysql -u root -p dolibarr -e "SHOW TABLES LIKE '%mv3%';"

# Créer la table si nécessaire
mysql -u root -p dolibarr < sql/llx_mv3_rapport.sql
```

---

### 4. Session expirée
**Erreur** : `not_authenticated` (code 401)

**Cause** :
- Session Dolibarr expirée
- Cookies non transmis
- Utilisateur non connecté

**Solution** :
- Se reconnecter à Dolibarr
- Vérifier `credentials: 'include'` dans api.ts

---

### 5. Mémoire épuisée
**Erreur** : `Allowed memory size of 134217728 bytes exhausted`

**Cause** :
- Requête trop lourde
- Boucle infinie
- Trop de données chargées en mémoire

**Solution temporaire** :
```php
ini_set('memory_limit', '256M');
```

**Solution définitive** :
- Optimiser la requête SQL
- Ajouter pagination
- Limiter les JOINs

---

## 🚨 Scénarios de test

### Test 1 : Endpoint fonctionne normalement

**Requête** :
```bash
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php \
  -H "Cookie: DOLSESSID_mv3pro2=abc123"
```

**Réponse attendue (200)** :
```json
{
  "success": true,
  "debug_info": {
    "user_info": {
      "dolibarr_user_id": 20,
      "email": "fernando@example.com"
    },
    "total_rapports_in_entity": 5,
    "rapports_with_NEW_filter": 2
  }
}
```

---

### Test 2 : Non authentifié

**Requête** :
```bash
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports.php
```

**Réponse attendue (401)** :
```json
{
  "success": false,
  "error": "not_authenticated",
  "message": "Utilisateur non authentifié ou non lié à Dolibarr"
}
```

---

### Test 3 : Erreur fatale PHP (test volontaire)

**Modifier temporairement rapports.php ligne 50** :
```php
mv3_fonction_inexistante();  // Fonction qui n'existe pas
```

**Requête** :
```bash
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports.php \
  -H "Cookie: DOLSESSID_mv3pro2=abc123"
```

**Réponse attendue (500)** :
```json
{
  "success": false,
  "error": "fatal_error",
  "message": "Call to undefined function mv3_fonction_inexistante()",
  "file": "rapports.php",
  "line": 50
}
```

**Log serveur attendu** :
```
[MV3 FATAL rapports.php] Call to undefined function mv3_fonction_inexistante() at /var/www/.../rapports.php:50
```

**⚠️ IMPORTANT** : Ne pas oublier de supprimer cette ligne de test après !

---

## 📝 Checklist de déploiement

Avant de tester en production :

- [ ] Uploader `api/v1/rapports.php` avec gestionnaire anti-500
- [ ] Uploader `api/v1/rapports_debug.php` avec gestionnaire anti-500
- [ ] Vérifier que `core/init.php` existe et charge auth.php
- [ ] Vérifier que `core/auth.php` contient `mv3_get_dolibarr_user_id()`
- [ ] Vérifier que `core/permissions.php` contient `mv3_is_admin()`
- [ ] Vérifier les permissions des fichiers (644 pour .php)
- [ ] Tester avec cURL (voir commande ci-dessus)
- [ ] Vérifier les logs PHP (tail -f)
- [ ] Tester dans la PWA avec mode debug activé

---

## 🎯 Résultats attendus après déploiement

### ✅ AVANT (sans gestionnaire)
```
❌ Erreur 500
❌ Page HTML au lieu de JSON
❌ Impossible de savoir quelle fonction manque
❌ Aucun message dans le panneau debug PWA
```

### ✅ APRÈS (avec gestionnaire)
```
✅ Code HTTP clair (401, 500)
✅ JSON propre avec message d'erreur
✅ Fichier + ligne de l'erreur fournis
✅ Log serveur avec préfixe [MV3 FATAL] ou [MV3 EXCEPTION]
✅ Panneau debug PWA affiche le message
✅ Diagnostic immédiat sans accès SSH
```

---

## 🔄 Prochaine étape

Une fois que vous avez le message d'erreur exact :

1. **Si c'est `Call to undefined function mv3_xxx()`**
   → Vérifier que `core/init.php` est bien chargé
   → Vérifier que la fonction existe dans `core/auth.php` ou `core/functions.php`

2. **Si c'est `Undefined variable $xxx`**
   → Ajouter `global $db, $conf, $user;` en début de fichier

3. **Si c'est `Table doesn't exist`**
   → Créer la table avec les scripts SQL dans `sql/`

4. **Si c'est `not_authenticated`**
   → Vérifier que la session Dolibarr est valide
   → Vérifier `credentials: 'include'` dans la PWA

5. **Si c'est `Division by zero` ou erreur logique**
   → Bug dans le code métier, corriger la logique

---

## 💡 Astuce : Test rapide sans navigateur

```bash
# Script de test complet
#!/bin/bash

# Remplacer par votre session ID
SESSION="votre_DOLSESSID_ici"
BASE_URL="https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1"

echo "🔍 Test rapports_debug.php..."
curl -s "${BASE_URL}/rapports_debug.php" \
  -H "Cookie: DOLSESSID_mv3pro2=${SESSION}" | jq .

echo ""
echo "🔍 Test rapports.php..."
curl -s "${BASE_URL}/rapports.php?limit=5" \
  -H "Cookie: DOLSESSID_mv3pro2=${SESSION}" | jq .
```

Sauvegarder dans `test_api.sh`, puis :
```bash
chmod +x test_api.sh
./test_api.sh
```

---

## 📞 Support

En cas de problème persistant :

1. **Regardez les logs avec** : `tail -f /var/log/php-fpm/error.log | grep MV3`
2. **Testez avec cURL** (voir commandes ci-dessus)
3. **Activez le mode debug PWA** et copiez le message
4. **Envoyez-moi** :
   - Le message d'erreur JSON complet
   - Les logs PHP avec préfixe [MV3]
   - Le timestamp de l'erreur

---

**Statut actuel** : ✅ Gestionnaires anti-500 installés et prêts à capturer toutes les erreurs !

**Prochaine étape** : Déployez les fichiers, testez, et envoyez-moi le message d'erreur exact si le problème persiste.
