<?php
// Configuration CORS sécurisée
require_once __DIR__ . '/cors_config.php';

header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../../main.inc.php';

$input = json_decode(file_get_contents('php://input'), true);
$session_token = $input['session_token'] ?? '';

if (empty($session_token)) {
    echo json_encode(['success' => false, 'message' => 'Token requis']);
    exit;
}

$sql = "UPDATE ".MAIN_DB_PREFIX."mv3_subcontractor_sessions";
$sql .= " SET last_activity = NOW()";
$sql .= " WHERE session_id = '".$db->escape($session_token)."'";

if ($db->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
