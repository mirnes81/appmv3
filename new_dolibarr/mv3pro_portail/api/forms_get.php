<?php
/**
 * Détail d'un formulaire
 *
 * GET /forms/get/{id}
 * Header: X-Auth-Token: ...
 * Returns: {"success": true, "form": {...}}
 */

require_once __DIR__ . '/cors_config.php';
require_once __DIR__ . '/auth_helper.php';

header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../main.inc.php';

$user = checkAuth();

$form_id = (int)($_GET['id'] ?? 0);

if (!$form_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID requis']);
    exit;
}

$sql = "SELECT r.rowid, r.fk_user, r.fk_projet, r.date_rapport,";
$sql .= " r.zone_travail, r.description, r.heures_debut, r.heures_fin,";
$sql .= " r.temps_total, r.travaux_realises, r.observations, r.statut,";
$sql .= " r.gps_latitude, r.gps_longitude,";
$sql .= " r.meteo_temperature, r.meteo_condition,";
$sql .= " r.date_creation, r.date_modification,";
$sql .= " p.ref as projet_ref, p.title as projet_title,";
$sql .= " u.firstname, u.lastname";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_rapport as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user";
$sql .= " WHERE r.rowid = ".$form_id;
$sql .= " AND r.entity = ".$conf->entity;

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Formulaire non trouvé']);
    exit;
}

$obj = $db->fetch_object($resql);

$sql_photos = "SELECT rowid, filepath, filename, description, ordre FROM ".MAIN_DB_PREFIX."mv3_rapport_photo";
$sql_photos .= " WHERE fk_rapport = ".$form_id;
$sql_photos .= " ORDER BY ordre";

$resql_photos = $db->query($sql_photos);
$photos = [];

if ($resql_photos) {
    while ($photo = $db->fetch_object($resql_photos)) {
        $photos[] = [
            'id' => (string)$photo->rowid,
            'filepath' => $photo->filepath,
            'filename' => $photo->filename,
            'description' => $photo->description,
            'url' => DOL_URL_ROOT.'/document.php?modulepart=mv3pro_portail&file='.$photo->filepath
        ];
    }
}

$materials_used = [];
if (!empty($obj->travaux_realises)) {
    $lines = explode("\n", $obj->travaux_realises);
    foreach ($lines as $line) {
        if (strpos($line, '- ') === 0) {
            $line = trim(substr($line, 2));
            if (preg_match('/^(.+?):\s*(\d+(?:\.\d+)?)\s*(\w+)/', $line, $matches)) {
                $materials_used[] = [
                    'label' => $matches[1],
                    'quantity' => floatval($matches[2]),
                    'unit' => $matches[3]
                ];
            }
        }
    }
}

$form = [
    'id' => (string)$obj->rowid,
    'type' => 'rapport',
    'user_id' => (string)$obj->fk_user,
    'user_name' => trim($obj->firstname . ' ' . $obj->lastname),
    'project_id' => $obj->fk_projet ? (int)$obj->fk_projet : null,
    'project_ref' => $obj->projet_ref,
    'project_title' => $obj->projet_title,
    'client_name' => $obj->zone_travail,
    'date' => $obj->date_rapport,
    'start_time' => $obj->heures_debut,
    'end_time' => $obj->heures_fin,
    'total_hours' => $obj->temps_total ? floatval($obj->temps_total) : null,
    'description' => $obj->description,
    'observations' => $obj->observations,
    'materials_used' => $materials_used,
    'photos' => $photos,
    'gps_location' => [
        'latitude' => $obj->gps_latitude ? floatval($obj->gps_latitude) : null,
        'longitude' => $obj->gps_longitude ? floatval($obj->gps_longitude) : null
    ],
    'weather' => [
        'temperature' => $obj->meteo_temperature ? floatval($obj->meteo_temperature) : null,
        'conditions' => $obj->meteo_condition
    ],
    'status' => 'synced',
    'created_at' => $obj->date_creation,
    'updated_at' => $obj->date_modification
];

echo json_encode([
    'success' => true,
    'form' => $form
]);
