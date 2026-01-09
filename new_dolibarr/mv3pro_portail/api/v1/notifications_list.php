<?php
/**
 * API v1 - Notifications - Liste
 * GET /api/v1/notifications_list.php?limit=50
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$limit = (int)get_param('limit', 50);

// Vérifier si la table existe
$table_check = $db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."mv3_notifications'");
if (!$table_check || $db->num_rows($table_check) == 0) {
    // Table n'existe pas, retourner liste vide
    json_ok(['notifications' => [], 'count' => 0]);
}

$sql = "SELECT rowid, fk_user, type, titre, message, fk_object, object_type, statut, date_creation, date_lecture, entity FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql .= " WHERE fk_user = ".$auth['user_id'];
$sql .= " AND entity IN (".getEntity('user').")";
$sql .= " ORDER BY date_creation DESC LIMIT ".$limit;

$resql = $db->query($sql);
if (!$resql) {
    $error_msg = 'Erreur lors de la récupération des notifications';
    if ($db->lasterror()) {
        $error_msg .= ': ' . $db->lasterror();
    }
    error_log('[MV3 Notifications] SQL Error: ' . $error_msg);
    error_log('[MV3 Notifications] SQL Query: ' . $sql);
    json_error($error_msg . ' | SQL: ' . $sql, 'DATABASE_ERROR', 500);
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
