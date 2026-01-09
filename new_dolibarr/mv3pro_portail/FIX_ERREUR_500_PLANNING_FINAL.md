# Fix Erreur 500 Planning - Correction Finale

## Problème identifié

L'erreur 500 persistait même après les corrections précédentes.

## Cause racine : Erreur de syntaxe PHP

**Ligne 68 du fichier `api/v1/planning.php`** : Il manquait `."` à la fin de la ligne de concaténation SQL.

### Code erroné

```php
AND a.entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1)
AND (ac.code IN ('AC_POS', 'AC_plan') OR ac.code IS NULL)
```

### Code corrigé

```php
AND a.entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1)."
AND (ac.code IN ('AC_POS', 'AC_plan') OR ac.code IS NULL)
```

### Explication

Sans le `."` à la fin de la ligne 68, PHP interprétait :
```php
... = ".((isset($conf->entity) ...))AND (ac.code IN ...
                                    ^^^
                              Identifiant invalide !
```

Cela causait une erreur de parsing :
```
PHP Parse error: syntax error, unexpected identifier "IN"
```

## Méthode de diagnostic utilisée

```bash
php -l planning.php
```

Cette commande vérifie la syntaxe PHP sans exécuter le fichier.

## Corrections complémentaires appliquées

### 1. Vérification environnement Dolibarr
```php
global $db, $conf;

if (!isset($db) || !isset($conf)) {
    http_response_code(500);
    echo json_encode(['error' => 'Environnement Dolibarr non chargé']);
    exit;
}
```

### 2. Suppression headers en double
```php
// Avant
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($events);

// Après
http_response_code(200);
echo json_encode($events);
// Note: headers déjà envoyés par _bootstrap.php
```

### 3. Logs SQL améliorés
```php
if (!$resql) {
    $error_msg = 'Erreur lors de la récupération du planning';
    if ($db->lasterror()) {
        $error_msg .= ': ' . $db->lasterror();
    }
    error_log('[MV3 Planning] SQL Error: ' . $error_msg);
    error_log('[MV3 Planning] SQL Query: ' . $sql);
    json_error($error_msg, 'DATABASE_ERROR', 500);
}
```

### 4. Validation entity
```php
// Validation améliorée de $conf->entity
AND a.entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1)."
```

## Fichier de diagnostic créé

`api/v1/test_planning.php` - Permet de tester étape par étape :
1. Chargement bootstrap
2. Variables globales
3. Authentification
4. Requête SQL

Accès : `/custom/mv3pro_portail/api/v1/test_planning.php`

## Vérifications effectuées

```bash
✅ php -l planning.php  → No syntax errors
✅ php -l rapports.php  → No syntax errors
✅ npm run build        → Build réussi (220 KB)
```

## Test

Rafraîchir la page Planning dans Bolt preview.

L'erreur 500 devrait être résolue. Si le planning est vide, c'est normal pour les comptes "unlinked".

## Fichiers modifiés

- ✅ `api/v1/planning.php` - Erreur syntaxe corrigée + améliorations
- ✅ `api/v1/rapports.php` - Headers optimisés
- ✅ `api/v1/test_planning.php` - Fichier de diagnostic créé

## Leçon apprise

**Toujours vérifier la syntaxe PHP avec `php -l` avant de déployer !**

Cette simple commande aurait détecté l'erreur immédiatement et évité plusieurs tentatives de correction.

Date: 2026-01-09
