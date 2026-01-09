# Récapitulatif Session 2026-01-09

---

## 🎯 Deux problèmes résolus

### 1. DEV MODE: Désactivation blocage anti-brute-force ✅

**Problème:**
- Après 5 tentatives de mot de passe incorrectes, compte verrouillé 15 minutes
- Impossible de tester rapidement pendant le développement

**Solution:**
- Flag `MV3_AUTH_DISABLE_LOCK = true` ajouté dans `/mobile_app/api/auth.php`
- Mode développement: pas de verrouillage, tests illimités
- Compteur de tentatives continue d'incrémenter (traçabilité)
- Message: "Tentative X/5. DEV MODE: Verrouillage désactivé."

**Fichier modifié:**
- `/new_dolibarr/mv3pro_portail/mobile_app/api/auth.php`

**Documentation:**
- `/new_dolibarr/mv3pro_portail/mobile_app/DEV_MODE_DISABLE_LOCK.md`

⚠️ **IMPORTANT:** Mettre `MV3_AUTH_DISABLE_LOCK = false` avant production !

---

### 2. FIX: Redirections PWA après login ✅

**Problème:**
- Après login PWA, redirection vers la racine Dolibarr au lieu du dashboard PWA
- URL incorrecte: `https://crm.mv-3pro.ch/#/dashboard`
- Obligation de taper manuellement l'URL complète

**Solution:**
- Création fichier `/pwa/src/config.ts` avec chemins centralisés
- Utilisation de `PWA_URLS.dashboard` au lieu de `'/#/dashboard'`
- Correction du manifest PWA (scope + start_url)
- Build complet effectué dans `pwa_dist/`

**Fichiers modifiés:**
- ✨ `/pwa/src/config.ts` (nouveau)
- `/pwa/src/pages/Login.tsx`
- `/pwa/src/lib/api.ts`
- `/pwa/vite.config.ts`

**Documentation:**
- `/pwa/FIX_REDIRECTIONS_PWA.md`

**Build:**
- ✅ `npm install` + `npm run build` effectués
- ✅ Fichiers générés dans `pwa_dist/`

---

## 🧪 Tests à effectuer

### Test 1: DEV MODE Anti-brute-force

1. Ouvrir: `/custom/mv3pro_portail/pwa_dist/#/login`
2. Activer "Mode Debug"
3. Essayer 10 fois avec un mauvais mot de passe
4. **Attendu:** Message "Tentative 10/5. DEV MODE: Verrouillage désactivé."
5. Entrer le bon mot de passe
6. **Attendu:** Connexion réussie (pas de blocage)

### Test 2: Redirections PWA

1. Ouvrir: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Activer "Mode Debug"
3. Se connecter avec email/password
4. **Attendu après login:**
   - ✅ URL: `/custom/mv3pro_portail/pwa_dist/#/dashboard`
   - ✅ Dashboard affiché
   - ❌ Plus de redirection vers login Dolibarr racine

5. Cliquer sur menu: Planning, Rapports, etc.
6. **Attendu:**
   - URLs restent dans `/custom/mv3pro_portail/pwa_dist/#/...`

7. Se déconnecter
8. **Attendu:**
   - Retour sur `/custom/mv3pro_portail/pwa_dist/#/login`

---

## 📁 Fichiers créés/modifiés

### DEV MODE

**Modifié:**
- `/new_dolibarr/mv3pro_portail/mobile_app/api/auth.php`
  - Ligne 11: `define('MV3_AUTH_DISABLE_LOCK', true);`
  - Ligne 143: Condition sur vérification `locked_until`
  - Ligne 171: Condition sur écriture `locked_until`
  - Ligne 196: Message personnalisé DEV MODE

**Créé:**
- `/new_dolibarr/mv3pro_portail/mobile_app/DEV_MODE_DISABLE_LOCK.md`

### FIX Redirections

**Créé:**
- `/pwa/src/config.ts` ✨
  - `BASE_PWA_PATH`, `PWA_URLS`, `API_PATHS`

**Modifié:**
- `/pwa/src/pages/Login.tsx`
  - Import `PWA_URLS`
  - Ligne 261: `window.location.href = PWA_URLS.dashboard;`

- `/pwa/src/lib/api.ts`
  - Import `API_PATHS`, `PWA_URLS`
  - Ligne 96: `window.location.href = PWA_URLS.login;`

- `/pwa/vite.config.ts`
  - Ligne 19: `scope: '/custom/mv3pro_portail/pwa_dist/'`
  - Ligne 20: `start_url: '/custom/mv3pro_portail/pwa_dist/#/dashboard'`

**Build:**
- `/pwa_dist/` (tous les fichiers régénérés)
  - `index.html`
  - `manifest.webmanifest`
  - `assets/index-D89soRs7.js`
  - `sw.js`

**Documentation:**
- `/pwa/FIX_REDIRECTIONS_PWA.md`
- `/RECAPITULATIF_SESSION_2026-01-09.md` (ce fichier)

---

## ⚠️ Rappels importants

### Avant mise en PRODUCTION

1. **Désactiver DEV MODE dans auth.php:**
   ```php
   // Ligne 11
   define('MV3_AUTH_DISABLE_LOCK', false);
   ```

2. **Tester le verrouillage:**
   - 5 tentatives incorrectes → blocage 15 min
   - Impossible de se connecter pendant 15 min
   - Message: "Compte verrouillé temporairement..."

### Si changement de chemin PWA

1. **Modifier config.ts:**
   ```typescript
   export const BASE_PWA_PATH = '/nouveau/chemin';
   ```

2. **Modifier vite.config.ts:**
   ```typescript
   base: '/nouveau/chemin/',
   scope: '/nouveau/chemin/',
   start_url: '/nouveau/chemin/#/dashboard',
   ```

3. **Rebuild:**
   ```bash
   cd pwa && npm run build
   ```

---

## 📊 État final

| Fonctionnalité | Status | Détails |
|----------------|--------|---------|
| DEV MODE Anti-brute-force | ✅ Actif | Désactivé pour tests illimités |
| Redirections PWA | ✅ Corrigées | Toujours dans `/pwa_dist/` |
| Config centralisée | ✅ Créée | `/pwa/src/config.ts` |
| Manifest PWA | ✅ Corrigé | Scope + start_url OK |
| Build PWA | ✅ Généré | Dans `/pwa_dist/` |
| Documentation | ✅ Complète | 2 docs + ce récap |

---

## 🚀 Prochaines actions recommandées

1. **Tester sur smartphone:**
   - Login avec mode debug
   - Vérifier URL après login
   - Tester navigation entre pages
   - Vérifier logout

2. **Avant production:**
   - Désactiver DEV MODE (`MV3_AUTH_DISABLE_LOCK = false`)
   - Tester le blocage anti-brute-force fonctionne
   - Vérifier les logs serveur

3. **Si tout OK:**
   - Déployer sur production
   - Documenter la procédure de changement de chemin PWA
   - Former les utilisateurs

---

Date: 2026-01-09
Session: 2 problèmes résolus
Status: ✅ TERMINÉ
Build: ✅ PWA générée dans pwa_dist/
Tests: ⏳ À effectuer sur smartphone
