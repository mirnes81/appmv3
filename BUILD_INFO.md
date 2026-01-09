# MV3 PRO PWA - Build Information

## 📦 Build Status: ✅ SUCCESS

### Build Details
- **Date**: 2026-01-09
- **Version**: 1.0.1
- **Location**: new_dolibarr/mv3pro_portail/pwa_dist/
- **Total Size**: 201.53 KB (gzipped: 61.58 KB)
- **Build Time**: 2.51s

## 🔄 Dernière mise à jour (2026-01-09)

### Messages d'erreur améliorés
- ✅ Message 401 plus clair: "Compte mobile introuvable"
- ✅ Lien vers l'interface d'administration
- ✅ Instructions précises pour l'administrateur
- ✅ Lien permanent sur la page de login

### Fichiers modifiés
- `mobile_app/api/auth.php` - Message d'erreur amélioré
- `pwa/src/pages/Login.tsx` - Lien d'aide ajouté
- `pwa_dist/assets/index-BG4ySEry.js` - Build mis à jour

### Files Structure
```
new_dolibarr/mv3pro_portail/pwa_dist/
├── index.html              # 1.16 kB - Point d'entrée PWA
├── .htaccess               # Configuration Apache (routing + cache)
├── assets/
│   ├── index-BG4ySEry.js   # 201 kB - Bundle JS React/TS
│   └── index-BQiQB-1j.css  # 3.68 kB - Styles
├── manifest.webmanifest    # 0.39 kB - Manifest PWA
├── registerSW.js           # 0.20 kB - Service Worker registration
├── sw.js                   # Service Worker
├── workbox-1d305bb8.js     # Workbox runtime
├── icon-192.png            # Icône PWA 192x192
└── icon-512.png            # Icône PWA 512x512
```

## 🚀 Démarrage en Dev

Le dev server Vite est démarré automatiquement.

## 🏗️ Rebuild

Pour rebuilder la PWA:
```bash
cd new_dolibarr/mv3pro_portail/pwa
npm install  # Si nécessaire
npm run build
# Les fichiers sont générés dans ../pwa_dist/
```

Puis déployez `pwa_dist/` sur votre serveur Dolibarr.

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
- **Auth Mobile**: `/custom/mv3pro_portail/mobile_app/api/auth.php`
- **Auth**: Tokens JWT (stockés dans localStorage)
- **Durée session**: 30 jours

### Authentification Mobile Indépendante

⚠️ **Important:** La PWA utilise une authentification mobile dédiée (table `llx_mv3_mobile_users`),
pas les identifiants Dolibarr standard.

**Pour créer un utilisateur mobile:**
1. Interface web: `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
2. Ou SQL: `mysql -u root -p dolibarr < sql/INSTALLATION_RAPIDE.sql`

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

