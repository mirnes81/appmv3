<?php

class ActionsMv3pro_portail
{
    public $db;
    public $resprints;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $contexts = explode(':', $parameters['context']);
        if (in_array('propalcard', $contexts)) {
            if (!empty($object->id) && !empty($object->socid)) {
                $url = DOL_URL_ROOT.'/custom/mv3pro_portail/propal_tab.php?id='.$object->id;
                $this->resprints = '<div class="inline-block divButAction">';
                $this->resprints .= '<a class="butAction" href="'.$url.'">';
                $this->resprints .= '<span class="fa fa-layer-group"></span> MV-3 PRO';
                $this->resprints .= '</a>';
                $this->resprints .= '</div>';
            }
        }

        return 0;
    }
}
