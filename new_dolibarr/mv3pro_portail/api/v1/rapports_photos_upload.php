<?php
/**
 * API v1 - Rapports - Upload photos
 *
 * POST /api/v1/rapports_photos_upload.php
 *
 * Upload de photos pour un rapport
 * Content-Type: multipart/form-data
 *
 * Params:
 * - rapport_id: ID du rapport
 * - files[]: fichiers images
 * - descriptions[]: descriptions optionnelles (même ordre que files)
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Auth requise
$auth = require_auth();
require_rights('write', $auth);

// Méthode POST uniquement
require_method('POST');

// Paramètres
$rapport_id = (int)get_param('rapport_id', 0);
require_param($rapport_id, 'rapport_id');

// Vérifier que le rapport existe
$sql = "SELECT rowid, fk_user FROM ".MAIN_DB_PREFIX."mv3_rapport";
$sql .= " WHERE rowid = ".(int)$rapport_id;
$sql .= " AND entity IN (".getEntity('project').")";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Rapport non trouvé', 'NOT_FOUND', 404);
}

$rapport = $db->fetch_object($resql);

// Vérifier droits (worker peut uploader sur ses propres rapports)
if (!empty($auth['rights']['worker']) &&
    empty($auth['rights']['write']) &&
    $rapport->fk_user != $auth['user_id']) {
    json_error('Accès refusé à ce rapport', 'FORBIDDEN', 403);
}

// Vérifier upload de fichiers
if (empty($_FILES['files'])) {
    json_error('Aucun fichier uploadé', 'NO_FILES', 400);
}

// Répertoire de destination
$upload_dir = $conf->mv3pro_portail->dir_output . '/rapports/' . $rapport_id;

// Créer le répertoire si n'existe pas
if (!is_dir($upload_dir)) {
    if (!dol_mkdir($upload_dir, 0755) < 0) {
        json_error('Impossible de créer le répertoire de destination', 'MKDIR_ERROR', 500);
    }
}

// Configuration upload
$max_size = 10 * 1024 * 1024; // 10 MB
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

// Descriptions optionnelles
$descriptions = isset($_POST['descriptions']) ? $_POST['descriptions'] : [];

$uploaded_photos = [];
$errors = [];

// Traiter chaque fichier
$files = $_FILES['files'];
$file_count = is_array($files['name']) ? count($files['name']) : 1;

for ($i = 0; $i < $file_count; $i++) {
    // Extraire info fichier
    $file = [
        'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
        'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
        'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
        'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
        'size' => is_array($files['size']) ? $files['size'][$i] : $files['size']
    ];

    // Vérifier erreur upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur upload fichier {$file['name']}: code {$file['error']}";
        continue;
    }

    // Vérifier taille
    if ($file['size'] > $max_size) {
        $errors[] = "Fichier {$file['name']} trop volumineux (max 10MB)";
        continue;
    }

    // Vérifier type MIME réel
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        $errors[] = "Type de fichier non autorisé pour {$file['name']}: {$mime}";
        continue;
    }

    // Nettoyer nom fichier
    $filename = dol_sanitizeFileName($file['name']);
    $filename = time() . '_' . uniqid() . '_' . $filename;

    // Chemin complet
    $filepath = $upload_dir . '/' . $filename;

    // Déplacer fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $errors[] = "Impossible de sauvegarder {$file['name']}";
        continue;
    }

    // Chemin relatif pour BDD
    $relative_path = 'rapports/' . $rapport_id . '/' . $filename;
    $description = isset($descriptions[$i]) ? $descriptions[$i] : '';

    // Insérer en BDD
    $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport_photo";
    $sql_insert .= " (fk_rapport, filepath, filename, description, ordre, date_upload)";
    $sql_insert .= " VALUES (";
    $sql_insert .= " ".(int)$rapport_id.",";
    $sql_insert .= " '".$db->escape($relative_path)."',";
    $sql_insert .= " '".$db->escape($filename)."',";
    $sql_insert .= " '".$db->escape($description)."',";
    $sql_insert .= " ".$i.",";
    $sql_insert .= " NOW()";
    $sql_insert .= ")";

    $resql_insert = $db->query($sql_insert);

    if ($resql_insert) {
        $photo_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_rapport_photo");

        $uploaded_photos[] = [
            'id' => $photo_id,
            'filename' => $filename,
            'description' => $description,
            'url' => '/custom/mv3pro_portail/document.php?modulepart=mv3pro_portail&file='.urlencode($relative_path)
        ];
    } else {
        $errors[] = "Erreur BDD pour {$file['name']}";
        // Supprimer fichier uploadé
        @unlink($filepath);
    }
}

// Réponse
$response = [
    'uploaded' => count($uploaded_photos),
    'photos' => $uploaded_photos
];

if (!empty($errors)) {
    $response['errors'] = $errors;
}

if (count($uploaded_photos) === 0) {
    json_error('Aucune photo n\'a pu être uploadée', 'UPLOAD_FAILED', 400, $errors);
}

json_ok($response);
