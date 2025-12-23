<?php
/**
 * Descripteur du module MV-3 TV Display
 * Module d'affichage TV professionnel pour Dolibarr
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modMv3TvDisplay extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 500002;
        $this->rights_class = 'mv3_tv_display';
        $this->family = "projects";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Module d'affichage TV professionnel pour chantiers, dépôts et showrooms";
        $this->descriptionlong = "Module d'affichage TV avec modes multiples: chantier, dépôt, exposition, bureau. Affichage temps réel, carrousel automatique, galerie photos, QR codes, statistiques animées.";
        $this->editor_name = 'MV-3 PRO';
        $this->editor_url = 'https://www.mv-3pro.ch';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'tv';

        $this->module_parts = array();

        $this->dirs = array();

        $this->config_page_url = array("config.php@mv3_tv_display");

        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();

        $this->langfiles = array("mv3_tv_display@mv3_tv_display");

        $this->phpmin = array(7, 4);
        $this->need_dolibarr_version = array(13, 0);

        $this->warnings_activation = array();
        $this->warnings_activation_ext = array();

        $this->const = array();

        $r = 0;

        // Configuration par défaut
        $this->const[$r][0] = "MV3_TV_COMPANY_NAME";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "MV-3 PRO";
        $this->const[$r][3] = "Nom de l'entreprise affiché";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_COMPANY_SLOGAN";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "Experts en carrelage depuis 2020";
        $this->const[$r][3] = "Slogan de l'entreprise";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_COLOR_PRIMARY";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "#3b82f6";
        $this->const[$r][3] = "Couleur primaire (hex)";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_COLOR_SECONDARY";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "#2563eb";
        $this->const[$r][3] = "Couleur secondaire (hex)";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_SLIDE_DURATION";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "10";
        $this->const[$r][3] = "Durée d'affichage de chaque slide (secondes)";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_AUTO_REFRESH";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "300";
        $this->const[$r][3] = "Fréquence de rafraîchissement complet (secondes)";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_GOAL_M2";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "500";
        $this->const[$r][3] = "Objectif m² par semaine";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_GOAL_RAPPORTS";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "20";
        $this->const[$r][3] = "Objectif nombre de rapports par semaine";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_SHOW_STATS";
        $this->const[$r][1] = "yesno";
        $this->const[$r][2] = "1";
        $this->const[$r][3] = "Afficher le slide statistiques";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_SHOW_PHOTOS";
        $this->const[$r][1] = "yesno";
        $this->const[$r][2] = "1";
        $this->const[$r][3] = "Afficher le slide photos";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_SHOW_ALERTS";
        $this->const[$r][1] = "yesno";
        $this->const[$r][2] = "1";
        $this->const[$r][3] = "Afficher le slide signalements";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_SHOW_GOALS";
        $this->const[$r][1] = "yesno";
        $this->const[$r][2] = "1";
        $this->const[$r][3] = "Afficher le slide objectifs";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_SHOW_QR";
        $this->const[$r][1] = "yesno";
        $this->const[$r][2] = "1";
        $this->const[$r][3] = "Afficher le slide QR code";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_MESSAGE_DEPOT";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "Ensemble, nous réalisons de grandes choses!";
        $this->const[$r][3] = "Message de motivation pour le mode dépôt";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_CONTACT_EMAIL";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "info@mv-3pro.ch";
        $this->const[$r][3] = "Email de contact affiché";
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "MV3_TV_REVIEWS";
        $this->const[$r][1] = "texte";
        $this->const[$r][2] = "";
        $this->const[$r][3] = "Avis clients (format: Nom|Note|Commentaire, un par ligne)";
        $this->const[$r][4] = 0;
        $r++;

        $this->tabs = array();

        $this->dictionaries = array();

        $this->boxes = array();

        $this->cronjobs = array();

        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
        $this->rights[$r][1] = 'Lire les affichages TV';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
        $this->rights[$r][1] = 'Gérer les affichages TV (admin)';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'manage';
        $r++;

        $this->menu = array();
        $r = 0;

        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=tools',
            'type' => 'left',
            'titre' => 'TV Display',
            'mainmenu' => 'tools',
            'leftmenu' => 'mv3_tv_display',
            'url' => '/custom/mv3_tv_display/admin/config.php',
            'langs' => 'mv3_tv_display@mv3_tv_display',
            'position' => 1000,
            'enabled' => '$conf->mv3_tv_display->enabled',
            'perms' => '$user->rights->mv3_tv_display->read',
            'target' => '',
            'user' => 2
        );
    }

    public function init($options = '')
    {
        global $conf, $langs;

        $sql = array();

        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
