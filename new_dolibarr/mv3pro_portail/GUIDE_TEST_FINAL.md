# 🧪 GUIDE DE TEST FINAL - PWA MV3 PRO

**Date:** 10 janvier 2026
**Version:** 3.0
**Statut:** ✅ PRÊT POUR TESTS EN PRODUCTION

---

## ✅ VÉRIFICATION PRÉALABLE

### **Architecture validée:**

```
new_dolibarr/mv3pro_portail/
├── api/v1/
│   ├── mv3_auth.php ✅ (middleware auth par token)
│   ├── _bootstrap.php ✅ (auth multi-mode: token prioritaire)
│   ├── planning_upload_photo.php ✅ (auth par token uniquement)
│   ├── object/
│   │   ├── get.php ✅ (auth par token)
│   │   ├── upload.php ✅ (auth par token)
│   │   └── file.php ✅ (auth par token)
│   ├── regie.php ✅ (via _bootstrap.php)
│   ├── sens_pose.php ✅ (via _bootstrap.php)
│   ├── materiel.php ✅ (via _bootstrap.php)
│   └── notifications.php ✅ (via _bootstrap.php)
└── pwa_dist/ ✅ (build DmJXHRZF)
```

### **Vérifications effectuées:**

- ✅ Aucune dépendance obligatoire à `$_SESSION` dans les endpoints token
- ✅ Tous les endpoints retournent du JSON standardisé
- ✅ Auth par token prioritaire sur session PHP
- ✅ Réponses HTTP codes cohérentes (200/400/401/403/500)
- ✅ Logging activable (mode debug)

---

## 🔐 TEST 1: AUTHENTIFICATION PAR TOKEN

### **Objectif:** Vérifier que le login PWA génère un token valide

### **Commande:**

```bash
curl -X POST "https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/api/auth.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"email":"votre@email.com","password":"votre_mot_de_passe"}'
```

### **Résultat attendu:**

```json
{
  "success": true,
  "token": "abc123def456...",
  "user": {
    "id": 1,
    "email": "votre@email.com",
    "firstname": "John",
    "lastname": "Doe",
    "dolibarr_user_id": 42
  }
}
```

### **Validation:**

- ✅ `success` = `true`
- ✅ `token` présent (chaîne longue)
- ✅ `user.dolibarr_user_id` présent (> 0)

### **Si erreur:**

**401 - Identifiants incorrects:**
```json
{
  "success": false,
  "error": "Invalid credentials"
}
```

**403 - Compte non lié:**
```json
{
  "success": false,
  "error": "ACCOUNT_NOT_LINKED",
  "message": "Compte non lié à un utilisateur Dolibarr"
}
```

---

## 📤 TEST 2: UPLOAD PHOTO AVEC TOKEN

### **Objectif:** Vérifier que l'upload fonctionne SANS session PHP

### **Prérequis:**

1. Récupérer le token du TEST 1
2. Avoir un event_id valide (ex: 74049)
3. Avoir une image test (photo.jpg)

### **Commande:**

```bash
TOKEN="abc123def456..."  # Token du TEST 1
EVENT_ID=74049           # ID d'un événement existant

curl -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Auth-Token: $TOKEN" \
  -F "event_id=$EVENT_ID" \
  -F "file=@photo.jpg" \
  "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_upload_photo.php"
```

### **Résultat attendu:**

```json
{
  "success": true,
  "message": "Photo uploadée avec succès",
  "event_id": 74049,
  "file": {
    "id": 1234,
    "name": "photo_1736524800.jpg",
    "original_name": "photo.jpg",
    "size": 123456,
    "mime_type": "image/jpeg",
    "url": "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&filename=photo_1736524800.jpg"
  }
}
```

### **Validation:**

- ✅ `success` = `true`
- ✅ `file.name` présent
- ✅ `file.url` accessible
- ✅ HTTP code = 201

### **Erreurs possibles:**

**401 - Token invalide:**
```json
{
  "success": false,
  "error": "UNAUTHORIZED",
  "message": "Non authentifié. Token manquant ou invalide."
}
```

**403 - Compte non lié:**
```json
{
  "success": false,
  "error": "ACCOUNT_NOT_LINKED",
  "message": "Votre compte n'est pas lié à un utilisateur Dolibarr"
}
```

**404 - Événement non trouvé:**
```json
{
  "success": false,
  "error": "EVENT_NOT_FOUND",
  "message": "Événement non trouvé ou accès refusé"
}
```

**413 - Fichier trop volumineux:**
```json
{
  "success": false,
  "error": "FILE_TOO_LARGE",
  "message": "Fichier trop volumineux. Maximum: 10 MB"
}
```

**415 - Type de fichier incorrect:**
```json
{
  "success": false,
  "error": "INVALID_FILE_TYPE",
  "message": "Type de fichier non autorisé. Seules les images sont acceptées (JPEG, PNG, GIF, WebP)"
}
```

---

## 🔍 TEST 3: RÉCUPÉRER UN OBJET AVEC FICHIERS

### **Objectif:** Vérifier que l'API générique retourne les fichiers uploadés

### **Commande:**

```bash
TOKEN="abc123def456..."
EVENT_ID=74049

curl -H "Authorization: Bearer $TOKEN" \
  -H "X-Auth-Token: $TOKEN" \
  "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/object/get.php?type=actioncomm&id=$EVENT_ID"
```

### **Résultat attendu:**

```json
{
  "success": true,
  "id": 74049,
  "type": "actioncomm",
  "label": "RDV Client ABC",
  "files": [
    {
      "id": 1234,
      "name": "photo_1736524800.jpg",
      "size": 123456,
      "mime_type": "image/jpeg",
      "date": "2026-01-10 14:30:00",
      "url": "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/object/file.php?type=actioncomm&id=74049&filename=photo_1736524800.jpg"
    }
  ],
  "extrafields": { ... }
}
```

### **Validation:**

- ✅ `files` contient la photo uploadée
- ✅ `files[0].url` accessible
- ✅ HTTP code = 200

---

## 📋 TEST 4: ENDPOINTS MÉTIER

### **Objectif:** Vérifier que tous les endpoints retournent des données JSON valides

### **A. Régie**

```bash
TOKEN="abc123def456..."

curl -H "Authorization: Bearer $TOKEN" \
  -H "X-Auth-Token: $TOKEN" \
  "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/regie.php"
```

**Résultat attendu:**
```json
{
  "success": true,
  "regies": [
    {
      "id": 1,
      "ref": "REG-2026-001",
      "status": 1,
      "status_label": "Validé",
      "date_regie": "2026-01-10",
      "project": { ... },
      "total_ttc": 1500.00
    }
  ],
  "total": 10,
  "limit": 50,
  "offset": 0
}
```

### **B. Sens de Pose**

```bash
curl -H "Authorization: Bearer $TOKEN" \
  -H "X-Auth-Token: $TOKEN" \
  "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/sens_pose.php"
```

**Résultat attendu:**
```json
{
  "success": true,
  "sens_pose": [
    {
      "id": 1,
      "ref": "SP-2026-001",
      "date": "2026-01-10",
      "projet": { ... }
    }
  ]
}
```

### **C. Matériel**

```bash
curl -H "Authorization: Bearer $TOKEN" \
  -H "X-Auth-Token: $TOKEN" \
  "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/materiel.php"
```

**Résultat attendu:**
```json
{
  "success": true,
  "materiel": [
    {
      "id": 1,
      "ref": "MAT-001",
      "label": "Perceuse",
      "type": "outils",
      "status": 1
    }
  ]
}
```

### **D. Notifications**

```bash
curl -H "Authorization: Bearer $TOKEN" \
  -H "X-Auth-Token: $TOKEN" \
  "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/notifications.php"
```

**Résultat attendu:**
```json
{
  "success": true,
  "notifications": [
    {
      "id": 1,
      "titre": "Nouveau rapport",
      "message": "Un nouveau rapport a été créé",
      "type": "rapport_new",
      "is_read": 0,
      "date": "2026-01-10 14:30:00",
      "url": "#/rapports/42",
      "icon": "file-text",
      "color": "blue"
    }
  ],
  "count": 5,
  "total_unread": 3
}
```

### **Validation globale:**

- ✅ Tous retournent `{"success": true, ...}`
- ✅ Aucune erreur 501 (Not Implemented)
- ✅ Aucune erreur PHP brute affichée
- ✅ HTTP code = 200

---

## 🐛 TEST 5: MODE DEBUG

### **Objectif:** Vérifier que le logging fonctionne

### **Activer le mode debug:**

**Option 1: Variable d'environnement**
```bash
echo "putenv('MV3_DEBUG=1');" >> /var/www/dolibarr/custom/mv3pro_portail/api/v1/planning_upload_photo.php
```

**Option 2: Constante PHP**
```php
// Au début de planning_upload_photo.php
define('MV3_DEBUG', true);
```

**Option 3: Configuration Dolibarr**
```sql
INSERT INTO llx_const (name, value, type, entity)
VALUES ('MV3_DEBUG', '1', 'chaine', 1)
ON DUPLICATE KEY UPDATE value = '1';
```

### **Lancer un upload:**

```bash
TOKEN="abc123def456..."
curl -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Auth-Token: $TOKEN" \
  -F "event_id=74049" \
  -F "file=@photo.jpg" \
  "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_upload_photo.php"
```

### **Consulter les logs:**

```bash
tail -f /var/www/dolibarr/documents/mv3pro_portail/logs/api.log
```

### **Résultat attendu:**

```
[2026-01-10 14:30:15] === MV3 Auth Start ===
[2026-01-10 14:30:15] Token trouvé: abc123def456...
[2026-01-10 14:30:15] SQL: SELECT u.rowid, u.email, ...
[2026-01-10 14:30:15] Mobile user trouvé: ID=1, Email=test@example.com
[2026-01-10 14:30:15] Utilisateur Dolibarr chargé: ID=42, Login=testuser
[2026-01-10 14:30:15] Auth SUCCESS via token
```

### **Validation:**

- ✅ Log créé dans `documents/mv3pro_portail/logs/api.log`
- ✅ Contient les étapes d'authentification
- ✅ Format lisible

---

## 🚀 TEST 6: PWA EN CONDITIONS RÉELLES

### **Objectif:** Tester depuis un téléphone réel

### **Étapes:**

**1. Force Reload PWA**

Ouvrir sur téléphone:
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/FORCE_RELOAD.html
```

Cliquer: **🚀 Forcer la mise à jour**

Attendre 3 secondes → Redirection automatique

**2. Connexion**

- Ouvrir PWA
- Se connecter avec email/password
- Vérifier que le token est stocké:
  - Ouvrir DevTools (si possible)
  - Console: `localStorage.getItem('mv3pro_token')`
  - Doit retourner le token

**3. Upload photo depuis Planning**

- Aller dans Planning
- Ouvrir un RDV
- Cliquer sur "Ajouter photo"
- Prendre ou sélectionner une photo
- Appuyer sur "Valider"

**Résultat attendu:**
- ✅ Photo uploadée en 1-3 secondes
- ✅ Photo apparaît dans la galerie
- ✅ Pas d'erreur 401/403/500
- ✅ Message de succès affiché

**4. Vérifier les autres pages:**

- **Notifications:** Affiche la liste (vide ou remplie)
- **Régie:** Affiche la liste (vide ou remplie)
- **Matériel:** Affiche la liste (vide ou remplie)
- **Sens de Pose:** Affiche la liste (vide ou remplie)

**Ce qui ne doit PAS arriver:**
- ❌ Erreur "Endpoint non disponible (501)"
- ❌ Écran blanc
- ❌ Erreur PHP affichée
- ❌ Redirect vers login en boucle

---

## 📊 CHECKLIST FINALE

### **Backend:**

- [ ] Helper `mv3_auth.php` créé et fonctionnel
- [ ] `planning_upload_photo.php` accepte le token (pas de session obligatoire)
- [ ] Tous les endpoints `object/*.php` utilisent le token
- [ ] Endpoints métier créés: `regie.php`, `sens_pose.php`, `materiel.php`
- [ ] `notifications.php` fonctionnel
- [ ] Aucune erreur 501
- [ ] Réponses JSON standardisées partout
- [ ] Logging activable (mode debug)
- [ ] Permissions dossiers OK (`documents/mv3pro_portail/`)

### **Tests:**

- [ ] TEST 1: Login retourne un token ✅
- [ ] TEST 2: Upload photo avec token fonctionne ✅
- [ ] TEST 3: API object/get retourne les fichiers ✅
- [ ] TEST 4: Tous les endpoints métier retournent JSON ✅
- [ ] TEST 5: Mode debug log dans api.log ✅
- [ ] TEST 6: PWA fonctionne sur téléphone réel ✅

### **Documentation:**

- [ ] `PWA_AUTH_FIX_COMPLETE.md` à jour
- [ ] `GUIDE_TEST_FINAL.md` (ce fichier) créé
- [ ] `RESUME_AUTHENTIFICATION_PWA.txt` créé

---

## 🆘 DÉPANNAGE RAPIDE

### **Erreur: 401 Unauthorized**

**Cause:** Token manquant ou invalide

**Solution:**
1. Vérifier que le token est dans localStorage
2. Se reconnecter (login à nouveau)
3. Forcer reload PWA (FORCE_RELOAD.html)

### **Erreur: 403 ACCOUNT_NOT_LINKED**

**Cause:** Utilisateur PWA non lié à Dolibarr

**Solution:**
1. Admin → Configuration → Utilisateurs mobiles
2. Lier l'utilisateur à un compte Dolibarr

### **Erreur: Upload échoue (500)**

**Cause:** Permissions dossiers ou taille fichier

**Solution:**
```bash
# Vérifier permissions
ls -la /var/www/dolibarr/documents/mv3pro_portail/planning/

# Corriger si nécessaire
chmod 755 /var/www/dolibarr/documents/mv3pro_portail/planning/
chown www-data:www-data /var/www/dolibarr/documents/mv3pro_portail/planning/

# Vérifier taille max upload PHP
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

### **Erreur: Table manquante**

**Cause:** Tables SQL non créées

**Solution:**
```bash
cd /var/www/dolibarr/custom/mv3pro_portail/sql/
mysql -u root -p dolibarr < llx_mv3_mobile_users.sql
mysql -u root -p dolibarr < llx_mv3_notifications.sql
# etc.
```

---

## 📞 SUPPORT

**Logs:**
```bash
# API logs
tail -f /var/www/dolibarr/documents/mv3pro_portail/logs/api.log

# Apache logs
tail -f /var/log/apache2/error.log

# PHP logs
tail -f /var/log/php/error.log
```

**Debug mode:**
- Ajouter `?debug=1` à l'URL
- Ou header: `X-MV3-Debug: 1`
- Consulter les logs

---

**Version:** 3.0
**Build:** DmJXHRZF
**Date:** 10 janvier 2026

**🎯 OBJECTIF:** Tous les tests doivent passer ✅
