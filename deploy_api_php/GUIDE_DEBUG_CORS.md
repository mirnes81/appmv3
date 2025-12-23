# GUIDE DE DÉBOGAGE - PROBLÈME CORS

## Problème actuel

L'application ne peut pas se connecter à l'API car les requêtes sont bloquées par CORS:
```
Access to fetch at 'https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/login.php'
has been blocked by CORS policy
```

## Solutions

### SOLUTION 1: Ajouter le fichier .htaccess

Un fichier `.htaccess` a été créé dans `api_mobile/`. Assurez-vous qu'il est bien uploadé:

```bash
# Vérifier que le fichier existe
ssh user@crm.mv-3pro.ch
ls -la /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/.htaccess

# Si manquant, l'uploader
scp deploy_api_php/api_mobile/.htaccess user@crm.mv-3pro.ch:/var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile/
```

### SOLUTION 2: Vérifier que mod_headers est activé

```bash
# Sur le serveur
ssh user@crm.mv-3pro.ch
a2enmod headers
a2enmod rewrite
systemctl restart apache2
```

### SOLUTION 3: Tester l'API avec test.php

Un fichier de test a été créé pour vérifier que l'API fonctionne:

```bash
# Tester depuis la ligne de commande
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/test.php

# Ou ouvrir dans un navigateur
https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/test.php
```

**Réponse attendue:**
```json
{
    "status": "ok",
    "message": "API MV3 Pro Mobile fonctionne",
    "database": "connected",
    "active_users": 5
}
```

### SOLUTION 4: Ajouter les headers CORS dans Apache directement

Si le .htaccess ne fonctionne pas, éditez la configuration Apache:

```bash
ssh user@crm.mv-3pro.ch
nano /etc/apache2/sites-available/crm.mv-3pro.ch.conf
```

Ajoutez dans le VirtualHost:
```apache
<Directory /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
    Header always set Access-Control-Max-Age "3600"
</Directory>
```

Puis redémarrez Apache:
```bash
systemctl restart apache2
```

### SOLUTION 5: Vérifier la configuration PHP

```bash
# Vérifier que PHP peut envoyer des headers
php -i | grep "allow_url_fopen"
```

### SOLUTION 6: Test manuel de l'API

Testez l'API avec curl en incluant tous les headers:

```bash
curl -X OPTIONS \
  https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/login.php \
  -H "Origin: https://app.mv-3pro.ch" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  -v
```

Vous devriez voir dans la réponse:
```
< Access-Control-Allow-Origin: *
< Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
< Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
```

### SOLUTION 7: Tester le login

Une fois les headers CORS configurés, testez le login:

```bash
curl -X POST https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/login.php \
  -H "Content-Type: application/json" \
  -H "Origin: https://app.mv-3pro.ch" \
  -d '{"email":"votre@email.com","password":"votre_mdp"}' \
  -v
```

## Checklist de débogage

- [ ] Fichier .htaccess uploadé dans api_mobile/
- [ ] mod_headers activé dans Apache
- [ ] mod_rewrite activé dans Apache
- [ ] Apache redémarré après modifications
- [ ] test.php accessible et retourne "ok"
- [ ] curl -X OPTIONS fonctionne et retourne les headers CORS
- [ ] login.php répond avec les headers CORS
- [ ] L'application PWA peut se connecter

## Vérification rapide

```bash
# Tout vérifier en une commande
ssh user@crm.mv-3pro.ch "cd /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile && \
  ls -la .htaccess && \
  curl -I http://localhost/custom/mv3pro_portail/api_mobile/test.php"
```

## Si rien ne fonctionne

Envoyez-moi les résultats de:

```bash
# 1. Vérifier Apache
apachectl -M | grep headers
apachectl -M | grep rewrite

# 2. Tester l'API localement
curl -I http://localhost/custom/mv3pro_portail/api_mobile/test.php

# 3. Vérifier les logs
tail -50 /var/log/apache2/error.log
tail -50 /var/log/apache2/access.log | grep api_mobile
```
