# Guide - Ouverture de fichiers sécurisée dans la PWA

## Objectif

Fernando peut maintenant ouvrir les PDF, photos et plans directement dans son navigateur depuis la PWA, sans avoir besoin de se connecter à Dolibarr.

## Fonctionnalités implémentées

### 1. Backend - Endpoint détail événement
**Fichier** : `/api/v1/planning_view.php`

Retourne pour un événement :
- Informations de base (titre, dates, lieu, description)
- Utilisateur assigné
- Société/Client
- Projet
- Objet lié (commande, facture, etc.)
- **Liste des fichiers joints** avec :
  - Nom du fichier
  - Taille (en octets et format lisible)
  - Type MIME
  - Indicateur image (is_image)
  - URL de téléchargement sécurisée

**Exemple d'appel** :
```
GET /custom/mv3pro_portail/api/v1/planning_view.php?id=74049
Authorization: Bearer TOKEN
```

**Exemple de réponse** :
```json
{
  "success": true,
  "id": 74049,
  "titre": "Installation carrelage",
  "projet": {
    "id": 123,
    "ref": "PROJ-2024-001",
    "titre": "Rénovation cuisine"
  },
  "societe": {
    "id": 45,
    "nom": "Maison Dupont"
  },
  "fichiers": [
    {
      "name": "plan-cuisine.pdf",
      "size": 245678,
      "size_human": "239.92 KB",
      "mime": "application/pdf",
      "is_image": false,
      "url": "/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=plan-cuisine.pdf"
    },
    {
      "name": "photo-existant.jpg",
      "size": 1234567,
      "size_human": "1.18 MB",
      "mime": "image/jpeg",
      "is_image": true,
      "url": "/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=photo-existant.jpg"
    }
  ]
}
```

### 2. Backend - Endpoint streaming fichier sécurisé
**Fichier** : `/api/v1/planning_file.php`

Stream un fichier de manière sécurisée avec :
- Vérification du token PWA
- Contrôle des droits d'accès :
  - **Admin** : accès total à tous les fichiers
  - **Employee** : accès uniquement si assigné à l'événement
- Headers pour ouvrir dans le navigateur (inline, pas téléchargement)
- Support CORS pour la PWA

**Exemple d'appel** :
```
GET /custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=plan-cuisine.pdf
Authorization: Bearer TOKEN
X-Auth-Token: TOKEN
```

**Réponse** :
- Headers HTTP :
  - `Content-Type: application/pdf` (ou image/jpeg, etc.)
  - `Content-Disposition: inline; filename="plan-cuisine.pdf"`
  - `Content-Length: 245678`
- Corps : contenu binaire du fichier

### 3. Frontend - Affichage et ouverture des fichiers
**Fichier** : `/pwa/src/pages/PlanningDetail.tsx`

Modifications :
- Appelle `planning_view.php` pour récupérer les détails et fichiers
- Affiche la liste des fichiers avec :
  - Icône selon le type (🖼️ pour images, 📕 pour PDF, 📄 pour autres)
  - Nom du fichier
  - Taille lisible
  - Bouton "Ouvrir"
- Fonction `openFile` qui :
  1. Fait un fetch avec le token dans les headers
  2. Récupère le fichier en tant que blob
  3. Crée une URL temporaire avec `URL.createObjectURL`
  4. Ouvre l'URL dans un nouvel onglet

**Avantage** : Le token n'est jamais exposé dans l'URL, il reste dans les headers HTTP.

## Fichiers à uploader sur le serveur

### Backend API (2 fichiers)
Uploader vers : `/htdocs/custom/mv3pro_portail/api/v1/`

1. **planning_view.php** (mis à jour)
   - Source : `/new_dolibarr/mv3pro_portail/api/v1/planning_view.php`
   - Récupère les détails + fichiers d'un événement

2. **planning_file.php** (nouveau)
   - Source : `/new_dolibarr/mv3pro_portail/api/v1/planning_file.php`
   - Stream les fichiers de manière sécurisée

### Frontend PWA (répertoire complet)
Uploader vers : `/htdocs/custom/mv3pro_portail/pwa_dist/`

**Important** : Tout le répertoire `pwa_dist/` a été recompilé avec le nouveau code

**Contient** :
- `index.html`
- `manifest.webmanifest`
- `registerSW.js`
- `sw.js`
- `workbox-1d305bb8.js`
- `assets/index-BQiQB-1j.css`
- `assets/index-Ctvf43r6.js` (⚠️ nouveau build avec le code de streaming sécurisé)
- `icon-192.png`
- `icon-512.png`

## Instructions d'upload via FileZilla

### Étape 1 : Connexion FTP
1. Ouvrir FileZilla
2. Se connecter à votre serveur HostStar
3. Naviguer vers `/htdocs/custom/mv3pro_portail/`

### Étape 2 : Backend API
1. Aller dans `/htdocs/custom/mv3pro_portail/api/v1/`
2. Uploader :
   - `planning_view.php` (remplace l'ancien)
   - `planning_file.php` (nouveau)
3. Vérifier permissions : 644 (rw-r--r--)

### Étape 3 : Frontend PWA
1. Aller dans `/htdocs/custom/mv3pro_portail/`
2. **Renommer** l'ancien `pwa_dist/` en `pwa_dist_old/` (backup)
3. **Uploader** le nouveau répertoire `pwa_dist/` complet
4. Vérifier permissions :
   - Répertoires : 755 (rwxr-xr-x)
   - Fichiers : 644 (rw-r--r--)

## Vérification après upload

### Test 1 : Vérifier l'endpoint planning_view.php

Ouvrir la console du navigateur (F12) et exécuter :

```javascript
const token = localStorage.getItem('mv3pro_token');

fetch('https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php?id=74049', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'X-Auth-Token': token
  }
})
.then(r => r.json())
.then(data => {
  console.log('✅ planning_view.php OK');
  console.log('Événement:', data.titre);
  console.log('Fichiers trouvés:', data.fichiers.length);
  console.log('Fichiers:', data.fichiers);
})
.catch(e => console.error('❌ Erreur:', e));
```

**Résultat attendu** : Affiche les détails de l'événement et la liste des fichiers

### Test 2 : Vérifier l'endpoint planning_file.php

```javascript
const token = localStorage.getItem('mv3pro_token');

// Remplacer par l'URL d'un vrai fichier récupéré dans le test 1
const fileUrl = 'https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=xxx.pdf';

fetch(fileUrl, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'X-Auth-Token': token
  }
})
.then(r => {
  console.log('✅ planning_file.php OK - Status:', r.status);
  console.log('Content-Type:', r.headers.get('Content-Type'));
  return r.blob();
})
.then(blob => {
  console.log('✅ Fichier reçu:', blob.size, 'octets');
  const url = URL.createObjectURL(blob);
  window.open(url, '_blank');
})
.catch(e => console.error('❌ Erreur:', e));
```

**Résultat attendu** : Le fichier s'ouvre dans un nouvel onglet

### Test 3 : Vérifier dans la PWA

1. Se connecter à la PWA : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Aller dans **Planning**
3. Cliquer sur un rendez-vous qui a des fichiers joints
4. **Résultat attendu** :
   - La page de détail affiche :
     - Titre de l'événement
     - Projet (si lié)
     - Client (si lié)
     - Lieu, date, description
     - Section "Fichiers joints" avec la liste des fichiers
   - Chaque fichier a :
     - Une icône (🖼️ pour images, 📕 pour PDF)
     - Le nom du fichier
     - La taille
     - Un bouton "Ouvrir"
5. Cliquer sur **"Ouvrir"** à côté d'un fichier
6. **Résultat attendu** : Le fichier s'ouvre dans un nouvel onglet du navigateur

## Utilisation pour Fernando

### Scénario d'usage typique

1. **Matin** : Fernando ouvre la PWA sur son téléphone
2. Il va dans **Planning** pour voir ses rendez-vous du jour
3. Il clique sur son premier rendez-vous "Installation carrelage"
4. La page de détail s'affiche avec :
   - Client : Maison Dupont
   - Projet : Rénovation cuisine
   - Lieu : 123 rue de la Paix, Genève
   - **3 fichiers joints** :
     - Plan de pose (PDF)
     - Photo de l'existant (JPG)
     - Liste des matériaux (PDF)
5. Fernando clique sur **"Ouvrir"** à côté du "Plan de pose"
6. Le PDF s'ouvre directement dans le navigateur de son téléphone
7. Il peut zoomer, défiler, et voir tous les détails du plan
8. Il ferme l'onglet et revient à la page de détail
9. Il clique sur la "Photo de l'existant" pour la voir
10. L'image s'affiche en plein écran dans le navigateur

**Avantage** : Fernando n'a pas besoin de se connecter à Dolibarr, tout est accessible directement depuis la PWA avec son token mobile.

## Sécurité

### Contrôle d'accès

**Admin** (par exemple, vous) :
- Accès total à tous les fichiers de tous les événements

**Employee** (par exemple, Fernando) :
- Accès uniquement aux fichiers des événements qui lui sont assignés
- Si Fernando essaie d'accéder au fichier d'un événement assigné à quelqu'un d'autre → **Erreur 403 (Accès refusé)**

### Protection du token

- Le token PWA n'est **jamais** exposé dans l'URL
- Il est transmis uniquement via les headers HTTP :
  - `Authorization: Bearer TOKEN`
  - `X-Auth-Token: TOKEN`
- Les fichiers sont streamés avec `Content-Disposition: inline` pour s'ouvrir dans le navigateur
- Les URLs temporaires créées avec `URL.createObjectURL` n'exposent pas le token

### Logs

Les accès aux fichiers sont loggés avec :
- ID de l'événement
- Nom du fichier
- ID de l'utilisateur
- Type d'accès (admin ou employee)

Exemple de log :
```
[DEBUG] Streaming file: plan-cuisine.pdf (application/pdf) for event #74049 to user 123
[DEBUG] Planning file #74049 - Employee access granted (assigned user)
```

## Dépannage

### Erreur 404 sur planning_view.php

**Cause** : Le fichier n'a pas été uploadé

**Solution** :
```bash
# Vérifier que le fichier existe
ls -la /htdocs/custom/mv3pro_portail/api/v1/planning_view.php

# Si absent, réuploader
```

### Erreur 404 sur planning_file.php

**Cause** : Le fichier n'a pas été uploadé

**Solution** :
```bash
# Vérifier que le fichier existe
ls -la /htdocs/custom/mv3pro_portail/api/v1/planning_file.php

# Si absent, réuploader
```

### Erreur 403 (Accès refusé) sur un fichier

**Cause** : L'utilisateur n'est pas assigné à l'événement

**Solution** :
- Dans Dolibarr, ouvrir l'événement
- Vérifier que l'utilisateur est bien assigné dans le champ "Affecté à"
- Si ce n'est pas le cas, modifier l'événement pour l'assigner

### Le fichier ne s'ouvre pas dans le navigateur

**Cause** : Problème de streaming ou de blob

**Solution** :
1. Ouvrir la console (F12)
2. Vérifier les erreurs JavaScript
3. Tester manuellement avec le code du Test 2 ci-dessus

### La PWA affiche toujours l'ancienne version

**Cause** : Cache du navigateur

**Solution** :
1. Vider le cache : Ctrl+Shift+R (ou Cmd+Shift+R sur Mac)
2. Ou dans les DevTools :
   - F12 > Application > Clear Storage > Clear site data

## Checklist complète

- [ ] Backend : `planning_view.php` uploadé et mis à jour
- [ ] Backend : `planning_file.php` uploadé (nouveau)
- [ ] Frontend : Tout le répertoire `pwa_dist/` uploadé
- [ ] Permissions : 644 sur les fichiers PHP
- [ ] Permissions : 644 sur les fichiers PWA
- [ ] Test 1 : planning_view.php retourne les fichiers
- [ ] Test 2 : planning_file.php stream un fichier
- [ ] Test 3 : La PWA affiche la liste des fichiers
- [ ] Test 4 : Le bouton "Ouvrir" ouvre le fichier dans le navigateur
- [ ] Cache navigateur vidé

## Résumé

**Avant** : Fernando devait se connecter à Dolibarr pour voir les fichiers

**Maintenant** : Fernando ouvre la PWA, va dans Planning, clique sur son rendez-vous, et peut ouvrir tous les PDF/photos/plans directement dans son navigateur mobile, sans jamais sortir de la PWA.

**Sécurité** : Seuls les utilisateurs authentifiés avec un token valide peuvent accéder aux fichiers, et uniquement aux fichiers de leurs propres événements (sauf admin).

**Date** : 2026-01-09
