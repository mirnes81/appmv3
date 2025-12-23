<?php
/**
 * Générateur PDF pour bons de régie
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/pdf/modules_pdf.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

class pdf_regie extends ModelePDFRegie
{
    public $emetteur;
    public $db;

    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->name = "regie";
        $this->description = "Modèle de PDF pour bons de régie MV-3 PRO";

        $this->option_multilang = 1;
        $this->option_freetext = 1;
        $this->option_draft_watermark = 1;

        $this->franchise = !empty($mysoc->tva_assuj) ? 0 : 1;

        $this->emetteur = $mysoc;
        if (!$this->emetteur->country_code) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2);
        }

        $this->tab_top = 90;
        $this->tab_height = 110;
        $this->line_height = 5;
    }

    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $user, $langs, $conf, $mysoc;

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }

        $outputlangs->loadLangs(array("main", "dict", "companies"));

        $nblignes = count($object->lines);

        $dir = $conf->mv3pro_portail->dir_output.'/regie/'.$object->id;
        if (!file_exists($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        }

        $file = $dir."/bon_regie_".$object->ref.".pdf";

        $pdf = pdf_getInstance($this->format);
        $default_font_size = pdf_getPDFFontSize($outputlangs);
        $heightforinfotot = 40;
        $heightforfreetext = isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5;
        $heightforfooter = $this->marge_basse + 8;

        $pdf->SetAutoPageBreak(1, 0);

        if (class_exists('TCPDF')) {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        $pdf->Open();
        $pagenb = 0;

        $pdf->SetDrawColor(128, 128, 128);

        $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
        $pdf->SetSubject($outputlangs->transnoentities("Bon de régie"));
        $pdf->SetCreator("Dolibarr ".DOL_VERSION);
        $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
        $pdf->SetKeywords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Bon de régie"));

        if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
            $pdf->SetCompression(false);
        }

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

        $pdf->AddPage();
        if (!empty($tplidx)) {
            $pdf->useTemplate($tplidx);
        }
        $pagenb++;

        $this->_pagehead($pdf, $object, 1, $outputlangs);
        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->MultiCell(0, 3, '', 0, 'J');
        $pdf->SetTextColor(0, 0, 0);

        $tab_top = $this->tab_top;
        $tab_top_newpage = empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? $this->tab_top_newpage + 0 : 10;
        $tab_height = $this->tab_height;
        $tab_height_newpage = $this->tab_height_newpage;

        $nexY = $tab_top + $this->line_height;

        $this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);

        $nexY = $tab_top + $this->line_height;

        require_once __DIR__.'/../class/regie.class.php';

        $object->fetch_lines();

        foreach ($object->lines as $i => $line) {
            if ($nexY + 10 > $tab_top + $tab_height) {
                $pdf->AddPage();
                $pagenb++;
                $nexY = $tab_top_newpage;
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->SetTextColor(0, 0, 0);
                $this->pdfTabTitles($pdf, $tab_top_newpage, $tab_height_newpage, $outputlangs, 1);
            }

            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->SetXY($this->marge_gauche, $nexY);

            $type_label = '';
            switch ($line->line_type) {
                case 'TIME':
                    $type_label = 'Main d\'œuvre';
                    break;
                case 'MATERIAL':
                    $type_label = 'Matériel';
                    break;
                case 'OPTION':
                    $type_label = 'Forfait';
                    break;
            }

            $pdf->MultiCell(25, 4, $type_label, 0, 'L', 0);

            $pdf->SetXY($this->marge_gauche + 25, $nexY);
            $pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($line->description), 0, 'L', 0);

            $pdf->SetXY($this->marge_gauche + 105, $nexY);
            $pdf->MultiCell(20, 4, $line->qty.' '.$line->unit, 0, 'R', 0);

            $pdf->SetXY($this->marge_gauche + 125, $nexY);
            $pdf->MultiCell(25, 4, price($line->price_unit, 0, $outputlangs).' CHF', 0, 'R', 0);

            $pdf->SetXY($this->marge_gauche + 150, $nexY);
            $pdf->MultiCell(30, 4, price($line->total_ht, 0, $outputlangs).' CHF', 0, 'R', 0);

            $nexY += 5;
        }

        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetXY($this->marge_gauche, $nexY + 5);
        $pdf->MultiCell(150, 5, 'TOTAL HT', 1, 'R', 0);
        $pdf->SetXY($this->marge_gauche + 150, $nexY + 5);
        $pdf->MultiCell(30, 5, price($object->total_ht, 0, $outputlangs).' CHF', 1, 'R', 0);

        $pdf->SetXY($this->marge_gauche, $nexY + 10);
        $pdf->MultiCell(150, 5, 'TVA '.$object->tva_tx.'%', 1, 'R', 0);
        $pdf->SetXY($this->marge_gauche + 150, $nexY + 10);
        $pdf->MultiCell(30, 5, price($object->total_tva, 0, $outputlangs).' CHF', 1, 'R', 0);

        $pdf->SetFont('', 'B', $default_font_size + 2);
        $pdf->SetXY($this->marge_gauche, $nexY + 15);
        $pdf->MultiCell(150, 6, 'TOTAL TTC', 1, 'R', 0);
        $pdf->SetXY($this->marge_gauche + 150, $nexY + 15);
        $pdf->MultiCell(30, 6, price($object->total_ttc, 0, $outputlangs).' CHF', 1, 'R', 0);

        $nexY += 30;

        if ($object->status >= Regie::STATUS_SIGNED && $object->date_signature) {
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetXY($this->marge_gauche, $nexY);
            $pdf->MultiCell(0, 5, '🔒 DOCUMENT SIGNÉ ÉLECTRONIQUEMENT', 0, 'C', 0);

            $nexY += 10;

            $sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_regie_signature";
            $sql .= " WHERE fk_regie = ".(int)$object->id;
            $sql .= " ORDER BY sign_datetime DESC LIMIT 1";
            $resql = $this->db->query($sql);

            if ($resql && $this->db->num_rows($resql) > 0) {
                $sign = $this->db->fetch_object($resql);

                if (file_exists($sign->sign_file)) {
                    $pdf->Image($sign->sign_file, $this->marge_gauche, $nexY, 60, 20);
                }

                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->SetXY($this->marge_gauche + 70, $nexY);
                $pdf->MultiCell(100, 4, 'Signé par: '.$sign->sign_name.' '.$sign->sign_firstname, 0, 'L', 0);
                $pdf->SetXY($this->marge_gauche + 70, $nexY + 5);
                $pdf->MultiCell(100, 4, 'Fonction: '.$sign->sign_role, 0, 'L', 0);
                $pdf->SetXY($this->marge_gauche + 70, $nexY + 10);
                $pdf->MultiCell(100, 4, 'Date: '.dol_print_date($object->date_signature, 'dayhour'), 0, 'L', 0);
                $pdf->SetXY($this->marge_gauche + 70, $nexY + 15);
                $pdf->MultiCell(100, 4, 'IP: '.$object->sign_ip, 0, 'L', 0);
            }

            $nexY += 30;
        }

        if (count($object->photos) > 0) {
            $pdf->AddPage();
            $pagenb++;

            $pdf->SetFont('', 'B', $default_font_size + 2);
            $pdf->SetXY($this->marge_gauche, 20);
            $pdf->MultiCell(0, 6, 'ANNEXE: PREUVES PHOTOGRAPHIQUES', 0, 'C', 0);

            $photo_x = $this->marge_gauche;
            $photo_y = 35;
            $photo_width = 85;
            $photo_height = 60;
            $photo_spacing = 10;
            $col = 0;

            foreach ($object->photos as $photo) {
                if (file_exists($photo['photo_path'])) {
                    if ($photo_y + $photo_height > 270) {
                        $pdf->AddPage();
                        $pagenb++;
                        $photo_y = 20;
                        $col = 0;
                    }

                    $pdf->Image($photo['photo_path'], $photo_x, $photo_y, $photo_width, $photo_height);

                    $pdf->SetFont('', 'B', 8);
                    $pdf->SetXY($photo_x, $photo_y + $photo_height + 2);
                    $pdf->MultiCell($photo_width, 3, $photo['photo_type'], 0, 'L', 0);

                    if ($photo['label']) {
                        $pdf->SetFont('', '', 7);
                        $pdf->SetXY($photo_x, $photo_y + $photo_height + 6);
                        $pdf->MultiCell($photo_width, 3, substr($photo['label'], 0, 50), 0, 'L', 0);
                    }

                    $pdf->SetFont('', '', 6);
                    $pdf->SetXY($photo_x, $photo_y + $photo_height + 10);
                    $pdf->MultiCell($photo_width, 3, dol_print_date($photo['date_photo'], 'dayhour'), 0, 'L', 0);

                    $col++;
                    if ($col >= 2) {
                        $col = 0;
                        $photo_x = $this->marge_gauche;
                        $photo_y += $photo_height + 20;
                    } else {
                        $photo_x += $photo_width + $photo_spacing;
                    }
                }
            }
        }

        $pdf->Close();

        $pdf->Output($file, 'F');

        if (!empty($conf->global->MAIN_UMASK)) {
            @chmod($file, octdec($conf->global->MAIN_UMASK));
        }

        $this->result = array('fullpath' => $file);

        return 1;
    }

    protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
    {
        global $conf, $langs;

        $outputlangs->loadLangs(array("main", "companies"));

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - 100;

        $pdf->SetXY($this->marge_gauche, $posy);

        $logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
        if ($this->emetteur->logo) {
            if (is_readable($logo)) {
                $height = pdf_getHeightForLogo($logo);
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size - 2);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else {
            $text = $this->emetteur->name;
            $pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
        }

        $pdf->SetFont('', 'B', $default_font_size + 3);
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $title = 'BON DE RÉGIE';
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities($title), '', 'R');

        $pdf->SetFont('', 'B', $default_font_size + 2);

        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($object->ref), '', 'R');

        $pdf->SetFont('', '', $default_font_size);

        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(100, 3, 'Date: '.dol_print_date($object->date_regie, "day", false, $outputlangs, true), '', 'R');

        $posy += 20;

        require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
        $project = new Project($this->db);
        $project->fetch($object->fk_project);

        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $societe = new Societe($this->db);
        $societe->fetch($object->fk_soc);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $default_font_size - 1);
        $pdf->SetXY($this->marge_gauche, $posy);

        $pdf->MultiCell(80, 3, 'Projet: '.$project->ref.' - '.$project->title, 0, 'L');
        $pdf->SetXY($this->marge_gauche, $posy + 5);
        $pdf->MultiCell(80, 3, 'Client: '.$societe->name, 0, 'L');

        if ($object->location_text) {
            $pdf->SetXY($this->marge_gauche, $posy + 10);
            $pdf->MultiCell(80, 3, 'Lieu: '.$object->location_text, 0, 'L');
        }

        if ($object->type_regie) {
            $pdf->SetXY($this->marge_gauche, $posy + 15);
            $pdf->MultiCell(80, 3, 'Type: '.$object->type_regie, 0, 'L');
        }
    }

    protected function pdfTabTitles(&$pdf, $tab_top, $tab_height, $outputlangs, $hidetop = 0)
    {
        $pdf->SetFont('', 'B', 9);

        $pdf->SetXY($this->marge_gauche, $tab_top);
        $pdf->MultiCell(25, 5, 'Type', 1, 'L', 1);

        $pdf->SetXY($this->marge_gauche + 25, $tab_top);
        $pdf->MultiCell(80, 5, 'Description', 1, 'L', 1);

        $pdf->SetXY($this->marge_gauche + 105, $tab_top);
        $pdf->MultiCell(20, 5, 'Qté', 1, 'R', 1);

        $pdf->SetXY($this->marge_gauche + 125, $tab_top);
        $pdf->MultiCell(25, 5, 'P.U.', 1, 'R', 1);

        $pdf->SetXY($this->marge_gauche + 150, $tab_top);
        $pdf->MultiCell(30, 5, 'Total HT', 1, 'R', 1);
    }
}

class ModelePDFRegie
{
    public $page_largeur = 210;
    public $page_hauteur = 297;
    public $format = array(210, 297);
    public $marge_gauche = 10;
    public $marge_droite = 10;
    public $marge_haute = 10;
    public $marge_basse = 10;
}
?>
