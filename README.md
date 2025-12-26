# MV3PRO - Application de Gestion de Chantiers

Application PWA complète avec modules Dolibarr pour la gestion de chantiers.

## Structure du Projet

```
project/
├── src/                   # Code source React/TypeScript
│   ├── App.tsx           # Composant principal
│   ├── main.tsx          # Point d'entrée
│   └── pages/            # Pages de l'application
│       ├── Login.tsx     # Page de connexion
│       └── Dashboard.tsx # Tableau de bord
│
├── pro/                  # Build de production (prêt à déployer)
│   ├── api/             # API PHP de connexion Dolibarr
│   ├── assets/          # JS et CSS compilés
│   ├── index.html       # Page principale
│   ├── manifest.json    # Manifeste PWA
│   ├── sw.js            # Service Worker
│   └── .htaccess        # Configuration Apache
│
└── new_dolibarr/        # Modules Dolibarr
    ├── mv3pro_portail/  # Module portail principal
    └── mv3_tv_display/  # Module affichage TV
```

## Développement

### Installation

```bash
npm install
```

### Développement local

```bash
npm run dev
```

Ouvre l'application sur http://localhost:5173

### Build de production

```bash
npm run build
```

Compile l'application dans le dossier `pro/`

## Déploiement

### 1. Application PWA

Uploadez le contenu du dossier `pro/` sur votre serveur web :

```bash
# Via FTP/SFTP vers votre serveur
/public_html/pro/
├── api/
├── assets/
├── index.html
├── manifest.json
├── sw.js
└── .htaccess
```

### 2. Configuration API

Éditez `pro/api/index.php` :

```php
define('DOLIBARR_URL', 'https://votre-dolibarr.com');
define('DOLIBARR_API_KEY', 'votre-clé-api');
```

### 3. Modules Dolibarr

Uploadez `new_dolibarr/` dans votre installation Dolibarr :

```bash
/htdocs/custom/
├── mv3pro_portail/
└── mv3_tv_display/
```

Activez les modules depuis l'interface Dolibarr (Configuration → Modules).

## URLs d'accès

- **Application PWA** : `https://app.mv-3pro.ch/pro/`
- **API** : `https://app.mv-3pro.ch/pro/api/`

## Fonctionnalités

- Authentification via Dolibarr
- Gestion des rapports de chantier
- Gestion du matériel
- Planning et calendrier
- Sens de pose
- Gestion de régie
- Mode hors ligne (PWA)
- Notifications push

## Technologies

- **Frontend** : React 18 + TypeScript + Vite
- **Styling** : Tailwind CSS
- **Backend** : PHP + Dolibarr API
- **Base de données** : MySQL/MariaDB (via Dolibarr)

## Documentation

Consultez les fichiers README dans chaque module pour plus de détails :

- `new_dolibarr/mv3pro_portail/` - Documentation du module portail
- `pro/README_INSTALLATION.txt` - Guide d'installation PWA

## Support

Développé pour MV3PRO - Gestion de chantiers
