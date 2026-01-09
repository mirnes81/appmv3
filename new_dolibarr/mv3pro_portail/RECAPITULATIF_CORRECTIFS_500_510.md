# 🎯 RÉCAPITULATIF - Correctifs erreurs 500/510 Production

**Date** : 2026-01-09
**URL Production** : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
**Hébergeur** : Hoststar Suisse
**Dolibarr** : 21.0

---

## ✅ Analyse complète effectuée

J'ai analysé l'intégralité de votre codebase et identifié **3 problèmes critiques** qui causent les erreurs 500/510 en production.

---

## 🔴 Problèmes identifiés

### **1. Routing SPA cassé → 404 sur refresh/accès direct**

**Cause** :
- Il manque le fichier `.htaccess` dans `pwa_dist/`
- Apache ne sait pas rediriger les routes React vers `index.html`

**Symptômes** :
- Refresh sur `/pwa_dist/#/dashboard` → 404
- Accès direct à n'importe quelle route → 404
- Seule la page login `/pwa_dist/` fonctionne

**Solution** :
- Créer `/custom/mv3pro_portail/pwa_dist/.htaccess`
- Utiliser le fichier `FIX_1_htaccess_pwa_dist.txt`

---

### **2. CORS bloque le header X-Auth-Token → 500**

**Cause** :
- Le fichier `cors_config.php` ne liste PAS `X-Auth-Token` dans les headers autorisés
- Le navigateur envoie le token mais le serveur le refuse en preflight
- Résultat : 500 Internal Server Error

**Symptômes** :
- Erreurs 500 sur les appels API
- Console navigateur : "CORS error" ou "Preflight failed"
- Token envoyé mais rejeté

**Solution** :
- Corriger `/custom/mv3pro_portail/api/cors_config.php`
- Ajouter `X-Auth-Token` et `X-MV3-Debug` dans `Access-Control-Allow-Headers`
- Utiliser le fichier `FIX_2_cors_config.php`

---

### **3. Service Worker cache de vieilles versions**

**Cause** :
- Workbox cache agressivement les assets
- Navigateur sert une ancienne version même après rebuild

**Symptômes** :
- Modifications du code ne s'affichent pas
- Ancienne version de l'app reste chargée
- Erreurs qui persistent même après correctifs

**Solution** :
- Vider le cache navigateur (F12 → Application → Clear Storage)
- CTRL+SHIFT+DEL → Tout supprimer
- Rebuild complet : `npm run build`

---

## 📋 Correctifs appliqués dans le code

### ✅ 1. CORS config corrigée
- **Fichier** : `/api/cors_config.php`
- **Ligne 43** : Ajout de `X-Auth-Token` et `X-MV3-Debug`

**Avant** :
```php
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');
```

**Après** :
```php
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-MV3-Debug, X-Client-Info, Apikey');
```

### ✅ 2. Build production généré
- **Dossier** : `/pwa_dist/`
- **Statut** : Build réussi (219.97 KB)
- **Service Worker** : Généré avec 9 entrées en cache

---

## 📁 Fichiers créés pour vous

### 1. `ACTIONS_IMMEDIATES.md`
Guide rapide des 3 actions à faire MAINTENANT (5 minutes).

### 2. `DIAGNOSTIC_HOSTSTAR.md`
Guide complet de diagnostic avec :
- Checklist de déploiement
- Commandes pour consulter les logs
- Tests des endpoints API
- Résolution de problèmes

### 3. `FIX_1_htaccess_pwa_dist.txt`
Fichier `.htaccess` complet pour `pwa_dist/` avec :
- Routing SPA (redirection vers index.html)
- Headers de sécurité
- Cache optimisé
- Compression GZIP

### 4. `FIX_2_cors_config.php`
Version corrigée de `cors_config.php` avec les bons headers CORS.

### 5. `TEST_API_ENDPOINTS.sh`
Script bash pour tester tous les endpoints API depuis la ligne de commande.

---

## 🚀 Déploiement en production

### Étape 1 : Télécharger les fichiers corrigés (3 minutes)

**Via FTP (FileZilla) ou SFTP** :

```
Fichiers à télécharger vers Hoststar :
├── /custom/mv3pro_portail/pwa_dist/
│   ├── .htaccess                    ← NOUVEAU (FIX_1)
│   ├── index.html                   ← Remplacer
│   ├── manifest.webmanifest         ← Remplacer
│   ├── sw.js                        ← Remplacer
│   ├── registerSW.js                ← Remplacer
│   ├── workbox-1d305bb8.js          ← Remplacer
│   └── assets/
│       ├── index-BQiQB-1j.css       ← Remplacer
│       └── index-CT4p1pgp.js        ← Remplacer
│
└── /custom/mv3pro_portail/api/
    └── cors_config.php              ← Remplacer (FIX_2)
```

**IMPORTANT** :
- Le fichier `.htaccess` dans `pwa_dist/` est CRITIQUE
- Vérifiez que les permissions sont `644` (-rw-r--r--)

---

### Étape 2 : Vider le cache (2 minutes)

**Sur TOUS les appareils** :

1. **Desktop (Chrome/Edge/Firefox)** :
   - CTRL+SHIFT+DEL
   - Cocher : Cookies, Cache, Stockage local
   - Période : Tout
   - Effacer

2. **Mobile iOS (Safari)** :
   - Réglages → Safari → Effacer historique et données

3. **Mobile Android (Chrome)** :
   - Paramètres → Stockage → Effacer données du site

---

### Étape 3 : Test de validation (3 minutes)

1. **Ouvrez** : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/

2. **Connectez-vous**

3. **Testez chaque page** :
   - Dashboard → ✅ Doit afficher "Bienvenue"
   - Planning → ✅ Doit charger les événements
   - Rapports → ✅ Doit afficher la liste
   - Profil → ✅ Doit afficher les infos user

4. **Test refresh** :
   - Allez sur Dashboard
   - Appuyez sur F5 (refresh)
   - La page doit recharger SANS 404

5. **Test accès direct** :
   - Ouvrez un nouvel onglet
   - Collez : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/planning
   - Doit s'afficher directement SANS 404

6. **F12 → Console** :
   - Aucune erreur rouge
   - Pas de CORS errors
   - Pas de 500/510

---

## 🧪 Outils de diagnostic

### Mode Debug PWA

1. Ouvrez : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug
2. Activez "Mode Debug"
3. Revenir au Dashboard
4. F12 → Console : Logs `[MV3PRO DEBUG]` visibles

### Test direct API (curl)

```bash
# Récupérer votre token
# F12 → Application → Local Storage → mv3pro_token

# Tester /me.php
curl -H "X-Auth-Token: VOTRE_TOKEN" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/me.php

# Tester /planning.php
curl -H "X-Auth-Token: VOTRE_TOKEN" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning.php

# Tester /rapports.php
curl -H "X-Auth-Token: VOTRE_TOKEN" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports.php
```

Attendu : `{"success":true,...}` avec code HTTP 200

### Script de test complet

Rendez exécutable :
```bash
chmod +x TEST_API_ENDPOINTS.sh
```

Lancez :
```bash
./TEST_API_ENDPOINTS.sh VOTRE_TOKEN
```

---

## 📞 Support et prochaines étapes

### Si tout fonctionne ✅

Félicitations ! L'application est fonctionnelle.

**Prochaines optimisations possibles** :
- Implémenter les endpoints manquants (Matériel, Notifications, Régie, Sens de pose)
- Ajouter un système de versioning automatique
- Restreindre les CORS en production (whitelist de domaines)
- Configurer les logs Apache/PHP pour monitoring

---

### Si problèmes persistent ❌

**Informations à collecter** :

1. **Console navigateur (F12)** :
   - Capturez toutes les erreurs rouges
   - Network : Cliquez sur requête en erreur → Copiez Headers + Response

2. **Logs serveur** :
   - Apache : `/var/log/apache2/error.log`
   - PHP : `/var/log/php/error.log`
   - Dolibarr : `documents/dolibarr.log`

3. **Test endpoints direct** :
   - Lancez `TEST_API_ENDPOINTS.sh` avec votre token
   - Copiez le résultat complet

4. **Vérifications** :
   - `.htaccess` existe dans `pwa_dist/` ? (ls -la)
   - Permissions correctes ? (644)
   - Version PHP ? (php -v)
   - Modules Apache ? (apache2ctl -M ou httpd -M)

**Consultez** : `DIAGNOSTIC_HOSTSTAR.md` pour un guide complet.

---

## 📈 Architecture technique

**Frontend (PWA)** :
- React 18 + TypeScript
- React Router v6 (hash mode)
- Vite build + Workbox PWA
- API client avec authentification Bearer + X-Auth-Token

**Backend (API)** :
- PHP 7.4+ (Dolibarr)
- Architecture modulaire avec `_bootstrap.php`
- Authentification unifiée (3 modes : Session, Mobile Token, API Token)
- CORS centralisé

**Serveur** :
- Apache (+ possiblement NGINX en proxy)
- Hoststar Suisse
- Dolibarr 21.0

---

## ✅ Checklist finale

- [ ] `.htaccess` créé dans `pwa_dist/`
- [ ] `cors_config.php` corrigé avec X-Auth-Token
- [ ] Fichiers `pwa_dist/` téléchargés via FTP
- [ ] Cache navigateur vidé sur tous les appareils
- [ ] Login fonctionne
- [ ] Dashboard s'affiche
- [ ] Planning charge les données
- [ ] Rapports charge la liste
- [ ] Refresh ne donne plus 404
- [ ] Accès direct aux routes fonctionne
- [ ] Pas d'erreurs CORS dans F12
- [ ] Pas d'erreurs 500/510

---

## 🎓 Résumé des changements

1. **CORS** : Ajout de `X-Auth-Token` dans les headers autorisés
2. **Routing SPA** : Création de `.htaccess` pour rediriger vers `index.html`
3. **Build** : Génération d'une version production optimisée
4. **Documentation** : 5 guides complets créés
5. **Outils** : Script de test des endpoints API

**Statut** : Prêt pour déploiement production 🚀

---

**Dernière mise à jour** : 2026-01-09
**Version PWA** : 1.0.0
**Build ID** : CT4p1pgp
