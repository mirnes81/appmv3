<?php
require_once '../config.php';

$userId = requireAuth();
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['client_name']) || !isset($input['description'])) {
    jsonError('Client name and description are required');
}

$db = getDB();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO llx_mv3_rapport
        (user_id, date_rapport, heure_debut, heure_fin, client_name, description, observations, status, date_creation)
        VALUES
        (:user_id, :date_rapport, :heure_debut, :heure_fin, :client_name, :description, :observations, :status, NOW())
    ");

    $stmt->execute([
        'user_id' => $userId,
        'date_rapport' => $input['date'] ?? date('Y-m-d'),
        'heure_debut' => $input['start_time'] ?? null,
        'heure_fin' => $input['end_time'] ?? null,
        'client_name' => $input['client_name'],
        'description' => $input['description'],
        'observations' => $input['observations'] ?? null,
        'status' => 'synced'
    ]);

    $reportId = $db->lastInsertId();

    if (isset($input['photos']) && is_array($input['photos'])) {
        foreach ($input['photos'] as $photo) {
            $stmt = $db->prepare("
                INSERT INTO llx_mv3_rapport_photos
                (rapport_id, filename, file_size, uploaded_at)
                VALUES
                (:rapport_id, :filename, :file_size, NOW())
            ");

            $stmt->execute([
                'rapport_id' => $reportId,
                'filename' => $photo['filename'] ?? 'photo_' . time() . '.jpg',
                'file_size' => $photo['size'] ?? 0
            ]);
        }
    }

    $db->commit();

    jsonResponse([
        'success' => true,
        'report_id' => $reportId,
        'message' => 'Report created successfully'
    ], 201);

} catch (Exception $e) {
    $db->rollBack();
    jsonError('Failed to create report: ' . $e->getMessage(), 500);
}
