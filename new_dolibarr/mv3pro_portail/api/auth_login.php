<?php
/**
 * Endpoint authentification MV3 PRO PWA
 *
 * POST /auth/login
 * Body: {"login": "user@example.com", "password": "password"}
 * Returns: {"success": true, "token": "...", "user": {...}}
 */

require_once __DIR__ . '/cors_config.php';
header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$input = json_decode(file_get_contents('php://input'), true);

$login = $input['login'] ?? '';
$password = $input['password'] ?? '';

if (empty($login) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Login et mot de passe requis']);
    exit;
}

$sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.email, u.pass_crypted, u.api_key, u.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE (u.login = '".$db->escape($login)."' OR u.email = '".$db->escape($login)."')";
$sql .= " AND u.entity = ".$conf->entity;

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Identifiants invalides']);
    exit;
}

$user_obj = $db->fetch_object($resql);

if ($user_obj->statut != 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Compte désactivé']);
    exit;
}

$hash = $user_obj->pass_crypted;

$valid_password = false;

if (password_verify($password, $hash)) {
    $valid_password = true;
} elseif (md5($password) === $hash) {
    $valid_password = true;
}

if (!$valid_password) {
    http_response_code(401);
    echo json_encode(['error' => 'Mot de passe incorrect']);
    exit;
}

$api_key = $user_obj->api_key;

if (empty($api_key)) {
    $api_key = bin2hex(random_bytes(32));

    $sql_update = "UPDATE ".MAIN_DB_PREFIX."user";
    $sql_update .= " SET api_key = '".$db->escape($api_key)."'";
    $sql_update .= " WHERE rowid = ".(int)$user_obj->rowid;

    $db->query($sql_update);
}

$token = base64_encode(json_encode([
    'user_id' => $user_obj->rowid,
    'api_key' => $api_key,
    'login' => $user_obj->login,
    'issued_at' => time(),
    'expires_at' => time() + (30 * 24 * 3600)
]));

echo json_encode([
    'success' => true,
    'token' => $token,
    'api_key' => $api_key,
    'user' => [
        'id' => (string)$user_obj->rowid,
        'login' => $user_obj->login,
        'lastname' => $user_obj->lastname,
        'firstname' => $user_obj->firstname,
        'email' => $user_obj->email,
        'name' => trim($user_obj->firstname . ' ' . $user_obj->lastname)
    ]
]);
