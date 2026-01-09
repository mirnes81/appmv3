# FIX: Headers Authorization + Boucle de redirection

Date: 2026-01-09

---

## 🐛 Problèmes identifiés

### Problème 1: Header Authorization bloqué par NGINX
**Symptôme:** Les requêtes API avec `Authorization: Bearer TOKEN` retournaient 401
**Cause:** NGINX ne transmet pas le header `Authorization` par défaut aux scripts PHP/FastCGI

### Problème 2: Boucle de redirection après login
**Symptôme:** Login réussi (toutes étapes vertes) mais retour immédiat sur /login
**Cause:** 
- `ProtectedRoute` ne vérifiait pas correctement le token
- `api.ts` n'envoyait pas `X-Auth-Token` (uniquement `Authorization`)

---

## ✅ Solutions appliquées

### 1. Ajout de X-Auth-Token dans api.ts

**Fichier:** `/new_dolibarr/mv3pro_portail/pwa/src/lib/api.ts`

**AVANT:**
```typescript
if (token) {
  headers['Authorization'] = `Bearer ${token}`;
}
```

**APRÈS:**
```typescript
if (token) {
  headers['Authorization'] = `Bearer ${token}`;
  headers['X-Auth-Token'] = token;
}
```

**Pourquoi:** NGINX transmet `X-Auth-Token` mais pas `Authorization`. Les deux headers sont envoyés pour compatibilité.

---

### 2. Amélioration de ProtectedRoute

**Fichier:** `/new_dolibarr/mv3pro_portail/pwa/src/components/ProtectedRoute.tsx`

**Améliorations:**
1. Vérifie la présence du token dans `localStorage`
2. Appelle `/api/v1/me.php` avec les headers `Authorization` ET `X-Auth-Token`
3. Gère les erreurs 401 (token invalide) vs 500 (erreur serveur)
4. Sur 401 : nettoie le token et redirige vers login
5. Sur 500 : garde le token et laisse passer (affiche l'erreur)

**Code ajouté:**
```typescript
useEffect(() => {
  const token = storage.getToken();
  
  if (!token) {
    setHasValidToken(false);
    return;
  }

  const response = await fetch('/custom/mv3pro_portail/api/v1/me.php', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'X-Auth-Token': token,
    },
  });

  if (response.status === 401) {
    storage.clearToken();
    setHasValidToken(false);
  } else if (response.ok) {
    setHasValidToken(true);
  }
}, [location.pathname]);
```

---

### 3. Debug Panel sur Dashboard

**Fichier:** `/new_dolibarr/mv3pro_portail/pwa/src/pages/Dashboard.tsx`

**Ajout:**
- Panneau debug visible uniquement si `localStorage.mv3_debug === '1'`
- Affiche:
  - Token présent: YES/NO
  - Token masqué: abc...xyz
  - Route actuelle
  - User ID et Email
  - Résultat du test `/api/v1/me.php`:
    - Status HTTP
    - Success: true/false
    - Réponse complète

**Activation:**
1. Sur la page login, cliquer sur "Mode Debug"
2. Se connecter
3. Le dashboard affichera le panneau debug en haut

---

## 🎯 Validation

### Test 1: Login avec mode debug

1. Aller sur `/custom/mv3pro_portail/pwa_dist/#/login`
2. Activer "Mode Debug"
3. Se connecter avec email + password
4. Observer les 4 étapes qui passent au vert
5. La page se recharge
6. Le dashboard s'affiche avec le panneau debug en haut
7. **STOP CONDITION:** PAS de retour sur login

### Test 2: Panneau debug

**Vérifications dans le panneau:**
- ✅ Token présent: YES
- ✅ Token masqué: affiché
- ✅ User ID: numéro valide
- ✅ User Email: email correct
- ✅ Test /me.php Status: 200
- ✅ Test /me.php Success: ✅

### Test 3: Navigation post-login

1. Depuis le dashboard, cliquer sur "Planning"
2. Cliquer sur "Accueil"
3. Observer que le dashboard se recharge
4. **STOP CONDITION:** PAS de retour sur login

---

## 📊 Comparaison Avant/Après

### AVANT

**Flux login:**
1. Login → Stocke token dans localStorage
2. Redirige vers dashboard
3. `ProtectedRoute` vérifie `isAuthenticated` du contexte
4. Contexte pas à jour → `isAuthenticated = false`
5. **Redirection vers login** ← BOUCLE!

**Requêtes API:**
```
Authorization: Bearer abc123
X-Auth-Token: (absent)
```
→ NGINX ne transmet pas Authorization
→ PHP ne reçoit AUCUN header d'auth
→ **401 Unauthorized**

### APRÈS

**Flux login:**
1. Login → Stocke token
2. Reload complet (window.location.href)
3. AuthContext se réinitialise
4. AuthContext lit token depuis localStorage
5. AuthContext appelle /me.php avec les 2 headers
6. AuthContext met à jour user
7. `ProtectedRoute` vérifie token avec /me.php
8. Token valide → **Dashboard s'affiche**

**Requêtes API:**
```
Authorization: Bearer abc123
X-Auth-Token: abc123
```
→ NGINX transmet X-Auth-Token
→ PHP reçoit X-Auth-Token via $_SERVER['HTTP_X_AUTH_TOKEN']
→ _bootstrap.php extrait le token
→ **200 OK**

---

## 🔧 Fichiers modifiés

1. `/new_dolibarr/mv3pro_portail/pwa/src/lib/api.ts`
   - Ajout header `X-Auth-Token`

2. `/new_dolibarr/mv3pro_portail/pwa/src/components/ProtectedRoute.tsx`
   - Vérification async du token avec /me.php
   - Gestion 401 vs 500

3. `/new_dolibarr/mv3pro_portail/pwa/src/pages/Dashboard.tsx`
   - Ajout panneau debug
   - Test /me.php au mount

4. `/new_dolibarr/mv3pro_portail/pwa/src/pages/Login.tsx`
   - Déjà corrigé (window.location.href)

---

## 📝 Notes techniques

### Pourquoi 2 headers (Authorization + X-Auth-Token)?

**Raison:**
- `Authorization` est le header standard OAuth/JWT
- NGINX (par défaut) ne transmet PAS `Authorization` aux scripts FastCGI
- `X-Auth-Token` est un header custom que NGINX transmet sans problème

**Stratégie:**
1. Frontend envoie les 2 headers
2. Backend (_bootstrap.php) essaie les 2:
   - D'abord Authorization (si NGINX configuré)
   - Sinon X-Auth-Token (fallback)
3. Compatibilité maximale

### Pourquoi reload complet (window.location.href)?

**Avec navigate() (SPA):**
- Pas de reload
- Contexte garde son état en mémoire
- user = null
- isAuthenticated = false
- → Boucle

**Avec window.location.href:**
- Reload complet
- Contexte se réinitialise
- useEffect s'exécute
- Lit token → Appelle /me.php → setUser()
- isAuthenticated = true
- → Dashboard s'affiche

---

Date: 2026-01-09
Version: 2.0
Status: ✅ CORRIGÉ
Build: `index-2Ze314hI.js`
