<?php
/**
 * Module MV-3 PRO Portail - MINIMAL VERSION
 * Planning + PWA uniquement
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modMv3pro_portail extends DolibarrModules
{
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;
        $this->numero      = 510000;
        $this->rights_class = 'mv3pro_portail';
        $this->family      = 'mv3pro';
        $this->name        = 'MV3 PRO Portail';
        $this->description = 'Planning + Rapports chantier + PWA mobile pour techniciens';
        $this->version     = '3.0.0-rapports';
        $this->const_name  = 'MAIN_MODULE_MV3PRO_PORTAIL';
        $this->picto       = 'fa-calendar';

        $this->module_parts = array(
            'css' => array(),
        );

        // Répertoires documents
        $this->dirs = array(
            '/mv3pro_portail/temp',
            '/mv3pro_portail/planning',
            '/mv3pro_portail/report'
        );

        // Constantes (minimal)
        $this->const = array();
        $r = 0;

        $this->const[$r] = array(
            'MV3PRO_PORTAIL_DIR_OUTPUT',
            'chaine',
            DOL_DATA_ROOT.'/documents/mv3pro_portail',
            'Répertoire des documents du module MV3 PRO',
            0,
            'current',
            1
        );
        $r++;

        $this->const[$r] = array(
            'MV3PRO_PWA_URL',
            'chaine',
            '',
            'URL de la Progressive Web App',
            0,
            'current',
            1
        );
        $r++;

        $this->config_page_url = array('setup.php@mv3pro_portail');

        // Droits (minimal)
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 510001;
        $this->rights[$r][1] = 'Accès module MV-3 PRO';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = 510002;
        $this->rights[$r][1] = 'Modification planning';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->rights[$r][0] = 510003;
        $this->rights[$r][1] = 'Créer/modifier ses rapports';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'reports_create';
        $r++;

        $this->rights[$r][0] = 510004;
        $this->rights[$r][1] = 'Voir tous les rapports';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'reports_readall';
        $r++;

        $this->rights[$r][0] = 510005;
        $this->rights[$r][1] = 'Valider/supprimer rapports';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'reports_admin';
        $r++;

        // Menus (Dashboard + Planning)
        $this->menu = array();
        $r = 0;

        // Menu principal en haut
        $this->menu[$r] = array(
            'fk_menu'   => '',
            'type'      => 'top',
            'titre'     => 'MV-3 PRO',
            'prefix'    => '<span class="fa fa-calendar fa-fw paddingright pictofixedwidth"></span>',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/dashboard/index.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 1000,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Dashboard
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Dashboard',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_dashboard',
            'url'       => '/custom/mv3pro_portail/dashboard/index.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 10,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Planning
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Planning',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_planning',
            'url'       => '/comm/action/index.php?mainmenu=mv3pro',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 100,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Rapports
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Rapports',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_reports',
            'url'       => '/custom/mv3pro_portail/reports/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 200,
            'enabled'   => '1',
            'perms'     => '$user->rights->mv3pro_portail->reports_create',
            'target'    => '',
            'user'      => 2
        );
        $r++;
    }

    public function init($options = '')
    {
        global $conf, $langs;

        $sql = array();

        // Créer tables rapports
        $sql[] = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."mv3_report (
            rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
            entity INTEGER DEFAULT 1 NOT NULL,
            ref VARCHAR(30) NOT NULL,
            fk_project INTEGER DEFAULT NULL,
            fk_user_author INTEGER NOT NULL,
            fk_user_assigned INTEGER DEFAULT NULL,
            date_report DATE NOT NULL,
            time_start DATETIME DEFAULT NULL,
            time_end DATETIME DEFAULT NULL,
            duration_minutes INTEGER DEFAULT NULL,
            note_public TEXT,
            note_private TEXT,
            status INTEGER DEFAULT 0 NOT NULL,
            datec DATETIME,
            tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            fk_user_creat INTEGER,
            fk_user_modif INTEGER,
            UNIQUE KEY uk_mv3_report_ref (ref, entity),
            INDEX idx_mv3_report_entity (entity),
            INDEX idx_mv3_report_project (fk_project),
            INDEX idx_mv3_report_author (fk_user_author),
            INDEX idx_mv3_report_date (date_report),
            INDEX idx_mv3_report_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."mv3_report_line (
            rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
            entity INTEGER DEFAULT 1 NOT NULL,
            fk_report INTEGER NOT NULL,
            label VARCHAR(255) NOT NULL,
            description TEXT,
            qty_minutes INTEGER DEFAULT NULL,
            note TEXT,
            sort_order INTEGER DEFAULT 0,
            datec DATETIME,
            tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_mv3_report_line_report (fk_report),
            INDEX idx_mv3_report_line_entity (entity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."mv3_report_counter (
            entity INTEGER NOT NULL,
            year INTEGER NOT NULL,
            last_value INTEGER DEFAULT 0 NOT NULL,
            PRIMARY KEY (entity, year),
            INDEX idx_mv3_counter_entity (entity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $this->_init($sql, $options);
    }
}
