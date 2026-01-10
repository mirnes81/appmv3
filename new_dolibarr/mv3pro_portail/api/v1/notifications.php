<?php
/**
 * API v1 - Notifications
 * GET /api/v1/notifications.php?limit=50&status=non_lu
 *
 * Retourne la liste des notifications de l'utilisateur connecté
 * Utilise la même logique que /custom/mv3pro_portail/notifications/list.php
 *
 * Paramètres:
 * - limit (int) : Nombre max de notifications (défaut: 50)
 * - status (string) : Filtrer par statut (non_lu, lu, traite, reporte) (optionnel)
 *
 * Permissions:
 * - Employé : voit uniquement ses notifications
 * - Admin : voit toutes les notifications ou filtre optionnel par user_id
 */

require_once __DIR__.'/_bootstrap.php';
require_once __DIR__ . '/../../core/init.php';

$auth = require_auth();
require_method('GET');

// Récupérer ID Dolibarr et statut admin
$dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
$is_admin = mv3_is_admin($auth);

// Paramètres
$limit = (int)get_param('limit', 50);
$status = get_param('status', null);
$user_id_filter = (int)get_param('user_id', 0);

// Limite max
if ($limit > 500) {
    $limit = 500;
}

// Vérifier si la table existe
if (!mv3_table_exists($db, 'mv3_notifications')) {
    json_ok([
        'notifications' => [],
        'count' => 0,
        'total_unread' => 0,
        'message' => 'Table notifications non créée'
    ]);
}

// Construction de la requête SQL
$sql = "SELECT rowid, fk_user, type, titre, message, fk_object, object_type, statut, date_creation, date_lecture, entity";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql .= " WHERE 1=1";

// Filtrage par utilisateur (admin voit tout ou filtre, employé voit ses notifications)
$user_filter = mv3_get_user_filter_sql($auth, 'fk_user', $user_id_filter);
if (!empty($user_filter)) {
    $sql .= " AND " . $user_filter;
}

// Filtrage par statut
if ($status && in_array($status, ['non_lu', 'lu', 'traite', 'reporte'])) {
    $sql .= " AND statut = '".$db->escape($status)."'";
}

// Multi-entity
$sql .= " AND entity IN (".getEntity('user').")";

// Ordre et limite
$sql .= " ORDER BY date_creation DESC";
$sql .= " LIMIT ".$limit;

// Exécution
$resql = $db->query($sql);

if (!$resql) {
    $error_msg = 'Erreur lors de la récupération des notifications';
    $sql_error = $db->lasterror();

    log_error('notifications', $error_msg, [
        'sql' => $sql,
        'user_id' => $auth['user_id']
    ], $sql_error);

    json_error($error_msg, 500);
}

// Construction de la liste
$list = [];
while ($obj = $db->fetch_object($resql)) {
    $is_read = ($obj->statut === 'non_lu') ? 0 : 1;

    // Construction de l'URL selon le type d'objet
    $url = null;
    if ($obj->fk_object && $obj->object_type) {
        switch ($obj->object_type) {
            case 'rapport':
            case 'rapports':
                $url = "#/rapports/".$obj->fk_object;
                break;
            case 'planning':
            case 'actioncomm':
                $url = "#/planning/".$obj->fk_object;
                break;
            case 'materiel':
                $url = "#/materiel/".$obj->fk_object;
                break;
            case 'regie':
                $url = "#/regie/".$obj->fk_object;
                break;
            case 'sens_pose':
                $url = "#/sens-pose/".$obj->fk_object;
                break;
            default:
                $url = null;
        }
    }

    $list[] = [
        'id' => (int)$obj->rowid,
        'user_id' => (int)$obj->fk_user,
        'type' => $obj->type,
        'titre' => $obj->titre,
        'message' => $obj->message,
        'date' => $obj->date_creation,
        'date_lecture' => $obj->date_lecture,
        'is_read' => $is_read,
        'statut' => $obj->statut,
        'object_id' => $obj->fk_object ? (int)$obj->fk_object : null,
        'object_type' => $obj->object_type,
        'url' => $url,
        'icon' => get_notification_icon($obj->type),
        'color' => get_notification_color($obj->type)
    ];
}

// Compter les non lues (pour l'utilisateur connecté)
$sql_unread = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mv3_notifications";
$sql_unread .= " WHERE fk_user = ".$auth['user_id']." AND statut = 'non_lu'";
$sql_unread .= " AND entity IN (".getEntity('user').")";

$resql_unread = $db->query($sql_unread);
$total_unread = 0;
if ($resql_unread) {
    $obj_unread = $db->fetch_object($resql_unread);
    $total_unread = (int)$obj_unread->nb;
}

json_ok([
    'notifications' => $list,
    'count' => count($list),
    'total_unread' => $total_unread,
    'limit' => $limit,
    'filters' => [
        'status' => $status,
        'user_id' => $user_id_filter > 0 ? $user_id_filter : null
    ]
]);

/**
 * Retourne l'icône correspondant au type de notification
 */
function get_notification_icon($type) {
    $icons = [
        'rapport_new' => 'file-text',
        'rapport_validated' => 'check-circle',
        'rapport_rejected' => 'x-circle',
        'materiel_low' => 'alert-triangle',
        'materiel_empty' => 'alert-circle',
        'planning_new' => 'calendar',
        'planning_updated' => 'calendar',
        'planning_cancelled' => 'x',
        'message' => 'message-circle',
        'info' => 'info',
        'warning' => 'alert-triangle',
        'error' => 'alert-circle',
        'success' => 'check-circle'
    ];

    return $icons[$type] ?? 'bell';
}

/**
 * Retourne la couleur correspondant au type de notification
 */
function get_notification_color($type) {
    $colors = [
        'rapport_new' => 'blue',
        'rapport_validated' => 'green',
        'rapport_rejected' => 'red',
        'materiel_low' => 'orange',
        'materiel_empty' => 'red',
        'planning_new' => 'blue',
        'planning_updated' => 'blue',
        'planning_cancelled' => 'red',
        'message' => 'blue',
        'info' => 'blue',
        'warning' => 'orange',
        'error' => 'red',
        'success' => 'green'
    ];

    return $colors[$type] ?? 'gray';
}
