<?php
/**
 * API v1 - Sens de Pose - Signature
 * POST /api/v1/sens_pose_signature.php
 * Body: {"sens_pose_id": 123, "signature_data": "data:image/png;base64,..."}
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$auth = require_auth();
require_rights('write', $auth);
require_method('POST');

$body = get_json_body(true);
$id = isset($body['sens_pose_id']) ? (int)$body['sens_pose_id'] : 0;
$signature_data = isset($body['signature_data']) ? trim($body['signature_data']) : '';
require_param($id, 'sens_pose_id');
require_param($signature_data, 'signature_data');

// Vérifier existe
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".$id;
if (!$db->query($sql) || $db->num_rows($db->query($sql)) === 0) json_error('Non trouvé', 'NOT_FOUND', 404);

// Sauvegarder signature
$signature_data = preg_replace('/^data:image\/(png|jpeg);base64,/', '', $signature_data);
$image_data = base64_decode($signature_data);

$sig_dir = $conf->mv3pro_portail->dir_output . '/sens_pose_signatures';
if (!is_dir($sig_dir)) dol_mkdir($sig_dir, 0755);

$filename = 'signature_sp_' . $id . '_' . time() . '.png';
$filepath = $sig_dir . '/' . $filename;
file_put_contents($filepath, $image_data);

// Update status
$sql = "UPDATE ".MAIN_DB_PREFIX."mv3_sens_pose SET status = 2, date_signature = NOW() WHERE rowid = ".$id;
$db->query($sql);

json_ok(['signed' => true, 'signature' => ['filename' => $filename]]);
