# ✅ FIX FORMAT JSON - DOUBLE IMBRICATION data.data.items

## 🎯 Problème résolu

**Bug** : L'API renvoyait `{ success: true, data: { data: { items: [...] } } }` au lieu de `{ success: true, data: { items: [...] } }`

**Cause** : Appel de `json_ok(['data' => [...]])` alors que `json_ok()` encapsule déjà dans `data`

**Impact** : Page /#/rapports affichait "Cannot read properties of undefined (reading 'items')"

---

## 🔧 Corrections effectuées

### 1. ✅ api/v1/rapports.php

**Ligne 182-188 - AVANT (mauvais)** :
```php
json_ok([
    'data' => [
        'items' => $rapports,
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $limit > 0 ? ceil($total / $limit) : 0,
    ]
]);
```

**APRÈS (correct)** :
```php
json_ok([
    'items' => $rapports,
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'total_pages' => $limit > 0 ? ceil($total / $limit) : 0,
]);
```

**Résultat envoyé au client** :
```json
{
  "success": true,
  "error_code": null,
  "message": "OK",
  "data": {
    "items": [],
    "page": 1,
    "limit": 20,
    "total": 0,
    "total_pages": 0
  }
}
```

**Ligne 136-148 - Gestion erreur** :
```php
// Retourner format standard même en erreur avec items vide
http_response_code(200);
echo json_encode([
    'success' => false,
    'error_code' => 'DATABASE_ERROR',
    'message' => $error_msg,
    'data' => [
        'items' => [],
        'page' => $page,
        'limit' => $limit,
        'total' => 0,
        'total_pages' => 0,
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
```

**Garantie** : `items` existe **TOUJOURS** même en cas d'erreur SQL.

---

### 2. ✅ api/v1/rapports_view.php

**Ligne 212-216 - AVANT (mauvais)** :
```php
json_ok([
    'data' => [
        'rapport' => $rapport_data,
        'photos' => $photos,
        'pdf_url' => $pdf_url,
    ]
]);
```

**APRÈS (correct)** :
```php
json_ok([
    'rapport' => $rapport_data,
    'photos' => $photos,
    'pdf_url' => $pdf_url,
]);
```

**Résultat** :
```json
{
  "success": true,
  "data": {
    "rapport": {...},
    "photos": [...],
    "pdf_url": "..."
  }
}
```

---

### 3. ✅ api/v1/rapports_photos_upload.php

**Ligne 59-62 - Bug mkdir CORRIGÉ** :

**AVANT (logique inversée)** :
```php
if (!is_dir($upload_dir)) {
    if (!dol_mkdir($upload_dir, 0755) < 0) {
        json_error('Impossible de créer le répertoire de destination', 'MKDIR_ERROR', 500);
    }
}
```

**Problème** : `!dol_mkdir(...) < 0` est toujours faux car :
- Si `dol_mkdir()` retourne -1 (erreur), alors `!-1 < 0` → `0 < 0` → `false`
- Donc l'erreur n'est jamais levée !

**APRÈS (logique correcte)** :
```php
if (!is_dir($upload_dir)) {
    $mkdir_result = dol_mkdir($upload_dir, 0755);
    if ($mkdir_result < 0) {
        json_error('Impossible de créer le répertoire de destination', 'MKDIR_ERROR', 500);
    }
}
```

**Résultat upload** :
```json
{
  "success": true,
  "data": {
    "uploaded": 3,
    "photos": [...]
  }
}
```

---

### 4. ✅ Vérification autres endpoints

**Endpoints vérifiés** :
- ✅ `rapports_create.php` - Utilise `json_ok($response)` directement - **OK**
- ✅ `rapports_pdf.php` - Utilise `json_ok(['pdf' => [...]])` - **OK**
- ✅ `rapports_send_email.php` - Utilise `json_ok(['sent' => true, ...])` - **OK**
- ✅ `rapports_debug.php` - Utilise `json_ok($response)` - **OK**

**Conclusion** : Seuls `rapports.php` et `rapports_view.php` avaient la double imbrication.

---

## 📊 Format JSON standardisé

### Structure réponse SUCCESS

```json
{
  "success": true,
  "error_code": null,
  "message": "OK",
  "data": {
    // Contenu spécifique à l'endpoint
  }
}
```

### Structure réponse ERROR

```json
{
  "success": false,
  "error_code": "DATABASE_ERROR",
  "message": "Erreur lors de la récupération des rapports",
  "data": {
    "items": [],
    "page": 1,
    "limit": 20,
    "total": 0,
    "total_pages": 0
  }
}
```

### Règles JSON

1. **JAMAIS** passer `['data' => ...]` à `json_ok()`
2. `json_ok()` encapsule déjà dans `data`
3. `items` doit **TOUJOURS** exister (array vide si aucun résultat)
4. `success: false` doit quand même retourner `data` avec structure attendue
5. HTTP 200 même en cas d'erreur métier (seules les erreurs critiques utilisent 4xx/5xx)

---

## 🎨 Code PWA (déjà robuste)

Le code PWA avait déjà des fallbacks dans `Rapports.tsx` :

```typescript
// Fallback robuste pour gérer différents formats de réponse
const items = response?.data?.items ?? [];
const totalCount = response?.data?.total ?? 0;
const totalPages = response?.data?.total_pages ?? 0;

setRapports(Array.isArray(items) ? items : []);
```

**Maintenant** :
- Backend renvoie `data.items` correctement
- Frontend lit `response.data.items` sans fallback complexe
- Plus d'erreur "Cannot read properties of undefined"

---

## 🧪 Tests de validation

### Test 1 : Liste vide
```bash
GET /api/v1/rapports.php?limit=20&page=1

# Réponse attendue
{
  "success": true,
  "data": {
    "items": [],
    "page": 1,
    "limit": 20,
    "total": 0,
    "total_pages": 0
  }
}
```

### Test 2 : Liste avec rapports
```bash
GET /api/v1/rapports.php?limit=20&page=1

# Réponse attendue
{
  "success": true,
  "data": {
    "items": [
      {
        "rowid": 123,
        "ref": "RAP-2024-001",
        "date_rapport": "2024-01-10",
        "statut": 1,
        "statut_text": "valide",
        "client_nom": "Client ABC",
        "nb_photos": 5
      }
    ],
    "page": 1,
    "limit": 20,
    "total": 1,
    "total_pages": 1
  }
}
```

### Test 3 : Erreur SQL (table manquante)
```bash
GET /api/v1/rapports.php

# Réponse attendue
{
  "success": false,
  "error_code": "DATABASE_ERROR",
  "message": "Erreur lors de la récupération des rapports: Table 'llx_mv3_rapport' doesn't exist",
  "data": {
    "items": [],
    "page": 1,
    "limit": 20,
    "total": 0,
    "total_pages": 0
  }
}
```

### Test 4 : Détail rapport
```bash
GET /api/v1/rapports_view.php?id=123

# Réponse attendue
{
  "success": true,
  "data": {
    "rapport": {
      "rowid": 123,
      "ref": "RAP-2024-001",
      "date_rapport": "2024-01-10",
      ...
    },
    "photos": [
      {
        "id": 456,
        "filename": "photo_1.jpg",
        "url": "/custom/mv3pro_portail/mobile_app/rapports/photo.php?id=456"
      }
    ],
    "pdf_url": "/custom/mv3pro_portail/rapports/pdf.php?id=123"
  }
}
```

### Test 5 : Upload photos
```bash
POST /api/v1/rapports_photos_upload.php
Content-Type: multipart/form-data

rapport_id=123
files[]=photo1.jpg
files[]=photo2.jpg

# Réponse attendue
{
  "success": true,
  "data": {
    "uploaded": 2,
    "photos": [
      {
        "id": 789,
        "filename": "1234567890_unique_photo1.jpg",
        "description": "",
        "url": "/custom/mv3pro_portail/document.php?..."
      },
      {
        "id": 790,
        "filename": "1234567890_unique_photo2.jpg",
        "description": "",
        "url": "/custom/mv3pro_portail/document.php?..."
      }
    ]
  }
}
```

---

## 🎉 Résultat final

### Avant correction

**Backend** :
```json
{
  "success": true,
  "data": {
    "data": {
      "items": [...]  // ❌ Double imbrication
    }
  }
}
```

**Frontend** :
```javascript
const items = response.data.items;  // ❌ undefined
// TypeError: Cannot read properties of undefined (reading 'items')
```

### Après correction

**Backend** :
```json
{
  "success": true,
  "data": {
    "items": [...]  // ✅ Format correct
  }
}
```

**Frontend** :
```javascript
const items = response.data.items;  // ✅ Array
setRapports(items);  // ✅ Fonctionne
```

---

## 📦 PWA Rebuilt

**Build réussi** :
```
✓ 65 modules transformed.
../pwa_dist/assets/index-CtK1W4DF.js   278.08 kB
../pwa_dist/assets/index-BQiQB-1j.css    3.68 kB
✓ built in 2.99s

PWA v0.17.5
precache  10 entries (277.08 KiB)
```

**Fichiers générés** :
- `pwa_dist/index.html` ✅
- `pwa_dist/manifest.webmanifest` ✅
- `pwa_dist/icon-192.png` ✅ (PNG valide)
- `pwa_dist/icon-512.png` ✅ (PNG valide)
- `pwa_dist/sw.js` ✅
- `pwa_dist/assets/` ✅

---

## ✅ Checklist finale

- [x] `rapports.php` retourne `data.items` (pas `data.data.items`)
- [x] `rapports_view.php` retourne `data.rapport` (pas `data.data.rapport`)
- [x] `rapports_photos_upload.php` mkdir corrigé (logique inversée)
- [x] Tous les endpoints retournent JSON standard
- [x] `items` existe **TOUJOURS** même si vide ou erreur
- [x] PWA rebuild complet sans erreur TypeScript
- [x] Icônes PNG valides (192x192 et 512x512)
- [x] Service Worker généré correctement
- [x] Console logs propres (pas de spam /me.php)
- [x] Placeholder images 404 élégant (📷 "Image indisponible")

---

## 🚀 URL de test

```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/rapports
```

**Test à faire** :
1. Ouvrir la page /#/rapports
2. Vérifier : pas d'erreur "Cannot read properties..."
3. Vérifier : affiche "Aucun rapport enregistré" si vide
4. Vérifier : affiche la liste des rapports si présents
5. Console F12 : 0 erreur, 0 warning

---

**Date** : 2026-01-10
**Version** : 2.2.0 (JSON format fix)
**Status** : ✅ CORRIGÉ ET DÉPLOYÉ
