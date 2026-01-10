<?php
/**
 * Sert les photos depuis le système de fichiers
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();

global $db, $user;

requireMobileSession('../login_mobile.php');

$id = GETPOST('id', 'int');
if (empty($id)) {
    http_response_code(400);
    exit;
}

// Récupérer la photo
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE rowid = ".(int)$id;
$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) == 0) {
    http_response_code(404);
    exit;
}

$photo = $db->fetch_object($resql);

// Construire le chemin du fichier
$filepath = DOL_DATA_ROOT.'/'.ltrim($photo->path, '/');

if (!file_exists($filepath)) {
    http_response_code(404);
    exit;
}

// Déterminer le type MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filepath);
finfo_close($finfo);

// Envoyer les headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, max-age=31536000');

// Envoyer le fichier
readfile($filepath);
exit;
?>
