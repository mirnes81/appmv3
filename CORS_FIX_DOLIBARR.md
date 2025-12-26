# 🔧 Correction CORS pour Dolibarr

## Problème
L'application ne peut pas se connecter à l'API Dolibarr à cause de la politique CORS du navigateur.

**Erreur typique dans la console (F12)** :
```
Access to fetch at 'https://crm.mv-3pro.ch/api/index.php/users/info' from origin 'http://localhost:5173'
has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

---

## Solution 1 : Modification du fichier PHP (RECOMMANDÉ)

### 1. Connexion SSH au serveur
```bash
ssh votre_utilisateur@crm.mv-3pro.ch
```

### 2. Éditer le fichier API
```bash
nano /var/www/html/dolibarr/htdocs/api/index.php
```

### 3. Ajouter CORS au début du fichier

**Ajoutez ces lignes juste après `<?php` :**

```php
<?php
// ============================================
// CORS Configuration pour MV3 Pro Mobile App
// ============================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, DOLAPIKEY, Accept, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Gestion de la requête OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ... reste du code existant ...
```

### 4. Sauvegarder et quitter
```
CTRL+O (sauvegarder)
ENTER (confirmer)
CTRL+X (quitter)
```

---

## Solution 2 : Fichier .htaccess

Si vous préférez ne pas modifier le code PHP, créez un fichier `.htaccess` dans le dossier API.

### 1. Créer le fichier
```bash
nano /var/www/html/dolibarr/htdocs/api/.htaccess
```

### 2. Ajouter ce contenu
```apache
# CORS Headers pour MV3 Pro Mobile App
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, DOLAPIKEY, Accept, Authorization, X-Requested-With"
    Header always set Access-Control-Max-Age "86400"

    # Handle preflight OPTIONS requests
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>
```

### 3. Vérifier que mod_headers est activé
```bash
sudo a2enmod headers
sudo systemctl restart apache2
```

---

## Solution 3 : Configuration Apache globale

Pour une configuration plus propre, éditez la configuration du VirtualHost.

### 1. Éditer le VirtualHost
```bash
sudo nano /etc/apache2/sites-available/crm.mv-3pro.ch.conf
```

### 2. Ajouter dans la section <VirtualHost>
```apache
<VirtualHost *:443>
    ServerName crm.mv-3pro.ch

    # ... votre configuration existante ...

    # CORS pour l'API
    <Directory /var/www/html/dolibarr/htdocs/api>
        Header always set Access-Control-Allow-Origin "*"
        Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
        Header always set Access-Control-Allow-Headers "Content-Type, DOLAPIKEY, Accept, Authorization, X-Requested-With"
        Header always set Access-Control-Max-Age "86400"
    </Directory>
</VirtualHost>
```

### 3. Redémarrer Apache
```bash
sudo apache2ctl configtest
sudo systemctl restart apache2
```

---

## Solution 4 : Nginx (si vous utilisez Nginx)

### 1. Éditer la configuration
```bash
sudo nano /etc/nginx/sites-available/crm.mv-3pro.ch
```

### 2. Ajouter dans le bloc location
```nginx
server {
    listen 443 ssl;
    server_name crm.mv-3pro.ch;

    location /api {
        # CORS headers
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'Content-Type, DOLAPIKEY, Accept, Authorization, X-Requested-With' always;
        add_header 'Access-Control-Max-Age' '86400' always;

        # Handle preflight
        if ($request_method = 'OPTIONS') {
            return 200;
        }

        # ... reste de votre config ...
    }
}
```

### 3. Redémarrer Nginx
```bash
sudo nginx -t
sudo systemctl restart nginx
```

---

## Vérification CORS

### Test 1 : Curl avec headers CORS
```bash
curl -I https://crm.mv-3pro.ch/api/index.php/users/info \
  -H "Origin: http://localhost:5173" \
  -H "DOLAPIKEY: 04VxqqZ4fEi78j4tYVNqc18jQ0TWU1Wr"
```

**Résultat attendu :**
```
HTTP/2 200
access-control-allow-origin: *
access-control-allow-methods: GET, POST, PUT, DELETE, OPTIONS
access-control-allow-headers: Content-Type, DOLAPIKEY, Accept, Authorization, X-Requested-With
```

### Test 2 : Console navigateur
1. Ouvrir http://localhost:5173/pro/
2. Ouvrir la console (F12)
3. Essayer de se connecter
4. Vérifier qu'il n'y a plus d'erreur CORS

---

## Configuration de sécurité CORS (Production)

⚠️ **Important** : `Access-Control-Allow-Origin: *` autorise toutes les origines.

Pour la **production**, limitez l'accès aux domaines autorisés :

```php
<?php
$allowed_origins = [
    'http://localhost:5173',
    'https://app.mv-3pro.ch',
    'https://mobile.mv-3pro.ch'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: https://app.mv-3pro.ch');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, DOLAPIKEY, Accept, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

---

## Dépannage

### Erreur : "mod_headers not found"
```bash
sudo a2enmod headers
sudo systemctl restart apache2
```

### Erreur : "Configuration invalide"
```bash
# Vérifier la syntaxe
sudo apache2ctl configtest

# Voir les logs
sudo tail -f /var/log/apache2/error.log
```

### CORS toujours bloqué
1. Vider le cache du navigateur (CTRL+SHIFT+DEL)
2. Essayer en navigation privée
3. Vérifier les logs : `/var/log/apache2/error.log`
4. Vérifier que les headers sont bien envoyés avec les outils de dev (F12 → Network)

---

## Alternative : Proxy de développement

Si vous ne voulez pas modifier Dolibarr, utilisez un proxy dans Vite :

### Éditer `vite.config.ts`
```typescript
export default defineConfig({
  server: {
    proxy: {
      '/api': {
        target: 'https://crm.mv-3pro.ch',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path
      }
    }
  }
})
```

### Puis dans `.env`
```bash
VITE_API_BASE=/api/index.php
```

Cette méthode fonctionne **uniquement en développement** (localhost). En production, vous devrez quand même configurer CORS.

---

## Vérification finale

Une fois CORS configuré, testez :

1. ✅ Rechargez http://localhost:5173/pro/
2. ✅ Entrez votre DOLAPIKEY
3. ✅ Cliquez "Se connecter"
4. ✅ Vous devriez voir le Dashboard

---

**Support** : Si le problème persiste, envoyez-moi :
- Les logs Apache/Nginx
- La sortie de la commande curl de test
- Une capture d'écran de la console (F12)
