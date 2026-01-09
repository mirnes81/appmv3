<?php
/**
 * API v1 - Notifications - Nombre non lues
 * GET /api/v1/notifications_unread_count.php
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

// Vérifier si la table existe
if (!mv3_table_exists($db, 'mv3_notifications')) {
    json_ok(['unread_count' => 0]);
}

$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql .= " WHERE fk_user = ".$auth['user_id']." AND statut = 'non_lu'";
$sql .= " AND entity IN (".getEntity('user').")";

$resql = $db->query($sql);
$count = 0;

if ($resql) {
    $obj = $db->fetch_object($resql);
    $count = (int)$obj->nb;
} else {
    log_error('notifications_unread_count', 'SQL Error: '.$db->lasterror(), [
        'sql' => $sql
    ], $db->lasterror());
}

json_ok(['unread_count' => $count]);
