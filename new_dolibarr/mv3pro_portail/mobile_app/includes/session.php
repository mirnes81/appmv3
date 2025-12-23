<?php
/**
 * Session management for mobile app
 */

define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

function checkMobileSession() {
    if (!isset($_SESSION['dol_login'])) {
        header('Location: /custom/mv3pro_portail/mobile_app/index.php');
        exit;
    }
    return $_SESSION['dol_login'];
}

function getMobileUserId() {
    global $user;
    return $user->id ?? null;
}

function getMobileUsername() {
    global $user;
    return $user->login ?? '';
}
