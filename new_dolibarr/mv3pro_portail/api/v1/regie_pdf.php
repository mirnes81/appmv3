<?php
/**
 * API v1 - Régie - Génération PDF
 *
 * POST /api/v1/regie_pdf.php
 *
 * Génère un PDF pour un bon de régie
 *
 * Body:
 * {
 *   "regie_id": 123
 * }
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Auth requise
$auth = require_auth();

// Méthode POST uniquement
require_method('POST');

// Récupérer body JSON
$body = get_json_body(true);
$regie_id = isset($body['regie_id']) ? (int)$body['regie_id'] : 0;
require_param($regie_id, 'regie_id');

// Récupérer la régie avec détails
$sql = "SELECT r.*,
        p.ref as projet_ref, p.title as projet_title,
        s.nom as client_nom, s.address, s.zip, s.town";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_project";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = r.fk_soc";
$sql .= " WHERE r.rowid = ".(int)$regie_id;
$sql .= " AND r.entity IN (".getEntity('project').")";

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Régie non trouvée', 'NOT_FOUND', 404);
}

$regie = $db->fetch_object($resql);

// Récupérer les lignes
$sql_lines = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_regie_line";
$sql_lines .= " WHERE fk_regie = ".(int)$regie_id;
$sql_lines .= " ORDER BY rowid ASC";

$resql_lines = $db->query($sql_lines);
$lines = [];

if ($resql_lines) {
    while ($line = $db->fetch_object($resql_lines)) {
        $lines[] = $line;
    }
}

// Créer PDF avec TCPDF
require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('MV3 PRO');
$pdf->SetTitle('Bon de régie ' . $regie->ref);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->SetFont('helvetica', '', 10);
$pdf->AddPage();

// En-tête
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 10, 'BON DE RÉGIE', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, $regie->ref, 0, 1, 'C');
$pdf->Ln(5);

// Informations
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 6, 'Date:', 0, 0);
$pdf->Cell(0, 6, dol_print_date(strtotime($regie->date_regie), 'day'), 0, 1);

if ($regie->location_text) {
    $pdf->Cell(50, 6, 'Lieu:', 0, 0);
    $pdf->Cell(0, 6, $regie->location_text, 0, 1);
}

if ($regie->projet_ref) {
    $pdf->Cell(50, 6, 'Projet:', 0, 0);
    $pdf->Cell(0, 6, $regie->projet_ref . ' - ' . $regie->projet_title, 0, 1);
}

if ($regie->client_nom) {
    $pdf->Cell(50, 6, 'Client:', 0, 0);
    $pdf->Cell(0, 6, $regie->client_nom, 0, 1);
}

$pdf->Ln(5);

// Tableau des lignes
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(70, 7, 'Description', 1, 0, 'L');
$pdf->Cell(20, 7, 'Qté', 1, 0, 'C');
$pdf->Cell(25, 7, 'P.U. HT', 1, 0, 'R');
$pdf->Cell(20, 7, 'TVA %', 1, 0, 'C');
$pdf->Cell(30, 7, 'Total HT', 1, 1, 'R');

$pdf->SetFont('helvetica', '', 9);

foreach ($lines as $line) {
    $pdf->Cell(70, 6, $line->description, 1, 0, 'L');
    $pdf->Cell(20, 6, number_format($line->qty, 2, ',', ''), 1, 0, 'C');
    $pdf->Cell(25, 6, number_format($line->unit_price, 2, ',', ' ') . ' €', 1, 0, 'R');
    $pdf->Cell(20, 6, number_format($line->tva_tx, 2, ',', ''), 1, 0, 'C');
    $pdf->Cell(30, 6, number_format($line->total_ht, 2, ',', ' ') . ' €', 1, 1, 'R');
}

// Totaux
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(135, 7, 'Total HT', 1, 0, 'R');
$pdf->Cell(30, 7, number_format($regie->total_ht, 2, ',', ' ') . ' €', 1, 1, 'R');

$pdf->Cell(135, 7, 'Total TVA', 1, 0, 'R');
$pdf->Cell(30, 7, number_format($regie->total_tva, 2, ',', ' ') . ' €', 1, 1, 'R');

$pdf->Cell(135, 7, 'Total TTC', 1, 0, 'R');
$pdf->Cell(30, 7, number_format($regie->total_ttc, 2, ',', ' ') . ' €', 1, 1, 'R');

// Notes
if ($regie->note_public) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Notes:', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $regie->note_public, 0, 'L');
}

// Répertoire de sortie
$output_dir = $conf->mv3pro_portail->dir_output . '/regie_pdf';
if (!is_dir($output_dir)) {
    dol_mkdir($output_dir, 0755);
}

$filename = 'regie_' . $regie->ref . '.pdf';
$filepath = $output_dir . '/' . $filename;

$pdf->Output($filepath, 'F');

if (!file_exists($filepath)) {
    json_error('Erreur lors de la génération du PDF', 'PDF_ERROR', 500);
}

json_ok([
    'pdf' => [
        'filename' => $filename,
        'url' => '/custom/mv3pro_portail/document.php?modulepart=mv3pro_portail&file=regie_pdf/'.urlencode($filename),
        'size' => filesize($filepath)
    ]
]);
