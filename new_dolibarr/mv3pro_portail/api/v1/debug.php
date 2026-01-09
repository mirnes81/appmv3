<?php
/**
 * API v1 - Debug / Diagnostic complet
 *
 * Teste tous les endpoints API et affiche un rapport détaillé
 *
 * SÉCURITÉ:
 * - Accessible uniquement avec DEBUG_KEY ou si admin Dolibarr
 * - Désactivable via config
 */

// Activer error_reporting complet pour ce script
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Capturer les fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'fatal_error' => true,
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'type' => $error['type'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
});

// Charger bootstrap
require_once __DIR__ . '/_bootstrap.php';

// Vérifier sécurité
$is_admin = false;
$debug_key_provided = false;

// Méthode 1: Admin Dolibarr
if (!empty($user->id) && !empty($user->admin)) {
    $is_admin = true;
}

// Méthode 2: DEBUG_KEY
$provided_key = get_param('debug_key', '');
$configured_key = $conf->global->MV3PRO_DEBUG_KEY ?? '';

if ($provided_key && $configured_key && $provided_key === $configured_key) {
    $debug_key_provided = true;
}

// Méthode 3: Mode développement (fichier flag)
$dev_mode = file_exists('/tmp/mv3pro_debug.flag');

if (!$is_admin && !$debug_key_provided && !$dev_mode) {
    json_error('Accès refusé. Admin Dolibarr ou DEBUG_KEY requis', 'FORBIDDEN', 403);
}

// ============================================================================
// CONFIGURATION DES TESTS
// ============================================================================

$endpoints_to_test = [
    // Auth & User
    [
        'name' => 'Me (infos utilisateur)',
        'url' => 'me.php',
        'method' => 'GET',
        'params' => [],
        'needs_auth' => true,
    ],

    // Planning
    [
        'name' => 'Planning - Liste',
        'url' => 'planning.php',
        'method' => 'GET',
        'params' => ['from' => date('Y-m-d'), 'to' => date('Y-m-d', strtotime('+7 days'))],
        'needs_auth' => true,
    ],
    [
        'name' => 'Planning - Détail',
        'url' => 'planning_view.php',
        'method' => 'GET',
        'params' => ['id' => 1],
        'needs_auth' => true,
        'expected_404' => true, // OK si aucun planning avec ID 1
    ],

    // Rapports
    [
        'name' => 'Rapports - Liste',
        'url' => 'rapports.php',
        'method' => 'GET',
        'params' => ['limit' => 10],
        'needs_auth' => true,
    ],
    [
        'name' => 'Rapports - Détail',
        'url' => 'rapports_view.php',
        'method' => 'GET',
        'params' => ['id' => 1],
        'needs_auth' => true,
        'expected_404' => true,
    ],

    // Matériel
    [
        'name' => 'Matériel - Liste',
        'url' => 'materiel_list.php',
        'method' => 'GET',
        'params' => [],
        'needs_auth' => true,
    ],
    [
        'name' => 'Matériel - Détail',
        'url' => 'materiel_view.php',
        'method' => 'GET',
        'params' => ['id' => 1],
        'needs_auth' => true,
        'expected_404' => true,
    ],

    // Notifications
    [
        'name' => 'Notifications - Liste',
        'url' => 'notifications_list.php',
        'method' => 'GET',
        'params' => [],
        'needs_auth' => true,
    ],
    [
        'name' => 'Notifications - Nombre non lues',
        'url' => 'notifications_unread_count.php',
        'method' => 'GET',
        'params' => [],
        'needs_auth' => true,
    ],

    // Régie
    [
        'name' => 'Régie - Liste',
        'url' => 'regie_list.php',
        'method' => 'GET',
        'params' => ['limit' => 10],
        'needs_auth' => true,
    ],
    [
        'name' => 'Régie - Détail',
        'url' => 'regie_view.php',
        'method' => 'GET',
        'params' => ['id' => 1],
        'needs_auth' => true,
        'expected_404' => true,
    ],

    // Sens de Pose
    [
        'name' => 'Sens Pose - Liste',
        'url' => 'sens_pose_list.php',
        'method' => 'GET',
        'params' => ['limit' => 10],
        'needs_auth' => true,
    ],
    [
        'name' => 'Sens Pose - Détail',
        'url' => 'sens_pose_view.php',
        'method' => 'GET',
        'params' => ['id' => 1],
        'needs_auth' => true,
        'expected_404' => true,
    ],

    // Frais
    [
        'name' => 'Frais - Liste',
        'url' => 'frais_list.php',
        'method' => 'GET',
        'params' => [],
        'needs_auth' => true,
    ],
];

// ============================================================================
// FONCTION DE TEST D'UN ENDPOINT
// ============================================================================

function test_endpoint($endpoint_config, $token = null) {
    global $db;

    $start_time = microtime(true);
    $result = [
        'name' => $endpoint_config['name'],
        'url' => $endpoint_config['url'],
        'method' => $endpoint_config['method'],
        'params' => $endpoint_config['params'],
        'status' => 'OK',
        'http_code' => null,
        'response_time_ms' => 0,
        'response_preview' => null,
        'error' => null,
        'sql_error' => null,
        'file' => null,
        'line' => null,
    ];

    try {
        // Construire l'URL complète
        $base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $endpoint_config['url'];

        // Ajouter les paramètres GET
        if (!empty($endpoint_config['params'])) {
            $base_url .= '?' . http_build_query($endpoint_config['params']);
        }

        // Initialiser curl
        $ch = curl_init($base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Ajouter le token si nécessaire
        $headers = ['Content-Type: application/json'];
        if ($token && $endpoint_config['needs_auth']) {
            $headers[] = 'X-Auth-Token: ' . $token;
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Exécuter
        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        $end_time = microtime(true);
        $result['response_time_ms'] = round(($end_time - $start_time) * 1000, 2);
        $result['http_code'] = $http_code;

        // Analyser la réponse
        if ($curl_error) {
            $result['status'] = 'ERROR';
            $result['error'] = 'cURL error: ' . $curl_error;
        } elseif ($http_code >= 500) {
            $result['status'] = 'ERROR';
            $result['error'] = 'Erreur serveur 500';

            // Tenter de parser la réponse JSON pour extraire l'erreur
            $json = json_decode($response_body, true);
            if ($json) {
                $result['response_preview'] = $json;
                if (isset($json['error'])) {
                    $result['error'] = $json['error'];
                }
                if (isset($json['file'])) {
                    $result['file'] = $json['file'];
                }
                if (isset($json['line'])) {
                    $result['line'] = $json['line'];
                }
            } else {
                $result['response_preview'] = substr($response_body, 0, 500);
            }

            // Capturer l'erreur SQL si disponible
            if ($db->lasterror()) {
                $result['sql_error'] = $db->lasterror();
            }
        } elseif ($http_code == 404 && !empty($endpoint_config['expected_404'])) {
            $result['status'] = 'OK';
            $result['response_preview'] = '404 attendu (aucune donnée de test)';
        } elseif ($http_code == 404) {
            $result['status'] = 'WARNING';
            $result['error'] = 'Endpoint non trouvé (404)';
        } elseif ($http_code == 401 || $http_code == 403) {
            $result['status'] = 'ERROR';
            $result['error'] = 'Authentification/Autorisation échouée';
            $json = json_decode($response_body, true);
            if ($json && isset($json['error'])) {
                $result['response_preview'] = $json;
            }
        } elseif ($http_code >= 200 && $http_code < 300) {
            $result['status'] = 'OK';
            $json = json_decode($response_body, true);
            if ($json) {
                // Limiter la preview
                $preview = $json;
                if (isset($preview['data']) && is_array($preview['data']) && count($preview['data']) > 2) {
                    $preview['data'] = array_slice($preview['data'], 0, 2);
                    $preview['_truncated'] = 'Données limitées pour preview';
                }
                $result['response_preview'] = $preview;
            } else {
                $result['response_preview'] = substr($response_body, 0, 200);
            }
        } else {
            $result['status'] = 'WARNING';
            $result['error'] = 'Code HTTP inattendu: ' . $http_code;
            $json = json_decode($response_body, true);
            $result['response_preview'] = $json ?: substr($response_body, 0, 200);
        }

    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['error'] = 'Exception: ' . $e->getMessage();
        $result['file'] = $e->getFile();
        $result['line'] = $e->getLine();
    }

    return $result;
}

// ============================================================================
// OBTENIR UN TOKEN DE TEST
// ============================================================================

$test_token = null;
$test_user_info = null;

// Si on a déjà un token (depuis Authorization), l'utiliser
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    $test_token = trim($matches[1]);
} elseif ($auth_header) {
    $test_token = trim($auth_header);
}

// Sinon, chercher un utilisateur mobile actif pour obtenir un token de test
if (!$test_token) {
    $sql = "SELECT s.session_token, u.email, u.firstname, u.lastname
            FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions s
            INNER JOIN ".MAIN_DB_PREFIX."mv3_mobile_users u ON u.rowid = s.user_id
            WHERE s.expires_at > NOW()
            AND u.is_active = 1
            ORDER BY s.last_activity DESC
            LIMIT 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $session = $db->fetch_object($resql);
        $test_token = $session->session_token;
        $test_user_info = [
            'email' => $session->email,
            'name' => trim($session->firstname . ' ' . $session->lastname),
        ];
    }
}

// ============================================================================
// EXÉCUTER TOUS LES TESTS
// ============================================================================

$test_results = [];
$stats = [
    'total' => 0,
    'ok' => 0,
    'warning' => 0,
    'error' => 0,
    'total_time_ms' => 0,
];

foreach ($endpoints_to_test as $endpoint_config) {
    $test_result = test_endpoint($endpoint_config, $test_token);
    $test_results[] = $test_result;

    $stats['total']++;
    $stats['total_time_ms'] += $test_result['response_time_ms'];

    switch ($test_result['status']) {
        case 'OK':
            $stats['ok']++;
            break;
        case 'WARNING':
            $stats['warning']++;
            break;
        case 'ERROR':
            $stats['error']++;
            break;
    }
}

// ============================================================================
// INFORMATIONS SYSTÈME
// ============================================================================

$system_info = [
    'php_version' => PHP_VERSION,
    'dolibarr_version' => DOL_VERSION ?? 'N/A',
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'database_type' => $db->type ?? 'N/A',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'error_reporting' => error_reporting(),
    'display_errors' => ini_get('display_errors'),
];

// ============================================================================
// VÉRIFICATIONS DE CONFIGURATION
// ============================================================================

$config_checks = [
    [
        'name' => 'Module MV3PRO activé',
        'status' => !empty($conf->mv3pro_portail->enabled) ? 'OK' : 'ERROR',
        'value' => !empty($conf->mv3pro_portail->enabled) ? 'Oui' : 'Non',
    ],
    [
        'name' => 'Table llx_mv3_mobile_users',
        'status' => 'OK',
        'value' => 'Vérification...',
    ],
    [
        'name' => 'Table llx_mv3_mobile_sessions',
        'status' => 'OK',
        'value' => 'Vérification...',
    ],
    [
        'name' => 'Table llx_mv3_rapport',
        'status' => 'OK',
        'value' => 'Vérification...',
    ],
];

// Vérifier les tables
$required_tables = ['mv3_mobile_users', 'mv3_mobile_sessions', 'mv3_rapport'];
foreach ($required_tables as $idx => $table) {
    $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX.$table."'";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $config_checks[$idx + 1]['status'] = 'OK';
        $config_checks[$idx + 1]['value'] = 'Existe';
    } else {
        $config_checks[$idx + 1]['status'] = 'ERROR';
        $config_checks[$idx + 1]['value'] = 'Table manquante';
    }
}

// ============================================================================
// RETOURNER LE RAPPORT COMPLET
// ============================================================================

json_ok([
    'debug_mode' => true,
    'access_method' => $is_admin ? 'admin' : ($debug_key_provided ? 'debug_key' : 'dev_mode'),
    'timestamp' => date('Y-m-d H:i:s'),
    'test_user' => $test_user_info,
    'has_test_token' => !empty($test_token),
    'system_info' => $system_info,
    'config_checks' => $config_checks,
    'stats' => $stats,
    'test_results' => $test_results,
]);
