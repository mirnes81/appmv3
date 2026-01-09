<?php
/**
 * API v1 - Notifications - Liste
 * GET /api/v1/notifications_list.php?limit=50
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$limit = (int)get_param('limit', 50);

// Vérifier si la table existe (retourne liste vide si absent)
if (!mv3_table_exists($db, 'mv3_notifications')) {
    json_ok(['notifications' => [], 'count' => 0]);
}

$sql = "SELECT rowid, fk_user, type, titre, message, fk_object, object_type, statut, date_creation, date_lecture, entity FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql .= " WHERE fk_user = ".$auth['user_id'];
$sql .= " AND entity IN (".getEntity('user').")";
$sql .= " ORDER BY date_creation DESC LIMIT ".$limit;

$resql = $db->query($sql);
if (!$resql) {
    $error_msg = 'Erreur lors de la récupération des notifications';
    $db_error = $db->lasterror();
    if ($db_error) {
        $error_msg .= ': ' . $db_error;
    }
    error_log('[MV3 Notifications] SQL Error: ' . $error_msg);
    error_log('[MV3 Notifications] SQL Query: ' . $sql);

    // Retourner un tableau vide plutôt qu'une erreur
    json_ok(['notifications' => [], 'count' => 0]);
}

$list = [];
while ($obj = $db->fetch_object($resql)) {
    $list[] = [
        'id' => (int)$obj->rowid,
        'type' => $obj->type,
        'titre' => $obj->titre,
        'message' => $obj->message,
        'date' => $obj->date_creation,
        'is_read' => ($obj->statut !== 'non_lu') ? 1 : 0,
        'statut' => $obj->statut,
        'url' => $obj->fk_object ? "/custom/mv3pro_portail/mobile_app/".$obj->object_type."/view.php?id=".$obj->fk_object : null
    ];
}

json_ok(['notifications' => $list, 'count' => count($list)]);
