# 🚀 Test et Déploiement - MV3 Pro

## ✅ Configuration validée

Votre API Dolibarr est **opérationnelle** :
- ✓ URL: https://crm.mv-3pro.ch/api/index.php
- ✓ CORS: Configuré correctement
- ✓ DOLAPIKEY: Fonctionnelle
- ✓ Données utilisateur: Accessibles

---

## 📋 Tests

### 1️⃣ Test automatique de l'API

Ouvrez dans votre navigateur :
```
http://localhost:5173/pro/test-api.html
```

Cette page va automatiquement :
- ✓ Vérifier la configuration
- ✓ Tester la connexion à l'API
- ✓ Récupérer vos données utilisateur

**Si tous les tests sont verts**, cliquez sur "Ouvrir l'application".

---

### 2️⃣ Test de l'application

1. Ouvrez : `http://localhost:5173/pro/`

2. Entrez votre DOLAPIKEY :
   ```
   04VxqqZ4fEi78j4tYVNqc18jQ0TWU1Wr
   ```

3. Cliquez sur **"Se connecter"**

4. Vous devriez voir le **Dashboard** avec :
   - Votre nom : Velagic MIRNES
   - Statistiques du jour
   - Menu de navigation

---

## 🔍 Diagnostic en cas de problème

Si la connexion échoue, cliquez sur **"Diagnostic API"** en bas de la page de login.

Le diagnostic vous montrera :
- ✓ Configuration actuelle
- ✓ Test de connexion au serveur
- ✓ Vérification CORS
- ✓ Messages d'erreur détaillés

---

## 📦 Structure du build

```
/pro/
├── index.html          ← Application principale
├── test-api.html       ← Page de test API
├── manifest.json       ← Configuration PWA
├── sw.js              ← Service Worker (mode hors-ligne)
├── .htaccess          ← Configuration Apache
└── assets/
    ├── index-*.css    ← Styles
    └── index-*.js     ← JavaScript compilé
```

---

## 🌐 Déploiement sur votre serveur

### Option 1 : FTP (Simple)

1. Connectez-vous à votre serveur FTP
2. Allez dans le dossier web de votre site
3. Créez un dossier `/app` (ou `/mobile`)
4. Uploadez tout le contenu de `/pro/` dans ce dossier
5. Accédez à : `https://votre-domaine.com/app/`

### Option 2 : SSH (Recommandé)

```bash
# Sur votre ordinateur
cd /tmp/cc-agent/59302460/project/pro
tar -czf mv3pro-app.tar.gz *

# Transférez sur le serveur
scp mv3pro-app.tar.gz user@votre-serveur.com:/tmp/

# Sur le serveur
ssh user@votre-serveur.com
cd /var/www/html
mkdir -p app
cd app
tar -xzf /tmp/mv3pro-app.tar.gz
chown -R www-data:www-data .
chmod -R 755 .
```

### Option 3 : Même domaine que Dolibarr

Si vous voulez installer sur `https://crm.mv-3pro.ch/app/` :

```bash
ssh user@crm.mv-3pro.ch
cd /var/www/html/dolibarr/htdocs
mkdir -p custom/mv3pro-app
cd custom/mv3pro-app

# Puis uploadez les fichiers du build ici
```

---

## 🔐 Configuration Apache (si nécessaire)

Si vous avez des problèmes d'accès, ajoutez ce fichier `.htaccess` :

```apache
# Déjà inclus dans /pro/.htaccess
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>

# HTTPS Redirect (optionnel)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Cache Control
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/html "access plus 0 seconds"
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

---

## 📱 Installation PWA (Progressive Web App)

L'application peut être installée comme une app native :

### Sur Android :
1. Ouvrez l'app dans Chrome
2. Cliquez sur le menu (3 points)
3. Sélectionnez "Installer l'application"
4. L'icône apparaîtra sur votre écran d'accueil

### Sur iOS :
1. Ouvrez l'app dans Safari
2. Cliquez sur le bouton Partager
3. Sélectionnez "Sur l'écran d'accueil"
4. Confirmez l'installation

---

## 🔄 Mode hors-ligne

L'application fonctionne hors-ligne après la première connexion :

1. **Première connexion** : Nécessite Internet
2. **Données en cache** : Rapports, photos, planning
3. **Synchronisation auto** : Quand Internet revient
4. **Indicateur de statut** : En haut de l'écran

---

## 🧪 Tests fonctionnels

### Checklist avant déploiement :

- [ ] Page de test API : Tous les tests verts
- [ ] Login : Connexion réussie avec DOLAPIKEY
- [ ] Dashboard : Affichage des statistiques
- [ ] Navigation : Tous les onglets accessibles
- [ ] Photos : Prise de photo fonctionnelle
- [ ] GPS : Localisation activée
- [ ] Mode hors-ligne : Fonctionne sans Internet
- [ ] Synchronisation : Upload des rapports

---

## 🐛 Problèmes courants

### ❌ "Impossible de contacter le serveur"
**Solution** : Vérifiez CORS sur Dolibarr (voir CORS_FIX_DOLIBARR.md)

### ❌ "DOLAPIKEY invalide"
**Solution** : Générez une nouvelle clé dans Dolibarr → Menu Utilisateur → Clé API

### ❌ "Cannot read properties of undefined"
**Solution** : Rechargez la page (CTRL+SHIFT+R)

### ❌ Page blanche
**Solution** : Ouvrez la console (F12) et vérifiez les erreurs JavaScript

### ❌ Erreur 404 après navigation
**Solution** : Vérifiez que le fichier `.htaccess` est présent

---

## 📊 Monitoring

### Logs Apache (serveur)
```bash
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log
```

### Console navigateur (client)
```
F12 → Console → Filtrer "API"
```

### Performance
```
F12 → Network → Vérifier temps de réponse API
```

---

## 🔄 Mise à jour

Pour mettre à jour l'application :

1. Modifiez le code source
2. Rebuild :
   ```bash
   npm run build
   ```
3. Copiez le nouveau build :
   ```bash
   cp -r dist/* /chemin/vers/serveur/app/
   ```
4. Videz le cache navigateur (CTRL+SHIFT+R)

---

## 🆘 Support

En cas de problème :

1. Consultez `CORS_FIX_DOLIBARR.md`
2. Testez avec `test-api.html`
3. Vérifiez la console du navigateur (F12)
4. Vérifiez les logs Apache
5. Ouvrez le "Diagnostic API" dans l'app

---

## ✅ Prêt pour la production !

Votre application est maintenant prête à être déployée. Suivez les étapes de déploiement ci-dessus et testez chaque fonctionnalité avant de la mettre à disposition de vos utilisateurs.

**URL de test actuelle** : http://localhost:5173/pro/
**URL de production** : https://votre-domaine.com/app/ (après déploiement)

Bonne chance ! 🚀
