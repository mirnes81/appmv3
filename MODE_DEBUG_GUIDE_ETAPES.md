# MODE DEBUG GUIDÉ - ÉTAPE PAR ÉTAPE

## 🎯 Objectif

Suivre visuellement chaque étape du processus d'authentification, de la connexion jusqu'au dashboard, avec des informations détaillées à chaque étape.

---

## 🚀 Activation du mode debug

### Sur la page de login

1. **Ouvrir l'application:** `http://votre-serveur/custom/mv3pro_portail/pwa_dist/#/login`
2. **Cliquer sur le bouton "Mode Debug"** en dessous du titre
3. **Le bouton devient rouge:** "🔍 DEBUG MODE ON"
4. **Un message apparaît:** "Mode debug activé - Suivi étape par étape"

**Le mode debug est maintenant actif et persistera dans votre navigateur** (stocké dans localStorage avec la clé `mv3_debug`).

---

## 📊 Les 4 étapes tracées

Quand vous vous connectez en mode debug, vous verrez **4 étapes** s'exécuter en temps réel:

### Étape 1: Connexion au serveur
- **Appel API:** `POST /custom/mv3pro_portail/mobile_app/api/auth.php?action=login`
- **Ce qui est tracé:**
  - Status HTTP (200 = OK)
  - Utilisateur retourné (email, nom)
  - ID utilisateur Dolibarr (ou NULL si non lié)
  - Token reçu (masqué: 6 premiers + 4 derniers caractères)
- **Statut:**
  - ✅ Vert = Login réussi
  - ❌ Rouge = Échec (mauvais identifiants, compte inactif, etc.)

### Étape 2: Stockage du token
- **Action:** Sauvegarde du token dans `localStorage`
- **Ce qui est tracé:**
  - Token masqué
  - Longueur du token
  - Vérification que le token est bien stocké
  - Vérification que le token lu correspond au token stocké
- **Statut:**
  - ✅ Vert = Token stocké avec succès
  - ❌ Rouge = Échec du stockage

### Étape 3: Test API /me.php
- **Appel API:** `GET /custom/mv3pro_portail/api/v1/me.php`
- **Headers envoyés:**
  - `Authorization: Bearer <token>`
  - `X-Auth-Token: <token>`
  - `X-MV3-Debug: 1`
- **Ce qui est tracé:**
  - Status HTTP (200 = OK, 401 = Non autorisé)
  - Données utilisateur retournées
  - `is_unlinked` (true/false)
  - `dolibarr_user_id`
  - Droits utilisateur (read, write, worker)
- **Statut:**
  - ✅ Vert = API répond correctement
  - ❌ Rouge = Erreur 401, token invalide, session expirée, etc.

### Étape 4: Redirection Dashboard
- **Action:** Redirection vers `/dashboard`
- **Ce qui est tracé:**
  - URL de destination
  - État de préparation
- **Statut:**
  - ✅ Vert = Prêt pour la redirection
  - ⚙️ Bleu = En cours de redirection

---

## 🔍 Informations affichées pour chaque étape

### Format d'affichage

Chaque étape affiche:
1. **Icône de statut:**
   - ⏳ Gris = En attente
   - ⚙️ Bleu = En cours
   - ✅ Vert = Réussi
   - ❌ Rouge = Échec

2. **Nom de l'étape:** ÉTAPE X: Description

3. **Détails JSON:** Un bloc JSON avec toutes les informations techniques

4. **Message d'erreur:** (si échec) Un message explicatif en rouge

---

## 📝 Exemple de flux réussi

```
🔍 DEBUG - Suivi étape par étape

✅ ÉTAPE 1: Connexion au serveur
{
  "status": 200,
  "user_email": "info@mv-3pro.ch",
  "user_name": "John Doe",
  "dolibarr_user_id": 0,
  "token_received": true,
  "token_masked": "abc123....xyz9"
}

✅ ÉTAPE 2: Stockage du token
{
  "token_masked": "abc123....xyz9",
  "token_length": 64,
  "stored_in_localStorage": true,
  "token_matches": true
}

✅ ÉTAPE 3: Test API /me.php
{
  "status": 200,
  "user_id": null,
  "user_email": "info@mv-3pro.ch",
  "user_name": "John Doe",
  "is_unlinked": true,
  "dolibarr_user_id": 0,
  "rights": {
    "read": true,
    "write": false,
    "worker": false
  }
}

✅ ÉTAPE 4: Redirection Dashboard
{
  "redirect_to": "/dashboard",
  "ready": true
}
```

**→ Après 1 seconde, redirection automatique vers le dashboard**

---

## �� Exemple de flux avec erreur

### Cas 1: Token invalide ou expiré

```
✅ ÉTAPE 1: Connexion au serveur
(...)

✅ ÉTAPE 2: Stockage du token
(...)

❌ ÉTAPE 3: Test API /me.php
❌ HTTP 401: Unauthorized

{
  "status": 401,
  "statusText": "Unauthorized",
  "response": {
    "success": false,
    "error": "UNAUTHORIZED",
    "message": "Authentification requise"
  },
  "token_sent": "Bearer abc123....xyz9",
  "headers_sent": {
    "Authorization": "Present",
    "X-Auth-Token": "Present",
    "X-MV3-Debug": "1"
  }
}
```

**→ Le flux s'arrête à l'étape 3, pas de redirection**

### Cas 2: Erreur de connexion

```
❌ ÉTAPE 1: Connexion au serveur
❌ Email ou mot de passe incorrect

{
  "status": 401,
  "response": {
    "success": false,
    "message": "Email ou mot de passe incorrect"
  }
}
```

**→ Le flux s'arrête à l'étape 1, pas de suite**

---

## 🖥️ Logs Backend (côté serveur)

En plus de l'affichage frontend, le header `X-MV3-Debug: 1` active des logs serveur dans le fichier error_log d'Apache/PHP.

### Format des logs backend

```
[MV3 API] ========== AUTH START ==========
[MV3 API] path=/custom/mv3pro_portail/api/v1/me.php
[MV3 API] method=GET
[MV3 API] auth_header_present=1
[MV3 API] x_auth_token_present=1
[MV3 API] token_extracted=1
[MV3 API] token_mask=abc123....xyz9
[MV3 API] token_length=64
[MV3 API] session_found=1
[MV3 API] user_rowid=1
[MV3 API] user_email=info@mv-3pro.ch
[MV3 API] dolibarr_user_id=0
[MV3 API] session_expired=0
[MV3 API] is_unlinked=1
[MV3 API] auth_result=SUCCESS
[MV3 API] auth_mode=mobile_token
[MV3 API] user_id=null
[MV3 API] mobile_user_id=1
[MV3 API] is_unlinked=1
[MV3 API] ========== AUTH END ==========
[MV3 API] /me.php auth successful, building response
```

### Localisation des logs

**Sur le serveur:**
- Debian/Ubuntu: `/var/log/apache2/error.log`
- RHEL/CentOS: `/var/log/httpd/error_log`
- Ou selon la configuration PHP: `/var/log/php/error.log`

**Voir les logs en temps réel:**
```bash
tail -f /var/log/apache2/error.log | grep "MV3 API"
```

---

## 🔧 Console du navigateur

Le mode debug écrit également des logs dans la console du navigateur (F12).

### Format des logs console

```javascript
[DEBUG STEP 1] Login request to: /custom/mv3pro_portail/mobile_app/api/auth.php?action=login
[DEBUG STEP 1] Login response: {status: 200, success: true, hasToken: true, user: {...}}
[DEBUG STEP 2] Token stored in localStorage: abc123....xyz9
[DEBUG STEP 3] Testing /me.php with token: abc123....xyz9
[DEBUG STEP 3] Headers sent: {Authorization: "Bearer abc123....xyz9", X-Auth-Token: "abc123....xyz9", X-MV3-Debug: "1"}
[DEBUG STEP 3] /me.php response status: 200
[DEBUG STEP 3] /me.php response body: {success: true, user: {...}}
[DEBUG STEP 4] All checks passed, redirecting to dashboard
[DEBUG] Authentication flow complete, navigating to dashboard
```

---

## ✅ Checklist de diagnostic

Utilisez cette checklist pour identifier le problème:

### ☑️ Étape 1 échoue
- **Problème:** Identifiants incorrects ou compte inactif
- **Solution:** Vérifier email/mot de passe, vérifier que le compte est actif dans la base de données

### ☑️ Étape 1 OK, Étape 2 échoue
- **Problème:** LocalStorage bloqué ou navigateur en mode privé
- **Solution:** Désactiver le mode privé, vérifier les paramètres de sécurité du navigateur

### ☑️ Étapes 1-2 OK, Étape 3 échoue avec 401
- **Problème:** Token invalide, session expirée, ou problème serveur
- **Actions:**
  1. Vérifier les logs backend pour voir si le token arrive
  2. Vérifier que la session existe en DB: `SELECT * FROM llx_mv3_mobile_sessions WHERE expires_at > NOW()`
  3. Vérifier que l'utilisateur mobile existe: `SELECT * FROM llx_mv3_mobile_users WHERE email = 'info@mv-3pro.ch'`

### ☑️ Étapes 1-2 OK, Étape 3 échoue avec 500
- **Problème:** Erreur serveur PHP/Dolibarr
- **Actions:**
  1. Vérifier les logs PHP: `tail -f /var/log/apache2/error.log`
  2. Vérifier que Dolibarr est accessible
  3. Vérifier la connexion à la base de données

### ☑️ Étapes 1-3 OK, is_unlinked = true
- **Ce n'est PAS un bug!** C'est le comportement attendu.
- **Signification:** Le compte mobile n'est pas lié à un utilisateur Dolibarr
- **Solution:** L'administrateur doit lier le compte:
  1. Aller sur `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
  2. Modifier l'utilisateur concerné
  3. Sélectionner un utilisateur Dolibarr dans la liste déroulante
  4. Enregistrer

### ☑️ Toutes les étapes OK, mais boucle vers /login
- **Problème:** Problème dans AuthContext ou ProtectedRoute
- **Actions:**
  1. Vérifier la console pour des erreurs React
  2. Vérifier que `useAuth()` retourne bien l'utilisateur
  3. Vérifier que ProtectedRoute ne redirige pas à tort

---

## 🔄 Désactivation du mode debug

### Via l'interface

1. **Cliquer sur le bouton "🔍 DEBUG MODE ON"**
2. **Le bouton devient gris:** "Mode Debug"
3. **Le mode debug est désactivé**

### Via la console

```javascript
localStorage.removeItem('mv3_debug');
location.reload();
```

---

## 🎓 Points clés à retenir

1. **Le mode debug est visuel:** Vous voyez chaque étape en temps réel
2. **Les tokens sont masqués:** Sécurité préservée (6 premiers + 4 derniers caractères)
3. **Les logs sont multiples:** Frontend (page + console) + Backend (error_log)
4. **Le mode persiste:** Une fois activé, il reste actif jusqu'à désactivation manuelle
5. **is_unlinked = true n'est PAS un bug:** C'est une fonctionnalité pour gérer les comptes non liés

---

## 🆘 Besoin d'aide?

Si le problème persiste après avoir suivi ce guide:

1. **Activer le mode debug**
2. **Reproduire le problème**
3. **Faire une capture d'écran** du panneau debug complet
4. **Copier les logs console** (F12 → Console → tout sélectionner → copier)
5. **Copier les logs backend** (`tail -100 /var/log/apache2/error.log | grep "MV3 API"`)
6. **Envoyer toutes ces informations** avec une description du problème

---

Date: 2026-01-09
Version: 1.0
Status: ✅ MODE DEBUG GUIDÉ OPÉRATIONNEL
