# Guide Planning - Lien utilisateur mobile <-> Dolibarr

## Date: 2026-01-09

---

## Fonctionnement du système de planning

### 1. Création des RDV dans Dolibarr

Les rendez-vous sont créés dans **Dolibarr > Agenda** :

```
Menu > Agenda > Nouvel événement

Champs importants :
- Titre : "Finir Appartements Ingold Sol Complet"
- Assigné à : FERNANDO (utilisateur Dolibarr)
- Date début / Date fin
- Projet (optionnel)
- Client (optionnel)
- Description / Notes
```

### 2. Connexion à la PWA

L'utilisateur **FERNANDO** :
- ✅ Se connecte à la **PWA MV3** (pas à Dolibarr)
- ✅ Utilise son **email + mot de passe mobile**
- ❌ **NE se connecte PAS** à l'interface Dolibarr

### 3. Lien entre utilisateur mobile et Dolibarr

Le lien se fait via la table `llx_mv3_mobile_users` :

```sql
llx_mv3_mobile_users.dolibarr_user_id = rowid utilisateur Dolibarr
```

**Exemple pour FERNANDO :**

```sql
-- Utilisateur Dolibarr
SELECT rowid, login, lastname, firstname
FROM llx_user
WHERE login = 'fernando';
-- Résultat: rowid = 15, login = 'fernando'

-- Utilisateur mobile
SELECT rowid, email, firstname, lastname, dolibarr_user_id
FROM llx_mv3_mobile_users
WHERE email = 'fernando@mv3.com';
-- Résultat: rowid = 5, dolibarr_user_id = 15 ✅
```

### 4. Filtrage dans la PWA

Quand Fernando accède au Planning PWA :

1. Il s'authentifie avec son token mobile
2. Le système récupère son `dolibarr_user_id` (15)
3. Le Planning affiche **uniquement** les événements où :
   - `actioncomm.fk_user_author = 15` (créé par Fernando)
   - **OU** `actioncomm.fk_user_action = 15` (assigné à Fernando) ✅
   - **OU** `actioncomm.fk_user_done = 15` (terminé par Fernando)
   - **OU** `actioncomm_resources.fk_element = 15` (Fernando dans les ressources)

---

## Vérification du système

### Étape 1: Vérifier que l'utilisateur mobile est lié

```sql
-- Vérifier le lien pour Fernando
SELECT
    m.rowid as mobile_id,
    m.email,
    m.firstname,
    m.lastname,
    m.dolibarr_user_id,
    u.login as dolibarr_login,
    u.lastname as dol_lastname,
    u.firstname as dol_firstname
FROM llx_mv3_mobile_users m
LEFT JOIN llx_user u ON u.rowid = m.dolibarr_user_id
WHERE m.email = 'fernando@mv3.com';
```

**Résultat attendu :**
```
mobile_id | email              | firstname | lastname | dolibarr_user_id | dolibarr_login | dol_lastname | dol_firstname
----------|--------------------|-----------|-----------|--------------------|----------------|--------------|---------------
5         | fernando@mv3.com   | Fernando  | Silva     | 15                 | fernando       | Silva        | Fernando
```

**Si `dolibarr_user_id` est NULL :**
```sql
-- Corriger le lien (remplacer 15 par le bon ID Dolibarr)
UPDATE llx_mv3_mobile_users
SET dolibarr_user_id = 15
WHERE email = 'fernando@mv3.com';
```

### Étape 2: Vérifier les événements dans Dolibarr

```sql
-- Chercher les événements assignés à Fernando (ID = 15)
SELECT
    a.id,
    a.label,
    a.datep as date_debut,
    a.datep2 as date_fin,
    a.fk_user_action as assigne_a,
    s.nom as client,
    p.ref as projet
FROM llx_actioncomm a
LEFT JOIN llx_societe s ON s.rowid = a.fk_soc
LEFT JOIN llx_projet p ON p.rowid = a.fk_project
WHERE a.fk_user_action = 15
ORDER BY a.datep DESC
LIMIT 10;
```

**Résultat attendu :**
```
id  | label                                      | date_debut          | date_fin            | assigne_a | client        | projet
----|--------------------------------------------|--------------------|--------------------|-----------|--------------|---------
234 | Finir Appartements Ingold Sol Complet      | 2026-01-10 08:00:00| 2026-01-10 17:00:00| 15        | INGOLD SA    | APPT-2024-15
```

**Si aucun résultat :**
- Vérifier que les événements existent dans Dolibarr > Agenda
- Vérifier que le champ "Assigné à" est bien renseigné avec FERNANDO
- Vérifier les dates

### Étape 3: Utiliser l'endpoint de diagnostic

```bash
# Test avec curl (remplacer TOKEN par le vrai token)
curl -H "X-Auth-Token: TOKEN" \
  "https://votre-domaine.com/custom/mv3pro_portail/api/v1/planning_debug.php?from=2026-01-01&to=2026-12-31"
```

**Résultat attendu :**
```json
{
  "success": true,
  "user_info": {
    "auth_mode": "mobile_token",
    "mobile_user_id": 5,
    "dolibarr_user_id": 15,
    "email": "fernando@mv3.com",
    "name": "Fernando Silva",
    "is_unlinked": false
  },
  "dolibarr_user": {
    "rowid": 15,
    "login": "fernando",
    "lastname": "Silva",
    "firstname": "Fernando",
    "email": "fernando@dolibarr.com",
    "statut": 1,
    "statut_label": "Actif"
  },
  "events_stats": {
    "as_author": 5,
    "as_action_user": 23,
    "as_resource": 0,
    "total_in_period": 23
  },
  "events_samples": [
    {
      "id": 234,
      "label": "Finir Appartements Ingold Sol Complet",
      "datep": "2026-01-10 08:00:00",
      "datep2": "2026-01-10 17:00:00",
      "client": "INGOLD SA",
      "projet": "APPT-2024-15 - Appartements Ingold",
      "fk_user_author": 1,
      "fk_user_action": 15,
      "fk_user_done": 0
    }
  ],
  "diagnostic": [
    {
      "type": "OK",
      "message": "23 événement(s) trouvé(s) dans la période du 2026-01-01 au 2026-12-31"
    }
  ]
}
```

---

## Code actuel du planning

### Fichier: `/api/v1/planning.php`

Le code actuel **fonctionne correctement** :

```php
// Ligne 38-39: Récupérer l'ID utilisateur Dolibarr depuis l'auth
$user_id = $auth['user_id'];  // = dolibarr_user_id

// Ligne 42-46: Si compte non lié, retourner []
if (!$user_id) {
    http_response_code(200);
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

// Ligne 63-66: Filtrer les événements par dolibarr_user_id
WHERE (a.fk_user_author = ".(int)$user_id."
       OR a.fk_user_action = ".(int)$user_id."  ✅ Assigné à
       OR a.fk_user_done = ".(int)$user_id."
       OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$user_id."))
```

✅ **Le filtre utilise bien `dolibarr_user_id`**

---

## Fichier: `/api/v1/_bootstrap.php`

La fonction `require_auth()` retourne :

```php
$auth_result = [
    'mode' => 'mobile_token',
    'mobile_user_id' => $session->mobile_user_id,      // rowid llx_mv3_mobile_users
    'user_id' => $session->dolibarr_user_id,           // ✅ ID Dolibarr
    'email' => $session->email,
    'name' => trim($session->firstname . ' ' . $session->lastname),
    'is_unlinked' => empty($session->dolibarr_user_id),
    // ...
];
```

✅ **Le système retourne bien le `dolibarr_user_id`**

---

## Problèmes courants et solutions

### Problème 1: Le planning est vide

**Diagnostic:**
```sql
-- Vérifier le lien
SELECT dolibarr_user_id FROM llx_mv3_mobile_users WHERE email = 'fernando@mv3.com';
```

**Si NULL ou 0:**
```sql
-- Trouver l'ID Dolibarr de Fernando
SELECT rowid, login FROM llx_user WHERE login = 'fernando';
-- Résultat: rowid = 15

-- Mettre à jour le lien
UPDATE llx_mv3_mobile_users
SET dolibarr_user_id = 15
WHERE email = 'fernando@mv3.com';
```

### Problème 2: Certains événements n'apparaissent pas

**Vérifier dans Dolibarr:**
1. Menu > Agenda
2. Ouvrir l'événement
3. Vérifier le champ **"Assigné à"** (fk_user_action)
4. S'assurer que FERNANDO est bien sélectionné

**Si l'événement n'a pas d'utilisateur assigné:**
```sql
-- Assigner l'événement à Fernando (ID = 15)
UPDATE llx_actioncomm
SET fk_user_action = 15
WHERE id = 234;  -- ID de l'événement
```

### Problème 3: Événements d'un autre utilisateur visibles

**Diagnostic:**
```sql
-- Vérifier quel dolibarr_user_id est utilisé
SELECT rowid, email, dolibarr_user_id
FROM llx_mv3_mobile_users
WHERE email = 'fernando@mv3.com';
```

**Si le dolibarr_user_id est incorrect:**
```sql
-- Corriger avec le bon ID
UPDATE llx_mv3_mobile_users
SET dolibarr_user_id = 15  -- Le vrai ID de Fernando
WHERE email = 'fernando@mv3.com';
```

---

## Création d'un nouvel utilisateur mobile lié

### Étape 1: Trouver l'ID Dolibarr

```sql
SELECT rowid, login, lastname, firstname, email
FROM llx_user
WHERE login = 'fernando';
-- Résultat: rowid = 15
```

### Étape 2: Créer l'utilisateur mobile avec le lien

```sql
INSERT INTO llx_mv3_mobile_users (
    email,
    password_hash,
    dolibarr_user_id,  -- ✅ Lien important
    firstname,
    lastname,
    phone,
    role,
    is_active,
    created_at
)
VALUES (
    'fernando@mv3.com',
    '$2y$10$...hash...', -- Utiliser password_hash('motdepasse', PASSWORD_BCRYPT)
    15,  -- ✅ ID de l'utilisateur Dolibarr
    'Fernando',
    'Silva',
    '+33612345678',
    'employee',
    1,
    NOW()
);
```

### Étape 3: Vérifier le lien

```sql
SELECT
    m.rowid,
    m.email,
    m.dolibarr_user_id,
    u.login as dolibarr_login
FROM llx_mv3_mobile_users m
LEFT JOIN llx_user u ON u.rowid = m.dolibarr_user_id
WHERE m.email = 'fernando@mv3.com';
```

**Résultat attendu:**
```
rowid | email             | dolibarr_user_id | dolibarr_login
------|-------------------|--------------------|----------------
5     | fernando@mv3.com  | 15                 | fernando
```

---

## Endpoint de diagnostic

### URL

```
GET /custom/mv3pro_portail/api/v1/planning_debug.php
```

### Paramètres

- `from` (optionnel) : Date début (YYYY-MM-DD), défaut = aujourd'hui
- `to` (optionnel) : Date fin (YYYY-MM-DD), défaut = aujourd'hui + 30 jours

### Utilisation

#### Via curl

```bash
curl -H "X-Auth-Token: VOTRE_TOKEN" \
  "https://votre-domaine.com/custom/mv3pro_portail/api/v1/planning_debug.php?from=2026-01-01&to=2026-12-31"
```

#### Via la PWA (à ajouter dans Debug.tsx)

Ajouter un bouton dans la page Debug pour appeler cet endpoint.

### Réponse

```json
{
  "success": true,
  "user_info": {
    "auth_mode": "mobile_token",
    "mobile_user_id": 5,
    "dolibarr_user_id": 15,
    "email": "fernando@mv3.com",
    "name": "Fernando Silva",
    "is_unlinked": false
  },
  "dolibarr_user": {
    "rowid": 15,
    "login": "fernando",
    "lastname": "Silva",
    "firstname": "Fernando",
    "statut": 1
  },
  "events_stats": {
    "as_author": 5,
    "as_action_user": 23,
    "as_resource": 0,
    "total_in_period": 23
  },
  "events_samples": [...],
  "diagnostic": [
    {
      "type": "OK",
      "message": "23 événement(s) trouvé(s)"
    }
  ]
}
```

---

## Checklist de vérification

### Configuration utilisateur mobile

- [ ] L'utilisateur mobile existe dans `llx_mv3_mobile_users`
- [ ] Le champ `dolibarr_user_id` est renseigné (non NULL)
- [ ] Le `dolibarr_user_id` correspond au bon utilisateur Dolibarr
- [ ] L'utilisateur mobile est actif (`is_active = 1`)

### Configuration utilisateur Dolibarr

- [ ] L'utilisateur Dolibarr existe dans `llx_user`
- [ ] L'utilisateur Dolibarr est actif (`statut = 1`)
- [ ] L'utilisateur Dolibarr a le bon `rowid` (correspond au `dolibarr_user_id`)

### Configuration des événements

- [ ] Les événements existent dans Dolibarr > Agenda
- [ ] Les événements ont un utilisateur assigné (champ "Assigné à")
- [ ] Le champ `fk_user_action` pointe vers le bon `rowid` utilisateur
- [ ] Les dates des événements sont correctes
- [ ] Les événements ne sont pas supprimés

### Test de l'API

- [ ] L'utilisateur peut se connecter à la PWA
- [ ] L'endpoint `/api/v1/me.php` retourne les bonnes infos
- [ ] L'endpoint `/api/v1/planning_debug.php` affiche le lien correct
- [ ] L'endpoint `/api/v1/planning.php` retourne les événements

---

## Conclusion

Le système de planning **fonctionne déjà correctement** :

✅ Le code utilise bien `dolibarr_user_id` pour filtrer les événements
✅ Le lien entre utilisateur mobile et Dolibarr est géré via `llx_mv3_mobile_users.dolibarr_user_id`
✅ Les événements sont filtrés par `fk_user_action` (assigné à) et autres champs

**Si le planning est vide, il faut vérifier :**
1. Que le `dolibarr_user_id` est bien renseigné dans `llx_mv3_mobile_users`
2. Que les événements sont bien assignés à cet utilisateur dans Dolibarr
3. Que les dates des événements correspondent à la période demandée

**Utilisez l'endpoint de diagnostic** `planning_debug.php` pour identifier rapidement le problème.
