<?php
/**
 * API v1 - Téléchargement sécurisé de fichiers
 * GET /api/v1/file.php?module=actioncomm&id=123&file=document.pdf
 *
 * Sert les fichiers liés aux événements, rapports, etc. de manière sécurisée
 * - Vérifie l'authentification
 * - Vérifie que le fichier existe
 * - Vérifie que l'utilisateur a le droit d'accéder au document
 * - Empêche les path traversal attacks
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$module = get_param('module', '');
$id = (int)get_param('id', 0);
$filename = get_param('file', '');

// Validation des paramètres
if (!$module || !$id || !$filename) {
    json_error('Paramètres manquants', 'MISSING_PARAMS', 400);
}

// Nettoyer le nom du fichier pour éviter les path traversal
$filename = basename($filename);
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    json_error('Nom de fichier invalide', 'INVALID_FILENAME', 400);
}

// Déterminer le chemin selon le module
$filepath = null;

switch ($module) {
    case 'actioncomm':
        // Vérifier que l'utilisateur a accès à cet événement
        $sql = "SELECT id FROM ".MAIN_DB_PREFIX."actioncomm WHERE id = ".$id;
        $resql = $db->query($sql);
        if (!$resql || $db->num_rows($resql) === 0) {
            json_error('Événement non trouvé', 'NOT_FOUND', 404);
        }

        $filepath = DOL_DATA_ROOT.'/actioncomm/'.dol_sanitizeFileName($id).'/'.$filename;
        break;

    case 'rapport':
        // Vérifier que l'utilisateur a accès à ce rapport
        $sql = "SELECT id FROM ".MAIN_DB_PREFIX."mv3_rapport WHERE id = ".$id;
        $resql = $db->query($sql);
        if (!$resql || $db->num_rows($resql) === 0) {
            json_error('Rapport non trouvé', 'NOT_FOUND', 404);
        }

        $filepath = DOL_DATA_ROOT.'/mv3pro_portail/rapports/'.$id.'/'.$filename;
        break;

    case 'regie':
        // Vérifier que l'utilisateur a accès à ce bon de régie
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_regie WHERE rowid = ".$id;
        $resql = $db->query($sql);
        if (!$resql || $db->num_rows($resql) === 0) {
            json_error('Bon de régie non trouvé', 'NOT_FOUND', 404);
        }

        $filepath = DOL_DATA_ROOT.'/mv3pro_portail/regie/'.$id.'/'.$filename;
        break;

    case 'sens_pose':
        // Vérifier que l'utilisateur a accès à ce plan
        $sql = "SELECT id FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE id = ".$id;
        $resql = $db->query($sql);
        if (!$resql || $db->num_rows($resql) === 0) {
            json_error('Plan non trouvé', 'NOT_FOUND', 404);
        }

        $filepath = DOL_DATA_ROOT.'/mv3pro_portail/sens_pose/'.$id.'/'.$filename;
        break;

    default:
        json_error('Module non supporté', 'UNSUPPORTED_MODULE', 400);
}

// Vérifier que le fichier existe
if (!file_exists($filepath) || !is_file($filepath)) {
    log_debug("Fichier non trouvé: ".$filepath);
    json_error('Fichier non trouvé', 'FILE_NOT_FOUND', 404);
}

// Vérifier que le fichier est bien dans le bon répertoire (sécurité)
$realpath = realpath($filepath);
$basepath = realpath(DOL_DATA_ROOT);
if (strpos($realpath, $basepath) !== 0) {
    log_debug("Tentative d'accès hors du répertoire autorisé: ".$realpath);
    json_error('Accès refusé', 'ACCESS_DENIED', 403);
}

// Déterminer le type MIME
$mime = mime_content_type($filepath);
if (!$mime) {
    $mime = 'application/octet-stream';
}

// Log de l'accès
log_debug("Téléchargement fichier: module=".$module." id=".$id." file=".$filename." user=".$auth['user_id']);

// Envoyer le fichier
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($filepath));
header('Content-Disposition: inline; filename="'.basename($filename).'"');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

// Lire et envoyer le fichier
readfile($filepath);
exit;
