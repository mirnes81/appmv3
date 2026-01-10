<?php
/**
 * API Générique - Téléchargement / Suppression de fichier
 *
 * GET /api/v1/object/file.php?type=actioncomm&id=74049&filename=photo.jpg
 *   → Télécharge le fichier
 *
 * DELETE /api/v1/object/file.php?type=actioncomm&id=74049&filename=photo.jpg
 *   → Supprime le fichier
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
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier que l'utilisateur est connecté
if (!$user || !$user->id) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

// Charger le helper
require_once DOL_DOCUMENT_ROOT . '/custom/mv3pro_portail/class/object_helper.class.php';

// Récupérer les paramètres
$type = GETPOST('type', 'alpha');
$id = GETPOST('id', 'int');
$filename = GETPOST('filename', 'alphanohtml');

// Validation
if (empty($type) || empty($id) || empty($filename)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants: type, id et filename requis']);
    exit;
}

// Vérifier que le type est supporté
if (!in_array($type, ObjectHelper::getSupportedTypes())) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'error' => 'Type non supporté: ' . $type,
        'supported_types' => ObjectHelper::getSupportedTypes()
    ]);
    exit;
}

// Sécurité: empêcher la traversée de répertoires
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'Nom de fichier invalide']);
    exit;
}

try {
    $helper = new ObjectHelper($db, $user);
    $config = ObjectHelper::getTypeConfig($type);

    // Construire le chemin du fichier
    global $conf;
    $upload_dir = $conf->$config['module_dir']->dir_output . '/' . $id;
    $filepath = $upload_dir . '/' . $filename;

    // Vérifier que le fichier existe
    if (!file_exists($filepath)) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'Fichier non trouvé']);
        exit;
    }

    // Déterminer l'action selon la méthode HTTP
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'DELETE') {
        // Suppression
        header('Content-Type: application/json');

        $result = $helper->deleteFile($type, $id, $filename);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Fichier supprimé']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $helper->error ?? 'Erreur lors de la suppression']);
        }

    } elseif ($method === 'GET') {
        // Téléchargement / Affichage
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($filepath));
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        header('Cache-Control: public, max-age=3600');

        readfile($filepath);
        exit;

    } else {
        header('Content-Type: application/json');
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non supportée']);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
