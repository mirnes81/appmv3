<?php
/**
 * API de gestion du matériel
 */

require_once __DIR__ . '/config.php';

try {
    $session = verifyToken();
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'list':
            listMateriel($session);
            break;
        case 'update_status':
            updateStatus($session);
            break;
        default:
            jsonResponse(['error' => 'Action inconnue'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

function listMateriel($session) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT
            m.rowid as id,
            m.ref as reference,
            m.designation,
            m.statut,
            m.lieu_stockage,
            m.date_derniere_maintenance,
            m.notes
        FROM llx_mv3_materiel m
        ORDER BY m.designation ASC
    ");
    $stmt->execute();
    $materiel = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'materiel' => $materiel
    ]);
}

function updateStatus($session) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $data = getRequestBody();
    $db = getDB();

    $stmt = $db->prepare("
        UPDATE llx_mv3_materiel
        SET statut = ?, notes = ?
        WHERE rowid = ?
    ");
    $stmt->execute([
        $data['status'] ?? '',
        $data['notes'] ?? '',
        $data['id'] ?? 0
    ]);

    jsonResponse([
        'success' => true,
        'updated' => $stmt->rowCount() > 0
    ]);
}
