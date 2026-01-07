<?php
/**
 * API v1 - Rapports - Génération PDF
 *
 * POST /api/v1/rapports_pdf.php
 *
 * Génère un PDF pour un rapport
 *
 * Body:
 * {
 *   "rapport_id": 123
 * }
 *
 * Response:
 * {
 *   "success": true,
 *   "pdf": {
 *     "filename": "rapport_RAP000123.pdf",
 *     "url": "/document.php?...",
 *     "size": 245678
 *   }
 * }
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Auth requise
$auth = require_auth();

// Méthode POST uniquement
require_method('POST');

// Récupérer body JSON
$body = get_json_body(true);
$rapport_id = isset($body['rapport_id']) ? (int)$body['rapport_id'] : 0;
require_param($rapport_id, 'rapport_id');

// Récupérer le rapport
$sql = "SELECT r.*,
        u.lastname, u.firstname, u.login,
        p.ref as projet_ref, p.title as projet_title,
        t.nom as tiers_nom, t.address, t.zip, t.town";
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
    empty($auth['rights']['read']) &&
    $rapport->fk_user != $auth['user_id']) {
    json_error('Accès refusé à ce rapport', 'FORBIDDEN', 403);
}

// Récupérer les photos
$sql_photos = "SELECT filepath FROM ".MAIN_DB_PREFIX."mv3_rapport_photo";
$sql_photos .= " WHERE fk_rapport = ".(int)$rapport_id;
$sql_photos .= " ORDER BY ordre ASC, date_upload ASC";

$resql_photos = $db->query($sql_photos);
$photos = [];

if ($resql_photos) {
    while ($photo = $db->fetch_object($resql_photos)) {
        $full_path = $conf->mv3pro_portail->dir_output . '/' . $photo->filepath;
        if (file_exists($full_path)) {
            $photos[] = $full_path;
        }
    }
}

// Créer PDF simple avec TCPDF
require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Informations document
$pdf->SetCreator('MV3 PRO');
$pdf->SetAuthor('MV3 PRO');
$pdf->SetTitle('Rapport ' . $rapport->date_rapport);

// Marges
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// Police
$pdf->SetFont('helvetica', '', 10);

// Page
$pdf->AddPage();

// En-tête
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Rapport Journalier', 0, 1, 'C');
$pdf->Ln(5);

// Informations générales
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 6, 'Date:', 0, 0);
$pdf->Cell(0, 6, dol_print_date(strtotime($rapport->date_rapport), 'day'), 0, 1);

if ($rapport->projet_ref) {
    $pdf->Cell(50, 6, 'Projet:', 0, 0);
    $pdf->Cell(0, 6, $rapport->projet_ref . ' - ' . $rapport->projet_title, 0, 1);
}

if ($rapport->tiers_nom) {
    $pdf->Cell(50, 6, 'Client:', 0, 0);
    $pdf->Cell(0, 6, $rapport->tiers_nom, 0, 1);
}

$pdf->Cell(50, 6, 'Intervenant:', 0, 0);
$pdf->Cell(0, 6, trim($rapport->firstname . ' ' . $rapport->lastname), 0, 1);

if ($rapport->heures_debut && $rapport->heures_fin) {
    $pdf->Cell(50, 6, 'Horaires:', 0, 0);
    $pdf->Cell(0, 6, $rapport->heures_debut . ' - ' . $rapport->heures_fin . ' (' . $rapport->temps_total . 'h)', 0, 1);
}

$pdf->Ln(5);

// Zone de travail
if ($rapport->zone_travail) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Zone de travail:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $rapport->zone_travail, 0, 'L');
    $pdf->Ln(3);
}

// Travaux réalisés
if ($rapport->travaux_realises) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Travaux réalisés:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $rapport->travaux_realises, 0, 'L');
    $pdf->Ln(3);
}

// Observations
if ($rapport->observations) {
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Observations:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $rapport->observations, 0, 'L');
    $pdf->Ln(3);
}

// Photos
if (count($photos) > 0) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Photos (' . count($photos) . ')', 0, 1);
    $pdf->Ln(3);

    $max_photos_per_page = 4;
    $photo_count = 0;

    foreach ($photos as $photo_path) {
        if ($photo_count > 0 && $photo_count % $max_photos_per_page === 0) {
            $pdf->AddPage();
        }

        // Ajouter image (largeur max 170mm pour marges 15mm)
        try {
            $pdf->Image($photo_path, '', '', 170, 0, '', '', '', true, 300, '', false, false, 1);
            $pdf->Ln(5);
        } catch (Exception $e) {
            // Si erreur image, ignorer
        }

        $photo_count++;
    }
}

// Répertoire de sortie
$output_dir = $conf->mv3pro_portail->dir_output . '/rapports_pdf';
if (!is_dir($output_dir)) {
    dol_mkdir($output_dir, 0755);
}

// Nom fichier
$filename = 'rapport_' . $rapport->date_rapport . '_' . $rapport->rowid . '.pdf';
$filepath = $output_dir . '/' . $filename;

// Sauvegarder
$pdf->Output($filepath, 'F');

// Vérifier création
if (!file_exists($filepath)) {
    json_error('Erreur lors de la génération du PDF', 'PDF_ERROR', 500);
}

// Réponse
json_ok([
    'pdf' => [
        'filename' => $filename,
        'url' => '/custom/mv3pro_portail/document.php?modulepart=mv3pro_portail&file=rapports_pdf/'.urlencode($filename),
        'size' => filesize($filepath)
    ]
]);
