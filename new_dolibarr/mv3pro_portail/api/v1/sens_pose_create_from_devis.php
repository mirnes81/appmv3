<?php
/**
 * API v1 - Sens de Pose - Création depuis devis
 * POST /api/v1/sens_pose_create_from_devis.php
 * Body: {"devis_id": 123}
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_rights('write', $auth);
require_method('POST');

$body = get_json_body(true);
$devis_id = isset($body['devis_id']) ? (int)$body['devis_id'] : 0;
require_param($devis_id, 'devis_id');

// Récupérer le devis
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."propal WHERE rowid = ".$devis_id;
$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) json_error('Devis non trouvé', 'NOT_FOUND', 404);

$devis = $db->fetch_object($resql);
$ref = 'SP'.date('Ymd').'_DEV'.$devis_id;

$sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose";
$sql .= " (ref, entity, fk_projet, fk_soc, fk_propal, fk_user, date_creation, status)";
$sql .= " VALUES ('".$db->escape($ref)."', ".$conf->entity.", ".$devis->fk_projet.", ".$devis->fk_soc.", ".$devis_id.", ".$auth['user_id'].", NOW(), 0)";

if (!$db->query($sql)) json_error('Erreur création', 'CREATE_ERROR', 500);

$id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_sens_pose");
json_ok(['sens_pose' => ['id' => $id, 'ref' => $ref]], 201);
