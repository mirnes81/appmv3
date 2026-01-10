# 🚀 GUIDE DÉPLOIEMENT URGENT - ÉTAPE PAR ÉTAPE

## ⚠️ SITUATION ACTUELLE

Le fichier corrigé **N'EST PAS encore sur le serveur**.

**Preuve** : L'erreur indique ligne 905 dans l'ancienne version.
Notre version corrigée a la fonction protégée à la ligne 904-913.

---

## 📦 FICHIER À UPLOADER

**Emplacement local** :
```
new_dolibarr/mv3pro_portail/api/v1/_bootstrap.php
```

**Destination serveur** :
```
/home/ch314761/web/crm.mv-3pro.ch/public_html/custom/mv3pro_portail/api/v1/_bootstrap.php
```

**Taille du fichier** : 31 Ko (31,744 bytes)
**Nombre de lignes** : 914 lignes

---

## 🔧 MÉTHODE 1 : Via FTP (FileZilla, WinSCP, etc.)

### Étape 1 : Connexion FTP
```
Hôte : ftp.mv-3pro.ch (ou votre serveur FTP)
Utilisateur : ch314761
Mot de passe : [votre mot de passe]
Port : 21 (ou 22 pour SFTP)
```

### Étape 2 : Navigation
```
Naviguer vers :
/home/ch314761/web/crm.mv-3pro.ch/public_html/custom/mv3pro_portail/api/v1/
```

### Étape 3 : Backup de l'ancien fichier (IMPORTANT)
```
1. Clic droit sur _bootstrap.php
2. Renommer en : _bootstrap.php.OLD
```

### Étape 4 : Upload du nouveau fichier
```
1. Glisser-déposer le fichier _bootstrap.php
   OU
2. Clic droit → Upload
3. Sélectionner : new_dolibarr/mv3pro_portail/api/v1/_bootstrap.php
```

### Étape 5 : Vérification
```
1. Vérifier la taille du fichier uploadé : ~31 Ko
2. Vérifier la date de modification : aujourd'hui
```

---

## 🔧 MÉTHODE 2 : Via SSH (Terminal)

### Si vous avez accès SSH :

```bash
# 1. Connexion SSH
ssh ch314761@mv-3pro.ch

# 2. Aller dans le dossier
cd /home/ch314761/web/crm.mv-3pro.ch/public_html/custom/mv3pro_portail/api/v1/

# 3. Backup de l'ancien fichier
cp _bootstrap.php _bootstrap.php.OLD

# 4. Éditer le fichier
nano _bootstrap.php
```

**PUIS** : Copier TOUT le contenu du nouveau fichier et coller dans nano
- **Sauvegarder** : Ctrl+O, Entrée
- **Quitter** : Ctrl+X

---

## 🔧 MÉTHODE 3 : Via le gestionnaire de fichiers cPanel

### Étape 1 : Connexion cPanel
```
URL : https://cpanel.votre-hebergeur.com
Utilisateur : ch314761
```

### Étape 2 : Gestionnaire de fichiers
```
1. Cliquer sur "Gestionnaire de fichiers"
2. Naviguer vers : public_html/custom/mv3pro_portail/api/v1/
```

### Étape 3 : Backup
```
1. Sélectionner _bootstrap.php
2. Clic droit → Renommer → _bootstrap.php.OLD
```

### Étape 4 : Upload
```
1. Cliquer sur "Télécharger" (Upload)
2. Sélectionner le nouveau _bootstrap.php
3. Attendre la fin du transfert
```

---

## ✅ VÉRIFICATION IMMÉDIATE APRÈS DÉPLOIEMENT

### Test 1 : Via navigateur

**Ouvrir** :
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php
```

**AVANT (avec bug)** :
```html
<br />
<b>Fatal error</b>: Cannot redeclare mv3_check_table_or_empty()
```

**APRÈS (corrigé)** :
```json
{
  "success": true,
  "debug_info": {
    "user_info": {
      "dolibarr_user_id": 20,
      "email": "fernando@mv-3pro.ch"
    },
    "total_rapports_in_entity": 0
  }
}
```

---

### Test 2 : Via cURL (Terminal)

```bash
curl -i https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php \
  -H "Cookie: DOLSESSID_mv3pro2=VOTRE_SESSION_ID"
```

**AVANT** : `HTTP/1.1 500 Internal Server Error`
**APRÈS** : `HTTP/1.1 200 OK`

---

### Test 3 : PWA (Application)

1. **Aller sur** : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
2. **Connexion** : fernando@mv-3pro.ch
3. **Aller sur** : Rapports
4. **Appuyer sur** : F12 (Console développeur)
5. **Appuyer sur** : Ctrl+Shift+R (vider le cache + recharger)

**AVANT** :
```
❌ Erreur: Cannot redeclare mv3_check_table_or_empty()
```

**APRÈS** :
```
✅ Panneau debug affiche : "Aucun rapport affiché"
✅ Pas d'erreur dans la console
```

---

## 🎯 CHECKLIST COMPLÈTE

Cochez au fur et à mesure :

- [ ] **Connexion établie** (FTP/SSH/cPanel)
- [ ] **Dossier trouvé** : `custom/mv3pro_portail/api/v1/`
- [ ] **Backup créé** : `_bootstrap.php.OLD`
- [ ] **Fichier uploadé** : `_bootstrap.php` (31 Ko)
- [ ] **Permissions vérifiées** : 644
- [ ] **Test navigateur** : rapports_debug.php → 200 OK
- [ ] **Test PWA** : Rapports → Plus d'erreur
- [ ] **Cache vidé** : Ctrl+Shift+R
- [ ] **Console développeur** : Aucune erreur rouge

---

## 🔍 DIAGNOSTIC SI ÇA NE MARCHE PAS

### Problème 1 : Toujours "Cannot redeclare"

**Vérifier la taille du fichier** :
```bash
ls -lh custom/mv3pro_portail/api/v1/_bootstrap.php
```

**Attendu** : ~31 Ko (31,744 bytes)

**Si différent** :
- Le fichier n'a pas été uploadé correctement
- Re-uploader le fichier en mode BINAIRE (pas ASCII)

---

### Problème 2 : Erreur 404 Not Found

**Vérifier le chemin** :
```
Chemin correct :
/public_html/custom/mv3pro_portail/api/v1/_bootstrap.php

PAS :
/custom/mv3pro_portail/api/v1/_bootstrap.php (sans public_html)
```

---

### Problème 3 : Erreur de permissions

**Corriger les permissions** :
```bash
chmod 644 custom/mv3pro_portail/api/v1/_bootstrap.php
```

---

## 📞 BESOIN D'AIDE ?

Si vous rencontrez un problème :

1. **Envoyez-moi** :
   - Le résultat de : `ls -lh custom/mv3pro_portail/api/v1/_bootstrap.php`
   - Une capture d'écran de l'erreur
   - Le résultat de : `curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php`

2. **Ou testez** :
   - Ouvrir le fichier uploadé dans un éditeur de texte
   - Vérifier que la ligne 14 contient : `if (defined('MV3_BOOTSTRAP_V1_LOADED'))`

---

## 🎉 RÉSULTAT ATTENDU FINAL

### Dans le panneau Debug de la PWA :

```
🔧 Panneau de Debug

👤 Informations Utilisateur
• Nom: Fernando test
• Email: fernando@mv-3pro.ch
• Dolibarr User ID: 20
• Mode: mobile_token
• Admin: ✅ OUI

✅ NOUVEAU SYSTÈME (corrigé)
N/A

📊 Statistiques Rapports
• Total dans l'entité: 0
• Visibles avec NOUVEAU filtre: 0

🌐 Dernier Appel API
• Endpoint: rapports.php
• Timestamp: 2026-01-10 19:45:23
• Réponse:
{
  "success": true,
  "data": {
    "items": [],
    "total": 0,
    "page": 1,
    "per_page": 50
  }
}

📱 Rapports Affichés dans la PWA
Total affiché: 0 / 0
⚠️ Aucun rapport affiché
```

**Plus d'erreur "Cannot redeclare"** ✅

---

## ⏱️ TEMPS DE DÉPLOIEMENT ESTIMÉ

- **Via FTP** : 3 minutes
- **Via SSH** : 5 minutes (si copier-coller)
- **Via cPanel** : 4 minutes

**Total** : Moins de 5 minutes pour régler le problème définitivement !

---

**Date** : 2026-01-10 19:40
**Fichier** : new_dolibarr/mv3pro_portail/api/v1/_bootstrap.php
**Taille** : 31,744 bytes
**Lignes** : 914
**Status** : ✅ PRÊT À DÉPLOYER
