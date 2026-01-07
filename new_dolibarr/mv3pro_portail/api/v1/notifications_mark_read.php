<?php
/**
 * API v1 - Notifications - Marquer lu
 * POST /api/v1/notifications_mark_read.php?id=123
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('POST');

$id = (int)get_param('id', 0);
require_param($id, 'id');

$sql = "UPDATE ".MAIN_DB_PREFIX."mv3_notifications SET is_read = 1, date_read = NOW()";
$sql .= " WHERE rowid = ".$id." AND fk_user = ".$auth['user_id'];

$db->query($sql);
json_ok(['success' => true, 'marked_read' => $id]);
