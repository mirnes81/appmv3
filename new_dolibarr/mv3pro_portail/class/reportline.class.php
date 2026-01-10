<?php
/**
 * Classe ReportLine - Lignes de tâches des rapports
 */

class ReportLine
{
    public $db;

    public $id;
    public $entity;
    public $fk_report;

    public $label;
    public $description;
    public $qty_minutes;
    public $note;

    public $sort_order;

    public $datec;
    public $tms;

    public $error;
    public $errors = array();

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Créer une ligne
     */
    public function create($user)
    {
        global $conf;

        if (empty($this->entity)) {
            $this->entity = $conf->entity;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_report_line (";
        $sql .= " entity,";
        $sql .= " fk_report,";
        $sql .= " label,";
        $sql .= " description,";
        $sql .= " qty_minutes,";
        $sql .= " note,";
        $sql .= " sort_order,";
        $sql .= " datec";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " ".(int)$this->fk_report.",";
        $sql .= " '".$this->db->escape($this->label)."',";
        $sql .= " ".($this->description ? "'".$this->db->escape($this->description)."'" : 'NULL').",";
        $sql .= " ".((int)$this->qty_minutes ?: 'NULL').",";
        $sql .= " ".($this->note ? "'".$this->db->escape($this->note)."'" : 'NULL').",";
        $sql .= " ".(int)$this->sort_order.",";
        $sql .= " '".$this->db->idate(dol_now())."'";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mv3_report_line");
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Mettre à jour
     */
    public function update($user)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_report_line SET";
        $sql .= " label = '".$this->db->escape($this->label)."'";
        $sql .= ", description = ".($this->description ? "'".$this->db->escape($this->description)."'" : 'NULL');
        $sql .= ", qty_minutes = ".((int)$this->qty_minutes ?: 'NULL');
        $sql .= ", note = ".($this->note ? "'".$this->db->escape($this->note)."'" : 'NULL');
        $sql .= ", sort_order = ".(int)$this->sort_order;
        $sql .= " WHERE rowid = ".(int)$this->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Supprimer
     */
    public function delete($user)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_report_line WHERE rowid = ".(int)$this->id;
        $resql = $this->db->query($sql);

        if ($resql) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }
}
