# DÉPLOIEMENT COMPLET MV3 PRO MOBILE

## Vue d'ensemble

Deux éléments à déployer:

1. **Application PWA Mobile** → `https://app.mv-3pro.ch/pro/`
2. **API Backend PHP** → `https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/`

---

## 📦 FICHIERS À DÉPLOYER

### 1. pwa_pro_deploy.tar.gz (68 KB)
- Contenu: Application React PWA complète
- Destination: `/var/www/html/pro/` sur app.mv-3pro.ch
- URL finale: https://app.mv-3pro.ch/pro/

### 2. api_mobile_deploy.tar.gz (8.7 KB)
- Contenu: API PHP pour Dolibarr
- Destination: `/var/www/dolibarr/htdocs/custom/mv3pro_portail/` sur crm.mv-3pro.ch
- URL finale: https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/

---

## 🚀 DÉPLOIEMENT RAPIDE

### ÉTAPE 1: Déployer l'application PWA

```bash
# 1. Upload de l'archive PWA
scp pwa_pro_deploy.tar.gz user@app.mv-3pro.ch:/var/www/html/

# 2. Extraction sur le serveur
ssh user@app.mv-3pro.ch "cd /var/www/html && tar -xzf pwa_pro_deploy.tar.gz"

# 3. Permissions
ssh user@app.mv-3pro.ch "chmod -R 755 /var/www/html/pro && chown -R www-data:www-data /var/www/html/pro"

# 4. Test
curl -I https://app.mv-3pro.ch/pro/
```

### ÉTAPE 2: Déployer l'API

```bash
# 1. Upload de l'archive API
scp api_mobile_deploy.tar.gz user@crm.mv-3pro.ch:/var/www/dolibarr/htdocs/custom/mv3pro_portail/

# 2. Extraction sur le serveur
ssh user@crm.mv-3pro.ch "cd /var/www/dolibarr/htdocs/custom/mv3pro_portail && tar -xzf api_mobile_deploy.tar.gz && mv deploy_api_php/api_mobile ."

# 3. Configuration
ssh user@crm.mv-3pro.ch
nano /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/config.php
# Modifier: DB_HOST, DB_NAME, DB_USER, DB_PASS, JWT_SECRET

# 4. Permissions
chmod -R 755 /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile
chmod 644 /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/*.php

# 5. Test
curl -X POST https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"votre@email.com","password":"votremdp"}'
```

---

## 🔐 CONNEXION À L'APPLICATION

### URL: https://app.mv-3pro.ch/pro/

**Identifiants**: Utilisez vos identifiants Dolibarr
- Email: Votre email dans Dolibarr
- Mot de passe: Votre mot de passe Dolibarr

**Vérification préalable dans Dolibarr:**
1. Connectez-vous à https://crm.mv-3pro.ch
2. Vérifiez que votre email est renseigné dans votre profil
3. Vérifiez que votre compte est actif (statut = 1)

---

## 📋 CHECKLIST DE DÉPLOIEMENT

### PWA Mobile (app.mv-3pro.ch)
- [ ] Archive uploadée et extraite dans /var/www/html/pro/
- [ ] Permissions configurées (755 pour dossiers, 644 pour fichiers)
- [ ] Fichier .htaccess présent
- [ ] HTTPS fonctionnel
- [ ] Test: Page accessible sur https://app.mv-3pro.ch/pro/

### API Backend (crm.mv-3pro.ch)
- [ ] Archive uploadée et extraite dans /custom/mv3pro_portail/api_mobile/
- [ ] config.php configuré avec les bons paramètres MySQL
- [ ] JWT_SECRET généré et configuré
- [ ] Permissions configurées
- [ ] Test curl fonctionne (login endpoint)

### Base de données
- [ ] Tables llx_mv3_* existent dans Dolibarr
- [ ] Connexion MySQL testée
- [ ] Utilisateurs Dolibarr actifs avec emails

### Tests finaux
- [ ] Login fonctionne depuis l'app PWA
- [ ] Dashboard s'affiche après connexion
- [ ] Aucune erreur CORS
- [ ] Service Worker enregistré (vérifier dans DevTools)

---

## 🆘 DÉPANNAGE RAPIDE

### Erreur 404 sur la PWA
→ Vérifiez que le dossier `pro/` existe dans /var/www/html/
→ Vérifiez le .htaccess

### Erreur "Database connection failed"
→ Vérifiez config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS)
→ Testez: `mysql -u user -p -h localhost nom_base`

### Erreur "Invalid credentials"
→ Vérifiez que l'utilisateur existe dans Dolibarr
→ Vérifiez que l'email est renseigné dans Dolibarr
→ Vérifiez que le statut est = 1 (actif)

### Erreur CORS
→ Vérifiez que config.php contient les headers CORS
→ Vérifiez qu'Apache ne bloque pas les headers

---

## 📁 STRUCTURE FINALE

```
app.mv-3pro.ch
└── /var/www/html/pro/
    ├── index.html
    ├── manifest.json
    ├── sw.js
    ├── .htaccess
    └── assets/
        ├── index-*.js
        └── index-*.css

crm.mv-3pro.ch
└── /var/www/dolibarr/htdocs/custom/mv3pro_portail/
    └── api_mobile/
        ├── config.php
        ├── auth/
        │   ├── login.php
        │   ├── logout.php
        │   └── verify.php
        ├── reports/
        ├── dashboard/
        └── weather/
```

---

## 📖 DOCUMENTATION COMPLÈTE

- `deploy_api_php/GUIDE_INSTALLATION_API.md` - Installation détaillée de l'API
- `deploy_api_php/GUIDE_CONNEXION_SIMPLE.md` - Guide de connexion pour les utilisateurs
- `pro/README_INSTALLATION.txt` - Installation de la PWA

---

## ✅ COMMANDES RAPIDES

```bash
# Tout déployer en une fois (si vous avez les accès SSH)

# 1. PWA
scp pwa_pro_deploy.tar.gz user@app.mv-3pro.ch:/var/www/html/ && \
ssh user@app.mv-3pro.ch "cd /var/www/html && tar -xzf pwa_pro_deploy.tar.gz && chmod -R 755 pro"

# 2. API
scp api_mobile_deploy.tar.gz user@crm.mv-3pro.ch:/tmp/ && \
ssh user@crm.mv-3pro.ch "cd /var/www/dolibarr/htdocs/custom/mv3pro_portail && tar -xzf /tmp/api_mobile_deploy.tar.gz && mv deploy_api_php/api_mobile . && chmod -R 755 api_mobile"
```

**N'oubliez pas de configurer config.php après !**
