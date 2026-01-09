# 🔧 Diagnostic et Installation MV3 PRO Mobile PWA

## ✅ Ce qui fonctionne déjà

- ✅ **PWA buildée avec succès** - Les fichiers sont dans `pwa_dist/`
- ✅ **Code TypeScript compilé** - Aucune erreur TypeScript
- ✅ **Fichiers API PHP présents** - auth.php et me.php existent
- ✅ **Structure SQL définie** - Tables pour authentification mobile

---

## ❌ Ce qui doit être configuré sur votre serveur Dolibarr

### 1️⃣ **Tables SQL manquantes**

Les tables d'authentification mobile doivent être créées dans votre base de données:

```sql
-- Exécutez ce fichier dans votre base Dolibarr:
custom/mv3pro_portail/sql/llx_mv3_mobile_users.sql
```

**Commande rapide:**
```bash
mysql -u VOTRE_USER -p VOTRE_DATABASE < /path/to/dolibarr/htdocs/custom/mv3pro_portail/sql/llx_mv3_mobile_users.sql
```

Les tables créées:
- `llx_mv3_mobile_users` - Utilisateurs mobiles
- `llx_mv3_mobile_sessions` - Sessions/tokens
- `llx_mv3_mobile_login_history` - Historique connexions

---

### 2️⃣ **Copier les fichiers PWA sur le serveur**

Copiez le contenu de `pwa_dist/` vers votre serveur Dolibarr:

```bash
# Sur votre serveur Dolibarr
cd /var/www/html/dolibarr/htdocs/custom/mv3pro_portail/

# Créez le dossier si nécessaire
mkdir -p pwa_dist

# Copiez les fichiers (depuis votre machine locale)
scp -r pwa_dist/* user@serveur:/var/www/html/dolibarr/htdocs/custom/mv3pro_portail/pwa_dist/
```

**Ou via FTP/SFTP** si vous préférez.

---

### 3️⃣ **Créer un utilisateur mobile de test**

Une fois les tables créées, vous devez créer un utilisateur mobile:

```sql
-- Créer un utilisateur de test
-- Mot de passe: test123 (hash bcrypt)
INSERT INTO llx_mv3_mobile_users
(email, password_hash, firstname, lastname, role, is_active)
VALUES
('test@example.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Test',
 'User',
 'employee',
 1);
```

**OU créez le hash du mot de passe via PHP:**

```php
<?php
// Créez ce fichier temporaire: create_user.php
require_once 'main.inc.php';

$email = 'votre.email@example.com';
$password = 'VotreMotDePasse123';
$firstname = 'Prénom';
$lastname = 'Nom';

$hash = password_hash($password, PASSWORD_BCRYPT);

$sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_mobile_users";
$sql .= " (email, password_hash, firstname, lastname, role, is_active)";
$sql .= " VALUES ('".$db->escape($email)."', '".$hash."', ";
$sql .= " '".$db->escape($firstname)."', '".$db->escape($lastname)."', ";
$sql .= " 'employee', 1)";

if ($db->query($sql)) {
    echo "✅ Utilisateur créé avec succès!\n";
    echo "Email: $email\n";
    echo "Mot de passe: $password\n";
} else {
    echo "❌ Erreur: " . $db->lasterror();
}
```

---

### 4️⃣ **Vérifier les permissions fichiers**

Sur votre serveur:

```bash
cd /var/www/html/dolibarr/htdocs/custom/mv3pro_portail/

# Donner les bonnes permissions
chmod -R 755 pwa_dist/
chmod -R 755 mobile_app/
chmod -R 755 api/

# Si Apache/Nginx
chown -R www-data:www-data pwa_dist/
chown -R www-data:www-data mobile_app/
chown -R www-data:www-data api/
```

---

### 5️⃣ **Configuration Apache/Nginx**

#### Pour Apache (.htaccess)

Créez/éditez `.htaccess` dans `pwa_dist/`:

```apache
# pwa_dist/.htaccess
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /custom/mv3pro_portail/pwa_dist/
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /custom/mv3pro_portail/pwa_dist/index.html [L]
</IfModule>

<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Cache des assets
<FilesMatch "\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$">
  Header set Cache-Control "public, max-age=31536000"
</FilesMatch>

# Pas de cache pour index.html et manifest
<FilesMatch "\.(html|json|webmanifest)$">
  Header set Cache-Control "no-cache, no-store, must-revalidate"
</FilesMatch>
```

#### Pour Nginx

```nginx
location /custom/mv3pro_portail/pwa_dist/ {
    try_files $uri $uri/ /custom/mv3pro_portail/pwa_dist/index.html;

    # Cache des assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Pas de cache pour HTML/manifest
    location ~* \.(html|json|webmanifest)$ {
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }
}
```

---

## 🧪 Test de l'installation

### 1. Vérifier que les fichiers sont accessibles

Ouvrez dans votre navigateur:
```
https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/
```

Vous devriez voir la page de login.

### 2. Tester l'API d'authentification

```bash
# Test login
curl -X POST https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test123"}'
```

**Réponse attendue:**
```json
{
  "success": true,
  "token": "...",
  "user": {
    "user_rowid": 1,
    "email": "test@example.com",
    "firstname": "Test",
    "lastname": "User"
  }
}
```

### 3. Tester avec le navigateur

1. Ouvrez `https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/`
2. Entrez: `test@example.com` / `test123`
3. Appuyez sur F12 pour voir la console
4. Cliquez sur "Se connecter"

**Si erreur:**
- ✅ Vérifiez la console (F12 > Console)
- ✅ Vérifiez l'onglet Network (F12 > Network)
- ✅ Vérifiez les logs PHP (`tail -f /var/log/apache2/error.log`)

---

## 🔍 Diagnostic des erreurs courantes

### Erreur: "Impossible de charger Dolibarr"

**Cause:** Le fichier `main.inc.php` n'est pas trouvé

**Solution:**
```php
// Vérifiez dans auth.php ligne 38-43
// Ajustez le chemin si nécessaire
$res = @include __DIR__ . "/../../../main.inc.php";
```

### Erreur: "Table llx_mv3_mobile_users doesn't exist"

**Solution:**
```bash
mysql -u USER -p DATABASE < custom/mv3pro_portail/sql/llx_mv3_mobile_users.sql
```

### Erreur: "CORS policy"

**Solution:** Vérifiez que les headers CORS sont présents dans `auth.php`:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### Erreur 404 sur les API

**Solution:** Vérifiez que les chemins dans `api.ts` correspondent à votre structure:
```typescript
const API_BASE_URL = '/custom/mv3pro_portail/api/v1';
const AUTH_API_URL = '/custom/mv3pro_portail/mobile_app/api/auth.php';
```

### Page blanche après login

**Cause:** Les routes React ne fonctionnent pas

**Solution:** Configurez le `.htaccess` (voir section 5)

---

## 📱 Installation sur mobile

Une fois que tout fonctionne:

1. Ouvrez l'URL dans Chrome/Safari mobile
2. Chrome: Menu > "Ajouter à l'écran d'accueil"
3. Safari: Partager > "Sur l'écran d'accueil"
4. L'icône apparaît comme une vraie app!

---

## 🎯 Checklist rapide

- [ ] Tables SQL créées (llx_mv3_mobile_users, llx_mv3_mobile_sessions)
- [ ] Utilisateur de test créé
- [ ] Fichiers PWA copiés dans `pwa_dist/`
- [ ] Permissions fichiers OK (755)
- [ ] .htaccess configuré (si Apache)
- [ ] Test: URL accessible (https://domain/custom/mv3pro_portail/pwa_dist/)
- [ ] Test: Login fonctionne
- [ ] Test: Console browser sans erreurs
- [ ] Test: API retourne du JSON valide

---

## 🆘 Besoin d'aide?

Si vous avez une erreur spécifique:

1. **Ouvrez la console du navigateur** (F12)
2. **Copiez l'erreur exacte**
3. **Vérifiez les logs PHP** du serveur
4. **Testez les API directement** avec curl

Dites-moi l'erreur exacte et je vous aide à la résoudre!
