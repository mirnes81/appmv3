<?php
require_once '../config.php';

$userId = requireAuth();
$db = getDB();

$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');

$stmt = $db->prepare("
    SELECT
        COUNT(CASE WHEN DATE(date_rapport) = :today THEN 1 END) as reports_today,
        COUNT(CASE WHEN DATE(date_rapport) >= :week_start THEN 1 END) as reports_week,
        COUNT(CASE WHEN DATE(date_rapport) >= :month_start THEN 1 END) as reports_month,
        SUM(CASE
            WHEN DATE(date_rapport) = :today AND heure_debut IS NOT NULL AND heure_fin IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, heure_debut, heure_fin)
            ELSE 0
        END) as hours_today,
        SUM(CASE
            WHEN DATE(date_rapport) >= :week_start AND heure_debut IS NOT NULL AND heure_fin IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, heure_debut, heure_fin)
            ELSE 0
        END) as hours_week,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_sync
    FROM llx_mv3_rapport
    WHERE user_id = :user_id
");

$stmt->execute([
    'user_id' => $userId,
    'today' => $today,
    'week_start' => $weekStart,
    'month_start' => $monthStart
]);

$stats = $stmt->fetch();

$stmt = $db->prepare("
    SELECT COUNT(*) as photos_count
    FROM llx_mv3_rapport_photos p
    INNER JOIN llx_mv3_rapport r ON r.rowid = p.rapport_id
    WHERE r.user_id = :user_id
");

$stmt->execute(['user_id' => $userId]);
$photosData = $stmt->fetch();

$stats['photos_count'] = $photosData['photos_count'];
$stats['hours_today'] = (int)($stats['hours_today'] ?? 0);
$stats['hours_week'] = (int)($stats['hours_week'] ?? 0);

jsonResponse($stats);
