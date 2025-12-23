<?php
/**
 * Session management for mobile app with independent authentication
 */

// Vérifier si l'utilisateur est connecté via le système mobile
function checkMobileAuth() {
    // Récupérer le token depuis le localStorage (envoyé via header ou cookie)
    $token = null;

    // Essayer de récupérer depuis le header Authorization
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }

    // Essayer depuis les cookies
    if (!$token && isset($_COOKIE['mobile_auth_token'])) {
        $token = $_COOKIE['mobile_auth_token'];
    }

    // Essayer depuis la session PHP
    if (!$token && isset($_SESSION['mobile_auth_token'])) {
        $token = $_SESSION['mobile_auth_token'];
    }

    if (!$token) {
        return null;
    }

    global $db;

    // Vérifier le token dans la base de données
    $sql = "SELECT s.rowid, s.user_id, s.expires_at,
                   u.rowid as user_rowid, u.email, u.firstname, u.lastname, u.phone, u.role,
                   u.is_active, u.dolibarr_user_id
            FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions s
            INNER JOIN ".MAIN_DB_PREFIX."mv3_mobile_users u ON u.rowid = s.user_id
            WHERE s.session_token = '".$db->escape($token)."'
            AND s.expires_at > NOW()
            AND u.is_active = 1";

    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        return null;
    }

    $session = $db->fetch_object($resql);

    // Mettre à jour l'activité de la session
    $sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_mobile_sessions
                   SET last_activity = NOW()
                   WHERE session_token = '".$db->escape($token)."'";
    $db->query($sql_update);

    // Stocker dans la session PHP pour les autres pages
    $_SESSION['mobile_auth_token'] = $token;
    $_SESSION['mobile_user_id'] = $session->user_rowid;
    $_SESSION['mobile_user_data'] = [
        'id' => $session->user_rowid,
        'email' => $session->email,
        'firstname' => $session->firstname,
        'lastname' => $session->lastname,
        'phone' => $session->phone,
        'role' => $session->role,
        'dolibarr_user_id' => $session->dolibarr_user_id
    ];

    return $session;
}

function requireMobileAuth() {
    $user = checkMobileAuth();

    if (!$user) {
        // Pas authentifié, rediriger vers login
        header('Location: /custom/mv3pro_portail/mobile_app/login_mobile.php');
        exit;
    }

    return $user;
}

function getMobileUserId() {
    return $_SESSION['mobile_user_id'] ?? null;
}

function getMobileUserData() {
    return $_SESSION['mobile_user_data'] ?? null;
}

function getMobileUsername() {
    $userData = getMobileUserData();
    if ($userData) {
        return $userData['firstname'] . ' ' . $userData['lastname'];
    }
    return '';
}

function logoutMobile() {
    global $db;

    $token = $_SESSION['mobile_auth_token'] ?? null;

    if ($token) {
        // Supprimer la session de la base de données
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions
                WHERE session_token = '".$db->escape($token)."'";
        $db->query($sql);
    }

    // Nettoyer la session PHP
    unset($_SESSION['mobile_auth_token']);
    unset($_SESSION['mobile_user_id']);
    unset($_SESSION['mobile_user_data']);

    // Supprimer le cookie
    setcookie('mobile_auth_token', '', time() - 3600, '/');

    session_destroy();

    header('Location: /custom/mv3pro_portail/mobile_app/login_mobile.php');
    exit;
}
