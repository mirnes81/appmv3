<?php
/**
 * API v1 - Régie - Signature
 *
 * POST /api/v1/regie_signature.php
 *
 * Enregistre la signature électronique d'un bon de régie
 *
 * Body:
 * {
 *   "regie_id": 123,
 *   "signature_data": "data:image/png;base64,...",
 *   "latitude": 48.8566,
 *   "longitude": 2.3522
 * }
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Auth requise
$auth = require_auth();
require_rights('write', $auth);

// Méthode POST uniquement
require_method('POST');

// Récupérer body JSON
$body = get_json_body(true);
$regie_id = isset($body['regie_id']) ? (int)$body['regie_id'] : 0;
require_param($regie_id, 'regie_id');

$signature_data = isset($body['signature_data']) ? trim($body['signature_data']) : '';
require_param($signature_data, 'signature_data');

$latitude = isset($body['latitude']) ? (float)$body['latitude'] : null;
$longitude = isset($body['longitude']) ? (float)$body['longitude'] : null;

// Vérifier que la régie existe
$sql = "SELECT rowid, status FROM ".MAIN_DB_PREFIX."mv3_regie";
$sql .= " WHERE rowid = ".(int)$regie_id;
$sql .= " AND entity IN (".getEntity('project').")";

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Régie non trouvée', 'NOT_FOUND', 404);
}

$regie = $db->fetch_object($resql);

// Vérifier que la régie n'est pas déjà signée
if ((int)$regie->status >= 3) {
    json_error('Cette régie est déjà signée', 'ALREADY_SIGNED', 400);
}

// Valider base64
if (!preg_match('/^data:image\/(png|jpeg);base64,/', $signature_data)) {
    json_error('Format de signature invalide', 'INVALID_FORMAT', 400);
}

// Extraire et décoder l'image
$signature_data = preg_replace('/^data:image\/(png|jpeg);base64,/', '', $signature_data);
$image_data = base64_decode($signature_data);

if ($image_data === false) {
    json_error('Erreur de décodage de la signature', 'DECODE_ERROR', 400);
}

// Répertoire de destination
$signature_dir = $conf->mv3pro_portail->dir_output . '/regie_signatures';

if (!is_dir($signature_dir)) {
    if (dol_mkdir($signature_dir, 0755) < 0) {
        json_error('Impossible de créer le répertoire de signatures', 'MKDIR_ERROR', 500);
    }
}

// Sauvegarder l'image
$filename = 'signature_regie_' . $regie_id . '_' . time() . '.png';
$filepath = $signature_dir . '/' . $filename;

if (file_put_contents($filepath, $image_data) === false) {
    json_error('Erreur lors de la sauvegarde de la signature', 'SAVE_ERROR', 500);
}

// Mettre à jour la régie
$sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_regie SET";
$sql_update .= " status = 3,";
$sql_update .= " date_signature = NOW(),";
$sql_update .= " sign_latitude = ".($latitude !== null ? (float)$latitude : "NULL").",";
$sql_update .= " sign_longitude = ".($longitude !== null ? (float)$longitude : "NULL").",";
$sql_update .= " sign_ip = '".$db->escape($_SERVER['REMOTE_ADDR'])."',";
$sql_update .= " sign_useragent = '".$db->escape($_SERVER['HTTP_USER_AGENT'] ?? '')."'";
$sql_update .= " WHERE rowid = ".(int)$regie_id;

$resql_update = $db->query($sql_update);

if (!$resql_update) {
    json_error('Erreur lors de la mise à jour de la régie', 'UPDATE_ERROR', 500);
}

// Réponse
json_ok([
    'signed' => true,
    'signature' => [
        'filename' => $filename,
        'url' => '/custom/mv3pro_portail/document.php?modulepart=mv3pro_portail&file=regie_signatures/'.urlencode($filename),
        'date' => date('Y-m-d H:i:s'),
        'latitude' => $latitude,
        'longitude' => $longitude
    ]
]);
