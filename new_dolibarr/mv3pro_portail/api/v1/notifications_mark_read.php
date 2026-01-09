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

// Vérifier que la notification appartient à l'utilisateur
$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql_check .= " WHERE rowid = ".$id." AND fk_user = ".$auth['user_id'];
$resql_check = $db->query($sql_check);

if (!$resql_check || $db->num_rows($resql_check) == 0) {
    json_error('Notification non trouvée', 404);
}

// Marquer comme lu
$sql = "UPDATE ".MAIN_DB_PREFIX."mv3_notifications SET statut = 'lu', date_lecture = NOW()";
$sql .= " WHERE rowid = ".$id." AND fk_user = ".$auth['user_id'];

$resql = $db->query($sql);

if (!$resql) {
    log_error('notifications_mark_read', 'SQL Error: '.$db->lasterror(), [
        'sql' => $sql,
        'notification_id' => $id
    ], $db->lasterror());
    json_error('Erreur lors du marquage de la notification', 500);
}

json_ok(['success' => true, 'marked_read' => $id]);
