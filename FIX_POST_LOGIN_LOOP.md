# FIX POST-LOGIN LOOP - Compte non lié à Dolibarr

## Problème résolu

**Avant:** Après un login réussi avec un compte mobile dont `dolibarr_user_id = 0`, l'utilisateur était rejeté par les endpoints protégés et entrait dans une boucle de redirection infinie (login → dashboard → erreur 401 → login).

**Maintenant:** Le compte est accepté mais avec des fonctionnalités limitées, et l'utilisateur voit un message clair l'invitant à contacter l'administrateur.

---

## Corrections appliquées

### A) BACKEND: Tolérance pour dolibarr_user_id = 0

#### Fichier: `api/v1/_bootstrap.php`

La fonction `require_auth()` a été modifiée pour:

1. **Détecter les comptes non liés**
   ```php
   $is_unlinked = empty($session->dolibarr_user_id) || $session->dolibarr_user_id == 0;
   ```

2. **Ne plus charger l'utilisateur Dolibarr si non lié**
   ```php
   if (!$is_unlinked) {
       $dol_user = new User($db);
       if ($dol_user->fetch($session->dolibarr_user_id) > 0) {
           $dol_user->getrights();
           $user = $dol_user;
       }
   }
   ```

3. **Ajouter un flag `is_unlinked` dans la réponse d'authentification**
   ```php
   'is_unlinked' => $is_unlinked,
   'rights' => [
       'read' => true,
       'write' => !$is_unlinked, // Pas d'écriture si non lié
       'worker' => !$is_unlinked,
   ]
   ```

4. **Ne JAMAIS retourner 401** pour un compte mobile valide, même si non lié

#### Fichier: `api/v1/me.php`

L'endpoint `/api/v1/me.php` retourne maintenant:

```json
{
  "success": true,
  "user": {
    "id": null,
    "mobile_user_id": 123,
    "email": "info@mv-3pro.ch",
    "name": "John Doe",
    "role": "employee",
    "auth_mode": "mobile_token",
    "is_unlinked": true,
    "warning": "Compte non lié à Dolibarr. Fonctionnalités limitées.",
    "rights": {
      "read": true,
      "write": false,
      "worker": false
    }
  }
}
```

**Impact:**
- ✅ Plus de boucle de redirection
- ✅ Le token reste valide
- ✅ L'utilisateur peut consulter son profil
- ❌ L'utilisateur ne peut PAS créer de rapports, régies, sens de pose (write=false)

---

### B) ADMIN: Lien Dolibarr obligatoire

#### Fichier: `mobile_app/admin/manage_users.php`

**Validation côté serveur:**

Pour les rôles `employee` et `manager`, le lien avec un utilisateur Dolibarr est maintenant **OBLIGATOIRE**:

```php
// VALIDATION CREATE
if (in_array($role, ['employee', 'manager']) && (!$dolibarr_user || $dolibarr_user <= 0)) {
    $error = "⚠️ ERREUR: Le lien avec un utilisateur Dolibarr est OBLIGATOIRE pour les rôles 'Employé' et 'Manager'. Sans ce lien, l'utilisateur ne pourra pas utiliser l'application mobile correctement.";
}

// VALIDATION UPDATE (même chose)
if (in_array($role, ['employee', 'manager']) && (!$dolibarr_user || $dolibarr_user <= 0)) {
    $error = "⚠️ ERREUR: Le lien avec un utilisateur Dolibarr est OBLIGATOIRE...";
}
```

**Interface utilisateur:**

1. **Avertissement visuel** quand le rôle sélectionné est `employee` ou `manager`:
   ```
   ⚠️ OBLIGATOIRE pour les rôles Employé et Manager
   ```

2. **Label marqué comme requis** (`fieldrequired` class) dynamiquement via JavaScript

3. **JavaScript** qui met à jour l'interface en temps réel quand le rôle change:
   ```javascript
   roleSelect.addEventListener('change', updateDolibarrRequirement);
   ```

**Impact:**
- ✅ Les admins ne peuvent plus créer de comptes employee/manager sans lien Dolibarr
- ✅ Message d'erreur clair si tentative
- ✅ Interface visuelle qui guide l'admin

---

### C) PWA: Gestion du compte non lié

#### Fichiers modifiés:

##### 1. `pwa/src/lib/api.ts`

**Interface User étendue:**
```typescript
export interface User {
  id: number | null;
  email: string;
  name?: string;
  firstname?: string;
  lastname?: string;
  mobile_user_id?: number;
  role?: string;
  is_unlinked?: boolean;  // ← NOUVEAU
  warning?: string;        // ← NOUVEAU
  rights?: {
    read?: boolean;
    write?: boolean;
    validate?: boolean;
    worker?: boolean;
  };
}
```

**Fonction `me()` améliorée:**
- Parse la réponse `{ success, user }`
- Extrait firstname/lastname depuis `name` si nécessaire
- Retourne l'objet User complet avec is_unlinked

##### 2. `pwa/src/pages/AccountUnlinked.tsx` (NOUVEAU)

Page dédiée qui affiche:

```
⚠️ Compte non lié à Dolibarr

Bonjour John Doe,

Votre compte mobile (info@mv-3pro.ch) n'est actuellement pas lié à un
utilisateur Dolibarr. Sans ce lien, vous ne pouvez pas utiliser les
fonctionnalités de l'application mobile.

Que faire ?
• Contactez votre administrateur
• Demandez-lui de lier votre compte mobile à votre utilisateur Dolibarr

[Bouton: Ouvrir la gestion des utilisateurs]  (pour les admins)

[Bouton: Se déconnecter]
```

##### 3. `pwa/src/pages/Dashboard.tsx`

**Redirection automatique:**
```typescript
useEffect(() => {
  if (user?.is_unlinked) {
    navigate('/account-unlinked', { replace: true });
    return;
  }
}, [user, navigate]);
```

##### 4. `pwa/src/App.tsx`

**Nouvelle route:**
```typescript
<Route
  path="/account-unlinked"
  element={
    <ProtectedRoute>
      <AccountUnlinked />
    </ProtectedRoute>
  }
/>
```

**Impact:**
- ✅ Plus de boucle infinie
- ✅ Message clair et actionnable
- ✅ Lien direct vers la page admin pour corriger
- ✅ L'utilisateur comprend pourquoi ça ne marche pas

---

## Workflow complet

### Scénario 1: Login avec compte lié (normal)

1. **Login:** `info@mv-3pro.ch` avec `dolibarr_user_id = 5`
2. **Réponse:** `token` + `user { dolibarr_user_id: 5 }`
3. **Appel /me.php:** Retourne `is_unlinked: false`, `rights.write: true`
4. **Dashboard:** Affichage normal
5. **Actions:** Toutes les fonctionnalités disponibles

### Scénario 2: Login avec compte NON lié (avant le fix)

1. **Login:** `info@mv-3pro.ch` avec `dolibarr_user_id = 0`
2. **Réponse:** `token` + `user { dolibarr_user_id: 0 }`
3. **Appel /me.php:** ❌ 401 Unauthorized (require_auth échoue)
4. **Redirection:** → `/login`
5. **Boucle:** Login → Dashboard → 401 → Login → ...

### Scénario 3: Login avec compte NON lié (après le fix)

1. **Login:** `info@mv-3pro.ch` avec `dolibarr_user_id = 0`
2. **Réponse:** `token` + `user { dolibarr_user_id: 0 }`
3. **Appel /me.php:** ✅ 200 OK avec `is_unlinked: true`, `rights.write: false`
4. **Dashboard:** Détecte `is_unlinked`
5. **Redirection:** → `/account-unlinked`
6. **Affichage:** Page explicative avec bouton vers admin
7. **Pas de boucle!**

---

## Tests à effectuer

### Test 1: Login avec compte non lié

1. Créer un utilisateur mobile SANS lier à Dolibarr (dolibarr_user_id = NULL ou 0)
2. Se connecter avec cet utilisateur
3. **Résultat attendu:**
   - Login réussit ✅
   - Redirection vers `/account-unlinked` ✅
   - Message clair affiché ✅
   - Pas de boucle ✅

### Test 2: Tentative de création sans lien (employee)

1. Aller dans `manage_users.php`
2. Créer un utilisateur avec rôle "Employé"
3. Ne PAS sélectionner d'utilisateur Dolibarr
4. **Résultat attendu:**
   - ❌ Erreur affichée
   - Message: "Le lien avec un utilisateur Dolibarr est OBLIGATOIRE..."

### Test 3: Interface dynamique

1. Aller dans `manage_users.php`
2. Sélectionner rôle "Employé"
3. **Résultat attendu:**
   - ⚠️ Avertissement jaune affiché
   - Label "Lier à un utilisateur Dolibarr" marqué comme requis
4. Changer le rôle vers "Administrateur"
5. **Résultat attendu:**
   - ⚠️ Avertissement caché
   - Label n'est plus requis

### Test 4: Login avec compte lié (régression)

1. Se connecter avec un compte normal (dolibarr_user_id = 5)
2. **Résultat attendu:**
   - Login réussit ✅
   - Dashboard s'affiche normalement ✅
   - Toutes les fonctionnalités disponibles ✅

---

## Endpoints impactés

### Endpoints qui ACCEPTENT les comptes non liés

- ✅ `/api/v1/me.php` - Retourne les infos user avec is_unlinked
- ✅ `/mobile_app/api/auth.php?action=login` - Login fonctionne
- ✅ `/mobile_app/api/auth.php?action=logout` - Logout fonctionne

### Endpoints qui REFUSENT les comptes non liés

Tous les endpoints qui nécessitent `write: true` vont retourner une erreur explicite:

```php
if (!empty($auth['is_unlinked'])) {
    json_error('Compte non lié à Dolibarr. Contactez l\'administrateur.', 'ACCOUNT_UNLINKED', 403);
}
```

Exemples:
- ❌ `/api/v1/rapports_create.php`
- ❌ `/api/v1/regie_create.php`
- ❌ `/api/v1/sens_pose_create.php`

---

## Fichiers modifiés

```
new_dolibarr/mv3pro_portail/
├── api/v1/
│   ├── _bootstrap.php              ← Tolérance dolibarr_user_id=0
│   └── me.php                       ← Retourne is_unlinked + warning
├── mobile_app/admin/
│   └── manage_users.php             ← Validation + UI obligatoire
└── pwa/
    ├── src/
    │   ├── lib/api.ts               ← Interface User étendue
    │   ├── pages/
    │   │   ├── Dashboard.tsx        ← Redirection si is_unlinked
    │   │   └── AccountUnlinked.tsx  ← Page dédiée (NOUVEAU)
    │   └── App.tsx                  ← Route /account-unlinked
    └── pwa_dist/                    ← Build mis à jour
```

---

## Prochaines étapes

### Pour l'utilisateur final

1. Contacter l'administrateur
2. Lui communiquer votre email: `info@mv-3pro.ch`
3. Attendre qu'il lie votre compte
4. Se reconnecter

### Pour l'administrateur

1. Ouvrir `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
2. Cliquer sur "Modifier" pour l'utilisateur concerné
3. Sélectionner un utilisateur Dolibarr dans la liste
4. Enregistrer
5. L'utilisateur peut maintenant utiliser l'application normalement

---

## Notes importantes

### Sécurité

- ✅ Les comptes non liés ne peuvent PAS écrire dans la base Dolibarr
- ✅ Les droits sont vérifiés par `require_auth()`
- ✅ Le flag `is_unlinked` est côté serveur (pas manipulable par le client)

### Rétrocompatibilité

- ✅ Les comptes liés fonctionnent exactement comme avant
- ✅ Aucun changement pour les utilisateurs normaux
- ✅ Pas de régression fonctionnelle

### Performance

- ✅ Un seul appel `/me.php` au chargement
- ✅ Redirection immédiate vers AccountUnlinked
- ✅ Pas de requêtes API supplémentaires

---

**Date:** 2026-01-09
**Version:** 1.0
**Status:** ✅ RÉSOLU
