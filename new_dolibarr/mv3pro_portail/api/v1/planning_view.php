<?php
/**
 * API v1 - Planning - Détail complet
 * GET /api/v1/planning_view.php?id=123
 *
 * Retourne les détails complets d'un événement avec :
 * - Informations de base (titre, dates, lieu, description)
 * - Utilisateur assigné
 * - Tiers/Société
 * - Projet
 * - Objet lié (commande, facture, etc.)
 * - Fichiers joints avec URL de téléchargement
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$id = (int)get_param('id', 0);
require_param($id, 'id');

// Requête principale avec toutes les jointures
$sql = "SELECT
    a.id,
    a.label as titre,
    a.code as type_code,
    a.datep as date_debut,
    a.datep2 as date_fin,
    a.fulldayevent as all_day,
    a.location as lieu,
    a.note_private as description,
    a.fk_user_action as fk_user,
    a.fk_soc as fk_soc,
    a.fk_project as fk_project,
    a.percent as progression,
    a.elementtype,
    a.fk_element,

    u.firstname as user_firstname,
    u.lastname as user_lastname,
    u.login as user_login,

    s.nom as societe_nom,
    s.client as societe_type,

    p.ref as projet_ref,
    p.title as projet_title

FROM ".MAIN_DB_PREFIX."actioncomm as a
LEFT JOIN ".MAIN_DB_PREFIX."user as u ON a.fk_user_action = u.rowid
LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid
LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON a.fk_project = p.rowid
WHERE a.id = ".$id."
AND a.entity IN (".getEntity('agenda').")";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Événement non trouvé', 'NOT_FOUND', 404);
}

$event = $db->fetch_object($resql);

// Construire la réponse
$response = [
    'id' => (int)$event->id,
    'titre' => $event->titre ?: 'Sans titre',
    'type_code' => $event->type_code,
    'date_debut' => $event->date_debut,
    'date_fin' => $event->date_fin,
    'all_day' => (int)$event->all_day,
    'lieu' => $event->lieu,
    'description' => $event->description,
    'progression' => (int)$event->progression,

    'user' => null,
    'societe' => null,
    'projet' => null,
    'objet_lie' => null,
    'fichiers' => []
];

// Utilisateur assigné
if ($event->fk_user) {
    $response['user'] = [
        'id' => (int)$event->fk_user,
        'nom_complet' => trim($event->user_firstname.' '.$event->user_lastname),
        'login' => $event->user_login
    ];
}

// Société/Tiers
if ($event->fk_soc) {
    $response['societe'] = [
        'id' => (int)$event->fk_soc,
        'nom' => $event->societe_nom,
        'type' => (int)$event->societe_type
    ];
}

// Projet
if ($event->fk_project) {
    $response['projet'] = [
        'id' => (int)$event->fk_project,
        'ref' => $event->projet_ref,
        'titre' => $event->projet_title
    ];
}

// Objet lié (commande, facture, propal, etc.)
if ($event->elementtype && $event->fk_element) {
    $objet_ref = '';
    $objet_label = '';

    switch ($event->elementtype) {
        case 'order':
        case 'commande':
            $sql_obj = "SELECT ref, ref_client FROM ".MAIN_DB_PREFIX."commande WHERE rowid = ".(int)$event->fk_element;
            $res_obj = $db->query($sql_obj);
            if ($res_obj && $db->num_rows($res_obj) > 0) {
                $obj = $db->fetch_object($res_obj);
                $objet_ref = $obj->ref;
                $objet_label = 'Commande';
            }
            break;

        case 'invoice':
        case 'facture':
            $sql_obj = "SELECT ref FROM ".MAIN_DB_PREFIX."facture WHERE rowid = ".(int)$event->fk_element;
            $res_obj = $db->query($sql_obj);
            if ($res_obj && $db->num_rows($res_obj) > 0) {
                $obj = $db->fetch_object($res_obj);
                $objet_ref = $obj->ref;
                $objet_label = 'Facture';
            }
            break;

        case 'propal':
            $sql_obj = "SELECT ref FROM ".MAIN_DB_PREFIX."propal WHERE rowid = ".(int)$event->fk_element;
            $res_obj = $db->query($sql_obj);
            if ($res_obj && $db->num_rows($res_obj) > 0) {
                $obj = $db->fetch_object($res_obj);
                $objet_ref = $obj->ref;
                $objet_label = 'Proposition commerciale';
            }
            break;

        case 'project':
        case 'projet':
            $sql_obj = "SELECT ref, title FROM ".MAIN_DB_PREFIX."projet WHERE rowid = ".(int)$event->fk_element;
            $res_obj = $db->query($sql_obj);
            if ($res_obj && $db->num_rows($res_obj) > 0) {
                $obj = $db->fetch_object($res_obj);
                $objet_ref = $obj->ref;
                $objet_label = 'Projet';
            }
            break;
    }

    $response['objet_lie'] = [
        'type' => $event->elementtype,
        'type_label' => $objet_label,
        'id' => (int)$event->fk_element,
        'ref' => $objet_ref
    ];
}

// Récupérer les fichiers joints
// Les fichiers d'un actioncomm sont dans documents/actioncomm/{id}/
$upload_dir = DOL_DATA_ROOT.'/actioncomm/'.dol_sanitizeFileName($id);

log_debug("Recherche fichiers dans: ".$upload_dir);

if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || is_dir($upload_dir.'/'.$file)) {
            continue;
        }

        $filepath = $upload_dir.'/'.$file;
        $filesize = filesize($filepath);
        $mime = mime_content_type($filepath);

        // Déterminer si c'est une image
        $is_image = strpos($mime, 'image/') === 0;

        $response['fichiers'][] = [
            'name' => $file,
            'size' => $filesize,
            'size_human' => format_file_size($filesize),
            'mime' => $mime,
            'is_image' => $is_image,
            'url' => '/custom/mv3pro_portail/api/v1/planning_file.php?id='.$id.'&file='.urlencode($file)
        ];
    }
}

log_debug("Planning view #".$id." - ".count($response['fichiers'])." fichiers trouvés");

json_ok($response);

/**
 * Formater la taille d'un fichier en format lisible
 */
function format_file_size($bytes) {
    if ($bytes < 1024) return $bytes.' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2).' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 2).' MB';
    return round($bytes / 1073741824, 2).' GB';
}
