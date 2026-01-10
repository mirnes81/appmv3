<?php
/**
 * API Soumettre un rapport (changer statut)
 * POST /api/v1/reports_submit.php?id=123&status=1
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auth
$current_user = mv3_check_auth();

// Paramètres
$id = GETPOST('id', 'int');
$status = GETPOST('status', 'int');

if (empty($id)) {
    mv3_json_error('Paramètre id requis', 400, 'MISSING_ID');
}

if (!isset($status)) {
    mv3_json_error('Paramètre status requis', 400, 'MISSING_STATUS');
}

// Charger rapport
$report = new Report($db);
$result = $report->fetch($id);

if ($result <= 0) {
    mv3_json_error('Rapport introuvable', 404, 'NOT_FOUND');
}

// Vérifier droits
if (empty($current_user->admin) && $report->fk_user_author != $current_user->id) {
    mv3_json_error('Accès refusé', 403, 'FORBIDDEN');
}

// Validation statut
if ($status == Report::STATUS_VALIDATED && empty($current_user->admin)) {
    mv3_json_error('Seuls les administrateurs peuvent valider', 403, 'ADMIN_REQUIRED');
}

// Changer statut
$result = $report->setStatus($status, $current_user);

if ($result < 0) {
    mv3_json_error($report->error ?: 'Erreur lors du changement de statut', 500, 'STATUS_ERROR');
}

mv3_json_success(array(
    'id' => (int)$report->id,
    'ref' => $report->ref,
    'status' => (int)$report->status,
    'status_label' => $report->getLibStatut()
));
