# Upload Planning Détail - Instructions

## Fichiers à uploader

Vous devez uploader ces fichiers sur votre serveur HostStar :

### 1. Backend API (2 nouveaux fichiers)

Uploader vers : `/htdocs/custom/mv3pro_portail/api/v1/`

**Fichiers :**
- `planning_view.php` (depuis `/new_dolibarr/mv3pro_portail/api/v1/planning_view.php`)
- `file.php` (depuis `/new_dolibarr/mv3pro_portail/api/v1/file.php`)

### 2. Frontend PWA (répertoire complet)

Uploader tout le contenu vers : `/htdocs/custom/mv3pro_portail/pwa_dist/`

**Répertoire source :** `/new_dolibarr/mv3pro_portail/pwa_dist/`

**Contient :**
- `index.html`
- `manifest.webmanifest`
- `registerSW.js`
- `sw.js`
- `workbox-1d305bb8.js`
- `assets/index-BQiQB-1j.css`
- `assets/index-DkhmSsOD.js` (⚠️ nouveau fichier JS avec le code du détail planning)
- `icon-192.png`
- `icon-512.png`

---

## Instructions via FileZilla ou FTP

### Étape 1 : Connexion FTP

1. Ouvrir FileZilla
2. Se connecter à votre serveur HostStar :
   - Hôte : `ftp.crm.mv-3pro.ch` (ou votre serveur FTP)
   - Utilisateur : votre login HostStar
   - Mot de passe : votre mot de passe HostStar
   - Port : 21 (FTP) ou 22 (SFTP)

### Étape 2 : Uploader les fichiers API

1. Naviguer vers : `/htdocs/custom/mv3pro_portail/api/v1/`
2. Uploader :
   - `planning_view.php`
   - `file.php`
3. Vérifier les permissions : 644 (rw-r--r--)

### Étape 3 : Uploader la PWA

1. Naviguer vers : `/htdocs/custom/mv3pro_portail/`
2. **Supprimer l'ancien répertoire** `pwa_dist/` (ou le renommer en `pwa_dist_old/`)
3. **Uploader le nouveau répertoire** `pwa_dist/` complet
4. Vérifier les permissions :
   - Répertoires : 755 (rwxr-xr-x)
   - Fichiers : 644 (rw-r--r--)

---

## Alternative : via ligne de commande (si accès SSH)

Si vous avez accès SSH à votre serveur :

```bash
# Se connecter en SSH
ssh user@crm.mv-3pro.ch

# Aller dans le répertoire du module
cd /htdocs/custom/mv3pro_portail/

# Sauvegarder l'ancien pwa_dist
mv pwa_dist pwa_dist_old

# Uploader le nouveau via SCP depuis votre machine locale
# (depuis votre machine locale, pas sur le serveur)
scp -r /path/to/new_dolibarr/mv3pro_portail/pwa_dist/ user@crm.mv-3pro.ch:/htdocs/custom/mv3pro_portail/

# Uploader les fichiers API
scp /path/to/new_dolibarr/mv3pro_portail/api/v1/planning_view.php user@crm.mv-3pro.ch:/htdocs/custom/mv3pro_portail/api/v1/
scp /path/to/new_dolibarr/mv3pro_portail/api/v1/file.php user@crm.mv-3pro.ch:/htdocs/custom/mv3pro_portail/api/v1/

# Vérifier les permissions
chmod 755 /htdocs/custom/mv3pro_portail/pwa_dist
chmod 644 /htdocs/custom/mv3pro_portail/pwa_dist/index.html
chmod 644 /htdocs/custom/mv3pro_portail/pwa_dist/assets/*
chmod 644 /htdocs/custom/mv3pro_portail/api/v1/planning_view.php
chmod 644 /htdocs/custom/mv3pro_portail/api/v1/file.php
```

---

## Vérification après upload

### Test 1 : Vérifier que les fichiers API existent

Dans votre navigateur, ouvrir la console (F12) et tester :

```javascript
// Tester planning_view.php
fetch('https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php?id=74049', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'X-Auth-Token': 'YOUR_TOKEN'
  }
})
.then(r => r.json())
.then(data => console.log('✅ planning_view.php OK:', data))
.catch(e => console.error('❌ planning_view.php ERROR:', e));
```

### Test 2 : Vérifier que la PWA est mise à jour

1. Ouvrir : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Vider le cache du navigateur (Ctrl+Shift+R ou Cmd+Shift+R sur Mac)
3. Se connecter avec Fernando
4. Aller dans Planning
5. Cliquer sur un rendez-vous
6. **Résultat attendu** : La page de détail s'affiche avec toutes les infos (projet, tiers, description, fichiers)

### Test 3 : Vérifier le téléchargement de fichiers

Si un événement a des fichiers joints :
1. Aller dans le détail de l'événement
2. Cliquer sur "Ouvrir" à côté d'un fichier
3. **Résultat attendu** : Le fichier s'ouvre dans un nouvel onglet

---

## Dépannage

### Erreur 404 sur planning_view.php

**Cause** : Le fichier n'a pas été uploadé ou est au mauvais endroit

**Solution** :
```bash
# Vérifier que le fichier existe
ls -la /htdocs/custom/mv3pro_portail/api/v1/planning_view.php

# Si absent, réuploader
```

### Erreur 403 (Permission denied)

**Cause** : Mauvaises permissions sur les fichiers

**Solution** :
```bash
chmod 644 /htdocs/custom/mv3pro_portail/api/v1/planning_view.php
chmod 644 /htdocs/custom/mv3pro_portail/api/v1/file.php
```

### La PWA affiche toujours "Erreur 404"

**Cause** : Le cache du navigateur n'est pas vidé

**Solution** :
1. Vider le cache : Ctrl+Shift+R (ou Cmd+Shift+R sur Mac)
2. Ou forcer le rechargement dans les DevTools :
   - Ouvrir F12
   - Clic droit sur le bouton Actualiser
   - Choisir "Vider le cache et effectuer une actualisation forcée"

### Les fichiers joints ne s'affichent pas

**Cause** : Les événements dans Dolibarr n'ont pas de fichiers joints

**Solution** :
1. Dans Dolibarr, ouvrir l'événement
2. Vérifier qu'il y a des fichiers dans l'onglet "Documents"
3. Si pas de fichiers, en ajouter un
4. Recharger la page de détail dans la PWA

---

## Checklist complète

- [ ] Backend : `planning_view.php` uploadé dans `/api/v1/`
- [ ] Backend : `file.php` uploadé dans `/api/v1/`
- [ ] Frontend : Tout le répertoire `pwa_dist/` uploadé
- [ ] Permissions : 644 sur les fichiers PHP
- [ ] Permissions : 644 sur les fichiers PWA
- [ ] Test : planning_view.php répond (pas de 404)
- [ ] Test : La PWA affiche le détail d'un événement
- [ ] Test : Les fichiers joints s'ouvrent
- [ ] Cache navigateur vidé

---

## Résumé des chemins

| Local | Serveur |
|-------|---------|
| `/new_dolibarr/mv3pro_portail/api/v1/planning_view.php` | `/htdocs/custom/mv3pro_portail/api/v1/planning_view.php` |
| `/new_dolibarr/mv3pro_portail/api/v1/file.php` | `/htdocs/custom/mv3pro_portail/api/v1/file.php` |
| `/new_dolibarr/mv3pro_portail/pwa_dist/*` | `/htdocs/custom/mv3pro_portail/pwa_dist/*` |

---

**Une fois uploadé, Fernando pourra :**
- Cliquer sur ses rendez-vous dans le planning
- Voir tous les détails (projet, client, lieu, description)
- Ouvrir les photos et PDF associés
- Avoir toutes les infos nécessaires pour son intervention

**Date** : 2026-01-09
