<?php
/**
 * Diagnostic APPROFONDI - Analyse détaillée des erreurs
 *
 * Affiche la source exacte de chaque problème:
 * - Erreurs SQL complètes
 * - Stack traces
 * - Logs serveur
 * - Variables d'environnement
 * - État des tables
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

// Récupération des URLs
$api_base_url = $mv3_config->get('API_BASE_URL', '/custom/mv3pro_portail/api/v1/');
$full_api_url = dol_buildpath($api_base_url, 2);

// Credentials
$diag_email = $mv3_config->get('DIAGNOSTIC_USER_EMAIL', 'diagnostic@test.local');
$diag_password = $mv3_config->get('DIAGNOSTIC_USER_PASSWORD', 'DiagTest2026!');

// ========================================
// FONCTIONS DE DIAGNOSTIC
// ========================================

/**
 * Teste le login avec détails complets
 */
function deep_test_login($api_url, $email, $password, $db) {
    global $error_logger;

    $result = [
        'success' => false,
        'http_code' => null,
        'error' => null,
        'details' => [],
        'sql_checks' => [],
        'curl_info' => [],
        'response_raw' => null,
        'response_json' => null
    ];

    // 1. Vérifier que l'utilisateur existe en BDD
    $sql = "SELECT rowid, email, password_hash, firstname, lastname, role, is_active, login_attempts, locked_until, created_at";
    $sql .= " FROM ".MAIN_DB_PREFIX."mv3_mobile_users";
    $sql .= " WHERE email = '".$db->escape($email)."'";

    $resql = $db->query($sql);
    if ($resql) {
        if ($db->num_rows($resql) > 0) {
            $user_obj = $db->fetch_object($resql);
            $result['sql_checks']['user_exists'] = true;
            $result['sql_checks']['user_id'] = (int)$user_obj->rowid;
            $result['sql_checks']['user_email'] = $user_obj->email;
            $result['sql_checks']['user_name'] = $user_obj->firstname.' '.$user_obj->lastname;
            $result['sql_checks']['user_active'] = (int)$user_obj->is_active;
            $result['sql_checks']['user_role'] = $user_obj->role;
            $result['sql_checks']['login_attempts'] = (int)$user_obj->login_attempts;
            $result['sql_checks']['locked_until'] = $user_obj->locked_until;
            $result['sql_checks']['created_at'] = $user_obj->created_at;
            $result['sql_checks']['password_hash_length'] = strlen($user_obj->password_hash);

            // Vérifier si le compte est verrouillé
            if ($user_obj->locked_until && strtotime($user_obj->locked_until) > time()) {
                $result['details'][] = '⚠️ COMPTE VERROUILLÉ jusqu\'à '.$user_obj->locked_until;
            }

            // Vérifier si le compte est actif
            if ($user_obj->is_active == 0) {
                $result['details'][] = '⚠️ COMPTE DÉSACTIVÉ (is_active = 0)';
            }

            // Vérifier le format du hash
            if (substr($user_obj->password_hash, 0, 4) === '$2y$') {
                $result['sql_checks']['password_hash_format'] = 'bcrypt (OK)';
            } else {
                $result['sql_checks']['password_hash_format'] = 'INVALID - Not bcrypt!';
            }

            // Tester le password localement
            if (password_verify($password, $user_obj->password_hash)) {
                $result['sql_checks']['password_match_local'] = true;
            } else {
                $result['sql_checks']['password_match_local'] = false;
                $result['details'][] = '❌ Le mot de passe ne correspond PAS au hash en BDD';
            }
        } else {
            $result['sql_checks']['user_exists'] = false;
            $result['details'][] = '❌ L\'utilisateur '.$email.' N\'EXISTE PAS dans llx_mv3_mobile_users';
        }
    } else {
        $result['sql_checks']['db_error'] = $db->lasterror();
        $result['details'][] = '❌ Erreur SQL: '.$db->lasterror();
    }

    // 2. Vérifier la table des sessions
    $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $result['sql_checks']['sessions_table_exists'] = true;
        $result['sql_checks']['sessions_count'] = $obj->count;
    } else {
        $result['sql_checks']['sessions_table_exists'] = false;
        $result['details'][] = '❌ Table llx_mv3_mobile_sessions n\'existe pas: '.$db->lasterror();
    }

    // 3. Appel API réel avec capture complète
    try {
        $ch = curl_init($api_url.'auth/login.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email, 'password' => $password]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);

        $result['http_code'] = $http_code;
        $result['response_raw'] = $response;
        $result['curl_info'] = [
            'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            'http_code' => $http_code,
            'total_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
            'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
            'curl_errno' => $curl_errno,
            'curl_error' => $curl_error
        ];

        curl_close($ch);

        // Parser la réponse
        if ($response) {
            $json = json_decode($response, true);
            $result['response_json'] = $json;

            if ($json && isset($json['success']) && $json['success']) {
                $result['success'] = true;
                $result['details'][] = '✅ Login API réussi';
            } else {
                $result['error'] = $json['error'] ?? $json['message'] ?? 'Login failed';
                $result['details'][] = '❌ API Error: '.$result['error'];

                if (isset($json['debug_id'])) {
                    $result['details'][] = '🔍 Debug ID: '.$json['debug_id'];

                    // Récupérer les logs d'erreur
                    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_error_log";
                    $sql .= " WHERE debug_id = '".$db->escape($json['debug_id'])."'";
                    $resql = $db->query($sql);
                    if ($resql && $db->num_rows($resql) > 0) {
                        $log = $db->fetch_object($resql);
                        $result['error_log'] = [
                            'error_type' => $log->error_type,
                            'error_message' => $log->error_message,
                            'sql_error' => $log->sql_error,
                            'endpoint' => $log->endpoint,
                            'file_path' => $log->file_path,
                            'line_number' => $log->line_number,
                            'stack_trace' => $log->stack_trace
                        ];
                        $result['details'][] = '📄 Fichier: '.$log->file_path.':'.$log->line_number;
                        $result['details'][] = '❌ Erreur: '.$log->error_message;
                        if ($log->sql_error) {
                            $result['details'][] = '💾 SQL Error: '.$log->sql_error;
                        }
                    }
                }
            }
        } else {
            $result['error'] = 'No response from API';
            $result['details'][] = '❌ Pas de réponse du serveur';
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        $result['details'][] = '❌ Exception: '.$e->getMessage();
    }

    return $result;
}

/**
 * Teste un endpoint API avec détails
 */
function deep_test_endpoint($url, $method = 'GET', $auth_token = null, $db = null) {
    $result = [
        'url' => $url,
        'method' => $method,
        'success' => false,
        'http_code' => null,
        'error' => null,
        'details' => [],
        'response_raw' => null,
        'response_json' => null,
        'curl_info' => []
    ];

    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $headers = [];
        if ($auth_token) {
            $headers[] = 'X-Auth-Token: '.$auth_token;
            $headers[] = 'Authorization: Bearer '.$auth_token;
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $result['http_code'] = $http_code;
        $result['response_raw'] = $response;
        $result['curl_info'] = [
            'url' => curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            'http_code' => $http_code,
            'total_time' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
            'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE)
        ];

        curl_close($ch);

        if ($response) {
            $json = json_decode($response, true);
            $result['response_json'] = $json;

            if ($http_code >= 200 && $http_code < 300) {
                $result['success'] = true;
                $result['details'][] = '✅ HTTP '.$http_code;
            } else {
                $result['error'] = 'HTTP '.$http_code;
                $result['details'][] = '❌ HTTP '.$http_code;

                if ($json && isset($json['error'])) {
                    $result['details'][] = 'Error: '.$json['error'];
                }
                if ($json && isset($json['debug_id'])) {
                    $result['details'][] = 'Debug ID: '.$json['debug_id'];

                    // Récupérer le log
                    if ($db) {
                        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_error_log";
                        $sql .= " WHERE debug_id = '".$db->escape($json['debug_id'])."'";
                        $resql = $db->query($sql);
                        if ($resql && $db->num_rows($resql) > 0) {
                            $log = $db->fetch_object($resql);
                            $result['error_log'] = [
                                'file_path' => $log->file_path,
                                'line_number' => $log->line_number,
                                'error_message' => $log->error_message,
                                'sql_error' => $log->sql_error
                            ];
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        $result['details'][] = '❌ Exception: '.$e->getMessage();
    }

    return $result;
}

// ========================================
// EXÉCUTION DU DIAGNOSTIC
// ========================================

$action = GETPOST('action', 'alpha');

$results = [];

if ($action === 'run') {
    // Test 1: Login approfondi
    $results['login'] = deep_test_login($full_api_url, $diag_email, $diag_password, $db);

    // Test 2: Si login OK, tester les endpoints
    if ($results['login']['success']) {
        $token = $results['login']['response_json']['data']['token'] ?? $results['login']['response_json']['token'] ?? null;

        if ($token) {
            $results['me'] = deep_test_endpoint($full_api_url.'me.php', 'GET', $token, $db);
            $results['planning'] = deep_test_endpoint($full_api_url.'planning.php', 'GET', $token, $db);
            $results['rapports'] = deep_test_endpoint($full_api_url.'rapports.php', 'GET', $token, $db);
        }
    }

    // Test 3: Récupérer les dernières erreurs
    $sql = "SELECT debug_id, error_type, error_message, sql_error, endpoint, file_path, line_number, date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."mv3_error_log";
    $sql .= " WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $sql .= " ORDER BY date_creation DESC";
    $sql .= " LIMIT 20";

    $resql = $db->query($sql);
    $results['recent_errors'] = [];
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $results['recent_errors'][] = [
                'debug_id' => $obj->debug_id,
                'error_type' => $obj->error_type,
                'error_message' => $obj->error_message,
                'sql_error' => $obj->sql_error,
                'endpoint' => $obj->endpoint,
                'file_path' => $obj->file_path,
                'line_number' => $obj->line_number,
                'date' => $obj->date_creation
            ];
        }
    }
}

// ========================================
// AFFICHAGE
// ========================================

llxHeader('', 'Diagnostic approfondi');

print '<div class="fiche">';
print '<div class="titre">🔬 Diagnostic Approfondi - Analyse des erreurs</div>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="run">';
print '<p>';
print '<button type="submit" class="butAction">Lancer le diagnostic approfondi</button>';
print '</p>';
print '</form>';

if ($action === 'run' && !empty($results)) {
    // ===== TEST LOGIN =====
    print '<div style="margin-top: 30px;">';
    print '<h2>🔐 Test Login : '.$diag_email.'</h2>';

    $login = $results['login'];

    // Status
    if ($login['success']) {
        print '<div class="ok">✅ Login RÉUSSI</div>';
    } else {
        print '<div class="error">❌ Login ÉCHOUÉ</div>';
    }

    // Détails SQL
    print '<h3>📊 Vérifications Base de Données</h3>';
    print '<table class="border centpercent">';
    foreach ($login['sql_checks'] as $key => $value) {
        $display_value = is_bool($value) ? ($value ? '✅ Oui' : '❌ Non') : $value;
        $row_class = (is_bool($value) && !$value) ? 'style="background: #ffebee;"' : '';
        print '<tr '.$row_class.'><td width="40%">'.htmlspecialchars($key).'</td><td><b>'.$display_value.'</b></td></tr>';
    }
    print '</table>';

    // Détails API
    print '<h3>🌐 Appel API</h3>';
    print '<table class="border centpercent">';
    print '<tr><td width="40%">URL</td><td>'.$login['curl_info']['url'].'</td></tr>';
    print '<tr><td>HTTP Code</td><td><b>'.$login['http_code'].'</b></td></tr>';
    print '<tr><td>Content-Type</td><td>'.$login['curl_info']['content_type'].'</td></tr>';
    print '<tr><td>Response Time</td><td>'.$login['curl_info']['total_time'].' s</td></tr>';
    if ($login['curl_info']['curl_errno']) {
        print '<tr style="background: #ffebee;"><td>cURL Error</td><td><b>'.$login['curl_info']['curl_error'].'</b></td></tr>';
    }
    print '</table>';

    // Réponse JSON
    if ($login['response_json']) {
        print '<h3>📄 Réponse API</h3>';
        print '<pre style="background: #f5f5f5; padding: 15px; overflow: auto; max-height: 300px;">';
        print json_encode($login['response_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        print '</pre>';
    }

    // Log d'erreur détaillé
    if (isset($login['error_log'])) {
        print '<h3>🐛 Log d\'erreur détaillé</h3>';
        print '<table class="border centpercent">';
        print '<tr><td width="40%">Fichier</td><td><b>'.$login['error_log']['file_path'].':'.$login['error_log']['line_number'].'</b></td></tr>';
        print '<tr><td>Type d\'erreur</td><td>'.$login['error_log']['error_type'].'</td></tr>';
        print '<tr><td>Message</td><td><b style="color: #d32f2f;">'.$login['error_log']['error_message'].'</b></td></tr>';
        if ($login['error_log']['sql_error']) {
            print '<tr style="background: #ffebee;"><td>Erreur SQL</td><td><b>'.$login['error_log']['sql_error'].'</b></td></tr>';
        }
        if ($login['error_log']['stack_trace']) {
            print '<tr><td>Stack Trace</td><td><pre style="margin: 0; white-space: pre-wrap;">'.htmlspecialchars($login['error_log']['stack_trace']).'</pre></td></tr>';
        }
        print '</table>';
    }

    // Détails
    print '<h3>💡 Détails</h3>';
    print '<ul>';
    foreach ($login['details'] as $detail) {
        print '<li>'.$detail.'</li>';
    }
    print '</ul>';

    print '</div>';

    // ===== TESTS ENDPOINTS =====
    if (isset($results['me']) || isset($results['planning']) || isset($results['rapports'])) {
        print '<div style="margin-top: 30px;">';
        print '<h2>🌐 Tests Endpoints API</h2>';

        foreach (['me', 'planning', 'rapports'] as $endpoint_name) {
            if (isset($results[$endpoint_name])) {
                $endpoint = $results[$endpoint_name];

                print '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">';
                print '<h3>'.strtoupper($endpoint_name).' - '.$endpoint['url'].'</h3>';

                if ($endpoint['success']) {
                    print '<div class="ok">✅ HTTP '.$endpoint['http_code'].'</div>';
                } else {
                    print '<div class="error">❌ HTTP '.$endpoint['http_code'].'</div>';

                    if (isset($endpoint['error_log'])) {
                        print '<p><b>Erreur:</b> '.$endpoint['error_log']['error_message'].'</p>';
                        print '<p><b>Fichier:</b> '.$endpoint['error_log']['file_path'].':'.$endpoint['error_log']['line_number'].'</p>';
                        if ($endpoint['error_log']['sql_error']) {
                            print '<p><b>SQL Error:</b> '.$endpoint['error_log']['sql_error'].'</p>';
                        }
                    }
                }

                print '</div>';
            }
        }

        print '</div>';
    }

    // ===== ERREURS RÉCENTES =====
    if (!empty($results['recent_errors'])) {
        print '<div style="margin-top: 30px;">';
        print '<h2>📋 Dernières erreurs (1h)</h2>';

        print '<table class="border centpercent">';
        print '<tr class="liste_titre">';
        print '<th>Date</th>';
        print '<th>Endpoint</th>';
        print '<th>Type</th>';
        print '<th>Message</th>';
        print '<th>Fichier</th>';
        print '<th>Debug ID</th>';
        print '</tr>';

        foreach ($results['recent_errors'] as $error) {
            print '<tr>';
            print '<td>'.dol_print_date(strtotime($error['date']), 'dayhour').'</td>';
            print '<td>'.htmlspecialchars($error['endpoint']).'</td>';
            print '<td>'.htmlspecialchars($error['error_type']).'</td>';
            print '<td><b>'.htmlspecialchars($error['error_message']).'</b>';
            if ($error['sql_error']) {
                print '<br><small style="color: #d32f2f;">SQL: '.htmlspecialchars($error['sql_error']).'</small>';
            }
            print '</td>';
            print '<td><small>'.htmlspecialchars($error['file_path']).':'.$error['line_number'].'</small></td>';
            print '<td><code>'.htmlspecialchars($error['debug_id']).'</code></td>';
            print '</tr>';
        }

        print '</table>';
        print '</div>';
    }
}

// Instructions
print '<div style="margin-top: 30px; padding: 15px; background: #f8f8f8; border-left: 4px solid #2196F3;">';
print '<h3>ℹ️ À propos</h3>';
print '<p>Ce diagnostic approfondi affiche:</p>';
print '<ul>';
print '<li>✅ Vérifications BDD (utilisateur existe, password match, tables présentes)</li>';
print '<li>🌐 Détails complets des appels API (HTTP codes, réponses, erreurs cURL)</li>';
print '<li>🐛 Logs d\'erreurs détaillés avec fichiers, lignes, stack traces</li>';
print '<li>💾 Erreurs SQL complètes</li>';
print '<li>📋 Historique des erreurs récentes</li>';
print '</ul>';
print '<p><b>Credentials testés:</b> '.$diag_email.' (mot de passe configuré dans DIAGNOSTIC_USER_PASSWORD)</p>';
print '</div>';

print '</div>';

llxFooter();
$db->close();
