<?php
/**
 * Marquer une notification comme lue/traitée
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$back = GETPOST('back', 'alpha');

if (!$id) {
    header('Location: index.php');
    exit;
}

$user_id = $user->id;

// Vérifier que la notification appartient à l'utilisateur
$sql_check = "SELECT rowid, fk_object, object_type, statut
              FROM ".MAIN_DB_PREFIX."mv3_notifications
              WHERE rowid = ".(int)$id."
              AND fk_user = ".(int)$user_id;
$resql_check = $db->query($sql_check);

if (!$resql_check || $db->num_rows($resql_check) == 0) {
    header('Location: index.php');
    exit;
}

$notif = $db->fetch_object($resql_check);

// Marquer comme lu ou traité
$new_status = 'lu';
if ($action == 'traite') {
    $new_status = 'traite';
}

$sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_notifications
               SET statut = '".$db->escape($new_status)."',
                   date_lecture = NOW()
               WHERE rowid = ".(int)$id;

$db->query($sql_update);

// Rediriger
$redirect = 'index.php';

if ($back == 'dashboard') {
    $redirect = '../dashboard.php';
} elseif ($notif->fk_object && $notif->object_type) {
    // Rediriger vers l'objet lié
    if ($notif->object_type == 'rapport') {
        $redirect = '../rapports/view.php?id='.(int)$notif->fk_object;
    } elseif ($notif->object_type == 'materiel') {
        $redirect = '../materiel/view.php?id='.(int)$notif->fk_object;
    } elseif ($notif->object_type == 'planning') {
        $redirect = '../planning/index.php';
    }
}

header('Location: '.$redirect);
exit;
