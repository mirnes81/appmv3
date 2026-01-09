<?php
/**
 * API v1 - Auth - Login
 * POST /api/v1/auth/login.php
 *
 * Endpoint unifié d'authentification supportant :
 * - Utilisateurs mobiles (table llx_mv3_mobile_users)
 * - Utilisateurs Dolibarr standard (table llx_user)
 *
 * Body JSON :
 *   {"email": "user@example.com", "password": "password"}
 *   ou
 *   {"login": "username", "password": "password"}
 *
 * Retourne :
 *   {"success": true, "token": "...", "user": {...}}
 */

require_once __DIR__.'/../_bootstrap.php';

require_method('POST');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_error('Body JSON requis', 'INVALID_JSON', 400);
}

$email = $input['email'] ?? $input['login'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    json_error('Email/login et mot de passe requis', 'MISSING_CREDENTIALS', 400);
}

$email = strtolower(trim($email));

log_debug("Login attempt for: ".$email);

$token = null;
$user_data = null;
$auth_mode = null;

$table_exists = table_exists('mv3_mobile_users');

if ($table_exists) {
    log_debug("Trying mobile user authentication first");

    $sql = "SELECT u.rowid, u.email, u.firstname, u.lastname, u.password_hash, u.is_active,
                u.role, u.dolibarr_user_id, u.login_attempts, u.locked_until
            FROM ".MAIN_DB_PREFIX."mv3_mobile_users as u
            WHERE u.email = '".$db->escape($email)."'
            AND u.entity IN (".getEntity('user').")";

    $resql = $db->query($sql);

    if ($resql && $db->num_rows($resql) > 0) {
        $user = $db->fetch_object($resql);

        if (!$user->is_active) {
            log_debug("Mobile user account inactive");
            json_error('Compte désactivé. Contactez votre administrateur.', 'ACCOUNT_INACTIVE', 403, [
                'reason' => 'user_inactive',
                'email' => $email,
                'user_id' => (int)$user->rowid,
                'hint' => 'Le compte mobile est désactivé (is_active = 0)'
            ]);
        }

        if ($user->locked_until && strtotime($user->locked_until) > time()) {
            $remaining = ceil((strtotime($user->locked_until) - time()) / 60);
            log_debug("Mobile user account locked for ".$remaining." minutes");
            json_error("Compte verrouillé temporairement. Réessayez dans $remaining minute(s).", 'ACCOUNT_LOCKED', 403, [
                'reason' => 'locked',
                'email' => $email,
                'user_id' => (int)$user->rowid,
                'locked_until' => $user->locked_until,
                'remaining_minutes' => $remaining,
                'hint' => 'Le compte est verrouillé après trop de tentatives échouées'
            ]);
        }

        if (password_verify($password, $user->password_hash)) {
            log_debug("Mobile user password verified");

            $db->query("UPDATE ".MAIN_DB_PREFIX."mv3_mobile_users
                        SET login_attempts = 0, locked_until = NULL, last_login = NOW()
                        WHERE rowid = ".(int)$user->rowid);

            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + (30 * 24 * 3600));

            $sessions_table_exists = table_exists('mv3_mobile_sessions');

            if ($sessions_table_exists) {
                $sql_session = "INSERT INTO ".MAIN_DB_PREFIX."mv3_mobile_sessions
                                (user_id, session_token, device_info, ip_address, expires_at, last_activity)
                                VALUES (
                                    ".(int)$user->rowid.",
                                    '".$db->escape($token)."',
                                    '".$db->escape($_SERVER['HTTP_USER_AGENT'] ?? '')."',
                                    '".$db->escape($_SERVER['REMOTE_ADDR'])."',
                                    '".$expires_at."',
                                    NOW()
                                )";

                if (!$db->query($sql_session)) {
                    log_debug("Failed to create mobile session: " . $db->lasterror());
                    json_error('Erreur création session: ' . $db->lasterror(), 'SESSION_ERROR', 500);
                }
            } else {
                log_debug("Table mv3_mobile_sessions not found, skipping session creation");
            }

            $user_data = [
                'id' => (int)$user->rowid,
                'user_rowid' => (int)$user->rowid,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'name' => trim($user->firstname.' '.$user->lastname),
                'role' => $user->role,
                'dolibarr_user_id' => (int)$user->dolibarr_user_id,
                'auth_mode' => 'mobile'
            ];

            $auth_mode = 'mobile';

        } else {
            log_debug("Mobile user password failed");

            $attempts = (int)$user->login_attempts + 1;
            $sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_mobile_users SET login_attempts = ".$attempts;

            if ($attempts >= 5) {
                $locked_until = date('Y-m-d H:i:s', time() + 900);
                $sql_update .= ", locked_until = '".$locked_until."'";
            }

            $sql_update .= " WHERE rowid = ".(int)$user->rowid;
            $db->query($sql_update);

            if ($attempts >= 5) {
                json_error('Compte verrouillé pour 15 minutes après 5 tentatives échouées.', 'TOO_MANY_ATTEMPTS', 403, [
                    'reason' => 'locked',
                    'email' => $email,
                    'user_id' => (int)$user->rowid,
                    'attempts' => $attempts,
                    'locked_until' => $locked_until,
                    'hint' => 'Le compte est verrouillé après 5 tentatives échouées'
                ]);
            }

            json_error('Mot de passe incorrect.', 'INVALID_PASSWORD', 401, [
                'reason' => 'password_mismatch',
                'email' => $email,
                'user_id' => (int)$user->rowid,
                'attempts' => $attempts,
                'hint' => 'Le mot de passe ne correspond pas au hash stocké'
            ]);
        }
    }
}

if (!$token) {
    log_debug("Trying Dolibarr user authentication");

    $sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.email, u.pass_crypted,
                u.api_key, u.statut, u.admin
            FROM ".MAIN_DB_PREFIX."user as u
            WHERE (u.login = '".$db->escape($email)."' OR u.email = '".$db->escape($email)."')
            AND u.entity IN (".getEntity('user').")";

    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        log_debug("User not found in any table");
        json_error('Identifiants invalides', 'USER_NOT_FOUND', 401, [
            'reason' => 'user_not_found',
            'email' => $email,
            'hint' => 'Utilisateur non trouvé dans les tables llx_mv3_mobile_users et llx_user'
        ]);
    }

    $user_obj = $db->fetch_object($resql);

    if ($user_obj->statut != 1) {
        log_debug("Dolibarr user account inactive");
        json_error('Compte désactivé', 'ACCOUNT_INACTIVE', 403, [
            'reason' => 'user_inactive',
            'email' => $email,
            'user_id' => (int)$user_obj->rowid,
            'hint' => 'Le compte Dolibarr est désactivé (statut != 1)'
        ]);
    }

    $hash = $user_obj->pass_crypted;
    $valid_password = false;

    if (password_verify($password, $hash)) {
        $valid_password = true;
    } elseif (md5($password) === $hash) {
        $valid_password = true;
    }

    if (!$valid_password) {
        log_debug("Dolibarr user password failed");
        json_error('Mot de passe incorrect', 'INVALID_PASSWORD', 401, [
            'reason' => 'password_mismatch',
            'email' => $email,
            'user_id' => (int)$user_obj->rowid,
            'hint' => 'Le mot de passe ne correspond pas au hash Dolibarr'
        ]);
    }

    log_debug("Dolibarr user password verified");

    $api_key = $user_obj->api_key;

    if (empty($api_key)) {
        $api_key = bin2hex(random_bytes(32));

        $sql_update = "UPDATE ".MAIN_DB_PREFIX."user
                        SET api_key = '".$db->escape($api_key)."'
                        WHERE rowid = ".(int)$user_obj->rowid;

        $db->query($sql_update);
    }

    $token = base64_encode(json_encode([
        'user_id' => $user_obj->rowid,
        'api_key' => $api_key,
        'login' => $user_obj->login,
        'issued_at' => time(),
        'expires_at' => time() + (30 * 24 * 3600)
    ]));

    $user_data = [
        'id' => (int)$user_obj->rowid,
        'dolibarr_user_id' => (int)$user_obj->rowid,
        'login' => $user_obj->login,
        'email' => $user_obj->email,
        'firstname' => $user_obj->firstname,
        'lastname' => $user_obj->lastname,
        'name' => trim($user_obj->firstname.' '.$user_obj->lastname),
        'is_admin' => (int)$user_obj->admin === 1,
        'auth_mode' => 'dolibarr'
    ];

    $auth_mode = 'dolibarr';
}

if (!$token || !$user_data) {
    log_debug("Authentication failed - no token generated");
    json_error('Erreur d\'authentification', 'AUTH_ERROR', 500);
}

log_debug("Login successful - auth_mode: ".$auth_mode." - user_id: ".$user_data['id']);

json_ok([
    'token' => $token,
    'user' => $user_data,
    'auth_mode' => $auth_mode
]);

function table_exists($table_name) {
    global $db;

    $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX.$table_name."'";
    $resql = $db->query($sql);

    return $resql && $db->num_rows($resql) > 0;
}
