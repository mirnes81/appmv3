<?php
/**
 * API v1 - Notifications - Nombre non lues
 * GET /api/v1/notifications_unread_count.php
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql .= " WHERE fk_user = ".$auth['user_id']." AND is_read = 0";
$sql .= " AND entity IN (".getEntity('user').")";

$resql = $db->query($sql);
$count = 0;

if ($resql) {
    $obj = $db->fetch_object($resql);
    $count = (int)$obj->nb;
}

json_ok(['unread_count' => $count]);
