<?php
$res = 0;
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";
if (!$res && file_exists("../../../../../../main.inc.php")) $res = @include "../../../../../../main.inc.php";

if (!isset($_SESSION['dol_login']) || empty($user->id)) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');

$user_id = $user->id;

$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');

$sql_today = "SELECT
    COUNT(*) as count,
    COALESCE(SUM(surface_carrelee), 0) as surface,
    COALESCE(SUM(temps_total), 0) as hours
FROM ".MAIN_DB_PREFIX."mv3_rapport
WHERE fk_user = ".(int)$user_id."
AND DATE(date_rapport) = '".$db->escape($today)."'";

$resql_today = $db->query($sql_today);
$today_stats = $db->fetch_object($resql_today);

$sql_week = "SELECT
    COUNT(*) as count,
    COALESCE(SUM(surface_carrelee), 0) as surface,
    COALESCE(SUM(temps_total), 0) as hours
FROM ".MAIN_DB_PREFIX."mv3_rapport
WHERE fk_user = ".(int)$user_id."
AND DATE(date_rapport) >= '".$db->escape($week_start)."'";

$resql_week = $db->query($sql_week);
$week_stats = $db->fetch_object($resql_week);

$sql_month = "SELECT
    COUNT(*) as count,
    COALESCE(SUM(surface_carrelee), 0) as surface,
    COALESCE(SUM(temps_total), 0) as hours
FROM ".MAIN_DB_PREFIX."mv3_rapport
WHERE fk_user = ".(int)$user_id."
AND DATE(date_rapport) >= '".$db->escape($month_start)."'";

$resql_month = $db->query($sql_month);
$month_stats = $db->fetch_object($resql_month);

echo json_encode([
    'success' => true,
    'today' => [
        'count' => (int)$today_stats->count,
        'surface' => (float)$today_stats->surface,
        'hours' => round((float)$today_stats->hours, 2)
    ],
    'week' => [
        'count' => (int)$week_stats->count,
        'surface' => (float)$week_stats->surface,
        'hours' => round((float)$week_stats->hours, 2)
    ],
    'month' => [
        'count' => (int)$month_stats->count,
        'surface' => (float)$month_stats->surface,
        'hours' => round((float)$month_stats->hours, 2)
    ]
]);

$db->close();
