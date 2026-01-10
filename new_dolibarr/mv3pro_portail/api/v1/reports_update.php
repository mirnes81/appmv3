<?php
/**
 * API Mettre à jour un rapport
 * POST /api/v1/reports_update.php?id=123
 * Body: {project_id, date_report, time_start, time_end, duration_minutes, note_public, ...}
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
header('Access-Control-Allow-Methods: POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auth
$current_user = mv3_check_auth();

// Paramètres
$id = GETPOST('id', 'int');

if (empty($id)) {
    mv3_json_error('Paramètre id requis', 400, 'MISSING_ID');
}

// Body JSON
$data = mv3_get_json_body();

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

// Empêcher modification si validé (sauf admin)
if ($report->status == Report::STATUS_VALIDATED && empty($current_user->admin)) {
    mv3_json_error('Impossible de modifier un rapport validé', 403, 'REPORT_VALIDATED');
}

// Mettre à jour champs
if (isset($data['project_id'])) {
    $report->fk_project = !empty($data['project_id']) ? (int)$data['project_id'] : null;
}

if (isset($data['date_report'])) {
    $report->date_report = strtotime($data['date_report']);
}

if (isset($data['time_start'])) {
    $report->time_start = !empty($data['time_start']) ? strtotime($data['time_start']) : null;
}

if (isset($data['time_end'])) {
    $report->time_end = !empty($data['time_end']) ? strtotime($data['time_end']) : null;
}

if (isset($data['duration_minutes'])) {
    $report->duration_minutes = !empty($data['duration_minutes']) ? (int)$data['duration_minutes'] : null;
}

if (isset($data['note_public'])) {
    $report->note_public = $data['note_public'];
}

if (isset($data['note_private'])) {
    $report->note_private = $data['note_private'];
}

// Mettre à jour
$result = $report->update($current_user);

if ($result < 0) {
    mv3_json_error($report->error ?: 'Erreur lors de la mise à jour', 500, 'UPDATE_ERROR');
}

mv3_json_success(array(
    'id' => (int)$report->id,
    'ref' => $report->ref,
    'status' => (int)$report->status
));
