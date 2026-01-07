<?php
/**
 * Initialisation commune pour tous les endpoints API
 * 
 * Désactive CSRF et charge Dolibarr en mode API
 * 
 * Usage:
 *   require_once __DIR__ . '/_init_api.php';
 */

// --- Dolibarr bootstrap for API (no CSRF, no session) ---
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);

// Charger Dolibarr
$res = 0;
if (!$res && file_exists(__DIR__ . "/../../main.inc.php")) {
    $res = @include __DIR__ . "/../../main.inc.php";
}
if (!$res && file_exists(__DIR__ . "/../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../main.inc.php";
}

if (!$res) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Impossible de charger Dolibarr']);
    exit;
}

// Classes communes
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

// Variables globales disponibles
global $db, $conf, $user, $langs;
