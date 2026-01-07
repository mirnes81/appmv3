<?php
/**
 * API v1 - Sens de Pose - Création
 * POST /api/v1/sens_pose_create.php
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_rights('write', $auth);
require_method('POST');

$body = get_json_body(true);
$project_id = isset($body['project_id']) ? (int)$body['project_id'] : 0;
require_param($project_id, 'project_id');

$ref = 'SP'.date('Ymd').uniqid();
$date_pose = isset($body['date_pose']) ? $body['date_pose'] : date('Y-m-d');
$pieces_json = isset($body['pieces']) ? json_encode($body['pieces']) : '[]';

$sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose";
$sql .= " (ref, entity, fk_projet, fk_user, date_pose, date_creation, pieces_data, status)";
$sql .= " VALUES ('".$db->escape($ref)."', ".$conf->entity.", ".$project_id.", ".$auth['user_id'].", '".$db->escape($date_pose)."', NOW(), '".$db->escape($pieces_json)."', 0)";

if (!$db->query($sql)) json_error('Erreur création', 'CREATE_ERROR', 500);

$id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_sens_pose");
json_ok(['sens_pose' => ['id' => $id, 'ref' => $ref]], 201);
