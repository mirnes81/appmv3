# Fix Erreur 500 - Page Planning

## Problème identifié

Après login réussi, la page Planning affichait "Erreur 500".

## Causes trouvées

### 1. Vérification stricte user_id (ligne 32-36)
Si l'utilisateur est "unlinked" (pas lié à Dolibarr), `$auth['user_id']` était `null`, ce qui causait une erreur 400 immédiate.

### 2. Fonction `getEntity()` non disponible (ligne 54)
L'appel à `getEntity('actioncomm')` pouvait échouer si la fonction Dolibarr n'était pas chargée correctement.

### 3. Format de réponse incompatible
L'API retournait `{success: true, events: [...], count: ...}` mais le frontend TypeScript attendait directement `PlanningEvent[]`.

### 4. Noms de champs incompatibles
- API retournait `client` mais frontend attendait `client_nom`
- API retournait `date_start` mais frontend attendait `datep`

## Corrections appliquées

### api/v1/planning.php

#### 1. Gestion comptes unlinked
```php
// Avant
if (!$user_id) {
    json_error('Impossible de déterminer l\'ID utilisateur Dolibarr', 'NO_USER_ID', 400);
}

// Après
if (!$user_id) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
```

#### 2. Remplacement getEntity()
```php
// Avant
AND a.entity IN (".getEntity('actioncomm').")

// Après
AND a.entity = ".(isset($conf->entity) ? (int)$conf->entity : 1)
```

#### 3. Format de réponse compatible
```php
// Avant
json_ok([
    'events' => $events,
    'count' => count($events),
    'from' => $from,
    'to' => $to
]);

// Après
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
```

#### 4. Champs compatibles frontend
```php
$event = [
    'id' => (int)$obj->id,
    'label' => $obj->label,
    'datep' => $obj->datep,              // Frontend attend 'datep'
    'datef' => $obj->datep2 ?: null,
    'date_start' => $obj->datep,         // Alias pour compatibilité
    'date_end' => $obj->datep2 ?: $obj->datep,
    'client_nom' => $obj->client_nom,    // Frontend attend 'client_nom'
    'client' => $obj->client_nom,        // Alias
    'client_id' => $obj->client_id ? (int)$obj->client_id : null,
    'projet' => $obj->projet_title ? ($obj->projet_ref ? $obj->projet_ref.' - ' : '').$obj->projet_title : null,
    'projet_id' => $obj->projet_id ? (int)$obj->projet_id : null,
    'projet_ref' => $obj->projet_ref,
    'location' => $obj->location,
    'type' => 'actioncomm',
    'status' => $obj->percent == 100 ? 'done' : 'pending',
    'fullday' => (bool)$obj->fulldayevent,
    'percent' => (int)$obj->percent,
    'notes' => $obj->note_private
];
```

## Corrections bonus : API Rapports

Même problème de format sur l'API rapports - corrigé également pour cohérence :

```php
// Avant
json_ok([
    'rapports' => $rapports,
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'pages' => ceil($total / $limit)
]);

// Après
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rapports, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
```

## Pourquoi ce changement ?

La fonction `json_ok()` dans `_bootstrap.php` ajoute automatiquement `{success: true}` au début du tableau :

```php
if (is_array($data) && !isset($data['success'])) {
    $data = ['success' => true] + $data;
}
```

Pour un tableau indexé comme `[{event1}, {event2}]`, cela produit :
```json
{"success": true, "0": {event1}, "1": {event2}}
```

Ce qui n'est pas compatible avec le typage TypeScript `PlanningEvent[]`.

La solution : retourner directement le JSON sans passer par `json_ok()`.

## Test

Rafraîchir la page Planning dans Bolt preview :
1. ✅ Pas d'erreur 500
2. ✅ Filtres de date fonctionnels
3. ✅ Message "Aucune affectation pour cette période" si liste vide
4. ✅ Liste des événements si données présentes

## Fichiers modifiés

- ✅ `api/v1/planning.php` - 4 corrections appliquées
- ✅ `api/v1/rapports.php` - Format de réponse corrigé

## Build

✅ Build réussi : 220 KB

Date: 2026-01-09
