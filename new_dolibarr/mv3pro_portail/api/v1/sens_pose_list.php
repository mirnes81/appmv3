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

// Vérifier si la table existe
$table_check = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."mv3_sens_pose'");
if (!$table_check || $db->num_rows($table_check) == 0) {
    // Table n'existe pas, retourner liste vide
    json_ok(['sens_pose' => [], 'count' => 0]);
}

$sql = "SELECT s.rowid, s.ref, s.fk_projet, s.fk_client, s.client_name, s.statut, s.date_creation, s.signature_date,
        p.ref as projet_ref, p.title as projet_title,
        soc.nom as client_nom, u.lastname, u.firstname";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_sens_pose as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = s.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_client";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = s.fk_user_create";
$sql .= " WHERE s.entity IN (".getEntity('project').")";
$sql .= " ORDER BY s.date_creation DESC LIMIT ".$limit." OFFSET ".$offset;

$resql = $db->query($sql);
if (!$resql) {
    $error_msg = 'Erreur lors de la récupération des sens de pose';
    if ($db->lasterror()) {
        $error_msg .= ': ' . $db->lasterror();
    }
    error_log('[MV3 Sens Pose] SQL Error: ' . $error_msg);
    error_log('[MV3 Sens Pose] SQL Query: ' . $sql);
    json_error($error_msg . ' | SQL: ' . $sql, 'DATABASE_ERROR', 500);
}

$list = [];
while ($obj = $db->fetch_object($resql)) {
    $list[] = [
        'id' => (int)$obj->rowid,
        'ref' => $obj->ref,
        'date' => $obj->date_creation,
        'client' => $obj->client_nom ?: $obj->client_name,
        'projet' => ['id' => $obj->fk_projet, 'ref' => $obj->projet_ref, 'title' => $obj->projet_title],
        'status' => $obj->statut,
        'signature_date' => $obj->signature_date,
        'user' => trim($obj->firstname . ' ' . $obj->lastname)
    ];
}

json_ok(['sens_pose' => $list, 'count' => count($list)]);
