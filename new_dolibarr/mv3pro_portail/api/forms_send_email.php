<?php
/**
 * Envoi du formulaire par email
 *
 * POST /forms/send_email
 * Header: X-Auth-Token: ...
 * Body: {form_id, email_to, subject, message}
 * Returns: {"success": true}
 */

require_once __DIR__ . '/cors_config.php';
require_once __DIR__ . '/auth_helper.php';

header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

$user = checkAuth();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

$form_id = (int)($input['form_id'] ?? 0);
$email_to = $input['email_to'] ?? '';
$subject = $input['subject'] ?? 'Rapport de chantier';
$message = $input['message'] ?? '';

if (!$form_id || !$email_to) {
    http_response_code(400);
    echo json_encode(['error' => 'form_id et email_to requis']);
    exit;
}

if (!filter_var($email_to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email invalide']);
    exit;
}

$sql = "SELECT r.*, p.ref as projet_ref, p.title as projet_title,";
$sql .= " u.firstname, u.lastname, u.email as user_email";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_rapport as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user";
$sql .= " WHERE r.rowid = ".$form_id;
$sql .= " AND r.entity = ".$conf->entity;

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Formulaire non trouvé']);
    exit;
}

$obj = $db->fetch_object($resql);

if (empty($message)) {
    $message = "Bonjour,\n\n";
    $message .= "Veuillez trouver ci-joint le rapport de chantier.\n\n";
    $message .= "Détails du rapport:\n";
    $message .= "- Date: ".$obj->date_rapport."\n";
    $message .= "- Client: ".$obj->zone_travail."\n";

    if ($obj->projet_ref) {
        $message .= "- Projet: ".$obj->projet_ref." - ".$obj->projet_title."\n";
    }

    if ($obj->heures_debut && $obj->heures_fin) {
        $message .= "- Horaires: ".$obj->heures_debut." - ".$obj->heures_fin."\n";
    }

    $message .= "\nCordialement,\n";
    $message .= $obj->firstname." ".$obj->lastname;
}

$pdf_dir = DOL_DATA_ROOT.'/mv3pro_portail/pdf/';
if (!is_dir($pdf_dir)) {
    dol_mkdir($pdf_dir);
}

$pdf_file = $pdf_dir.'rapport_'.$form_id.'_'.time().'.pdf';

$pdf_url = DOL_MAIN_URL_ROOT.'/custom/mv3pro_portail/api/forms_pdf.php?id='.$form_id;
$pdf_content = file_get_contents($pdf_url.'&token='.urlencode($_SERVER['HTTP_X_AUTH_TOKEN'] ?? ''));

if ($pdf_content) {
    file_put_contents($pdf_file, $pdf_content);
}

$from = $conf->global->MAIN_MAIL_EMAIL_FROM ?? $obj->user_email ?? 'noreply@mv-3pro.ch';
$from_name = $conf->global->MAIN_INFO_SOCIETE_NOM ?? 'MV3 PRO';

$attachments = [];
if (file_exists($pdf_file)) {
    $attachments[] = $pdf_file;
}

$mail = new CMailFile(
    $subject,
    $email_to,
    $from,
    $message,
    $attachments,
    [],
    [],
    '',
    '',
    0,
    -1,
    '',
    '',
    '',
    '',
    'mail'
);

$result = $mail->sendfile();

if (file_exists($pdf_file)) {
    unlink($pdf_file);
}

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Email envoyé avec succès'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de l\'envoi de l\'email',
        'details' => $mail->error
    ]);
}
