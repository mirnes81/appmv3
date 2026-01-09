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
 * Récupère un token mobile admin valide pour les tests
 */
function get_test_admin_token($db) {
    // Chercher un utilisateur mobile admin actif avec session valide
    $sql = "SELECT s.session_token
            FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions s
            INNER JOIN ".MAIN_DB_PREFIX."mv3_mobile_users u ON u.rowid = s.user_id
            WHERE s.expires_at > NOW()
            AND u.is_active = 1
            AND u.role = 'admin'
            ORDER BY s.expires_at DESC
            LIMIT 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        return $obj->session_token;
    }

    return null;
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
        ['name' => '🗄️ Table - mv3_config', 'table' => 'mv3_config'],
        ['name' => '🗄️ Table - mv3_error_log', 'table' => 'mv3_error_log'],
        ['name' => '🗄️ Table - mv3_mobile_users', 'table' => 'mv3_mobile_users'],
        ['name' => '🗄️ Table - mv3_mobile_sessions', 'table' => 'mv3_mobile_sessions'],
        ['name' => '🗄️ Table - mv3_rapport', 'table' => 'mv3_rapport'],
        ['name' => '🗄️ Table - mv3_materiel', 'table' => 'mv3_materiel'],
        ['name' => '🗄️ Table - mv3_notifications', 'table' => 'mv3_notifications'],
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
    $auth_token = get_test_admin_token($db);

    // NIVEAU 1 : SMOKE TESTS
    if ($test_level == 'all' || $test_level == 'level1') {
        // Frontend pages
        foreach ($tests_config['level1_frontend_pages'] as $test) {
            $result = run_http_test($test);
            $all_results['level1_frontend_pages'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // API lists
        foreach ($tests_config['level1_api_list'] as $test) {
            $result = run_http_test($test, $auth_token);
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

        // Tests API view avec IDs réels
        if ($planning_id) {
            $test = ['name' => '🔌 API - Planning view (ID réel: '.$planning_id.')', 'url' => $full_api_url.'planning_view.php?id='.$planning_id, 'method' => 'GET', 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_api_view'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        if ($rapport_id) {
            $test = ['name' => '🔌 API - Rapport view (ID réel: '.$rapport_id.')', 'url' => $full_api_url.'rapports_view.php?id='.$rapport_id, 'method' => 'GET', 'requires_auth' => true];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_api_view'][] = $result;
            $stats['total']++;
            $stats[strtolower($result['status'])]++;
        }

        // Test marquer notification lue
        if ($notif_id) {
            $test = [
                'name' => '🔌 API - Marquer notification lue (ID: '.$notif_id.')',
                'url' => $full_api_url.'notifications_mark_read.php',
                'method' => 'POST',
                'data' => ['notification_id' => $notif_id],
                'requires_auth' => true
            ];
            $result = run_http_test($test, $auth_token);
            $all_results['level2_api_actions'][] = $result;
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

    // Afficher les résultats par niveau
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

    if (!empty($all_results['level2_api_view'])) {
        display_test_results('⚡ NIVEAU 2 - Tests Fonctionnels : API View (IDs réels)', $all_results['level2_api_view'], true);
    }

    if (!empty($all_results['level2_api_actions'])) {
        display_test_results('⚡ NIVEAU 2 - Tests Fonctionnels : Actions (créer/modifier)', $all_results['level2_api_actions'], true);
    }

    if (!empty($all_results['level3_permissions'])) {
        display_test_results('🔐 NIVEAU 3 - Tests Permissions : Mode DEV / Admin vs Employé / Fichiers', $all_results['level3_permissions'], true);
    }
}

// Documentation
print '<div class="info">';
print '<h3>📚 Guide du diagnostic QA</h3>';
print '<h4>🌟 Niveau 1 - Smoke Tests (Lecture)</h4>';
print '<ul>';
print '<li>Vérifie que toutes les <b>pages PWA</b> chargent (GET)</li>';
print '<li>Vérifie que tous les <b>endpoints API list</b> répondent</li>';
print '<li>Vérifie que toutes les <b>tables BDD</b> existent</li>';
print '<li>Vérifie que tous les <b>fichiers structure</b> sont présents</li>';
print '<li><b>Pas de modifications</b> - Lecture uniquement</li>';
print '</ul>';

print '<h4>⚡ Niveau 2 - Tests Fonctionnels (Boutons/Formulaires)</h4>';
print '<ul>';
print '<li>Teste les <b>endpoints View</b> avec des <b>IDs réels</b> récupérés depuis les listes</li>';
print '<li>Teste les <b>actions</b> : créer, modifier, supprimer (en mode DEV admin uniquement)</li>';
print '<li>Exemple : Marquer une notification comme lue, créer un rapport test, etc.</li>';
print '<li>⚠️ <b>Attention</b> : Ces tests modifient les données (mode DEV recommandé)</li>';
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
