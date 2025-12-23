<?php
/**
 * Supprimer un bon de régie - Version mobile
 */

require_once '../includes/session.php';
require_once '../../regie/class/regie.class.php';

global $db, $user, $conf;

checkMobileSession();

$id = GETPOST('id', 'int');

$regie = new Regie($db);
$result = $regie->fetch($id);

if ($result <= 0) {
    header('Location: list.php');
    exit;
}

if ($regie->status != 0) {
    header('Location: view.php?id='.$id);
    exit;
}

$result = $regie->delete($user);

if ($result > 0) {
    header('Location: list.php?deleted=1');
} else {
    header('Location: view.php?id='.$id.'&error=delete_failed');
}
exit;
