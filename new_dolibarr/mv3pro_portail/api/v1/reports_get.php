<?php
/**
 * API Récupérer un rapport
 * GET /api/v1/reports_get.php?id=123
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

require_once '../../core/init.php';
require_once '../../lib/api.lib.php';
require_once '../../lib/upload.lib.php';
require_once '../../class/report.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// Charger infos projet
$project = null;
if ($report->fk_project > 0) {
    $project = new Project($db);
    $project->fetch($report->fk_project);
}

// Charger auteur
$author = new User($db);
$author->fetch($report->fk_user_author);

// Charger fichiers/photos
$files = mv3_list_files($report->id);

// Préparer lignes
$lines = array();
if (!empty($report->lines)) {
    foreach ($report->lines as $line) {
        $lines[] = array(
            'id' => (int)$line->id,
            'label' => $line->label,
            'description' => $line->description,
            'qty_minutes' => (int)$line->qty_minutes,
            'note' => $line->note,
            'sort_order' => (int)$line->sort_order
        );
    }
}

// Réponse
$data = array(
    'id' => (int)$report->id,
    'ref' => $report->ref,
    'project' => $project ? array(
        'id' => (int)$project->id,
        'ref' => $project->ref,
        'title' => $project->title
    ) : null,
    'author' => array(
        'id' => (int)$author->id,
        'name' => $author->getFullName($langs),
        'login' => $author->login
    ),
    'date_report' => $report->date_report,
    'time_start' => $report->time_start,
    'time_end' => $report->time_end,
    'duration_minutes' => (int)$report->duration_minutes,
    'note_public' => $report->note_public,
    'note_private' => $report->note_private,
    'status' => (int)$report->status,
    'status_label' => $report->getLibStatut(),
    'lines' => $lines,
    'files' => $files,
    'created_at' => $report->datec,
    'updated_at' => $report->tms
);

mv3_json_success($data);
