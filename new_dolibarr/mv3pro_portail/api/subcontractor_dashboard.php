<?php
// Configuration CORS sécurisée
require_once __DIR__ . '/cors_config.php';

header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../../main.inc.php';

$input = json_decode(file_get_contents('php://input'), true);
$session_token = $input['session_token'] ?? '';

if (empty($session_token)) {
    echo json_encode(['success' => false, 'message' => 'Session requise']);
    exit;
}

$sql_session = "SELECT fk_subcontractor";
$sql_session .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_sessions";
$sql_session .= " WHERE session_id = '".$db->escape($session_token)."'";
$sql_session .= " AND expires_at > NOW()";

$resql_session = $db->query($sql_session);

if (!$resql_session || $db->num_rows($resql_session) === 0) {
    echo json_encode(['success' => false, 'message' => 'Session invalide']);
    exit;
}

$session_obj = $db->fetch_object($resql_session);
$fk_subcontractor = $session_obj->fk_subcontractor;

$sql_today = "SELECT COUNT(*) as count";
$sql_today .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_reports";
$sql_today .= " WHERE fk_subcontractor = ".(int)$fk_subcontractor;
$sql_today .= " AND report_date = CURDATE()";

$resql_today = $db->query($sql_today);
$today_report = null;
if ($resql_today) {
    $obj_today = $db->fetch_object($resql_today);
    if ($obj_today->count > 0) {
        $today_report = true;
    }
}

$sql_month = "SELECT COUNT(*) as count";
$sql_month .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_reports";
$sql_month .= " WHERE fk_subcontractor = ".(int)$fk_subcontractor;
$sql_month .= " AND MONTH(report_date) = MONTH(CURDATE())";
$sql_month .= " AND YEAR(report_date) = YEAR(CURDATE())";

$resql_month = $db->query($sql_month);
$month_reports = 0;
if ($resql_month) {
    $obj_month = $db->fetch_object($resql_month);
    $month_reports = $obj_month->count;
}

$sql_m2 = "SELECT SUM(surface_m2) as total";
$sql_m2 .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_reports";
$sql_m2 .= " WHERE fk_subcontractor = ".(int)$fk_subcontractor;
$sql_m2 .= " AND MONTH(report_date) = MONTH(CURDATE())";
$sql_m2 .= " AND YEAR(report_date) = YEAR(CURDATE())";

$resql_m2 = $db->query($sql_m2);
$total_m2 = 0;
if ($resql_m2) {
    $obj_m2 = $db->fetch_object($resql_m2);
    $total_m2 = $obj_m2->total ?? 0;
}

$sql_recent = "SELECT rowid, ref, report_date, surface_m2, amount_calculated, status";
$sql_recent .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_reports";
$sql_recent .= " WHERE fk_subcontractor = ".(int)$fk_subcontractor;
$sql_recent .= " ORDER BY report_date DESC, rowid DESC";
$sql_recent .= " LIMIT 10";

$resql_recent = $db->query($sql_recent);
$recent_reports = [];
if ($resql_recent) {
    while ($obj_recent = $db->fetch_object($resql_recent)) {
        $recent_reports[] = [
            'rowid' => $obj_recent->rowid,
            'ref' => $obj_recent->ref,
            'report_date' => $obj_recent->report_date,
            'surface_m2' => (float)$obj_recent->surface_m2,
            'amount_calculated' => (float)$obj_recent->amount_calculated,
            'status' => (int)$obj_recent->status
        ];
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'today_report' => $today_report,
        'month_reports' => $month_reports,
        'total_m2' => number_format($total_m2, 2, '.', ''),
        'recent_reports' => $recent_reports
    ]
]);
