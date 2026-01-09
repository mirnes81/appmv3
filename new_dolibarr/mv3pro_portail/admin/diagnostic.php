<?php
/**
 * Diagnostic système MV3 PRO Portail
 * Teste automatiquement toutes les pages et endpoints
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

// Récupération des URLs
$api_base_url = $mv3_config->get('API_BASE_URL', '/custom/mv3pro_portail/api/v1/');
$pwa_base_url = $mv3_config->get('PWA_BASE_URL', '/custom/mv3pro_portail/pwa_dist/');
$full_pwa_url = dol_buildpath($pwa_base_url, 2);
$full_api_url = dol_buildpath($api_base_url, 2);

// Statistiques erreurs
$error_stats = $error_logger->getStats(7);

// CONFIGURATION DES TESTS - ÉVOLUTIF
// Pour ajouter un nouveau test, il suffit d'ajouter une ligne ici
$tests_config = [
    'frontend_pages' => [
        ['name' => '📱 PWA - Page d\'accueil', 'url' => $full_pwa_url, 'method' => 'GET'],
        ['name' => '📱 PWA - Login', 'url' => $full_pwa_url.'#/login', 'method' => 'GET'],
        ['name' => '📱 PWA - Dashboard', 'url' => $full_pwa_url.'#/dashboard', 'method' => 'GET'],
        ['name' => '📱 PWA - Planning', 'url' => $full_pwa_url.'#/planning', 'method' => 'GET'],
        ['name' => '📱 PWA - Rapports', 'url' => $full_pwa_url.'#/rapports', 'method' => 'GET'],
        ['name' => '📱 PWA - Régie', 'url' => $full_pwa_url.'#/regie', 'method' => 'GET'],
        ['name' => '📱 PWA - Sens de pose', 'url' => $full_pwa_url.'#/sens-pose', 'method' => 'GET'],
        ['name' => '📱 PWA - Matériel', 'url' => $full_pwa_url.'#/materiel', 'method' => 'GET'],
        ['name' => '📱 PWA - Notifications', 'url' => $full_pwa_url.'#/notifications', 'method' => 'GET'],
        ['name' => '📱 PWA - Profil', 'url' => $full_pwa_url.'#/profil', 'method' => 'GET'],
        ['name' => '📱 PWA - Debug', 'url' => $full_pwa_url.'#/debug', 'method' => 'GET'],
    ],
    'backend_api' => [
        ['name' => '🔌 API - Index', 'url' => $full_api_url.'index.php', 'method' => 'GET', 'requires_auth' => false],
        ['name' => '🔌 API - Me', 'url' => $full_api_url.'me.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Planning list', 'url' => $full_api_url.'planning.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Planning view', 'url' => $full_api_url.'planning_view.php?id=1', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Rapports list', 'url' => $full_api_url.'rapports.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Notifications list', 'url' => $full_api_url.'notifications_list.php', 'method' => 'GET', 'requires_auth' => true],
        ['name' => '🔌 API - Materiel list', 'url' => $full_api_url.'materiel_list.php', 'method' => 'GET', 'requires_auth' => true],
    ],
    'backend_auth' => [
        ['name' => '🔐 Auth - Login endpoint', 'url' => dol_buildpath('/custom/mv3pro_portail/mobile_app/api/auth.php?action=login', 2), 'method' => 'POST'],
        ['name' => '🔐 Auth - Logout endpoint', 'url' => dol_buildpath('/custom/mv3pro_portail/mobile_app/api/auth.php?action=logout', 2), 'method' => 'POST'],
    ],
    'database_tables' => [
        ['name' => '🗄️ Table - mv3_config', 'table' => MAIN_DB_PREFIX.'mv3_config'],
        ['name' => '🗄️ Table - mv3_error_log', 'table' => MAIN_DB_PREFIX.'mv3_error_log'],
        ['name' => '🗄️ Table - mv3_mobile_users', 'table' => MAIN_DB_PREFIX.'mv3_mobile_users'],
        ['name' => '🗄️ Table - mv3_rapport', 'table' => MAIN_DB_PREFIX.'mv3_rapport'],
        ['name' => '🗄️ Table - mv3_materiel', 'table' => MAIN_DB_PREFIX.'mv3_materiel'],
        ['name' => '🗄️ Table - mv3_notifications', 'table' => MAIN_DB_PREFIX.'mv3_notifications'],
    ],
    'files_structure' => [
        ['name' => '📁 Fichier - API bootstrap', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/api/v1/_bootstrap.php'],
        ['name' => '📁 Fichier - Config class', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/class/mv3_config.class.php'],
        ['name' => '📁 Fichier - Error logger class', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/class/mv3_error_logger.class.php'],
        ['name' => '📁 Fichier - PWA index.html', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/pwa_dist/index.html'],
        ['name' => '📁 Dossier - PWA assets', 'path' => DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/pwa_dist/assets', 'is_dir' => true],
    ],
];

// Fonction de test HTTP
function run_http_test($test) {
    $result = [
        'name' => $test['name'],
        'status' => 'UNKNOWN',
        'http_code' => null,
        'response_time' => 0,
        'error_message' => null,
        'details' => []
    ];

    $start = microtime(true);

    try {
        $ch = curl_init($test['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($test['method'] == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        // Si nécessite auth, ajouter un token de test
        if (!empty($test['requires_auth'])) {
            // Note: En production, utiliser un vrai token de test
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer test_token',
                'X-Auth-Token: test_token'
            ]);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        curl_close($ch);

        $result['http_code'] = $http_code;

        if ($curl_error) {
            $result['status'] = 'ERROR';
            $result['error_message'] = 'cURL Error: '.$curl_error;
        } elseif ($http_code == 200) {
            $result['status'] = 'OK';
        } elseif ($http_code == 401 && !empty($test['requires_auth'])) {
            $result['status'] = 'OK'; // Normal si pas de token valide
            $result['details'][] = 'Auth required (expected)';
        } elseif ($http_code >= 400 && $http_code < 500) {
            $result['status'] = 'WARNING';
            $result['error_message'] = 'Client error';
        } elseif ($http_code >= 500) {
            $result['status'] = 'ERROR';
            $result['error_message'] = 'Server error';
        } else {
            $result['status'] = 'WARNING';
        }
    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['error_message'] = $e->getMessage();
    }

    $result['response_time'] = round((microtime(true) - $start) * 1000, 2);

    return $result;
}

// Fonction de test table BDD
function run_database_test($test, $db) {
    $result = [
        'name' => $test['name'],
        'status' => 'UNKNOWN',
        'error_message' => null,
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
        }
    } catch (Exception $e) {
        $result['status'] = 'ERROR';
        $result['error_message'] = $e->getMessage();
    }

    return $result;
}

// Fonction de test fichier/dossier
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
            $count = count(scandir($test['path'])) - 2; // -2 pour . et ..
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

// Exécuter tous les tests
$all_results = [];
$stats = [
    'total' => 0,
    'ok' => 0,
    'warning' => 0,
    'error' => 0,
    'unknown' => 0
];

if ($action == 'run_tests') {
    // Tests pages frontend
    foreach ($tests_config['frontend_pages'] as $test) {
        $result = run_http_test($test);
        $all_results['frontend_pages'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;
    }

    // Tests API backend
    foreach ($tests_config['backend_api'] as $test) {
        $result = run_http_test($test);
        $all_results['backend_api'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;
    }

    // Tests Auth
    foreach ($tests_config['backend_auth'] as $test) {
        $result = run_http_test($test);
        $all_results['backend_auth'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;
    }

    // Tests tables BDD
    foreach ($tests_config['database_tables'] as $test) {
        $result = run_database_test($test, $db);
        $all_results['database_tables'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;
    }

    // Tests fichiers
    foreach ($tests_config['files_structure'] as $test) {
        $result = run_file_test($test);
        $all_results['files_structure'][] = $result;
        $stats['total']++;
        $stats[strtolower($result['status'])]++;
    }
}

// Export JSON
if ($action == 'export_json' && !empty($all_results)) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="diagnostic_mv3pro_'.date('Y-m-d_H-i-s').'.json"');
    echo json_encode([
        'date' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'results' => $all_results
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Header
llxHeader('', 'Diagnostic système MV3 PRO', '');

print load_fiche_titre('Diagnostic système MV3 PRO', '', 'fa-stethoscope');

// Navigation tabs
$head = [
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/setup.php', 'Configuration', 'config'],
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/errors.php', 'Journal d\'erreurs ('.$error_stats['total'].')', 'errors'],
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/diagnostic.php', 'Diagnostic système', 'diagnostic'],
];

print dol_get_fiche_head($head, 'diagnostic', '', -1);

// Actions
print '<div style="margin-bottom: 20px; text-align: center;">';
if (empty($action) || $action != 'run_tests') {
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=run_tests" class="butAction" style="font-size: 16px; padding: 12px 24px;">🚀 Lancer le diagnostic complet</a>';
} else {
    print '<a href="'.$_SERVER['PHP_SELF'].'" class="butAction">🔄 Nouveau diagnostic</a> ';
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=export_json" class="butAction">📥 Exporter JSON</a>';
}
print '</div>';

// Afficher les statistiques si tests exécutés
if ($action == 'run_tests' && !empty($all_results)) {
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<th colspan="5">📊 Résumé du diagnostic</th>';
    print '</tr>';

    print '<tr class="oddeven">';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>Total tests</b><br><span style="font-size: 32px; font-weight: bold;">'.$stats['total'].'</span></td>';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>✅ OK</b><br><span style="font-size: 32px; font-weight: bold; color: green;">'.$stats['ok'].'</span></td>';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>⚠️ Warnings</b><br><span style="font-size: 32px; font-weight: bold; color: orange;">'.$stats['warning'].'</span></td>';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>❌ Erreurs</b><br><span style="font-size: 32px; font-weight: bold; color: red;">'.$stats['error'].'</span></td>';
    print '<td style="text-align: center; font-size: 18px; padding: 20px;"><b>Taux de réussite</b><br><span style="font-size: 32px; font-weight: bold; color: '.($stats['ok'] / $stats['total'] > 0.8 ? 'green' : 'orange').';">'.round(($stats['ok'] / $stats['total']) * 100).'%</span></td>';
    print '</tr>';

    print '</table>';
    print '</div>';

    print '<br>';

    // Afficher chaque catégorie de tests
    $categories = [
        'frontend_pages' => '📱 Pages PWA Frontend',
        'backend_api' => '🔌 Endpoints API Backend',
        'backend_auth' => '🔐 Authentification',
        'database_tables' => '🗄️ Tables Base de données',
        'files_structure' => '📁 Structure fichiers'
    ];

    foreach ($categories as $cat_key => $cat_label) {
        if (empty($all_results[$cat_key])) continue;

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';

        print '<tr class="liste_titre">';
        print '<th colspan="5">'.$cat_label.'</th>';
        print '</tr>';

        print '<tr style="background: #f0f0f0;">';
        print '<th width="40%">Test</th>';
        print '<th width="15%">Status</th>';
        print '<th width="15%">HTTP Code</th>';
        print '<th width="15%">Temps (ms)</th>';
        print '<th width="15%">Détails</th>';
        print '</tr>';

        foreach ($all_results[$cat_key] as $result) {
            $status_color = $result['status'] == 'OK' ? 'green' : ($result['status'] == 'WARNING' ? 'orange' : 'red');
            $status_icon = $result['status'] == 'OK' ? '✅' : ($result['status'] == 'WARNING' ? '⚠️' : '❌');

            print '<tr class="oddeven">';
            print '<td>'.$result['name'].'</td>';
            print '<td style="text-align: center;"><span style="color: '.$status_color.'; font-weight: bold; font-size: 16px;">'.$status_icon.'</span> '.$result['status'].'</td>';
            print '<td style="text-align: center;">'.($result['http_code'] ?? '-').'</td>';
            print '<td style="text-align: center;">'.($result['response_time'] ?? '-').'</td>';
            print '<td>';
            if ($result['error_message']) {
                print '<span style="color: red;">'.$result['error_message'].'</span>';
            } elseif (!empty($result['details'])) {
                print implode(', ', $result['details']);
            } else {
                print '-';
            }
            print '</td>';
            print '</tr>';
        }

        print '</table>';
        print '</div>';

        print '<br>';
    }
}

// Instructions
print '<div class="info">';
print '<h3>📚 À propos du diagnostic</h3>';
print '<ul>';
print '<li><b>Lancer le diagnostic</b> : Teste automatiquement toutes les pages PWA, tous les endpoints API, les tables BDD, et la structure fichiers</li>';
print '<li><b>✅ OK</b> : Le test a réussi</li>';
print '<li><b>⚠️ WARNING</b> : Le test a partiellement réussi ou retourné un code inhabituel</li>';
print '<li><b>❌ ERROR</b> : Le test a échoué (page non trouvée, erreur serveur, etc.)</li>';
print '<li><b>Exporter JSON</b> : Télécharge un rapport complet en JSON pour analyse ou archivage</li>';
print '<li><b>Évolutivité</b> : Pour ajouter un nouveau test, modifiez simplement le fichier diagnostic.php et ajoutez une ligne dans $tests_config</li>';
print '</ul>';
print '</div>';

print '<br>';

// Info évolutivité
print '<div class="info" style="background: #e3f2fd;">';
print '<h3>🔧 Comment ajouter un nouveau test ?</h3>';
print '<p>Éditez le fichier <code>/custom/mv3pro_portail/admin/diagnostic.php</code> et ajoutez une ligne dans la section <code>$tests_config</code> :</p>';
print '<pre style="background: white; padding: 10px; border-radius: 4px;">';
print '// Pour une page PWA:
[\'name\' => \'📱 PWA - Ma nouvelle page\', \'url\' => $full_pwa_url.\'#/ma-page\', \'method\' => \'GET\'],

// Pour un endpoint API:
[\'name\' => \'🔌 API - Mon endpoint\', \'url\' => $full_api_url.\'mon_endpoint.php\', \'method\' => \'GET\', \'requires_auth\' => true],

// Pour une table BDD:
[\'name\' => \'🗄️ Table - ma_table\', \'table\' => MAIN_DB_PREFIX.\'ma_table\'],

// Pour un fichier:
[\'name\' => \'📁 Fichier - mon fichier\', \'path\' => DOL_DOCUMENT_ROOT.\'/custom/mv3pro_portail/mon_fichier.php\'],';
print '</pre>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
