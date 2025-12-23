<?php
/**
 * Classe Regie - Bon de régie MV3 PRO
 *
 * Gestion complète des bons de régie:
 * - Création, modification, suppression
 * - Gestion des lignes (temps, matériel, options)
 * - Photos de preuve
 * - Signature électronique
 * - Génération PDF
 * - Envoi email
 */

// DOL_DOCUMENT_ROOT doit être défini par main.inc.php avant d'inclure cette classe
if (!defined('DOL_DOCUMENT_ROOT')) {
    die('Error: This file must be included after main.inc.php has been loaded.');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class Regie extends CommonObject
{
    public $element = 'regie';
    public $table_element = 'mv3_regie';
    public $picto = 'generic';

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_SENT = 2;
    const STATUS_SIGNED = 3;
    const STATUS_INVOICED = 4;
    const STATUS_CANCELED = 9;

    public $rowid;
    public $ref;
    public $entity;
    public $fk_project;
    public $fk_soc;
    public $fk_user_author;
    public $fk_user_valid;
    public $fk_facture;
    public $date_regie;
    public $date_creation;
    public $date_modification;
    public $date_validation;
    public $date_envoi;
    public $date_signature;
    public $location_text;
    public $type_regie;
    public $status;
    public $total_ht;
    public $total_tva;
    public $total_ttc;
    public $tva_tx;
    public $note_public;
    public $note_private;
    public $sign_latitude;
    public $sign_longitude;
    public $sign_ip;
    public $sign_useragent;

    public $lines = array();
    public $photos = array();

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Créer un bon de régie
     */
    public function create($user, $notrigger = 0)
    {
        global $conf;

        $error = 0;
        $now = dol_now();

        $this->db->begin();

        if (empty($this->ref)) {
            $this->ref = $this->getNextNumRef();
        }

        if (empty($this->date_regie)) {
            $this->date_regie = $now;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_regie (";
        $sql .= "ref, entity, fk_project, fk_soc, fk_user_author,";
        $sql .= "date_regie, date_creation, location_text, type_regie,";
        $sql .= "status, total_ht, total_tva, total_ttc, tva_tx,";
        $sql .= "note_public, note_private";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= " ".$conf->entity.",";
        $sql .= " ".(int)$this->fk_project.",";
        $sql .= " ".($this->fk_soc > 0 ? (int)$this->fk_soc : "NULL").",";
        $sql .= " ".(int)$user->id.",";
        $sql .= " '".$this->db->idate($this->date_regie)."',";
        $sql .= " '".$this->db->idate($now)."',";
        $sql .= " ".($this->location_text ? "'".$this->db->escape($this->location_text)."'" : "NULL").",";
        $sql .= " ".($this->type_regie ? "'".$this->db->escape($this->type_regie)."'" : "NULL").",";
        $sql .= " ".self::STATUS_DRAFT.",";
        $sql .= " 0, 0, 0, 8.1,";
        $sql .= " ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "NULL").",";
        $sql .= " ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "NULL");
        $sql .= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql = $this->db->query($sql);

        if ($resql) {
            $this->rowid = $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mv3_regie");
            $this->db->commit();
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Charger un bon de régie
     */
    public function fetch($id, $ref = '')
    {
        $sql = "SELECT r.*";
        $sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie as r";

        if ($id) {
            $sql .= " WHERE r.rowid = ".(int)$id;
        } else {
            $sql .= " WHERE r.ref = '".$this->db->escape($ref)."'";
        }

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);

        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->rowid = $obj->rowid;
                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->entity = $obj->entity;
                $this->fk_project = $obj->fk_project;
                $this->fk_soc = $obj->fk_soc;
                $this->fk_user_author = $obj->fk_user_author;
                $this->fk_user_valid = $obj->fk_user_valid;
                $this->fk_facture = $obj->fk_facture;
                $this->date_regie = $this->db->jdate($obj->date_regie);
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->date_validation = $this->db->jdate($obj->date_validation);
                $this->date_envoi = $this->db->jdate($obj->date_envoi);
                $this->date_signature = $this->db->jdate($obj->date_signature);
                $this->location_text = $obj->location_text;
                $this->type_regie = $obj->type_regie;
                $this->status = $obj->status;
                $this->total_ht = $obj->total_ht;
                $this->total_tva = $obj->total_tva;
                $this->total_ttc = $obj->total_ttc;
                $this->tva_tx = $obj->tva_tx;
                $this->note_public = $obj->note_public;
                $this->note_private = $obj->note_private;
                $this->sign_latitude = $obj->sign_latitude;
                $this->sign_longitude = $obj->sign_longitude;
                $this->sign_ip = $obj->sign_ip;
                $this->sign_useragent = $obj->sign_useragent;

                $this->fetch_lines();
                $this->fetch_photos();

                return 1;
            }
            return 0;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Charger les lignes
     */
    public function fetch_lines()
    {
        $this->lines = array();

        $sql = "SELECT l.*";
        $sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie_line as l";
        $sql .= " WHERE l.fk_regie = ".(int)$this->id;
        $sql .= " ORDER BY l.rang ASC, l.rowid ASC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                $line = new RegieLine($this->db);
                $line->id = $obj->rowid;
                $line->fk_regie = $obj->fk_regie;
                $line->line_type = $obj->line_type;
                $line->rang = $obj->rang;
                $line->fk_product = $obj->fk_product;
                $line->fk_user_tech = $obj->fk_user_tech;
                $line->description = $obj->description;
                $line->date_line = $this->db->jdate($obj->date_line);
                $line->date_start = $this->db->jdate($obj->date_start);
                $line->date_end = $this->db->jdate($obj->date_end);
                $line->duration = $obj->duration;
                $line->qty = $obj->qty;
                $line->unit = $obj->unit;
                $line->price_unit = $obj->price_unit;
                $line->remise_percent = $obj->remise_percent;
                $line->tva_tx = $obj->tva_tx;
                $line->total_ht = $obj->total_ht;
                $line->total_tva = $obj->total_tva;
                $line->total_ttc = $obj->total_ttc;

                $this->lines[] = $line;
                $i++;
            }
            return $num;
        }
        return -1;
    }

    /**
     * Charger les photos
     */
    public function fetch_photos()
    {
        $this->photos = array();

        $sql = "SELECT p.*";
        $sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie_photo as p";
        $sql .= " WHERE p.fk_regie = ".(int)$this->id;
        $sql .= " ORDER BY p.position ASC, p.date_photo DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $this->photos[] = (array)$obj;
                $i++;
            }
            return $num;
        }
        return -1;
    }

    /**
     * Ajouter une ligne
     */
    public function addline($line_type, $description, $qty = 1, $price_unit = 0, $options = array())
    {
        $line = new RegieLine($this->db);
        $line->fk_regie = $this->id;
        $line->line_type = $line_type;
        $line->description = $description;
        $line->qty = $qty;
        $line->price_unit = $price_unit;
        $line->unit = $options['unit'] ?? 'unit';
        $line->tva_tx = $options['tva_tx'] ?? 8.1;
        $line->fk_product = $options['fk_product'] ?? null;
        $line->fk_user_tech = $options['fk_user_tech'] ?? null;
        $line->date_line = $options['date_line'] ?? dol_now();
        $line->date_start = $options['date_start'] ?? null;
        $line->date_end = $options['date_end'] ?? null;
        $line->duration = $options['duration'] ?? 0;

        $result = $line->insert();

        if ($result > 0) {
            $this->update_totals();
            return $result;
        }

        return -1;
    }

    /**
     * Mettre à jour les totaux
     */
    public function update_totals()
    {
        $total_ht = 0;
        $total_tva = 0;

        $this->fetch_lines();

        foreach ($this->lines as $line) {
            $total_ht += $line->total_ht;
            $total_tva += $line->total_tva;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_regie SET";
        $sql .= " total_ht = ".((float)$total_ht).",";
        $sql .= " total_tva = ".((float)$total_tva).",";
        $sql .= " total_ttc = ".((float)($total_ht + $total_tva));
        $sql .= " WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            $this->total_ht = $total_ht;
            $this->total_tva = $total_tva;
            $this->total_ttc = $total_ht + $total_tva;
            return 1;
        }
        return -1;
    }

    /**
     * Valider le bon (prêt pour signature)
     */
    public function setValidated($user)
    {
        if ($this->status != self::STATUS_DRAFT) {
            return -1;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_regie SET";
        $sql .= " status = ".self::STATUS_VALIDATED.",";
        $sql .= " fk_user_valid = ".(int)$user->id.",";
        $sql .= " date_validation = '".$this->db->idate(dol_now())."'";
        $sql .= " WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            $this->status = self::STATUS_VALIDATED;
            return 1;
        }
        return -1;
    }

    /**
     * Marquer comme envoyé au client
     */
    public function setSent()
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_regie SET";
        $sql .= " status = ".self::STATUS_SENT.",";
        $sql .= " date_envoi = '".$this->db->idate(dol_now())."'";
        $sql .= " WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            $this->status = self::STATUS_SENT;
            return 1;
        }
        return -1;
    }

    /**
     * Marquer comme signé
     */
    public function setSigned($signature_data)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_regie SET";
        $sql .= " status = ".self::STATUS_SIGNED.",";
        $sql .= " date_signature = '".$this->db->idate(dol_now())."',";
        $sql .= " sign_ip = '".$this->db->escape($signature_data['ip'])."',";
        $sql .= " sign_useragent = '".$this->db->escape($signature_data['useragent'])."'";

        if (!empty($signature_data['latitude'])) {
            $sql .= ", sign_latitude = '".$this->db->escape($signature_data['latitude'])."'";
        }
        if (!empty($signature_data['longitude'])) {
            $sql .= ", sign_longitude = '".$this->db->escape($signature_data['longitude'])."'";
        }

        $sql .= " WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            $this->status = self::STATUS_SIGNED;
            return 1;
        }
        return -1;
    }

    /**
     * Générer la référence
     */
    public function getNextNumRef()
    {
        global $conf;

        $year = date('Y');
        $month = date('m');

        $sql = "SELECT MAX(CAST(SUBSTRING(ref, 12) AS UNSIGNED)) as max";
        $sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie";
        $sql .= " WHERE ref LIKE 'BR-".$year.$month."-%'";
        $sql .= " AND entity = ".$conf->entity;

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $max = $obj->max ?? 0;
            $num = str_pad($max + 1, 4, '0', STR_PAD_LEFT);
            return 'BR-'.$year.$month.'-'.$num;
        }

        return 'BR-'.$year.$month.'-0001';
    }

    /**
     * Retourne le nom avec lien cliquable
     *
     * @param int $withpicto 0=Pas de picto, 1=Inclut le picto, 2=Picto seul
     * @param string $option 'nolink' pour ne pas avoir de lien
     * @param int $notooltip 1=Désactive la tooltip
     * @param int $save_lastsearch_value -1=Auto, 0=Jamais, 1=Toujours sauver
     * @param int $addlabel 0=Non, 1=Ajouter le label
     * @return string Chaîne avec URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $save_lastsearch_value = -1, $addlabel = 0)
    {
        global $conf, $langs;

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1;
        }

        $result = '';
        $label = '<strong>Bon de régie:</strong> '.$this->ref;
        if ($this->status == self::STATUS_DRAFT) {
            $label .= '<br><span class="opacitymedium">Brouillon</span>';
        }

        $url = dol_buildpath('/mv3pro_portail/regie/card.php', 1).'?id='.$this->id;

        if ($option != 'nolink') {
            $linkstart = '<a href="'.$url.'"';
            $linkstart .= ($notooltip ? '' : ' title="'.dol_escape_htmltag($label, 1).'"');
            $linkstart .= ' class="classfortooltip">';
            $linkend = '</a>';
        } else {
            $linkstart = '<span>';
            $linkend = '</span>';
        }

        if ($withpicto) {
            $result .= ($linkstart.img_object(($notooltip ? '' : $label), 'generic', ($notooltip ? '' : 'class="classfortooltip"')).$linkend);
        }
        if ($withpicto && $withpicto != 2) {
            $result .= ' ';
        }
        if ($withpicto != 2) {
            $result .= $linkstart.$this->ref.$linkend;
        }
        if ($addlabel) {
            $result .= ' - '.$this->LibStatut($this->status, 0);
        }

        return $result;
    }

    /**
     * Obtenir le statut avec badge
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    public function LibStatut($status, $mode = 0)
    {
        $statusLabel = array(
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_VALIDATED => 'Validé',
            self::STATUS_SENT => 'Envoyé',
            self::STATUS_SIGNED => 'Signé',
            self::STATUS_INVOICED => 'Facturé',
            self::STATUS_CANCELED => 'Annulé'
        );

        $statusColor = array(
            self::STATUS_DRAFT => 'status1',
            self::STATUS_VALIDATED => 'status3',
            self::STATUS_SENT => 'status7',
            self::STATUS_SIGNED => 'status4',
            self::STATUS_INVOICED => 'status6',
            self::STATUS_CANCELED => 'status9'
        );

        $label = $statusLabel[$status] ?? 'Unknown';
        $color = $statusColor[$status] ?? 'status0';

        if ($mode == 0) {
            return $label;
        }

        return dolGetStatus($label, '', '', $color, $mode);
    }

    /**
     * Obtenir le nom du projet
     */
    public function getProjectName()
    {
        if (!$this->fk_project) return '';

        require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
        $project = new Project($this->db);
        $project->fetch($this->fk_project);
        return $project->ref.' - '.$project->title;
    }

    /**
     * Obtenir le nom du client
     */
    public function getClientName()
    {
        if (!$this->fk_soc) return '';

        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $societe = new Societe($this->db);
        $societe->fetch($this->fk_soc);
        return $societe->name;
    }
}

/**
 * Classe RegieLine - Ligne de bon de régie
 */
class RegieLine
{
    public $db;
    public $id;
    public $fk_regie;
    public $line_type;
    public $rang;
    public $fk_product;
    public $fk_user_tech;
    public $description;
    public $date_line;
    public $date_start;
    public $date_end;
    public $duration;
    public $qty;
    public $unit;
    public $price_unit;
    public $remise_percent;
    public $tva_tx;
    public $total_ht;
    public $total_tva;
    public $total_ttc;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function insert()
    {
        $this->calculate_totals();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_regie_line (";
        $sql .= "fk_regie, line_type, description, qty, unit, price_unit,";
        $sql .= "remise_percent, tva_tx, total_ht, total_tva, total_ttc,";
        $sql .= "fk_product, fk_user_tech, date_line, date_start, date_end, duration";
        $sql .= ") VALUES (";
        $sql .= (int)$this->fk_regie.",";
        $sql .= "'".$this->db->escape($this->line_type)."',";
        $sql .= "'".$this->db->escape($this->description)."',";
        $sql .= (float)$this->qty.",";
        $sql .= "'".$this->db->escape($this->unit)."',";
        $sql .= (float)$this->price_unit.",";
        $sql .= (float)$this->remise_percent.",";
        $sql .= (float)$this->tva_tx.",";
        $sql .= (float)$this->total_ht.",";
        $sql .= (float)$this->total_tva.",";
        $sql .= (float)$this->total_ttc.",";
        $sql .= ($this->fk_product ? (int)$this->fk_product : "NULL").",";
        $sql .= ($this->fk_user_tech ? (int)$this->fk_user_tech : "NULL").",";
        $sql .= ($this->date_line ? "'".$this->db->idate($this->date_line)."'" : "NULL").",";
        $sql .= ($this->date_start ? "'".$this->db->idate($this->date_start)."'" : "NULL").",";
        $sql .= ($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "NULL").",";
        $sql .= (int)$this->duration;
        $sql .= ")";

        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mv3_regie_line");
            return $this->id;
        }

        return -1;
    }

    public function calculate_totals()
    {
        $base_ht = $this->qty * $this->price_unit;
        $remise = $base_ht * ($this->remise_percent / 100);
        $this->total_ht = $base_ht - $remise;
        $this->total_tva = $this->total_ht * ($this->tva_tx / 100);
        $this->total_ttc = $this->total_ht + $this->total_tva;
    }
}
?>
