<?php
/**
 * API v1 - Régie - Envoi email
 *
 * POST /api/v1/regie_send_email.php
 *
 * Envoie le PDF d'un bon de régie par email
 *
 * Body:
 * {
 *   "regie_id": 123,
 *   "to": "client@example.com",  // optionnel
 *   "subject": "Sujet custom",    // optionnel
 *   "message": "Message custom"   // optionnel
 * }
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

// Auth requise
$auth = require_auth();
require_rights('write', $auth);

// Méthode POST uniquement
require_method('POST');

// Récupérer body JSON
$body = get_json_body(true);
$regie_id = isset($body['regie_id']) ? (int)$body['regie_id'] : 0;
require_param($regie_id, 'regie_id');

$to = isset($body['to']) ? trim($body['to']) : '';
$subject = isset($body['subject']) ? trim($body['subject']) : '';
$message = isset($body['message']) ? trim($body['message']) : '';

// Récupérer la régie
$sql = "SELECT r.*, s.email as client_email, s.nom as client_nom";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = r.fk_soc";
$sql .= " WHERE r.rowid = ".(int)$regie_id;
$sql .= " AND r.entity IN (".getEntity('project').")";

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Régie non trouvée', 'NOT_FOUND', 404);
}

$regie = $db->fetch_object($resql);

// Destinataire
if (empty($to)) {
    $to = $regie->client_email;
}

if (empty($to)) {
    json_error('Aucune adresse email de destination', 'NO_RECIPIENT', 400);
}

if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    json_error('Adresse email invalide: ' . $to, 'INVALID_EMAIL', 400);
}

// Chercher le PDF
$pdf_dir = $conf->mv3pro_portail->dir_output . '/regie_pdf';
$pdf_filename = 'regie_' . $regie->ref . '.pdf';
$pdf_path = $pdf_dir . '/' . $pdf_filename;

if (!file_exists($pdf_path)) {
    json_error('PDF non trouvé. Veuillez d\'abord générer le PDF', 'PDF_NOT_FOUND', 404);
}

// Sujet
if (empty($subject)) {
    $subject = 'Bon de régie - ' . $regie->ref;
}

// Message
if (empty($message)) {
    $message = "Bonjour,\n\n";
    $message .= "Veuillez trouver ci-joint le bon de régie " . $regie->ref . ".\n\n";
    $message .= "Date: " . dol_print_date(strtotime($regie->date_regie), 'day') . "\n";
    $message .= "Montant TTC: " . number_format($regie->total_ttc, 2, ',', ' ') . " €\n\n";
    $message .= "Cordialement";
}

// Email expéditeur
$from = $conf->global->MAIN_MAIL_EMAIL_FROM ?: 'noreply@' . $_SERVER['HTTP_HOST'];

// Créer l'email
$attachments = [
    [
        'path' => $pdf_path,
        'name' => $pdf_filename,
        'mime' => 'application/pdf'
    ]
];

$mail = new CMailFile(
    $subject,
    $to,
    $from,
    $message,
    $attachments
);

$result = $mail->sendfile();

if (!$result) {
    $error_msg = $mail->error ?: 'Erreur inconnue';
    json_error('Erreur lors de l\'envoi: ' . $error_msg, 'SEND_ERROR', 500);
}

json_ok([
    'sent' => true,
    'to' => $to,
    'subject' => $subject
]);
