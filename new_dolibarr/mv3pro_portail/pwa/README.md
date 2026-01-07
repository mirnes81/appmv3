# MV3 PRO Mobile - PWA

Progressive Web App moderne pour les ouvriers MV3 Carrelage.

## Vue d'ensemble

PWA React/Vite mobile-first avec fonctionnalités chantier (GPS, photos, signature), offline-ready, installable sur Android/iOS.

## Structure

```
pwa/
├── src/
│   ├── components/      Composants réutilisables
│   ├── pages/           Pages de l'application
│   ├── lib/             API client + device features
│   ├── contexts/        React contexts (Auth)
│   ├── hooks/           Custom hooks (useOnline)
│   ├── App.tsx          Router principal
│   ├── main.tsx         Point d'entrée
│   └── index.css        Styles globaux
├── public/              Assets statiques
├── index.html           HTML template
├── vite.config.ts       Configuration Vite
├── package.json         Dépendances
└── README.md            Ce fichier

Sortie build: ../pwa_dist/
```

## Installation

```bash
cd /custom/mv3pro_portail/pwa
npm install
```

## Développement

```bash
npm run dev
```

Ouvre http://localhost:3100

## Build production

```bash
npm run build
```

Génère les fichiers dans `/pwa_dist/` prêts à être servis.

## URL production

```
https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/
```

L'URL utilise HashRouter (`#/`) pour compatibilité serveur statique:
- https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/#/login
- https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/#/dashboard

## Routes

| Route | Description | Status |
|-------|-------------|--------|
| `/login` | Connexion email/password | ✅ Fonctionnel |
| `/dashboard` | Tableau de bord | ✅ Fonctionnel |
| `/planning` | Liste affectations | ✅ Fonctionnel |
| `/planning/:id` | Détail affectation | 🚧 Stub (endpoint manquant) |
| `/rapports` | Liste rapports | ✅ Fonctionnel |
| `/rapports/new` | Rapport simple | ✅ Fonctionnel |
| `/rapports/new-pro` | Rapport PRO (GPS+photos+météo) | ✅ Fonctionnel |
| `/rapports/:id` | Détail rapport | 🚧 Stub (endpoint manquant) |
| `/regie` | Liste régie | 🚧 Stub (endpoint manquant) |
| `/regie/new` | Nouvelle régie | 🚧 Stub (endpoint manquant) |
| `/sens-pose` | Liste sens de pose | 🚧 Stub (endpoint manquant) |
| `/sens-pose/new` | Nouveau plan | 🚧 Stub (endpoint manquant) |
| `/materiel` | Liste matériel | 🚧 Stub (endpoint manquant) |
| `/notifications` | Notifications | 🚧 Stub (endpoint manquant) |
| `/profil` | Profil + déconnexion | ✅ Fonctionnel |

## API Endpoints utilisés

### Disponibles (Étape 2)

- `POST /mobile_app/api/auth.php?action=login` - Authentification
- `GET /api/v1/me.php` - Infos utilisateur
- `GET /api/v1/planning.php` - Planning
- `GET /api/v1/rapports.php` - Liste rapports
- `POST /api/v1/rapports_create.php` - Créer rapport

### À créer (backend)

- `GET /api/v1/planning/:id`
- `GET /api/v1/rapports/:id`
- `GET /api/v1/regie.php`
- `POST /api/v1/regie_create.php`
- `GET /api/v1/sens_pose.php`
- `POST /api/v1/sens_pose_create.php`
- `GET /api/v1/materiel.php`
- `GET /api/v1/notifications.php`

## Features device

### GPS / Géolocalisation

```typescript
import { getGeolocation } from './lib/device';

const position = await getGeolocation();
// { latitude, longitude, accuracy, timestamp }
```

Utilisé dans: Rapport PRO

### Camera / Photos

```typescript
import { capturePhoto } from './lib/device';

const base64 = await capturePhoto({
  maxWidth: 1200,
  quality: 0.8
});
```

Compression client automatique. Utilisé dans: Rapport PRO

### Signature

```typescript
import { SignatureCapture } from './lib/device';

const canvas = document.getElementById('signature');
const signature = new SignatureCapture(canvas);

// Effacer
signature.clear();

// Vérifier vide
signature.isEmpty();

// Récupérer base64
const dataUrl = signature.toDataURL();
```

Utilisé dans: Régie, Sens de pose (quand endpoints créés)

## PWA Features

### Manifest

- Installable sur Android/iOS
- Icônes 192x192 et 512x512
- Thème cyan (#0891b2)
- Mode standalone
- Orientation portrait

### Service Worker

- Cache automatique assets (Workbox)
- Mode offline basique
- Mise à jour automatique

### Offline banner

Affichage automatique quand `navigator.onLine = false`

## Auth Flow

1. Login (`/login`)
2. POST `/mobile_app/api/auth.php?action=login` avec email/password
3. Réponse contient `token`
4. Token stocké dans `localStorage` (`mv3pro_token`)
5. Toutes requêtes API incluent `Authorization: Bearer TOKEN`
6. Si 401 → logout automatique + redirect `/login`

## Design System

Variables CSS (reprises étape 3):

```css
--color-primary: #0891b2;
--color-success: #10b981;
--color-warning: #f59e0b;
--color-error: #ef4444;
--space-1: 8px;
--space-2: 16px;
--radius-md: 8px;
```

### Composants

- `.btn`, `.btn-primary`, `.btn-success`, `.btn-full`
- `.card`
- `.form-group`, `.form-label`, `.form-input`, `.form-textarea`
- `.alert`, `.alert-success`, `.alert-error`, `.alert-info`
- `.badge`, `.badge-success`, `.badge-warning`

### UX chantier

- Boutons minimum 48px hauteur (touch-friendly)
- Polices 16px+ (lisibilité plein soleil)
- Contrastes forts (pas de gris clair)
- Navigation bottom sticky
- États: loading, empty, error

## Technologies

- **React 18** - UI framework
- **React Router 6** - Navigation (HashRouter)
- **TypeScript** - Type safety
- **Vite** - Build tool ultra-rapide
- **Vite PWA Plugin** - Service Worker + Manifest
- **CSS Variables** - Design system

## Déploiement

### Sur serveur Dolibarr

1. Builder la PWA:
   ```bash
   cd /custom/mv3pro_portail/pwa
   npm run build
   ```

2. Le dossier `/pwa_dist/` est créé avec tous les fichiers

3. Accéder via:
   ```
   https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/
   ```

4. (Optionnel) Ajouter lien dans menu Dolibarr:
   - Paramètres → Menus
   - Ajouter entrée pointant vers `/custom/mv3pro_portail/pwa_dist/`

### Installation PWA sur mobile

1. Ouvrir URL dans Chrome/Safari mobile
2. Appuyer sur "Ajouter à l'écran d'accueil"
3. L'icône MV3 PRO apparaît comme une app native

## Troubleshooting

### Build fail

```bash
cd pwa
rm -rf node_modules package-lock.json
npm install
npm run build
```

### API 401 Unauthorized

Vérifier:
- Token valide dans localStorage (`mv3pro_token`)
- Endpoint API v1 accessible
- Session mobile valide côté serveur

### Photos ne fonctionnent pas

- Nécessite HTTPS (ou localhost)
- Permissions navigateur camera

### GPS ne fonctionne pas

- Nécessite HTTPS (ou localhost)
- Permissions navigateur geolocation
- Non bloquant: erreur affichée, formulaire reste utilisable

## Prochaines étapes

### Backend (Étape 5)

Créer les endpoints manquants:
- `/api/v1/regie.php` + `regie_create.php`
- `/api/v1/sens_pose.php` + `sens_pose_create.php`
- `/api/v1/materiel.php`
- `/api/v1/notifications.php`
- Détails: `planning/:id`, `rapports/:id`

### Features additionnelles

- [ ] Brouillons offline (localStorage)
- [ ] Sync automatique quand connexion rétablie
- [ ] Notifications push
- [ ] QR Code scan (pour matériel)
- [ ] Reconnaissance vocale (notes)
- [ ] Mode sombre
- [ ] Multi-langue

## Support

Version: 1.0.0
Date: 2025-01-07
Module: MV3 PRO Portail
Compatibilité: Mobile Android/iOS, Chrome, Safari, Firefox
