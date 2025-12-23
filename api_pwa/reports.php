<?php
/**
 * API de gestion des rapports
 * Endpoints: list, create, update, delete, drafts
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    $session = verifyToken();

    switch ($action) {
        case 'list':
            listReports($session);
            break;
        case 'create':
            createReport($session);
            break;
        case 'drafts':
            listDrafts($session);
            break;
        case 'save_draft':
            saveDraft($session);
            break;
        case 'delete_draft':
            deleteDraft($session);
            break;
        default:
            jsonResponse(['error' => 'Action inconnue'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

/**
 * Lister les rapports depuis Dolibarr
 */
function listReports($session) {
    $db = getDB();

    // Récupérer les rapports depuis la table llx_mv3_rapport
    $stmt = $db->prepare("
        SELECT
            r.rowid as id,
            r.client_nom,
            r.projet_nom,
            r.date_debut,
            r.date_fin,
            r.statut,
            r.type_rapport,
            r.notes,
            r.created_at
        FROM llx_mv3_rapport r
        WHERE r.fk_user_author = ?
        ORDER BY r.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$session['dolibarr_user_id']]);
    $reports = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'reports' => $reports
    ]);
}

/**
 * Créer un nouveau rapport
 */
function createReport($session) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $data = getRequestBody();
    $db = getDB();

    // Insérer dans llx_mv3_rapport
    $stmt = $db->prepare("
        INSERT INTO llx_mv3_rapport (
            fk_user_author,
            client_nom,
            projet_nom,
            date_debut,
            date_fin,
            type_rapport,
            notes,
            statut,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'brouillon', NOW())
    ");

    $stmt->execute([
        $session['dolibarr_user_id'],
        $data['client'] ?? '',
        $data['project'] ?? '',
        $data['startDate'] ?? date('Y-m-d H:i:s'),
        $data['endDate'] ?? date('Y-m-d H:i:s'),
        $data['type'] ?? 'general',
        $data['notes'] ?? ''
    ]);

    $reportId = $db->lastInsertId();

    jsonResponse([
        'success' => true,
        'report_id' => $reportId
    ]);
}

/**
 * Lister les brouillons
 */
function listDrafts($session) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT
            id,
            draft_name,
            report_type,
            content,
            photos,
            voice_notes,
            gps_location,
            auto_saved_at,
            created_at,
            updated_at
        FROM llx_mv3_report_drafts
        WHERE user_id = ?
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$session['user_id']]);
    $drafts = $stmt->fetchAll();

    // Décoder les JSON
    foreach ($drafts as &$draft) {
        $draft['content'] = json_decode($draft['content'], true);
        $draft['photos'] = json_decode($draft['photos'], true);
        $draft['voice_notes'] = json_decode($draft['voice_notes'], true);
        $draft['gps_location'] = json_decode($draft['gps_location'], true);
    }

    jsonResponse([
        'success' => true,
        'drafts' => $drafts
    ]);
}

/**
 * Sauvegarder un brouillon
 */
function saveDraft($session) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $data = getRequestBody();
    $db = getDB();

    $draftId = $data['id'] ?? null;

    if ($draftId) {
        // Mise à jour
        $stmt = $db->prepare("
            UPDATE llx_mv3_report_drafts
            SET
                draft_name = ?,
                report_type = ?,
                content = ?,
                photos = ?,
                voice_notes = ?,
                gps_location = ?,
                auto_saved_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([
            $data['name'] ?? 'Brouillon',
            $data['type'] ?? 'general',
            json_encode($data['content'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($data['photos'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($data['voiceNotes'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($data['location'] ?? null, JSON_UNESCAPED_UNICODE),
            $draftId,
            $session['user_id']
        ]);
    } else {
        // Création
        $stmt = $db->prepare("
            INSERT INTO llx_mv3_report_drafts (
                user_id,
                draft_name,
                report_type,
                content,
                photos,
                voice_notes,
                gps_location
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $session['user_id'],
            $data['name'] ?? 'Brouillon ' . date('d/m H:i'),
            $data['type'] ?? 'general',
            json_encode($data['content'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($data['photos'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($data['voiceNotes'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($data['location'] ?? null, JSON_UNESCAPED_UNICODE)
        ]);
        $draftId = $db->lastInsertId();
    }

    jsonResponse([
        'success' => true,
        'draft_id' => $draftId
    ]);
}

/**
 * Supprimer un brouillon
 */
function deleteDraft($session) {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $draftId = $_GET['id'] ?? null;
    if (!$draftId) {
        jsonResponse(['error' => 'ID du brouillon requis'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("
        DELETE FROM llx_mv3_report_drafts
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$draftId, $session['user_id']]);

    jsonResponse([
        'success' => true,
        'deleted' => $stmt->rowCount() > 0
    ]);
}
