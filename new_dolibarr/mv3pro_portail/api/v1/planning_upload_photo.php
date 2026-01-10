<?php
/**
 * POST /api/v1/planning_upload_photo.php
 *
 * Upload de photos vers un événement de planning
 * Version 2.0 - Authentification par token PWA
 *
 * Stockage: /documents/mv3pro_portail/planning/{event_id}/
 * Authentification: Bearer token + X-Auth-Token + Session Dolibarr (fallback)
 */

// Désactiver l'affichage des erreurs PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Client-Info, Apikey');
header('Content-Type: application/json; charset=utf-8');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration Dolibarr
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', 1);

// Charger Dolibarr
$res = 0;
if (!$res && file_exists(__DIR__ . "/../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../main.inc.php";
}
if (!$res && file_exists(__DIR__ . "/../../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../../main.inc.php";
}

if (!$res) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'Erreur de configuration serveur'
    ]);
    exit;
}

global $db, $conf, $user;

// Charger le helper d'authentification
require_once __DIR__ . '/mv3_auth.php';

// Mode debug
$debug = mv3_isDebugMode();

// 1. AUTHENTIFICATION
$auth = mv3_authenticateOrFail($db, $debug);
$user = $auth['user'];

// 2. VÉRIFICATION MÉTHODE HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mv3_jsonError(405, 'METHOD_NOT_ALLOWED', 'Méthode non autorisée. Utilisez POST.');
}

// 3. RÉCUPÉRATION DE L'EVENT ID
$event_id = 0;
if (isset($_POST['event_id'])) {
    $event_id = (int)$_POST['event_id'];
} elseif (isset($_POST['id'])) {
    $event_id = (int)$_POST['id'];
} elseif (isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
}

if (!$event_id || $event_id <= 0) {
    mv3_jsonError(400, 'INVALID_EVENT_ID', 'ID événement manquant ou invalide');
}

// 4. VÉRIFICATION DU FICHIER UPLOADÉ
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    if (isset($_FILES['file']['error'])) {
        $error_code = $_FILES['file']['error'];
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                mv3_jsonError(413, 'FILE_TOO_LARGE', 'Fichier trop volumineux (max: ' . ini_get('upload_max_filesize') . ')');
            case UPLOAD_ERR_PARTIAL:
                mv3_jsonError(400, 'UPLOAD_INCOMPLETE', 'Upload incomplet, veuillez réessayer');
            case UPLOAD_ERR_NO_FILE:
                mv3_jsonError(400, 'NO_FILE', 'Aucun fichier sélectionné');
            default:
                mv3_jsonError(400, 'UPLOAD_ERROR', 'Erreur lors de l\'upload du fichier (code: ' . $error_code . ')');
        }
    }
    mv3_jsonError(400, 'NO_FILE', 'Aucun fichier uploadé');
}

$file = $_FILES['file'];

// Limite de taille (10 MB par défaut)
$max_size = 10 * 1024 * 1024;
if ($file['size'] > $max_size) {
    mv3_jsonError(413, 'FILE_TOO_LARGE', 'Fichier trop volumineux. Maximum: ' . ($max_size / 1024 / 1024) . ' MB');
}

// 5. VÉRIFICATION DU TYPE DE FICHIER
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    mv3_jsonError(415, 'INVALID_FILE_TYPE', 'Type de fichier non autorisé. Seules les images sont acceptées (JPEG, PNG, GIF, WebP)');
}

// Vérification de l'extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($extension, $allowed_extensions)) {
    mv3_jsonError(415, 'INVALID_FILE_EXTENSION', 'Extension de fichier non autorisée');
}

// 6. CHARGER LES LIBRAIRIES DOLIBARR
try {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
} catch (Exception $e) {
    mv3_jsonError(500, 'LIBRARY_ERROR', 'Erreur de chargement des librairies: ' . $e->getMessage());
}

// 7. VÉRIFIER QUE L'ÉVÉNEMENT EXISTE
$object = new ActionComm($db);
$result = $object->fetch($event_id);

if ($result <= 0) {
    mv3_jsonError(404, 'EVENT_NOT_FOUND', 'Événement non trouvé ou accès refusé');
}

// 8. VÉRIFIER LES PERMISSIONS
if (!mv3_checkPermission($user, 'agenda', 'create')) {
    mv3_jsonError(403, 'PERMISSION_DENIED', 'Vous n\'avez pas la permission d\'ajouter des fichiers');
}

// 9. DÉFINIR LE RÉPERTOIRE DE STOCKAGE (mv3pro_portail/planning/)
$upload_dir = DOL_DATA_ROOT . '/mv3pro_portail/planning/' . $event_id;

// Créer le répertoire si nécessaire
if (!is_dir($upload_dir)) {
    $mkdir_result = dol_mkdir($upload_dir);
    if ($mkdir_result < 0) {
        mv3_jsonError(500, 'MKDIR_ERROR', 'Impossible de créer le répertoire de stockage');
    }
}

// Vérifier que le répertoire est accessible en écriture
if (!is_writable($upload_dir)) {
    mv3_jsonError(500, 'DIRECTORY_NOT_WRITABLE', 'Répertoire non accessible en écriture');
}

// 10. GÉNÉRER UN NOM DE FICHIER SÉCURISÉ
$base_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$filename = $base_name . '_' . time() . '.' . $extension;
$dest_path = $upload_dir . '/' . $filename;

// 11. DÉPLACER LE FICHIER UPLOADÉ
if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
    mv3_jsonError(500, 'MOVE_ERROR', 'Erreur lors du déplacement du fichier');
}

// 12. INDEXER DANS ECM_FILES
$sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_files (
    label,
    entity,
    filepath,
    filename,
    src_object_type,
    src_object_id,
    fullpath_orig,
    position,
    date_c,
    fk_user_c
) VALUES (
    '".$db->escape($file['name'])."',
    ".(int)$conf->entity.",
    '".$db->escape('mv3pro_portail/planning/' . $event_id)."',
    '".$db->escape($filename)."',
    'actioncomm',
    ".(int)$event_id.",
    '".$db->escape($file['name'])."',
    0,
    '".$db->idate(dol_now())."',
    ".(int)$user->id."
)";

$resql = $db->query($sql);
$file_id = $resql ? $db->last_insert_id(MAIN_DB_PREFIX."ecm_files") : 0;

if (!$resql && $debug) {
    error_log('[MV3 Upload] Erreur SQL ecm_files: ' . $db->lasterror());
}

// 13. GÉNÉRER LES URLS
$photo_url = DOL_MAIN_URL_ROOT . '/custom/mv3pro_portail/api/v1/planning_file.php?id=' . $event_id . '&filename=' . urlencode($filename);

// 14. RETOURNER LE SUCCÈS
mv3_jsonSuccess([
    'message' => 'Photo uploadée avec succès',
    'event_id' => $event_id,
    'file' => [
        'id' => $file_id,
        'name' => $filename,
        'original_name' => $file['name'],
        'size' => $file['size'],
        'mime_type' => $mime_type,
        'url' => $photo_url
    ]
], 201);
