# 🔍 Diagnostic Production Hoststar - MV3 PRO PWA

## ✅ Informations collectées

- **URL production** : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
- **Hébergeur** : Hoststar Suisse
- **Dolibarr** : 21.0
- **Serveur** : Apache (probablement avec NGINX en proxy reverse)
- **PHP** : À confirmer (7.4/8.0/8.1)
- **Cloudflare** : NON
- **WAF** : NON

---

## 🚨 Problèmes identifiés et correctifs

### **PROBLÈME 1 : Routing SPA cassé (refresh/accès direct)**

**Symptôme** :
- Refresh sur `/pwa_dist/#/dashboard` → 404
- Accès direct à une route → 404
- Seul `/pwa_dist/` (page login) fonctionne

**Cause** :
- **Manque le fichier `.htaccess` dans `pwa_dist/`**
- Apache ne sait pas rediriger toutes les routes vers `index.html`

**Solution** :
- Créer `/custom/mv3pro_portail/pwa_dist/.htaccess`
- Voir fichier `FIX_1_htaccess_pwa_dist.txt`

---

### **PROBLÈME 2 : CORS bloque X-Auth-Token**

**Symptôme** :
- Erreurs 500/510 sur certains appels API
- Console navigateur : "CORS error" ou "Preflight failed"
- Token envoyé mais rejeté

**Cause** :
- `cors_config.php` ne liste PAS `X-Auth-Token` dans `Access-Control-Allow-Headers`
- Le navigateur envoie le header mais le serveur le refuse en preflight

**Solution** :
- Corriger `/custom/mv3pro_portail/api/cors_config.php`
- Voir fichier `FIX_2_cors_config.php`

---

### **PROBLÈME 3 : Authorization header bloqué par NGINX**

**Symptôme** :
- Token Bearer fonctionne en dev mais pas en prod
- `$_SERVER['HTTP_AUTHORIZATION']` est vide

**Cause** :
- NGINX (proxy reverse devant Apache) supprime `Authorization` header par défaut
- Hoststar Suisse utilise souvent NGINX → Apache

**Solution** :
- L'API utilise déjà `X-Auth-Token` en PRIORITÉ (ligne 221 de `_bootstrap.php`)
- S'assurer que le client PWA envoie **les deux headers** :
  - `Authorization: Bearer <token>`
  - `X-Auth-Token: <token>`
- **Déjà implémenté** dans `api.ts` (lignes 67-68) ✅

---

### **PROBLÈME 4 : Service Worker cache de vieilles versions**

**Symptôme** :
- Modifications du code ne s'affichent pas
- Ancienne version de l'app reste chargée
- Logs console : "Service worker found in cache"

**Cause** :
- Le Service Worker (Workbox) cache agressivement les assets
- Pas de mécanisme de cache-busting

**Solution** :
- Forcer le nettoyage du cache navigateur :
  - F12 → Application → Clear Storage → Clear site data
  - Ou CTRL+SHIFT+DEL → Tout supprimer

- Ajouter un système de versioning :
  - Modifier `manifest.webmanifest` avec un numéro de version
  - Rebuild complet : `npm run build`

---

## 📋 Checklist de déploiement

### Étape 1 : Vérifier la config serveur

**1. Hoststar Panel → Site Info**
```
Serveur web : Apache ou NGINX ?
Version PHP : 7.4 / 8.0 / 8.1 ?
```

**2. Tester PHP info**
- Créer `/custom/mv3pro_portail/phpinfo.php` :
```php
<?php phpinfo(); ?>
```
- Ouvrir : `https://crm.mv-3pro.ch/custom/mv3pro_portail/phpinfo.php`
- Noter : Version PHP + `apache_get_modules` + `$_SERVER['HTTP_AUTHORIZATION']`
- **SUPPRIMER le fichier après test (sécurité)**

---

### Étape 2 : Appliquer les correctifs

**1. Créer `.htaccess` dans `pwa_dist/`**
```bash
# Via FTP ou SSH
cd /path/to/custom/mv3pro_portail/pwa_dist/
nano .htaccess
```

Coller le contenu de `FIX_1_htaccess_pwa_dist.txt`

**2. Corriger `cors_config.php`**
```bash
cd /path/to/custom/mv3pro_portail/api/
nano cors_config.php
```

Remplacer la ligne 43 par :
```php
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-MV3-Debug, X-Client-Info, Apikey');
```

**3. Vider le cache navigateur**
- F12 → Application → Clear Storage → Clear site data
- CTRL+SHIFT+DEL → Tout supprimer

**4. Rebuild la PWA (si modifications code)**
```bash
cd /path/to/new_dolibarr/mv3pro_portail/pwa
npm run build
```

---

### Étape 3 : Tester les endpoints API

**Test direct dans le navigateur** :

1. **Login d'abord** :
   - Ouvrir : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
   - Se connecter
   - F12 → Application → Local Storage → Copier la valeur de `mv3pro_token`

2. **Tester /me.php** :
```bash
curl -H "X-Auth-Token: VOTRE_TOKEN_ICI" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/me.php
```

Attendu :
```json
{"success":true,"user":{"id":1,"email":"...","name":"..."}}
```

3. **Tester /planning.php** :
```bash
curl -H "X-Auth-Token: VOTRE_TOKEN_ICI" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning.php
```

4. **Tester /rapports.php** :
```bash
curl -H "X-Auth-Token: VOTRE_TOKEN_ICI" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports.php
```

---

### Étape 4 : Activer le mode debug

**Dans la PWA** :
1. Ouvrir : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug`
2. Activer "Mode Debug"
3. Revenir au Dashboard
4. F12 → Console
5. Copier les logs `[MV3PRO DEBUG]`

**Dans l'API** :
1. Ajouter le header `X-MV3-Debug: 1` dans les requêtes
2. Consulter les logs PHP (voir Étape 5)

---

### Étape 5 : Consulter les logs serveur

**Hoststar Panel → Logs**

Si SSH disponible :
```bash
# Apache error log
tail -f /var/log/apache2/error.log

# PHP error log
tail -f /var/log/php/error.log

# Logs Dolibarr
tail -f /path/to/dolibarr/documents/dolibarr.log
```

**Chercher** :
- `[MV3 API]` → Logs de l'API
- `PHP Fatal error` → Erreurs PHP
- `404` ou `500` → Erreurs HTTP

---

## 🧪 Test complet des pages

| Page | URL | Test | Résultat attendu |
|------|-----|------|------------------|
| Login | `/pwa_dist/` | Ouvrir | Formulaire login |
| Dashboard | `/pwa_dist/#/dashboard` | Refresh | Dashboard sans 404 |
| Planning | `/pwa_dist/#/planning` | Accès direct | Liste planning |
| Rapports | `/pwa_dist/#/rapports` | Accès direct | Liste rapports |
| Matériel | `/pwa_dist/#/materiel` | Accès direct | 501 "Non implémenté" |
| Notifications | `/pwa_dist/#/notifications` | Accès direct | 501 "Non implémenté" |
| Profil | `/pwa_dist/#/profil` | Accès direct | Infos user |
| Régie | `/pwa_dist/#/regie` | Accès direct | 501 "Non implémenté" |
| Sens de pose | `/pwa_dist/#/sens-pose` | Accès direct | 501 "Non implémenté" |

---

## 🔧 Commandes utiles Hoststar

**Via FTP (FileZilla)** :
- Serveur : `ftp.votredomaine.ch`
- Port : 21
- Protocole : FTP ou SFTP

**Via SSH (si disponible)** :
```bash
ssh user@votredomaine.ch
cd /path/to/custom/mv3pro_portail/
ls -la
```

**Test PHP version** :
```bash
php -v
```

**Test permissions** :
```bash
ls -la pwa_dist/
# .htaccess doit être lisible : -rw-r--r--
```

---

## 📞 Support

Si problème persiste après ces correctifs :

1. **Capturer les logs** :
   - Console navigateur (F12)
   - Network (requêtes en erreur)
   - Logs serveur Apache/PHP

2. **Me transmettre** :
   - URL exacte qui casse
   - Code HTTP (404/500/510)
   - Message d'erreur complet
   - Logs PHP si accessibles

3. **Vérifier permissions** :
   - `.htaccess` → 644 (-rw-r--r--)
   - `index.html` → 644
   - Dossiers → 755 (drwxr-xr-x)
