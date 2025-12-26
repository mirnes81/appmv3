<?php
/**
 * Liste des rapports/formulaires
 *
 * GET /forms/list?type=rapport&limit=50&offset=0
 * Header: X-Auth-Token: ...
 * Returns: {"success": true, "forms": [...]}
 */

require_once __DIR__ . '/cors_config.php';
require_once __DIR__ . '/auth_helper.php';

header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../../main.inc.php';

$user = checkAuth();

$type = $_GET['type'] ?? 'rapport';
$limit = min((int)($_GET['limit'] ?? 50), 100);
$offset = (int)($_GET['offset'] ?? 0);
$date_from = $_GET['date_from'] ?? null;
$date_to = $_GET['date_to'] ?? null;

$forms = [];

if ($type === 'rapport') {
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
    $sql .= " WHERE r.entity = ".$conf->entity;

    if ($date_from) {
        $sql .= " AND r.date_rapport >= '".$db->escape($date_from)."'";
    }
    if ($date_to) {
        $sql .= " AND r.date_rapport <= '".$db->escape($date_to)."'";
    }

    $sql .= " ORDER BY r.date_rapport DESC, r.rowid DESC";
    $sql .= " LIMIT ".$limit." OFFSET ".$offset;

    $resql = $db->query($sql);

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $sql_photos = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE fk_rapport = ".(int)$obj->rowid;
            $resql_photos = $db->query($sql_photos);
            $nb_photos = 0;
            if ($resql_photos) {
                $photo_obj = $db->fetch_object($resql_photos);
                $nb_photos = $photo_obj->nb;
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

            $forms[] = [
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
                'photos_count' => (int)$nb_photos,
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
        }
    }
}

echo json_encode([
    'success' => true,
    'forms' => $forms,
    'count' => count($forms)
]);
