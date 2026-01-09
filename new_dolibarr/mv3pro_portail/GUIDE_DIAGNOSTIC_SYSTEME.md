# Guide du Système de Diagnostic MV3 PRO

## Vue d'ensemble

Le système de diagnostic permet de tester automatiquement tous les endpoints API et toutes les pages PWA de l'application MV3 PRO. Il fournit un rapport détaillé avec :

- Code HTTP de chaque endpoint
- Temps de réponse
- Erreurs détaillées (message, fichier, ligne)
- Erreurs SQL si disponibles
- Preview de la réponse JSON

## Accès au système de diagnostic

### 1. Backend Debug Endpoint

**URL:** `https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php`

**Sécurité:** Accessible uniquement si :
- Vous êtes admin Dolibarr (connecté à l'interface web)
- OU vous fournissez une DEBUG_KEY valide
- OU le mode développement est activé (fichier `/tmp/mv3pro_debug.flag`)

**Pour activer le mode dev:**
```bash
# Sur le serveur
touch /tmp/mv3pro_debug.flag
```

**Pour désactiver le mode dev:**
```bash
rm /tmp/mv3pro_debug.flag
```

### 2. Frontend Debug Interface (PWA)

**URL:** `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug`

**Accès:** Disponible après login dans la PWA

**Fonctionnalités:**
- Diagnostic complet (backend + frontend)
- Test backend API uniquement
- Test frontend API uniquement
- Export du rapport en JSON
- Gestion du token et mode debug console

## Utilisation

### Test complet depuis la PWA

1. Connectez-vous à la PWA
2. Allez sur `/#/debug`
3. Cliquez sur **"Diagnostic Complet"**
4. Attendez les résultats (quelques secondes)
5. Consultez le rapport détaillé
6. Cliquez sur **"Exporter JSON"** pour télécharger le rapport complet

### Test depuis le backend uniquement

Avec curl :

```bash
# Si vous avez un token
curl -H "X-Auth-Token: VOTRE_TOKEN" \
  https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php | jq .

# Si mode dev activé (pas besoin de token)
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php | jq .
```

## Interprétation des résultats

### Statuts

- **OK** (vert) : Endpoint fonctionne correctement
- **WARNING** (orange) : Endpoint fonctionne mais avec avertissements (ex: 404 attendu)
- **ERROR** (rouge) : Endpoint échoue

### Statistiques affichées

1. **Total** : Nombre total d'endpoints testés
2. **OK** : Nombre d'endpoints qui fonctionnent
3. **Warning** : Nombre d'avertissements
4. **Erreurs** : Nombre d'endpoints qui échouent
5. **Temps total** : Temps total d'exécution de tous les tests

### Détails d'un test

Pour chaque endpoint testé, vous obtenez :

- **Name** : Nom du endpoint
- **URL** : URL testée avec paramètres
- **HTTP Code** : Code de réponse (200, 401, 500, etc.)
- **Response Time** : Temps de réponse en millisecondes
- **Status** : OK / WARNING / ERROR
- **Error** : Message d'erreur si échec
- **SQL Error** : Requête SQL échouée si disponible
- **File / Line** : Fichier et ligne de l'erreur PHP
- **Response Preview** : Aperçu de la réponse JSON

## Endpoints testés

Le système teste automatiquement :

### Auth & User
- `me.php` - Informations utilisateur

### Planning
- `planning.php` - Liste des plannings
- `planning_view.php` - Détail d'un planning

### Rapports
- `rapports.php` - Liste des rapports
- `rapports_view.php` - Détail d'un rapport

### Matériel
- `materiel_list.php` - Liste du matériel
- `materiel_view.php` - Détail d'un matériel

### Notifications
- `notifications_list.php` - Liste des notifications
- `notifications_unread_count.php` - Nombre de notifications non lues

### Régie
- `regie_list.php` - Liste des régies
- `regie_view.php` - Détail d'une régie

### Sens de Pose
- `sens_pose_list.php` - Liste des sens de pose
- `sens_pose_view.php` - Détail d'un sens de pose

### Frais
- `frais_list.php` - Liste des frais

## Vérifications système

Le diagnostic vérifie également :

1. **Module MV3PRO activé** : Vérifie que le module est bien activé dans Dolibarr
2. **Tables requises** : Vérifie la présence de toutes les tables nécessaires
   - `llx_mv3_mobile_users`
   - `llx_mv3_mobile_sessions`
   - `llx_mv3_rapport`

## Export du rapport

Le bouton **"Exporter JSON"** télécharge un fichier JSON complet contenant :

```json
{
  "timestamp": "2026-01-09T...",
  "user": {...},
  "backend_report": {
    "system_info": {...},
    "config_checks": [...],
    "stats": {...},
    "test_results": [...]
  },
  "frontend_tests": [...],
  "browser_info": {...},
  "storage_info": {...}
}
```

Ce fichier peut être partagé avec un développeur pour analyse.

## Résolution des problèmes courants

### Erreur 403 "Accès refusé"

**Cause:** Vous n'êtes pas authentifié comme admin et le mode dev n'est pas activé

**Solution:**
```bash
# Activer le mode dev sur le serveur
touch /tmp/mv3pro_debug.flag
```

### Erreur 500 sur un endpoint

**Détails affichés:**
- Message d'erreur exact
- Fichier PHP qui a causé l'erreur
- Numéro de ligne
- Erreur SQL si applicable

**Exemple:**
```json
{
  "name": "Rapports - Liste",
  "status": "ERROR",
  "http_code": 500,
  "error": "Undefined variable: conf",
  "file": "/path/to/rapports.php",
  "line": 42,
  "sql_error": null
}
```

### Pas de token de test disponible

**Cause:** Aucune session mobile active dans la base de données

**Solution:** Connectez-vous d'abord à la PWA pour créer une session, puis relancez le diagnostic.

## Outils supplémentaires

### Mode debug console

Active les logs détaillés dans la console du navigateur (F12)

### Effacer token

Supprime le token de session et force une reconnexion

### Recharger la page

Recharge la page de diagnostic (utile après modifications)

## Sécurité

- Le système de diagnostic ne doit **JAMAIS** être accessible en production sans authentification
- Le fichier `/tmp/mv3pro_debug.flag` doit être supprimé après utilisation
- Les rapports JSON contiennent des informations sensibles (tokens, emails, etc.)

## Pour les développeurs

### Ajouter un nouveau test

Éditez `/api/v1/debug.php` et ajoutez dans `$endpoints_to_test` :

```php
[
    'name' => 'Mon Endpoint',
    'url' => 'mon_endpoint.php',
    'method' => 'GET',
    'params' => ['param1' => 'value1'],
    'needs_auth' => true,
    'expected_404' => false, // true si 404 est normal
],
```

### Personnaliser les tests frontend

Éditez `/pwa/src/pages/Debug.tsx` dans la fonction `testFrontendAPI()`.

## Support

En cas de problème avec le système de diagnostic, vérifiez :

1. Les permissions d'accès (admin ou mode dev)
2. La présence d'un token valide
3. Les logs serveur PHP
4. La console navigateur (F12)
