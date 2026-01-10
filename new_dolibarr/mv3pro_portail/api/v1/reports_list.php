<?php
/**
 * API Liste des rapports
 * GET /api/v1/reports_list.php?project_id=&date_from=&date_to=&status=&user_id=
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

require_once '../../core/init.php';
require_once '../../lib/api.lib.php';
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
$project_id = GETPOST('project_id', 'int');
$date_from = GETPOST('date_from', 'alpha');
$date_to = GETPOST('date_to', 'alpha');
$status = GETPOST('status', 'int');
$user_id = GETPOST('user_id', 'int');
$limit = GETPOST('limit', 'int') ?: 100;
$offset = GETPOST('offset', 'int') ?: 0;

// Requête
$sql = "SELECT r.rowid, r.ref, r.fk_project, r.fk_user_author, r.fk_user_assigned,";
$sql .= " r.date_report, r.time_start, r.time_end, r.duration_minutes,";
$sql .= " r.status, r.datec";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_report as r";
$sql .= " WHERE r.entity IN (".getEntity('mv3_report').")";

// Filtres
if ($project_id > 0) {
    $sql .= " AND r.fk_project = ".(int)$project_id;
}

if (!empty($date_from)) {
    $sql .= " AND r.date_report >= '".$db->escape($date_from)."'";
}

if (!empty($date_to)) {
    $sql .= " AND r.date_report <= '".$db->escape($date_to)."'";
}

if ($status !== '') {
    $sql .= " AND r.status = ".(int)$status;
}

if ($user_id > 0) {
    $sql .= " AND r.fk_user_author = ".(int)$user_id;
} elseif (empty($current_user->admin)) {
    // Non-admin : voir seulement ses rapports
    $sql .= " AND r.fk_user_author = ".(int)$current_user->id;
}

$sql .= " ORDER BY r.date_report DESC, r.rowid DESC";
$sql .= $db->plimit($limit, $offset);

$resql = $db->query($sql);

if (!$resql) {
    mv3_json_error($db->lasterror(), 500, 'DB_ERROR');
}

$reports = array();
$num = $db->num_rows($resql);

for ($i = 0; $i < $num; $i++) {
    $obj = $db->fetch_object($resql);

    // Projet
    $project_ref = '';
    $project_title = '';
    if ($obj->fk_project > 0) {
        $project = new Project($db);
        if ($project->fetch($obj->fk_project) > 0) {
            $project_ref = $project->ref;
            $project_title = $project->title;
        }
    }

    // Auteur
    $author_name = '';
    if ($obj->fk_user_author > 0) {
        $u = new User($db);
        if ($u->fetch($obj->fk_user_author) > 0) {
            $author_name = $u->getFullName($langs);
        }
    }

    $reports[] = array(
        'id' => (int)$obj->rowid,
        'ref' => $obj->ref,
        'project_id' => (int)$obj->fk_project,
        'project_ref' => $project_ref,
        'project_title' => $project_title,
        'author_id' => (int)$obj->fk_user_author,
        'author_name' => $author_name,
        'date_report' => $db->jdate($obj->date_report),
        'duration_minutes' => (int)$obj->duration_minutes,
        'status' => (int)$obj->status,
        'status_label' => Report::getLibStatut((int)$obj->status),
        'created_at' => $db->jdate($obj->datec)
    );
}

// Count total
$sql_count = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."mv3_report as r";
$sql_count .= " WHERE r.entity IN (".getEntity('mv3_report').")";
if ($project_id > 0) $sql_count .= " AND r.fk_project = ".(int)$project_id;
if (!empty($date_from)) $sql_count .= " AND r.date_report >= '".$db->escape($date_from)."'";
if (!empty($date_to)) $sql_count .= " AND r.date_report <= '".$db->escape($date_to)."'";
if ($status !== '') $sql_count .= " AND r.status = ".(int)$status;
if ($user_id > 0) $sql_count .= " AND r.fk_user_author = ".(int)$user_id;
elseif (empty($current_user->admin)) $sql_count .= " AND r.fk_user_author = ".(int)$current_user->id;

$resql_count = $db->query($sql_count);
$total = 0;
if ($resql_count) {
    $obj_count = $db->fetch_object($resql_count);
    $total = (int)$obj_count->total;
}

mv3_json_success(array(
    'reports' => $reports,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset
));
