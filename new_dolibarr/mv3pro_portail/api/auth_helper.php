<?php
/**
 * Helper pour vérifier l'authentification
 */

function checkAuth() {
    global $db, $conf;

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

    $sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.email, u.statut";
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

    return [
        'id' => $user_obj->rowid,
        'login' => $user_obj->login,
        'lastname' => $user_obj->lastname,
        'firstname' => $user_obj->firstname,
        'email' => $user_obj->email
    ];
}
