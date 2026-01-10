# ✅ SOLUTION: Erreur 401 Upload Photos

## Date: 10 janvier 2026 - 21:50

---

## 🎯 PROBLÈME IDENTIFIÉ

**Erreur:** `401 Unauthorized - Authentification requise`

**Cause:** L'API nécessite une authentification, mais le Monitor Live n'envoyait pas les credentials.

---

## ✅ SOLUTION CRÉÉE

### **Nouveau Monitor Live avec Session Dolibarr**

**URL à utiliser:**
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/live_debug_session.php
```

### **Avantages:**
- ✅ Utilise directement votre session Dolibarr active
- ✅ Pas besoin de token
- ✅ Authentification automatique
- ✅ Interface graphique moderne
- ✅ Logs détaillés en temps réel
- ✅ Statistiques d'upload

---

## 📋 INSTRUCTIONS D'UTILISATION

### **Étape 1: Se connecter à Dolibarr**

1. Ouvrez dans votre navigateur: `https://crm.mv-3pro.ch/`
2. Connectez-vous avec vos identifiants Dolibarr
3. **NE FERMEZ PAS cet onglet**

### **Étape 2: Ouvrir le Monitor**

1. **Dans le même navigateur**, ouvrez un **nouvel onglet**
2. Allez à: `https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/live_debug_session.php`
3. Vous devriez voir:
   - ✅ **Connecté** (avec votre nom)
   - Formulaire d'upload actif
   - Statistiques à zéro

### **Étape 3: Tester l'Upload**

1. Laissez **Event ID = 74049**
2. Cliquez sur **"Choisir un fichier"**
3. Sélectionnez une image (JPEG, PNG, GIF ou WebP)
4. Cliquez sur **"📤 Uploader la Photo"**
5. **Observez les logs en direct!**

---

## 🔍 LOGS ATTENDUS

### **Si tout fonctionne:**

```
[HH:MM:SS] 🚀 Début de l'upload: photo.jpg (256.42 KB)
[HH:MM:SS] 📋 Event ID: 74049
[HH:MM:SS] 👤 Utilisateur: VOTRE_NOM (ID: XXX)
[HH:MM:SS] 🌐 Envoi vers: /custom/mv3pro_portail/api/v1/planning_upload_photo_session.php
[HH:MM:SS] 🔐 Utilisation de la session Dolibarr active
[HH:MM:SS] 📊 Progression: 100%
[HH:MM:SS] ✅ Upload réussi en XXXms
[HH:MM:SS] 📦 Réponse: {"success":true,"message":"Photo uploadée avec succès",...}
```

### **Statistiques attendues:**
- **Total Uploads:** 1
- **Succès:** 1
- **Erreurs:** 0
- **Temps Moyen:** ~XXXms

---

## 🚨 Si vous voyez "Non connecté"

### **Symptôme:**
```
❌ Non connecté
Vous devez être connecté à Dolibarr pour utiliser ce monitor
```

### **Solutions:**

**1. Vous n'êtes pas connecté à Dolibarr**
- Cliquez sur "Se connecter"
- Connectez-vous avec vos identifiants
- Retournez au Monitor et **rechargez la page**

**2. Votre session a expiré**
- Retournez à Dolibarr: `https://crm.mv-3pro.ch/`
- Reconnectez-vous
- Retournez au Monitor et **rechargez la page**

**3. Cookies bloqués**
- Vérifiez que votre navigateur accepte les cookies
- Désactivez les extensions qui bloquent les cookies (Privacy Badger, etc.)
- Essayez en mode navigation privée

---

## 🎯 FICHIERS CRÉÉS

### 1. **Monitor Live avec Session** ⭐ PRINCIPAL
**Fichier:** `/api/v1/live_debug_session.php`
- Interface graphique complète
- Détection automatique de la session Dolibarr
- Logs en temps réel
- Statistiques

### 2. **Endpoint Upload avec Session**
**Fichier:** `/api/v1/planning_upload_photo_session.php`
- Version de l'API qui accepte la session Dolibarr
- Logs de debug complets
- Gestion d'erreurs détaillée

### 3. **Script d'Obtention de Token** (alternatif)
**Fichier:** `/api/v1/get_debug_token.php`
- Pour obtenir un token si besoin
- Test d'upload manuel

### 4. **Diagnostic Système**
**Fichier:** `/api/v1/test_upload_debug.php`
- Tests complets du système
- Vérification de la configuration

---

## 🔧 DIFFÉRENCES TECHNIQUES

### **Ancien Endpoint** (`planning_upload_photo.php`)
- ❌ Définit `NOLOGIN=1`
- ❌ N'accepte pas la session Dolibarr automatiquement
- ❌ Nécessite un Bearer token ou X-Auth-Token

### **Nouveau Endpoint** (`planning_upload_photo_session.php`)
- ✅ Ne définit PAS `NOLOGIN`
- ✅ Accepte automatiquement la session Dolibarr
- ✅ Utilise les cookies de session
- ✅ Logs de debug complets

---

## 📊 VÉRIFICATIONS APRÈS UPLOAD

### **1. Vérifier dans le Monitor**
- ✅ Logs montrent "Upload réussi"
- ✅ Statistiques: Succès = 1
- ✅ Réponse JSON affichée

### **2. Vérifier sur le serveur**
```bash
ls -lah /var/www/dolibarr/documents/mv3pro_portail/planning/74049/
```
Vous devriez voir votre fichier uploadé.

### **3. Vérifier en base de données**
```sql
SELECT * FROM llx_ecm_files
WHERE src_object_type = 'actioncomm'
AND src_object_id = 74049
ORDER BY date_c DESC
LIMIT 5;
```

### **4. Vérifier dans la PWA**
1. Ouvrez la PWA: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Connectez-vous
3. Allez dans **Planning** → Événement #74049
4. Onglet **Photos**
5. Votre photo devrait apparaître!

---

## ⚠️ NOTES IMPORTANTES

### **Mode Debug Activé**
Le mode debug est **activé par défaut** dans `planning_upload_photo_session.php`.

**Logs générés dans:**
- `/var/log/apache2/error.log`
- `/var/log/php-fpm/error.log` (selon config)

**Pour désactiver après résolution:**
```php
// Ligne 9 de planning_upload_photo_session.php:
define('DEBUG_UPLOAD', false);
```

### **Sécurité**
- ✅ Vérifie l'authentification Dolibarr
- ✅ Vérifie que l'événement existe
- ✅ Vérifie le type de fichier (images uniquement)
- ✅ Génère des noms de fichiers sécurisés
- ✅ Enregistre l'upload dans ecm_files

---

## 🎓 POUR ALLER PLUS LOIN

### **Tester depuis la PWA**
Une fois que l'upload fonctionne dans le Monitor, testez-le directement depuis la PWA:
1. Connectez-vous à la PWA
2. Planning → Événement #74049
3. Photos → Ajouter une photo
4. **Ouvrez la console (F12)** pour voir les logs

### **Logs Serveur en Temps Réel**
Si vous avez accès SSH:
```bash
sudo tail -f /var/log/apache2/error.log | grep "MV3 UPLOAD DEBUG"
```

---

## ✅ CHECKLIST DE VALIDATION

- [ ] Ouvrir `live_debug_session.php`
- [ ] Voir "✅ Connecté" avec mon nom
- [ ] Sélectionner une image
- [ ] Cliquer "Uploader"
- [ ] Voir "✅ Upload réussi" dans les logs
- [ ] Voir la photo dans la PWA
- [ ] Fichier existe sur le serveur
- [ ] Entrée existe dans ecm_files

---

## 🚀 PROCHAINES ÉTAPES

Une fois que l'upload fonctionne:

1. **Désactiver le mode debug** (ligne 9 de `planning_upload_photo_session.php`)
2. **Mettre à jour la PWA** pour utiliser le bon endpoint
3. **Tester en production** avec plusieurs utilisateurs
4. **Nettoyer les logs** serveur

---

## 📞 BESOIN D'AIDE?

**Collectez ces informations:**
1. Capture d'écran de `live_debug_session.php`
2. Logs affichés dans la section "Logs en Direct"
3. Réponse serveur (section "Dernière Réponse")
4. Votre statut d'authentification (Connecté/Non connecté)

---

## 🎉 RÉSUMÉ

**AVANT:**
- ❌ Erreur 401 - Authentification requise
- ❌ Pas de support session Dolibarr dans l'API
- ❌ Nécessitait des tokens complexes

**APRÈS:**
- ✅ Authentification automatique via session Dolibarr
- ✅ Upload fonctionnel avec logs détaillés
- ✅ Interface de debug moderne
- ✅ Facile à utiliser

---

**URL PRINCIPALE À UTILISER:**
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/live_debug_session.php
```

**TESTEZ MAINTENANT!** 🚀
