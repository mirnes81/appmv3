# Fix 404 - Planning View API

## Problème identifié

Lorsqu'on clique sur un événement de planning dans la PWA (ex: `/#/planning/74049`), la PWA appelle :
```
/custom/mv3pro_portail/api/v1/planning_view.php?id=74049
```

L'API répond **404 (Not Found)** car le fichier n'est pas déployé sur le serveur de production.

---

## Solution

**Les fichiers existent déjà** dans le dépôt et sont **prêts à être déployés**.

### Fichiers à uploader

Les fichiers suivants doivent être uploadés sur le serveur dans `/custom/mv3pro_portail/api/v1/` :

1. **planning_view.php** - Endpoint détail événement
2. **planning_file.php** - Endpoint streaming fichiers joints

---

## 1. planning_view.php

### Fonctionnalités

Retourne le détail complet d'un événement de planning avec :

**Informations de base** :
- ID, titre, type, dates (début/fin), all_day
- Lieu, description, progression

**Relations** :
- Utilisateur assigné (id, nom_complet, login)
- Société/Tiers (id, nom, type)
- Projet (id, ref, titre)
- Objet lié (commande, facture, propal, etc.)

**Fichiers joints** :
- Liste des fichiers avec : name, size, size_human, mime, is_image
- URL de téléchargement sécurisée via `planning_file.php`

### Endpoint

```
GET /custom/mv3pro_portail/api/v1/planning_view.php?id=123
```

**Headers requis** :
```
Authorization: Bearer {token}
X-Auth-Token: {token}
```

**Réponse** :
```json
{
  "success": true,
  "data": {
    "id": 74049,
    "titre": "Installation chantier ABC",
    "type_code": "AC_RDV",
    "date_debut": "2026-01-15 09:00:00",
    "date_fin": "2026-01-15 17:00:00",
    "all_day": 0,
    "lieu": "12 Rue de la Paix, 75000 Paris",
    "description": "Installation des équipements...",
    "progression": 75,
    "user": {
      "id": 5,
      "nom_complet": "Jean Dupont",
      "login": "jdupont"
    },
    "societe": {
      "id": 123,
      "nom": "SARL XYZ",
      "type": 1
    },
    "projet": {
      "id": 456,
      "ref": "PRJ-2026-001",
      "titre": "Chantier ABC"
    },
    "objet_lie": {
      "type": "commande",
      "type_label": "Commande",
      "id": 789,
      "ref": "CMD-2026-001"
    },
    "fichiers": [
      {
        "name": "photo_chantier.jpg",
        "size": 1234567,
        "size_human": "1.18 MB",
        "mime": "image/jpeg",
        "is_image": true,
        "url": "/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=photo_chantier.jpg"
      },
      {
        "name": "devis.pdf",
        "size": 234567,
        "size_human": "229.07 KB",
        "mime": "application/pdf",
        "is_image": false,
        "url": "/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=devis.pdf"
      }
    ]
  }
}
```

**Erreurs** :
- **401** : Non authentifié
- **404** : Événement non trouvé

### Sécurité

- ✅ Authentification requise (Bearer token)
- ✅ Vérification entity Dolibarr
- ✅ Accès limité aux événements de l'entité courante

---

## 2. planning_file.php

### Fonctionnalités

Stream un fichier joint à un événement de planning de manière sécurisée.

**Règles d'accès** :
- **Admin** : accès total à tous les fichiers
- **Employee** : accès uniquement si assigné à l'événement

### Endpoint

```
GET /custom/mv3pro_portail/api/v1/planning_file.php?id=123&file=photo.jpg
```

**Headers requis** :
```
Authorization: Bearer {token}
X-Auth-Token: {token}
```

**Réponse** :
- Headers HTTP avec type MIME correct
- Stream du fichier (inline pour affichage dans navigateur)
- CORS headers pour la PWA

**Erreurs** :
- **401** : Non authentifié
- **403** : Accès refusé (utilisateur non assigné)
- **404** : Événement ou fichier non trouvé

### Sécurité

- ✅ Authentification requise
- ✅ Vérification des droits d'accès par rôle
- ✅ Protection contre path traversal (`dol_sanitizeFileName`)
- ✅ Vérification type de fichier (pas de répertoire)
- ✅ CORS headers pour PWA

---

## 3. Instructions de déploiement

### Via FTP/SFTP

**1. Connexion au serveur**
```bash
# Hostname : mv3pro.ch (ou IP du serveur)
# User : votre_user
# Path : /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/
```

**2. Upload des fichiers**

Uploader les fichiers suivants depuis le dépôt local vers le serveur :
```
Source (local) :
  /tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/api/v1/planning_view.php
  /tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/api/v1/planning_file.php

Destination (serveur) :
  /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/planning_view.php
  /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/planning_file.php
```

**3. Vérifier les permissions**
```bash
chmod 644 planning_view.php
chmod 644 planning_file.php
```

### Via Hoststar File Manager

1. Se connecter à Hoststar Control Panel
2. Ouvrir File Manager
3. Naviguer vers : `htdocs/custom/mv3pro_portail/api/v1/`
4. Uploader les 2 fichiers :
   - `planning_view.php`
   - `planning_file.php`
5. Vérifier que les fichiers sont bien visibles dans la liste

---

## 4. Tests de validation

### Test 1 : Vérifier l'existence du fichier

**Via navigateur** :
```
https://mv3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php
```

**Résultat attendu** :
- ❌ **Avant déploiement** : 404 Not Found
- ✅ **Après déploiement** : 401 Unauthorized (fichier existe mais token manquant)

### Test 2 : Appel API authentifié

**Via cURL** :
```bash
# Récupérer un token (remplacer credentials)
TOKEN=$(curl -X POST https://mv3pro.ch/custom/mv3pro_portail/api/v1/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"login","email":"admin@test.local","password":"Test2026!"}' \
  | jq -r '.data.token')

# Tester planning_view.php (remplacer ID par un vrai ID)
curl -X GET "https://mv3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php?id=74049" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Auth-Token: $TOKEN"
```

**Résultat attendu** :
```json
{
  "success": true,
  "data": {
    "id": 74049,
    "titre": "...",
    "date_debut": "...",
    "fichiers": [...]
  }
}
```

### Test 3 : Via la PWA

1. Ouvrir la PWA : `https://mv3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Se connecter
3. Aller sur Planning
4. Cliquer sur un événement

**Résultat attendu** :
- ✅ Page de détail s'affiche avec toutes les infos
- ✅ Fichiers joints affichés (si présents)
- ✅ Possibilité d'ouvrir les fichiers

---

## 5. Troubleshooting

### Problème : Toujours 404 après upload

**Causes possibles** :
1. Fichier uploadé dans le mauvais répertoire
2. Permissions incorrectes
3. .htaccess bloque l'accès

**Solutions** :

**1. Vérifier le chemin**
```bash
# Via SSH
ls -la /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/planning*.php

# Résultat attendu :
# -rw-r--r-- 1 www-data www-data 6789 Jan 09 12:00 planning_view.php
# -rw-r--r-- 1 www-data www-data 4567 Jan 09 12:00 planning_file.php
```

**2. Vérifier .htaccess**
```bash
cat /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/.htaccess
```

Doit contenir :
```apache
RewriteEngine On
RewriteBase /custom/mv3pro_portail/api/v1/

# Autoriser tous les fichiers .php
<FilesMatch "\.php$">
    Require all granted
</FilesMatch>
```

**3. Vérifier les logs Apache/Nginx**
```bash
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
```

### Problème : 401 avec token valide

**Causes** :
1. Bootstrap ne charge pas correctement
2. Fonction `require_auth()` échoue

**Solution** :

Vérifier que `_bootstrap.php` existe :
```bash
ls -la /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/_bootstrap.php
```

Vérifier les logs :
```bash
tail -f /path/to/dolibarr/documents/mv3pro_portail/debug.log
```

### Problème : Fichiers joints non trouvés

**Causes** :
1. Répertoire `DOL_DATA_ROOT/actioncomm/{id}/` n'existe pas
2. Fichiers stockés ailleurs
3. Permissions insuffisantes

**Solution** :

Vérifier où sont stockés les fichiers :
```sql
SELECT * FROM llx_ecm_files
WHERE filename LIKE '%actioncomm%'
LIMIT 10;
```

Vérifier le répertoire :
```bash
ls -la /path/to/dolibarr/documents/actioncomm/74049/
```

---

## 6. Architecture des fichiers

```
/custom/mv3pro_portail/
├── api/
│   └── v1/
│       ├── _bootstrap.php          [EXISTS]
│       ├── planning.php             [EXISTS] - Liste événements
│       ├── planning_view.php        [TO UPLOAD] - Détail événement
│       ├── planning_file.php        [TO UPLOAD] - Stream fichiers
│       ├── planning_debug.php       [EXISTS] - Debug
│       ├── rapports.php             [EXISTS]
│       ├── notifications_list.php   [EXISTS]
│       └── ...
└── pwa_dist/
    ├── index.html
    └── assets/
        └── index-*.js               [CALLS planning_view.php]
```

---

## 7. Frontend - Code d'appel

**Fichier** : `/pwa/src/pages/PlanningDetail.tsx`

**Ligne 63** :
```typescript
const data = await apiClient(`/planning_view.php?id=${id}`);
```

Le frontend appelle donc bien `/planning_view.php` avec le paramètre `id`.

**Fonction apiClient** (dans `/pwa/src/lib/api.ts`) :
- Ajoute automatiquement le base URL
- Ajoute automatiquement le token d'authentification
- Gère les erreurs

---

## 8. Checklist de déploiement

- [ ] **1. Vérifier que les fichiers existent localement**
  ```bash
  ls -la new_dolibarr/mv3pro_portail/api/v1/planning*.php
  ```

- [ ] **2. Se connecter au serveur (FTP/SFTP/Hoststar)**

- [ ] **3. Uploader les fichiers**
  - `planning_view.php` → `/custom/mv3pro_portail/api/v1/`
  - `planning_file.php` → `/custom/mv3pro_portail/api/v1/`

- [ ] **4. Vérifier les permissions**
  ```bash
  chmod 644 planning_view.php planning_file.php
  ```

- [ ] **5. Tester l'endpoint sans token (doit retourner 401)**
  ```bash
  curl https://mv3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php?id=1
  # Résultat attendu : {"success":false,"error":"...","code":"AUTH_REQUIRED"}
  ```

- [ ] **6. Tester avec token valide**
  - Se connecter à la PWA
  - Cliquer sur un événement dans Planning
  - Vérifier que le détail s'affiche

- [ ] **7. Tester les fichiers joints (si présents)**
  - Cliquer sur "Ouvrir" sur un fichier joint
  - Vérifier que le fichier s'ouvre dans un nouvel onglet

- [ ] **8. Vérifier dans les logs de diagnostic**
  ```bash
  tail -f /path/to/dolibarr/documents/mv3pro_portail/debug.log
  ```

---

## 9. Résultat attendu après déploiement

### Avant
- ❌ Click sur événement → 404 Not Found
- ❌ Diagnostic QA : "API - Planning view" → ERROR 404

### Après
- ✅ Click sur événement → Détail complet affiché
- ✅ Diagnostic QA : "API - Planning view" → OK 200
- ✅ Fichiers joints accessibles (si présents)
- ✅ Toutes les infos affichées : dates, lieu, description, utilisateur, société, projet

---

## 10. Impact

**Pages concernées** :
- Planning Detail (`/#/planning/{id}`)

**Fonctionnalités déblocées** :
- ✅ Voir le détail complet d'un événement
- ✅ Voir les infos du projet lié
- ✅ Voir la société/tiers
- ✅ Voir l'utilisateur assigné
- ✅ Télécharger/ouvrir les fichiers joints

**Score QA** :
- **Avant** : Niveau 2 Planning Tests → ~60% (404 errors)
- **Après** : Niveau 2 Planning Tests → ~95-100%

---

**Date** : 2026-01-09
**Version** : 2.1.1
**Fichiers à déployer** : 2
- `api/v1/planning_view.php`
- `api/v1/planning_file.php`
**Priorité** : HAUTE (bloque fonctionnalité Planning)
**Auteur** : MV3 PRO Development Team
