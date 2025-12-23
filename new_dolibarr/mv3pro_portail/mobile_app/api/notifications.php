<?php
/**
 * API Notifications pour mobile app
 * GET: Liste des notifications
 * POST: Marquer comme lu
 * DELETE: Supprimer notification
 */

define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";

if (!$res) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot load Dolibarr']);
    exit;
}

header('Content-Type: application/json');

// Vérifier authentification
if (!$user->rights->mv3pro_portail->read) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = GETPOST('action', 'alpha');

try {
    switch($method) {
        case 'GET':
            if ($action == 'count') {
                // Compter notifications non lues
                echo json_encode(getUnreadCount($db, $user));
            } else {
                // Liste des notifications
                $statut = GETPOST('statut', 'alpha') ?: 'non_lu';
                $limit = GETPOST('limit', 'int') ?: 50;
                echo json_encode(getNotifications($db, $user, $statut, $limit));
            }
            break;

        case 'POST':
            if ($action == 'mark_read') {
                // Marquer comme lu
                $id = GETPOST('id', 'int');
                echo json_encode(markAsRead($db, $user, $id));
            } elseif ($action == 'mark_all_read') {
                // Tout marquer comme lu
                echo json_encode(markAllAsRead($db, $user));
            }
            break;

        case 'DELETE':
            // Supprimer notification
            $id = GETPOST('id', 'int');
            echo json_encode(deleteNotification($db, $user, $id));
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Récupérer les notifications
 */
function getNotifications($db, $user, $statut, $limit)
{
    $sql = "SELECT n.rowid, n.type, n.titre, n.message, n.fk_object, n.object_type,";
    $sql .= " n.statut, n.date_creation, n.date_lecture";
    $sql .= " FROM ".MAIN_DB_PREFIX."mv3_notifications n";
    $sql .= " WHERE n.fk_user = ".(int)$user->id;
    $sql .= " AND n.entity IN (".getEntity('mv3_notifications').")";

    if ($statut && $statut != 'all') {
        $sql .= " AND n.statut = '".$db->escape($statut)."'";
    }

    $sql .= " ORDER BY n.date_creation DESC";
    $sql .= " LIMIT ".(int)$limit;

    $resql = $db->query($sql);
    if (!$resql) {
        throw new Exception($db->lasterror());
    }

    $notifications = array();
    while ($obj = $db->fetch_object($resql)) {
        $notifications[] = array(
            'id' => $obj->rowid,
            'type' => $obj->type,
            'titre' => $obj->titre,
            'message' => $obj->message,
            'fk_object' => $obj->fk_object,
            'object_type' => $obj->object_type,
            'statut' => $obj->statut,
            'date_creation' => $obj->date_creation,
            'date_lecture' => $obj->date_lecture,
            'time_ago' => getTimeAgo($obj->date_creation)
        );
    }

    return array(
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    );
}

/**
 * Compter notifications non lues
 */
function getUnreadCount($db, $user)
{
    $sql = "SELECT COUNT(*) as nb";
    $sql .= " FROM ".MAIN_DB_PREFIX."mv3_notifications";
    $sql .= " WHERE fk_user = ".(int)$user->id;
    $sql .= " AND statut = 'non_lu'";
    $sql .= " AND entity IN (".getEntity('mv3_notifications').")";

    $resql = $db->query($sql);
    if (!$resql) {
        throw new Exception($db->lasterror());
    }

    $obj = $db->fetch_object($resql);

    return array(
        'success' => true,
        'count' => (int)$obj->nb
    );
}

/**
 * Marquer comme lu
 */
function markAsRead($db, $user, $id)
{
    // Vérifier que la notification appartient à l'utilisateur
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_notifications";
    $sql .= " WHERE rowid = ".(int)$id;
    $sql .= " AND fk_user = ".(int)$user->id;

    $resql = $db->query($sql);
    if (!$resql || $db->num_rows($resql) == 0) {
        throw new Exception('Notification non trouvée');
    }

    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_notifications";
    $sql .= " SET statut = 'lu', date_lecture = NOW()";
    $sql .= " WHERE rowid = ".(int)$id;
    $sql .= " AND fk_user = ".(int)$user->id;

    if (!$db->query($sql)) {
        throw new Exception($db->lasterror());
    }

    return array('success' => true, 'message' => 'Marqué comme lu');
}

/**
 * Marquer tout comme lu
 */
function markAllAsRead($db, $user)
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_notifications";
    $sql .= " SET statut = 'lu', date_lecture = NOW()";
    $sql .= " WHERE fk_user = ".(int)$user->id;
    $sql .= " AND statut = 'non_lu'";
    $sql .= " AND entity IN (".getEntity('mv3_notifications').")";

    if (!$db->query($sql)) {
        throw new Exception($db->lasterror());
    }

    return array('success' => true, 'message' => 'Toutes marquées comme lues');
}

/**
 * Supprimer notification
 */
function deleteNotification($db, $user, $id)
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_notifications";
    $sql .= " WHERE rowid = ".(int)$id;
    $sql .= " AND fk_user = ".(int)$user->id;

    if (!$db->query($sql)) {
        throw new Exception($db->lasterror());
    }

    return array('success' => true, 'message' => 'Notification supprimée');
}

/**
 * Calculer le temps écoulé (il y a X minutes/heures)
 */
function getTimeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return "À l'instant";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "Il y a ".$mins." min";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Il y a ".$hours." h";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Il y a ".$days." jour".($days > 1 ? 's' : '');
    } else {
        return date('d.m.Y', $timestamp);
    }
}
