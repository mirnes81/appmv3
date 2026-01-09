<?php
/**
 * API v1 - Notifications - Marquer comme lu
 * PUT /api/v1/notifications_read.php?id=123
 * PUT /api/v1/notifications_read.php?ids=123,456,789
 * PUT /api/v1/notifications_read.php?all=1
 *
 * Marque une ou plusieurs notifications comme lues
 *
 * Paramètres:
 * - id (int) : ID d'une notification unique
 * - ids (string) : Liste d'IDs séparés par virgule (ex: "1,2,3")
 * - all (int) : Si = 1, marque toutes les notifications non lues comme lues
 *
 * Permissions:
 * - L'utilisateur ne peut marquer que ses propres notifications
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('PUT');

// Vérifier si la table existe
if (!mv3_table_exists($db, 'mv3_notifications')) {
    json_error('Table notifications non créée', 500);
}

// Récupérer les paramètres
$id = (int)get_param('id', 0);
$ids = get_param('ids', '');
$mark_all = (int)get_param('all', 0);

$notification_ids = [];

// Cas 1 : Marquer une seule notification
if ($id > 0) {
    $notification_ids[] = $id;
}
// Cas 2 : Marquer plusieurs notifications
elseif (!empty($ids)) {
    $ids_array = explode(',', $ids);
    foreach ($ids_array as $single_id) {
        $single_id = (int)trim($single_id);
        if ($single_id > 0) {
            $notification_ids[] = $single_id;
        }
    }
}
// Cas 3 : Marquer toutes les notifications non lues
elseif ($mark_all === 1) {
    // Récupérer tous les IDs des notifications non lues de l'utilisateur
    $sql_get_ids = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_notifications";
    $sql_get_ids .= " WHERE fk_user = ".$auth['user_id'];
    $sql_get_ids .= " AND statut = 'non_lu'";
    $sql_get_ids .= " AND entity IN (".getEntity('user').")";

    $resql_ids = $db->query($sql_get_ids);
    if ($resql_ids) {
        while ($obj_id = $db->fetch_object($resql_ids)) {
            $notification_ids[] = (int)$obj_id->rowid;
        }
    }
}

// Vérifier qu'on a au moins un ID
if (empty($notification_ids)) {
    json_error('Aucune notification à marquer', 400);
}

// Vérifier que toutes les notifications appartiennent à l'utilisateur
$ids_list = implode(',', $notification_ids);
$sql_check = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql_check .= " WHERE rowid IN (".$ids_list.")";
$sql_check .= " AND fk_user = ".$auth['user_id'];

$resql_check = $db->query($sql_check);
if (!$resql_check) {
    log_error('notifications_read', 'SQL Error: '.$db->lasterror(), [
        'sql' => $sql_check,
        'ids' => $ids_list
    ], $db->lasterror());
    json_error('Erreur lors de la vérification des notifications', 500);
}

$obj_check = $db->fetch_object($resql_check);
$found_count = (int)$obj_check->nb;

if ($found_count === 0) {
    json_error('Aucune notification trouvée', 404);
}

if ($found_count !== count($notification_ids)) {
    json_error('Certaines notifications ne vous appartiennent pas', 403);
}

// Marquer comme lu
$sql = "UPDATE ".MAIN_DB_PREFIX."mv3_notifications";
$sql .= " SET statut = 'lu', date_lecture = NOW()";
$sql .= " WHERE rowid IN (".$ids_list.")";
$sql .= " AND fk_user = ".$auth['user_id'];

$resql = $db->query($sql);

if (!$resql) {
    log_error('notifications_read', 'SQL Error: '.$db->lasterror(), [
        'sql' => $sql,
        'ids' => $ids_list
    ], $db->lasterror());
    json_error('Erreur lors du marquage des notifications', 500);
}

$affected_rows = $db->affected_rows($resql);

// Récupérer le nouveau count des non lues
$sql_unread = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql_unread .= " WHERE fk_user = ".$auth['user_id']." AND statut = 'non_lu'";
$sql_unread .= " AND entity IN (".getEntity('user').")";

$resql_unread = $db->query($sql_unread);
$new_unread_count = 0;
if ($resql_unread) {
    $obj_unread = $db->fetch_object($resql_unread);
    $new_unread_count = (int)$obj_unread->nb;
}

json_ok([
    'success' => true,
    'marked_count' => $affected_rows,
    'notification_ids' => $notification_ids,
    'new_unread_count' => $new_unread_count,
    'message' => $affected_rows > 1 ? $affected_rows.' notifications marquées comme lues' : 'Notification marquée comme lue'
]);
