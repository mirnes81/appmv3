<?php
/**
 * Bootstrap API v1 - MV3 PRO Portail
 *
 * Charge l'environnement Dolibarr et fournit des helpers pour les endpoints API
 *
 * Supporte 3 modes d'authentification:
 * - Mode A: Session Dolibarr (admin/chef)
 * - Mode B: Token mobile (Bearer)
 * - Mode C: Token API ancien (X-Auth-Token)
 */

// Protection contre les chargements multiples
if (defined('MV3_BOOTSTRAP_V1_LOADED')) {
    return;
}
define('MV3_BOOTSTRAP_V1_LOADED', true);

// Désactiver les erreurs PHP en mode production (à adapter selon besoin)
if (!defined('SHOW_PHP_ERRORS')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

// Headers JSON + UTF-8
header('Content-Type: application/json; charset=utf-8');

// Gestionnaire d'erreurs pour retourner du JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $errstr,
        'code' => 'SERVER_ERROR',
        'debug_info' => [
            'file' => basename($errfile),
            'line' => $errline,
            'errno' => $errno
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
});

// Gestionnaire d'erreurs fatales pour retourner du JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'error' => 'Erreur fatale: ' . $error['message'],
            'code' => 'FATAL_ERROR',
            'debug_info' => [
                'file' => basename($error['file']),
                'line' => $error['line'],
                'type' => $error['type']
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
});

// Gestionnaire d'exceptions pour retourner du JSON
set_exception_handler(function($exception) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $exception->getMessage(),
        'code' => 'EXCEPTION',
        'debug_info' => [
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
});

// CORS
require_once __DIR__ . '/../cors_config.php';
setCorsHeaders();
handleCorsPreflightRequest();

// --- Dolibarr bootstrap for API (no CSRF, no menu) ---
// Note: NOLOGIN permet l'auth manuelle via require_auth()
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);

// Charger Dolibarr
$res = 0;
if (!$res && file_exists(__DIR__ . "/../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../main.inc.php";
}
if (!$res && file_exists(__DIR__ . "/../../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../../main.inc.php";
}

if (!$res) {
    json_error('Impossible de charger Dolibarr', 'DOLIBARR_LOAD_ERROR', 500);
}

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

// Charger le système de debug
require_once __DIR__ . '/debug_log.php';

// Variables globales disponibles
global $db, $conf, $user, $langs;

/**
 * Helper pour logger des messages de debug
 *
 * @param string $message Message à logger
 * @param array $data Données supplémentaires (optionnel)
 * @return void
 */
if (!function_exists('log_debug')) {
    function log_debug($message, $data = null) {
        DebugLogger::log($message, $data);
    }
}

/**
 * Helper pour logger des erreurs
 *
 * @param string $code Code d'erreur
 * @param string $message Message d'erreur
 * @param array $extra_data Données supplémentaires
 * @param string|null $sql_error Erreur SQL éventuelle
 * @return void
 */
if (!function_exists('log_error')) {
    function log_error($code, $message, $extra_data = [], $sql_error = null) {
    try {
        $error_data = [
            'code' => $code,
            'message' => $message,
            'extra' => $extra_data,
        ];

        if ($sql_error) {
            $error_data['sql_error'] = $sql_error;
        }

        DebugLogger::log('[ERROR] ' . $message, $error_data);
    } catch (Exception $e) {
        // Fallback sur error_log natif si DebugLogger échoue
        error_log("[MV3PRO ERROR] $code: $message | " . json_encode($extra_data));
        if ($sql_error) {
            error_log("[MV3PRO SQL ERROR] $sql_error");
        }
    }
    }
}

/**
 * Retourne une réponse JSON de succès
 *
 * @param mixed $data Données à retourner
 * @param int $code Code HTTP (défaut: 200)
 * @return void
 */
if (!function_exists('json_ok')) {
    function json_ok($data, $code = 200) {
        http_response_code($code);

        if (is_array($data) && !isset($data['success'])) {
            $data = ['success' => true] + $data;
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

/**
 * Retourne une réponse JSON d'erreur
 *
 * @param string $message Message d'erreur
 * @param string $code Code d'erreur
 * @param int $http_code Code HTTP (défaut: 400)
 * @param array $extra_data Données supplémentaires (reason, hint, debug, etc.)
 * @return void
 */
if (!function_exists('json_error')) {
    function json_error($message, $code = 'ERROR', $http_code = 400, $extra_data = []) {
        global $db;

        http_response_code($http_code);

        $response = [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];

        // Générer debug_id unique
        $debug_id = 'ERR_'.strtoupper(substr(md5(microtime(true).mt_rand()), 0, 12));
        $response['debug_id'] = $debug_id;

        // Ajouter les données supplémentaires
        if (!empty($extra_data)) {
            foreach ($extra_data as $key => $value) {
                $response[$key] = $value;
            }
        }

        // Ajouter debug info si disponible
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $backtrace[0] ?? null;

        if ($caller) {
            $response['debug'] = [
                'file' => basename($caller['file'] ?? 'unknown'),
                'line' => $caller['line'] ?? 0
            ];
        }

        // Ajouter SQL error si disponible
        if ($db && method_exists($db, 'lasterror')) {
            $sql_error = $db->lasterror();
            if (!empty($sql_error)) {
                $response['sql_error'] = $sql_error;
            }
        }

        // Log l'erreur (ne doit jamais casser la réponse)
        try {
            log_error(
                $code,
                $message,
                array_merge(['debug_id' => $debug_id], $extra_data),
                $db ? $db->lasterror() : null
            );
        } catch (Exception $e) {
            // Fallback si le logging échoue - on log avec error_log natif
            error_log("[MV3PRO CRITICAL] Failed to log error: " . $e->getMessage());
            error_log("[MV3PRO ERROR] Original error: $code - $message");
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

/**
 * Vérifie que la méthode HTTP est correcte
 *
 * @param string|array $methods Méthode(s) autorisée(s) (ex: 'GET' ou ['GET', 'POST'])
 * @return void
 */
if (!function_exists('require_method')) {
    function require_method($methods) {
        $methods = (array)$methods;
        $current = $_SERVER['REQUEST_METHOD'];

        if (!in_array($current, $methods)) {
            json_error(
                'Méthode ' . $current . ' non autorisée. Utiliser: ' . implode(', ', $methods),
                'METHOD_NOT_ALLOWED',
                405
            );
        }
    }
}

/**
 * Récupère un paramètre de manière sécurisée
 *
 * @param string $name Nom du paramètre
 * @param string $default Valeur par défaut
 * @param string $method 'GET' ou 'POST' ou 'ANY'
 * @return mixed
 */
if (!function_exists('get_param')) {
    function get_param($name, $default = '', $method = 'ANY') {
        global $db;

        $value = null;

        if ($method === 'GET' || $method === 'ANY') {
            $value = $_GET[$name] ?? null;
        }

        if (($method === 'POST' || $method === 'ANY') && $value === null) {
            $value = $_POST[$name] ?? null;
        }

        if ($value === null) {
            return $default;
        }

        // Protection basique
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value;
    }
}

/**
 * Récupère le body JSON de la requête
 *
 * @param bool $required Si true, erreur 400 si pas de JSON valide
 * @return array|null
 */
if (!function_exists('get_json_body')) {
    function get_json_body($required = false) {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if ($required && (!$data || json_last_error() !== JSON_ERROR_NONE)) {
            json_error('Body JSON invalide ou manquant', 'INVALID_JSON', 400);
        }

        return $data ?: [];
    }
}

/**
 * Authentification unifiée
 * Supporte 3 modes: Session Dolibarr, Token Mobile, Token API
 *
 * @param bool $required Si true, erreur 401 si non authentifié
 * @return array|null Informations utilisateur ou null
 */
if (!function_exists('require_auth')) {
    function require_auth($required = true) {
    global $db, $conf, $user;

    $is_debug = isset($_SERVER['HTTP_X_MV3_DEBUG']) && $_SERVER['HTTP_X_MV3_DEBUG'] === '1';

    if ($is_debug) {
        error_log('[MV3 API] ========== AUTH START ==========');
        error_log('[MV3 API] path=' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
        error_log('[MV3 API] method=' . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
        error_log('[MV3 API] auth_header_present=' . (isset($_SERVER['HTTP_AUTHORIZATION']) ? '1' : '0'));
        error_log('[MV3 API] x_auth_token_present=' . (isset($_SERVER['HTTP_X_AUTH_TOKEN']) ? '1' : '0'));
    }

    DebugLogger::log('require_auth() called', [
        'required' => $required,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'request_method' => $_SERVER['REQUEST_METHOD'],
    ]);

    $auth_result = null;
    $auth_mode = null;

    // MODE A: Session Dolibarr (priorité la plus haute)
    if (!empty($user->id) && isset($_SESSION['dol_login'])) {
        DebugLogger::log('MODE A: Dolibarr Session detected', [
            'user_id' => $user->id,
            'login' => $user->login,
        ]);
        $auth_mode = 'dolibarr_session';
        $auth_result = [
            'mode' => $auth_mode,
            'user_id' => $user->id,
            'login' => $user->login,
            'name' => $user->getFullName($langs),
            'email' => $user->email,
            'dolibarr_user' => $user,
            'rights' => [
                'read' => !empty($user->rights->mv3pro_portail->read),
                'write' => !empty($user->rights->mv3pro_portail->write),
                'validate' => !empty($user->rights->mv3pro_portail->validate),
                'worker' => !empty($user->rights->mv3pro_portail->worker),
            ]
        ];
    }

    // MODE B: Token Mobile (X-Auth-Token or Bearer)
    if (!$auth_result) {
        DebugLogger::log('MODE B: Checking Mobile Token');

        $bearer = null;

        // PRIORITY 1: X-Auth-Token (fonctionne toujours avec NGINX)
        if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
            $bearer = trim($_SERVER['HTTP_X_AUTH_TOKEN']);

            if ($is_debug) {
                error_log('[MV3 API] token_source=X-Auth-Token');
                error_log('[MV3 API] x_auth_token_present=1');
            }

            DebugLogger::log('Token extracted from X-Auth-Token', [
                'token_length' => strlen($bearer),
                'token_preview' => substr($bearer, 0, 20) . '...',
            ]);
        }
        // PRIORITY 2: Authorization header (fallback, peut être bloqué par NGINX)
        elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                $bearer = trim($matches[1]);
            } else {
                $bearer = trim($auth);
            }

            if ($is_debug) {
                error_log('[MV3 API] token_source=Authorization');
                error_log('[MV3 API] authorization_header_present=1');
            }

            DebugLogger::log('Token extracted from Authorization header', [
                'token_length' => strlen($bearer),
                'token_preview' => substr($bearer, 0, 20) . '...',
            ]);
        }

        if ($bearer) {
            $token_mask = strlen($bearer) > 10 ? substr($bearer, 0, 6) . '....' . substr($bearer, -4) : 'short';

            if ($is_debug) {
                error_log('[MV3 API] token_extracted=1');
                error_log('[MV3 API] token_mask=' . $token_mask);
                error_log('[MV3 API] token_length=' . strlen($bearer));
            }
        } else {
            if ($is_debug) {
                error_log('[MV3 API] token_not_found=1');
                error_log('[MV3 API] x_auth_token=' . (!empty($_SERVER['HTTP_X_AUTH_TOKEN']) ? 'PRESENT' : 'NONE'));
                error_log('[MV3 API] authorization=' . (!empty($_SERVER['HTTP_AUTHORIZATION']) ? 'PRESENT' : 'NONE'));
            }
            DebugLogger::log('No token found in X-Auth-Token or Authorization header');
        }

        if ($bearer) {
            $sql = "SELECT s.rowid, s.user_id, s.expires_at,
                           u.rowid as mobile_user_id, u.email, u.firstname, u.lastname,
                           u.phone, u.role, u.is_active, u.dolibarr_user_id
                    FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions s
                    INNER JOIN ".MAIN_DB_PREFIX."mv3_mobile_users u ON u.rowid = s.user_id
                    WHERE s.session_token = '".$db->escape($bearer)."'
                    AND s.expires_at > NOW()
                    AND u.is_active = 1";

            DebugLogger::log('Executing SQL query for mobile session', ['sql' => $sql]);

            $resql = $db->query($sql);

            if ($resql && $db->num_rows($resql) > 0) {
                $session = $db->fetch_object($resql);
                $db->free($resql);

                if ($is_debug) {
                    error_log('[MV3 API] session_found=1');
                    error_log('[MV3 API] user_rowid=' . $session->mobile_user_id);
                    error_log('[MV3 API] user_email=' . $session->email);
                    error_log('[MV3 API] dolibarr_user_id=' . ($session->dolibarr_user_id ?: '0'));
                }

                DebugLogger::log('Mobile session found in DB', [
                    'mobile_user_id' => $session->mobile_user_id,
                    'email' => $session->email,
                    'dolibarr_user_id' => $session->dolibarr_user_id,
                    'expires_at' => $session->expires_at,
                ]);

                // Détecter si le compte mobile n'est pas lié à Dolibarr
                $is_unlinked = empty($session->dolibarr_user_id) || $session->dolibarr_user_id == 0;

                if ($is_debug) {
                    error_log('[MV3 API] session_expired=0');
                    error_log('[MV3 API] is_unlinked=' . ($is_unlinked ? '1' : '0'));
                }

                DebugLogger::log('Checking if account is unlinked', [
                    'dolibarr_user_id' => $session->dolibarr_user_id,
                    'is_unlinked' => $is_unlinked,
                ]);

                // Charger l'utilisateur Dolibarr lié (si existe)
                if (!$is_unlinked) {
                    DebugLogger::log('Loading Dolibarr user', ['dolibarr_user_id' => $session->dolibarr_user_id]);
                    $dol_user = new User($db);
                    $fetch_result = $dol_user->fetch($session->dolibarr_user_id);
                    if ($fetch_result > 0) {
                        $dol_user->getrights();
                        $user = $dol_user; // Mettre à jour la variable globale $user
                        DebugLogger::log('Dolibarr user loaded successfully', [
                            'user_id' => $dol_user->id,
                            'login' => $dol_user->login,
                        ]);
                    } else {
                        DebugLogger::log('Failed to load Dolibarr user', [
                            'dolibarr_user_id' => $session->dolibarr_user_id,
                            'fetch_result' => $fetch_result,
                        ]);
                    }
                } else {
                    DebugLogger::log('Account is unlinked, skipping Dolibarr user loading');
                }

                $auth_mode = 'mobile_token';
                $auth_result = [
                    'mode' => $auth_mode,
                    'mobile_user_id' => $session->mobile_user_id,
                    'user_id' => $is_unlinked ? null : $session->dolibarr_user_id,
                    'email' => $session->email,
                    'name' => trim($session->firstname . ' ' . $session->lastname),
                    'role' => $session->role,
                    'dolibarr_user' => $user ?? null,
                    'is_unlinked' => $is_unlinked, // FLAG: compte non lié
                    'rights' => [
                        'read' => true,
                        'write' => !$is_unlinked, // Pas d'écriture si non lié
                        'worker' => !$is_unlinked,
                    ]
                ];

                DebugLogger::log('Auth result created', [
                    'mode' => $auth_mode,
                    'is_unlinked' => $is_unlinked,
                    'write_permission' => !$is_unlinked,
                ]);

                // Mettre à jour last_activity
                if (!$db->query("UPDATE ".MAIN_DB_PREFIX."mv3_mobile_sessions
                           SET last_activity = NOW()
                           WHERE session_token = '".$db->escape($bearer)."'")) {
                    error_log("Failed to update last_activity: " . $db->lasterror());
                }
            } else {
                if ($resql) {
                    $db->free($resql);
                }
                if ($is_debug) {
                    error_log('[MV3 API] session_found=0');
                    error_log('[MV3 API] session_expired_or_not_found=1');
                    if ($db->lasterror()) {
                        error_log('[MV3 API] db_error=' . $db->lasterror());
                    }
                }

                DebugLogger::log('Mobile session NOT found in DB or expired', [
                    'num_rows' => $resql ? $db->num_rows($resql) : 0,
                    'db_error' => $db->lasterror(),
                ]);
            }
        } else {
            if ($is_debug) {
                error_log('[MV3 API] bearer_token_not_found=1');
            }
            DebugLogger::log('No bearer token found, skipping MODE B');
        }
    }

    // MODE C: Token API Ancien (X-Auth-Token)
    if (!$auth_result) {
        $api_token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';

        if ($api_token) {
            $decoded = json_decode(base64_decode($api_token), true);

            if ($decoded && isset($decoded['user_id']) && isset($decoded['api_key'])) {
                // Vérifier expiration
                if (isset($decoded['expires_at']) && $decoded['expires_at'] < time()) {
                    if ($required) {
                        json_error('Token expiré', 'TOKEN_EXPIRED', 401);
                    }
                    return null;
                }

                $sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.email, u.statut
                        FROM ".MAIN_DB_PREFIX."user as u
                        WHERE u.rowid = ".(int)$decoded['user_id']."
                        AND u.api_key = '".$db->escape($decoded['api_key'])."'
                        AND u.statut = 1";

                $resql = $db->query($sql);

                if ($resql && $db->num_rows($resql) > 0) {
                    $user_obj = $db->fetch_object($resql);
                    $db->free($resql);

                    // Charger droits
                    $dol_user = new User($db);
                    if ($dol_user->fetch($user_obj->rowid) > 0) {
                        $dol_user->getrights();
                        $user = $dol_user;
                    }

                    $auth_mode = 'api_token';
                    $auth_result = [
                        'mode' => $auth_mode,
                        'user_id' => $user_obj->rowid,
                        'login' => $user_obj->login,
                        'name' => trim($user_obj->firstname . ' ' . $user_obj->lastname),
                        'email' => $user_obj->email,
                        'dolibarr_user' => $user,
                        'rights' => [
                            'read' => !empty($user->rights->mv3pro_portail->read),
                            'write' => !empty($user->rights->mv3pro_portail->write),
                            'validate' => !empty($user->rights->mv3pro_portail->validate),
                            'worker' => !empty($user->rights->mv3pro_portail->worker),
                        ]
                    ];
                }
            }
        }
    }

    // Si authentification requise et pas d'auth valide
    if ($required && !$auth_result) {
        if ($is_debug) {
            error_log('[MV3 API] auth_result=FAILED');
            error_log('[MV3 API] reason=NO_VALID_AUTH_FOUND');
            error_log('[MV3 API] ========== AUTH END ==========');
        }

        DebugLogger::log('Authentication FAILED - No valid auth found', [
            'required' => $required,
            'tried_modes' => ['dolibarr_session', 'mobile_token', 'api_token'],
        ]);
        json_error(
            'Authentification requise. Utilisez session Dolibarr, Bearer token ou X-Auth-Token',
            'UNAUTHORIZED',
            401
        );
    }

    if ($auth_result) {
        if ($is_debug) {
            error_log('[MV3 API] auth_result=SUCCESS');
            error_log('[MV3 API] auth_mode=' . ($auth_result['mode'] ?? 'unknown'));
            error_log('[MV3 API] user_id=' . ($auth_result['user_id'] ?? 'null'));
            error_log('[MV3 API] mobile_user_id=' . ($auth_result['mobile_user_id'] ?? 'N/A'));
            error_log('[MV3 API] is_unlinked=' . ($auth_result['is_unlinked'] ?? '0'));
            error_log('[MV3 API] ========== AUTH END ==========');
        }

        DebugLogger::log('Authentication SUCCESS', [
            'mode' => $auth_result['mode'],
            'user_id' => $auth_result['user_id'] ?? 'null',
            'mobile_user_id' => $auth_result['mobile_user_id'] ?? 'N/A',
            'is_unlinked' => $auth_result['is_unlinked'] ?? false,
        ]);
    } else {
        if ($is_debug) {
            error_log('[MV3 API] auth_result=NULL (optional auth)');
            error_log('[MV3 API] ========== AUTH END ==========');
        }
        DebugLogger::log('Authentication returned null (optional auth)');
    }

    return $auth_result;
    }
}

/**
 * Vérifie les droits utilisateur
 *
 * @param string|array $rights Droit(s) requis ('read', 'write', 'validate', 'worker')
 * @param array $auth_data Données d'authentification (retour de require_auth)
 * @return void
 */
if (!function_exists('require_rights')) {
    function require_rights($rights, $auth_data) {
        $rights = (array)$rights;

        foreach ($rights as $right) {
            if (empty($auth_data['rights'][$right])) {
                json_error(
                    'Droits insuffisants. Droit requis: ' . $right,
                    'FORBIDDEN',
                    403
                );
            }
        }
    }
}

/**
 * Vérifie le mode DEV et bloque les non-admins si activé
 *
 * @param array|null $auth_data Données d'authentification (retour de require_auth)
 * @return void
 */
if (!function_exists('check_dev_mode')) {
    function check_dev_mode($auth_data = null) {
    global $db;

    // Charger la config
    require_once DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/class/mv3_config.class.php';
    $mv3_config = new Mv3Config($db);

    // Vérifier si mode DEV actif
    if (!$mv3_config->isDevMode()) {
        return; // Mode DEV OFF = tout le monde passe
    }

    // Mode DEV actif - vérifier si admin
    $is_admin = false;

    if ($auth_data) {
        // Vérifier si l'utilisateur a les droits admin
        if (!empty($auth_data['dolibarr_user'])) {
            $is_admin = !empty($auth_data['dolibarr_user']->admin);
        } elseif (isset($auth_data['is_admin'])) {
            $is_admin = $auth_data['is_admin'];
        }
    }

    if (!$is_admin) {
        // Non-admin en mode DEV = accès refusé
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Application en maintenance',
            'message' => 'L\'application est actuellement en cours de mise à jour. Veuillez réessayer dans quelques instants.',
            'code' => 'DEV_MODE_ACTIVE',
            'dev_mode' => true
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Admin = accès autorisé même en mode DEV
    }
}

/**
 * Valide un paramètre requis
 *
 * @param mixed $value Valeur à vérifier
 * @param string $name Nom du paramètre (pour message d'erreur)
 * @return void
 */
if (!function_exists('require_param')) {
    function require_param($value, $name) {
        if ($value === null || $value === '') {
            json_error('Paramètre requis manquant: ' . $name, 'MISSING_PARAMETER', 400);
        }
    }
}

/**
 * Log une action API (optionnel, à implémenter selon besoin)
 *
 * @param string $endpoint Endpoint appelé
 * @param array $auth_data Données auth
 * @param array $extra_data Données supplémentaires
 * @return void
 */
if (!function_exists('log_api_call')) {
    function log_api_call($endpoint, $auth_data, $extra_data = []) {
        global $db;

        // TODO: Implémenter si besoin de logs API
        // Exemple: INSERT INTO llx_mv3_api_logs...
    }
}

// =============================================================================
// DATABASE COMPATIBILITY HELPERS
// Système de vérification des tables/colonnes avec cache
// =============================================================================

/**
 * Cache global pour éviter de requêter plusieurs fois la même info
 */
global $MV3_DB_SCHEMA_CACHE;
$MV3_DB_SCHEMA_CACHE = [];

/**
 * Vérifie si une table existe dans la base de données
 *
 * @param object $db Instance de base de données Dolibarr
 * @param string $table_name Nom de la table (avec ou sans préfixe)
 * @return bool
 */
if (!function_exists('mv3_table_exists')) {
    function mv3_table_exists($db, $table_name) {
    global $MV3_DB_SCHEMA_CACHE;

    // Ajouter le préfixe si nécessaire
    if (strpos($table_name, MAIN_DB_PREFIX) !== 0) {
        $table_name = MAIN_DB_PREFIX . $table_name;
    }

    $cache_key = 'table_' . $table_name;

    // Vérifier le cache
    if (isset($MV3_DB_SCHEMA_CACHE[$cache_key])) {
        return $MV3_DB_SCHEMA_CACHE[$cache_key];
    }

    // Requête pour vérifier l'existence
    $sql = "SHOW TABLES LIKE '".$db->escape($table_name)."'";
    $resql = $db->query($sql);

    $exists = ($resql && $db->num_rows($resql) > 0);

    if ($resql) {
        $db->free($resql);
    }

    // Mettre en cache
    $MV3_DB_SCHEMA_CACHE[$cache_key] = $exists;

    return $exists;
    }
}

/**
 * Vérifie si une colonne existe dans une table
 *
 * @param object $db Instance de base de données Dolibarr
 * @param string $table_name Nom de la table (avec ou sans préfixe)
 * @param string $column_name Nom de la colonne
 * @return bool
 */
if (!function_exists('mv3_column_exists')) {
    function mv3_column_exists($db, $table_name, $column_name) {
    global $MV3_DB_SCHEMA_CACHE;

    // Ajouter le préfixe si nécessaire
    if (strpos($table_name, MAIN_DB_PREFIX) !== 0) {
        $table_name = MAIN_DB_PREFIX . $table_name;
    }

    $cache_key = 'column_' . $table_name . '.' . $column_name;

    // Vérifier le cache
    if (isset($MV3_DB_SCHEMA_CACHE[$cache_key])) {
        return $MV3_DB_SCHEMA_CACHE[$cache_key];
    }

    // Requête pour vérifier l'existence
    $sql = "SHOW COLUMNS FROM ".$table_name." LIKE '".$db->escape($column_name)."'";
    $resql = $db->query($sql);

    $exists = ($resql && $db->num_rows($resql) > 0);

    if ($resql) {
        $db->free($resql);
    }

    // Mettre en cache
    $MV3_DB_SCHEMA_CACHE[$cache_key] = $exists;

    return $exists;
    }
}

/**
 * Construit un champ SQL conditionnel selon l'existence de la colonne
 * Retourne soit "table.column" soit "valeur_par_defaut AS column"
 *
 * @param object $db Instance de base de données
 * @param string $table_name Nom de la table
 * @param string $column_name Nom de la colonne
 * @param mixed $default_value Valeur par défaut si la colonne n'existe pas (NULL, '', 0, etc.)
 * @param string $alias_prefix Préfixe de table (ex: 'a' pour 'a.note_private')
 * @return string Fragment SQL à insérer dans la requête SELECT
 */
if (!function_exists('mv3_select_column')) {
    function mv3_select_column($db, $table_name, $column_name, $default_value = null, $alias_prefix = '') {
    $exists = mv3_column_exists($db, $table_name, $column_name);

    if ($exists) {
        if ($alias_prefix) {
            return $alias_prefix . '.' . $column_name;
        }
        return $column_name;
    } else {
        // Colonne n'existe pas, retourner valeur par défaut avec alias
        if ($default_value === null) {
            $value = 'NULL';
        } elseif (is_string($default_value)) {
            $value = "'" . $db->escape($default_value) . "'";
        } elseif (is_numeric($default_value)) {
            $value = $default_value;
        } else {
            $value = 'NULL';
        }

        return $value . ' AS ' . $column_name;
    }
    }
}

/**
 * Retourne un tableau vide JSON avec le bon format si la table n'existe pas
 * Et enregistre l'erreur dans les logs pour diagnostic
 *
 * @param object $db Instance de base de données
 * @param string $table_name Nom de la table à vérifier
 * @param string $endpoint_name Nom de l'endpoint pour les logs
 * @return bool true si la table existe, false si elle n'existe pas (et response envoyée)
 */
if (!function_exists('mv3_check_table_or_empty')) {
    function mv3_check_table_or_empty($db, $table_name, $endpoint_name = 'unknown') {
        if (!mv3_table_exists($db, $table_name)) {
            error_log("[MV3 $endpoint_name] Table manquante: $table_name");
            http_response_code(200);
            echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        return true;
    }
}
