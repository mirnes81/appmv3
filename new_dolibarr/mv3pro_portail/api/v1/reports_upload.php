<?php
/**
 * API Upload photo pour un rapport
 * POST /api/v1/reports_upload.php?report_id=123
 * multipart/form-data : file
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auth
$current_user = mv3_check_auth();

// Paramètres
$report_id = GETPOST('report_id', 'int');

if (empty($report_id)) {
    mv3_json_error('Paramètre report_id requis', 400, 'MISSING_REPORT_ID');
}

// Vérifier fichier
if (empty($_FILES['file'])) {
    mv3_json_error('Aucun fichier fourni', 400, 'MISSING_FILE');
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

// Valider image
$error = '';
if (!mv3_validate_image($_FILES['file'], $error)) {
    mv3_json_error($error, 400, 'INVALID_FILE');
}

// Upload
$filename = mv3_upload_file($report->id, $_FILES['file'], $error);

if (!$filename) {
    mv3_json_error($error ?: 'Erreur lors de l\'upload', 500, 'UPLOAD_ERROR');
}

// URL document
$file_url = DOL_URL_ROOT.'/document.php?modulepart=mv3pro_portail&file=report/'.$report->id.'/'.urlencode($filename);

mv3_json_success(array(
    'filename' => $filename,
    'url' => $file_url
), 201);
