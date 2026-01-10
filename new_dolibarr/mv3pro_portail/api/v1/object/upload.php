<?php
/**
 * API Générique - Upload de fichier
 *
 * POST /api/v1/object/upload.php
 * Body (multipart/form-data):
 *   - type: actioncomm|task|project
 *   - id: ID de l'objet
 *   - file: Fichier à uploader
 *
 * Retourne: infos du fichier uploadé
 */

// Bootstrap Dolibarr
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
    $res = include "../../../../main.inc.php";
}

if (!$res) {
    die(json_encode(['error' => 'Dolibarr main.inc.php not found']));
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Charger le helper d'authentification
require_once __DIR__ . '/../mv3_auth.php';

// Authentification
$debug = mv3_isDebugMode();
$auth = mv3_authenticateOrFail($db, $debug);
$user = $auth['user'];

// Charger le helper d'objets
require_once DOL_DOCUMENT_ROOT . '/custom/mv3pro_portail/class/object_helper.class.php';

// Récupérer les paramètres
$type = GETPOST('type', 'alpha');
$id = GETPOST('id', 'int');

// Validation
if (empty($type) || empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants: type et id requis']);
    exit;
}

// Vérifier qu'un fichier a été uploadé
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Aucun fichier uploadé ou erreur lors de l\'upload']);
    exit;
}

// Vérifier que le type est supporté
if (!in_array($type, ObjectHelper::getSupportedTypes())) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Type non supporté: ' . $type,
        'supported_types' => ObjectHelper::getSupportedTypes()
    ]);
    exit;
}

// Limite de taille (10 MB par défaut)
$maxSize = 10 * 1024 * 1024;
if ($_FILES['file']['size'] > $maxSize) {
    http_response_code(413);
    echo json_encode([
        'error' => 'Fichier trop volumineux',
        'max_size' => $maxSize,
        'file_size' => $_FILES['file']['size']
    ]);
    exit;
}

try {
    // Créer le helper
    $helper = new ObjectHelper($db, $user);

    // Uploader le fichier
    $result = $helper->uploadFile($type, $id, $_FILES['file']);

    if ($result === false) {
        http_response_code(500);
        echo json_encode(['error' => $helper->error ?? 'Erreur lors de l\'upload']);
        exit;
    }

    http_response_code(201);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
