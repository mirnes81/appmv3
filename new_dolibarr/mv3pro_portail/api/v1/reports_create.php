<?php
/**
 * API Créer un rapport
 * POST /api/v1/reports_create.php
 * Body: {project_id, date_report, time_start, time_end, duration_minutes, note_public, lines}
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

require_once '../../core/init.php';
require_once '../../lib/api.lib.php';
require_once '../../class/report.class.php';
require_once '../../class/reportline.class.php';

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

// Body JSON
$data = mv3_get_json_body();

// Validation
mv3_require_fields($data, array('date_report'));

// Créer rapport
$report = new Report($db);

$report->fk_project = !empty($data['project_id']) ? (int)$data['project_id'] : null;
$report->fk_user_author = $current_user->id;
$report->fk_user_assigned = !empty($data['user_assigned_id']) ? (int)$data['user_assigned_id'] : null;

$report->date_report = strtotime($data['date_report']);

if (!empty($data['time_start'])) {
    $report->time_start = strtotime($data['time_start']);
}

if (!empty($data['time_end'])) {
    $report->time_end = strtotime($data['time_end']);
}

$report->duration_minutes = !empty($data['duration_minutes']) ? (int)$data['duration_minutes'] : null;
$report->note_public = !empty($data['note_public']) ? $data['note_public'] : '';
$report->note_private = !empty($data['note_private']) ? $data['note_private'] : '';
$report->status = !empty($data['status']) ? (int)$data['status'] : Report::STATUS_DRAFT;

// Lignes
if (!empty($data['lines']) && is_array($data['lines'])) {
    foreach ($data['lines'] as $idx => $line_data) {
        if (empty($line_data['label'])) continue;

        $line = new ReportLine($db);
        $line->label = $line_data['label'];
        $line->description = !empty($line_data['description']) ? $line_data['description'] : '';
        $line->qty_minutes = !empty($line_data['qty_minutes']) ? (int)$line_data['qty_minutes'] : null;
        $line->note = !empty($line_data['note']) ? $line_data['note'] : '';
        $line->sort_order = $idx;

        $report->lines[] = $line;
    }
}

// Créer
$result = $report->create($current_user);

if ($result < 0) {
    mv3_json_error($report->error ?: 'Erreur lors de la création', 500, 'CREATE_ERROR');
}

// Charger rapport complet
$report->fetch($result);

mv3_json_success(array(
    'id' => (int)$report->id,
    'ref' => $report->ref,
    'status' => (int)$report->status
), 201);
