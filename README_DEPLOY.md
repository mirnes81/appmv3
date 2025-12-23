# 🚀 GUIDE DE DÉPLOIEMENT MV3 PRO PWA

## 📋 Architecture

```
PWA (React)                    Dolibarr API REST
https://app.mv-3pro.ch/pro/    https://crm.mv-3pro.ch/api/
           ↓                              ↑
           └──────── Reverse Proxy ───────┘
                    (/api/ → crm)
```

## ✅ Prérequis

- ✅ Accès FTP à https://app.mv-3pro.ch/
- ✅ Accès à Dolibarr (crm.mv-3pro.ch)
- ✅ DOLAPIKEY générée dans Dolibarr
- ❌ PAS de backend Node
- ❌ PAS de Supabase
- ❌ PAS de MySQL externe

## 🎯 Étape 1 : Compilation de l'application

```bash
npm install
npm run build
```

Le dossier `dist/` contient l'application compilée.

## 📤 Étape 2 : Déploiement FTP

### Structure cible sur le serveur :
```
/app.mv-3pro.ch/pro/
├── index.html
├── .htaccess        ← IMPORTANT : Reverse proxy
├── manifest.json
├── sw.js
└── assets/
    ├── index-[hash].js
    ├── index-[hash].css
    └── ...
```

### Instructions FTP :

1. **Connectez-vous au FTP** : app.mv-3pro.ch
2. **Allez dans le dossier** : `/pro/`
3. **Copiez TOUT le contenu de `dist/`** dans `/pro/`
4. **Vérifiez que `.htaccess` est bien présent**

⚠️ **ATTENTION** : Le fichier `.htaccess` est OBLIGATOIRE pour le reverse proxy !

## 🔧 Étape 3 : Vérification du reverse proxy

Le fichier `.htaccess` redirige les appels `/api/*` vers `https://crm.mv-3pro.ch/api/*`

### Vérifier que le proxy fonctionne :

```bash
curl -H "DOLAPIKEY: votre_cle" https://app.mv-3pro.ch/api/index.php/users/info
```

Si ça fonctionne, vous devez voir les infos de votre utilisateur Dolibarr.

### Si le proxy ne fonctionne pas :

1. Vérifiez que `mod_rewrite` est activé :
   ```bash
   a2enmod rewrite
   a2enmod proxy
   a2enmod proxy_http
   a2enmod headers
   ```

2. Vérifiez la config Apache :
   ```apache
   <Directory /path/to/app.mv-3pro.ch/pro>
       AllowOverride All
   </Directory>
   ```

3. Redémarrez Apache :
   ```bash
   systemctl restart apache2
   ```

## 🔑 Étape 4 : Obtenir votre DOLAPIKEY

1. Connectez-vous à **https://crm.mv-3pro.ch/**
2. Cliquez sur **votre nom** en haut à droite
3. **"Modifier ma fiche utilisateur"**
4. Onglet **"Clé API"**
5. **"Générer une nouvelle clé"**
6. **Copiez la clé**

## 🎉 Étape 5 : Première connexion

1. Ouvrez **https://app.mv-3pro.ch/pro/**
2. Collez votre **DOLAPIKEY**
3. Cliquez sur **"Se connecter"**

✅ Vous êtes connecté !

## 📱 Fonctionnalités disponibles

### ✅ Authentification
- DOLAPIKEY uniquement (pas de JWT, pas d'email/mot de passe)
- Session stockée en localStorage
- Mode biométrique disponible

### ✅ Suivi des heures
- Boutons **▶️ Démarrer**, **⏸ Pause**, **▶️ Reprendre**, **⏹ Stop**
- Total journalier visible
- Historique des périodes
- Sauvegarde automatique en localStorage

### ✅ Mode offline
- IndexedDB pour stocker :
  - Rapports en brouillon
  - Photos non uploadées
  - Notes vocales
  - Cache des données
- Synchronisation automatique au retour en ligne
- Indicateur de statut réseau

### ✅ Gestion des rapports
- Création de fiches d'intervention
- Photos avec compression
- Notes vocales avec transcription
- Géolocalisation GPS
- Sauvegarde automatique toutes les 10 secondes

### ✅ Données Dolibarr
- **Interventions** → API Fichinter
- **Planning** → API Agenda
- **Utilisateurs** → API Users
- **Photos** → ECM Dolibarr
- **Heures** → Stocké en localStorage (pour l'instant)

## 🔄 Mise à jour de l'application

Pour mettre à jour l'application :

```bash
# 1. Compiler la nouvelle version
npm run build

# 2. FTP : Remplacer le contenu de /pro/ avec dist/
# ⚠️ NE PAS oublier le .htaccess !

# 3. Vider le cache du navigateur
# Ctrl + Shift + R (ou Cmd + Shift + R sur Mac)
```

## 🐛 Debug

### L'application ne charge pas
- Vérifiez que tous les fichiers sont bien uploadés
- Vérifiez les permissions (644 pour les fichiers, 755 pour les dossiers)
- Ouvrez la console du navigateur (F12) pour voir les erreurs

### Erreur CORS
- Vérifiez que `.htaccess` est présent
- Vérifiez que `mod_headers` est activé
- Vérifiez les logs Apache : `/var/log/apache2/error.log`

### Connexion échoue
- Vérifiez que la DOLAPIKEY est valide
- Testez directement l'API :
  ```bash
  curl -H "DOLAPIKEY: votre_cle" https://crm.mv-3pro.ch/api/index.php/users/info
  ```
- Ouvrez la console du navigateur pour voir l'erreur

### Le proxy ne fonctionne pas
- Vérifiez que `mod_rewrite` et `mod_proxy` sont activés
- Vérifiez la config Apache (AllowOverride All)
- Testez le proxy directement :
  ```bash
  curl https://app.mv-3pro.ch/api/index.php/status
  ```

## 📝 Fichiers importants

### `.htaccess` (public/.htaccess)
Gère le reverse proxy et le routing SPA. **NE PAS SUPPRIMER !**

### `.env`
```env
VITE_API_BASE=/api/index.php
VITE_DEFAULT_DOLIBARR_URL=https://crm.mv-3pro.ch
```

### `vite.config.ts`
Configuration du build. Pas besoin de modifier.

## 🔒 Sécurité

- ✅ HTTPS obligatoire (Let's Encrypt configuré)
- ✅ Authentification par DOLAPIKEY uniquement
- ✅ CORS configuré pour l'API
- ✅ Headers de sécurité (X-Content-Type-Options, X-Frame-Options, etc.)
- ✅ Service Worker pour le cache offline

## 📊 Monitoring

### Logs Apache
```bash
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log
```

### Console navigateur
- F12 → Onglet Console
- Affiche les erreurs JavaScript
- Affiche les requêtes réseau

### Application → Service Workers (F12)
- Vérifiez que le Service Worker est activé
- Vérifiez le cache offline

## 🎯 Checklist de déploiement

- [ ] `npm run build` exécuté sans erreurs
- [ ] Tous les fichiers de `dist/` copiés dans `/pro/`
- [ ] `.htaccess` présent et correctement configuré
- [ ] Test de l'application : https://app.mv-3pro.ch/pro/
- [ ] Test de connexion avec DOLAPIKEY
- [ ] Test du mode offline (désactiver le réseau)
- [ ] Test du suivi des heures (Start/Pause/Stop)
- [ ] Test de création d'un rapport
- [ ] Test d'ajout de photos

## 🆘 Support

En cas de problème :

1. **Consultez les logs Apache**
2. **Ouvrez la console du navigateur** (F12)
3. **Testez l'API directement** avec curl
4. **Vérifiez le fichier `.htaccess`**

## 🎉 Félicitations !

Votre PWA MV3 Pro est déployée et opérationnelle !

**URL de production** : https://app.mv-3pro.ch/pro/
