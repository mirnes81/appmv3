<?php
/**
 * DEBUG AUTH - Endpoint de debug pour tracer l'authentification
 *
 * NE PAS UTILISER EN PRODUCTION
 */

// Mode debug complet
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/_bootstrap.php';

// Permettre l'activation du debug via paramètre GET
if (isset($_GET['enable_logs'])) {
    DebugLogger::enable();
    json_ok(['success' => true, 'message' => 'Debug logs enabled. Logs will be written to: ' . DebugLogger::getLogFile()]);
}

if (isset($_GET['disable_logs'])) {
    DebugLogger::disable();
    json_ok(['success' => true, 'message' => 'Debug logs disabled.']);
}

if (isset($_GET['clear_logs'])) {
    DebugLogger::clearLog();
    json_ok(['success' => true, 'message' => 'Debug logs cleared.']);
}

if (isset($_GET['view_logs'])) {
    $log_file = DebugLogger::getLogFile();
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        header('Content-Type: text/plain; charset=utf-8');
        echo $logs;
    } else {
        echo "No log file found.";
    }
    exit;
}

require_method('GET');

// Récupérer le token
$token = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        $token = $matches[1];
    }
}

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'request' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'headers' => [
            'Authorization' => isset($_SERVER['HTTP_AUTHORIZATION']) ? 'Present (Bearer...)' : 'Missing',
            'Content-Type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
        ],
        'token_present' => $token ? 'YES (first 20 chars: ' . substr($token, 0, 20) . '...)' : 'NO',
        'token_length' => $token ? strlen($token) : 0,
    ],
    'session' => [
        'php_session_id' => session_id() ?: 'No session',
        'dol_login' => $_SESSION['dol_login'] ?? 'Not set',
        'dolibarr_user_id' => !empty($user->id) ? $user->id : 'No Dolibarr user',
    ],
];

// Tester l'authentification
try {
    $auth = require_auth(false); // Ne pas faire d'erreur si non auth

    if ($auth) {
        $debug_info['auth_result'] = [
            'status' => 'AUTHENTICATED',
            'mode' => $auth['mode'],
            'user_id' => $auth['user_id'] ?? 'NULL',
            'mobile_user_id' => $auth['mobile_user_id'] ?? 'N/A',
            'email' => $auth['email'],
            'name' => $auth['name'],
            'role' => $auth['role'] ?? 'N/A',
            'is_unlinked' => $auth['is_unlinked'] ?? false,
            'rights' => $auth['rights'] ?? [],
        ];

        // Vérifier la session dans la DB
        if ($token) {
            $sql = "SELECT s.rowid, s.user_id, s.session_token, s.expires_at, s.last_activity,
                           u.rowid as mobile_user_id, u.email, u.firstname, u.lastname,
                           u.role, u.is_active, u.dolibarr_user_id,
                           d.login as dolibarr_login, d.statut as dolibarr_statut
                    FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions s
                    INNER JOIN ".MAIN_DB_PREFIX."mv3_mobile_users u ON u.rowid = s.user_id
                    LEFT JOIN ".MAIN_DB_PREFIX."user d ON d.rowid = u.dolibarr_user_id
                    WHERE s.session_token = '".$db->escape($token)."'";

            $resql = $db->query($sql);

            if ($resql && $db->num_rows($resql) > 0) {
                $session_row = $db->fetch_object($resql);

                $debug_info['database_session'] = [
                    'found' => 'YES',
                    'session_id' => $session_row->rowid,
                    'user_id' => $session_row->user_id,
                    'expires_at' => $session_row->expires_at,
                    'is_expired' => strtotime($session_row->expires_at) < time() ? 'YES - EXPIRED!' : 'No',
                    'last_activity' => $session_row->last_activity,
                    'mobile_user' => [
                        'id' => $session_row->mobile_user_id,
                        'email' => $session_row->email,
                        'name' => $session_row->firstname . ' ' . $session_row->lastname,
                        'role' => $session_row->role,
                        'is_active' => $session_row->is_active,
                        'dolibarr_user_id' => $session_row->dolibarr_user_id ?: 'NULL/0 - NOT LINKED!',
                    ],
                ];

                if ($session_row->dolibarr_user_id) {
                    $debug_info['database_session']['dolibarr_user'] = [
                        'login' => $session_row->dolibarr_login,
                        'statut' => $session_row->dolibarr_statut,
                    ];
                } else {
                    $debug_info['database_session']['dolibarr_user'] = 'NOT LINKED';
                }
            } else {
                $debug_info['database_session'] = [
                    'found' => 'NO - Token not found in database!',
                    'sql_executed' => $sql,
                ];
            }
        }
    } else {
        $debug_info['auth_result'] = [
            'status' => 'NOT AUTHENTICATED',
            'reason' => 'require_auth returned null',
        ];
    }
} catch (Exception $e) {
    $debug_info['auth_result'] = [
        'status' => 'ERROR',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ];
}

// Test de connexion DB
$debug_info['database'] = [
    'connected' => $db && $db->connected ? 'YES' : 'NO',
    'type' => $db->type ?? 'Unknown',
    'db_name' => $conf->db->name ?? 'Unknown',
];

// Lister toutes les sessions actives
$sql = "SELECT s.rowid, s.user_id, s.expires_at, s.last_activity,
               u.email, u.firstname, u.lastname, u.dolibarr_user_id
        FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions s
        INNER JOIN ".MAIN_DB_PREFIX."mv3_mobile_users u ON u.rowid = s.user_id
        WHERE s.expires_at > NOW()
        ORDER BY s.last_activity DESC
        LIMIT 10";

$resql = $db->query($sql);
$active_sessions = [];

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $active_sessions[] = [
            'session_id' => $obj->rowid,
            'user_id' => $obj->user_id,
            'email' => $obj->email,
            'name' => $obj->firstname . ' ' . $obj->lastname,
            'dolibarr_user_id' => $obj->dolibarr_user_id ?: 'NULL',
            'expires_at' => $obj->expires_at,
            'last_activity' => $obj->last_activity,
        ];
    }
}

$debug_info['active_sessions'] = [
    'count' => count($active_sessions),
    'sessions' => $active_sessions,
];

// Informations sur les utilisateurs mobiles
$sql = "SELECT rowid, email, firstname, lastname, role, is_active, dolibarr_user_id
        FROM ".MAIN_DB_PREFIX."mv3_mobile_users
        ORDER BY created_at DESC
        LIMIT 10";

$resql = $db->query($sql);
$mobile_users = [];

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $mobile_users[] = [
            'id' => $obj->rowid,
            'email' => $obj->email,
            'name' => $obj->firstname . ' ' . $obj->lastname,
            'role' => $obj->role,
            'is_active' => $obj->is_active,
            'dolibarr_user_id' => $obj->dolibarr_user_id ?: 'NULL/0 - NOT LINKED',
        ];
    }
}

$debug_info['mobile_users'] = [
    'count' => count($mobile_users),
    'users' => $mobile_users,
];

// Retourner toutes les infos
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'debug' => $debug_info,
    'warning' => '⚠️ Ce endpoint expose des informations sensibles. NE PAS utiliser en production!',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
