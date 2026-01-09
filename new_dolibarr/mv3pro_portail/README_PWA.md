# 📱 MV3 PRO Mobile - Application PWA

Application mobile Progressive Web App (PWA) pour la gestion des rapports de chantier, planning et matériel.

## 🎯 Fonctionnalités

- ✅ **Authentification sécurisée** avec tokens JWT
- ✅ **Dashboard mobile** avec vue d'ensemble
- ✅ **Planning** des interventions
- ✅ **Rapports de chantier** avec photos
- ✅ **Gestion du matériel**
- ✅ **Notifications**
- ✅ **Mode hors-ligne** (PWA)
- ✅ **Installation sur mobile** comme une vraie app

## 📁 Structure du projet

```
mv3pro_portail/
├── pwa/                        # Code source React + TypeScript
│   ├── src/
│   │   ├── components/         # Composants réutilisables
│   │   ├── contexts/           # Contextes React (Auth, etc.)
│   │   ├── hooks/              # Hooks personnalisés
│   │   ├── lib/                # Utilitaires (API client)
│   │   ├── pages/              # Pages de l'application
│   │   └── main.tsx            # Point d'entrée
│   ├── package.json
│   ├── vite.config.ts
│   └── tsconfig.json
│
├── pwa_dist/                   # Build de production (déployé)
│   ├── index.html
│   ├── assets/
│   ├── manifest.webmanifest
│   ├── sw.js                   # Service Worker
│   └── .htaccess
│
├── mobile_app/                 # API PHP legacy
│   └── api/
│       └── auth.php            # Authentification mobile
│
└── sql/
    └── llx_mv3_mobile_users.sql # Tables SQL nécessaires
```

## 🚀 Installation

### Prérequis

- Dolibarr 13+ installé et fonctionnel
- Apache avec mod_rewrite activé
- PHP 7.4+
- MySQL/MariaDB
- Node.js 18+ (pour le développement uniquement)

### Étape 1: Créer les tables SQL

```bash
cd /var/www/html/dolibarr/htdocs/custom/mv3pro_portail
mysql -u VOTRE_USER -p VOTRE_DATABASE < sql/llx_mv3_mobile_users.sql
```

Vérifiez que les tables sont créées:
```sql
SHOW TABLES LIKE 'llx_mv3_mobile%';
```

Vous devriez voir:
- `llx_mv3_mobile_users`
- `llx_mv3_mobile_sessions`
- `llx_mv3_mobile_login_history`

### Étape 2: Créer un utilisateur mobile

**Option A: Via l'interface admin**

Accédez à: `https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/admin/create_mobile_user.php`

**Option B: Via SQL direct**

```sql
-- Mot de passe: test123
INSERT INTO llx_mv3_mobile_users
(email, password_hash, firstname, lastname, role, is_active)
VALUES
('test@example.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Test', 'User', 'employee', 1);
```

### Étape 3: Vérifier les permissions

```bash
chmod -R 755 pwa_dist/
chmod -R 755 mobile_app/
chown -R www-data:www-data pwa_dist/
chown -R www-data:www-data mobile_app/
```

### Étape 4: Activer mod_rewrite (Apache)

```bash
a2enmod rewrite
systemctl restart apache2
```

### Étape 5: Tester l'installation

Ouvrez: `https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/`

Login avec: `test@example.com` / `test123`

## 🛠️ Développement

### Installation des dépendances

```bash
cd pwa/
npm install
```

### Lancer le serveur de dev

```bash
npm run dev
```

L'application sera accessible sur `http://localhost:3100`

### Builder pour la production

```bash
npm run build
```

Les fichiers seront générés dans `pwa_dist/`

### Configuration de l'API

Éditez `pwa/src/lib/api.ts` pour configurer les URLs de l'API:

```typescript
const API_BASE_URL = '/custom/mv3pro_portail/api/v1';
const AUTH_API_URL = '/custom/mv3pro_portail/mobile_app/api/auth.php';
```

## 🔧 Configuration

### Variables d'environnement (dev uniquement)

Créez `pwa/.env.local`:

```bash
VITE_API_BASE_URL=/custom/mv3pro_portail/api/v1
VITE_AUTH_API_URL=/custom/mv3pro_portail/mobile_app/api/auth.php
```

### Configuration Apache

Le fichier `.htaccess` dans `pwa_dist/` est déjà configuré pour:
- Routing React (SPA)
- Cache des assets
- Headers de sécurité
- Compression GZIP

### Configuration Nginx

Si vous utilisez Nginx au lieu d'Apache:

```nginx
location /custom/mv3pro_portail/pwa_dist/ {
    try_files $uri $uri/ /custom/mv3pro_portail/pwa_dist/index.html;

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~* \.(html|json|webmanifest)$ {
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }
}
```

## 📱 Installation sur mobile

### iOS (Safari)

1. Ouvrez l'URL dans Safari
2. Appuyez sur le bouton "Partager"
3. Sélectionnez "Sur l'écran d'accueil"
4. Confirmez

### Android (Chrome)

1. Ouvrez l'URL dans Chrome
2. Appuyez sur le menu (3 points)
3. Sélectionnez "Ajouter à l'écran d'accueil"
4. Confirmez

L'icône de l'application apparaîtra sur votre écran d'accueil comme une vraie app native!

## 🔒 Sécurité

### Authentification

- Tokens JWT avec expiration (30 jours)
- Hash des mots de passe avec bcrypt
- Protection contre le brute-force (5 tentatives max)
- Verrouillage automatique du compte (15 min)
- Sessions stockées en base de données

### Headers de sécurité

Les headers suivants sont configurés automatiquement:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`

### HTTPS obligatoire

En production, utilisez TOUJOURS HTTPS pour protéger les tokens et mots de passe.

## 🐛 Dépannage

### Erreur: "Impossible de charger Dolibarr"

Le chemin vers `main.inc.php` est incorrect.

Éditez `mobile_app/api/auth.php` ligne 38-43:
```php
$res = @include __DIR__ . "/../../../main.inc.php";
```

### Erreur: "Table doesn't exist"

Les tables SQL ne sont pas créées.

```bash
mysql -u USER -p DATABASE < sql/llx_mv3_mobile_users.sql
```

### Erreur 404 sur les routes

Le `.htaccess` ne fonctionne pas ou mod_rewrite est désactivé.

```bash
a2enmod rewrite
systemctl restart apache2
```

### Page blanche

Vérifiez la console du navigateur (F12 > Console).

Vérifiez les logs Apache:
```bash
tail -f /var/log/apache2/error.log
```

### Erreur CORS

Les headers CORS ne sont pas configurés dans `auth.php`.

Vérifiez que ces lignes sont présentes:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

## 📊 API Endpoints

### Authentification

**POST** `/mobile_app/api/auth.php?action=login`
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**POST** `/mobile_app/api/auth.php?action=logout`
```
Headers: Authorization: Bearer TOKEN
```

**POST** `/mobile_app/api/auth.php?action=verify`
```
Headers: Authorization: Bearer TOKEN
```

### API v1 (avec authentification)

**GET** `/api/v1/me.php` - Infos utilisateur
**GET** `/api/v1/planning.php` - Planning
**GET** `/api/v1/rapports.php` - Liste des rapports
**POST** `/api/v1/rapports_create.php` - Créer un rapport

Toutes les requêtes nécessitent le header:
```
Authorization: Bearer TOKEN
```

## 🎨 Personnalisation

### Couleurs

Éditez `pwa/src/index.css` pour changer la palette de couleurs.

Les couleurs principales:
- `#0891b2` - Cyan 600 (primaire)
- `#06b6d4` - Cyan 500 (secondaire)

### Logo et icônes

Remplacez les fichiers:
- `pwa/public/icon-192.png` (192x192)
- `pwa/public/icon-512.png` (512x512)

Puis rebuilder:
```bash
npm run build
```

### Nom de l'application

Éditez `pwa/public/manifest.webmanifest`:
```json
{
  "name": "Votre Nom d'App",
  "short_name": "VotreApp"
}
```

## 📚 Technologies utilisées

- **React 18** - Framework UI
- **TypeScript** - Typage statique
- **Vite** - Build tool ultra-rapide
- **React Router** - Routing client-side
- **Workbox** - Service Worker pour PWA
- **PHP 7.4+** - Backend API
- **MySQL** - Base de données

## 📄 Licence

Ce module est propriétaire et fait partie du système MV3 PRO pour Dolibarr.

## 🆘 Support

Pour toute question ou problème:

1. Consultez `DIAGNOSTIC_ET_INSTALLATION.md` dans la racine
2. Vérifiez les logs (console browser + logs PHP)
3. Testez les API avec curl
4. Contactez le support technique

## 📝 Changelog

### Version 1.0.0 (2026-01-09)

- ✅ Interface de login sécurisée
- ✅ Authentification par token JWT
- ✅ Dashboard mobile
- ✅ Planning des interventions
- ✅ Création de rapports
- ✅ Mode PWA avec installation
- ✅ Service Worker pour offline
- ✅ Build optimisé avec code splitting
