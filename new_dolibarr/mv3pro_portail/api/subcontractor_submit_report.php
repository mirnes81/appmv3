<?php
// Configuration CORS sécurisée
require_once __DIR__ . '/cors_config.php';

header('Content-Type: application/json');
setCorsHeaders(); // Utilise la configuration centralisée
handleCorsPreflightRequest();

require_once '../../../main.inc.php';

$input = json_decode(file_get_contents('php://input'), true);

$session_token = $input['session_token'] ?? '';
$report_date = $input['report_date'] ?? '';
$work_type = $input['work_type'] ?? '';
$start_time = $input['start_time'] ?? '';
$end_time = $input['end_time'] ?? '';
$surface_m2 = (float)($input['surface_m2'] ?? 0);
$hours_worked = (float)($input['hours_worked'] ?? 0);
$notes = $input['notes'] ?? '';
$latitude = $input['latitude'] ?? null;
$longitude = $input['longitude'] ?? null;
$signature_data = $input['signature_data'] ?? '';
$photos = $input['photos'] ?? [];

if (empty($session_token)) {
    echo json_encode(['success' => false, 'message' => 'Session requise']);
    exit;
}

$sql_session = "SELECT s.fk_subcontractor, c.rate_type, c.rate_amount";
$sql_session .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_sessions as s";
$sql_session .= " INNER JOIN ".MAIN_DB_PREFIX."mv3_subcontractors as c ON c.rowid = s.fk_subcontractor";
$sql_session .= " WHERE s.session_id = '".$db->escape($session_token)."'";
$sql_session .= " AND s.expires_at > NOW()";

$resql_session = $db->query($sql_session);

if (!$resql_session || $db->num_rows($resql_session) === 0) {
    echo json_encode(['success' => false, 'message' => 'Session invalide']);
    exit;
}

$session_obj = $db->fetch_object($resql_session);
$fk_subcontractor = $session_obj->fk_subcontractor;

$amount_calculated = 0;
if ($session_obj->rate_type === 'm2') {
    $amount_calculated = $surface_m2 * $session_obj->rate_amount;
} elseif ($session_obj->rate_type === 'hourly') {
    $amount_calculated = $hours_worked * $session_obj->rate_amount;
} elseif ($session_obj->rate_type === 'daily') {
    $amount_calculated = $session_obj->rate_amount;
}

$year = date('Y');
$month = date('m');
$day = date('d');

$sql_max = "SELECT MAX(CAST(SUBSTRING(ref, 13) AS UNSIGNED)) as max_num";
$sql_max .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_reports";
$sql_max .= " WHERE ref LIKE 'RST-".$year.$month.$day."-%'";
$sql_max .= " AND entity = ".$conf->entity;

$resql_max = $db->query($sql_max);
$max_num = 0;
if ($resql_max) {
    $obj_max = $db->fetch_object($resql_max);
    $max_num = $obj_max->max_num ?? 0;
}
$next_num = str_pad($max_num + 1, 4, '0', STR_PAD_LEFT);
$ref = 'RST-'.$year.$month.$day.'-'.$next_num;

$db->begin();

$sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_subcontractor_reports (";
$sql .= "ref, entity, fk_subcontractor, report_date, work_type,";
$sql .= "surface_m2, hours_worked, start_time, end_time,";
$sql .= "amount_calculated, notes, latitude, longitude,";
$sql .= "signature_data, signature_date, status, photo_count, date_creation";
$sql .= ") VALUES (";
$sql .= "'".$db->escape($ref)."',";
$sql .= $conf->entity.",";
$sql .= (int)$fk_subcontractor.",";
$sql .= "'".$db->escape($report_date)."',";
$sql .= "'".$db->escape($work_type)."',";
$sql .= (float)$surface_m2.",";
$sql .= (float)$hours_worked.",";
$sql .= ($start_time ? "'".$db->escape($start_time)."'" : "NULL").",";
$sql .= ($end_time ? "'".$db->escape($end_time)."'" : "NULL").",";
$sql .= (float)$amount_calculated.",";
$sql .= ($notes ? "'".$db->escape($notes)."'" : "NULL").",";
$sql .= ($latitude ? "'".$db->escape($latitude)."'" : "NULL").",";
$sql .= ($longitude ? "'".$db->escape($longitude)."'" : "NULL").",";
$sql .= ($signature_data ? "'".$db->escape($signature_data)."'" : "NULL").",";
$sql .= "NOW(),";
$sql .= "1,";
$sql .= count($photos).",";
$sql .= "NOW()";
$sql .= ")";

if (!$db->query($sql)) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du rapport']);
    exit;
}

$report_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_subcontractor_reports");

$upload_dir = DOL_DATA_ROOT.'/mv3pro_portail/subcontractor_reports/'.$report_id;
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

foreach ($photos as $index => $photo) {
    $photo_data = $photo['data'] ?? '';
    $photo_name = $photo['name'] ?? 'photo_'.$index.'.jpg';
    $photo_size = $photo['size'] ?? 0;
    $photo_lat = $photo['latitude'] ?? null;
    $photo_lon = $photo['longitude'] ?? null;
    $photo_date = $photo['date'] ?? date('Y-m-d H:i:s');

    if (preg_match('/^data:image\/(\w+);base64,/', $photo_data, $type)) {
        $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
        $photo_data = base64_decode($photo_data);

        $file_name = 'photo_'.time().'_'.$index.'.jpg';
        $file_path = $upload_dir.'/'.$file_name;

        if (file_put_contents($file_path, $photo_data)) {
            $sql_photo = "INSERT INTO ".MAIN_DB_PREFIX."mv3_subcontractor_photos (";
            $sql_photo .= "fk_report, photo_type, file_path, file_name, file_size,";
            $sql_photo .= "latitude, longitude, photo_date, position";
            $sql_photo .= ") VALUES (";
            $sql_photo .= (int)$report_id.",";
            $sql_photo .= "'work',";
            $sql_photo .= "'".$db->escape($file_path)."',";
            $sql_photo .= "'".$db->escape($file_name)."',";
            $sql_photo .= (int)$photo_size.",";
            $sql_photo .= ($photo_lat ? "'".$db->escape($photo_lat)."'" : "NULL").",";
            $sql_photo .= ($photo_lon ? "'".$db->escape($photo_lon)."'" : "NULL").",";
            $sql_photo .= "'".$db->escape($photo_date)."',";
            $sql_photo .= (int)$index;
            $sql_photo .= ")";

            $db->query($sql_photo);
        }
    }
}

$db->commit();

echo json_encode([
    'success' => true,
    'message' => 'Rapport soumis avec succès',
    'report_id' => $report_id,
    'report_ref' => $ref
]);
