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
        $this->description = 'Planning + PWA (Progressive Web App) pour les techniciens';
        $this->version     = '2.0.0-minimal';
        $this->const_name  = 'MAIN_MODULE_MV3PRO_PORTAIL';
        $this->picto       = 'fa-calendar';

        $this->module_parts = array(
            'css' => array(),
        );

        // Répertoires documents (minimal)
        $this->dirs = array(
            '/mv3pro_portail/temp',
            '/mv3pro_portail/planning'
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
    }

    public function init($options = '')
    {
        global $conf, $langs;

        $sql = array();

        return $this->_init($sql, $options);
    }
}
