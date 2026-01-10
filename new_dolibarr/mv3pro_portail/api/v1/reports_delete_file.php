<?php
/**
 * API Supprimer une photo d'un rapport
 * POST /api/v1/reports_delete_file.php?report_id=123&filename=photo.jpg
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

require_once '../../core/init.php';
require_once '../../lib/api.lib.php';
require_once '../../lib/upload.lib.php';
require_once '../../class/report.class.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auth
$current_user = mv3_check_auth();

// Paramètres
$report_id = GETPOST('report_id', 'int');
$filename = GETPOST('filename', 'alpha');

if (empty($report_id)) {
    mv3_json_error('Paramètre report_id requis', 400, 'MISSING_REPORT_ID');
}

if (empty($filename)) {
    mv3_json_error('Paramètre filename requis', 400, 'MISSING_FILENAME');
}

// Charger rapport
$report = new Report($db);
$result = $report->fetch($report_id);

if ($result <= 0) {
    mv3_json_error('Rapport introuvable', 404, 'NOT_FOUND');
}

// Vérifier droits
if (empty($current_user->admin) && $report->fk_user_author != $current_user->id) {
    mv3_json_error('Accès refusé', 403, 'FORBIDDEN');
}

// Supprimer fichier
$error = '';
$result = mv3_delete_file($report->id, $filename, $error);

if (!$result) {
    mv3_json_error($error ?: 'Erreur lors de la suppression', 500, 'DELETE_ERROR');
}

mv3_json_success(array('deleted' => true));
