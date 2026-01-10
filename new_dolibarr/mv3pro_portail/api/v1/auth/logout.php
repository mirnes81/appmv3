<?php
/**
 * API v1 - Auth - Logout
 * POST /api/v1/auth/logout.php
 *
 * Déconnecte l'utilisateur et invalide son token/session
 *
 * Headers requis :
 *   Authorization: Bearer {token}
 *
 * Retourne :
 *   {"success": true, "message": "Déconnexion réussie"}
 */

require_once __DIR__.'/../_bootstrap.php';

$auth = require_auth();
require_method('POST');

log_debug("Logout - user_id: ".$auth['user_id']." - auth_mode: ".$auth['auth_mode']);

if ($auth['auth_mode'] === 'mobile') {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions
            WHERE session_token = '".$db->escape($auth['raw_token'])."'";

    if (!$db->query($sql)) {
        error_log("Failed to delete mobile session: " . $db->lasterror());
    }

    log_debug("Mobile session deleted");

} else {
    log_debug("Dolibarr user logout (token-based, no session to delete)");
}

json_ok(['message' => 'Déconnexion réussie']);
