# Fix Erreur 500 - Page Rapports

## Problème identifié

Après login réussi, la page Rapports affichait "Erreur 500".

## Causes trouvées

### 1. Filtre utilisateur manquant pour comptes unlinked
**Ligne 48-50 :** Si l'utilisateur est "unlinked" (pas lié à Dolibarr), `$auth['user_id']` était `null`, donc aucun filtre WHERE n'était ajouté, ce qui pouvait causer des problèmes.

### 2. Accès à `$conf->entity` non sécurisé
**Ligne 41 :** Accès direct à `$conf->entity` sans vérifier son existence pouvait causer une erreur PHP si le bootstrap Dolibarr n'était pas correctement chargé.

### 3. Sous-requête vers table inexistante
**Ligne 83 :** Sous-SELECT vers `llx_mv3_rapport_photo` qui peut ne pas exister sur tous les serveurs.

### 4. Format de réponse incompatible
L'API retournait `id`, `date`, `projet_title` mais le frontend TypeScript attendait `rowid`, `date_rapport`, `projet_nom`.

## Corrections appliquées

### api/v1/rapports.php

#### 1. Sécurisation entity
```php
// Avant
$where[] = "r.entity = ".(int)$conf->entity;

// Après
$entity = isset($conf->entity) ? (int)$conf->entity : 1;
$where[] = "r.entity = ".$entity;
```

#### 2. Gestion comptes unlinked
```php
// Ajouté
} elseif (!empty($auth['mobile_user_id'])) {
    // Si compte unlinked, retourner liste vide pour l'instant
    $where[] = "1 = 0";
} else {
    $where[] = "1 = 0";
}
```

#### 3. Suppression sous-requête photos
```php
// Avant
(SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE fk_rapport = r.rowid) as nb_photos

// Après
0 as nb_photos
```

#### 4. Format de réponse compatible frontend
```php
$rapport = [
    'rowid' => (int)$obj->rowid,           // Frontend attend rowid
    'id' => (int)$obj->rowid,              // Alias pour compatibilité
    'date_rapport' => $obj->date_rapport,  // Frontend attend date_rapport
    'date' => $obj->date_rapport,          // Alias
    'fk_user' => $obj->fk_user ? (int)$obj->fk_user : null,
    'projet_nom' => $obj->projet_title,    // Frontend attend projet_nom
    'projet_title' => $obj->projet_title,  // Alias
    'description' => $obj->travaux_realises, // Alias
    // ... avec protection NULL sur heure_debut/heure_fin
];
```

## Comportement attendu

### Pour comptes liés (avec dolibarr_user_id)
- Liste leurs propres rapports
- Filtre : `WHERE fk_user = {dolibarr_user_id}`

### Pour comptes unlinked (sans dolibarr_user_id)
- Liste vide pour le moment
- Filtre : `WHERE 1 = 0`
- À améliorer plus tard avec table de correspondance

### Pour admins
- Peuvent filtrer par utilisateur avec `?user_id=X`

## Test

Rafraîchir la page Rapports dans Bolt preview :
1. ✅ Pas d'erreur 500
2. ✅ Message "Aucun rapport enregistré" si liste vide
3. ✅ Boutons "Rapport simple" et "Rapport PRO" visibles

## Prochaines étapes

Pour permettre aux comptes unlinked de créer des rapports :
1. Créer une table `llx_mv3_rapport_mobile` pour les rapports créés par comptes non liés
2. Ou ajouter colonne `mobile_user_id` dans `llx_mv3_rapport`
3. Adapter le filtre dans `rapports.php`

## Fichiers modifiés

- ✅ `api/v1/rapports.php` - 4 corrections appliquées

## Build

✅ Build réussi : 220 KB

Date: 2026-01-09
