<?php
/**
 * API Supprimer un rapport (admin only)
 * POST /api/v1/reports_delete.php?id=123
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

require_once '../../core/init.php';
require_once '../../lib/api.lib.php';
require_once '../../class/report.class.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auth admin requis
$current_user = mv3_check_admin();

// Paramètres
$id = GETPOST('id', 'int');

if (empty($id)) {
    mv3_json_error('Paramètre id requis', 400, 'MISSING_ID');
}

// Charger rapport
$report = new Report($db);
$result = $report->fetch($id);

if ($result <= 0) {
    mv3_json_error('Rapport introuvable', 404, 'NOT_FOUND');
}

// Supprimer fichiers associés
$upload_dir = $conf->mv3pro_portail->dir_output.'/report/'.$report->id;
if (dol_is_dir($upload_dir)) {
    dol_delete_dir_recursive($upload_dir);
}

// Supprimer rapport
$result = $report->delete($current_user);

if ($result < 0) {
    mv3_json_error($report->error ?: 'Erreur lors de la suppression', 500, 'DELETE_ERROR');
}

mv3_json_success(array('deleted' => true));
