# MV3 Pro - PWA Mobile Premium 🚀

Progressive Web App ultra-moderne pour la gestion de chantiers avec toutes les fonctionnalités premium.

## ✨ Fonctionnalités Premium

### 🎯 Productivité
- ✅ **Reconnaissance vocale** : Dictez vos observations pendant le travail
- ✅ **Templates de rapports** : Rapports pré-remplis pour gagner du temps
- ✅ **Auto-save intelligent** : Sauvegarde automatique toutes les 10 secondes
- ✅ **Mode photo rapide** : Ouvrir la caméra directement depuis le dashboard
- ✅ **Recherche globale** : Chercher dans tous les modules

### 📊 Dashboard Intelligent
- ✅ Statistiques personnelles (jour/semaine/mois)
- ✅ Météo en temps réel avec géolocalisation
- ✅ Timeline du planning
- ✅ Alertes de synchronisation
- ✅ Actions rapides personnalisables

### 🔒 Sécurité & Auth
- ✅ **Authentification biométrique** (Face ID / Touch ID)
- ✅ JWT avec expiration automatique
- ✅ Mode déconnecté sécurisé
- ✅ Chiffrement des données locales

### 📱 PWA Avancé
- ✅ **Mode 100% hors-ligne** avec synchronisation intelligente
- ✅ Installation native (iOS/Android)
- ✅ Notifications push
- ✅ Partage natif de fichiers
- ✅ Raccourcis 3D Touch
- ✅ Badge dynamique sur l'icône

### 🎨 Design Premium
- ✅ Interface moderne et fluide
- ✅ Animations micro-interactions
- ✅ Thème adaptatif (clair/sombre/auto)
- ✅ Gestes tactiles intuitifs
- ✅ Support Safe Area (iPhone notch)

### 📷 Capture Multimédia
- ✅ Appareil photo intégré avec compression
- ✅ Enregistrement vocal avec transcription IA
- ✅ Géolocalisation automatique des photos
- ✅ Upload prioritaire en arrière-plan

## 🛠 Technologies

- **Frontend** : React 18 + TypeScript + Vite
- **Styling** : Tailwind CSS
- **Database locale** : IndexedDB
- **Cache** : Service Worker + Supabase (optionnel)
- **Backend** : PHP 8+ + MySQL (Dolibarr)
- **Auth** : JWT + WebAuthn (biométrie)

## 📦 Installation

### 1. Installer les dépendances

```bash
npm install
```

### 2. Configurer les variables d'environnement

Créer un fichier `.env` :

```env
VITE_API_URL=https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile
VITE_SUPABASE_URL=https://votre-projet.supabase.co
VITE_SUPABASE_ANON_KEY=votre_cle_anon
```

### 3. Configurer Supabase (optionnel mais recommandé)

Exécuter le fichier `SUPABASE_MIGRATIONS.sql` dans votre dashboard Supabase :

1. Allez sur [supabase.com](https://supabase.com)
2. Dashboard > SQL Editor > New Query
3. Collez le contenu de `SUPABASE_MIGRATIONS.sql`
4. Exécutez

### 4. Installer les API PHP sur le serveur Dolibarr

```bash
# Sur votre serveur Dolibarr
cd /var/www/dolibarr/htdocs/custom/mv3pro_portail/

# Uploader le dossier api_php
scp -r ./api_php/ user@server:/var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/

# Configurer les permissions
chmod 755 api_mobile/
chmod 644 api_mobile/*.php
chmod 755 api_mobile/*/
chmod 644 api_mobile/*/*.php
```

Puis éditer `api_mobile/config.php` avec vos paramètres MySQL.

### 5. Créer les tables MySQL nécessaires

```sql
-- Table pour les photos des rapports
CREATE TABLE IF NOT EXISTS llx_mv3_rapport_photos (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    rapport_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size INT DEFAULT 0,
    uploaded_at DATETIME NOT NULL,
    FOREIGN KEY (rapport_id) REFERENCES llx_mv3_rapport(rowid) ON DELETE CASCADE
);

-- Index pour performance
CREATE INDEX idx_rapport_photos_rapport ON llx_mv3_rapport_photos(rapport_id);
```

### 6. Lancer en développement

```bash
npm run dev
```

L'app sera disponible sur `http://localhost:5173`

### 7. Build pour production

```bash
npm run build
```

Les fichiers seront dans le dossier `dist/`

## 🚀 Déploiement

### Option 1 : Hébergement statique (Vercel, Netlify)

```bash
# Build
npm run build

# Les fichiers dans dist/ sont prêts à être déployés
```

### Option 2 : Serveur web classique

```bash
# Build
npm run build

# Copier dist/ vers votre serveur web
rsync -avz dist/ user@server:/var/www/mv3-pwa/

# Configurer Nginx/Apache pour servir les fichiers
```

### Configuration Nginx

```nginx
server {
    listen 80;
    server_name app.mv-3pro.ch;

    root /var/www/mv3-pwa;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /manifest.json {
        add_header Cache-Control "public, max-age=86400";
    }

    location /sw.js {
        add_header Cache-Control "no-cache";
    }
}
```

## 📱 Installation sur mobile

### iOS (iPhone/iPad)

1. Ouvrir Safari
2. Aller sur l'URL de l'app
3. Appuyer sur le bouton Partager
4. Sélectionner "Sur l'écran d'accueil"
5. Confirmer

### Android

1. Ouvrir Chrome
2. Aller sur l'URL de l'app
3. Menu (3 points) > "Installer l'application"
4. Confirmer

L'app apparaîtra comme une app native !

## 🧪 Tests

### Test API

```bash
# Test de login
curl -X POST https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"password"}'
```

### Test de la PWA

1. Ouvrir Chrome DevTools
2. Application > Service Workers
3. Vérifier que le SW est enregistré
4. Application > Manifest
5. Vérifier que le manifest est valide

## 📖 Documentation Modules

### Rapports
- Création avec géolocalisation automatique
- Photos avec compression intelligente
- Notes vocales transcrites
- Auto-save toutes les 10 secondes
- Templates personnalisables

### Régie
- Suivi des heures par ouvrier
- Matériel utilisé
- Signature électronique
- Export PDF

### Sens de pose
- Plans par pièce
- Photos de référence
- Couleur et largeur des joints
- Signature client

### Matériel
- Inventaire en temps réel
- Traçabilité complète
- Alertes de stock

### Planning
- Vue calendrier
- Synchronisation avec Dolibarr
- Notifications de rappel

## 🔧 Configuration Avancée

### Activer la météo

Obtenir une clé API gratuite sur [OpenWeather](https://openweathermap.org/api)

Éditer `api_php/weather/current.php` :
```php
$apiKey = 'VOTRE_CLE_API';
```

### Personnaliser le thème

Éditer `src/index.css` :
```css
:root {
  --primary: #2563eb;  /* Couleur principale */
  --success: #10b981;  /* Succès */
  --warning: #f59e0b;  /* Avertissement */
  --danger: #ef4444;   /* Erreur */
}
```

### Configurer la compression des photos

Éditer `src/components/CameraCapture.tsx` :
```typescript
const quality = 0.8; // 0-1 (0.8 = 80% qualité)
const maxWidth = 1920;
const maxHeight = 1080;
```

## 🐛 Dépannage

### La PWA ne s'installe pas
- Vérifier que le site est en HTTPS
- Vérifier que `manifest.json` est accessible
- Vérifier que le Service Worker s'enregistre

### Les photos ne s'uploadent pas
- Vérifier les permissions PHP (upload_max_filesize)
- Vérifier que le dossier photos/ existe
- Vérifier les logs d'erreur PHP

### La synchronisation offline ne fonctionne pas
- Vérifier la console DevTools > Application > IndexedDB
- Vérifier la file sync_queue
- Vérifier la connexion réseau

### L'authentification biométrique ne fonctionne pas
- Nécessite HTTPS obligatoirement
- Nécessite un appareil compatible (iOS 14+, Android 9+)
- L'utilisateur doit avoir configuré Face ID/Touch ID

## 📊 Performance

- ⚡ **First Load** : < 2s
- ⚡ **Time to Interactive** : < 3s
- ⚡ **Lighthouse Score** : 95+
- ⚡ **Bundle Size** : ~200kb (gzipped ~60kb)

## 🔐 Sécurité

- ✅ HTTPS obligatoire
- ✅ JWT avec expiration
- ✅ RLS Supabase activé
- ✅ Protection CSRF
- ✅ Validation des entrées
- ✅ Sanitisation des données
- ✅ Protection SQL injection
- ✅ Rate limiting sur l'API

## 📝 Licence

Propriétaire - MV3 Pro © 2024

## 🤝 Support

Pour toute question ou problème :
- Email : support@mv-3pro.ch
- Documentation complète : Voir fichiers Markdown du projet

## 🎉 Crédits

Développé avec ❤️ pour MV3 Pro
Propulsé par React, TypeScript, Tailwind CSS, et Vite
