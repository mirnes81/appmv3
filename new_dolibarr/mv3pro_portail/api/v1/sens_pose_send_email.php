<?php
/**
 * API v1 - Sens de Pose - Email
 * POST /api/v1/sens_pose_send_email.php
 * Body: {"sens_pose_id": 123, "to": "client@example.com"}
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

$auth = require_auth();
require_rights('write', $auth);
require_method('POST');

$body = get_json_body(true);
$id = isset($body['sens_pose_id']) ? (int)$body['sens_pose_id'] : 0;
$to = isset($body['to']) ? trim($body['to']) : '';
require_param($id, 'sens_pose_id');
require_param($to, 'to');

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".$id;
$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) json_error('Non trouvé', 'NOT_FOUND', 404);

$obj = $db->fetch_object($resql);

$pdf_path = $conf->mv3pro_portail->dir_output . '/sens_pose_pdf/sens_pose_' . $obj->ref . '.pdf';
if (!file_exists($pdf_path)) json_error('PDF non trouvé', 'PDF_NOT_FOUND', 404);

$subject = 'Sens de pose - ' . $obj->ref;
$message = 'Veuillez trouver ci-joint le sens de pose.';
$from = $conf->global->MAIN_MAIL_EMAIL_FROM ?: 'noreply@localhost';

$mail = new CMailFile($subject, $to, $from, $message, [['path' => $pdf_path, 'name' => basename($pdf_path), 'mime' => 'application/pdf']]);
if (!$mail->sendfile()) json_error('Erreur envoi', 'SEND_ERROR', 500);

json_ok(['sent' => true, 'to' => $to]);
