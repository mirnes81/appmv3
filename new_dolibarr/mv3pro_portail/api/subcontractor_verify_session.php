<?php
// Configuration CORS sécurisée
require_once __DIR__ . '/cors_config.php';

header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../main.inc.php';

$input = json_decode(file_get_contents('php://input'), true);
$session_token = isset($input['session_token']) ? trim($input['session_token']) : '';

if (empty($session_token)) {
    echo json_encode(['success' => false, 'message' => 'Token requis']);
    exit;
}

$sql = "SELECT s.fk_subcontractor, s.expires_at,";
$sql .= " c.rowid, c.ref, c.firstname, c.lastname, c.email, c.phone, c.specialty, c.rate_type, c.rate_amount";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_sessions as s";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."mv3_subcontractors as c ON c.rowid = s.fk_subcontractor";
$sql .= " WHERE s.session_id = '".$db->escape($session_token)."'";
$sql .= " AND c.active = 1";

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    echo json_encode(['success' => false, 'message' => 'Session invalide']);
    exit;
}

$obj = $db->fetch_object($resql);

if (strtotime($obj->expires_at) < time()) {
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

$sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_subcontractor_sessions";
$sql_update .= " SET last_activity = NOW()";
$sql_update .= " WHERE session_id = '".$db->escape($session_token)."'";
$db->query($sql_update);

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $obj->rowid,
        'ref' => $obj->ref,
        'firstname' => $obj->firstname,
        'lastname' => $obj->lastname,
        'email' => $obj->email,
        'phone' => $obj->phone,
        'specialty' => $obj->specialty,
        'rate_type' => $obj->rate_type,
        'rate_amount' => (float)$obj->rate_amount
    ]
]);
