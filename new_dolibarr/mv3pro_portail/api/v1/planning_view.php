<?php
/**
 * API v1 - Planning - Détail
 * GET /api/v1/planning_view.php?id=123
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$id = (int)get_param('id', 0);
require_param($id, 'id');

$sql = "SELECT a.*, p.ref as projet_ref, p.title as projet_title";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = a.fk_project";
$sql .= " WHERE a.id = ".$id;
$sql .= " AND a.entity IN (".getEntity('agenda').")";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) json_error('Non trouvé', 'NOT_FOUND', 404);

$obj = $db->fetch_object($resql);
json_ok(['event' => [
    'id' => $obj->id,
    'label' => $obj->label,
    'date_start' => $obj->datep,
    'date_end' => $obj->datep2,
    'location' => $obj->location,
    'note' => $obj->note,
    'projet' => ['ref' => $obj->projet_ref, 'title' => $obj->projet_title]
]]);
