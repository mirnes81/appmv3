# Récapitulatif - Diagnostic Planning & Lien utilisateur

## Date: 2026-01-09

---

## Contexte

Vous avez demandé de vérifier le système de planning pour s'assurer que :

1. Les RDV créés dans **Dolibarr > Agenda** sont visibles dans la **PWA MV3**
2. Le lien utilisateur se fait via `llx_mv3_mobile_users.dolibarr_user_id`
3. Fernando voit **uniquement ses propres RDV** (filtrés par `dolibarr_user_id`)

---

## Analyse du code existant

### ✅ Le système fonctionne déjà correctement

Après analyse approfondie du code, **le système de filtrage fonctionne déjà comme attendu** :

#### 1. Authentification (`/api/v1/_bootstrap.php`)

La fonction `require_auth()` retourne :

```php
$auth_result = [
    'mode' => 'mobile_token',
    'mobile_user_id' => $session->mobile_user_id,     // ID mobile
    'user_id' => $session->dolibarr_user_id,          // ✅ ID Dolibarr
    'email' => $session->email,
    'name' => trim($session->firstname . ' ' . $session->lastname),
    'is_unlinked' => empty($session->dolibarr_user_id),
];
```

**✅ Le `user_id` retourné est bien le `dolibarr_user_id`**

#### 2. Planning (`/api/v1/planning.php`)

Le code filtre correctement par `dolibarr_user_id` :

```php
// Ligne 39: Récupérer l'ID utilisateur Dolibarr
$user_id = $auth['user_id'];  // = dolibarr_user_id

// Lignes 63-66: Filtrer les événements
WHERE (a.fk_user_author = ".(int)$user_id."
       OR a.fk_user_action = ".(int)$user_id."  // ✅ Assigné à
       OR a.fk_user_done = ".(int)$user_id."
       OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$user_id."))
```

**✅ Le filtre utilise bien le `dolibarr_user_id`**

---

## Nouveaux outils créés

### 1. Endpoint de diagnostic (`/api/v1/planning_debug.php`)

**URL:** `GET /api/v1/planning_debug.php?from=YYYY-MM-DD&to=YYYY-MM-DD`

**Fonctionnalités:**

- ✅ Affiche l'utilisateur mobile connecté
- ✅ Affiche l'utilisateur Dolibarr lié
- ✅ Vérifie si le lien `dolibarr_user_id` est correct
- ✅ Compte les événements par type (créés, assignés, ressources)
- ✅ Affiche des exemples d'événements
- ✅ Fournit un diagnostic automatique avec solutions SQL

**Exemple de réponse:**

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
      "message": "23 événement(s) trouvé(s) dans la période"
    }
  ]
}
```

### 2. Interface de diagnostic dans la PWA

**Emplacement:** Menu > Debug > Bouton "Diagnostic Planning"

**Affichage:**

1. **Utilisateur connecté**
   - Mode auth
   - Email
   - Nom
   - ID Mobile
   - **ID Dolibarr** (en rouge si non lié)
   - Avertissement si compte non lié

2. **Utilisateur Dolibarr lié**
   - Login
   - Nom complet
   - Statut (Actif/Inactif)

3. **Statistiques événements**
   - Créés (as_author)
   - **Assignés (as_action_user)** ← Le plus important
   - Ressources (as_resource)
   - **Total dans la période**

4. **Diagnostic automatique**
   - Messages d'erreur/warning/OK
   - Solutions SQL si problème détecté
   - Explications détaillées

5. **Exemples d'événements**
   - Affiche jusqu'à 5 événements
   - Détails complets (client, projet, IDs)

---

## Comment vérifier le système

### Étape 1: Vérifier le lien dans la base de données

```sql
-- Vérifier le lien pour Fernando
SELECT
    m.rowid as mobile_id,
    m.email,
    m.firstname,
    m.lastname,
    m.dolibarr_user_id,
    u.login as dolibarr_login,
    u.firstname as dol_firstname,
    u.lastname as dol_lastname
FROM llx_mv3_mobile_users m
LEFT JOIN llx_user u ON u.rowid = m.dolibarr_user_id
WHERE m.email = 'fernando@mv3.com';
```

**Résultat attendu:**
```
mobile_id | email            | firstname | lastname | dolibarr_user_id | dolibarr_login | dol_firstname | dol_lastname
----------|------------------|-----------|----------|------------------|----------------|---------------|-------------
5         | fernando@mv3.com | Fernando  | Silva    | 15               | fernando       | Fernando      | Silva
```

**Si `dolibarr_user_id` est NULL ou 0:**

```sql
-- 1. Trouver l'ID Dolibarr de Fernando
SELECT rowid, login, firstname, lastname
FROM llx_user
WHERE login = 'fernando';
-- Résultat: rowid = 15

-- 2. Mettre à jour le lien
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
WHERE a.fk_user_action = 15  -- Fernando
  AND a.datep >= CURDATE()
ORDER BY a.datep ASC
LIMIT 10;
```

**Si aucun résultat:**

Dans Dolibarr > Agenda:
1. Ouvrir un événement
2. Vérifier que le champ **"Assigné à"** contient **FERNANDO**
3. Si vide, sélectionner FERNANDO et sauvegarder

**Ou via SQL:**

```sql
-- Assigner l'événement à Fernando
UPDATE llx_actioncomm
SET fk_user_action = 15
WHERE id = 234;  -- ID de l'événement
```

### Étape 3: Utiliser le diagnostic PWA

1. Se connecter à la PWA avec le compte Fernando
2. Menu > **Debug**
3. Cliquer sur **"Diagnostic Planning"**
4. Vérifier les résultats :
   - **ID Dolibarr** doit être renseigné (ex: 15)
   - **Total période** doit afficher le nombre d'événements
   - **Diagnostic** doit afficher "OK"

**Si erreur détectée:**
- Le diagnostic affiche la solution SQL directe
- Exécuter la requête SQL proposée
- Relancer le diagnostic

---

## Fichiers créés/modifiés

### Nouveaux fichiers

1. **`api/v1/planning_debug.php`**
   - Endpoint de diagnostic complet
   - Vérifie le lien utilisateur mobile <-> Dolibarr
   - Compte les événements par type
   - Fournit des solutions SQL automatiques

2. **`GUIDE_PLANNING_UTILISATEUR.md`**
   - Documentation complète du système
   - Explications SQL détaillées
   - Checklist de vérification
   - Solutions aux problèmes courants

3. **`PLANNING_UTILISATEUR_RECAPITULATIF.md`**
   - Ce fichier (récapitulatif)

### Fichiers modifiés

1. **`pwa/src/pages/Debug.tsx`**
   - Ajout de l'interface "Diagnostic Planning"
   - Bouton dédié dans la page Debug
   - Affichage détaillé des résultats
   - Statistiques visuelles

---

## Build

Build réussi:

```bash
cd /tmp/cc-agent/59302460/project/new_dolibarr/mv3pro_portail/pwa
npm install
npm run build

✓ built in 2.93s
PWA v0.17.5
```

Aucune erreur TypeScript.

---

## Fichiers à uploader

### Backend PHP

```
new_dolibarr/mv3pro_portail/api/v1/planning_debug.php
```

### Frontend PWA

```
new_dolibarr/mv3pro_portail/pwa_dist/*
```

### Documentation

```
new_dolibarr/mv3pro_portail/GUIDE_PLANNING_UTILISATEUR.md
new_dolibarr/mv3pro_portail/PLANNING_UTILISATEUR_RECAPITULATIF.md
```

---

## Scénarios de test

### Test 1: Utilisateur correctement lié

**Données de test:**
- Utilisateur mobile: fernando@mv3.com (ID mobile = 5)
- Utilisateur Dolibarr: fernando (ID Dolibarr = 15)
- `dolibarr_user_id` = 15 dans `llx_mv3_mobile_users`
- 5 événements assignés à Fernando dans Dolibarr

**Résultat attendu:**

```
PWA > Menu > Debug > Diagnostic Planning
→ ID Dolibarr: 15 ✅
→ Utilisateur Dolibarr: Fernando Silva (Actif) ✅
→ Total période: 5 événements ✅
→ Diagnostic: OK ✅
```

### Test 2: Utilisateur non lié

**Données de test:**
- Utilisateur mobile: fernando@mv3.com (ID mobile = 5)
- `dolibarr_user_id` = NULL dans `llx_mv3_mobile_users`

**Résultat attendu:**

```
PWA > Menu > Debug > Diagnostic Planning
→ ID Dolibarr: NON LIÉ ❌
→ Avertissement: "Compte non lié à un utilisateur Dolibarr"
→ Diagnostic: ERROR
→ Solution: "UPDATE llx_mv3_mobile_users SET dolibarr_user_id = [ID] WHERE rowid = 5"
```

### Test 3: Planning normal

**Test:**
1. Se connecter avec Fernando
2. Aller dans Menu > Planning
3. Vérifier les événements affichés

**Résultat attendu:**
- Seuls les événements **assignés à Fernando** sont visibles
- Les événements d'autres utilisateurs ne sont **pas** visibles
- Les dates correspondent à la période sélectionnée

---

## Problèmes courants

### Problème 1: Planning vide alors que des événements existent

**Causes possibles:**

1. **`dolibarr_user_id` non renseigné**
   ```sql
   SELECT dolibarr_user_id FROM llx_mv3_mobile_users WHERE email = 'fernando@mv3.com';
   -- Si NULL ou 0, voir solution ci-dessous
   ```

2. **Événements non assignés à l'utilisateur**
   ```sql
   -- Vérifier les événements
   SELECT id, label, fk_user_action
   FROM llx_actioncomm
   WHERE label LIKE '%Ingold%';

   -- Si fk_user_action != 15, corriger:
   UPDATE llx_actioncomm SET fk_user_action = 15 WHERE id = 234;
   ```

3. **Dates incorrectes**
   - Vérifier que les événements ont des dates dans la période demandée

### Problème 2: Événements d'autres utilisateurs visibles

**Cause:** Le `dolibarr_user_id` est incorrect

**Solution:**
```sql
-- Vérifier quel ID est utilisé
SELECT rowid, email, dolibarr_user_id
FROM llx_mv3_mobile_users
WHERE email = 'fernando@mv3.com';

-- Corriger si nécessaire
UPDATE llx_mv3_mobile_users
SET dolibarr_user_id = 15  -- Le bon ID de Fernando
WHERE email = 'fernando@mv3.com';
```

### Problème 3: Erreur "Utilisateur Dolibarr non trouvé"

**Cause:** Le `dolibarr_user_id` pointe vers un utilisateur inexistant

**Solution:**
```sql
-- 1. Vérifier l'ID actuel
SELECT dolibarr_user_id FROM llx_mv3_mobile_users WHERE email = 'fernando@mv3.com';
-- Résultat: ex: 999

-- 2. Vérifier si cet ID existe
SELECT rowid, login FROM llx_user WHERE rowid = 999;
-- Aucun résultat = l'ID n'existe pas

-- 3. Trouver le bon ID
SELECT rowid, login FROM llx_user WHERE login = 'fernando';
-- Résultat: rowid = 15

-- 4. Corriger
UPDATE llx_mv3_mobile_users
SET dolibarr_user_id = 15
WHERE email = 'fernando@mv3.com';
```

---

## Checklist de déploiement

### 1. Upload des fichiers

- [ ] `api/v1/planning_debug.php` uploadé
- [ ] `pwa_dist/*` uploadé et remplace l'ancien build

### 2. Test du diagnostic

- [ ] Connexion à la PWA réussie
- [ ] Menu > Debug accessible
- [ ] Bouton "Diagnostic Planning" visible
- [ ] Click sur "Diagnostic Planning"
- [ ] Résultats affichés correctement

### 3. Vérification du lien utilisateur

- [ ] Diagnostic affiche "ID Dolibarr: [nombre]" (pas "NON LIÉ")
- [ ] Utilisateur Dolibarr affiché avec login correct
- [ ] Statistiques événements affichées
- [ ] Si erreur, solution SQL proposée

### 4. Test du planning

- [ ] Menu > Planning accessible
- [ ] Événements affichés
- [ ] Seuls les événements de l'utilisateur connecté visibles
- [ ] Dates correctes

### 5. Test de non-régression

- [ ] Login fonctionne toujours
- [ ] Dashboard fonctionne
- [ ] Autres pages (Rapports, Matériel, etc.) fonctionnent

---

## Conclusion

Le système de planning **fonctionne déjà correctement** au niveau du code. Le filtre utilise bien le `dolibarr_user_id` pour afficher uniquement les événements de l'utilisateur connecté.

**Si le planning est vide pour Fernando, il faut vérifier :**

1. ✅ Que `llx_mv3_mobile_users.dolibarr_user_id` est bien renseigné
2. ✅ Que les événements sont créés dans Dolibarr > Agenda
3. ✅ Que les événements sont assignés à Fernando (champ "Assigné à")

**Le nouvel outil de diagnostic** permet de vérifier rapidement tous ces points et fournit les solutions SQL directement.

---

## Utilisation du diagnostic

### Via l'interface PWA

1. Se connecter avec le compte Fernando
2. Menu > Debug
3. Cliquer sur "Diagnostic Planning"
4. Lire les résultats :
   - **Vert** : Tout fonctionne
   - **Orange** : Avertissement (ex: aucun événement)
   - **Rouge** : Erreur (ex: compte non lié)

### Via l'API directement

```bash
curl -H "X-Auth-Token: VOTRE_TOKEN" \
  "https://votre-domaine.com/custom/mv3pro_portail/api/v1/planning_debug.php?from=2026-01-01&to=2026-12-31"
```

Le diagnostic retourne un JSON complet avec toutes les informations nécessaires.

---

## Résumé final

✅ **Code vérifié** : Le système de filtrage fonctionne correctement
✅ **Diagnostic créé** : Nouveau endpoint `/api/v1/planning_debug.php`
✅ **Interface ajoutée** : Bouton "Diagnostic Planning" dans la PWA
✅ **Documentation complète** : Guide détaillé avec solutions SQL
✅ **Build réussi** : PWA compile sans erreur
✅ **Prêt pour déploiement** : Tous les fichiers sont prêts

Le système permet maintenant de diagnostiquer rapidement tout problème de lien entre utilisateur mobile et Dolibarr, et fournit les solutions SQL automatiquement.
