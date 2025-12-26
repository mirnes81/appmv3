# MV3PRO - Application de Gestion

## Structure du projet

Ce projet contient uniquement les éléments essentiels pour le déploiement :

```
.
├── new_dolibarr/          # Modules Dolibarr
│   ├── mv3_tv_display/    # Module d'affichage TV
│   └── mv3pro_portail/    # Module portail principal
│
└── pro/                   # Application web PWA
    ├── api/               # API PHP de connexion
    ├── assets/            # JavaScript et CSS compilés
    ├── manifest.json      # Manifeste PWA
    ├── sw.js              # Service Worker
    └── index.html         # Page principale
```

## Déploiement

### 1. Modules Dolibarr

Uploadez le contenu de `new_dolibarr/` dans votre installation Dolibarr :

```bash
# Via FTP/SFTP sur votre serveur Dolibarr
/htdocs/custom/
├── mv3_tv_display/
└── mv3pro_portail/
```

Puis activez les modules depuis l'interface Dolibarr (Configuration → Modules).

### 2. Application PWA

Uploadez le contenu de `pro/` sur votre serveur web :

```bash
# Via FTP/SFTP
/public_html/pro/
├── api/
├── assets/
├── index.html
├── manifest.json
├── sw.js
└── .htaccess
```

### 3. Configuration de l'API

Éditez `pro/api/index.php` et configurez :

```php
// URL de votre Dolibarr
define('DOLIBARR_URL', 'https://votre-dolibarr.com');
define('DOLIBARR_API_KEY', 'votre-clé-api');
```

## URLs d'accès

- **Application PWA** : `https://votre-domaine.com/pro/`
- **API** : `https://votre-domaine.com/pro/api/`

## Documentation complète

Pour plus de détails, consultez les fichiers README dans chaque dossier :
- `new_dolibarr/mv3pro_portail/` - Documentation du module
- `pro/README_INSTALLATION.txt` - Guide d'installation PWA

## Support

Modules développés pour MV3PRO - Gestion de chantiers
