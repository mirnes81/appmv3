# GUIDE MODE DEBUG - Diagnostic d'authentification approfondi

## Vue d'ensemble

Un système de debug complet a été mis en place pour tracer chaque étape du processus d'authentification et identifier précisément où se situe le problème.

**Le mode debug restera actif jusqu'à résolution complète du problème.**

---

## 🔧 Outils disponibles

### 1. Page de debug PWA
**URL:** `/#/debug` (accessible après connexion)

**Fonctionnalités:**
- Visualiser l'utilisateur actuel
- Voir le token stocké
- Activer/désactiver le debug frontend
- Activer le debug backend
- Récupérer les informations d'authentification complètes
- Effacer le token

### 2. Endpoint de debug backend
**URL:** `/custom/mv3pro_portail/api/v1/debug_auth.php`

**Actions disponibles:**
- `?enable_logs=1` - Activer les logs backend
- `?disable_logs=1` - Désactiver les logs backend
- `?clear_logs=1` - Effacer les logs
- `?view_logs=1` - Voir les logs
- Sans paramètre - Récupérer l'état complet de l'auth

### 3. Logs backend
**Fichier:** `/tmp/mv3pro_auth_debug.log`

Contient tous les logs détaillés du backend (si activé).

---

## 🚀 Comment utiliser le mode debug

### Étape 1: Activer le debug

#### Option A: Via la PWA (recommandé)

1. **Se connecter** à l'application (même si ça ne fonctionne pas)
2. **Aller sur:** `/#/debug`
3. **Activer le mode debug frontend:**
   - Cliquer sur "Activer debug"
   - Recharger la page (F5)
4. **Activer le debug backend:**
   - Cliquer sur "Activer debug backend"

#### Option B: Via cURL

```bash
# Activer les logs backend
curl "http://votre-serveur/custom/mv3pro_portail/api/v1/debug_auth.php?enable_logs=1"
```

### Étape 2: Reproduire le problème

1. **Ouvrir la console du navigateur** (F12)
2. **Se déconnecter** (si connecté)
3. **Vider le cache** (Ctrl+Shift+Delete)
4. **Se reconnecter** avec le compte problématique

### Étape 3: Collecter les logs

#### Frontend (Console du navigateur)

Vous verrez des logs comme:
```
[MV3PRO DEBUG] Login attempt {email: "info@mv-3pro.ch"}
[MV3PRO DEBUG] Login response {success: true, hasToken: true, ...}
[MV3PRO DEBUG] Token saved to localStorage
[MV3PRO DEBUG] Fetching /me.php
[MV3PRO DEBUG] API Request {url: "/custom/mv3pro_portail/api/v1/me.php", ...}
[MV3PRO DEBUG] API Response {status: 200, ...}
[MV3PRO DEBUG] /me.php response {success: true, user: {...}, is_unlinked: true}
```

#### Backend (Fichier de logs)

**Voir les logs:**
```bash
# Sur le serveur
tail -f /tmp/mv3pro_auth_debug.log
```

**Ou via l'endpoint:**
```bash
curl "http://votre-serveur/custom/mv3pro_portail/api/v1/debug_auth.php?view_logs=1"
```

Les logs backend montrent:
```
[2026-01-09 10:23:45] require_auth() called
{
    "required": true,
    "request_uri": "/custom/mv3pro_portail/api/v1/me.php",
    "request_method": "GET"
}
--------------------------------------------------------------------------------
[2026-01-09 10:23:45] MODE B: Checking Mobile Token
--------------------------------------------------------------------------------
[2026-01-09 10:23:45] Bearer token extracted
{
    "token_length": 64,
    "token_preview": "abc123def456..."
}
--------------------------------------------------------------------------------
[2026-01-09 10:23:45] Executing SQL query for mobile session
{
    "sql": "SELECT s.rowid, s.user_id, s.expires_at, ..."
}
--------------------------------------------------------------------------------
[2026-01-09 10:23:45] Mobile session found in DB
{
    "mobile_user_id": 1,
    "email": "info@mv-3pro.ch",
    "dolibarr_user_id": 0,
    "expires_at": "2026-01-10 10:23:45"
}
--------------------------------------------------------------------------------
[2026-01-09 10:23:45] Checking if account is unlinked
{
    "dolibarr_user_id": 0,
    "is_unlinked": true
}
--------------------------------------------------------------------------------
[2026-01-09 10:23:45] Account is unlinked, skipping Dolibarr user loading
--------------------------------------------------------------------------------
[2026-01-09 10:23:45] Auth result created
{
    "mode": "mobile_token",
    "is_unlinked": true,
    "write_permission": false
}
--------------------------------------------------------------------------------
[2026-01-09 10:23:45] Authentication SUCCESS
{
    "mode": "mobile_token",
    "user_id": "null",
    "mobile_user_id": 1,
    "is_unlinked": true
}
--------------------------------------------------------------------------------
```

### Étape 4: Récupérer l'état complet

**Via la PWA:**
1. Aller sur `/#/debug`
2. Cliquer sur "Récupérer les infos debug"
3. Copier le JSON affiché

**Via cURL:**
```bash
curl -H "Authorization: Bearer VOTRE_TOKEN" \
  "http://votre-serveur/custom/mv3pro_portail/api/v1/debug_auth.php"
```

Cela retourne:
```json
{
  "success": true,
  "debug": {
    "timestamp": "2026-01-09 10:23:45",
    "request": {
      "method": "GET",
      "uri": "/custom/mv3pro_portail/api/v1/debug_auth.php",
      "headers": {
        "Authorization": "Present (Bearer...)",
        "Content-Type": "application/json"
      },
      "token_present": "YES (first 20 chars: abc123def456...)",
      "token_length": 64
    },
    "session": {
      "php_session_id": "abc123",
      "dol_login": "Not set",
      "dolibarr_user_id": "No Dolibarr user"
    },
    "auth_result": {
      "status": "AUTHENTICATED",
      "mode": "mobile_token",
      "user_id": null,
      "mobile_user_id": 1,
      "email": "info@mv-3pro.ch",
      "name": "John Doe",
      "role": "employee",
      "is_unlinked": true,
      "rights": {
        "read": true,
        "write": false,
        "worker": false
      }
    },
    "database_session": {
      "found": "YES",
      "session_id": 15,
      "user_id": 1,
      "expires_at": "2026-01-10 10:23:45",
      "is_expired": "No",
      "last_activity": "2026-01-09 10:23:45",
      "mobile_user": {
        "id": 1,
        "email": "info@mv-3pro.ch",
        "name": "John Doe",
        "role": "employee",
        "is_active": 1,
        "dolibarr_user_id": "NULL/0 - NOT LINKED!"
      },
      "dolibarr_user": "NOT LINKED"
    },
    "database": {
      "connected": "YES",
      "type": "mysqli",
      "db_name": "dolibarr"
    },
    "active_sessions": {
      "count": 3,
      "sessions": [...]
    },
    "mobile_users": {
      "count": 5,
      "users": [...]
    }
  },
  "warning": "⚠️ Ce endpoint expose des informations sensibles. NE PAS utiliser en production!"
}
```

---

## 📊 Ce qu'il faut vérifier dans les logs

### 1. Le token est-il présent ?

**Frontend:**
```
[MV3PRO DEBUG] API Request {hasToken: true, tokenPreview: "abc123..."}
```

**Backend:**
```
[...] Bearer token extracted {"token_length": 64, ...}
```

✅ **Si OUI:** Le token est bien envoyé au serveur
❌ **Si NON:** Le token n'est pas stocké ou n'est pas envoyé

### 2. La session est-elle trouvée en DB ?

**Backend:**
```
[...] Mobile session found in DB
{
    "mobile_user_id": 1,
    "email": "info@mv-3pro.ch",
    "dolibarr_user_id": 0,
    "expires_at": "2026-01-10 10:23:45"
}
```

✅ **Si OUI:** La session existe et n'est pas expirée
❌ **Si NON:** Token invalide ou session expirée

### 3. Le compte est-il lié à Dolibarr ?

**Backend:**
```
[...] Checking if account is unlinked
{
    "dolibarr_user_id": 0,
    "is_unlinked": true
}
```

✅ **is_unlinked = false:** Compte correctement lié
⚠️ **is_unlinked = true:** Compte NON lié (c'est le problème actuel)

### 4. L'authentification réussit-elle ?

**Backend:**
```
[...] Authentication SUCCESS
{
    "mode": "mobile_token",
    "user_id": "null",
    "mobile_user_id": 1,
    "is_unlinked": true
}
```

✅ **SUCCESS:** L'auth fonctionne
❌ **FAILED:** L'auth échoue (regarder la raison)

### 5. La PWA reçoit-elle la réponse ?

**Frontend:**
```
[MV3PRO DEBUG] /me.php response
{
    "success": true,
    "user": {...},
    "is_unlinked": true
}
```

✅ **success: true:** La réponse est reçue
❌ **Erreur 401:** Token rejeté par le serveur

### 6. La redirection fonctionne-t-elle ?

**Si is_unlinked = true:**
```
# La PWA devrait rediriger vers /#/account-unlinked
```

✅ **Redirigé:** Comportement correct
❌ **Boucle vers /login:** Bug dans la PWA

---

## 🐛 Scénarios de problèmes courants

### Problème 1: Boucle de redirection infinie (login → dashboard → login)

**Symptômes:**
- Le login réussit (token reçu)
- Redirection vers dashboard
- Immédiatement redirigé vers login
- Boucle infinie

**Ce qu'il faut vérifier:**
1. **Token stocké:**
   ```javascript
   localStorage.getItem('mv3pro_token') // doit retourner le token
   ```

2. **Appel /me.php:**
   ```
   [MV3PRO DEBUG] Fetching /me.php
   [MV3PRO DEBUG] API Response {status: 200 ou 401?}
   ```

3. **Si 401:**
   - Le token n'est pas envoyé → Vérifier Authorization header
   - Le token est invalide → Vérifier la session en DB
   - Le token est expiré → Vérifier expires_at

4. **Si 200:**
   - Vérifier is_unlinked dans la réponse
   - Si is_unlinked = true, la PWA devrait rediriger vers /account-unlinked
   - Si boucle vers /login, il y a un bug dans la PWA

### Problème 2: Token non envoyé au serveur

**Symptômes:**
- Login réussit
- Token stocké dans localStorage
- Mais le serveur ne le reçoit pas

**Ce qu'il faut vérifier:**
1. **Frontend envoie le header:**
   ```
   [MV3PRO DEBUG] API Request {hasToken: true}
   ```

2. **Backend reçoit le header:**
   ```
   [...] Bearer token extracted {"token_length": 64}
   ```

3. **Si backend ne reçoit pas:**
   - Vérifier les CORS (Access-Control-Allow-Headers)
   - Vérifier le format du header (doit être "Bearer TOKEN")

### Problème 3: Session expirée immédiatement

**Symptômes:**
- Login réussit
- Immédiatement après, "Non autorisé"

**Ce qu'il faut vérifier:**
1. **Durée de session:**
   ```sql
   SELECT expires_at FROM llx_mv3_mobile_sessions
   WHERE session_token = 'VOTRE_TOKEN';
   ```

2. **Backend logs:**
   ```
   [...] Mobile session NOT found in DB or expired
   {
       "num_rows": 0,
       "db_error": "..."
   }
   ```

3. **Si expires_at dans le passé:**
   - Vérifier l'heure du serveur vs client
   - Vérifier la durée de session dans le code (auth_login.php)

### Problème 4: Compte non lié (is_unlinked = true)

**Symptômes:**
- Login réussit
- Auth SUCCESS dans les logs
- Mais is_unlinked = true
- Redirection vers /account-unlinked

**Ce n'est PAS un bug, c'est le comportement attendu!**

**Solution:**
1. **L'admin doit lier le compte:**
   - Ouvrir `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
   - Cliquer sur "Modifier" pour l'utilisateur
   - Sélectionner un utilisateur Dolibarr dans la liste
   - Enregistrer

2. **Vérifier en DB:**
   ```sql
   SELECT email, dolibarr_user_id
   FROM llx_mv3_mobile_users
   WHERE email = 'info@mv-3pro.ch';
   ```

   Doit retourner un dolibarr_user_id > 0.

---

## 🔍 Commandes utiles

### Vérifier l'état de la session en DB

```sql
-- Toutes les sessions actives
SELECT s.rowid, s.user_id, s.expires_at, s.last_activity,
       u.email, u.firstname, u.lastname, u.dolibarr_user_id
FROM llx_mv3_mobile_sessions s
INNER JOIN llx_mv3_mobile_users u ON u.rowid = s.user_id
WHERE s.expires_at > NOW()
ORDER BY s.last_activity DESC;

-- Session spécifique par token (remplacer 'TOKEN')
SELECT s.*, u.*
FROM llx_mv3_mobile_sessions s
INNER JOIN llx_mv3_mobile_users u ON u.rowid = s.user_id
WHERE s.session_token = 'TOKEN';

-- Utilisateurs mobiles sans lien Dolibarr
SELECT rowid, email, firstname, lastname, role, dolibarr_user_id
FROM llx_mv3_mobile_users
WHERE dolibarr_user_id IS NULL OR dolibarr_user_id = 0;
```

### Nettoyer les sessions expirées

```sql
DELETE FROM llx_mv3_mobile_sessions
WHERE expires_at < NOW();
```

### Forcer un lien Dolibarr (temporaire pour test)

```sql
-- ATTENTION: Utiliser un dolibarr_user_id qui existe vraiment!
UPDATE llx_mv3_mobile_users
SET dolibarr_user_id = 1  -- ID de l'admin Dolibarr
WHERE email = 'info@mv-3pro.ch';
```

---

## 📝 Rapport de bug à envoyer

Si le problème persiste après tous ces tests, collecter les informations suivantes:

1. **Copie de la console frontend** (F12, onglet Console)
   - Tous les logs `[MV3PRO DEBUG]`
   - Toutes les erreurs en rouge

2. **Copie du fichier de logs backend**
   ```bash
   cat /tmp/mv3pro_auth_debug.log
   ```

3. **Résultat de l'endpoint debug**
   ```bash
   curl -H "Authorization: Bearer VOTRE_TOKEN" \
     "http://votre-serveur/custom/mv3pro_portail/api/v1/debug_auth.php" > debug_result.json
   ```

4. **État de la session en DB**
   ```sql
   SELECT * FROM llx_mv3_mobile_sessions
   WHERE session_token = 'VOTRE_TOKEN';

   SELECT * FROM llx_mv3_mobile_users
   WHERE email = 'info@mv-3pro.ch';
   ```

5. **Description du problème**
   - Étapes exactes pour reproduire
   - Ce qui se passe (boucle, erreur 401, etc.)
   - Ce qui devrait se passer
   - Capture d'écran si possible

---

## ⚙️ Désactiver le mode debug (quand le problème est résolu)

### Frontend

```javascript
// Dans la console du navigateur
localStorage.removeItem('mv3pro_debug');
```

Ou via la page `/#/debug` → Cliquer sur "Désactiver debug"

### Backend

```bash
curl "http://votre-serveur/custom/mv3pro_portail/api/v1/debug_auth.php?disable_logs=1"
```

Ou sur le serveur:
```bash
rm /tmp/mv3pro_debug.flag
```

### Nettoyer les logs

```bash
curl "http://votre-serveur/custom/mv3pro_portail/api/v1/debug_auth.php?clear_logs=1"
```

Ou sur le serveur:
```bash
rm /tmp/mv3pro_auth_debug.log
```

---

## 🎯 Prochaines étapes

1. **Activer le mode debug** (frontend + backend)
2. **Reproduire le problème** en se reconnectant
3. **Collecter les logs** (console + fichier)
4. **Analyser les logs** en suivant les vérifications ci-dessus
5. **Identifier le point de défaillance** exact
6. **Appliquer la correction** appropriée
7. **Vérifier que le problème est résolu**
8. **Désactiver le mode debug**

---

**Le mode debug restera actif jusqu'à ce que le problème soit complètement résolu.**

Date: 2026-01-09
Version: 1.0
Status: ✅ MODE DEBUG ACTIF
