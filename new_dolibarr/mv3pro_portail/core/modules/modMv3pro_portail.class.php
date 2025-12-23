<?php
/**
 * Module MV-3 PRO Portail - Descriptor
 * Classe de description du module
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
        $this->description = 'Portail MV-3 PRO (rapports, signalements, matériel, régie, sens pose, frais, TV)';
        $this->version     = '1.1.0';
        $this->const_name  = 'MAIN_MODULE_MV3PRO_PORTAIL';
        $this->picto       = 'fa-cubes'; // Icône cubes/carrelage

        $this->module_parts = array(
            'triggers' => 1,
            'hooks'    => array('propalcard'),
            'css'      => array(),
            'models'   => 1,
            'moduleparts' => array('mv3pro_portail' => array('dir' => 'mv3pro_portail', 'label' => 'MV3 PRO Portail'))
        );

        $this->dirs = array('/mv3pro_portail/temp', '/mv3pro_portail/rapports');

        $this->const = array();
        $r = 0;

        $this->const[$r] = array(
            'MV3PRO_PORTAIL_DIR_OUTPUT',
            'chaine',
            DOL_DATA_ROOT.'/mv3pro_portail',
            'Répertoire des documents du module MV3 PRO',
            0,
            'current',
            1
        );
        $r++;

        $this->config_page_url = array('config.php@mv3pro_portail');

        // Droits
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = 510001;
        $this->rights[$r][1] = 'Lire module MV3';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = 510002;
        $this->rights[$r][1] = 'Écrire module MV3';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->rights[$r][0] = 510003;
        $this->rights[$r][1] = 'Valider rapports';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'validate';
        $r++;

        $this->rights[$r][0] = 510004;
        $this->rights[$r][1] = 'Accès interface mobile ouvrier';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'worker';
        $r++;

        $this->rights[$r][0] = 510005;
        $this->rights[$r][1] = 'Accès affichage TV';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'tv';

        // Menus
        $this->menu = array();
        $r = 0;

        // Menu principal en haut
        $this->menu[$r] = array(
            'fk_menu'   => '',
            'type'      => 'top',
            'titre'     => 'MV-3 PRO',
            'prefix'    => '<span class="fa fa-cubes fa-fw paddingright pictofixedwidth"></span>',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/index.php',
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
            'titre'     => 'Tableau de bord',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_dashboard',
            'url'       => '/custom/mv3pro_portail/index.php',
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
            'titre'     => 'Rapports journaliers',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_rapports',
            'url'       => '/custom/mv3pro_portail/rapports/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 200,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Liste des rapports
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_rapports',
            'type'      => 'left',
            'titre'     => '- Liste des rapports',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/rapports/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 201,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Nouveau rapport
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_rapports',
            'type'      => 'left',
            'titre'     => '- Nouveau rapport',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/rapports/new.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 202,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Signalements
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Signalements',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_signalements',
            'url'       => '/custom/mv3pro_portail/signalements/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 300,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Liste des signalements
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_signalements',
            'type'      => 'left',
            'titre'     => '- Liste des signalements',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/signalements/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 301,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Nouveau signalement
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_signalements',
            'type'      => 'left',
            'titre'     => '- Nouveau signalement',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/signalements/edit.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 302,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Matériel
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Matériel',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_materiel',
            'url'       => '/custom/mv3pro_portail/materiel/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 400,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Liste du matériel
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_materiel',
            'type'      => 'left',
            'titre'     => '- Liste du matériel',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/materiel/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 401,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Nouveau matériel
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_materiel',
            'type'      => 'left',
            'titre'     => '- Nouveau matériel',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/materiel/edit.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 402,
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
            'url'       => '/custom/mv3pro_portail/planning/index.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 500,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Liste du planning
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_planning',
            'type'      => 'left',
            'titre'     => '- Vue planning',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/planning/index.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 501,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Nouveau planning
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_planning',
            'type'      => 'left',
            'titre'     => '- Nouveau planning',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/planning/new.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 502,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Notifications
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Notifications',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_notifications',
            'url'       => '/custom/mv3pro_portail/notifications/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 600,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Mes notifications
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_notifications',
            'type'      => 'left',
            'titre'     => '- Mes notifications',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/notifications/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 601,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Envoyer notification (admin only)
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_notifications',
            'type'      => 'left',
            'titre'     => '- Envoyer notification',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/send_notification.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 602,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Configuration notifications (admin only)
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_notifications',
            'type'      => 'left',
            'titre'     => '- Configuration',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/admin/notifications.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 603,
            'enabled'   => '1',
            'perms'     => '$user->admin',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Bons de régie
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Bons de régie',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_regie',
            'url'       => '/custom/mv3pro_portail/regie/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 700,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Liste des bons de régie
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_regie',
            'type'      => 'left',
            'titre'     => '- Liste des bons',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/regie/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 701,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Nouveau bon de régie
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_regie',
            'type'      => 'left',
            'titre'     => '- Nouveau bon',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/regie/card.php?action=create',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 702,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Sens de pose
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Sens de pose',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => 'mv3pro_sens_pose',
            'url'       => '/custom/mv3pro_portail/sens_pose/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 750,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Liste des plans
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_sens_pose',
            'type'      => 'left',
            'titre'     => '- Liste des plans',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/sens_pose/list.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 751,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Nouveau plan
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_sens_pose',
            'type'      => 'left',
            'titre'     => '- Nouveau plan',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/sens_pose/new.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 752,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Créer depuis devis
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro,fk_leftmenu=mv3pro_sens_pose',
            'type'      => 'left',
            'titre'     => '- Depuis devis',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/sens_pose/new_from_devis.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 753,
            'enabled'   => '1',
            'perms'     => '1',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Sous-menu gauche: Mobile
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Interface mobile',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/mobile_app/index.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 800,
            'enabled'   => '1',
            'perms'     => '$user->rights->mv3pro_portail->worker',
            'target'    => '_blank',
            'user'      => 2
        );
        $r++;

        // Sous-menu: Configuration
        $this->menu[$r] = array(
            'fk_menu'   => 'fk_mainmenu=mv3pro',
            'type'      => 'left',
            'titre'     => 'Configuration',
            'mainmenu'  => 'mv3pro',
            'leftmenu'  => '',
            'url'       => '/custom/mv3pro_portail/admin/config.php',
            'langs'     => 'mv3pro_portail@mv3pro_portail',
            'position'  => 1000,
            'enabled'   => '1',
            'perms'     => '$user->admin',
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
