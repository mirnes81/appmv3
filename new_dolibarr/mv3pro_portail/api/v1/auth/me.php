<?php
/**
 * API v1 - Auth - Me
 * GET /api/v1/auth/me.php
 *
 * Récupère les informations de l'utilisateur connecté
 * basé sur le token Bearer
 *
 * Headers requis :
 *   Authorization: Bearer {token}
 *
 * Retourne :
 *   {"success": true, "user": {...}}
 */

require_once __DIR__.'/../_bootstrap.php';

$auth = require_auth();
require_method('GET');

log_debug("Auth me endpoint - user_id: ".$auth['user_id']." - auth_mode: ".$auth['auth_mode']);

if ($auth['auth_mode'] === 'mobile') {
    $sql = "SELECT u.rowid, u.email, u.firstname, u.lastname, u.role,
                u.dolibarr_user_id, u.is_active
            FROM ".MAIN_DB_PREFIX."mv3_mobile_users as u
            WHERE u.rowid = ".(int)$auth['user_id']."
            AND u.entity IN (".getEntity('user').")";

    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        json_error('Utilisateur non trouvé', 'USER_NOT_FOUND', 404);
    }

    $user = $db->fetch_object($resql);

    if (!$user->is_active) {
        json_error('Compte désactivé', 'ACCOUNT_INACTIVE', 403);
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

} else {
    $sql = "SELECT u.rowid, u.login, u.email, u.firstname, u.lastname,
                u.statut, u.admin
            FROM ".MAIN_DB_PREFIX."user as u
            WHERE u.rowid = ".(int)$auth['dolibarr_user_id']."
            AND u.entity IN (".getEntity('user').")";

    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        json_error('Utilisateur non trouvé', 'USER_NOT_FOUND', 404);
    }

    $user = $db->fetch_object($resql);

    if ($user->statut != 1) {
        json_error('Compte désactivé', 'ACCOUNT_INACTIVE', 403);
    }

    $user_data = [
        'id' => (int)$user->rowid,
        'dolibarr_user_id' => (int)$user->rowid,
        'login' => $user->login,
        'email' => $user->email,
        'firstname' => $user->firstname,
        'lastname' => $user->lastname,
        'name' => trim($user->firstname.' '.$user->lastname),
        'is_admin' => (int)$user->admin === 1,
        'auth_mode' => 'dolibarr'
    ];
}

json_ok(['user' => $user_data]);
