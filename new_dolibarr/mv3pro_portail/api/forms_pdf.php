<?php
/**
 * Génération PDF d'un formulaire
 *
 * GET /forms/pdf/{id}
 * Header: X-Auth-Token: ...
 * Returns: PDF file
 */

require_once __DIR__ . '/cors_config.php';
require_once __DIR__ . '/auth_helper.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$user = checkAuth();

$form_id = (int)($_GET['id'] ?? 0);

if (!$form_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID requis']);
    exit;
}

$sql = "SELECT r.*, p.ref as projet_ref, p.title as projet_title,";
$sql .= " u.firstname, u.lastname,";
$sql .= " s.nom as client_nom, s.address, s.zip, s.town, s.country_code";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_rapport as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
$sql .= " WHERE r.rowid = ".$form_id;
$sql .= " AND r.entity = ".$conf->entity;

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Formulaire non trouvé']);
    exit;
}

$obj = $db->fetch_object($resql);

require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator('MV3 PRO');
$pdf->SetAuthor('MV3 PRO');
$pdf->SetTitle('Rapport de chantier - '.$obj->date_rapport);

$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'RAPPORT DE CHANTIER', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Référence: RAPPORT-'.$obj->rowid, 0, 1, 'R');
$pdf->Cell(0, 5, 'Date: '.dol_print_date($obj->date_rapport, 'day'), 0, 1, 'R');
$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'CLIENT', 0, 1);
$pdf->SetFont('helvetica', '', 10);

if ($obj->client_nom) {
    $pdf->Cell(0, 5, $obj->client_nom, 0, 1);
    if ($obj->address) $pdf->Cell(0, 5, $obj->address, 0, 1);
    if ($obj->zip || $obj->town) {
        $pdf->Cell(0, 5, $obj->zip.' '.$obj->town, 0, 1);
    }
} else {
    $pdf->Cell(0, 5, $obj->zone_travail, 0, 1);
}

$pdf->Ln(5);

if ($obj->projet_ref) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'PROJET', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, $obj->projet_ref.' - '.$obj->projet_title, 0, 1);
    $pdf->Ln(5);
}

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'DÉTAILS DU RAPPORT', 0, 1);
$pdf->SetFont('helvetica', '', 10);

if ($obj->heures_debut && $obj->heures_fin) {
    $pdf->Cell(50, 5, 'Horaires:', 0, 0);
    $pdf->Cell(0, 5, $obj->heures_debut.' - '.$obj->heures_fin, 0, 1);
}

if ($obj->temps_total) {
    $pdf->Cell(50, 5, 'Temps total:', 0, 0);
    $pdf->Cell(0, 5, number_format($obj->temps_total, 2).' heures', 0, 1);
}

$pdf->Cell(50, 5, 'Intervenant:', 0, 0);
$pdf->Cell(0, 5, $obj->firstname.' '.$obj->lastname, 0, 1);

$pdf->Ln(5);

if ($obj->description) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'DESCRIPTION', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $obj->description, 0, 'L');
    $pdf->Ln(3);
}

if ($obj->travaux_realises) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'TRAVAUX RÉALISÉS', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $obj->travaux_realises, 0, 'L');
    $pdf->Ln(3);
}

if ($obj->observations) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'OBSERVATIONS', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, $obj->observations, 0, 'L');
    $pdf->Ln(3);
}

if ($obj->meteo_temperature || $obj->meteo_condition) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'CONDITIONS MÉTÉO', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    if ($obj->meteo_temperature) {
        $pdf->Cell(50, 5, 'Température:', 0, 0);
        $pdf->Cell(0, 5, $obj->meteo_temperature.'°C', 0, 1);
    }

    if ($obj->meteo_condition) {
        $pdf->Cell(50, 5, 'Conditions:', 0, 0);
        $pdf->Cell(0, 5, $obj->meteo_condition, 0, 1);
    }

    $pdf->Ln(3);
}

$sql_photos = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE fk_rapport = ".$form_id." ORDER BY ordre LIMIT 4";
$resql_photos = $db->query($sql_photos);

if ($resql_photos && $db->num_rows($resql_photos) > 0) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'PHOTOS', 0, 1);
    $pdf->Ln(3);

    $x_start = 15;
    $y_start = $pdf->GetY();
    $img_width = 85;
    $img_height = 60;
    $count = 0;

    while ($photo = $db->fetch_object($resql_photos)) {
        $photo_path = DOL_DATA_ROOT.'/'.$photo->filepath;

        if (file_exists($photo_path)) {
            $x = $x_start + ($count % 2) * ($img_width + 10);
            $y = $y_start + floor($count / 2) * ($img_height + 15);

            $pdf->Image($photo_path, $x, $y, $img_width, $img_height, '', '', '', false, 300, '', false, false, 1);

            if ($photo->description) {
                $pdf->SetXY($x, $y + $img_height + 2);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Cell($img_width, 4, $photo->description, 0, 0, 'C');
            }

            $count++;

            if ($count == 4) break;
        }
    }
}

$pdf->Output('rapport_'.$form_id.'.pdf', 'I');
exit;
