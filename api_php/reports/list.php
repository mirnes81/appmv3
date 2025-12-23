<?php
require_once '../config.php';

$userId = requireAuth();
$db = getDB();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : null;

$sql = "
    SELECT
        r.rowid as id,
        r.user_id,
        r.date_rapport as date,
        r.heure_debut as start_time,
        r.heure_fin as end_time,
        r.client_name,
        r.description,
        r.observations,
        r.status,
        r.date_creation as created_at,
        COUNT(DISTINCT p.rowid) as photos_count
    FROM llx_mv3_rapport r
    LEFT JOIN llx_mv3_rapport_photos p ON p.rapport_id = r.rowid
    WHERE r.user_id = :user_id
";

$params = ['user_id' => $userId];

if ($status) {
    $sql .= " AND r.status = :status";
    $params['status'] = $status;
}

$sql .= " GROUP BY r.rowid ORDER BY r.date_rapport DESC, r.date_creation DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$reports = $stmt->fetchAll();

foreach ($reports as &$report) {
    $report['photos'] = [];
}

jsonResponse($reports);
