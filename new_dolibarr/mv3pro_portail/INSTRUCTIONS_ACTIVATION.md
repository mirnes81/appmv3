# 🚀 INSTRUCTIONS D'ACTIVATION MODULE BONS DE RÉGIE

## ⚠️ PROBLÈMES RÉSOLUS

### Erreur "DOL_DOCUMENT_ROOT undefined"
✅ **CORRIGÉ** - Le fichier `regie/class/regie.class.php` a été mis à jour.

### Menu gauche ne s'affiche pas
✅ **SOLUTION CI-DESSOUS** - Il faut vider le cache Dolibarr.

---

## 📋 CHECKLIST ACTIVATION (À FAIRE DANS L'ORDRE)

### ✅ Étape 1: Vérifier que les fichiers sont bien copiés

```bash
# Vérifier que le dossier regie existe
ls -la /home/ch314761/web/crm.mv-3pro.ch/public_html/custom/mv3pro_portail/regie/

# Doit contenir:
# - class/
# - pdf/
# - list.php
# - card.php
# - sign.php
# - upload_photo.php
# - view_photo.php
```

### ✅ Étape 2: Importer les tables SQL

**Méthode 1: Via phpMyAdmin ou Adminer**

1. Aller sur phpMyAdmin/Adminer
2. Sélectionner votre base de données Dolibarr
3. Onglet "SQL"
4. Copier-coller le contenu du fichier `sql/llx_mv3_regie.sql`
5. Cliquer "Exécuter"

**Méthode 2: Via ligne de commande (SSH)**

```bash
mysql -u VOTRE_USER -p VOTRE_DATABASE < /path/to/sql/llx_mv3_regie.sql
```

**Vérification:**
```sql
-- Vérifier que les tables sont créées
SHOW TABLES LIKE 'llx_mv3_regie%';

-- Doit afficher 7 tables:
-- llx_mv3_regie
-- llx_mv3_regie_line
-- llx_mv3_regie_photo
-- llx_mv3_regie_token
-- llx_mv3_regie_signature
-- llx_mv3_regie_type
-- llx_mv3_regie_forfait
```

### ✅ Étape 3: Vider le cache Dolibarr ⚠️ IMPORTANT

**Méthode 1: Via l'interface Dolibarr (RECOMMANDÉ)**

1. Se connecter à Dolibarr
2. Aller sur **Accueil** (en haut à gauche)
3. Dans le menu de gauche, cliquer sur **Outils**
4. Cliquer sur **Purger cache / données compilées**
5. Cocher **TOUTES les cases**
6. Cliquer sur **Purger le cache**
7. ✅ Message de confirmation doit apparaître

**Méthode 2: Via ligne de commande (SSH)**

```bash
# Supprimer le cache des menus
rm -rf /home/ch314761/web/crm.mv-3pro.ch/public_html/documents/admin/temp/*

# Vider le cache Smarty/Twig
rm -rf /home/ch314761/web/crm.mv-3pro.ch/public_html/documents/admin/tpl/*
```

**Méthode 3: Via FTP/cPanel**

1. Se connecter en FTP
2. Aller dans `/public_html/documents/admin/temp/`
3. Supprimer TOUS les fichiers dans ce dossier
4. Aller dans `/public_html/documents/admin/tpl/`
5. Supprimer TOUS les fichiers dans ce dossier

### ✅ Étape 4: Se reconnecter à Dolibarr

1. **SE DÉCONNECTER** complètement de Dolibarr
2. **Fermer complètement le navigateur** (pas juste l'onglet)
3. **Rouvrir le navigateur**
4. Se reconnecter à Dolibarr
5. ✅ Le menu **"Bons de régie"** doit maintenant apparaître dans le menu gauche

---

## 🔍 VÉRIFICATION QUE ÇA MARCHE

### Test 1: Menu visible

✅ Dans le menu **MV-3 PRO** (gauche), vous devez voir:

```
📋 Bons de régie
  ├── - Liste des bons
  └── - Nouveau bon
```

Si ce n'est **PAS visible**:
- Vider à nouveau le cache (Étape 3)
- Vérifier que le fichier `core/modules/modMv3pro_portail.class.php` contient bien le menu régie
- Se reconnecter

### Test 2: Accès à la liste

1. Cliquer sur **"Bons de régie"** ou **"- Liste des bons"**
2. URL doit être: `https://crm.mv-3pro.ch/custom/mv3pro_portail/regie/list.php`
3. Page doit s'afficher **SANS ERREUR**
4. Message "Aucun bon de régie" doit s'afficher (c'est normal au début)

### Test 3: Création d'un bon

1. Cliquer sur **"- Nouveau bon"**
2. URL: `https://crm.mv-3pro.ch/custom/mv3pro_portail/regie/card.php?action=create`
3. Formulaire doit s'afficher
4. Sélectionner un projet
5. Remplir date, lieu, type
6. Cliquer **"Créer"**
7. ✅ Redirection vers la fiche du bon créé

---

## 🐛 RÉSOLUTION PROBLÈMES FRÉQUENTS

### Problème: "Class 'Regie' not found"

**Solution:**
```bash
# Vérifier que le fichier existe
ls -la /path/to/custom/mv3pro_portail/regie/class/regie.class.php

# Vérifier les permissions
chmod 644 /path/to/custom/mv3pro_portail/regie/class/regie.class.php
```

### Problème: "Table 'llx_mv3_regie' doesn't exist"

**Solution:** Les tables SQL n'ont pas été importées
- Retourner à l'Étape 2
- Importer le fichier SQL

### Problème: Menu toujours pas visible après cache vidé

**Solution 1: Vérifier les droits utilisateur**
```sql
-- Vérifier les droits de l'utilisateur connecté
SELECT * FROM llx_user WHERE login = 'VOTRE_LOGIN';
-- Vérifier la colonne 'admin' (doit être 1 pour voir tous les menus)
```

**Solution 2: Réactiver le module**
1. Aller sur **Accueil > Configuration > Modules**
2. Chercher **"MV3 PRO Portail"**
3. Cliquer sur **"Désactiver"**
4. Attendre 5 secondes
5. Cliquer sur **"Activer"**
6. Vider le cache (Étape 3)
7. Se reconnecter

**Solution 3: Vérifier le fichier modMv3pro_portail.class.php**
```bash
# Le fichier doit contenir le menu "Bons de régie" à la ligne ~395-444
grep -n "Bons de régie" /path/to/custom/mv3pro_portail/core/modules/modMv3pro_portail.class.php

# Doit retourner quelque chose comme:
# 399:            'titre'     => 'Bons de régie',
```

### Problème: "Permission denied" sur upload photos

**Solution:**
```bash
# Créer le dossier et donner les permissions
mkdir -p /home/ch314761/web/crm.mv-3pro.ch/public_html/documents/mv3pro_portail/regie
chmod 755 /home/ch314761/web/crm.mv-3pro.ch/public_html/documents/mv3pro_portail/regie
chown www-data:www-data /home/ch314761/web/crm.mv-3pro.ch/public_html/documents/mv3pro_portail/regie
```

### Problème: Page blanche après clic sur "Bons de régie"

**Solution: Activer l'affichage des erreurs PHP**

Ajouter en haut du fichier `list.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Puis consulter les logs:
```bash
tail -f /var/log/apache2/error.log
# ou
tail -f /home/ch314761/logs/error.log
```

---

## 📱 VÉRIFICATION INTERFACE MOBILE

1. Aller sur `https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/dashboard.php`
2. Dans la section "Actions rapides", vérifier qu'il y a:
   - 📝 **Bons de régie** (nouveau)
3. Dans la barre du bas, vérifier:
   - 🏠 Accueil | **📝 Régie** | 📋 Rapports | 🔔 | 👤

---

## ✅ CHECKLIST FINALE

- [ ] Tables SQL importées (7 tables)
- [ ] Cache Dolibarr vidé
- [ ] Déconnexion/reconnexion effectuée
- [ ] Menu "Bons de régie" visible dans le menu gauche
- [ ] Page liste accessible sans erreur
- [ ] Test création d'un bon réussi
- [ ] Interface mobile accessible
- [ ] Permissions dossier documents OK

---

## 🆘 BESOIN D'AIDE?

**Si après TOUTES ces étapes ça ne marche toujours pas:**

1. Copier-coller le message d'erreur COMPLET
2. Vérifier les logs Apache/PHP
3. Vérifier que le module MV3PRO_PORTAIL est bien activé
4. Envoyer:
   - Message d'erreur
   - URL de la page qui pose problème
   - Copie des logs

---

## 📞 CONTACT

Pour toute question, envoyer:
- Capture d'écran de l'erreur
- URL complète
- Version Dolibarr
- Version PHP

---

**MODULE BONS DE RÉGIE - VERSION 1.0**
**Créé pour MV-3 PRO - Novembre 2025**

✅ **Après validation de ces étapes, le module sera 100% opérationnel!**
