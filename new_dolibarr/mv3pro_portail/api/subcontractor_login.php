<?php
// Configuration CORS sécurisée
require_once __DIR__ . '/cors_config.php';

header('Content-Type: application/json');
setCorsHeaders(); // Utilise la configuration centralisée
handleCorsPreflightRequest();

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

$input = json_decode(file_get_contents('php://input'), true);

$pin_code = isset($input['pin_code']) ? trim($input['pin_code']) : '';
$device_info = isset($input['device_info']) ? $input['device_info'] : '';

if (empty($pin_code)) {
    echo json_encode(['success' => false, 'message' => 'Code PIN requis']);
    exit;
}

// Rate limiting: bloquer après 5 tentatives échouées en 15 minutes
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$time_window = date('Y-m-d H:i:s', strtotime('-15 minutes'));

// Compter les tentatives récentes depuis cette IP
$sql_check = "SELECT COUNT(*) as attempts FROM ".MAIN_DB_PREFIX."mv3_subcontractor_login_attempts";
$sql_check .= " WHERE ip_address = '".$db->escape($ip_address)."'";
$sql_check .= " AND success = 0";
$sql_check .= " AND attempt_time > '".$db->escape($time_window)."'";

$resql_check = $db->query($sql_check);
if ($resql_check) {
    $check = $db->fetch_object($resql_check);
    if ($check->attempts >= 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Trop de tentatives échouées. Veuillez réessayer dans 15 minutes.'
        ]);
        exit;
    }
}

$sql = "SELECT rowid, ref, firstname, lastname, email, phone, specialty, rate_type, rate_amount, active";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractors";
$sql .= " WHERE pin_code = '".$db->escape($pin_code)."'";
$sql .= " AND active = 1";
$sql .= " AND entity = ".$conf->entity;

$resql = $db->query($sql);

if (!$resql) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion']);
    exit;
}

if ($db->num_rows($resql) === 0) {
    // Enregistrer la tentative échouée
    $sql_log = "INSERT INTO ".MAIN_DB_PREFIX."mv3_subcontractor_login_attempts";
    $sql_log .= " (ip_address, pin_code, success, attempt_time)";
    $sql_log .= " VALUES ('".$db->escape($ip_address)."', '".$db->escape($pin_code)."', 0, NOW())";
    $db->query($sql_log);

    echo json_encode(['success' => false, 'message' => 'Code PIN incorrect']);
    exit;
}

$obj = $db->fetch_object($resql);

$session_id = bin2hex(random_bytes(32));
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));

$sql_session = "INSERT INTO ".MAIN_DB_PREFIX."mv3_subcontractor_sessions";
$sql_session .= " (session_id, fk_subcontractor, device_info, ip_address, created_at, last_activity, expires_at)";
$sql_session .= " VALUES (";
$sql_session .= "'".$db->escape($session_id)."',";
$sql_session .= (int)$obj->rowid.",";
$sql_session .= "'".$db->escape($device_info)."',";
$sql_session .= "'".$db->escape($ip_address)."',";
$sql_session .= "NOW(),";
$sql_session .= "NOW(),";
$sql_session .= "'".$db->escape($expires_at)."'";
$sql_session .= ")";

if (!$db->query($sql_session)) {
    echo json_encode(['success' => false, 'message' => 'Erreur de création de session']);
    exit;
}

$sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_subcontractors";
$sql_update .= " SET last_login = NOW()";
$sql_update .= " WHERE rowid = ".(int)$obj->rowid;
$db->query($sql_update);

// Enregistrer la tentative réussie
$sql_log_success = "INSERT INTO ".MAIN_DB_PREFIX."mv3_subcontractor_login_attempts";
$sql_log_success .= " (ip_address, pin_code, success, attempt_time, fk_subcontractor)";
$sql_log_success .= " VALUES ('".$db->escape($ip_address)."', '".$db->escape($pin_code)."', 1, NOW(), ".(int)$obj->rowid.")";
$db->query($sql_log_success);

// Nettoyer les tentatives de plus de 24 heures
$sql_clean = "DELETE FROM ".MAIN_DB_PREFIX."mv3_subcontractor_login_attempts";
$sql_clean .= " WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$db->query($sql_clean);

echo json_encode([
    'success' => true,
    'session_token' => $session_id,
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
