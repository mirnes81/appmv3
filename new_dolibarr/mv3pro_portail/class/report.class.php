<?php
/**
 * Classe Report - Gestion des rapports de chantier
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class Report extends CommonObject
{
    public $element = 'mv3_report';
    public $table_element = 'mv3_report';
    public $fk_element = 'fk_report';

    public $id;
    public $entity;
    public $ref;

    public $fk_project;
    public $fk_user_author;
    public $fk_user_assigned;

    public $date_report;
    public $time_start;
    public $time_end;
    public $duration_minutes;

    public $note_public;
    public $note_private;

    public $status;

    public $datec;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;

    public $lines = array();

    const STATUS_DRAFT = 0;
    const STATUS_SUBMITTED = 1;
    const STATUS_VALIDATED = 2;
    const STATUS_REJECTED = 9;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Créer un rapport
     */
    public function create($user, $notrigger = false)
    {
        global $conf;

        $error = 0;
        $now = dol_now();

        $this->db->begin();

        // Générer ref si vide
        if (empty($this->ref)) {
            $this->ref = $this->getNextNumRef();
            if (empty($this->ref)) {
                $this->error = 'ErrorFailedToGenerateRef';
                $this->db->rollback();
                return -1;
            }
        }

        // Valeurs par défaut
        if (empty($this->entity)) {
            $this->entity = $conf->entity;
        }
        if (empty($this->fk_user_author)) {
            $this->fk_user_author = $user->id;
        }
        if (empty($this->status)) {
            $this->status = self::STATUS_DRAFT;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_report (";
        $sql .= " entity,";
        $sql .= " ref,";
        $sql .= " fk_project,";
        $sql .= " fk_user_author,";
        $sql .= " fk_user_assigned,";
        $sql .= " date_report,";
        $sql .= " time_start,";
        $sql .= " time_end,";
        $sql .= " duration_minutes,";
        $sql .= " note_public,";
        $sql .= " note_private,";
        $sql .= " status,";
        $sql .= " datec,";
        $sql .= " fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " '".$this->db->escape($this->ref)."',";
        $sql .= " ".((int)$this->fk_project ?: 'NULL').",";
        $sql .= " ".(int)$this->fk_user_author.",";
        $sql .= " ".((int)$this->fk_user_assigned ?: 'NULL').",";
        $sql .= " '".$this->db->idate($this->date_report)."',";
        $sql .= " ".($this->time_start ? "'".$this->db->idate($this->time_start)."'" : 'NULL').",";
        $sql .= " ".($this->time_end ? "'".$this->db->idate($this->time_end)."'" : 'NULL').",";
        $sql .= " ".((int)$this->duration_minutes ?: 'NULL').",";
        $sql .= " ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : 'NULL').",";
        $sql .= " ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : 'NULL').",";
        $sql .= " ".(int)$this->status.",";
        $sql .= " '".$this->db->idate($now)."',";
        $sql .= " ".(int)$user->id;
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mv3_report");

            // Créer lignes
            if (!empty($this->lines) && is_array($this->lines)) {
                foreach ($this->lines as $line) {
                    $line->fk_report = $this->id;
                    $result = $line->create($user);
                    if ($result < 0) {
                        $error++;
                        $this->error = $line->error;
                        $this->errors = $line->errors;
                        break;
                    }
                }
            }

            if (!$error) {
                $this->db->commit();
                return $this->id;
            } else {
                $this->db->rollback();
                return -1;
            }
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Charger un rapport
     */
    public function fetch($id, $ref = null)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_report";
        $sql .= " WHERE entity IN (0, ".getEntity('mv3_report').")";
        if ($id) {
            $sql .= " AND rowid = ".(int)$id;
        } elseif ($ref) {
            $sql .= " AND ref = '".$this->db->escape($ref)."'";
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->entity = $obj->entity;
                $this->ref = $obj->ref;
                $this->fk_project = $obj->fk_project;
                $this->fk_user_author = $obj->fk_user_author;
                $this->fk_user_assigned = $obj->fk_user_assigned;
                $this->date_report = $this->db->jdate($obj->date_report);
                $this->time_start = $this->db->jdate($obj->time_start);
                $this->time_end = $this->db->jdate($obj->time_end);
                $this->duration_minutes = $obj->duration_minutes;
                $this->note_public = $obj->note_public;
                $this->note_private = $obj->note_private;
                $this->status = $obj->status;
                $this->datec = $this->db->jdate($obj->datec);
                $this->tms = $this->db->jdate($obj->tms);
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->fk_user_modif = $obj->fk_user_modif;

                // Charger lignes
                $this->fetch_lines();

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
        require_once __DIR__.'/reportline.class.php';

        $this->lines = array();

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_report_line";
        $sql .= " WHERE fk_report = ".(int)$this->id;
        $sql .= " ORDER BY sort_order ASC, rowid ASC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $line = new ReportLine($this->db);
                $line->id = $obj->rowid;
                $line->entity = $obj->entity;
                $line->fk_report = $obj->fk_report;
                $line->label = $obj->label;
                $line->description = $obj->description;
                $line->qty_minutes = $obj->qty_minutes;
                $line->note = $obj->note;
                $line->sort_order = $obj->sort_order;
                $line->datec = $this->db->jdate($obj->datec);
                $line->tms = $this->db->jdate($obj->tms);

                $this->lines[] = $line;
                $i++;
            }
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Mettre à jour
     */
    public function update($user, $notrigger = false)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_report SET";
        $sql .= " fk_project = ".((int)$this->fk_project ?: 'NULL');
        $sql .= ", fk_user_assigned = ".((int)$this->fk_user_assigned ?: 'NULL');
        $sql .= ", date_report = '".$this->db->idate($this->date_report)."'";
        $sql .= ", time_start = ".($this->time_start ? "'".$this->db->idate($this->time_start)."'" : 'NULL');
        $sql .= ", time_end = ".($this->time_end ? "'".$this->db->idate($this->time_end)."'" : 'NULL');
        $sql .= ", duration_minutes = ".((int)$this->duration_minutes ?: 'NULL');
        $sql .= ", note_public = ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : 'NULL');
        $sql .= ", note_private = ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : 'NULL');
        $sql .= ", status = ".(int)$this->status;
        $sql .= ", fk_user_modif = ".(int)$user->id;
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
    public function delete($user, $notrigger = false)
    {
        $this->db->begin();

        // Supprimer lignes (cascade via FK)
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_report WHERE rowid = ".(int)$this->id;
        $resql = $this->db->query($sql);

        if ($resql) {
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Changer statut
     */
    public function setStatus($status, $user)
    {
        $this->status = $status;
        return $this->update($user);
    }

    /**
     * Soumettre (brouillon → soumis)
     */
    public function submit($user)
    {
        return $this->setStatus(self::STATUS_SUBMITTED, $user);
    }

    /**
     * Valider (soumis → validé)
     */
    public function validate($user)
    {
        return $this->setStatus(self::STATUS_VALIDATED, $user);
    }

    /**
     * Rejeter
     */
    public function reject($user)
    {
        return $this->setStatus(self::STATUS_REJECTED, $user);
    }

    /**
     * Générer la prochaine référence
     */
    public function getNextNumRef()
    {
        global $conf;

        $entity = $conf->entity ?: 1;
        $year = date('Y');

        $this->db->begin();

        // Lock + SELECT
        $sql = "SELECT last_value FROM ".MAIN_DB_PREFIX."mv3_report_counter";
        $sql .= " WHERE entity = ".(int)$entity." AND year = ".(int)$year;
        $sql .= " FOR UPDATE";

        $resql = $this->db->query($sql);
        $current = 0;

        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $current = $obj->last_value;
            }
        }

        $next = $current + 1;

        // UPDATE ou INSERT
        if ($current > 0) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_report_counter";
            $sql .= " SET last_value = ".(int)$next;
            $sql .= " WHERE entity = ".(int)$entity." AND year = ".(int)$year;
        } else {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_report_counter";
            $sql .= " (entity, year, last_value) VALUES";
            $sql .= " (".(int)$entity.", ".(int)$year.", ".(int)$next.")";
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->db->commit();
            $ref = sprintf('RPT-%d-%06d', $year, $next);
            return $ref;
        } else {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Obtenir le statut en texte
     */
    public function getLibStatut($mode = 0)
    {
        $statuts = array(
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SUBMITTED => 'Soumis',
            self::STATUS_VALIDATED => 'Validé',
            self::STATUS_REJECTED => 'Rejeté'
        );
        return $statuts[$this->status] ?? 'Unknown';
    }
}
