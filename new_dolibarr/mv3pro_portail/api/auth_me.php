<?php
/**
 * Endpoint vérification token et récupération infos utilisateur
 *
 * GET /auth/me
 * Header: X-Auth-Token: ...
 * Returns: {"success": true, "user": {...}}
 */

require_once __DIR__ . '/cors_config.php';
header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../../main.inc.php';

$auth_token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';

if (empty($auth_token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token requis']);
    exit;
}

$decoded = json_decode(base64_decode($auth_token), true);

if (!$decoded || !isset($decoded['user_id']) || !isset($decoded['api_key'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalide']);
    exit;
}

if (isset($decoded['expires_at']) && $decoded['expires_at'] < time()) {
    http_response_code(401);
    echo json_encode(['error' => 'Token expiré']);
    exit;
}

$user_id = (int)$decoded['user_id'];
$api_key = $decoded['api_key'];

$sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.email, u.api_key, u.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE u.rowid = ".$user_id;
$sql .= " AND u.api_key = '".$db->escape($api_key)."'";
$sql .= " AND u.statut = 1";

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Utilisateur invalide ou désactivé']);
    exit;
}

$user_obj = $db->fetch_object($resql);

echo json_encode([
    'success' => true,
    'user' => [
        'id' => (string)$user_obj->rowid,
        'login' => $user_obj->login,
        'lastname' => $user_obj->lastname,
        'firstname' => $user_obj->firstname,
        'email' => $user_obj->email,
        'name' => trim($user_obj->firstname . ' ' . $user_obj->lastname)
    ]
]);
