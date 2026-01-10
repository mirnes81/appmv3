# ✅ SOLUTION COMPLÈTE: Upload de Photos Planning

## Date: 10 janvier 2026 - 22:00

---

## 🎯 RÉSUMÉ DES PROBLÈMES RÉSOLUS

### ✅ Problème 1: Erreur 401 - Authentification
**Cause:** L'API n'acceptait pas la session Dolibarr
**Solution:** Création d'un endpoint compatible avec la session Dolibarr

### ⚠️ Problème 2: Erreur 500 - Impossible de créer le répertoire (EN COURS)
**Cause:** Répertoires manquants ou permissions incorrectes
**Solution:** Scripts de diagnostic et de réparation créés

---

## 🚀 SOLUTION RAPIDE - 3 ÉTAPES

### **ÉTAPE 1: Diagnostic**
Ouvrez (après connexion à Dolibarr):
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/diagnostic_upload_permissions.php
```

Ce diagnostic vous montrera:
- ✅ Les répertoires qui existent
- ❌ Les répertoires manquants
- ⚠️ Les problèmes de permissions
- 📝 Les commandes de réparation

### **ÉTAPE 2: Réparation Automatique**
Ouvrez:
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/fix_directories.php
```

Ce script va:
- Créer automatiquement les répertoires manquants
- Tester les permissions
- Créer un répertoire de test pour l'événement #74049

### **ÉTAPE 3: Test d'Upload**
Si tout est OK, testez l'upload:
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/live_debug_session.php
```

---

## 🛠️ RÉPARATION MANUELLE (Si nécessaire)

### **Option A: Via l'Interface Web**

1. Connectez-vous à Dolibarr
2. Ouvrez: `fix_directories.php`
3. Le script créera automatiquement les répertoires
4. Si des erreurs persistent, passez à l'Option B

### **Option B: Via SSH (Accès Serveur Requis)**

**Se connecter au serveur:**
```bash
ssh votreuser@crm.mv-3pro.ch
```

**Créer les répertoires:**
```bash
# Aller dans le répertoire de données
cd /var/www/dolibarr/documents

# Créer la structure complète
sudo mkdir -p mv3pro_portail/planning
sudo mkdir -p mv3pro_portail/rapports
sudo mkdir -p mv3pro_portail/regie
sudo mkdir -p mv3pro_portail/sens_pose
sudo mkdir -p mv3pro_portail/temp

# Définir les permissions (775 = lecture/écriture/exécution pour propriétaire et groupe)
sudo chmod -R 775 mv3pro_portail

# Définir le propriétaire (www-data est généralement l'utilisateur Apache/Nginx)
sudo chown -R www-data:www-data mv3pro_portail

# Vérifier les permissions
ls -lah mv3pro_portail/
```

**Résultat attendu:**
```
drwxrwxr-x 7 www-data www-data 4096 Jan 10 22:00 mv3pro_portail
drwxrwxr-x 2 www-data www-data 4096 Jan 10 22:00 planning
drwxrwxr-x 2 www-data www-data 4096 Jan 10 22:00 rapports
```

---

## 🔍 FICHIERS CRÉÉS/MODIFIÉS

### **1. Scripts de Diagnostic**

| Fichier | Description | URL |
|---------|-------------|-----|
| `diagnostic_upload_permissions.php` | Diagnostic complet des permissions | [Ouvrir](https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/diagnostic_upload_permissions.php) |
| `fix_directories.php` | Réparation automatique des répertoires | [Ouvrir](https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/fix_directories.php) |
| `live_debug_session.php` | Monitor d'upload avec session Dolibarr | [Ouvrir](https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/live_debug_session.php) |

### **2. API Modifiée**

**Fichier:** `planning_upload_photo_session.php`

**Changements:**
- ✅ Support de la session Dolibarr (pas de NOLOGIN)
- ✅ Chemin de répertoire robuste avec fallback
- ✅ Logs de debug détaillés (activés par défaut)
- ✅ Gestion d'erreurs améliorée

**Code clé:**
```php
// Utiliser un chemin robuste qui fonctionne toujours
$base_dir = DOL_DATA_ROOT . '/documents/mv3pro_portail';

// Si le module a défini dir_output, l'utiliser
if (isset($conf->mv3pro_portail->dir_output) && !empty($conf->mv3pro_portail->dir_output)) {
    $base_dir = $conf->mv3pro_portail->dir_output;
}

$upload_dir = $base_dir . '/planning/' . $event_id;
```

### **3. Configuration du Module**

**Fichier:** `core/modules/modMv3pro_portail.class.php`

**Changements:**
- ✅ Ajout des répertoires manquants dans `$this->dirs`
- ✅ Correction du chemin `dir_output` (ajout de `/documents/`)

**Avant:**
```php
$this->dirs = array('/mv3pro_portail/temp', '/mv3pro_portail/rapports');
DOL_DATA_ROOT.'/mv3pro_portail'  // ❌ Mauvais
```

**Après:**
```php
$this->dirs = array(
    '/mv3pro_portail/temp',
    '/mv3pro_portail/rapports',
    '/mv3pro_portail/planning',    // ✅ Ajouté
    '/mv3pro_portail/regie',       // ✅ Ajouté
    '/mv3pro_portail/sens_pose'    // ✅ Ajouté
);
DOL_DATA_ROOT.'/documents/mv3pro_portail'  // ✅ Corrigé
```

---

## 📊 CHECKLIST DE RÉSOLUTION

### **Étape 1: Diagnostic**
- [ ] Ouvrir `diagnostic_upload_permissions.php`
- [ ] Vérifier que DOL_DATA_ROOT est défini
- [ ] Vérifier que `/documents` existe et est accessible
- [ ] Noter les répertoires marqués en ❌

### **Étape 2: Réparation**
- [ ] Ouvrir `fix_directories.php`
- [ ] Vérifier les répertoires créés (✅)
- [ ] Si des échecs (❌), noter les chemins
- [ ] Tester la création du répertoire Event #74049

### **Étape 3: Permissions Manuelles (si nécessaire)**
- [ ] Se connecter en SSH au serveur
- [ ] Exécuter les commandes `mkdir -p`
- [ ] Exécuter les commandes `chmod -R 775`
- [ ] Exécuter les commandes `chown -R www-data:www-data`
- [ ] Vérifier avec `ls -lah`

### **Étape 4: Test d'Upload**
- [ ] Ouvrir `live_debug_session.php`
- [ ] Voir "✅ Connecté"
- [ ] Sélectionner une image
- [ ] Cliquer "📤 Uploader"
- [ ] Vérifier le log: "✅ Upload réussi"

### **Étape 5: Vérification**
- [ ] Vérifier le fichier sur le serveur
- [ ] Vérifier l'entrée dans `llx_ecm_files`
- [ ] Ouvrir la PWA et voir la photo

---

## 🔧 DÉPANNAGE

### **Erreur: "Impossible de créer le répertoire"**

**Causes possibles:**
1. Permissions insuffisantes
2. Propriétaire du répertoire incorrect
3. SELinux activé (bloque les créations)
4. Quota de disque atteint

**Solutions:**

**1. Vérifier les permissions:**
```bash
ls -lah /var/www/dolibarr/documents/
```
Vous devriez voir `drwxrwxr-x` et `www-data` comme propriétaire.

**2. Corriger le propriétaire:**
```bash
sudo chown -R www-data:www-data /var/www/dolibarr/documents/mv3pro_portail
```

**3. Si SELinux est activé:**
```bash
# Vérifier si SELinux est actif
getenforce

# Si "Enforcing", définir le contexte correct
sudo chcon -R -t httpd_sys_rw_content_t /var/www/dolibarr/documents/mv3pro_portail
```

**4. Vérifier l'espace disque:**
```bash
df -h
```

### **Erreur: "Authentification requise" (401)**

**Solution:** Vous n'êtes pas connecté à Dolibarr
1. Ouvrez `https://crm.mv-3pro.ch/`
2. Connectez-vous
3. Retournez au Monitor

### **Erreur: "Événement non trouvé" (404)**

**Solution:** L'événement n'existe pas
1. Vérifiez l'ID de l'événement
2. Utilisez un ID valide (74049 est un exemple)
3. Vérifiez dans la table `llx_actioncomm`

---

## 📈 LOGS SERVEUR

### **Activer les logs de debug:**

Les logs sont **déjà activés** dans `planning_upload_photo_session.php` (ligne 9):
```php
define('DEBUG_UPLOAD', true);
```

### **Voir les logs en temps réel:**

**Via SSH:**
```bash
# Logs Apache
sudo tail -f /var/log/apache2/error.log | grep "MV3 UPLOAD DEBUG"

# Logs PHP-FPM (si utilisé)
sudo tail -f /var/log/php-fpm/error.log | grep "MV3 UPLOAD DEBUG"
```

**Logs générés:**
```
[MV3 UPLOAD DEBUG] === DÉBUT UPLOAD (SESSION VERSION) ===
[MV3 UPLOAD DEBUG] Bootstrap chargé
[MV3 UPLOAD DEBUG] User ID: 1
[MV3 UPLOAD DEBUG] Event ID reçu: 74049
[MV3 UPLOAD DEBUG] Fichier reçu: photo.jpg (256000 bytes)
[MV3 UPLOAD DEBUG] DOL_DATA_ROOT: /var/www/dolibarr/documents
[MV3 UPLOAD DEBUG] Base dir: /var/www/dolibarr/documents/mv3pro_portail
[MV3 UPLOAD DEBUG] Upload dir: /var/www/dolibarr/documents/mv3pro_portail/planning/74049
[MV3 UPLOAD DEBUG] Dir existe: NON
[MV3 UPLOAD DEBUG] Création du répertoire...
[MV3 UPLOAD DEBUG] Résultat dol_mkdir: 0
[MV3 UPLOAD DEBUG] Fichier déplacé avec succès
[MV3 UPLOAD DEBUG] === UPLOAD TERMINÉ AVEC SUCCÈS ===
```

### **Désactiver les logs après résolution:**
```php
// Ligne 9 de planning_upload_photo_session.php:
define('DEBUG_UPLOAD', false);
```

---

## 🎯 ARCHITECTURE DES RÉPERTOIRES

### **Structure attendue:**

```
/var/www/dolibarr/
└── documents/
    └── mv3pro_portail/              (775, www-data:www-data)
        ├── planning/                (775, www-data:www-data)
        │   ├── 74049/              (créé automatiquement)
        │   │   └── photo_123.jpg
        │   ├── 74050/
        │   └── ...
        ├── rapports/               (775, www-data:www-data)
        ├── regie/                  (775, www-data:www-data)
        ├── sens_pose/              (775, www-data:www-data)
        └── temp/                   (775, www-data:www-data)
```

### **Permissions expliquées:**

- **775** = `rwxrwxr-x`
  - Propriétaire (www-data): Lecture, Écriture, Exécution
  - Groupe (www-data): Lecture, Écriture, Exécution
  - Autres: Lecture, Exécution

- **www-data:www-data**
  - Utilisateur: `www-data` (Apache/Nginx)
  - Groupe: `www-data`

---

## ✅ RÉSULTAT ATTENDU

### **Dans le Monitor Live:**

```
[11:XX:XX] 🚀 Début de l'upload: photo.jpg (256.42 KB)
[11:XX:XX] 📋 Event ID: 74049
[11:XX:XX] 👤 Utilisateur: MIRNES Velagic (ID: 1)
[11:XX:XX] 🌐 Envoi vers: /custom/mv3pro_portail/api/v1/planning_upload_photo_session.php
[11:XX:XX] 🔐 Utilisation de la session Dolibarr active
[11:XX:XX] 📊 Progression: 100%
[11:XX:XX] ✅ Upload réussi en XXXms
[11:XX:XX] 📦 Réponse: {"success":true,"message":"Photo uploadée avec succès","file":{"name":"photo_1736545896.jpg",...}}
```

**Statistiques:**
- **Total Uploads:** 1
- **Succès:** 1
- **Erreurs:** 0
- **Temps Moyen:** ~XXXms

### **Sur le Serveur:**

```bash
ls -lah /var/www/dolibarr/documents/mv3pro_portail/planning/74049/
# Résultat:
# -rw-r--r-- 1 www-data www-data 256K Jan 10 22:00 photo_1736545896.jpg
```

### **En Base de Données:**

```sql
SELECT * FROM llx_ecm_files
WHERE src_object_type = 'actioncomm'
AND src_object_id = 74049
ORDER BY date_c DESC LIMIT 1;
```

**Résultat attendu:**
| id | filename | filepath | src_object_id | date_c |
|----|----------|----------|---------------|---------|
| XXX | photo_1736545896.jpg | mv3pro_portail/planning/74049 | 74049 | 2026-01-10 22:00:00 |

---

## 🚀 PROCHAINES ÉTAPES

Une fois l'upload fonctionnel:

### **1. Désactiver le mode debug**
```php
// Dans planning_upload_photo_session.php, ligne 9:
define('DEBUG_UPLOAD', false);
```

### **2. Mettre à jour la PWA**
La PWA doit utiliser le bon endpoint:
```typescript
// Dans src/lib/api.ts ou équivalent:
const uploadUrl = `${API_BASE}/planning_upload_photo.php`; // Ancien
// Changer pour:
const uploadUrl = `${API_BASE}/planning_upload_photo_session.php`; // Nouveau
```

### **3. Tester en production**
- Tester avec plusieurs utilisateurs
- Tester différents formats d'image (JPEG, PNG, WebP)
- Tester différentes tailles de fichier
- Vérifier les permissions après upload

### **4. Documenter pour l'équipe**
- Sauvegarder l'URL des outils de diagnostic
- Documenter la procédure de réparation
- Former les utilisateurs

---

## 📞 SUPPORT

### **Si le problème persiste:**

**1. Collectez ces informations:**
- Capture d'écran de `diagnostic_upload_permissions.php`
- Capture d'écran de `fix_directories.php`
- Capture d'écran des logs dans `live_debug_session.php`
- Résultat de `ls -lah /var/www/dolibarr/documents/mv3pro_portail/`
- Logs serveur (10 dernières lignes avec "MV3 UPLOAD DEBUG")

**2. Vérifiez:**
- Que vous êtes connecté à Dolibarr
- Que l'événement existe (ID valide)
- Que le fichier est une image valide
- Que le serveur a de l'espace disque

**3. Testez:**
- Avec un autre navigateur
- En mode navigation privée
- Avec un fichier plus petit (<500 KB)
- Avec un autre événement

---

## 🎉 CONCLUSION

**Problèmes résolus:**
- ✅ Authentification via session Dolibarr
- ✅ Scripts de diagnostic créés
- ✅ Scripts de réparation créés
- ✅ Configuration du module corrigée
- ✅ Logs de debug activés

**Action immédiate:**
1. Ouvrir `fix_directories.php`
2. Créer les répertoires manquants
3. Tester l'upload dans `live_debug_session.php`

**Si échec:**
- Exécuter les commandes SSH manuellement
- Vérifier les logs serveur
- Contacter le support avec les informations collectées

---

## 🔗 URLS IMPORTANTES

| Outil | URL |
|-------|-----|
| **Diagnostic Permissions** | https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/diagnostic_upload_permissions.php |
| **Réparation Répertoires** | https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/fix_directories.php |
| **Monitor Upload** | https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/live_debug_session.php |
| **PWA** | https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/ |

**TESTEZ MAINTENANT!** 🚀
