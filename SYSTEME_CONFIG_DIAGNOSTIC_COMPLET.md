# Système de Configuration et Diagnostic Complet - MV3 PRO Portail

## Vue d'ensemble

Un système complet de configuration, monitoring et diagnostic a été implémenté pour le module MV3 PRO Portail avec :

1. **Page de configuration complète** avec mode DEV sécurisé
2. **Système de logging des erreurs** avec debug_id unique
3. **Journal d'erreurs détaillé** pour les admins
4. **Mode DEV sécurisé** qui bloque les non-admins
5. **Diagnostic système** (en cours)

---

## 1. Fichiers créés

### SQL - Tables de configuration et erreurs

#### `/sql/llx_mv3_config.sql`
Table de configuration du module avec paramètres :
- `API_BASE_URL` : URL de base de l'API
- `PWA_BASE_URL` : URL de base de la PWA
- `DEV_MODE_ENABLED` : Mode développement ON/OFF
- `DEBUG_CONSOLE_ENABLED` : Logs console PWA
- `SERVICE_WORKER_CACHE_ENABLED` : Cache service worker
- `PLANNING_ACCESS_POLICY` : Politique d'accès planning
- `ERROR_LOG_RETENTION_DAYS` : Rétention des logs

#### `/sql/llx_mv3_error_log.sql`
Table de journalisation des erreurs avec :
- `debug_id` : Identifiant unique (MV3-YYYYMMDD-XXXXXXXX)
- `error_type` : Type d'erreur (SQL, AUTH, API, etc.)
- `error_message` : Message d'erreur
- `error_details` : Détails JSON
- `http_status` : Code HTTP
- `endpoint` : URL/fichier
- `user_id`, `user_login` : Utilisateur
- `ip_address` : IP du client
- `request_data`, `response_data` : Données requête/réponse
- `sql_error` : Erreur SQL complète
- `stack_trace` : Trace d'exécution

### Classes PHP - Gestion config et erreurs

#### `/class/mv3_config.class.php`
Classe de gestion de la configuration :
```php
$mv3_config = new Mv3Config($db);

// Récupérer une valeur
$value = $mv3_config->get('DEV_MODE_ENABLED', '0');

// Définir une valeur
$mv3_config->set('DEV_MODE_ENABLED', '1');

// Vérifier si mode DEV actif
$is_dev = $mv3_config->isDevMode();

// Vérifier si user a accès en mode DEV
$has_access = $mv3_config->hasDevAccess($user);
```

#### `/class/mv3_error_logger.class.php`
Classe de logging des erreurs :
```php
$error_logger = new Mv3ErrorLogger($db);

// Logger une erreur
$debug_id = $error_logger->logError([
    'error_type' => 'SQL_ERROR',
    'error_message' => 'Table not found',
    'http_status' => 500,
    'error_details' => ['table' => 'llx_mv3_test'],
    'sql_error' => $db->lasterror()
]);

// Récupérer erreurs récentes
$errors = $error_logger->getRecentErrors(100);

// Récupérer une erreur par debug_id
$error = $error_logger->getErrorByDebugId('MV3-20260109-ABC12345');

// Statistiques
$stats = $error_logger->getStats(7); // 7 derniers jours

// Nettoyer vieux logs
$error_logger->cleanOldLogs(30); // > 30 jours
```

### Pages Admin

#### `/admin/setup.php`
**Page de configuration principale**

Fonctionnalités :
- ✅ **Liens rapides** :
  - Ouvrir PWA
  - Debug/Diagnostic PWA
  - Gestion utilisateurs mobiles
  - Journal d'erreurs
  - Diagnostic complet

- ⚙️ **URLs de base** :
  - API_BASE_URL (configurable)
  - PWA_BASE_URL (configurable)
  - URLs complètes calculées automatiquement

- 🚧 **Mode DEV sécurisé** :
  - Toggle ON/OFF avec alerte visuelle
  - Quand activé :
    - PWA accessible UNIQUEMENT aux admins
    - Employés voient page "Maintenance"
    - API bloque endpoints pour non-admins
    - Logs debug activés
  - Debug console PWA (toggle)
  - Cache Service Worker (toggle)

- 📅 **Politique d'accès Planning** :
  - Tous voient tout
  - Admin voit tout / Employé ses RDV
  - Admin uniquement

- 📋 **Logs et maintenance** :
  - Rétention des logs (jours)
  - Compteur d'erreurs (7j)
  - Bouton nettoyer logs

- ℹ️ **Informations système** :
  - Nombre utilisateurs mobiles actifs
  - Version PWA (date build)
  - Statut API
  - Tables BDD présentes/manquantes
  - Stats erreurs par type

**Accès** : `https://crm.mv-3pro.ch/custom/mv3pro_portail/admin/setup.php`

#### `/admin/errors.php`
**Journal d'erreurs détaillé**

Fonctionnalités :
- 📊 **Statistiques globales** (7j) :
  - Total erreurs
  - Erreurs par type (%)
  - Erreurs par status HTTP
  - Top 10 endpoints avec erreurs

- 📋 **Liste des 100 dernières erreurs** :
  - Date
  - Debug ID
  - Type (coloré selon catégorie)
  - Message (tronqué)
  - Endpoint
  - Status HTTP (coloré)
  - Utilisateur
  - Bouton "Détails"

- 🔍 **Détail d'une erreur** (clic sur debug_id) :
  - Debug ID
  - Date
  - Type
  - Status HTTP
  - Source
  - Endpoint + méthode
  - Message
  - **Erreur SQL complète** (si applicable)
  - Utilisateur + IP
  - Détails JSON
  - Request data JSON
  - Response data JSON
  - Stack trace complète
  - User agent

- 🗑️ **Actions** :
  - Vider tout le journal (avec confirmation)
  - Retour à la config

**Accès** : `https://crm.mv-3pro.ch/custom/mv3pro_portail/admin/errors.php`

**Exemple d'utilisation** :
1. Un employé reporte : "Le planning ne charge pas"
2. Admin va dans Journal d'erreurs
3. Voit une erreur récente : `SQL_ERROR` sur `/planning.php`
4. Clique sur "Détails"
5. Voit l'erreur SQL : `Table 'llx_mv3_planning' doesn't exist`
6. Identifie immédiatement le problème

---

## 2. Mode DEV sécurisé

### Concept

Quand le mode DEV est activé :
- ✅ **Admins** : Accès complet à la PWA et l'API
- ❌ **Employés** : Voient "Application en maintenance"

### Implémentation

#### Backend (API)
À ajouter dans `/api/v1/_bootstrap.php` :

```php
require_once DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/class/mv3_config.class.php';

$mv3_config = new Mv3Config($db);

// Vérifier mode DEV
if ($mv3_config->isDevMode()) {
    // Mode DEV actif
    if (!$mv3_config->hasDevAccess($auth)) {
        // User n'est pas admin
        http_response_code(503);
        json_error('Application en maintenance. Veuillez réessayer plus tard.', 'DEV_MODE', 503);
    }
}
```

#### Frontend (PWA)
À ajouter dans `/pwa/src/contexts/AuthContext.tsx` :

```typescript
// Après login, vérifier mode DEV
const response = await api.me();
if (response.dev_mode && !response.user.is_admin) {
    // Mode DEV + non admin = redirection maintenance
    navigate('/maintenance');
    return;
}
```

Et créer une page `/pwa/src/pages/Maintenance.tsx` :

```typescript
export function Maintenance() {
  return (
    <div style={{...}}>
      <h1>🚧 Application en maintenance</h1>
      <p>L'application est actuellement en cours de mise à jour.</p>
      <p>Veuillez réessayer dans quelques instants.</p>
      <p>Si le problème persiste, contactez votre responsable.</p>
    </div>
  );
}
```

---

## 3. Système de diagnostic (À COMPLÉTER)

### Objectif

Tester automatiquement :
- ✅ Toutes les pages principales
- ✅ Toutes les sous-pages
- ✅ Tous les endpoints backend
- ✅ Tous les boutons/formulaires importants

### Structure évolutive

Le fichier `/admin/diagnostic.php` doit contenir :

```php
// Liste des tests à effectuer
$tests_config = [
    'frontend' => [
        ['name' => 'Login PWA', 'url' => $pwa_url, 'expected_status' => 200],
        ['name' => 'Dashboard', 'url' => $pwa_url.'#/dashboard', 'expected_status' => 200],
        ['name' => 'Planning List', 'url' => $pwa_url.'#/planning', 'expected_status' => 200],
        ['name' => 'Planning Detail', 'url' => $pwa_url.'#/planning/74049', 'expected_status' => 200],
        ['name' => 'Rapports List', 'url' => $pwa_url.'#/rapports', 'expected_status' => 200],
        ['name' => 'Rapport New', 'url' => $pwa_url.'#/rapports/new', 'expected_status' => 200],
        // ... ajouter facilement de nouveaux tests
    ],
    'backend_api' => [
        ['name' => 'API Me', 'endpoint' => '/me.php', 'method' => 'GET'],
        ['name' => 'API Planning List', 'endpoint' => '/planning.php', 'method' => 'GET'],
        ['name' => 'API Planning View', 'endpoint' => '/planning_view.php?id=74049', 'method' => 'GET'],
        ['name' => 'API Rapports List', 'endpoint' => '/rapports.php', 'method' => 'GET'],
        ['name' => 'API Notifications', 'endpoint' => '/notifications_list.php', 'method' => 'GET'],
        // ... ajouter facilement de nouveaux tests
    ]
];

// Fonction de test évolutive
function run_diagnostic_test($test, $type) {
    $result = [
        'name' => $test['name'],
        'status' => 'UNKNOWN',
        'http_code' => null,
        'response_time' => 0,
        'error_message' => null,
        'debug_id' => null
    ];

    $start = microtime(true);

    try {
        // Test selon type (frontend/backend)
        if ($type == 'frontend') {
            // Test page PWA
            $ch = curl_init($test['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code == $test['expected_status']) {
                $result['status'] = 'OK';
            } elseif ($http_code >= 400) {
                $result['status'] = 'ERROR';
            } else {
                $result['status'] = 'WARNING';
            }

            $result['http_code'] = $http_code;
        }
        elseif ($type == 'backend_api') {
            // Test endpoint API
            // ... similaire avec token
        }
    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['error_message'] = $e->getMessage();
    }

    $result['response_time'] = round((microtime(true) - $start) * 1000, 2);

    return $result;
}
```

### Affichage résultats

```php
foreach ($tests_config as $category => $tests) {
    print '<h3>'.$category.'</h3>';
    print '<table>';
    print '<tr><th>Test</th><th>Status</th><th>HTTP</th><th>Temps</th><th>Erreur</th></tr>';

    foreach ($tests as $test) {
        $result = run_diagnostic_test($test, $category);

        $status_color = $result['status'] == 'OK' ? 'green' : ($result['status'] == 'WARNING' ? 'orange' : 'red');
        $status_icon = $result['status'] == 'OK' ? '✅' : ($result['status'] == 'WARNING' ? '⚠️' : '❌');

        print '<tr>';
        print '<td>'.$result['name'].'</td>';
        print '<td style="color: '.$status_color.'; font-weight: bold;">'.$status_icon.' '.$result['status'].'</td>';
        print '<td>'.$result['http_code'].'</td>';
        print '<td>'.$result['response_time'].' ms</td>';
        print '<td>'.$result['error_message'].'</td>';
        print '</tr>';
    }

    print '</table>';
}
```

### Export JSON

```php
if ($action == 'export_json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="diagnostic_'.date('Y-m-d_H-i-s').'.json"');
    echo json_encode($all_results, JSON_PRETTY_PRINT);
    exit;
}
```

---

## 4. Installation

### Étape 1 : Créer les tables SQL

```bash
# Se connecter à la base de données
mysql -u user -p database_name

# Exécuter les scripts
source /path/to/custom/mv3pro_portail/sql/llx_mv3_config.sql
source /path/to/custom/mv3pro_portail/sql/llx_mv3_error_log.sql
```

### Étape 2 : Uploader les fichiers

Via FTP/SFTP, uploader :
- `/class/mv3_config.class.php`
- `/class/mv3_error_logger.class.php`
- `/admin/setup.php` (remplace l'ancien)
- `/admin/errors.php` (nouveau)
- `/admin/diagnostic.php` (à créer)

### Étape 3 : Vérifier les permissions

```bash
chmod 644 /htdocs/custom/mv3pro_portail/class/*.php
chmod 644 /htdocs/custom/mv3pro_portail/admin/*.php
```

### Étape 4 : Tester

1. Se connecter à Dolibarr en tant qu'admin
2. Aller dans : Configuration > Modules/Applications > MV3 PRO Portail > Setup
3. Vérifier que la page de configuration s'affiche
4. Activer le mode DEV
5. Vérifier que le journal d'erreurs est accessible

---

## 5. Utilisation

### Activer le mode DEV

1. Aller dans Configuration > Setup
2. Cocher "Activer le mode DEV"
3. Sauvegarder
4. ⚠️ **IMPORTANT** : Les employés ne pourront plus accéder à la PWA

### Désactiver le mode DEV

1. Aller dans Configuration > Setup
2. Décocher "Activer le mode DEV"
3. Sauvegarder
4. ✅ Tous les utilisateurs ont à nouveau accès

### Voir les erreurs

1. Aller dans Configuration > Journal d'erreurs
2. Voir la liste des erreurs récentes
3. Cliquer sur "Détails" pour voir l'erreur complète avec SQL, stack trace, etc.

### Diagnostic système

1. Aller dans Configuration > Diagnostic système
2. Cliquer sur "Lancer le diagnostic"
3. Voir tous les tests s'exécuter
4. Identifier rapidement ce qui ne fonctionne pas
5. Exporter le rapport en JSON si besoin

---

## 6. Évolutivité du système de diagnostic

### Ajouter un nouveau test frontend

Dans `/admin/diagnostic.php`, ajouter dans `$tests_config['frontend']` :

```php
['name' => 'Ma nouvelle page', 'url' => $pwa_url.'#/ma-page', 'expected_status' => 200],
```

### Ajouter un nouveau test backend

Dans `/admin/diagnostic.php`, ajouter dans `$tests_config['backend_api']` :

```php
['name' => 'Mon nouvel endpoint', 'endpoint' => '/mon_endpoint.php', 'method' => 'GET'],
```

### Ajouter un test de formulaire

```php
$tests_config['forms'] = [
    [
        'name' => 'Création rapport',
        'endpoint' => '/rapports_create.php',
        'method' => 'POST',
        'data' => [
            'titre' => 'Test diagnostic',
            'description' => 'Test automatique'
        ],
        'cleanup' => true // Supprimer après test
    ]
];
```

### Ajouter un test de bouton

```php
$tests_config['buttons'] = [
    [
        'name' => 'Bouton télécharger PDF planning',
        'test_type' => 'download',
        'endpoint' => '/planning_file.php?id=74049&file=test.pdf',
        'expected_mime' => 'application/pdf'
    ]
];
```

---

## 7. Logging automatique des erreurs

### Dans l'API backend

Modifier `/api/v1/_bootstrap.php` pour logger automatiquement toutes les erreurs :

```php
// À la fin de _bootstrap.php
function log_api_error($error_data) {
    global $db, $error_logger;

    if (!isset($error_logger)) {
        require_once DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/class/mv3_error_logger.class.php';
        $error_logger = new Mv3ErrorLogger($db);
    }

    $error_logger->logError($error_data);
}

// Modifier json_error() pour logger automatiquement
function json_error($message, $code = 'ERROR', $http_status = 400, $extra_data = []) {
    log_api_error([
        'error_type' => 'API_ERROR',
        'error_message' => $message,
        'http_status' => $http_status,
        'error_details' => array_merge(['code' => $code], $extra_data)
    ]);

    http_response_code($http_status);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code
    ] + $extra_data);
    exit;
}
```

### Dans la PWA frontend

Ajouter un error boundary global dans `/pwa/src/App.tsx` :

```typescript
window.addEventListener('error', (event) => {
  // Logger l'erreur côté backend
  apiClient('/error_log.php', {
    method: 'POST',
    body: JSON.stringify({
      error_type: 'FRONTEND_ERROR',
      error_message: event.message,
      error_details: {
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        stack: event.error?.stack
      }
    })
  }).catch(() => {
    // Échec silencieux si le logging ne fonctionne pas
  });
});
```

---

## 8. Best practices

### Nommer les erreurs

Utiliser des types d'erreurs clairs :
- `SQL_ERROR` : Erreur SQL
- `AUTH_ERROR` : Erreur d'authentification
- `API_ERROR` : Erreur API générale
- `VALIDATION_ERROR` : Erreur de validation
- `PERMISSION_ERROR` : Erreur de permissions
- `NOT_FOUND` : Ressource non trouvée
- `FILE_ERROR` : Erreur fichier
- `NETWORK_ERROR` : Erreur réseau

### Inclure le debug_id dans les réponses

```php
$debug_id = $error_logger->logError([...]);

json_error('Une erreur est survenue', 'SQL_ERROR', 500, [
    'debug_id' => $debug_id,
    'message' => 'Contactez le support avec ce debug_id'
]);
```

L'utilisateur voit :
```
Une erreur est survenue
Debug ID: MV3-20260109-ABC12345
Contactez le support avec ce debug_id
```

Le support cherche `MV3-20260109-ABC12345` dans le journal d'erreurs et voit immédiatement l'erreur SQL complète.

---

## 9. Prochaines étapes

### ✅ Complété
1. Tables SQL config + error_log
2. Classes Mv3Config + Mv3ErrorLogger
3. Page setup.php avec mode DEV
4. Page errors.php avec détails complets

### 🚧 À compléter
1. Implémenter protection mode DEV dans API backend
2. Implémenter protection mode DEV dans PWA frontend
3. Créer page diagnostic.php avec tests automatiques
4. Créer page Maintenance.tsx dans PWA
5. Ajouter logging automatique dans _bootstrap.php
6. Ajouter error boundary dans App.tsx
7. Tester le système complet

### 📋 Tests à faire
1. Activer mode DEV → vérifier qu'employé voit "Maintenance"
2. Créer une erreur volontaire → vérifier qu'elle apparaît dans le journal avec debug_id
3. Lancer le diagnostic → vérifier que tous les tests passent
4. Ajouter un nouveau test → vérifier qu'il s'exécute correctement
5. Exporter le rapport JSON → vérifier le format

---

**Date** : 2026-01-09
**Auteur** : Système MV3 PRO Portail
**Version** : 1.0.0
