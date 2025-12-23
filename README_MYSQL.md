# MV3 Pro PWA - Mode MySQL

## ✅ Configuration terminée !

Votre application est maintenant configurée pour utiliser **MySQL** au lieu de Supabase.

## 📁 Fichiers importants

### SQL
- `sql_mysql_pwa.sql` - Script SQL à exécuter dans phpMyAdmin

### API PHP
- `api_pwa/config.php` - Configuration de la connexion MySQL
- `api_pwa/auth.php` - Authentification (login/logout)
- `api_pwa/reports.php` - Gestion des rapports
- `api_pwa/materiel.php` - Gestion du matériel
- `api_pwa/.htaccess` - Configuration Apache

### Documentation
- `INSTALLATION_MYSQL.md` - Guide complet d'installation pas-à-pas

## 🚀 Installation rapide

1. **Base de données** : Exécutez `sql_mysql_pwa.sql` dans phpMyAdmin
2. **API** : Copiez `api_pwa/` dans `/dolibarr/custom/mv3pro_portail/`
3. **Config** : Modifiez `.env` avec l'URL de votre API
4. **Build** : `npm run build`
5. **Deploy** : Copiez `dist/` sur votre serveur

## 🔐 Compte de test

- **Email** : test@mv3pro.com
- **Mot de passe** : test123

## 📖 Documentation

Consultez `INSTALLATION_MYSQL.md` pour le guide complet.

## 🎯 Architecture

```
Application React (PWA)
        ↓
   API PHP (api_pwa/)
        ↓
   MySQL (Dolibarr)
```

## 🔧 Configuration

### Fichier .env
```env
VITE_API_URL=https://votre-domaine.com/dolibarr/custom/mv3pro_portail/api_pwa
```

### Connexion MySQL (config.php)
L'API utilise automatiquement la configuration Dolibarr.

## ⚡ Fonctionnalités

- ✅ Authentification email/mot de passe
- ✅ Mode hors-ligne avec cache
- ✅ Auto-sauvegarde des brouillons
- ✅ Synchronisation intelligente
- ✅ Photos avec compression
- ✅ Notes vocales
- ✅ Géolocalisation GPS
- ✅ PWA installable

## 🆘 Support

Problèmes courants :

**Erreur CORS** :
- Vérifiez le fichier `.htaccess`
- Activez `mod_headers` dans Apache

**Connexion échoue** :
- Vérifiez les logs PHP
- Testez `/api_pwa/auth.php?action=verify`
- Vérifiez les identifiants MySQL dans `config.php`

**Build échoue** :
- `npm install`
- Supprimez `node_modules/` et réinstallez
