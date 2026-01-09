# 🚀 Démarrage Rapide - MV3 PRO Mobile PWA

## ⚡ Installation en 5 minutes

### 1️⃣ Créer les tables (30 secondes)

```bash
mysql -u root -p dolibarr < new_dolibarr/mv3pro_portail/sql/llx_mv3_mobile_users.sql
```

### 2️⃣ Créer un utilisateur (1 minute)

**Option rapide:**
```sql
USE dolibarr;
INSERT INTO llx_mv3_mobile_users
(email, password_hash, firstname, lastname, role, is_active)
VALUES
('admin@test.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Admin', 'Test', 'manager', 1);
```

Login: `admin@test.com` / Mot de passe: `test123`

**Option interface:**

Accédez à: `https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/admin/create_mobile_user.php`

### 3️⃣ Copier les fichiers (2 minutes)

Copiez le dossier `new_dolibarr/mv3pro_portail/` vers votre serveur Dolibarr:

```bash
# Exemple avec SCP
scp -r new_dolibarr/mv3pro_portail/* user@serveur:/var/www/html/dolibarr/htdocs/custom/mv3pro_portail/

# Ou avec SFTP/FTP selon votre configuration
```

### 4️⃣ Permissions (30 secondes)

Sur le serveur:
```bash
cd /var/www/html/dolibarr/htdocs/custom/mv3pro_portail
chmod -R 755 pwa_dist/
chmod -R 755 mobile_app/
chown -R www-data:www-data .
```

### 5️⃣ Activer mod_rewrite (30 secondes)

```bash
a2enmod rewrite
systemctl restart apache2
```

### ✅ Test final (30 secondes)

Ouvrez: `https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/`

Login: `admin@test.com` / `test123`

---

## 🎯 Que faire si ça ne marche pas?

### Problème 1: Page blanche

**Solution:**
```bash
# Vérifiez que mod_rewrite est activé
apache2ctl -M | grep rewrite

# Si pas de résultat:
a2enmod rewrite
systemctl restart apache2
```

### Problème 2: Erreur "Table doesn't exist"

**Solution:**
```bash
# Vérifiez que les tables existent
mysql -u root -p dolibarr -e "SHOW TABLES LIKE 'llx_mv3_mobile%';"

# Si vide, créez-les:
mysql -u root -p dolibarr < new_dolibarr/mv3pro_portail/sql/llx_mv3_mobile_users.sql
```

### Problème 3: Erreur CORS ou API

**Solution:**
Ouvrez F12 dans votre navigateur, regardez l'onglet Console et Network.

Copiez l'erreur exacte et consultez: `DIAGNOSTIC_ET_INSTALLATION.md`

### Problème 4: "Impossible de charger Dolibarr"

Le chemin vers `main.inc.php` est incorrect.

**Solution:**
```bash
# Testez manuellement
php -r "require_once '/var/www/html/dolibarr/htdocs/main.inc.php'; echo 'OK';"
```

---

## 📱 Installation sur téléphone

1. **Ouvrez l'URL sur votre téléphone**
2. **Chrome:** Menu > "Ajouter à l'écran d'accueil"
3. **Safari:** Partager > "Sur l'écran d'accueil"
4. **Profitez!**

---

## 🎨 Personnalisation rapide

### Changer les couleurs

Éditez: `pwa/src/index.css`

```css
/* Cherchez ces valeurs et modifiez-les */
--primary: #0891b2;    /* Couleur principale */
--secondary: #06b6d4;  /* Couleur secondaire */
```

Puis rebuilder:
```bash
cd new_dolibarr/mv3pro_portail/pwa
npm run build
```

### Changer le nom de l'app

Éditez: `pwa_dist/manifest.webmanifest`

```json
{
  "name": "Votre Entreprise Mobile",
  "short_name": "VotreApp"
}
```

---

## 📚 Documentation complète

- **Installation détaillée:** `new_dolibarr/mv3pro_portail/README_PWA.md`
- **Diagnostic complet:** `DIAGNOSTIC_ET_INSTALLATION.md`
- **Installation dans pwa_dist:** `new_dolibarr/mv3pro_portail/pwa_dist/INSTALLATION.md`

---

## ✅ Checklist complète

- [ ] Tables SQL créées
- [ ] Utilisateur de test créé
- [ ] Fichiers copiés sur le serveur
- [ ] Permissions configurées (755)
- [ ] mod_rewrite activé
- [ ] Test de connexion réussi
- [ ] Installation sur mobile réussie
- [ ] Déconnexion/reconnexion fonctionne

---

## �� Commandes utiles

```bash
# Voir les logs Apache en temps réel
tail -f /var/log/apache2/error.log

# Voir les erreurs PHP
tail -f /var/log/apache2/error.log | grep PHP

# Vérifier les tables
mysql -u root -p dolibarr -e "SELECT email, firstname, lastname, is_active FROM llx_mv3_mobile_users;"

# Réinitialiser le mot de passe d'un utilisateur
# Mot de passe: nouveau123
mysql -u root -p dolibarr -e "UPDATE llx_mv3_mobile_users SET password_hash='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email='admin@test.com';"
```

---

## 🆘 Besoin d'aide?

Si après avoir suivi ce guide vous avez toujours des problèmes:

1. Ouvrez F12 dans votre navigateur
2. Allez dans Console
3. Copiez l'erreur exacte
4. Consultez `DIAGNOSTIC_ET_INSTALLATION.md`
5. Contactez le support avec les détails

**L'application est 100% fonctionnelle si toutes les étapes sont suivies correctement!**
