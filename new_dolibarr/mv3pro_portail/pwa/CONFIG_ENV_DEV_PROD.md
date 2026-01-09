# Configuration Environnement Dev/Prod

Date: 2026-01-09

---

## 🎯 Objectif

Permettre le développement et test de la PWA dans Bolt SANS casser la production Dolibarr.

**Solution:** Variables d'environnement Vite pour gérer automatiquement les différences dev/prod.

---

## 📁 Fichiers de configuration

### `.env.development` (Développement dans Bolt)

```env
VITE_API_BASE=/custom/mv3pro_portail
VITE_BASE_PATH=/
```

**Utilisation:** `npm run dev`

**Comportement:**
- PWA servie sur: `http://localhost:5173/`
- Appels API vers: `/custom/mv3pro_portail/api/v1/...`
- Redirections: `/#/dashboard`, `/#/login` (racine)

---

### `.env.production` (Production Dolibarr)

```env
VITE_API_BASE=/custom/mv3pro_portail
VITE_BASE_PATH=/custom/mv3pro_portail/pwa_dist
```

**Utilisation:** `npm run build`

**Comportement:**
- PWA servie depuis: `/custom/mv3pro_portail/pwa_dist/`
- Appels API vers: `/custom/mv3pro_portail/api/v1/...`
- Redirections: `/custom/mv3pro_portail/pwa_dist/#/dashboard`

---

## 🔧 Fichiers modifiés

### 1. `/pwa/src/config.ts`

**Avant (chemins en dur):**
```typescript
export const BASE_PWA_PATH = '/custom/mv3pro_portail/pwa_dist';
export const API_PATHS = {
  base: '/custom/mv3pro_portail/api/v1',
  auth: '/custom/mv3pro_portail/mobile_app/api/auth.php',
};
```

**Après (variables d'environnement):**
```typescript
const API_BASE = import.meta.env.VITE_API_BASE || '/custom/mv3pro_portail';
const BASE_PATH = import.meta.env.VITE_BASE_PATH || '/custom/mv3pro_portail/pwa_dist';

export const BASE_PWA_PATH = BASE_PATH;
export const API_PATHS = {
  base: `${API_BASE}/api/v1`,
  auth: `${API_BASE}/mobile_app/api/auth.php`,
};
```

**Impact:**
- Tous les chemins API et redirections utilisent maintenant les variables d'environnement
- Changement automatique selon le mode (dev/prod)

---

### 2. `/pwa/vite.config.ts`

**Avant:**
```typescript
export default defineConfig({
  base: '/custom/mv3pro_portail/pwa_dist/',
  // ...
});
```

**Après:**
```typescript
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const basePath = env.VITE_BASE_PATH || '/custom/mv3pro_portail/pwa_dist';

  return {
    base: basePath,
    plugins: [
      VitePWA({
        manifest: {
          scope: `${basePath}/`,
          start_url: `${basePath}/#/dashboard`,
        }
      })
    ]
  };
});
```

**Impact:**
- Base path adapté automatiquement selon l'environnement
- Manifest PWA généré avec les bons chemins
- Assets référencés correctement

---

### 3. `/pwa/src/vite-env.d.ts` (nouveau)

```typescript
interface ImportMetaEnv {
  readonly VITE_API_BASE: string;
  readonly VITE_BASE_PATH: string;
}
```

**Impact:**
- TypeScript reconnaît les variables d'environnement
- Autocomplétion dans l'IDE
- Pas d'erreurs de compilation

---

## 🚀 Utilisation

### Développement dans Bolt

```bash
cd /tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/pwa

# Mode dev (utilise .env.development)
npm run dev
```

**Résultat:**
- Server: `http://localhost:5173/`
- Base path: `/` (racine)
- Hot reload activé
- DevTools activés

**Test:**
- Login → redirige vers `/#/dashboard`
- API appelle `/custom/mv3pro_portail/api/v1/...`

---

### Build pour production

```bash
cd /tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/pwa

# Build prod (utilise .env.production)
npm run build
```

**Résultat:**
- Fichiers générés dans: `../pwa_dist/`
- Base path: `/custom/mv3pro_portail/pwa_dist`
- Assets optimisés
- Service worker généré

**Test:**
- Copier `pwa_dist/` sur serveur Dolibarr
- Ouvrir: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
- Login → redirige vers `/custom/mv3pro_portail/pwa_dist/#/dashboard`

---

## 📊 Comparaison des modes

| Aspect | Développement | Production |
|--------|--------------|------------|
| Command | `npm run dev` | `npm run build` |
| Env file | `.env.development` | `.env.production` |
| Base path | `/` | `/custom/mv3pro_portail/pwa_dist` |
| API base | `/custom/mv3pro_portail` | `/custom/mv3pro_portail` |
| Server | Vite dev (5173) | Static files |
| Hot reload | ✅ Oui | ❌ Non |
| Optimisé | ❌ Non | ✅ Oui (minify, gzip) |
| Service Worker | ❌ Désactivé | ✅ Activé |

---

## 🔍 Vérification build production

### 1. Manifest PWA

```bash
cat pwa_dist/manifest.webmanifest | jq .
```

**Attendu:**
```json
{
  "scope": "/custom/mv3pro_portail/pwa_dist/",
  "start_url": "/custom/mv3pro_portail/pwa_dist/#/dashboard"
}
```

### 2. Index.html

```bash
head -15 pwa_dist/index.html
```

**Attendu:**
- Assets référencent `/custom/mv3pro_portail/pwa_dist/assets/...`
- Icon référence `/custom/mv3pro_portail/pwa_dist/icon-192.png`

### 3. JavaScript bundle

```bash
grep -o "VITE_API_BASE" pwa_dist/assets/*.js
```

**Attendu:** Aucune occurrence (variables remplacées au build)

---

## 🎯 Avantages de cette approche

### 1. Un seul code source
- ✅ Même code pour dev et prod
- ✅ Pas de branches séparées
- ✅ Pas de conditions `if (isDev)`

### 2. Configuration centralisée
- ✅ Tous les chemins dans `config.ts`
- ✅ Variables d'environnement standard Vite
- ✅ Facile à modifier

### 3. Sécurité
- ✅ Pas de secrets dans le code
- ✅ `.env.*` ignorés par Git (à ajouter dans .gitignore)
- ✅ Valeurs différentes par environnement

### 4. Déploiement simplifié
- ✅ `npm run build` = prêt pour prod
- ✅ Aucune modification manuelle
- ✅ Copier/coller `pwa_dist/` suffit

---

## ⚙️ Personnalisation

### Changer l'URL de l'API (dev seulement)

```env
# .env.development
VITE_API_BASE=http://localhost:8000/custom/mv3pro_portail
VITE_BASE_PATH=/
```

**Use case:** API locale pour tests

---

### Changer le chemin de déploiement

```env
# .env.production
VITE_API_BASE=/custom/mv3pro_portail
VITE_BASE_PATH=/mon/nouveau/chemin
```

**Puis rebuild:**
```bash
npm run build
```

Tout s'adapte automatiquement.

---

## 🐛 Troubleshooting

### Problème: Assets 404 après build

**Cause:** Base path incorrect

**Solution:**
1. Vérifier `.env.production`:
   ```env
   VITE_BASE_PATH=/custom/mv3pro_portail/pwa_dist
   ```
2. Rebuild:
   ```bash
   npm run build
   ```
3. Vérifier dans `pwa_dist/index.html` que les chemins sont bons

---

### Problème: API calls 404 en dev

**Cause:** CORS ou proxy non configuré

**Solution:**
Ajouter dans `vite.config.ts`:
```typescript
export default defineConfig(({ mode }) => {
  return {
    server: {
      proxy: {
        '/custom/mv3pro_portail': {
          target: 'https://crm.mv-3pro.ch',
          changeOrigin: true,
        }
      }
    }
  };
});
```

---

### Problème: Redirections incorrectes

**Cause:** Cache navigateur

**Solution:**
1. Hard refresh: Ctrl+Shift+R
2. Vider cache localStorage:
   ```javascript
   localStorage.clear();
   ```
3. Mode navigation privée pour tester

---

## 📝 Checklist déploiement

### Avant chaque build production

- [ ] `.env.production` existe et contient les bonnes valeurs
- [ ] `VITE_BASE_PATH` correspond au chemin de déploiement
- [ ] Code poussé sur Git (si applicable)

### Build

- [ ] `npm install` (si dépendances changées)
- [ ] `npm run build`
- [ ] Vérifier aucune erreur TypeScript
- [ ] Vérifier aucune erreur Vite

### Vérification

- [ ] `pwa_dist/manifest.webmanifest` a le bon scope
- [ ] `pwa_dist/index.html` référence les assets avec bon base path
- [ ] Taille du bundle raisonnable (~220 KB)

### Déploiement

- [ ] Copier `pwa_dist/*` vers `/custom/mv3pro_portail/pwa_dist/` sur serveur
- [ ] Tester login
- [ ] Tester navigation
- [ ] Tester redirections
- [ ] Vérifier Service Worker s'installe

---

## 🔄 Workflow complet

### 1. Développer dans Bolt

```bash
npm run dev
```

Modifier le code, voir les changements en temps réel.

### 2. Tester localement

Ouvrir `http://localhost:5173/`

### 3. Builder pour prod

```bash
npm run build
```

### 4. Tester le build localement (optionnel)

```bash
npm run preview
```

Ouvre le build sur `http://localhost:4173/custom/mv3pro_portail/pwa_dist/`

### 5. Déployer sur serveur

```bash
# Sur serveur Dolibarr
cd /path/to/dolibarr/custom/mv3pro_portail/
rm -rf pwa_dist/*
cp -r /path/to/build/pwa_dist/* pwa_dist/
```

### 6. Tester en production

Ouvrir: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`

---

Date: 2026-01-09
Status: ✅ Configuré et testé
Build: ✅ Fonctionnel
Mode dev: ✅ Prêt pour Bolt
Mode prod: ✅ Prêt pour Dolibarr
