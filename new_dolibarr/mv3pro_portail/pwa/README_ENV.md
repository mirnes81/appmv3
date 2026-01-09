# Configuration Dev/Prod - Guide Rapide

## 🎯 Objectif

Développer dans Bolt + Déployer en prod SANS toucher au code.

---

## 📁 Fichiers créés

### Variables d'environnement

```
.env.development     → npm run dev (Bolt)
.env.production      → npm run build (Dolibarr)
.env.example         → Template de référence
```

### Configuration

```
src/config.ts        → Chemins API et PWA (utilise les env vars)
src/vite-env.d.ts    → Types TypeScript pour env vars
vite.config.ts       → Config Vite adaptative (dev/prod)
```

---

## 🚀 Utilisation

### Développement dans Bolt

```bash
npm run dev
```

**Résultat:**
- Serveur: `http://localhost:5173/`
- Base path: `/` (racine)
- API: `/custom/mv3pro_portail`
- Hot reload actif

### Build production

```bash
npm run build
```

**Résultat:**
- Output: `../pwa_dist/`
- Base path: `/custom/mv3pro_portail/pwa_dist`
- Assets optimisés
- Prêt pour déploiement Dolibarr

---

## ✅ Vérification

### Build OK ?

```bash
# Vérifier manifest
cat ../pwa_dist/manifest.webmanifest | jq .scope

# Attendu: "/custom/mv3pro_portail/pwa_dist/"
```

### Config OK ?

```bash
# Dev
cat .env.development

# Prod
cat .env.production
```

---

## 🎯 Critères de réussite

- ✅ `npm run dev` → Preview dans Bolt fonctionne
- ✅ `npm run build` → Build généré dans `pwa_dist/`
- ✅ Même code = dev + prod
- ✅ Changement env = changement comportement

---

## 📖 Documentation complète

Voir: `CONFIG_ENV_DEV_PROD.md`

---

Date: 2026-01-09
Status: ✅ Configuré
