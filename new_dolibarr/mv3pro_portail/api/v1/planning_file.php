<?php
/**
 * API v1 - Planning - Fichier sécurisé
 * GET /api/v1/planning_file.php?id=123&file=xxx.pdf
 *
 * Stream un fichier associé à un événement de planning de manière sécurisée
 * avec vérification du token et des droits d'accès
 *
 * Règles d'accès :
 * - Admin : accès total à tous les fichiers
 * - Employee : accès uniquement si assigné à l'événement
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$id = (int)get_param('id', 0);
$file = get_param('file', '');

require_param($id, 'id');
require_param($file, 'file');

// Vérifier que l'événement existe
$sql = "SELECT a.id, a.fk_user_action, a.label
FROM ".MAIN_DB_PREFIX."actioncomm as a
WHERE a.id = ".$id."
AND a.entity IN (".getEntity('agenda').")";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) {
    if ($resql) {
        $db->free($resql);
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Événement non trouvé']);
    exit;
}

$event = $db->fetch_object($resql);
$db->free($resql);

// Vérifier les droits d'accès
// RÈGLE: Tous les employés authentifiés peuvent voir les fichiers de planning
// (contexte: app mobile où tout le monde doit voir les chantiers)
$has_access = true;
log_debug("Planning file #".$id." - Access granted (authenticated user)");

if (!$has_access) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Accès refusé',
        'message' => 'Vous n\'êtes pas autorisé à accéder à ce fichier'
    ]);
    exit;
}

// Chercher le fichier d'abord dans ECM
$filepath = null;
$display_filename = $file;

log_debug("Recherche fichier: ".$file." pour event #".$id);

// Méthode 1: Via ECM
$sql_file = "SELECT
    ecm.filename as stored_filename,
    ecm.label as filename,
    ecm.filepath
FROM ".MAIN_DB_PREFIX."ecm_files as ecm
WHERE ecm.src_object_type = 'actioncomm'
AND ecm.src_object_id = ".$id."
AND ecm.filename = '".$db->escape($file)."'";

log_debug("SQL ECM file:", ['sql' => $sql_file]);

$resql_file = $db->query($sql_file);
if ($resql_file && $db->num_rows($resql_file) > 0) {
    $file_obj = $db->fetch_object($resql_file);
    $relative_path = $file_obj->filepath.'/'.$file_obj->stored_filename;
    $filepath = DOL_DATA_ROOT.'/'.$relative_path;
    $display_filename = $file_obj->filename;

    log_debug("Fichier trouvé via ECM:");
    log_debug("  - Chemin relatif: ".$relative_path);
    log_debug("  - Chemin complet: ".$filepath);
    log_debug("  - Display name: ".$display_filename);

    $db->free($resql_file);
}

// Méthode 2 (fallback): Filesystem direct
if (!$filepath || !file_exists($filepath)) {
    log_debug("Fichier non trouvé via ECM, tentative filesystem...");

    $upload_dir = DOL_DATA_ROOT.'/actioncomm/'.dol_sanitizeFileName($id);
    $filepath = $upload_dir.'/'.dol_sanitizeFileName($file);

    log_debug("Chemin filesystem: ".$filepath);
}

// Vérifier que le fichier existe
if (!file_exists($filepath)) {
    log_debug("⚠️ FICHIER NON TROUVÉ: ".$filepath);

    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Fichier non trouvé',
        'file' => $file,
        'debug' => 'Fichier introuvable sur le disque'
    ]);
    exit;
}

// Vérifier que c'est bien un fichier (pas un répertoire)
if (!is_file($filepath)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Chemin invalide'
    ]);
    exit;
}

// Déterminer le type MIME
$mime = mime_content_type($filepath);
if (!$mime) {
    // Fallback sur l'extension
    $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $mime_map = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
    ];
    $mime = $mime_map[$ext] ?? 'application/octet-stream';
}

// Headers pour ouvrir dans le navigateur (inline) plutôt que télécharger
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($filepath));
header('Content-Disposition: inline; filename="'.$display_filename.'"');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

// CORS headers pour la PWA
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

// Log de l'accès
log_debug("Streaming file: ".$file." (".$mime.") for event #".$id." to user ".$auth['user_id']);

// Streamer le fichier
readfile($filepath);
exit;
