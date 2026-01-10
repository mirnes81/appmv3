# Mode Debug Upload Photos - Guide Complet

## Date: 10 janvier 2026

## Objectif
Diagnostiquer et résoudre l'erreur 500 lors de l'upload de photos vers les événements du planning.

---

## 🔧 Outils de Debug Disponibles

### 1. Mode Debug dans planning_upload_photo.php
Le fichier a été modifié pour inclure un mode debug détaillé activé par défaut.

**Constante:** `DEBUG_UPLOAD = true` (ligne 9)

**Logs générés:**
- ✓ Chargement du bootstrap
- ✓ Validation de la méthode POST
- ✓ Authentification utilisateur
- ✓ Validation Event ID
- ✓ Validation du fichier uploadé
- ✓ Chargement des librairies Dolibarr
- ✓ Vérification et création des répertoires
- ✓ Déplacement du fichier
- ✓ Insertion en base de données

**Localisation des logs:**
- Sur le serveur: `/var/log/apache2/error.log` ou `/var/log/php-fpm/error.log`
- Dans le navigateur: Console DevTools (si erreurs PHP affichées)

---

### 2. Script de Diagnostic Complet
**URL:** `https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/test_upload_debug.php`

**Tests effectués:**
1. ✅ Configuration PHP (upload_max_filesize, post_max_size, etc.)
2. ✅ Chargement Bootstrap Dolibarr
3. ✅ Vérification variables globales ($db, $conf, $user)
4. ✅ Vérification DOL_DOCUMENT_ROOT
5. ✅ Vérification module mv3pro_portail
6. ✅ Test création de répertoire avec dol_mkdir()
7. ✅ Test chargement classe ActionComm
8. ✅ Test authentification
9. ✅ Test connexion base de données
10. ✅ Permissions système

**Formulaire de test:**
Le script inclut un formulaire pour tester l'upload manuellement avec un Event ID spécifique.

---

## 📋 Procédure de Diagnostic

### Étape 1: Vérifier les Logs Serveur
```bash
# Sur le serveur
sudo tail -f /var/log/apache2/error.log | grep "MV3 UPLOAD DEBUG"
```

### Étape 2: Accéder au Script de Diagnostic
1. Ouvrir: `https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/test_upload_debug.php`
2. Vérifier que tous les tests passent (✓)
3. Noter les erreurs (✗)

### Étape 3: Tester l'Upload depuis le Script
1. Dans le formulaire, entrer Event ID: `74049`
2. Sélectionner une image
3. Cliquer "Tester Upload"
4. Observer le résultat

### Étape 4: Tester depuis la PWA
1. Ouvrir la PWA: Planning → Événement #74049
2. Onglet Photos → Ajouter une photo
3. Sélectionner une image
4. **Simultanément:**
   - Observer la console du navigateur
   - Observer les logs serveur

---

## 🔍 Messages de Debug Détaillés

### Format des logs:
```
[MV3 UPLOAD DEBUG] <message>
```

### Séquence normale d'exécution:
```
[MV3 UPLOAD DEBUG] === DÉBUT UPLOAD ===
[MV3 UPLOAD DEBUG] Bootstrap chargé, vérification méthode...
[MV3 UPLOAD DEBUG] Méthode POST validée, authentification...
[MV3 UPLOAD DEBUG] Auth OK - User ID: 123
[MV3 UPLOAD DEBUG] Event ID reçu: 74049
[MV3 UPLOAD DEBUG] Event ID validé: 74049
[MV3 UPLOAD DEBUG] $_FILES: Array(...)
[MV3 UPLOAD DEBUG] Fichier reçu: photo.jpg (123456 bytes)
[MV3 UPLOAD DEBUG] Chargement librairies Dolibarr...
[MV3 UPLOAD DEBUG] DOL_DOCUMENT_ROOT: /var/www/dolibarr/htdocs
[MV3 UPLOAD DEBUG] files.lib.php chargé
[MV3 UPLOAD DEBUG] actioncomm.class.php chargé
[MV3 UPLOAD DEBUG] Fetch ActionComm #74049
[MV3 UPLOAD DEBUG] Résultat fetch: 1
[MV3 UPLOAD DEBUG] Vérification $conf->mv3pro_portail: EXISTS
[MV3 UPLOAD DEBUG] Vérification $conf->mv3pro_portail->dir_output: /var/www/dolibarr/documents/mv3pro_portail
[MV3 UPLOAD DEBUG] Upload dir: /var/www/dolibarr/documents/mv3pro_portail/planning/74049
[MV3 UPLOAD DEBUG] Dir existe: NON
[MV3 UPLOAD DEBUG] Création du répertoire...
[MV3 UPLOAD DEBUG] Résultat dol_mkdir: 1
[MV3 UPLOAD DEBUG] Destination: /var/www/dolibarr/documents/mv3pro_portail/planning/74049/photo_1736510400.jpg
[MV3 UPLOAD DEBUG] Tmp file: /tmp/phpXXXXXX
[MV3 UPLOAD DEBUG] Tmp file existe: OUI
[MV3 UPLOAD DEBUG] Fichier déplacé avec succès
[MV3 UPLOAD DEBUG] Fichier existe: OUI
[MV3 UPLOAD DEBUG] Exécution SQL INSERT ecm_files...
[MV3 UPLOAD DEBUG] SQL: INSERT INTO...
[MV3 UPLOAD DEBUG] SQL INSERT OK
[MV3 UPLOAD DEBUG] === UPLOAD TERMINÉ AVEC SUCCÈS ===
```

---

## 🚨 Erreurs Communes et Solutions

### Erreur 1: "$conf->mv3pro_portail NOT EXISTS"
**Cause:** Module non activé
**Solution:**
```bash
# Activer le module
cd /var/www/dolibarr
./htdocs/custom/mv3pro_portail/scripts/activate_module.sh
```

### Erreur 2: "Impossible de créer le répertoire"
**Cause:** Permissions insuffisantes
**Solution:**
```bash
# Donner les bonnes permissions
sudo chown -R www-data:www-data /var/www/dolibarr/documents/mv3pro_portail
sudo chmod -R 755 /var/www/dolibarr/documents/mv3pro_portail
```

### Erreur 3: "move_uploaded_file a échoué"
**Causes possibles:**
- Permissions insuffisantes
- Disque plein
- open_basedir restriction

**Solution:**
```bash
# Vérifier espace disque
df -h

# Vérifier permissions
ls -la /var/www/dolibarr/documents/mv3pro_portail/planning/

# Vérifier open_basedir dans php.ini
php -i | grep open_basedir
```

### Erreur 4: "Erreur SQL ecm_files"
**Cause:** Table manquante ou structure incorrecte
**Solution:**
```sql
-- Vérifier la table
SHOW TABLES LIKE 'llx_ecm_files';

-- Vérifier la structure
DESC llx_ecm_files;
```

---

## 🎯 Points de Contrôle Critiques

### 1. Module activé
```bash
# Vérifier dans Dolibarr
# Home → Setup → Modules → MV3 PRO Portail → Activé
```

### 2. Permissions répertoire
```bash
ls -la /var/www/dolibarr/documents/ | grep mv3pro_portail
# Doit afficher: drwxr-xr-x www-data www-data
```

### 3. Configuration PHP
```bash
php -i | grep -E "upload_max_filesize|post_max_size|max_file_uploads"
# upload_max_filesize: 10M minimum
# post_max_size: 10M minimum
```

### 4. Base de données
```sql
-- Test connexion
SELECT COUNT(*) FROM llx_ecm_files;

-- Test insertion
INSERT INTO llx_ecm_files (label, entity, filepath, filename, src_object_type, src_object_id)
VALUES ('test', 1, 'test', 'test.jpg', 'actioncomm', 1);
```

---

## 📞 Commandes Utiles

### Voir les logs en temps réel:
```bash
sudo tail -f /var/log/apache2/error.log
```

### Filtrer uniquement les logs MV3:
```bash
sudo tail -f /var/log/apache2/error.log | grep "MV3"
```

### Voir les dernières erreurs PHP:
```bash
sudo tail -100 /var/log/apache2/error.log | grep -i "error"
```

### Vérifier les fichiers uploadés:
```bash
ls -lah /var/www/dolibarr/documents/mv3pro_portail/planning/74049/
```

---

## 🔐 Sécurité

**IMPORTANT:** Le mode debug affiche des informations sensibles dans les logs.

**Désactiver le mode debug après diagnostic:**
```php
// Dans planning_upload_photo.php ligne 9:
define('DEBUG_UPLOAD', false);
```

**Nettoyer les logs:**
```bash
sudo truncate -s 0 /var/log/apache2/error.log
```

---

## ✅ Checklist de Résolution

- [ ] Accéder au script de diagnostic
- [ ] Vérifier que tous les tests passent
- [ ] Tester l'upload manuel depuis le script
- [ ] Observer les logs serveur en temps réel
- [ ] Tester l'upload depuis la PWA
- [ ] Identifier le point exact de l'erreur
- [ ] Appliquer la correction appropriée
- [ ] Re-tester l'upload
- [ ] Désactiver le mode debug

---

## 📝 Rapport de Bug

Si le problème persiste, collecter les informations suivantes:

1. **Sortie complète du script test_upload_debug.php**
2. **Logs serveur complets** (30 dernières lignes)
3. **Console navigateur** (screenshot ou copie)
4. **Version PHP:** `php -v`
5. **Version Dolibarr:** (visible dans Dolibarr → Home → About)
6. **Système d'exploitation:** `uname -a`

---

## 🎓 Ressources

- [Documentation Dolibarr - Modules](https://wiki.dolibarr.org/index.php/Module_development)
- [PHP File Uploads](https://www.php.net/manual/en/features.file-upload.php)
- [Dolibarr ECM (Document Management)](https://wiki.dolibarr.org/index.php/ECM_-_Electronic_Document_Management)
