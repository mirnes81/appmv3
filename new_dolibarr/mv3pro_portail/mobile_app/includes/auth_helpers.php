<?php
/**
 * Authentication Helpers
 * Fonctions de vérification de session et d'authentification
 */

function requireMobileSession($redirectUrl = '../login_mobile.php')
{
    global $user;

    if (!isset($_SESSION['dol_login']) || empty($user->id)) {
        header('Location: ' . $redirectUrl);
        exit;
    }

    return $user;
}

function checkApiAuth($db)
{
    global $user;

    if (!isset($_SESSION['dol_login']) || empty($user->id)) {
        jsonError('Non authentifié', 401);
    }

    return $user;
}

function requireUserRights($rightModule, $rightLevel = 'read')
{
    global $user;

    if (!isset($user->rights->$rightModule->$rightLevel) || !$user->rights->$rightModule->$rightLevel) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès refusé']);
        exit;
    }

    return true;
}

function verifyMobileToken($db, $token)
{
    if (!$token) {
        return false;
    }

    $sql = "SELECT s.rowid, s.user_id, s.expires_at,";
    $sql .= " u.email, u.firstname, u.lastname, u.role, u.is_active";
    $sql .= " FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions as s";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."mv3_mobile_users as u ON u.rowid = s.user_id";
    $sql .= " WHERE s.session_token = '".$db->escape($token)."'";
    $sql .= " AND s.expires_at > NOW()";
    $sql .= " AND u.is_active = 1";

    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        return false;
    }

    $session = $db->fetch_object($resql);

    $db->query("UPDATE ".MAIN_DB_PREFIX."mv3_mobile_sessions
                SET last_activity = NOW()
                WHERE session_token = '".$db->escape($token)."'");

    return [
        'user_rowid' => (int)$session->user_id,
        'email' => $session->email,
        'firstname' => $session->firstname,
        'lastname' => $session->lastname,
        'role' => $session->role
    ];
}
