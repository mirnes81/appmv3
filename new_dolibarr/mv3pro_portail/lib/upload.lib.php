<?php
/**
 * Bibliothèque Upload - Gestion upload fichiers/photos
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * Upload fichier pour un rapport
 */
function mv3_upload_file($report_id, $file_info, &$error = '')
{
    global $conf;

    if (empty($file_info) || !is_array($file_info)) {
        $error = 'Aucun fichier fourni';
        return false;
    }

    if ($file_info['error'] !== UPLOAD_ERR_OK) {
        $error = 'Erreur upload : '.$file_info['error'];
        return false;
    }

    // Répertoire cible
    $upload_dir = $conf->mv3pro_portail->dir_output.'/report/'.$report_id;

    if (!dol_is_dir($upload_dir)) {
        dol_mkdir($upload_dir);
    }

    // Nom fichier sécurisé
    $filename = dol_sanitizeFileName($file_info['name']);
    $filepath = $upload_dir.'/'.$filename;

    // Si fichier existe, ajouter timestamp
    if (file_exists($filepath)) {
        $info = pathinfo($filename);
        $filename = $info['filename'].'_'.time().'.'.($info['extension'] ?? '');
        $filepath = $upload_dir.'/'.$filename;
    }

    // Déplacer le fichier
    if (move_uploaded_file($file_info['tmp_name'], $filepath)) {
        @chmod($filepath, 0644);
        return $filename;
    } else {
        $error = 'Impossible de déplacer le fichier';
        return false;
    }
}

/**
 * Lister les fichiers d'un rapport
 */
function mv3_list_files($report_id)
{
    global $conf;

    $upload_dir = $conf->mv3pro_portail->dir_output.'/report/'.$report_id;

    if (!dol_is_dir($upload_dir)) {
        return array();
    }

    $files = array();
    $file_list = dol_dir_list($upload_dir, 'files');

    if (is_array($file_list)) {
        foreach ($file_list as $file) {
            $files[] = array(
                'name' => $file['name'],
                'size' => $file['size'],
                'date' => $file['date'],
                'path' => $file['fullname'],
                'url' => '/document.php?modulepart=mv3pro_portail&file=report/'.$report_id.'/'.urlencode($file['name'])
            );
        }
    }

    return $files;
}

/**
 * Supprimer un fichier d'un rapport
 */
function mv3_delete_file($report_id, $filename, &$error = '')
{
    global $conf;

    $filename = dol_sanitizeFileName($filename);
    $filepath = $conf->mv3pro_portail->dir_output.'/report/'.$report_id.'/'.$filename;

    if (!file_exists($filepath)) {
        $error = 'Fichier introuvable';
        return false;
    }

    if (unlink($filepath)) {
        return true;
    } else {
        $error = 'Impossible de supprimer le fichier';
        return false;
    }
}

/**
 * Valider type de fichier (images seulement)
 */
function mv3_validate_image($file_info, &$error = '')
{
    $allowed = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_info['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        $error = 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP';
        return false;
    }

    // Taille max 10 MB
    if ($file_info['size'] > 10 * 1024 * 1024) {
        $error = 'Fichier trop volumineux (max 10 MB)';
        return false;
    }

    return true;
}
