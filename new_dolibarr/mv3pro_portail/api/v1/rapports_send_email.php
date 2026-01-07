<?php
/**
 * API v1 - Rapports - Envoi email
 *
 * POST /api/v1/rapports_send_email.php
 *
 * Envoie le PDF d'un rapport par email
 *
 * Body:
 * {
 *   "rapport_id": 123,
 *   "to": "client@example.com",  // optionnel, sinon email du projet/client
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
$rapport_id = isset($body['rapport_id']) ? (int)$body['rapport_id'] : 0;
require_param($rapport_id, 'rapport_id');

$to = isset($body['to']) ? trim($body['to']) : '';
$subject = isset($body['subject']) ? trim($body['subject']) : '';
$message = isset($body['message']) ? trim($body['message']) : '';

// Récupérer le rapport
$sql = "SELECT r.*,
        u.lastname, u.firstname, u.email as user_email,
        p.ref as projet_ref, p.title as projet_title,
        t.nom as tiers_nom, t.email as client_email";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_rapport as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as t ON t.rowid = p.fk_soc";
$sql .= " WHERE r.rowid = ".(int)$rapport_id;
$sql .= " AND r.entity IN (".getEntity('project').")";

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Rapport non trouvé', 'NOT_FOUND', 404);
}

$rapport = $db->fetch_object($resql);

// Vérifier droits d'accès
if (!empty($auth['rights']['worker']) &&
    empty($auth['rights']['write']) &&
    $rapport->fk_user != $auth['user_id']) {
    json_error('Accès refusé à ce rapport', 'FORBIDDEN', 403);
}

// Déterminer destinataire
if (empty($to)) {
    // Utiliser email du client si disponible
    $to = $rapport->client_email;
}

if (empty($to)) {
    json_error('Aucune adresse email de destination. Veuillez spécifier "to" ou lier un client avec email au projet', 'NO_RECIPIENT', 400);
}

// Valider email
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    json_error('Adresse email invalide: ' . $to, 'INVALID_EMAIL', 400);
}

// Chercher le PDF (le générer si n'existe pas)
$pdf_dir = $conf->mv3pro_portail->dir_output . '/rapports_pdf';
$pdf_filename = 'rapport_' . $rapport->date_rapport . '_' . $rapport->rowid . '.pdf';
$pdf_path = $pdf_dir . '/' . $pdf_filename;

if (!file_exists($pdf_path)) {
    // Générer le PDF d'abord
    // Inclure la logique de génération ou faire un appel interne
    // Pour simplifier, retourner erreur demandant de générer d'abord
    json_error('PDF non trouvé. Veuillez d\'abord générer le PDF via /api/v1/rapports_pdf.php', 'PDF_NOT_FOUND', 404);
}

// Préparer le sujet
if (empty($subject)) {
    $subject = 'Rapport journalier - ' . dol_print_date(strtotime($rapport->date_rapport), 'day');
    if ($rapport->projet_ref) {
        $subject .= ' - ' . $rapport->projet_ref;
    }
}

// Préparer le message
if (empty($message)) {
    $message = "Bonjour,\n\n";
    $message .= "Veuillez trouver ci-joint le rapport journalier du " . dol_print_date(strtotime($rapport->date_rapport), 'day') . ".\n\n";

    if ($rapport->projet_ref) {
        $message .= "Projet: " . $rapport->projet_ref . " - " . $rapport->projet_title . "\n";
    }

    if ($rapport->zone_travail) {
        $message .= "Zone: " . $rapport->zone_travail . "\n";
    }

    $message .= "\nCordialement,\n";
    $message .= trim($rapport->firstname . ' ' . $rapport->lastname);
}

// Email expéditeur (config Dolibarr)
$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
$from_name = $conf->global->MAIN_INFO_SOCIETE_NOM;

if (empty($from)) {
    $from = 'noreply@' . $_SERVER['HTTP_HOST'];
    $from_name = 'MV3 PRO';
}

// Créer l'email avec pièce jointe
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
    $attachments,
    [],  // BCC
    [],  // CC
    '',  // From name
    '',  // Reply to
    0,   // Delivery receipt
    -1,  // Message type
    '',  // Errors to
    '',  // CSS
    '',  // Track ID
    '',  // Headers
    'mail' // Transport
);

$result = $mail->sendfile();

if (!$result) {
    $error_msg = $mail->error ? $mail->error : 'Erreur inconnue lors de l\'envoi';
    json_error('Erreur lors de l\'envoi de l\'email: ' . $error_msg, 'SEND_ERROR', 500);
}

// Réponse
json_ok([
    'sent' => true,
    'to' => $to,
    'subject' => $subject,
    'attachments' => [
        [
            'filename' => $pdf_filename,
            'size' => filesize($pdf_path)
        ]
    ]
]);
