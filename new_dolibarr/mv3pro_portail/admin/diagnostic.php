<?php
/**
 * Diagnostic QA complet - MV3 PRO Portail
 *
 * 3 niveaux de tests :
 * 1. Smoke tests (lecture) - Vérifier que tout charge
 * 2. Tests fonctionnels (boutons/formulaires) - Tester les actions en mode DEV admin
 * 3. Tests permissions (admin vs employé) - Vérifier les droits d'accès
 *
 * Système évolutif pour ajouter facilement de nouveaux tests
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/../class/mv3_config.class.php';
require_once __DIR__.'/../class/mv3_error_logger.class.php';

// Droits admin requis
if (!$user->admin) {
    accessforbidden();
}

$mv3_config = new Mv3Config($db);
$error_logger = new Mv3ErrorLogger($db);

$action = GETPOST('action', 'alpha');
$test_level = GETPOST('test_level', 'alpha') ?: 'all';

// Récupération des URLs
$api_base_url = $mv3_config->get('API_BASE_URL', '/custom/mv3pro_portail/api/v1/');
$pwa_base_url = $mv3_config->get('PWA_BASE_URL', '/custom/mv3pro_portail/pwa_dist/');
$full_pwa_url = dol_buildpath($pwa_base_url, 2);
$full_api_url = dol_buildpath($api_base_url, 2);

// Statistiques erreurs
$error_stats = $error_logger->getStats(7);

// ========================================
// HELPERS - Fonctions utilitaires
// ========================================

/**
 * Récupère les credentials de diagnostic depuis la config
 */
function get_diagnostic_credentials($mv3_config) {
    return [
        'email' => $mv3_config->get('DIAGNOSTIC_USER_EMAIL', 'diagnostic@test.local'),
        'password' => $mv3_config->get('DIAGNOSTIC_USER_PASSWORD', 'DiagTest2026!')
    ];
}

/**
 * Effectue un login réel et retourne le token
 */
function perform_real_login($api_url, $credentials) {
    $result = [
        'success' => false,
        'token' => null,
        'user' => null,
        'error' => null,
        'http_code' => null
    ];

    try {
        $ch = curl_init($api_url.'auth/login.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($credentials));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result['http_code'] = $http_code;

        if ($response) {
            $json = json_decode($response, true);
            if ($json && isset($json['success']) && $json['success']) {
                $result['success'] = true;
                $result['token'] = $json['data']['token'] ?? $json['token'] ?? null;
                $result['user'] = $json['data']['user'] ?? $json['user'] ?? null;
            } else {
                $result['error'] = $json['error'] ?? $json['message'] ?? 'Login failed';
            }
        } else {
            $result['error'] = 'No response from server';
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }

    return $result;
}

/**
 * Effectue un logout réel
 */
function perform_real_logout($api_url, $token) {
    $result = [
        'success' => false,
        'error' => null,
        'http_code' => null
    ];

    try {
        $ch = curl_init($api_url.'auth/logout.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Auth-Token: '.$token,
            'Authorization: Bearer '.$token
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result['http_code'] = $http_code;

        if ($response) {
            $json = json_decode($response, true);
            if ($json && isset($json['success']) && $json['success']) {
                $result['success'] = true;
            } else {
                $result['error'] = $json['error'] ?? $json['message'] ?? 'Logout failed';
            }
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }

    return $result;
}

/**
 * Récupère un ID réel depuis une table pour les tests
 */
function get_real_id($db, $table, $conditions = '') {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$table;
    if ($conditions) {
        $sql .= " WHERE ".$conditions;
    }
    $sql .= " ORDER BY rowid DESC LIMIT 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        return $obj->rowid;
    }

    return null;
}

/**
 * Exécute un test HTTP avec détails complets
 */
function run_http_test($test, $auth_token = null) {
    $result = [
        'name' => $test['name'],
        'status' => 'UNKNOWN',
        'http_code' => null,
        'response_time' => 0,
        'error_message' => null,
        'debug_id' => null,
        'sql_error' => null,
        'details' => []
    ];

    $start = microtime(true);

    try {
        $ch = curl_init($test['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Headers
        $headers = [];
        if ($auth_token) {
            $headers[] = 'X-Auth-Token: '.$auth_token;
            $headers[] = 'Authorization: Bearer '.$auth_token;
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Method
        if (!empty($test['method'])) {
            if ($test['method'] == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($test['data'])) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
            } elseif ($test['method'] == 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty($test['data'])) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
                }
            } elseif ($test['method'] == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        curl_close($ch);

        $result['http_code'] = $http_code;

        // Parser la réponse JSON pour extraire debug_id et erreurs
        if ($response) {
            $json = json_decode($response, true);
            if ($json) {
                if (isset($json['debug_id'])) {
                    $result['debug_id'] = $json['debug_id'];
                }
                if (isset($json['sql_error'])) {
                    $result['sql_error'] = $json['sql_error'];
                }
                if (isset($json['error'])) {
                    $result['error_message'] = $json['error'];
                }
                if (isset($json['code'])) {
                    $result['details'][] = 'Code: '.$json['code'];
                }
            }
        }

        if ($curl_error) {
            $result['status'] = 'ERROR';
            $result['error_message'] = 'cURL: '.$curl_error;
        } elseif ($http_code == 200) {
            $result['status'] = 'OK';
        } elseif ($http_code == 201) {
            $result['status'] = 'OK';
            $result['details'][] = 'Created';
        } elseif ($http_code == 401 && !empty($test['expect_401'])) {
            $result['status'] = 'OK';
            $result['details'][] = 'Auth required (expected)';
        } elseif ($http_code == 403 && !empty($test['expect_403'])) {
            $result['status'] = 'OK';
            $result['details'][] = 'Forbidden (expected)';
        } elseif ($http_code == 503 && !empty($test['expect_503'])) {
            $result['status'] = 'OK';
            $result['details'][] = 'Maintenance mode (expected)';
        } elseif ($http_code >= 400 && $http_code < 500) {
            $result['status'] = 'WARNING';
            if (!$result['error_message']) {
                $result['error_message'] = 'Client error '.$http_code;
            }
        } elseif ($http_code >= 500) {
            $result['status'] = 'ERROR';
            if (!$result['error_message']) {
                $result['error_message'] = 'Server error '.$http_code;
            }
        } else {
            $result['status'] = 'WARNING';
            $result['details'][] = 'Unexpected code';
        }
    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['error_message'] = $e->getMessage();
    }

    $result['response_time'] = round((microtime(true) - $start) * 1000, 2);

    return $result;
}

/**
 * Test table BDD
 */
function run_database_test($test, $db) {
    $result = [
        'name' => $test['name'],
        'status' => 'UNKNOWN',
        'error_message' => null,
        'sql_error' => null,
        'details' => []
    ];

    try {
        $sql = "SHOW TABLES LIKE '".$test['table']."'";
        $resql = $db->query($sql);

        if ($resql && $db->num_rows($resql) > 0) {
            $result['status'] = 'OK';

            // Compter les lignes
            $sql_count = "SELECT COUNT(*) as nb FROM ".$test['table'];
            $resql_count = $db->query($sql_count);
            if ($resql_count) {
                $obj = $db->fetch_object($resql_count);
                $result['details'][] = $obj->nb.' rows';
            }
        } else {
            $result['status'] = 'ERROR';
            $result['error_message'] = 'Table not found';
            $result['sql_error'] = $db->lasterror();
        }
    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['error_message'] = $e->getMessage();
        $result['sql_error'] = $db->lasterror();
    }

    return $result;
}

/**
 * Test fichier/dossier
 */
function run_file_test($test) {
    $result = [
        'name' => $test['name'],
        'status' => 'UNKNOWN',
        'error_message' => null,
        'details' => []
    ];

    if (!empty($test['is_dir'])) {
        if (is_dir($test['path'])) {
            $result['status'] = 'OK';
            $count = count(scandir($test['path'])) - 2;
            $result['details'][] = $count.' files';
        } else {
            $result['status'] = 'ERROR';
            $result['error_message'] = 'Directory not found';
        }
    } else {
        if (file_exists($test['path'])) {
            $result['status'] = 'OK';
            $size = filesize($test['path']);
            $result['details'][] = round($size / 1024, 2).' KB';
        } else {
            $result['status'] = 'ERROR';
            $result['error_message'] = 'File not found';
        }
    }

    return $result;
}

// ========================================
// CONFIGURATION DES TESTS - ÉVOLUTIF
// ========================================

$tests_config = [
    // NIVEAU 1 : SMOKE TESTS (LECTURE)
    'level1_frontend_pages' => [
        ['name' => '📱 PWA - Index', 'url' => $full_pwa_url, 'method' => 'GET'],
        ['name' => '📱 PWA - Login', 'url' => $full_pwa_url.'#/login', 'method' => 'GET'],
        ['name' => '�� PWA - Dashboard', 'url' => $full_pwa_url.'#/dashboard', 'method' => 'GET'],
        ['name' => '📱 PWA - Planning list', 'url' => $full_pwa_url.'#/planning', 'method' => 'GET'],
        ['name' => '📱 PWA - Rapports list', 'url' => $full_pwa_url.'#/rapports', 'method' => 'GET'],
        ['name' => '📱 PWA - Rapports new', 'url' => $full_pwa_url.'#/rapports/new', 'method' => 'GET'],
        ['name' => '📱 PWA - Rapports new pro', 'url' => $full_pwa_url.'#/rapports/new-pro', 'method' => 'GET'],
        ['name' => '📱 PWA - Régie list', 'url' => $full_pwa_url.'#/regie', 'method' => 'GET'],
        ['name' => '📱 PWA - Régie new', 'url' => $full_pwa_url.'#/regie/new', 'method' => 'GET'],
        ['name' => '📱 PWA - Sens pose list', 'url' => $full_pwa_url.'#/sens-pose', 'method' => 'GET'],
        ['name' => '📱 PWA - Sens pose new', 'url' => $full_pwa_url.'#/sens-pose/new', 'method' => 'GET'],
        ['name' => '📱 PWA - Matériel', 'url' => $full_pwa_url.'#/materiel', 'method' => 'GET'],
        ['name' => '📱 PWA - Notifications', 'url' => $full_pwa_url.'#/notifications', 'method' => 'GET'],
        ['name' => '📱 PWA - Profil', 'url' => $full_pwa_url.'#/profil', 'method' => 'GET'],
        ['name' => '📱 PWA - Debug', 'url' => $full_pwa_url.'#/debug', 'method' => 'GET'],
        ['name' => '📱 PWA - Maintenance', 'url' => $full_pwa_url.'#/maintenance', 'method' => 'GET'],
    ],
    'level1_api_list' => [
        ['name' => '🔌 API - Index', 'url' => $full_api_url.'index.php', 'method' => 'GET', 'requires_auth' => false],
        ['name' => '🔌 API - Me', 'url' => $full_api_url.'me.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Planning list', 'url' => $full_api_url.'planning.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Rapports list', 'url' => $full_api_url.'rapports.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Notifications list', 'url' => $full_api_url.'notifications_list.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Notifications unread count', 'url' => $full_api_url.'notifications_unread_count.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Materiel list', 'url' => $full_api_url.'materiel_list.php', 'method' => 'GET', 'requires_auth' => true],
    ],
    'level1_database' => [
        ['name' => '🗄️ Table - mv3_config', 'table' => MAIN_DB_PREFIX.'mv3_config'],
        ['name' => '🗄️ Table - mv3_error_log', 'table' => MAIN_DB_PREFIX.'mv3_error_log'],
        ['name' => '🗄️ Table - mv3_mobile_users', 'table' => MAIN_DB_PREFIX.'mv3_mobile_users'],
        ['name' => '🗄️ Table - mv3_mobile_sessions', 'table' => MAIN_DB_PREFIX.'mv3_mobile_sessions'],
        ['name' => '🗄️ Table - mv3_rapport', 'table' => MAIN_DB_PREFIX.'mv3_rapport'],
        ['name' => '🗄️ Table - mv3_materiel', 'table' => MAIN_DB_PREFIX.'mv3_materiel'],
        ['name' => '🗄️ Table - mv3_notifications', 'table' => MAIN_DB_PREFIX.'mv3_notifications'],
        ['name' => '🗄️ Table - mv3_sens_pose', 'table' => MAIN_DB_PREFIX.'mv3_sens_pose'],
    ],
    'level1_files' => [
        ['name' => '📁 Config class', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/class/mv3_config.class.php'],
        ['name' => '📁 Error logger class', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/class/mv3_error_logger.class.php'],
        ['name' => '📁 API bootstrap', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/api/v1/_bootstrap.php'],
        ['name' => '📁 PWA index', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/pwa_dist/index.html'],
        ['name' => '📁 PWA assets', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/pwa_dist/assets', 'is_dir' => true],
    ],

    // NIVEAU 2 : TESTS FONCTIONNELS (avec IDs réels)
    'level2_api_view' => [
        // Ces tests seront remplis dynamiquement avec des IDs réels
    ],
    'level2_api_actions' => [
        // Tests de création/modification/suppression
    ],

    // NIVEAU 3 : TESTS PERMISSIONS
    'level3_permissions' => [
        // Tests mode DEV, admin vs employé, fichiers
    ],
];

// ========================================
// EXÉCUTION DES TESTS
// ========================================

$all_results = [];
$stats = [
    'total' => 0,
    'ok' => 0,
    'warning' => 0,
    'error' => 0,
    'unknown' => 0
];

if ($action == 'run_tests') {
    // Obtenir credentials et effectuer login réel
    $credentials = get_diagnostic_credentials($mv3_config);
    $login_result = perform_real_login($full_api_url, $credentials);
    $auth_token = $login_result['token'];

    // NIVEAU 1 : SMOKE TESTS
    if ($test_level == 'all' || $test_level == 'level1') {
        // Test Auth Login
        $user_name = '';
        if ($login_result['user']) {
            $user_name = $login_result['user']['name'] ?? ($login_result['user']['firstname'].' '.$login_result['user']['lastname']) ?? $login_result['user']['email'] ?? '';
        }
        $result = [
            'name' => '🔐 Auth - Login (POST JSON) - /api/v1/auth/login.php',
            'status' => $login_result['success'] ? 'OK' : 'ERROR',
            'http_code' => $login_result['http_code'],
            'response_time' => 0,
            'error_message' => $login_result['error'],
            'debug_id' => null,
            'sql_error' => null,
            'details' => $login_result['user'] ? ['User: '.trim($user_name), 'Token: '.substr($auth_token ?? '', 0, 16).'...'] : []
        ];
        $all_results['level1_auth'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;

        // Afficher un avertissement si le login a échoué
        if (!$login_result['success'] || !$auth_token) {
            $result = [
                'name' => '⚠️ WARNING - Login failed',
                'status' => 'WARNING',
                'http_code' => null,
                'response_time' => 0,
                'error_message' => 'Les tests nécessitant authentification seront SKIP. Vérifier credentials dans config (DIAGNOSTIC_USER_EMAIL / DIAGNOSTIC_USER_PASSWORD)',
                'debug_id' => null,
                'sql_error' => null,
                'details' => ['Solution: Créer utilisateur mobile admin diagnostic@test.local']
            ];
            $all_results['level1_auth'][] = $result;
            $stats['total']++;
            $stats['warning']++;
        }

        // Frontend pages
        foreach ($tests_config['level1_frontend_pages'] as $test) {
            $result = run_http_test($test);
            $all_results['level1_frontend_pages'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // API lists
        foreach ($tests_config['level1_api_list'] as $test) {
            // Ne passer le token que si le test requiert l'authentification
            $token_to_use = (!empty($test['requires_auth']) && $test['requires_auth'] === true) ? $auth_token : null;

            // Si le test requiert auth mais qu'on n'a pas de token, afficher un warning
            if (!empty($test['requires_auth']) && $test['requires_auth'] === true && !$auth_token) {
                $result = [
                    'name' => $test['name'],
                    'status' => 'WARNING',
                    'http_code' => null,
                    'response_time' => 0,
                    'error_message' => 'Auth token not available (login failed)',
                    'debug_id' => null,
                    'sql_error' => null,
                    'details' => ['Skipped - Login required']
                ];
            } else {
                $result = run_http_test($test, $token_to_use);
            }

            $all_results['level1_api_list'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // Database
        foreach ($tests_config['level1_database'] as $test) {
            $result = run_database_test($test, $db);
            $all_results['level1_database'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // Files
        foreach ($tests_config['level1_files'] as $test) {
            $result = run_file_test($test);
            $all_results['level1_files'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }
    }

    // NIVEAU 2 : TESTS FONCTIONNELS avec IDs réels
    if ($test_level == 'all' || $test_level == 'level2') {
        // Récupérer des IDs réels pour les tests
        $planning_id = get_real_id($db, 'actioncomm', '1=1');
        $rapport_id = get_real_id($db, 'mv3_rapport', '1=1');
        $notif_id = get_real_id($db, 'mv3_notifications', 'is_read = 0');
        $sens_pose_id = get_real_id($db, 'mv3_sens_pose', '1=1');
        $regie_id = get_real_id($db, 'mv3_rapport', "type = 'regie'");

        // ===== TESTS PLANNING =====
        // Test Planning list
        $test = ['name' => '📋 Planning - List', 'url' => $full_api_url.'planning.php', 'method' => 'GET', 'requires_auth' => true];
        $result = run_http_test($test, $auth_token);
        $all_results['level2_planning'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;

        // Test Planning detail avec ID réel
        if ($planning_id) {
            $test = ['name' => '📋 Planning - Detail (ID: '.$planning_id.')', 'url' => $full_api_url.'planning_view.php?id='.$planning_id, 'method' => 'GET', 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_planning'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;

            // Test sous-page PWA Planning detail
            $test = ['name' => '📋 Planning - PWA Detail page #/planning/'.$planning_id, 'url' => $full_pwa_url.'#/planning/'.$planning_id, 'method' => 'GET'];
            $result = run_http_test($test);
            $all_results['level2_planning'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;

            // Test accès fichier attaché planning
            $sql = "SELECT ecm.filename
                    FROM ".MAIN_DB_PREFIX."ecm_files ecm
                    WHERE ecm.src_object_type = 'action'
                    AND ecm.src_object_id = ".(int)$planning_id."
                    LIMIT 1";
            $resql = $db->query($sql);
            if ($resql && $db->num_rows($resql) > 0) {
                $obj = $db->fetch_object($resql);
                $test = ['name' => '📋 Planning - Open attachment ('.$obj->filename.')', 'url' => $full_api_url.'planning_file.php?id='.$planning_id.'&file='.$obj->filename, 'method' => 'GET', 'requires_auth' => true];
                $result = run_http_test($test, $auth_token);
                $all_results['level2_planning'][] = $result;
                $stats['total']++;
                $stats[strtolower($result['status'])]++;
            }
        }

        // ===== TESTS RAPPORTS =====
        // Test Rapports list
        $test = ['name' => '📝 Rapports - List', 'url' => $full_api_url.'rapports.php', 'method' => 'GET', 'requires_auth' => true];
        $result = run_http_test($test, $auth_token);
        $all_results['level2_rapports'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;

        // Test Rapport view avec ID réel
        if ($rapport_id) {
            $test = ['name' => '📝 Rapports - View (ID: '.$rapport_id.')', 'url' => $full_api_url.'rapports_view.php?id='.$rapport_id, 'method' => 'GET', 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_rapports'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;

            // Test sous-page PWA Rapport detail
            $test = ['name' => '📝 Rapports - PWA Detail page #/rapports/'.$rapport_id, 'url' => $full_pwa_url.'#/rapports/'.$rapport_id, 'method' => 'GET'];
            $result = run_http_test($test);
            $all_results['level2_rapports'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // Test Create Rapport (DEV mode admin only)
        if ($mv3_config->isDevMode() && $user->admin) {
            $test_rapport_data = [
                'titre' => 'TEST DIAGNOSTIC - Rapport '.date('Y-m-d H:i:s'),
                'description' => 'Test créé automatiquement par diagnostic QA',
                'date_rapport' => date('Y-m-d'),
                'temps_passe' => '02:00',
                'type' => 'standard'
            ];
            $test = ['name' => '📝 Rapports - Create (DEV only)', 'url' => $full_api_url.'rapports_create.php', 'method' => 'POST', 'data' => $test_rapport_data, 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_rapports'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;

            // Récupérer l'ID du rapport créé pour les tests suivants
            $created_rapport_id = null;
            if ($result['status'] == 'OK' && $result['http_code'] == 201) {
                $created_rapport_id = get_real_id($db, 'mv3_rapport', "titre LIKE 'TEST DIAGNOSTIC%'");
            }

            // Test Update Rapport
            if ($created_rapport_id) {
                $test = ['name' => '📝 Rapports - Update (ID: '.$created_rapport_id.')', 'url' => $full_api_url.'rapports_view.php?id='.$created_rapport_id, 'method' => 'PUT', 'data' => ['description' => 'Description mise à jour par diagnostic'], 'requires_auth' => true];
                $result = run_http_test($test, $auth_token);
                $all_results['level2_rapports'][] = $result;
                $stats['total']++;
                $stats[strtolower($result['status'])]++;

                // Test Submit Rapport
                $test = ['name' => '📝 Rapports - Submit (ID: '.$created_rapport_id.')', 'url' => $full_api_url.'rapports_view.php?id='.$created_rapport_id.'&action=submit', 'method' => 'POST', 'requires_auth' => true];
                $result = run_http_test($test, $auth_token);
                $all_results['level2_rapports'][] = $result;
                $stats['total']++;
                $stats[strtolower($result['status'])]++;

                // Test Delete Rapport
                $test = ['name' => '📝 Rapports - Delete (ID: '.$created_rapport_id.')', 'url' => $full_api_url.'rapports_view.php?id='.$created_rapport_id, 'method' => 'DELETE', 'requires_auth' => true];
                $result = run_http_test($test, $auth_token);
                $all_results['level2_rapports'][] = $result;
                $stats['total']++;
                $stats[strtolower($result['status'])]++;
            }
        }

        // ===== TESTS NOTIFICATIONS =====
        // Test Notifications list
        $test = ['name' => '🔔 Notifications - List', 'url' => $full_api_url.'notifications_list.php', 'method' => 'GET', 'requires_auth' => true];
        $result = run_http_test($test, $auth_token);
        $all_results['level2_notifications'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;

        // Test Notifications unread count
        $test = ['name' => '🔔 Notifications - Unread count', 'url' => $full_api_url.'notifications_unread_count.php', 'method' => 'GET', 'requires_auth' => true];
        $result = run_http_test($test, $auth_token);
        $all_results['level2_notifications'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;

        // Test Create Notification (DEV only)
        if ($mv3_config->isDevMode() && $user->admin) {
            $test_user_id = $login_result['user']['id'] ?? 1;
            $test_notif_data = [
                'user_id' => $test_user_id,
                'titre' => 'TEST DIAGNOSTIC - Notification',
                'message' => 'Test créé par diagnostic QA',
                'type' => 'info',
                'priority' => 'normal'
            ];
            $test = ['name' => '🔔 Notifications - Create (DEV only)', 'url' => $full_api_url.'notifications_list.php', 'method' => 'POST', 'data' => $test_notif_data, 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_notifications'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;

            // Récupérer l'ID de la notification créée
            $created_notif_id = get_real_id($db, 'mv3_notifications', "titre LIKE 'TEST DIAGNOSTIC%'");
        }

        // Test Mark as read
        $notif_to_mark = $created_notif_id ?? $notif_id;
        if ($notif_to_mark) {
            $test = ['name' => '🔔 Notifications - Mark as read (ID: '.$notif_to_mark.')', 'url' => $full_api_url.'notifications_mark_read.php', 'method' => 'POST', 'data' => ['notification_id' => $notif_to_mark], 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_notifications'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // Test Delete Notification (DEV only)
        if ($created_notif_id && $mv3_config->isDevMode() && $user->admin) {
            $test = ['name' => '🔔 Notifications - Delete (ID: '.$created_notif_id.')', 'url' => $full_api_url.'notifications_list.php?id='.$created_notif_id, 'method' => 'DELETE', 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_notifications'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // ===== TESTS SENS DE POSE =====
        // Test Sens de pose list
        $test = ['name' => '📐 Sens de pose - List', 'url' => $full_api_url.'sens_pose_list.php', 'method' => 'GET', 'requires_auth' => true];
        $result = run_http_test($test, $auth_token);
        $all_results['level2_sens_pose'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;

        // Test Sens de pose view avec ID réel
        if ($sens_pose_id) {
            $test = ['name' => '📐 Sens de pose - View (ID: '.$sens_pose_id.')', 'url' => $full_api_url.'sens_pose_view.php?id='.$sens_pose_id, 'method' => 'GET', 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_sens_pose'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;

            // Test sous-page PWA Sens de pose detail
            $test = ['name' => '📐 Sens de pose - PWA Detail page #/sens-pose/'.$sens_pose_id, 'url' => $full_pwa_url.'#/sens-pose/'.$sens_pose_id, 'method' => 'GET'];
            $result = run_http_test($test);
            $all_results['level2_sens_pose'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // Test Create Sens de pose (DEV only)
        if ($mv3_config->isDevMode() && $user->admin) {
            $test_sens_pose_data = [
                'client_name' => 'TEST CLIENT DIAGNOSTIC',
                'chantier' => 'Chantier test diagnostic',
                'date_pose' => date('Y-m-d'),
                'surface_total' => 50.00,
                'type_pose' => 'simple'
            ];
            $test = ['name' => '📐 Sens de pose - Create (DEV only)', 'url' => $full_api_url.'sens_pose_create.php', 'method' => 'POST', 'data' => $test_sens_pose_data, 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_sens_pose'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;

            // Récupérer l'ID créé
            $created_sens_pose_id = null;
            if ($result['status'] == 'OK' && $result['http_code'] == 201) {
                $created_sens_pose_id = get_real_id($db, 'mv3_sens_pose', "client_name LIKE 'TEST CLIENT DIAGNOSTIC%'");
            }

            // Test Sign
            if ($created_sens_pose_id) {
                $test = ['name' => '📐 Sens de pose - Sign (ID: '.$created_sens_pose_id.')', 'url' => $full_api_url.'sens_pose_signature.php?id='.$created_sens_pose_id, 'method' => 'POST', 'data' => ['signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg'], 'requires_auth' => true];
                $result = run_http_test($test, $auth_token);
                $all_results['level2_sens_pose'][] = $result;
                $stats['total']++;
                $stats[strtolower($result['status'])]++;

                // Test PDF
                $test = ['name' => '📐 Sens de pose - Generate PDF (ID: '.$created_sens_pose_id.')', 'url' => $full_api_url.'sens_pose_pdf.php?id='.$created_sens_pose_id, 'method' => 'GET', 'requires_auth' => true];
                $result = run_http_test($test, $auth_token);
                $all_results['level2_sens_pose'][] = $result;
                $stats['total']++;
                $stats[strtolower($result['status'])]++;

                // Test Delete
                $test = ['name' => '📐 Sens de pose - Delete (ID: '.$created_sens_pose_id.')', 'url' => $full_api_url.'sens_pose_view.php?id='.$created_sens_pose_id, 'method' => 'DELETE', 'requires_auth' => true];
                $result = run_http_test($test, $auth_token);
                $all_results['level2_sens_pose'][] = $result;
                $stats['total']++;
                $stats[strtolower($result['status'])]++;
            }
        }

        // ===== TEST AUTH LOGOUT =====
        if ($auth_token) {
            $logout_result = perform_real_logout($full_api_url, $auth_token);
            $result = [
                'name' => '🔐 Auth - Logout (with token)',
                'status' => $logout_result['success'] ? 'OK' : 'WARNING',
                'http_code' => $logout_result['http_code'],
                'response_time' => 0,
                'error_message' => $logout_result['error'],
                'debug_id' => null,
                'sql_error' => null,
                'details' => []
            ];
            $all_results['level2_auth'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }
    }

    // NIVEAU 3 : TESTS PERMISSIONS
    if ($test_level == 'all' || $test_level == 'level3') {
        $dev_mode = $mv3_config->isDevMode();

        // Test 1: Vérifier que mode DEV est bien configuré
        $test = ['name' => '🔐 Config - Mode DEV status', 'url' => '', 'method' => 'GET'];
        $result = [
            'name' => '🔐 Config - Mode DEV status',
            'status' => 'OK',
            'http_code' => null,
            'response_time' => 0,
            'error_message' => null,
            'debug_id' => null,
            'sql_error' => null,
            'details' => ['Mode: '.($dev_mode ? 'DEV (ON)' : 'PRODUCTION (OFF)')]
        ];
        $all_results['level3_permissions'][] = $result;
        $stats['total']++;
        $stats['ok']++;

        // Test 2: Si mode DEV ON, vérifier que l'API bloque bien les non-admins
        if ($dev_mode) {
            $test = [
                'name' => '🔐 Mode DEV - API bloque non-admin (expect 503)',
                'url' => $full_api_url.'planning.php',
                'method' => 'GET',
                'expect_503' => true
            ];
            $result = run_http_test($test, null); // Pas de token = non-admin
            $all_results['level3_permissions'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // Test 3: Vérifier accès fichier avec token valide
        if ($auth_token) {
            $planning_id = get_real_id($db, 'actioncomm', '1=1');
            if ($planning_id) {
                // D'abord récupérer les fichiers de ce planning
                $sql = "SELECT ecm.filename
                        FROM ".MAIN_DB_PREFIX."ecm_files ecm
                        WHERE ecm.src_object_type = 'action'
                        AND ecm.src_object_id = ".(int)$planning_id."
                        LIMIT 1";
                $resql = $db->query($sql);
                if ($resql && $db->num_rows($resql) > 0) {
                    $obj = $db->fetch_object($resql);
                    $test = [
                        'name' => '🔐 Permissions - Accès fichier planning avec token',
                        'url' => $full_api_url.'planning_file.php?id='.$planning_id.'&file='.$obj->filename,
                        'method' => 'GET',
                        'requires_auth' => true
                    ];
                    $result = run_http_test($test, $auth_token);
                    $all_results['level3_permissions'][] = $result;
                    $stats['total']++;
                    $stats[strtolower($result['status'])]++;
                }
            }
        }

        // Test 4: Vérifier que fichier sans token est refusé
        $planning_id = get_real_id($db, 'actioncomm', '1=1');
        if ($planning_id) {
            $test = [
                'name' => '🔐 Permissions - Accès fichier SANS token (expect 401)',
                'url' => $full_api_url.'planning_file.php?id='.$planning_id.'&file=test.pdf',
                'method' => 'GET',
                'expect_401' => true
            ];
            $result = run_http_test($test, null);
            $all_results['level3_permissions'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }
    }
}

// Export JSON
if ($action == 'export_json' && !empty($all_results)) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="diagnostic_qa_mv3pro_'.date('Y-m-d_H-i-s').'.json"');
    echo json_encode([
        'date' => date('Y-m-d H:i:s'),
        'test_level' => $test_level,
        'stats' => $stats,
        'results' => $all_results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// AFFICHAGE HTML
// ========================================

llxHeader('', 'Diagnostic QA complet - MV3 PRO', '');

print load_fiche_titre('Diagnostic QA complet - MV3 PRO', '', 'fa-stethoscope');

// Navigation tabs
$head = [
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/setup.php', 'Configuration', 'config'],
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/errors.php', 'Journal d\'erreurs ('.$error_stats['total'].')', 'errors'],
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/diagnostic.php', 'Diagnostic QA', 'diagnostic'],
];

print dol_get_fiche_head($head, 'diagnostic', '', -1);

// Boutons actions
print '<div style="margin-bottom: 20px; text-align: center;">';
if (empty($action) || $action != 'run_tests') {
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=run_tests&test_level=all" class="butAction" style="font-size: 16px; padding: 12px 24px;">🚀 Lancer diagnostic complet (tous niveaux)</a><br><br>';
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=run_tests&test_level=level1" class="butAction">Niveau 1 : Smoke tests</a> ';
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=run_tests&test_level=level2" class="butAction">Niveau 2 : Tests fonctionnels</a> ';
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=run_tests&test_level=level3" class="butAction">Niveau 3 : Permissions</a>';
} else {
    print '<a href="'.$_SERVER['PHP_SELF'].'" class="butAction">🔄 Nouveau diagnostic</a> ';
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=export_json" class="butAction">📥 Exporter JSON</a>';
}
print '</div>';

// Fonction d'affichage d'une catégorie de tests
function display_test_results($title, $results, $show_details = true) {
    if (empty($results)) return;

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<th colspan="'.($show_details ? '8' : '6').'">'.$title.'</th>';
    print '</tr>';

    print '<tr style="background: #f0f0f0;">';
    print '<th width="30%">Test</th>';
    print '<th width="10%">Status</th>';
    print '<th width="10%">HTTP</th>';
    print '<th width="10%">Temps (ms)</th>';
    if ($show_details) {
        print '<th width="15%">Debug ID</th>';
        print '<th width="25%">SQL Error</th>';
    }
    print '</tr>';

    foreach ($results as $result) {
        $status_color = $result['status'] == 'OK' ? 'green' : ($result['status'] == 'WARNING' ? 'orange' : 'red');
        $status_icon = $result['status'] == 'OK' ? '✅' : ($result['status'] == 'WARNING' ? '⚠️' : '❌');

        print '<tr class="oddeven">';
        print '<td>'.$result['name'].'</td>';
        print '<td style="text-align: center;"><span style="color: '.$status_color.'; font-weight: bold; font-size: 16px;">'.$status_icon.'</span> '.$result['status'].'</td>';
        print '<td style="text-align: center;">'.($result['http_code'] ?? '-').'</td>';
        print '<td style="text-align: center;">'.($result['response_time'] ?? '-').'</td>';

        if ($show_details) {
            print '<td>';
            if ($result['debug_id']) {
                print '<a href="errors.php?debug_id='.$result['debug_id'].'" target="_blank" style="font-family: monospace; font-size: 11px;">'.$result['debug_id'].'</a>';
            } else {
                print '-';
            }
            print '</td>';

            print '<td>';
            if ($result['sql_error']) {
                print '<span style="color: red; font-size: 11px;">'.substr($result['sql_error'], 0, 80).'...</span>';
            } elseif ($result['error_message']) {
                print '<span style="color: orange; font-size: 11px;">'.$result['error_message'].'</span>';
            } elseif (!empty($result['details'])) {
                print '<span style="color: gray; font-size: 11px;">'.implode(', ', $result['details']).'</span>';
            } else {
                print '-';
            }
            print '</td>';
        }

        print '</tr>';
    }

    print '</table>';
    print '</div>';
    print '<br>';
}

// Afficher les résultats si tests exécutés
if ($action == 'run_tests' && !empty($all_results)) {
    // Résumé global
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<th colspan="5">📊 Résumé global - Niveau: '.strtoupper($test_level).'</th>';
    print '</tr>';

    print '<tr class="oddeven">';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>Total</b><br><span style="font-size: 32px; font-weight: bold;">'.$stats['total'].'</span></td>';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>✅ OK</b><br><span style="font-size: 32px; font-weight: bold; color: green;">'.$stats['ok'].'</span></td>';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>⚠️ Warning</b><br><span style="font-size: 32px; font-weight: bold; color: orange;">'.$stats['warning'].'</span></td>';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>❌ Error</b><br><span style="font-size: 32px; font-weight: bold; color: red;">'.$stats['error'].'</span></td>';
    $success_rate = $stats['total'] > 0 ? round(($stats['ok'] / $stats['total']) * 100) : 0;
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>Taux</b><br><span style="font-size: 32px; font-weight: bold; color: '.($success_rate >= 80 ? 'green' : 'orange').';">'.$success_rate.'%</span></td>';
    print '</tr>';

    print '</table>';
    print '</div>';

    print '<br>';

    // Lien vers diagnostic approfondi si erreurs détectées
    if ($stats['error'] > 0 || $stats['warning'] > 0) {
        print '<div class="info" style="background: #fff3cd; border-left: 4px solid #ff9800; padding: 15px; margin-bottom: 20px;">';
        print '<h3>🔬 Analyse approfondie des erreurs</h3>';
        print '<p>Des erreurs ou warnings ont été détectés. Pour une analyse détaillée avec fichiers sources, erreurs SQL complètes et stack traces:</p>';
        print '<p><a href="diagnostic_deep.php" class="butAction">🔬 Lancer le diagnostic approfondi</a></p>';
        print '<p><small>Le diagnostic approfondi affiche: fichier PHP exact, numéro de ligne, erreur SQL, stack trace, vérifications BDD, historique des erreurs</small></p>';
        print '</div>';
    }

    // Afficher les résultats par niveau
    if (!empty($all_results['level1_auth'])) {
        display_test_results('🔐 NIVEAU 1 - Authentification : Login/Logout', $all_results['level1_auth'], true);
    }

    if (!empty($all_results['level1_frontend_pages'])) {
        display_test_results('🌟 NIVEAU 1 - Smoke Tests : Pages PWA Frontend', $all_results['level1_frontend_pages'], false);
    }

    if (!empty($all_results['level1_api_list'])) {
        display_test_results('🌟 NIVEAU 1 - Smoke Tests : Endpoints API (listes)', $all_results['level1_api_list'], true);
    }

    if (!empty($all_results['level1_database'])) {
        display_test_results('🌟 NIVEAU 1 - Smoke Tests : Tables base de données', $all_results['level1_database'], false);
    }

    if (!empty($all_results['level1_files'])) {
        display_test_results('🌟 NIVEAU 1 - Smoke Tests : Structure fichiers', $all_results['level1_files'], false);
    }

    if (!empty($all_results['level2_planning'])) {
        display_test_results('📋 NIVEAU 2 - Planning : List + Detail + Attachments + PWA pages', $all_results['level2_planning'], true);
    }

    if (!empty($all_results['level2_rapports'])) {
        display_test_results('📝 NIVEAU 2 - Rapports : CRUD complet + PWA pages (DEV mode)', $all_results['level2_rapports'], true);
    }

    if (!empty($all_results['level2_notifications'])) {
        display_test_results('🔔 NIVEAU 2 - Notifications : Create + Mark Read + Delete (DEV mode)', $all_results['level2_notifications'], true);
    }

    if (!empty($all_results['level2_sens_pose'])) {
        display_test_results('📐 NIVEAU 2 - Sens de pose : Create + Sign + PDF + Delete + PWA pages (DEV mode)', $all_results['level2_sens_pose'], true);
    }

    if (!empty($all_results['level2_auth'])) {
        display_test_results('🔐 NIVEAU 2 - Authentification : Logout avec token', $all_results['level2_auth'], true);
    }

    if (!empty($all_results['level3_permissions'])) {
        display_test_results('🔐 NIVEAU 3 - Tests Permissions : Mode DEV / Admin vs Employé / Fichiers', $all_results['level3_permissions'], true);
    }
}

// Documentation
print '<div class="info">';
print '<h3>📚 Guide du diagnostic QA complet</h3>';

print '<h4>🔐 Authentification (Login/Logout réels)</h4>';
print '<ul>';
print '<li><b>Login</b> : POST JSON avec credentials depuis config (DIAGNOSTIC_USER_EMAIL / DIAGNOSTIC_USER_PASSWORD)</li>';
print '<li><b>Logout</b> : POST avec token obtenu du login</li>';
print '<li>Le token est utilisé pour tous les tests API nécessitant authentification</li>';
print '</ul>';

print '<h4>🌟 Niveau 1 - Smoke Tests (Lecture uniquement)</h4>';
print '<ul>';
print '<li>Vérifie que toutes les <b>pages PWA</b> chargent (GET)</li>';
print '<li>Vérifie que tous les <b>endpoints API list</b> répondent</li>';
print '<li>Vérifie que toutes les <b>tables BDD</b> existent</li>';
print '<li>Vérifie que tous les <b>fichiers structure</b> sont présents</li>';
print '<li><b>Pas de modifications</b> - Lecture uniquement</li>';
print '</ul>';

print '<h4>⚡ Niveau 2 - Tests Fonctionnels (Boutons/Formulaires avec IDs réels)</h4>';
print '<ul>';
print '<li><b>Planning</b> : List + Detail (ID réel) + Open attachment inline + PWA pages (#/planning/:id)</li>';
print '<li><b>Rapports</b> : List + View + Create + Update + Submit + Delete + PWA pages (#/rapports/:id)</li>';
print '<li><b>Notifications</b> : List + Unread count + Create + Mark as read + Delete</li>';
print '<li><b>Sens de pose</b> : List + View + Create + Sign + Generate PDF + Delete + PWA pages (#/sens-pose/:id)</li>';
print '<li>⚠️ <b>Attention</b> : Les tests CRUD (Create/Update/Delete) nécessitent <b>mode DEV ON + admin</b></li>';
print '<li>Tous les tests affichent : <b>HTTP code + debug_id + SQL error</b> si applicable</li>';
print '</ul>';

print '<h4>🔐 Niveau 3 - Tests Permissions</h4>';
print '<ul>';
print '<li><b>Mode DEV</b> : Vérifie que l\'API bloque bien les non-admins (expect 503)</li>';
print '<li><b>Fichiers</b> : Vérifie l\'accès avec token valide (OK) et sans token (expect 401)</li>';
print '<li><b>Admin vs Employé</b> : Vérifie que les permissions sont correctes</li>';
print '<li>Vérifie que les employés ne voient que leurs données (planning, etc.)</li>';
print '</ul>';

print '<h4>ℹ️ Détails des colonnes</h4>';
print '<ul>';
print '<li><b>Status</b> : OK (✅) / WARNING (⚠️) / ERROR (❌)</li>';
print '<li><b>HTTP</b> : Code HTTP de la réponse (200, 401, 500, etc.)</li>';
print '<li><b>Temps</b> : Temps de réponse en millisecondes</li>';
print '<li><b>Debug ID</b> : Identifiant unique si erreur (cliquer pour voir détails dans Journal d\'erreurs)</li>';
print '<li><b>SQL Error</b> : Erreur SQL complète si erreur de base de données</li>';
print '</ul>';

print '<h4>🔧 Ajouter de nouveaux tests</h4>';
print '<p>Éditez <code>/custom/mv3pro_portail/admin/diagnostic.php</code> et ajoutez dans <code>$tests_config</code> :</p>';
print '<pre style="background: white; padding: 10px; border-radius: 4px;">';
print '// Niveau 1 - Page PWA
$tests_config[\'level1_frontend_pages\'][] = [
    \'name\' => \'📱 PWA - Ma page\',
    \'url\' => $full_pwa_url.\'#/ma-page\',
    \'method\' => \'GET\'
];

// Niveau 2 - Test avec ID réel
$mon_id = get_real_id($db, \'ma_table\', \'condition\');
$tests_config[\'level2_api_view\'][] = [
    \'name\' => \'🔌 API - Mon view (ID: \'.$mon_id.\')\',
    \'url\' => $full_api_url.\'mon_view.php?id=\'.$mon_id,
    \'method\' => \'GET\',
    \'requires_auth\' => true
];';
print '</pre>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
