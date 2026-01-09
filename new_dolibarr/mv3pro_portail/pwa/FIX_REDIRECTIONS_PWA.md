# FIX: Redirections PWA après login

Date: 2026-01-09

---

## 🎯 Problème résolu

**Avant:**
- Après login PWA, redirection vers la racine Dolibarr au lieu du dashboard PWA
- URL incorrecte: `https://crm.mv-3pro.ch/#/dashboard`
- Obligé de taper manuellement: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/dashboard`

**Après:**
- Redirection automatique vers: `/custom/mv3pro_portail/pwa_dist/#/dashboard`
- Navigation toujours dans le sous-dossier pwa_dist
- Plus de sortie du contexte PWA

---

## 🔧 Modifications apportées

### 1. Création du fichier de configuration centralisé

**Fichier:** `/pwa/src/config.ts` ✨ NOUVEAU

```typescript
export const BASE_PWA_PATH = '/custom/mv3pro_portail/pwa_dist';

export const PWA_URLS = {
  login: `${BASE_PWA_PATH}/#/login`,
  dashboard: `${BASE_PWA_PATH}/#/dashboard`,
  planning: `${BASE_PWA_PATH}/#/planning`,
  // ... toutes les routes PWA
};

export const API_PATHS = {
  base: '/custom/mv3pro_portail/api/v1',
  auth: '/custom/mv3pro_portail/mobile_app/api/auth.php',
};
```

**Avantages:**
- ✅ Tous les chemins centralisés dans un seul fichier
- ✅ Facile à modifier si le chemin change
- ✅ Type-safe avec TypeScript
- ✅ Réutilisable dans toute l'application

---

### 2. Modification de Login.tsx

**Fichier:** `/pwa/src/pages/Login.tsx`

**Ligne 261 - AVANT:**
```typescript
window.location.href = '/#/dashboard';
```

**Ligne 261 - APRÈS:**
```typescript
import { PWA_URLS } from '../config';
// ...
window.location.href = PWA_URLS.dashboard;
```

**Impact:**
- Redirection vers `/custom/mv3pro_portail/pwa_dist/#/dashboard` après login debug
- Ne sort plus du contexte PWA

---

### 3. Modification de api.ts

**Fichier:** `/pwa/src/lib/api.ts`

**Ligne 94 - AVANT:**
```typescript
window.location.href = '/custom/mv3pro_portail/pwa_dist/#/login';
```

**Ligne 94 - APRÈS:**
```typescript
import { API_PATHS, PWA_URLS } from '../config';

const API_BASE_URL = API_PATHS.base;
const AUTH_API_URL = API_PATHS.auth;
// ...
window.location.href = PWA_URLS.login;
```

**Impact:**
- Redirection vers login PWA lors d'un 401 Unauthorized
- Utilise la config centralisée

---

### 4. Modification de vite.config.ts

**Fichier:** `/pwa/vite.config.ts`

**Lignes 19-20 - AVANT:**
```typescript
scope: '/',
start_url: '/',
```

**Lignes 19-20 - APRÈS:**
```typescript
scope: '/custom/mv3pro_portail/pwa_dist/',
start_url: '/custom/mv3pro_portail/pwa_dist/#/dashboard',
```

**Impact:**
- PWA manifest corrigé avec le bon scope
- L'application installée démarre directement sur le dashboard
- Pas de conflit avec d'autres applications du domaine

---

## ✅ Résultat final

### Flux de connexion corrigé

1. **Ouverture PWA:**
   - URL: `/custom/mv3pro_portail/pwa_dist/`
   - Redirect auto vers: `/custom/mv3pro_portail/pwa_dist/#/login`

2. **Après login (mode debug):**
   - ✅ Redirection: `/custom/mv3pro_portail/pwa_dist/#/dashboard`
   - ❌ Plus de redirection vers: `/#/dashboard` (racine)

3. **Après logout:**
   - Retour sur: `/custom/mv3pro_portail/pwa_dist/#/login`

4. **Erreur 401 (token expiré):**
   - Redirection: `/custom/mv3pro_portail/pwa_dist/#/login`
   - Clear du token localStorage

5. **Navigation dans l'app:**
   - Toutes les routes restent dans `pwa_dist`
   - React Router (HashRouter) gère la navigation interne

---

## 📱 Test sur smartphone

### Scénario de test

1. Ouvrir: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Activer "Mode Debug"
3. Se connecter avec email/password
4. **Résultat attendu:**
   - ✅ URL après login: `/custom/mv3pro_portail/pwa_dist/#/dashboard`
   - ✅ Dashboard affiché
   - ❌ Plus jamais de redirection vers login Dolibarr racine

---

## 🔄 Build et déploiement

**Build effectué:**
```bash
cd /tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/pwa
npm install
npm run build
```

**Fichiers générés dans pwa_dist/:**
- ✅ `index.html` avec base path correct
- ✅ `manifest.webmanifest` avec scope et start_url corrects
- ✅ `assets/index-D89soRs7.js` (bundle avec redirections fixes)
- ✅ `sw.js` (service worker)

---

## 📊 Comparaison avant/après

| Événement | AVANT | APRÈS |
|-----------|-------|-------|
| Login debug | `/#/dashboard` (racine) | `/custom/mv3pro_portail/pwa_dist/#/dashboard` ✅ |
| Login normal | Navigate (OK) | Navigate (OK) ✅ |
| Logout | Navigate `/login` (OK) | Navigate `/login` (OK) ✅ |
| 401 Error | `/custom/.../pwa_dist/#/login` ✅ | `/custom/.../pwa_dist/#/login` ✅ |
| PWA installée | Start URL: `/` ❌ | Start URL: `/custom/.../pwa_dist/#/dashboard` ✅ |
| Scope manifest | `/` (trop large) ❌ | `/custom/mv3pro_portail/pwa_dist/` ✅ |

---

## 🎯 Points clés

1. **Centralisation des chemins:**
   - Fichier `config.ts` unique
   - Changement facile si base path modifié

2. **Cohérence PWA:**
   - Toutes les redirections utilisent `PWA_URLS`
   - Scope et start_url corrects dans manifest

3. **Types de redirections:**
   - `window.location.href` → pour reloads complets (login, 401)
   - `navigate()` → pour navigation React Router (logout, liens)

4. **HashRouter:**
   - Routes: `#/dashboard`, `#/login`, etc.
   - Pas de serveur-side routing nécessaire
   - Compatible avec tous les hébergements

---

## 🚀 Prochaines étapes (optionnel)

**Si changement de domaine ou chemin:**

```typescript
// config.ts
export const BASE_PWA_PATH = '/nouveau/chemin/pwa';
```

**Puis rebuild:**
```bash
npm run build
```

Toutes les redirections suivront automatiquement.

---

Date: 2026-01-09
Version: 1.0
Status: ✅ Résolu et testé
Build: ✅ Généré dans pwa_dist/
