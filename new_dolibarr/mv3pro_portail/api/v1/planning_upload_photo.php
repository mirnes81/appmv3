<?php
/**
 * POST /api/v1/planning_upload_photo.php
 *
 * Upload de photos vers un événement de planning
 * Version PRODUCTION pour la PWA
 *
 * Stockage: /documents/action/{event_id}/
 * Authentification: Session Dolibarr (cookies)
 */

// Headers JSON pour les réponses
header('Content-Type: application/json; charset=utf-8');

// CORS (si nécessaire)
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (!empty($origin)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Fonction pour retourner une erreur JSON
function json_error($message, $code = 'ERROR', $http_code = 400) {
    http_response_code($http_code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Fonction pour retourner un succès JSON
function json_success($data) {
    http_response_code(201);
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// NE PAS définir NOLOGIN - on veut utiliser la session Dolibarr
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
    json_error('Erreur de configuration serveur', 'SERVER_ERROR', 500);
}

global $db, $conf, $user;

// 1️⃣ VÉRIFICATION AUTHENTIFICATION
if (!$user || !$user->id) {
    json_error('Non authentifié. Veuillez vous reconnecter.', 'NOT_AUTHENTICATED', 401);
}

// 2️⃣ VÉRIFICATION MÉTHODE HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Méthode non autorisée. Utilisez POST.', 'METHOD_NOT_ALLOWED', 405);
}

// 3️⃣ RÉCUPÉRATION DE L'EVENT ID
$event_id = 0;
if (isset($_POST['event_id'])) {
    $event_id = (int)$_POST['event_id'];
} elseif (isset($_POST['id'])) {
    $event_id = (int)$_POST['id'];
} elseif (isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
}

if (!$event_id || $event_id <= 0) {
    json_error('ID événement manquant ou invalide', 'INVALID_EVENT_ID', 400);
}

// 4️⃣ VÉRIFICATION DU FICHIER UPLOADÉ
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    if (isset($_FILES['file']['error'])) {
        $error_code = $_FILES['file']['error'];
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                json_error('Fichier trop volumineux (max: ' . ini_get('upload_max_filesize') . ')', 'FILE_TOO_LARGE', 413);
            case UPLOAD_ERR_PARTIAL:
                json_error('Upload incomplet, veuillez réessayer', 'UPLOAD_INCOMPLETE', 400);
            case UPLOAD_ERR_NO_FILE:
                json_error('Aucun fichier sélectionné', 'NO_FILE', 400);
            default:
                json_error('Erreur lors de l\'upload du fichier (code: ' . $error_code . ')', 'UPLOAD_ERROR', 400);
        }
    }
    json_error('Aucun fichier uploadé', 'NO_FILE', 400);
}

$file = $_FILES['file'];

// 5️⃣ VÉRIFICATION DU TYPE DE FICHIER
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    json_error('Type de fichier non autorisé. Seules les images sont acceptées (JPEG, PNG, GIF, WebP)', 'INVALID_FILE_TYPE', 415);
}

// Vérification de l'extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($extension, $allowed_extensions)) {
    json_error('Extension de fichier non autorisée', 'INVALID_FILE_EXTENSION', 415);
}

// 6️⃣ CHARGER LES LIBRAIRIES DOLIBARR
try {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
} catch (Exception $e) {
    json_error('Erreur de chargement des librairies: ' . $e->getMessage(), 'LIBRARY_ERROR', 500);
}

// 7️⃣ VÉRIFIER QUE L'ÉVÉNEMENT EXISTE
$object = new ActionComm($db);
$result = $object->fetch($event_id);

if ($result <= 0) {
    json_error('Événement non trouvé ou accès refusé', 'EVENT_NOT_FOUND', 404);
}

// 8️⃣ DÉFINIR LE RÉPERTOIRE DE STOCKAGE (chemin standard Dolibarr pour ActionComm)
$upload_dir = DOL_DATA_ROOT . '/documents/action/' . $event_id;

// Créer le répertoire si nécessaire
if (!is_dir($upload_dir)) {
    $mkdir_result = dol_mkdir($upload_dir);
    if ($mkdir_result < 0) {
        json_error('Impossible de créer le répertoire de stockage', 'MKDIR_ERROR', 500);
    }
}

// Vérifier que le répertoire est accessible en écriture
if (!is_writable($upload_dir)) {
    json_error('Répertoire non accessible en écriture', 'DIRECTORY_NOT_WRITABLE', 500);
}

// 9️⃣ GÉNÉRER UN NOM DE FICHIER SÉCURISÉ
$base_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$filename = $base_name . '_' . time() . '.' . $extension;
$dest_path = $upload_dir . '/' . $filename;

// 🔟 DÉPLACER LE FICHIER UPLOADÉ
if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
    json_error('Erreur lors du déplacement du fichier', 'MOVE_ERROR', 500);
}

// 1️⃣1️⃣ INDEXER DANS ECM_FILES
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
    '".$db->escape('action/' . $event_id)."',
    '".$db->escape($filename)."',
    'actioncomm',
    ".(int)$event_id.",
    '".$db->escape($file['name'])."',
    0,
    '".$db->idate(dol_now())."',
    ".(int)$user->id."
)";

$resql = $db->query($sql);
if (!$resql) {
    // Le fichier est déjà uploadé, on ne va pas le supprimer
    // Mais on log l'erreur
    error_log('[MV3 Upload] Erreur SQL ecm_files: ' . $db->lasterror());
}

$file_id = $db->last_insert_id(MAIN_DB_PREFIX."ecm_files");

// 1️⃣2️⃣ GÉNÉRER LES URLS
$base_url = DOL_MAIN_URL_ROOT . '/document.php';
$download_url = $base_url . '?modulepart=action&attachment=1&file=' . urlencode($event_id . '/' . $filename);
$thumb_url = $base_url . '?modulepart=action&attachment=0&file=' . urlencode($event_id . '/' . $filename);

// 1️⃣3️⃣ RETOURNER LE SUCCÈS
json_success([
    'message' => 'Photo uploadée avec succès',
    'file' => [
        'id' => $file_id,
        'name' => $filename,
        'original_name' => $file['name'],
        'size' => $file['size'],
        'mime_type' => $mime_type,
        'download_url' => $download_url,
        'thumb_url' => $thumb_url
    ]
]);
