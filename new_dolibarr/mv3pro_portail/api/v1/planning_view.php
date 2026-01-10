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

// Détecter quelle colonne de note existe (compatibilité multi-versions Dolibarr)
// Essayer note_private d'abord (version récente), sinon note (version ancienne)
if (mv3_column_exists($db, 'actioncomm', 'note_private')) {
    $note_field = 'a.note_private';
} else {
    $note_field = 'a.note';
}

// Requête principale avec toutes les jointures
$sql = "SELECT
    a.id,
    a.label as titre,
    a.code as type_code,
    a.datep as date_debut,
    a.datep2 as date_fin,
    a.fulldayevent as all_day,
    a.location as lieu,
    ".$note_field." as description,
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
    if ($resql) {
        $db->free($resql);
    }
    json_error('Événement non trouvé', 'NOT_FOUND', 404);
}

$event = $db->fetch_object($resql);
$db->free($resql);

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
            if ($res_obj) {
                $db->free($res_obj);
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
            if ($res_obj) {
                $db->free($res_obj);
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
            if ($res_obj) {
                $db->free($res_obj);
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
            if ($res_obj) {
                $db->free($res_obj);
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

// Récupérer les fichiers joints via la table ECM de Dolibarr
log_debug("===== SCAN FICHIERS PLANNING #".$id." (VIA ECM) =====");
log_debug("DOL_DATA_ROOT: ".DOL_DATA_ROOT);

// Détecter quelles colonnes existent dans llx_ecm_files
$has_filesize = mv3_column_exists($db, 'ecm_files', 'filesize');
$filesize_field = $has_filesize ? 'ecm.filesize,' : '';

log_debug("Colonne filesize disponible: ".($has_filesize ? 'OUI' : 'NON'));

// Méthode 1: Via la table llx_ecm_files (méthode standard Dolibarr)
$sql_files = "SELECT
    ecm.rowid,
    ecm.label as filename,
    ecm.filename as stored_filename,
    ecm.filepath,
    ecm.fullpath_orig,
    ecm.date_c as date_creation,
    ".$filesize_field."
    ecm.position
FROM ".MAIN_DB_PREFIX."ecm_files as ecm
WHERE ecm.src_object_type = 'actioncomm'
AND ecm.src_object_id = ".$id."
ORDER BY ecm.position ASC, ecm.date_c DESC";

log_debug("SQL ECM:", ['sql' => $sql_files]);

$resql_files = $db->query($sql_files);
$files_count_ecm = 0;

if ($resql_files) {
    $num_files = $db->num_rows($resql_files);
    log_debug("Fichiers trouvés dans ECM: ".$num_files);

    while ($file_obj = $db->fetch_object($resql_files)) {
        log_debug("  - Fichier ECM: ".$file_obj->filename);

        // Construire le chemin complet du fichier
        $relative_path = $file_obj->filepath.'/'.$file_obj->stored_filename;
        $filepath = DOL_DATA_ROOT.'/'.$relative_path;

        log_debug("    Chemin relatif: ".$relative_path);
        log_debug("    Chemin complet: ".$filepath);
        log_debug("    Fichier existe: ".(file_exists($filepath) ? 'OUI' : 'NON'));

        if (!file_exists($filepath)) {
            log_debug("    => IGNORÉ (fichier n'existe pas sur disque)");
            continue;
        }

        // Taille: utiliser la colonne ECM si disponible, sinon lire depuis le disque
        $filesize = ($has_filesize && isset($file_obj->filesize) && $file_obj->filesize > 0)
            ? $file_obj->filesize
            : filesize($filepath);

        $mime = mime_content_type($filepath);
        $is_image = strpos($mime, 'image/') === 0;

        $base_api_path = dirname($_SERVER['SCRIPT_NAME']);

        $file_info = [
            'name' => $file_obj->filename,
            'size' => (int)$filesize,
            'size_human' => format_file_size($filesize),
            'mime' => $mime,
            'is_image' => $is_image,
            'url' => $base_api_path.'/planning_file.php?id='.$id.'&file='.urlencode($file_obj->stored_filename)
        ];

        log_debug("    => AJOUTÉ: ".$file_obj->filename." (".$mime.", ".format_file_size($filesize).", is_image=".($is_image?'yes':'no').")");
        $response['fichiers'][] = $file_info;
        $files_count_ecm++;
    }

    $db->free($resql_files);
} else {
    log_debug("Erreur requête ECM: ".$db->lasterror());
}

log_debug("Total fichiers ECM ajoutés: ".$files_count_ecm);

// Méthode 2 (fallback): Scanner le dossier filesystem si aucun fichier trouvé via ECM
if ($files_count_ecm === 0) {
    log_debug("Aucun fichier via ECM, tentative scan filesystem...");

    $upload_dir = DOL_DATA_ROOT.'/actioncomm/'.dol_sanitizeFileName($id);
    log_debug("Upload dir: ".$upload_dir);
    log_debug("Dossier existe: ".(is_dir($upload_dir) ? 'OUI' : 'NON'));

    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        log_debug("Fichiers bruts trouvés par scandir: ".count($files)." fichiers");

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || is_dir($upload_dir.'/'.$file)) {
                continue;
            }

            $filepath = $upload_dir.'/'.$file;
            if (!file_exists($filepath)) {
                continue;
            }

            $filesize = filesize($filepath);
            $mime = mime_content_type($filepath);
            $is_image = strpos($mime, 'image/') === 0;
            $base_api_path = dirname($_SERVER['SCRIPT_NAME']);

            $file_info = [
                'name' => $file,
                'size' => $filesize,
                'size_human' => format_file_size($filesize),
                'mime' => $mime,
                'is_image' => $is_image,
                'url' => $base_api_path.'/planning_file.php?id='.$id.'&file='.urlencode($file)
            ];

            log_debug("  Filesystem: AJOUTÉ ".$file);
            $response['fichiers'][] = $file_info;
        }
    } else {
        log_debug("⚠️ DOSSIER FILESYSTEM INEXISTANT: ".$upload_dir);
    }
}

log_debug("TOTAL fichiers retournés: ".count($response['fichiers']));
log_debug("===== FIN SCAN FICHIERS =====");

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
