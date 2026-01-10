# 🔍 Debug Upload Photos - Instructions Complètes

## Date: 10 janvier 2026 - 21:47

---

## ⚡ ACTION IMMÉDIATE

### 1️⃣ Accéder au Monitor Live (RECOMMANDÉ)

**URL:** `https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/live_debug.html`

**Ce que vous verrez:**
- Interface graphique moderne avec formulaire de test
- Logs en temps réel dans le navigateur
- Barre de progression
- Statistiques d'upload
- Réponses serveur formatées

**Comment l'utiliser:**
1. Ouvrir l'URL dans votre navigateur
2. Vérifier que Event ID = 74049
3. Cliquer sur "Choisir un fichier" et sélectionner une image
4. Cliquer sur "📤 Uploader la Photo"
5. **Observer attentivement:**
   - Les logs qui défilent en temps réel
   - La barre de progression
   - Le statut (vert = succès, rouge = erreur)
   - La réponse du serveur

**Avantages:**
- ✅ Interface visuelle claire
- ✅ Logs détaillés dans le navigateur
- ✅ Aucun accès SSH requis
- ✅ Statistiques en direct
- ✅ Historique des uploads

---

### 2️⃣ Diagnostic Complet du Système

**URL:** `https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/test_upload_debug.php`

**Ce que vous verrez:**
- Liste de 10 tests système
- État de chaque composant (✓ ou ✗)
- Formulaire de test d'upload manuel
- Informations détaillées sur la configuration

**Tests effectués:**
1. Configuration PHP upload
2. Bootstrap Dolibarr
3. Variables globales
4. Chemins système
5. Module mv3pro_portail
6. Permissions répertoires
7. Classe ActionComm
8. Authentification
9. Base de données
10. Système de fichiers

---

### 3️⃣ Logs Serveur (Si vous avez accès SSH)

```bash
# Voir les logs en temps réel
sudo tail -f /var/log/apache2/error.log | grep "MV3 UPLOAD DEBUG"

# Ou tous les logs MV3
sudo tail -f /var/log/apache2/error.log | grep "MV3"
```

**Séquence normale attendue:**
```
[MV3 UPLOAD DEBUG] === DÉBUT UPLOAD ===
[MV3 UPLOAD DEBUG] Bootstrap chargé, vérification méthode...
[MV3 UPLOAD DEBUG] Méthode POST validée, authentification...
[MV3 UPLOAD DEBUG] Auth OK - User ID: XXX
[MV3 UPLOAD DEBUG] Event ID reçu: 74049
[MV3 UPLOAD DEBUG] Event ID validé: 74049
[MV3 UPLOAD DEBUG] Fichier reçu: XXX.jpg (XXX bytes)
[MV3 UPLOAD DEBUG] Chargement librairies Dolibarr...
[MV3 UPLOAD DEBUG] DOL_DOCUMENT_ROOT: /var/www/...
[MV3 UPLOAD DEBUG] files.lib.php chargé
[MV3 UPLOAD DEBUG] actioncomm.class.php chargé
[MV3 UPLOAD DEBUG] Fetch ActionComm #74049
[MV3 UPLOAD DEBUG] Résultat fetch: 1
[MV3 UPLOAD DEBUG] Vérification $conf->mv3pro_portail: EXISTS
[MV3 UPLOAD DEBUG] Upload dir: /var/www/.../planning/74049
[MV3 UPLOAD DEBUG] Dir existe: NON/OUI
[MV3 UPLOAD DEBUG] Fichier déplacé avec succès
[MV3 UPLOAD DEBUG] SQL INSERT OK
[MV3 UPLOAD DEBUG] === UPLOAD TERMINÉ AVEC SUCCÈS ===
```

---

## 🎯 Procédure Recommandée

### Étape 1: Test avec le Monitor Live
1. Ouvrir `live_debug.html`
2. Tester un upload
3. **Noter exactement où ça plante**
4. Faire une capture d'écran

### Étape 2: Vérifier le Diagnostic Système
1. Ouvrir `test_upload_debug.php`
2. Vérifier que tous les tests passent (✓)
3. Si des tests échouent (✗), **noter lesquels**
4. Tester l'upload manuel depuis ce script

### Étape 3: Analyser les Logs (si échec)
1. Si vous avez SSH, consulter les logs serveur
2. Chercher le dernier message avant l'erreur
3. Noter le message d'erreur exact

### Étape 4: Appliquer la Solution
Selon l'erreur identifiée, voir la section **Solutions** ci-dessous.

---

## 🚨 Solutions aux Erreurs Courantes

### Erreur: "Module non activé" ou "$conf->mv3pro_portail NOT EXISTS"

**Symptôme:** Le script de diagnostic indique que le module n'est pas activé.

**Solution:**
1. Se connecter à Dolibarr en tant qu'admin
2. Aller dans: Home → Setup → Modules
3. Chercher "MV3 PRO Portail"
4. Cliquer sur "Enable"

---

### Erreur: "Impossible de créer le répertoire"

**Symptôme:**
```
[MV3 UPLOAD DEBUG] ERREUR: Impossible de créer le répertoire
```

**Solution (SSH requis):**
```bash
# Donner les bonnes permissions
sudo chown -R www-data:www-data /var/www/dolibarr/documents/mv3pro_portail
sudo chmod -R 755 /var/www/dolibarr/documents/mv3pro_portail

# Créer le répertoire manuellement
sudo mkdir -p /var/www/dolibarr/documents/mv3pro_portail/planning
sudo chown -R www-data:www-data /var/www/dolibarr/documents/mv3pro_portail/planning
```

---

### Erreur: "move_uploaded_file a échoué"

**Symptômes possibles:**
- Permissions insuffisantes
- Disque plein
- open_basedir restriction

**Solutions:**

**1. Vérifier l'espace disque:**
```bash
df -h
```

**2. Vérifier les permissions:**
```bash
ls -la /var/www/dolibarr/documents/mv3pro_portail/planning/
```

**3. Donner les bonnes permissions:**
```bash
sudo chown -R www-data:www-data /var/www/dolibarr/documents/
sudo chmod -R 755 /var/www/dolibarr/documents/
```

**4. Vérifier open_basedir:**
```bash
php -i | grep open_basedir
```
Si une restriction existe, il faut l'ajuster dans `php.ini`.

---

### Erreur: "Erreur SQL ecm_files"

**Symptôme:**
```
[MV3 UPLOAD DEBUG] ERREUR SQL: Table 'llx_ecm_files' doesn't exist
```

**Solution (SQL):**
```sql
-- Vérifier que la table existe
SHOW TABLES LIKE 'llx_ecm_files';

-- Si elle n'existe pas, la créer (structure standard Dolibarr)
-- Contacter le support Dolibarr ou réinstaller le module ECM
```

---

### Erreur: "Auth failed" ou "Non authentifié"

**Symptôme:** L'authentification échoue

**Solutions:**

**1. Depuis la PWA:**
- Se déconnecter
- Se reconnecter
- Vérifier que le token est stocké dans localStorage

**2. Depuis le Monitor Live:**
Le script essaie d'utiliser le token dans localStorage. Si vous n'êtes pas connecté via la PWA:
- Ouvrir la PWA dans un autre onglet
- Se connecter
- Revenir au Monitor Live
- Re-tester

---

### Erreur: "DOL_DOCUMENT_ROOT non défini"

**Symptôme:** Le bootstrap Dolibarr ne se charge pas

**Solution:**
Le fichier `_bootstrap.php` ne trouve pas Dolibarr. Vérifier:
```bash
ls -la /var/www/dolibarr/htdocs/main.inc.php
```

Si le fichier n'existe pas, Dolibarr n'est pas installé correctement.

---

## 📊 Interprétation des Résultats

### ✅ Upload Réussi

**Logs attendus:**
```
✓ Bootstrap chargé
✓ Auth OK
✓ Event ID validé
✓ Fichier reçu
✓ Librairies chargées
✓ Répertoire créé/existe
✓ Fichier déplacé
✓ SQL INSERT OK
✓ Upload terminé
```

**Réponse HTTP:** `201 Created`

**Réponse JSON:**
```json
{
  "success": true,
  "message": "Photo uploadée avec succès",
  "file": {
    "name": "photo_1736510400.jpg",
    "original_name": "photo.jpg",
    "size": 123456,
    "mime": "image/jpeg"
  }
}
```

---

### ❌ Upload Échoué

**Le dernier log avant l'erreur indique où ça plante:**

| Dernier log | Problème | Solution |
|-------------|----------|----------|
| "Bootstrap chargé" | Erreur POST/Auth | Vérifier token, méthode HTTP |
| "Auth OK" | Event ID invalide | Vérifier que l'événement existe |
| "Event ID validé" | Fichier non reçu | Vérifier taille/type fichier |
| "Fichier reçu" | Librairies manquantes | Vérifier installation Dolibarr |
| "Librairies chargées" | Permissions répertoire | Corriger permissions (chmod/chown) |
| "Répertoire créé" | move_uploaded_file | Vérifier permissions + disque |
| "Fichier déplacé" | Erreur SQL | Vérifier table ecm_files |

---

## 🔧 Fichiers Modifiés

### 1. `planning_upload_photo.php`
**Modifications:**
- Ajout du mode DEBUG
- Logs détaillés à chaque étape
- Gestion d'erreurs améliorée
- Affichage des erreurs PHP activé

**Désactiver le debug:**
```php
// Ligne 9: Changer true en false
define('DEBUG_UPLOAD', false);
```

### 2. Nouveaux fichiers créés
- ✅ `test_upload_debug.php` - Diagnostic système complet
- ✅ `live_debug.html` - Monitor live avec interface graphique
- ✅ `MODE_DEBUG_UPLOAD.md` - Documentation complète

---

## 📞 Informations à Collecter (Si le problème persiste)

### Depuis le Monitor Live
1. Capture d'écran complète de la page
2. Copier tous les logs affichés
3. Copier la réponse serveur (section "Dernière Réponse")

### Depuis test_upload_debug.php
1. Capture d'écran complète de la page
2. Noter tous les tests qui échouent (✗)
3. Résultat du test d'upload manuel

### Depuis les Logs Serveur (si accès SSH)
```bash
# Récupérer les 50 dernières lignes
sudo tail -50 /var/log/apache2/error.log > debug_logs.txt
```

### Informations Système
```bash
# Version PHP
php -v

# Version Dolibarr
# (visible dans Dolibarr → Home → About)

# Système d'exploitation
uname -a

# Espace disque
df -h

# Permissions du répertoire
ls -la /var/www/dolibarr/documents/mv3pro_portail/
```

---

## ✅ Checklist Finale

Avant de déclarer le problème résolu, tester:

- [ ] Upload depuis le Monitor Live fonctionne
- [ ] Upload depuis le script test_upload_debug.php fonctionne
- [ ] Upload depuis la PWA fonctionne
- [ ] La photo apparaît dans la liste
- [ ] La photo apparaît dans l'onglet Photos
- [ ] Le fichier existe physiquement sur le serveur
- [ ] L'entrée existe dans la table ecm_files
- [ ] Pas d'erreur dans les logs serveur
- [ ] La barre de progression fonctionne
- [ ] La miniature s'affiche correctement

---

## 🎓 Commandes Utiles

```bash
# Voir les logs en temps réel
sudo tail -f /var/log/apache2/error.log | grep MV3

# Vérifier les fichiers uploadés
ls -lah /var/www/dolibarr/documents/mv3pro_portail/planning/74049/

# Nettoyer les logs (ATTENTION: efface tout)
sudo truncate -s 0 /var/log/apache2/error.log

# Vérifier les permissions
namei -l /var/www/dolibarr/documents/mv3pro_portail/planning/

# Tester l'écriture
sudo -u www-data touch /var/www/dolibarr/documents/mv3pro_portail/test.txt

# Vérifier la base de données
mysql -u root -p dolibarr -e "SELECT COUNT(*) FROM llx_ecm_files WHERE src_object_type='actioncomm';"
```

---

## 🚀 COMMENCEZ ICI

**1. Ouvrir dans votre navigateur:**
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/live_debug.html
```

**2. Tester l'upload**

**3. Si erreur, ouvrir:**
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/test_upload_debug.php
```

**4. Me communiquer:**
- Les logs affichés
- Les tests qui échouent
- Captures d'écran

---

## 📌 Notes Importantes

- ⚠️ Le mode debug affiche des infos sensibles dans les logs
- ⚠️ Désactiver le debug après résolution du problème
- ⚠️ Les logs serveur peuvent devenir volumineux
- ✅ Les 3 outils (Monitor Live, Diagnostic, Logs) sont complémentaires
- ✅ Commencer toujours par le Monitor Live (le plus simple)

---

**BON COURAGE!** 💪

Si le problème persiste après avoir suivi ces instructions, envoyez-moi les résultats des tests et je vous aiderai davantage.
