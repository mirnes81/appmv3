# Guide de Déploiement Complet - MV3 PRO Portail

## Problèmes identifiés

Les erreurs 404 actuelles proviennent d'endpoints manquants sur le serveur de production.

### 1. Authentification 404

**Problème** : Le diagnostic et la PWA appellent `/api/v1/auth/login.php` mais ce fichier n'existe pas.

**Impact** : Impossible de se connecter via l'API v1, tests de diagnostic échouent.

### 2. Planning View 404

**Problème** : La PWA appelle `/api/v1/planning_view.php?id=X` mais ce fichier n'existe pas.

**Impact** : Impossible de voir le détail d'un événement de planning.

---

## Solution - Fichiers à déployer

**Total : 7 fichiers à uploader**

### Groupe 1 : Authentification (4 fichiers)

**Nouveau répertoire** : `/custom/mv3pro_portail/api/v1/auth/`

1. **auth/login.php** - Endpoint login unifié
   - Supporte utilisateurs mobiles (table llx_mv3_mobile_users)
   - Supporte utilisateurs Dolibarr (table llx_user)
   - Accepte `{"email": "...", "password": "..."}` ou `{"login": "...", "password": "..."}`
   - Retourne `{"success": true, "token": "...", "user": {...}}`

2. **auth/me.php** - Endpoint info utilisateur
   - Récupère les infos de l'utilisateur connecté
   - Basé sur le token Bearer
   - Supporte les deux modes d'authentification

3. **auth/logout.php** - Endpoint déconnexion
   - Invalide le token/session
   - Nettoie la session mobile si applicable

4. **auth/.htaccess** - Configuration Apache
   - Autorise l'accès aux fichiers PHP
   - Configure CORS
   - Gère les requêtes OPTIONS

### Groupe 2 : Planning (2 fichiers)

**Répertoire existant** : `/custom/mv3pro_portail/api/v1/`

5. **planning_view.php** - Endpoint détail événement
   - Retourne informations complètes : dates, lieu, description
   - Relations : utilisateur, société, projet, objet lié
   - Liste des fichiers joints avec URLs sécurisées

6. **planning_file.php** - Endpoint streaming fichiers
   - Stream sécurisé des fichiers joints
   - Contrôle d'accès par rôle (admin / assigné)
   - Support tous types : images, PDF, documents

---

## Instructions de déploiement

### Méthode 1 - Via Hoststar File Manager

**Étape 1 : Créer le répertoire auth/**

1. Se connecter à Hoststar Control Panel
2. Ouvrir File Manager
3. Naviguer vers : `htdocs/custom/mv3pro_portail/api/v1/`
4. Créer un nouveau dossier nommé : `auth`

**Étape 2 : Uploader les fichiers auth/**

Dans le dossier `auth/` nouvellement créé, uploader :
- `login.php`
- `me.php`
- `logout.php`
- `.htaccess`

**Étape 3 : Uploader les fichiers planning**

Dans le dossier `api/v1/` (parent), uploader :
- `planning_view.php`
- `planning_file.php`

**Étape 4 : Vérifier les permissions**

Tous les fichiers doivent avoir les permissions : **644**

### Méthode 2 - Via FTP/SFTP

**Connexion** :
```
Host: mv3pro.ch (ou IP du serveur)
User: votre_user
Path: /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/
```

**Upload** :

1. Créer le répertoire `auth/` si nécessaire
2. Uploader les fichiers depuis le dépôt local :

```bash
# Source (local)
/tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/api/v1/auth/login.php
/tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/api/v1/auth/me.php
/tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/api/v1/auth/logout.php
/tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/api/v1/auth/.htaccess
/tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/api/v1/planning_view.php
/tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/api/v1/planning_file.php

# Destination (serveur)
/path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/auth/login.php
/path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/auth/me.php
/path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/auth/logout.php
/path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/auth/.htaccess
/path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/planning_view.php
/path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/planning_file.php
```

3. Appliquer les permissions :

```bash
chmod 644 auth/login.php auth/me.php auth/logout.php
chmod 644 auth/.htaccess
chmod 644 planning_view.php planning_file.php
```

---

## Tests de validation

### Test 1 : Vérifier l'existence des fichiers

**Avant déploiement** : 404 Not Found
**Après déploiement** : 401 Unauthorized ou 400 Bad Request (fichier existe, mais requête invalide)

**URLs à tester** :
```
https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/login.php
https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/me.php
https://mv3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php?id=1
```

### Test 2 : Test login API

**Via cURL** :
```bash
# Test login avec utilisateur Dolibarr
curl -X POST https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.local","password":"Test2026!"}'

# Résultat attendu :
{
  "success": true,
  "data": {
    "token": "...",
    "user": {
      "id": 1,
      "email": "admin@test.local",
      "firstname": "Admin",
      "lastname": "User",
      "name": "Admin User",
      "is_admin": true,
      "auth_mode": "dolibarr"
    },
    "auth_mode": "dolibarr"
  }
}
```

### Test 3 : Test auth/me avec token

```bash
# Récupérer le token du login
TOKEN=$(curl -X POST https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.local","password":"Test2026!"}' \
  | jq -r '.data.token')

# Tester /auth/me
curl -X GET https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/me.php \
  -H "Authorization: Bearer $TOKEN"

# Résultat attendu :
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "email": "admin@test.local",
      ...
    }
  }
}
```

### Test 4 : Test planning_view

```bash
# Avec le même token
curl -X GET "https://mv3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php?id=74049" \
  -H "Authorization: Bearer $TOKEN"

# Résultat attendu :
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

### Test 5 : Via la PWA

**1. Login** :
1. Ouvrir : `https://mv3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Se connecter avec email/password
3. Vérifier la connexion réussie

**2. Planning Detail** :
1. Aller dans Planning
2. Cliquer sur un événement
3. Vérifier que le détail complet s'affiche

**3. Fichiers joints** :
1. Dans le détail d'un événement avec fichiers
2. Cliquer sur "Ouvrir" sur un fichier
3. Vérifier que le fichier s'ouvre dans un nouvel onglet

---

## Architecture finale

```
/custom/mv3pro_portail/
├── api/
│   └── v1/
│       ├── _bootstrap.php           [EXISTS]
│       ├── index.php                [EXISTS]
│       ├── me.php                   [EXISTS - old, redirect to auth/me.php]
│       │
│       ├── auth/                    [NEW]
│       │   ├── .htaccess            [TO UPLOAD]
│       │   ├── login.php            [TO UPLOAD]
│       │   ├── me.php               [TO UPLOAD]
│       │   └── logout.php           [TO UPLOAD]
│       │
│       ├── planning.php             [EXISTS]
│       ├── planning_view.php        [TO UPLOAD]
│       ├── planning_file.php        [TO UPLOAD]
│       ├── planning_debug.php       [EXISTS]
│       │
│       ├── rapports.php             [EXISTS]
│       ├── rapports_create.php      [EXISTS]
│       ├── rapports_view.php        [EXISTS]
│       └── ...
└── pwa_dist/
    ├── index.html
    └── assets/
```

---

## Fonctionnalités déblocées

### 1. Authentification API v1

**Avant** :
- ❌ Login API v1 → 404 Not Found
- ❌ Tests de diagnostic → ERROR 404
- ❌ PWA peut appeler ancien endpoint mobile_app/api/auth.php uniquement

**Après** :
- ✅ Login API v1 → OK 200
- ✅ Tests de diagnostic → OK 200
- ✅ Endpoint unifié pour mobile + Dolibarr users
- ✅ Support email ou login
- ✅ Token standardisé
- ✅ Endpoint /auth/me pour vérifier token
- ✅ Endpoint /auth/logout pour déconnexion

### 2. Planning Detail

**Avant** :
- ❌ Click sur événement → 404 Not Found
- ❌ Fichiers joints inaccessibles

**Après** :
- ✅ Click sur événement → Détail complet
- ✅ Voir dates, lieu, description, progression
- ✅ Voir utilisateur assigné
- ✅ Voir société/tiers
- ✅ Voir projet lié
- ✅ Voir objet lié (commande, facture, etc.)
- ✅ Liste des fichiers joints
- ✅ Télécharger/ouvrir les fichiers (sécurisé)

### 3. Diagnostic QA

**Avant** :
- ❌ Niveau 1 - Auth Tests → ERROR 404
- ❌ Niveau 2 - Planning Tests → ERROR 404
- Score global : ~40-50%

**Après** :
- ✅ Niveau 1 - Auth Tests → OK 200
- ✅ Niveau 2 - Planning Tests → OK 200
- Score global : ~95-100%

---

## Compatibilité

### Endpoints Auth

**Nouveau endpoint unifié** : `/api/v1/auth/login.php`
- ✅ Accepte `{"email": "...", "password": "..."}`
- ✅ Accepte `{"login": "...", "password": "..."}`
- ✅ Supporte utilisateurs mobiles (llx_mv3_mobile_users)
- ✅ Supporte utilisateurs Dolibarr (llx_user)
- ✅ Détection automatique du type d'utilisateur

**Ancien endpoint mobile** : `/mobile_app/api/auth.php?action=login`
- ✅ Continue de fonctionner (pas touché)
- ✅ Utilisé par l'ancienne PWA mobile

**Ancien endpoint API** : `/api/auth_login.php`
- ✅ Continue de fonctionner (pas touché)
- ✅ Utilisé par certains scripts legacy

**Recommandation** : Migrer progressivement vers `/api/v1/auth/login.php` pour unifier l'authentification.

---

## Sécurité

### Authentification unifiée

**Mode Mobile** :
- Table : `llx_mv3_mobile_users`
- Session : `llx_mv3_mobile_sessions`
- Token : Random 64 chars hex
- Expiration : 30 jours
- Protection anti-brute-force : Oui (5 tentatives → lock 15min)

**Mode Dolibarr** :
- Table : `llx_user`
- Token : Base64 JSON avec api_key
- Expiration : 30 jours
- Protection : Validation api_key + statut actif

### Planning Files

**Contrôle d'accès** :
- ✅ Authentification requise (Bearer token)
- ✅ Vérification droits par rôle :
  - Admin : accès total
  - Employee : uniquement si assigné à l'événement
- ✅ Protection path traversal (`dol_sanitizeFileName`)
- ✅ Vérification type fichier (pas de répertoire)
- ✅ CORS headers pour PWA

---

## Troubleshooting

### Problème : Toujours 404 après upload

**Causes possibles** :
1. Fichiers uploadés dans le mauvais répertoire
2. Répertoire `auth/` non créé
3. Permissions incorrectes
4. .htaccess bloque l'accès

**Solutions** :

**1. Vérifier le chemin complet** :
```bash
# Via SSH
ls -la /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/auth/

# Résultat attendu :
# -rw-r--r-- 1 www-data www-data  256 Jan 09 12:00 .htaccess
# -rw-r--r-- 1 www-data www-data 6789 Jan 09 12:00 login.php
# -rw-r--r-- 1 www-data www-data 2345 Jan 09 12:00 me.php
# -rw-r--r-- 1 www-data www-data 1234 Jan 09 12:00 logout.php
```

**2. Vérifier que le répertoire api/v1 a son .htaccess** :
```bash
cat /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/.htaccess
```

Doit contenir au minimum :
```apache
<FilesMatch "\.php$">
    Require all granted
</FilesMatch>
```

**3. Vérifier les logs Apache/Nginx** :
```bash
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
```

### Problème : 401 avec token valide

**Causes** :
1. Token mal formaté dans l'en-tête Authorization
2. Bootstrap ne charge pas correctement
3. Fonction `require_auth()` échoue

**Solutions** :

**1. Vérifier le format du token** :
```bash
# Doit être : Authorization: Bearer {token}
# PAS : Authorization: {token}
```

**2. Vérifier _bootstrap.php** :
```bash
ls -la /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/_bootstrap.php
```

**3. Activer les logs de debug** :
```php
// Dans _bootstrap.php, augmenter le niveau de log
define('MV3_DEBUG_MODE', true);
```

**4. Vérifier les logs** :
```bash
tail -f /path/to/dolibarr/documents/mv3pro_portail/debug.log
```

### Problème : Login réussit mais /auth/me échoue

**Causes** :
1. Token non transmis dans Authorization header
2. Mode d'authentification non détecté
3. User ID dans le token ne correspond pas

**Solutions** :

**1. Vérifier le header Authorization** :
```javascript
// Dans le code frontend
headers: {
  'Authorization': `Bearer ${token}`,
  'X-Auth-Token': token  // fallback
}
```

**2. Tester avec cURL** :
```bash
# Voir Test 3 ci-dessus
```

**3. Vérifier les logs** :
```bash
grep "Auth me endpoint" /path/to/dolibarr/documents/mv3pro_portail/debug.log
```

### Problème : CORS errors

**Causes** :
1. .htaccess dans auth/ manquant ou mal configuré
2. Headers CORS non envoyés

**Solutions** :

**1. Vérifier que auth/.htaccess existe** :
```bash
cat /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/auth/.htaccess
```

**2. Tester avec OPTIONS** :
```bash
curl -X OPTIONS https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/login.php \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -v

# Doit retourner 200 avec headers CORS
```

---

## Checklist de déploiement

- [ ] **1. Créer le répertoire auth/**
  ```
  /custom/mv3pro_portail/api/v1/auth/
  ```

- [ ] **2. Uploader les 4 fichiers auth/**
  - `login.php`
  - `me.php`
  - `logout.php`
  - `.htaccess`

- [ ] **3. Uploader les 2 fichiers planning**
  - `planning_view.php`
  - `planning_file.php`

- [ ] **4. Vérifier les permissions (644)**
  ```bash
  chmod 644 auth/*.php auth/.htaccess
  chmod 644 planning_view.php planning_file.php
  ```

- [ ] **5. Test fichiers existent (doit retourner 401 ou 400, pas 404)**
  ```bash
  curl https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/login.php
  curl https://mv3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php
  ```

- [ ] **6. Test login API**
  ```bash
  curl -X POST https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/login.php \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@test.local","password":"password"}'
  ```

- [ ] **7. Test /auth/me avec token**
  ```bash
  curl -X GET https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/me.php \
    -H "Authorization: Bearer {token}"
  ```

- [ ] **8. Test PWA login**
  - Ouvrir PWA
  - Se connecter
  - Vérifier connexion réussie

- [ ] **9. Test planning detail**
  - Cliquer sur un événement
  - Vérifier détail complet
  - Tester ouverture fichier joint

- [ ] **10. Vérifier logs de diagnostic**
  ```bash
  tail -f /path/to/dolibarr/documents/mv3pro_portail/debug.log
  ```

---

## Résultat attendu

### Score Diagnostic QA

**Avant déploiement** :
```
📊 MV3 PRO API Diagnostic Results

Niveau 1 - Basic API Connectivity
  ❌ GET /api/v1/index.php → 404
  ❌ POST /api/v1/auth/login.php → 404
  ❌ GET /api/v1/auth/me.php → 404

Niveau 2 - Planning API Tests
  ❌ GET /api/v1/planning_view.php → 404
  ❌ GET /api/v1/planning_file.php → 404

Score Global : 40% (12/30 tests OK)
```

**Après déploiement** :
```
📊 MV3 PRO API Diagnostic Results

Niveau 1 - Basic API Connectivity
  ✅ GET /api/v1/index.php → 200 OK
  ✅ POST /api/v1/auth/login.php → 200 OK (with valid credentials)
  ✅ GET /api/v1/auth/me.php → 200 OK (with token)

Niveau 2 - Planning API Tests
  ✅ GET /api/v1/planning_view.php → 200 OK
  ✅ GET /api/v1/planning_file.php → 200 OK (with valid file)

Score Global : 95-100% (28-30/30 tests OK)
```

---

**Date** : 2026-01-09
**Version** : 2.2.0
**Fichiers à déployer** : 7 (4 auth + 2 planning + 1 htaccess)
**Priorité** : CRITIQUE (bloque authentification et planning)
**Temps estimé** : 10-15 minutes
**Auteur** : MV3 PRO Development Team
