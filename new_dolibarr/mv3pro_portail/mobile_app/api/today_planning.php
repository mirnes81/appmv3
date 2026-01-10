<?php
/**
 * API - Planning du jour pour l'ouvrier connecté
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
setupApiHeaders();

global $db, $user;

checkApiAuth($db);

$user_id = $user->id;
$today = date('Y-m-d');

$events = [];

$sql = "SELECT DISTINCT a.id, a.label, a.datep, a.datep2, a.fulldayevent, a.location,
        s.nom as client_nom, p.ref as projet_ref, p.title as projet_title
        FROM ".MAIN_DB_PREFIX."actioncomm a
        LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ac ON ac.id = a.fk_action
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = a.fk_soc
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = a.fk_project
        LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
        WHERE (a.fk_user_author = ".(int)$user_id."
               OR a.fk_user_action = ".(int)$user_id."
               OR a.fk_user_done = ".(int)$user_id."
               OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$user_id."))
        AND a.entity IN (".getEntity('actioncomm').")
        AND ac.code IN ('AC_POS', 'AC_plan')
        AND (
            (a.datep2 IS NOT NULL AND DATE(a.datep) <= '".$db->escape($today)."' AND DATE(a.datep2) >= '".$db->escape($today)."')
            OR (DATE(a.datep) = '".$db->escape($today)."')
        )
        ORDER BY a.datep ASC";

$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $event = [
            'id' => $obj->id,
            'label' => $obj->label,
            'client' => $obj->client_nom,
            'projet' => $obj->projet_title ? ($obj->projet_ref ? $obj->projet_ref.' - ' : '').$obj->projet_title : null,
            'location' => $obj->location,
            'fulldayevent' => $obj->fulldayevent ? true : false
        ];

        if (!$obj->fulldayevent) {
            $event['time'] = date('H:i', strtotime($obj->datep));
            if ($obj->datep2) {
                $event['time'] .= ' - '.date('H:i', strtotime($obj->datep2));
            }
        } else {
            $event['time'] = 'Toute la journée';
        }

        $events[] = $event;
    }
}

jsonSuccess([
    'events' => $events,
    'date' => $today
]);
?>
