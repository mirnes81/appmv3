<?php
/**
 * Création d'un rapport/formulaire
 *
 * POST /forms/create
 * Header: X-Auth-Token: ...
 * Body: {type, date, client_name, description, ...}
 * Returns: {"success": true, "form_id": 123}
 */

require_once __DIR__ . '/cors_config.php';
require_once __DIR__ . '/auth_helper.php';

header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../main.inc.php';

$user = checkAuth();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

$type = $input['type'] ?? 'rapport';
$date = $input['date'] ?? date('Y-m-d');
$client_name = $input['client_name'] ?? '';
$description = $input['description'] ?? '';
$observations = $input['observations'] ?? '';
$start_time = $input['start_time'] ?? null;
$end_time = $input['end_time'] ?? null;
$gps_latitude = $input['gps_latitude'] ?? ($input['gps_location']['latitude'] ?? null);
$gps_longitude = $input['gps_longitude'] ?? ($input['gps_location']['longitude'] ?? null);
$weather_temperature = $input['weather_temperature'] ?? ($input['weather']['temperature'] ?? null);
$weather_conditions = $input['weather_conditions'] ?? ($input['weather']['conditions'] ?? null);
$materials_used = $input['materials_used'] ?? [];
$project_id = $input['project_id'] ?? null;

$temps_total = 0;
if ($start_time && $end_time) {
    $start = strtotime($start_time);
    $end = strtotime($end_time);
    if ($end > $start) {
        $temps_total = ($end - $start) / 3600;
    }
}

$travaux_realises = '';
if (!empty($materials_used)) {
    $travaux_realises = "Matériaux utilisés:\n";
    foreach ($materials_used as $mat) {
        $label = $mat['label'] ?? $mat['name'] ?? '';
        $qty = $mat['quantity'] ?? 0;
        $unit = $mat['unit'] ?? '';
        $travaux_realises .= "- $label: $qty $unit\n";
    }
}

$db->begin();

$sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport (";
$sql .= "entity, fk_user, fk_projet, date_rapport, zone_travail,";
$sql .= "description, heures_debut, heures_fin, temps_total,";
$sql .= "travaux_realises, observations, statut,";
$sql .= "gps_latitude, gps_longitude,";
$sql .= "meteo_temperature, meteo_condition,";
$sql .= "date_creation";
$sql .= ") VALUES (";
$sql .= $conf->entity.",";
$sql .= (int)$user['id'].",";
$sql .= ($project_id ? (int)$project_id : "NULL").",";
$sql .= "'".$db->escape($date)."',";
$sql .= "'".$db->escape($client_name)."',";
$sql .= "'".$db->escape($description)."',";
$sql .= ($start_time ? "'".$db->escape($start_time)."'" : "NULL").",";
$sql .= ($end_time ? "'".$db->escape($end_time)."'" : "NULL").",";
$sql .= (float)$temps_total.",";
$sql .= "'".$db->escape($travaux_realises)."',";
$sql .= "'".$db->escape($observations)."',";
$sql .= "'valide',";
$sql .= ($gps_latitude ? "'".$db->escape($gps_latitude)."'" : "NULL").",";
$sql .= ($gps_longitude ? "'".$db->escape($gps_longitude)."'" : "NULL").",";
$sql .= ($weather_temperature ? (float)$weather_temperature : "NULL").",";
$sql .= ($weather_conditions ? "'".$db->escape($weather_conditions)."'" : "NULL").",";
$sql .= "NOW()";
$sql .= ")";

if (!$db->query($sql)) {
    $db->rollback();
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la création du formulaire',
        'details' => $db->lasterror()
    ]);
    exit;
}

$form_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_rapport");

$db->commit();

echo json_encode([
    'success' => true,
    'form_id' => $form_id,
    'message' => 'Formulaire créé avec succès'
]);
