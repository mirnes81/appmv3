# Correction Authentification et Session Rapports PWA

## Problèmes identifiés

1. **Cookies Dolibarr non transmis** → `credentials: "include"` manquant
2. **Appels API prématurés** → loadRapports() appelé sans vérifier user.id
3. **Endpoint me.php** ne retournait pas `dolibarr_user_id` clairement
4. **Erreurs 500 au lieu de 401** dans l'API

---

## Corrections effectuées

### 1. Ajout de `credentials: "include"` dans tous les appels API

**Fichier: `pwa/src/lib/api.ts`**

#### Fonction `apiFetch()` (ligne 81-85)
```typescript
const response = await fetch(url, {
  ...options,
  headers,
  credentials: 'include',  // ✅ AJOUTÉ
});
```

#### Fonction `login()` (ligne 289-293)
```typescript
const response = await fetch(`${AUTH_API_URL}?action=login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',  // ✅ AJOUTÉ
  body: JSON.stringify({ email, password }),
});
```

#### Fonction `logout()` (ligne 326-332)
```typescript
await fetch(`${AUTH_API_URL}?action=logout`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${storage.getToken()}`,
  },
  credentials: 'include',  // ✅ AJOUTÉ
});
```

#### Fonction `upload()` (ligne 534)
```typescript
xhr.open('POST', url);
xhr.withCredentials = true;  // ✅ AJOUTÉ
```

**Effet:** Tous les appels API transmettent maintenant les cookies de session Dolibarr.

---

### 2. Correction de `me.php` pour retourner `dolibarr_user_id`

**Fichier: `api/v1/me.php` (ligne 27-39)**

```php
$response = [
    'success' => true,
    'user' => [
        'id' => $auth['user_id'] ?? null,
        'dolibarr_user_id' => $auth['user_id'] ?? null,  // ✅ AJOUTÉ
        'login' => $auth['login'] ?? null,
        'name' => $auth['name'] ?? '',
        'email' => $auth['email'] ?? '',
        'role' => $auth['role'] ?? 'user',
        'auth_mode' => $auth['mode'],
        'rights' => $auth['rights'] ?? []
    ]
];
```

**Effet:** Le endpoint `/me.php` retourne maintenant explicitement le `dolibarr_user_id`.

---

### 3. Empêcher les appels API si user.id absent

**Fichier: `pwa/src/pages/Rapports.tsx`**

#### Dans `loadRapports()` (ligne 34-38)
```typescript
const loadRapports = async (resetPage = false) => {
  if (!user?.id) {
    setError('Veuillez vous connecter pour voir vos rapports');
    setLoading(false);
    return;  // ✅ AJOUTÉ - Stoppe l'appel API
  }
  // ... reste du code
```

#### Dans `useEffect()` (ligne 114-117)
```typescript
useEffect(() => {
  if (user?.id) {  // ✅ AJOUTÉ - Condition
    loadRapports(true);
  }
}, [user?.id, searchQuery, filterStatut, filterDateDebut, filterDateFin, filterUserId]);
```

#### Debug panel amélioré (ligne 209-221)
Affiche maintenant les infos directement depuis `user` (AuthContext) au lieu de seulement `debugData`.

**Effet:** Plus d'appels API tant que l'utilisateur n'est pas chargé.

---

### 4. Retourner 401 au lieu de 500 dans rapports.php

**Fichier: `api/v1/rapports.php` (ligne 28-37)**

```php
// Vérifier que l'utilisateur est valide
if (empty($auth['user_id'])) {
    http_response_code(401);  // ✅ AJOUTÉ
    echo json_encode([
        'success' => false,
        'error' => 'not_authenticated',
        'message' => 'Utilisateur non authentifié ou non lié à Dolibarr'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
```

#### Erreur DB retourne 500 avec debug (ligne 152-169)
```php
if (!$resql) {
    // ...
    http_response_code(500);  // ✅ Changé de 200 à 500
    echo json_encode([
        'success' => false,
        'error' => 'db_error',
        'message' => $error_msg,
        'debug' => [
            'sql_error' => $db_error,
            'query' => $sql
        ],
        // ...
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
```

**Effet:** Codes HTTP corrects (401 pour auth, 500 pour erreurs DB).

---

## Fichiers modifiés

1. ✅ `pwa/src/lib/api.ts` - Ajout credentials: include
2. ✅ `api/v1/me.php` - Retour explicite de dolibarr_user_id
3. ✅ `pwa/src/pages/Rapports.tsx` - Vérification user.id avant appels API
4. ✅ `api/v1/rapports.php` - Retour 401 si user.id absent

---

## Résultat attendu

### Avant
```
❌ Dolibarr User ID: NON DÉFINI
❌ Endpoint: vide
❌ Timestamp: Invalid Date
❌ Erreur 500 sur rapportsList
```

### Après
```
✅ Dolibarr User ID: 20 (ou votre ID)
✅ Nom: Fernando test (ou votre nom)
✅ Email: votre@email.com
✅ Endpoint: /rapports.php
✅ Timestamp: 10/01/2026 14:32:15
✅ Les rapports s'affichent correctement
```

---

## Déploiement

### Fichiers à uploader sur le serveur

**1. PWA compilée (pwa_dist/)**
```
custom/mv3pro_portail/pwa_dist/assets/index-CPmEceR_.js  (nouveau)
custom/mv3pro_portail/pwa_dist/assets/index-BQiQB-1j.css (nouveau)
custom/mv3pro_portail/pwa_dist/index.html
custom/mv3pro_portail/pwa_dist/sw.js
```

**2. Fichiers API PHP**
```
custom/mv3pro_portail/api/v1/me.php
custom/mv3pro_portail/api/v1/rapports.php
```

### Commandes

Via SSH:
```bash
# Depuis le dossier du projet local
scp -r new_dolibarr/mv3pro_portail/pwa_dist/* user@crm.mv-3pro.ch:/path/to/dolibarr/custom/mv3pro_portail/pwa_dist/

scp new_dolibarr/mv3pro_portail/api/v1/me.php user@crm.mv-3pro.ch:/path/to/dolibarr/custom/mv3pro_portail/api/v1/
scp new_dolibarr/mv3pro_portail/api/v1/rapports.php user@crm.mv-3pro.ch:/path/to/dolibarr/custom/mv3pro_portail/api/v1/
```

Via FTP:
1. Connectez-vous à votre serveur
2. Naviguez vers `/custom/mv3pro_portail/`
3. Uploadez `pwa_dist/` (remplacer les fichiers)
4. Uploadez `api/v1/me.php` et `api/v1/rapports.php`

---

## Test après déploiement

1. **Vider le cache du navigateur** (Ctrl+Shift+R)
2. Ouvrir la PWA: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
3. Se connecter
4. Aller sur "Rapports"
5. Activer le mode debug
6. Vérifier que:
   - ✅ Dolibarr User ID est défini
   - ✅ Les rapports s'affichent
   - ✅ Pas d'erreur 500

---

## Points clés

### credentials: "include"
**Obligatoire** pour transmettre les cookies de session Dolibarr entre PWA et API PHP.

Sans cela:
- La session PHP n'est pas transmise
- `$_SESSION['dol_login']` est vide
- `$user->id` est null
- L'API retourne 401 ou des erreurs

### Vérification user.id avant appels API
**Obligatoire** pour éviter les appels prématurés.

Sans cela:
- Les appels API se font avant que l'utilisateur soit chargé
- Le debug affiche "NON DÉFINI"
- Les erreurs sont difficiles à diagnostiquer

### Codes HTTP corrects
- **401**: Authentification requise
- **403**: Permissions insuffisantes
- **500**: Erreur serveur (DB, code PHP)

**Ne jamais retourner 200 avec une erreur**, sinon le frontend pense que tout va bien.

---

## Notes importantes

1. **Session Dolibarr** : Le mode d'authentification principal utilise la session Dolibarr (`$_SESSION['dol_login']`). Les tokens mobiles sont un fallback.

2. **Mode debug** : Le panneau de debug dans la PWA est maintenant fonctionnel et affiche les vraies données de l'utilisateur et des appels API.

3. **Compatibilité** : Ces changements sont rétro-compatibles avec l'authentification mobile (tokens).

4. **Sécurité** : Les cookies sont transmis avec `credentials: "include"` mais uniquement vers le même domaine (CORS autorisé).
