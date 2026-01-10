<?php
/**
 * POST /api/v1/planning_upload_photo.php
 *
 * Upload des photos vers un événement de planning
 */

// MODE DEBUG - Activer l'affichage des erreurs
define('DEBUG_UPLOAD', true);

if (DEBUG_UPLOAD) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    error_log('[MV3 UPLOAD DEBUG] === DÉBUT UPLOAD ===');
}

require_once __DIR__ . '/_bootstrap.php';

global $db, $conf, $user;

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Bootstrap chargé, vérification méthode...');

// Méthode POST uniquement
require_method('POST');

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Méthode POST validée, authentification...');

// Authentification obligatoire
$auth = require_auth(true);

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Auth OK - User ID: ' . $auth['user_id']);

// Récupérer l'ID de l'événement
$event_id = get_param('id', null);

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Event ID reçu: ' . var_export($event_id, true));

if (!$event_id || !is_numeric($event_id)) {
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] ERREUR: Event ID invalide');
    json_error('ID événement manquant ou invalide', 'INVALID_ID', 400);
}

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Event ID validé: ' . $event_id);

// Vérifier que le fichier a été uploadé
if (DEBUG_UPLOAD) {
    error_log('[MV3 UPLOAD DEBUG] $_FILES: ' . print_r($_FILES, true));
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error_msg = 'Aucun fichier uploadé';
    if (isset($_FILES['file']['error'])) {
        $error_code = $_FILES['file']['error'];
        if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] ERREUR upload PHP code: ' . $error_code);
        switch ($error_code) {
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
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] ERREUR: ' . $error_msg);
    json_error($error_msg, 'UPLOAD_ERROR', 400);
}

$file = $_FILES['file'];
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Fichier reçu: ' . $file['name'] . ' (' . $file['size'] . ' bytes)');

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

// Charger les librairies nécessaires
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Chargement librairies Dolibarr...');
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] DOL_DOCUMENT_ROOT: ' . DOL_DOCUMENT_ROOT);

try {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] files.lib.php chargé');

    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] actioncomm.class.php chargé');
} catch (Exception $e) {
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] ERREUR chargement: ' . $e->getMessage());
    json_error('Erreur chargement des librairies: ' . $e->getMessage(), 'LOAD_LIB_ERROR', 500);
}

$object = new ActionComm($db);
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Fetch ActionComm #' . $event_id);

$result = $object->fetch($event_id);

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Résultat fetch: ' . $result);

if ($result <= 0) {
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] ERREUR: Fetch failed');
    json_error('Impossible de charger l\'événement', 'LOAD_ERROR', 500);
}

// Générer un nom de fichier sécurisé
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$base_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
$filename = $base_name . '_' . time() . '.' . $extension;

// Créer le répertoire si nécessaire
if (DEBUG_UPLOAD) {
    error_log('[MV3 UPLOAD DEBUG] Vérification $conf->mv3pro_portail: ' . (isset($conf->mv3pro_portail) ? 'EXISTS' : 'NOT EXISTS'));
    error_log('[MV3 UPLOAD DEBUG] Vérification $conf->mv3pro_portail->dir_output: ' . (isset($conf->mv3pro_portail->dir_output) ? $conf->mv3pro_portail->dir_output : 'NOT SET'));
}

$upload_dir = $conf->mv3pro_portail->dir_output . '/planning/' . $event_id;
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Upload dir: ' . $upload_dir);
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Dir existe: ' . (is_dir($upload_dir) ? 'OUI' : 'NON'));

if (!is_dir($upload_dir)) {
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Création du répertoire...');
    $mkdir_result = dol_mkdir($upload_dir);
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Résultat dol_mkdir: ' . var_export($mkdir_result, true));
    if ($mkdir_result < 0) {
        if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] ERREUR: Impossible de créer le répertoire');
        json_error('Impossible de créer le répertoire', 'MKDIR_ERROR', 500);
    }
}

// Déplacer le fichier uploadé
$dest_path = $upload_dir . '/' . $filename;
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Destination: ' . $dest_path);
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Tmp file: ' . $file['tmp_name']);
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Tmp file existe: ' . (file_exists($file['tmp_name']) ? 'OUI' : 'NON'));

if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] ERREUR: move_uploaded_file a échoué');
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Last error: ' . error_get_last()['message']);
    json_error('Erreur lors du déplacement du fichier', 'MOVE_ERROR', 500);
}

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Fichier déplacé avec succès');
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Fichier existe: ' . (file_exists($dest_path) ? 'OUI' : 'NON'));

// Ajouter l'entrée dans ecm_files
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
    ".(int)$auth['user_id']."
)";

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] Exécution SQL INSERT ecm_files...');
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] SQL: ' . $sql);

$resql = $db->query($sql);
if (!$resql) {
    $db_error = $db->lasterror();
    error_log('[MV3 Planning Upload] Erreur SQL ecm_files: ' . $db_error);
    if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] ERREUR SQL: ' . $db_error);
    json_error('Erreur lors de l\'enregistrement en base de données: ' . $db_error, 'DB_ERROR', 500);
}

if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] SQL INSERT OK');

// Retourner les infos du fichier uploadé
http_response_code(201);
if (DEBUG_UPLOAD) error_log('[MV3 UPLOAD DEBUG] === UPLOAD TERMINÉ AVEC SUCCÈS ===');

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
