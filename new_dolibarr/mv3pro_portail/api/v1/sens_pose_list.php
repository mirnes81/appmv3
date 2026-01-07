<?php
/**
 * API v1 - Sens de Pose - Liste
 * GET /api/v1/sens_pose_list.php?limit=50&page=1
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$limit = (int)get_param('limit', 50);
$page = (int)get_param('page', 1);
if ($limit > 100) $limit = 100;
$offset = ($page - 1) * $limit;

$sql = "SELECT s.*, p.ref as projet_ref, p.title as projet_title,
        soc.nom as client_nom, u.lastname, u.firstname";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_sens_pose as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = s.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = s.fk_user";
$sql .= " WHERE s.entity IN (".getEntity('project').")";
$sql .= " ORDER BY s.date_creation DESC LIMIT ".$limit." OFFSET ".$offset;

$resql = $db->query($sql);
if (!$resql) json_error('Erreur BDD', 'DATABASE_ERROR', 500);

$list = [];
while ($obj = $db->fetch_object($resql)) {
    $list[] = [
        'id' => $obj->rowid,
        'ref' => $obj->ref,
        'date' => $obj->date_pose,
        'client' => $obj->client_nom,
        'projet' => ['id' => $obj->fk_projet, 'ref' => $obj->projet_ref],
        'status' => $obj->status
    ];
}

json_ok(['sens_pose' => $list, 'count' => count($list)]);
