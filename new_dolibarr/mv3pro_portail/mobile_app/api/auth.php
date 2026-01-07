<?php
/**
 * API Auth Mobile - Login/Logout/Verify
 *
 * Gère l'authentification des utilisateurs mobiles
 * TOUJOURS renvoie du JSON (même en erreur)
 */

// Headers JSON + CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Fonction helper pour réponse JSON
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Charger Dolibarr
$res = 0;
if (!$res && file_exists(__DIR__ . "/../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../main.inc.php";
}
if (!$res && file_exists(__DIR__ . "/../../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../../main.inc.php";
}

if (!$res) {
    jsonResponse(['success' => false, 'message' => 'Impossible de charger Dolibarr'], 500);
}

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

global $db, $conf;

// Récupérer l'action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Récupérer le body JSON
$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
}

// Router les actions
switch ($action) {
    case 'login':
        handleLogin($db, $conf, $data);
        break;

    case 'logout':
        handleLogout($db);
        break;

    case 'verify':
        handleVerify($db);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Action invalide'], 400);
}

/**
 * Gère le login
 */
function handleLogin($db, $conf, $data) {
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? trim($data['password']) : '';

    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'message' => 'Email et mot de passe requis'], 400);
    }

    // Valider email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Email invalide'], 400);
    }

    // Chercher l'utilisateur mobile
    $sql = "SELECT u.rowid, u.email, u.firstname, u.lastname, u.password_hash, u.is_active,";
    $sql .= " u.role, u.dolibarr_user_id, u.last_login, u.login_attempts, u.locked_until";
    $sql .= " FROM ".MAIN_DB_PREFIX."mv3_mobile_users as u";
    $sql .= " WHERE u.email = '".$db->escape($email)."'";
    $sql .= " AND u.entity IN (".getEntity('user').")";

    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        jsonResponse(['success' => false, 'message' => 'Email ou mot de passe incorrect'], 401);
    }

    $user = $db->fetch_object($resql);

    // Vérifier si compte actif
    if (!$user->is_active) {
        jsonResponse(['success' => false, 'message' => 'Compte désactivé. Contactez votre administrateur'], 403);
    }

    // Vérifier si compte verrouillé
    if ($user->locked_until && strtotime($user->locked_until) > time()) {
        $remaining = ceil((strtotime($user->locked_until) - time()) / 60);
        jsonResponse([
            'success' => false,
            'message' => "Compte verrouillé. Réessayez dans $remaining minute(s)"
        ], 403);
    }

    // Vérifier le mot de passe
    if (!password_verify($password, $user->password_hash)) {
        // Incrémenter tentatives échouées
        $attempts = (int)$user->login_attempts + 1;

        $sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_mobile_users SET";
        $sql_update .= " login_attempts = ".$attempts;

        // Verrouiller après 5 tentatives
        if ($attempts >= 5) {
            $locked_until = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            $sql_update .= ", locked_until = '".$locked_until."'";
        }

        $sql_update .= " WHERE rowid = ".(int)$user->rowid;
        $db->query($sql_update);

        if ($attempts >= 5) {
            jsonResponse([
                'success' => false,
                'message' => 'Trop de tentatives échouées. Compte verrouillé 15 minutes'
            ], 403);
        }

        jsonResponse(['success' => false, 'message' => 'Email ou mot de passe incorrect'], 401);
    }

    // Connexion réussie - Réinitialiser tentatives
    $db->query("UPDATE ".MAIN_DB_PREFIX."mv3_mobile_users
                SET login_attempts = 0, locked_until = NULL, last_login = NOW()
                WHERE rowid = ".(int)$user->rowid);

    // Générer token de session
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + (30 * 24 * 3600)); // 30 jours

    // Créer session
    $sql_session = "INSERT INTO ".MAIN_DB_PREFIX."mv3_mobile_sessions";
    $sql_session .= " (user_id, session_token, device_info, ip_address, expires_at, last_activity)";
    $sql_session .= " VALUES (";
    $sql_session .= " ".(int)$user->rowid.",";
    $sql_session .= " '".$db->escape($token)."',";
    $sql_session .= " '".$db->escape($_SERVER['HTTP_USER_AGENT'] ?? '')."',";
    $sql_session .= " '".$db->escape($_SERVER['REMOTE_ADDR'])."',";
    $sql_session .= " '".$expires_at."',";
    $sql_session .= " NOW()";
    $sql_session .= ")";

    if (!$db->query($sql_session)) {
        jsonResponse(['success' => false, 'message' => 'Erreur création session'], 500);
    }

    // Réponse succès
    jsonResponse([
        'success' => true,
        'token' => $token,
        'user' => [
            'user_rowid' => (int)$user->rowid,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'role' => $user->role,
            'dolibarr_user_id' => (int)$user->dolibarr_user_id
        ],
        'expires_at' => $expires_at
    ]);
}

/**
 * Gère le logout
 */
function handleLogout($db) {
    $token = null;

    // Récupérer token depuis Authorization header
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        jsonResponse(['success' => false, 'message' => 'Token manquant'], 401);
    }

    // Supprimer la session
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions";
    $sql .= " WHERE session_token = '".$db->escape($token)."'";

    $db->query($sql);

    jsonResponse(['success' => true, 'message' => 'Déconnexion réussie']);
}

/**
 * Vérifie la validité d'un token
 */
function handleVerify($db) {
    $token = null;

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        jsonResponse(['success' => false, 'message' => 'Token manquant'], 401);
    }

    // Vérifier session
    $sql = "SELECT s.rowid, s.user_id, s.expires_at,";
    $sql .= " u.email, u.firstname, u.lastname, u.role, u.is_active";
    $sql .= " FROM ".MAIN_DB_PREFIX."mv3_mobile_sessions as s";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."mv3_mobile_users as u ON u.rowid = s.user_id";
    $sql .= " WHERE s.session_token = '".$db->escape($token)."'";
    $sql .= " AND s.expires_at > NOW()";
    $sql .= " AND u.is_active = 1";

    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        jsonResponse(['success' => false, 'message' => 'Token invalide ou expiré'], 401);
    }

    $session = $db->fetch_object($resql);

    // Mettre à jour last_activity
    $db->query("UPDATE ".MAIN_DB_PREFIX."mv3_mobile_sessions
                SET last_activity = NOW()
                WHERE session_token = '".$db->escape($token)."'");

    jsonResponse([
        'success' => true,
        'user' => [
            'user_rowid' => (int)$session->user_id,
            'email' => $session->email,
            'firstname' => $session->firstname,
            'lastname' => $session->lastname,
            'role' => $session->role
        ]
    ]);
}
