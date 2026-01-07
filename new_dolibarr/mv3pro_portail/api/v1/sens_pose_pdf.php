<?php
/**
 * API v1 - Sens de Pose - PDF
 * POST /api/v1/sens_pose_pdf.php
 * Body: {"sens_pose_id": 123}
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

$auth = require_auth();
require_method('POST');

$body = get_json_body(true);
$id = isset($body['sens_pose_id']) ? (int)$body['sens_pose_id'] : 0;
require_param($id, 'sens_pose_id');

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".$id;
$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) json_error('Non trouvé', 'NOT_FOUND', 404);

$obj = $db->fetch_object($resql);

// Générer PDF simple
require_once DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/tcpdf.php';
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetFont('helvetica', '', 10);
$pdf->AddPage();
$pdf->Cell(0, 10, 'SENS DE POSE - ' . $obj->ref, 0, 1, 'C');
$pdf->Ln(5);
$pdf->MultiCell(0, 5, 'Date: ' . $obj->date_pose, 0, 'L');

$pdf_dir = $conf->mv3pro_portail->dir_output . '/sens_pose_pdf';
if (!is_dir($pdf_dir)) dol_mkdir($pdf_dir, 0755);

$filename = 'sens_pose_' . $obj->ref . '.pdf';
$filepath = $pdf_dir . '/' . $filename;
$pdf->Output($filepath, 'F');

json_ok(['pdf' => ['filename' => $filename, 'size' => filesize($filepath)]]);
