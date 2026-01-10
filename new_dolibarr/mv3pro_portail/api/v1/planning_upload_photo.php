<?php
/**
 * POST /api/v1/planning_upload_photo.php
 *
 * Upload des photos vers un événement de planning
 */

require_once __DIR__ . '/_bootstrap.php';

global $db, $conf, $user;

// Méthode POST uniquement
require_method('POST');

// Authentification obligatoire
$auth = require_auth(true);

// Récupérer l'ID de l'événement
$event_id = get_param('id', null);

if (!$event_id || !is_numeric($event_id)) {
    json_error('ID événement manquant ou invalide', 'INVALID_ID', 400);
}

// Vérifier que le fichier a été uploadé
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error_msg = 'Aucun fichier uploadé';
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_msg = 'Fichier trop volumineux';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_msg = 'Upload incomplet';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_msg = 'Aucun fichier sélectionné';
                break;
        }
    }
    json_error($error_msg, 'UPLOAD_ERROR', 400);
}

$file = $_FILES['file'];

// Vérifier que c'est une image
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    json_error('Type de fichier non autorisé. Seules les images sont acceptées.', 'INVALID_FILE_TYPE', 400);
}

// Vérifier la taille (max 10MB)
$max_size = 10 * 1024 * 1024;
if ($file['size'] > $max_size) {
    json_error('Fichier trop volumineux (max 10MB)', 'FILE_TOO_LARGE', 400);
}

// Vérifier que l'événement existe et que l'utilisateur y a accès
$sql = "SELECT a.id, a.label
        FROM ".MAIN_DB_PREFIX."actioncomm a
        LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
        WHERE a.id = ".(int)$event_id."
        AND (a.fk_user_author = ".(int)$auth['user_id']."
             OR a.fk_user_action = ".(int)$auth['user_id']."
             OR a.fk_user_done = ".(int)$auth['user_id']."
             OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$auth['user_id']."))
        LIMIT 1";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Événement non trouvé ou accès refusé', 'EVENT_NOT_FOUND', 404);
}

// Charger la classe ActionComm pour gérer les fichiers
dol_include_once('/comm/action/class/actioncomm.class.php');
$object = new ActionComm($db);
$result = $object->fetch($event_id);

if ($result <= 0) {
    json_error('Impossible de charger l\'événement', 'LOAD_ERROR', 500);
}

// Générer un nom de fichier sécurisé
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$base_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$filename = $base_name . '_' . time() . '.' . $extension;

// Créer le répertoire si nécessaire
$upload_dir = $conf->actioncomm->dir_output . '/' . $event_id;
if (!is_dir($upload_dir)) {
    dol_mkdir($upload_dir);
}

// Déplacer le fichier uploadé
$dest_path = $upload_dir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
    json_error('Erreur lors du déplacement du fichier', 'MOVE_ERROR', 500);
}

// Ajouter l'entrée dans ecm_files
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

$rel_path = dol_sanitizeFileName($event_id . '/' . $filename);

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
    '".$db->escape(dol_sanitizeFileName($event_id))."',
    '".$db->escape($filename)."',
    'actioncomm',
    ".(int)$event_id.",
    '".$db->escape($file['name'])."',
    0,
    '".$db->idate(dol_now())."',
    ".(int)$auth['user_id']."
)";

$resql = $db->query($sql);
if (!$resql) {
    error_log('[MV3 Planning Upload] Erreur SQL ecm_files: ' . $db->lasterror());
    json_error('Erreur lors de l\'enregistrement en base de données', 'DB_ERROR', 500);
}

// Retourner les infos du fichier uploadé
http_response_code(201);
echo json_encode([
    'success' => true,
    'message' => 'Photo uploadée avec succès',
    'file' => [
        'name' => $filename,
        'original_name' => $file['name'],
        'size' => $file['size'],
        'mime' => $mime_type
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
