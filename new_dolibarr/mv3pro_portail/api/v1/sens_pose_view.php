<?php
/**
 * API v1 - Sens de Pose - Détail
 * GET /api/v1/sens_pose_view.php?id=123
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$id = (int)get_param('id', 0);
require_param($id, 'id');

$sql = "SELECT s.*, p.ref as projet_ref FROM ".MAIN_DB_PREFIX."mv3_sens_pose as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = s.fk_projet";
$sql .= " WHERE s.rowid = ".$id." AND s.entity IN (".getEntity('project').")";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) json_error('Non trouvé', 'NOT_FOUND', 404);

$obj = $db->fetch_object($resql);
json_ok(['sens_pose' => [
    'id' => $obj->rowid,
    'ref' => $obj->ref,
    'date_pose' => $obj->date_pose,
    'projet' => ['ref' => $obj->projet_ref],
    'pieces' => json_decode($obj->pieces_data ?? '[]', true),
    'status' => $obj->status
]]);
