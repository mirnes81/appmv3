# MV3 PRO PWA - Build Information

## 📦 Build Status: ✅ SUCCESS

### Build Details
- **Date**: 2026-01-07
- **Location**: Racine du projet
- **Total Size**: 248 KB (gzipped: ~62 KB)

### Files Structure
```
/
├── index.html              # 1.16 kB - Point d'entrée PWA
├── assets/
│   ├── index-Docusysw.js   # 196 kB - Bundle JS React/TS
│   └── index-BQiQB-1j.css  # 3.6 kB - Styles
├── manifest.webmanifest    # 387 B - Manifest PWA
├── registerSW.js           # 196 B - Service Worker registration
├── sw.js                   # 1.6 kB - Service Worker
├── workbox-1d305bb8.js     # 22 kB - Workbox runtime
├── icon-192.png            # Icône PWA 192x192
└── icon-512.png            # Icône PWA 512x512
```

## 🚀 Démarrage en Dev

Le dev server Vite est démarré automatiquement.

## 🏗️ Rebuild

Pour rebuilder la PWA:
```bash
cd new_dolibarr/mv3pro_portail/pwa
npm run build
cp -r ../pwa_dist/* ../../..
```

## 📱 Fonctionnalités

✅ PWA installable (Add to Home Screen)
✅ Service Worker avec cache offline
✅ Responsive design
✅ Mode plein écran mobile
✅ Authentification Dolibarr
✅ API REST v1
✅ React 18 + TypeScript
✅ React Router v6
✅ Gestion hors-ligne

## 🔗 Backend

La PWA communique avec:
- **API REST**: `/custom/mv3pro_portail/api/v1/`
- **Auth**: Session Dolibarr via cookies

## 📊 Modules Inclus

- Dashboard
- Rapports journaliers
- Gestion matériel
- Feuilles de régie
- Notes de frais
- Sens de pose carrelage
- Planning
- Notifications
- Profil utilisateur

## 🔧 Configuration

Les chemins sont configurés pour fonctionner depuis la racine du serveur web.
Pour déployer ailleurs, ajuster les chemins dans `index.html`.

