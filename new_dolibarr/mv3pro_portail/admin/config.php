<?php
/**
 * Configuration module MV3 PRO
 * Redirige vers la page de configuration PWA complète
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

// Droits admin requis
if (!$user->admin) {
    accessforbidden();
}

// Redirection vers setup.php
header('Location: setup.php');
exit;
