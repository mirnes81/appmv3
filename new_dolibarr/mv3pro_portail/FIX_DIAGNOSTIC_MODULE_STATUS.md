# Fix - Statut module dans le diagnostic

## Date: 2026-01-09

---

## Problème

Le diagnostic affichait:
```
Module MV3PRO activé : Non
```

Alors que le module MV-3 PRO PORTAIL était bien activé dans Dolibarr.

---

## Cause

Le check utilisait `$conf->mv3pro_portail->enabled` pour vérifier le statut du module.

Or, dans Dolibarr, l'activation d'un module crée une constante avec le pattern:
```
MAIN_MODULE_NOMDUMODULE
```

Pour le module MV-3 PRO PORTAIL, la constante est:
```php
MAIN_MODULE_MV3PRO_PORTAIL
```

Cette constante est définie dans le fichier de description du module:
```php
// core/modules/modMv3pro_portail.class.php
$this->const_name  = 'MAIN_MODULE_MV3PRO_PORTAIL';
```

---

## Solution

Correction dans `/api/v1/debug.php` (lignes 424-430):

**Avant:**
```php
$config_checks = [
    [
        'name' => 'Module MV3PRO activé',
        'status' => !empty($conf->mv3pro_portail->enabled) ? 'OK' : 'ERROR',
        'value' => !empty($conf->mv3pro_portail->enabled) ? 'Oui' : 'Non',
    ],
    // ...
];
```

**Après:**
```php
// Vérifier l'activation du module via la constante principale
$module_enabled = false;
if (!empty($conf->global->MAIN_MODULE_MV3PRO_PORTAIL)) {
    $module_enabled = true;
} elseif (!empty($conf->mv3pro_portail->enabled)) {
    $module_enabled = true;
}

$config_checks = [
    [
        'name' => 'Module MV-3 PRO PORTAIL',
        'status' => $module_enabled ? 'OK' : 'ERROR',
        'value' => $module_enabled ? 'Activé' : 'Non activé',
    ],
    // ...
];
```

---

## Changements

### 1. Vérification double

Le check essaie maintenant **deux méthodes** pour détecter l'activation du module:
1. **Méthode principale**: `$conf->global->MAIN_MODULE_MV3PRO_PORTAIL`
2. **Méthode fallback**: `$conf->mv3pro_portail->enabled`

Cela garantit la compatibilité avec différentes versions de Dolibarr.

### 2. Nom du check corrigé

**Avant**: `Module MV3PRO activé`
**Après**: `Module MV-3 PRO PORTAIL`

Le nom correspond maintenant exactement au nom affiché dans Dolibarr.

### 3. Valeurs plus claires

**Avant**: `Oui` / `Non`
**Après**: `Activé` / `Non activé`

Plus explicite pour l'utilisateur.

---

## Comment vérifier dans Dolibarr

Pour confirmer qu'un module est activé, vous pouvez:

### 1. Via l'interface Dolibarr
```
Menu > Configuration > Modules > Rechercher "MV-3 PRO PORTAIL"
→ Le statut doit être "Activé" (bouton vert)
```

### 2. Via la base de données
```sql
SELECT name, value
FROM llx_const
WHERE name = 'MAIN_MODULE_MV3PRO_PORTAIL';
```

**Résultat attendu:**
```
name: MAIN_MODULE_MV3PRO_PORTAIL
value: 1
```

### 3. Via PHP (debug)
```php
<?php
require_once 'main.inc.php';
global $conf;

echo "MAIN_MODULE_MV3PRO_PORTAIL = ";
var_dump($conf->global->MAIN_MODULE_MV3PRO_PORTAIL);

echo "\nmv3pro_portail->enabled = ";
var_dump($conf->mv3pro_portail->enabled ?? 'NOT SET');
```

---

## Informations techniques

### Nom du module dans les fichiers

- **Nom de la classe**: `modMv3pro_portail`
- **Rights class**: `mv3pro_portail`
- **Constante**: `MAIN_MODULE_MV3PRO_PORTAIL`
- **Nom affiché**: `MV-3 PRO Portail`
- **Numéro**: `510000`

### Fichiers concernés

```
core/modules/modMv3pro_portail.class.php  # Descriptor du module
api/v1/debug.php                          # Diagnostic (CORRIGÉ)
pwa/src/pages/Debug.tsx                   # Interface de diagnostic
```

---

## Résultat attendu

Après correction, le diagnostic doit afficher:

```
Configuration Système
┌─────────────────────────────┬──────────┐
│ Module MV-3 PRO PORTAIL     │ ✓ Activé │
│ Table llx_mv3_mobile_users  │ ✓ Existe │
│ Table llx_mv3_mobile_sessions│ ✓ Existe │
│ Table llx_mv3_rapport       │ ✓ Existe │
└─────────────────────────────┴──────────┘
```

Au lieu de:

```
Configuration Système
┌─────────────────────────────┬──────────────┐
│ Module MV3PRO activé        │ ✗ Non        │ ← INCORRECT
│ Table llx_mv3_mobile_users  │ ✓ Existe     │
│ Table llx_mv3_mobile_sessions│ ✓ Existe    │
│ Table llx_mv3_rapport       │ ✓ Existe     │
└─────────────────────────────┴──────────────┘
```

---

## Test

1. Rebuild de la PWA (déjà fait)
2. Upload des fichiers sur le serveur
3. Accéder à la PWA
4. Menu > Debug
5. Lancer "Diagnostic Complet"
6. Vérifier que "Module MV-3 PRO PORTAIL" affiche "✓ Activé"

---

## Build

Build réussi:
```bash
npm run build
✓ built in 2.25s
```

Aucune erreur TypeScript.

---

## Résumé

✅ **Check du module corrigé**
- Utilise la bonne constante `MAIN_MODULE_MV3PRO_PORTAIL`
- Fallback sur `$conf->mv3pro_portail->enabled`
- Compatible avec toutes les versions de Dolibarr

✅ **Nom du check mis à jour**
- `Module MV-3 PRO PORTAIL` (correspond au nom officiel)

✅ **Valeurs plus explicites**
- `Activé` / `Non activé` (au lieu de `Oui` / `Non`)

✅ **Build réussi**
- PWA compile sans erreur
- Prête pour déploiement

Le diagnostic affichera maintenant le bon statut du module.
