<?php
/**
 * API v1 - Régie - Ajout photo
 *
 * POST /api/v1/regie_add_photo.php
 *
 * Upload de photos pour un bon de régie
 * Content-Type: multipart/form-data
 *
 * Params:
 * - regie_id: ID du bon
 * - files[]: fichiers images
 * - descriptions[]: descriptions optionnelles
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Auth requise
$auth = require_auth();
require_rights('write', $auth);

// Méthode POST uniquement
require_method('POST');

// Paramètres
$regie_id = (int)get_param('regie_id', 0);
require_param($regie_id, 'regie_id');

// Vérifier que la régie existe
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_regie";
$sql .= " WHERE rowid = ".(int)$regie_id;
$sql .= " AND entity IN (".getEntity('project').")";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Régie non trouvée', 'NOT_FOUND', 404);
}

// Vérifier upload de fichiers
if (empty($_FILES['files'])) {
    json_error('Aucun fichier uploadé', 'NO_FILES', 400);
}

// Répertoire de destination
$upload_dir = $conf->mv3pro_portail->dir_output . '/regie/' . $regie_id;

if (!is_dir($upload_dir)) {
    if (dol_mkdir($upload_dir, 0755) < 0) {
        json_error('Impossible de créer le répertoire', 'MKDIR_ERROR', 500);
    }
}

// Configuration
$max_size = 10 * 1024 * 1024; // 10 MB
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
$descriptions = isset($_POST['descriptions']) ? $_POST['descriptions'] : [];

$uploaded_photos = [];
$errors = [];

$files = $_FILES['files'];
$file_count = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $file_count; $i++) {
    $file = [
        'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
        'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
        'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
        'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
        'size' => is_array($files['size']) ? $files['size'][$i] : $files['size']
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur upload {$file['name']}: code {$file['error']}";
        continue;
    }

    if ($file['size'] > $max_size) {
        $errors[] = "Fichier {$file['name']} trop volumineux (max 10MB)";
        continue;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        $errors[] = "Type non autorisé pour {$file['name']}: {$mime}";
        continue;
    }

    $filename = dol_sanitizeFileName($file['name']);
    $filename = time() . '_' . uniqid() . '_' . $filename;
    $filepath = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $errors[] = "Impossible de sauvegarder {$file['name']}";
        continue;
    }

    $relative_path = 'regie/' . $regie_id . '/' . $filename;
    $description = isset($descriptions[$i]) ? $descriptions[$i] : '';

    $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."mv3_regie_photo";
    $sql_insert .= " (fk_regie, filepath, filename, description, date_upload)";
    $sql_insert .= " VALUES (";
    $sql_insert .= " ".(int)$regie_id.",";
    $sql_insert .= " '".$db->escape($relative_path)."',";
    $sql_insert .= " '".$db->escape($filename)."',";
    $sql_insert .= " '".$db->escape($description)."',";
    $sql_insert .= " NOW()";
    $sql_insert .= ")";

    $resql_insert = $db->query($sql_insert);

    if ($resql_insert) {
        $photo_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_regie_photo");

        $uploaded_photos[] = [
            'id' => $photo_id,
            'filename' => $filename,
            'description' => $description,
            'url' => '/custom/mv3pro_portail/document.php?modulepart=mv3pro_portail&file='.urlencode($relative_path)
        ];
    } else {
        $errors[] = "Erreur BDD pour {$file['name']}";
        @unlink($filepath);
    }
}

$response = [
    'uploaded' => count($uploaded_photos),
    'photos' => $uploaded_photos
];

if (!empty($errors)) {
    $response['errors'] = $errors;
}

if (count($uploaded_photos) === 0) {
    json_error('Aucune photo uploadée', 'UPLOAD_FAILED', 400);
}

json_ok($response);
