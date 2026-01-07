<?php
/**
 * API v1 - Notifications - Liste
 * GET /api/v1/notifications_list.php?limit=50
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$limit = (int)get_param('limit', 50);

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql .= " WHERE fk_user = ".$auth['user_id'];
$sql .= " AND entity IN (".getEntity('user').")";
$sql .= " ORDER BY date_notif DESC LIMIT ".$limit;

$resql = $db->query($sql);
if (!$resql) json_error('Erreur BDD', 'DATABASE_ERROR', 500);

$list = [];
while ($obj = $db->fetch_object($resql)) {
    $list[] = [
        'id' => $obj->rowid,
        'type' => $obj->type,
        'message' => $obj->message,
        'date' => $obj->date_notif,
        'is_read' => (int)$obj->is_read,
        'url' => $obj->target_url
    ];
}

json_ok(['notifications' => $list, 'count' => count($list)]);
