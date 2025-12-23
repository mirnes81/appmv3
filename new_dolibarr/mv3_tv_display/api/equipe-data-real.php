<?php
/**
 * API pour récupérer les données RÉELLES d'une équipe
 * Utilise les vraies données de MV3pro_portail
 */

require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

header('Content-Type: application/json');

$equipe_id = GETPOST('equipe_id', 'int') ?: 0;
$user_id = GETPOST('user_id', 'int') ?: 0;

$data = array();

// Si equipe_id fourni, on peut récupérer le nom (tu peux adapter selon ta structure)
// Sinon on prend le premier user actif
if (!$user_id && !$equipe_id) {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE statut = 1 AND entity = ".$conf->entity." LIMIT 1";
    $resql = $db->query($sql);
    if ($resql && $obj = $db->fetch_object($resql)) {
        $user_id = $obj->rowid;
    }
}

// ============================================
// INFOS ÉQUIPE/USER
// ============================================

if ($user_id) {
    $user = new User($db);
    $user->fetch($user_id);

    $data['initial'] = strtoupper(substr($user->firstname, 0, 1));
    $data['name'] = 'Équipe '.$user->firstname;
} else {
    $data['initial'] = 'A';
    $data['name'] = 'Équipe Alpha';
}

// ============================================
// PRODUCTION SEMAINE (m²)
// ============================================

$sql = "SELECT SUM(surface_carrelee) as m2_week
        FROM ".MAIN_DB_PREFIX."mv3_rapport
        WHERE WEEK(date_rapport) = WEEK(NOW())
        AND YEAR(date_rapport) = YEAR(NOW())
        ".($user_id ? "AND fk_user = ".$user_id : "")."
        AND entity = ".$conf->entity;
$resql = $db->query($sql);
$data['m2_week'] = $resql ? (int)$db->fetch_object($resql)->m2_week : 0;

// ============================================
// MEMBRES DE L'ÉQUIPE
// ============================================

$members = array();

if ($user_id) {
    $members[] = array('name' => $user->firstname.' '.$user->lastname);
} else {
    // Liste des users actifs
    $sql = "SELECT firstname, lastname
            FROM ".MAIN_DB_PREFIX."user
            WHERE statut = 1
            AND entity = ".$conf->entity."
            LIMIT 5";
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        for ($i = 0; $i < $num; $i++) {
            $obj = $db->fetch_object($resql);
            $members[] = array('name' => $obj->firstname.' '.$obj->lastname);
        }
    }
}

$data['members'] = $members;

// ============================================
// TÂCHES DU JOUR (Rapports + Projets actifs)
// ============================================

$tasks = array();

// Rapports d'aujourd'hui
$sql = "SELECT r.*, p.title as projet_title, p.ref
        FROM ".MAIN_DB_PREFIX."mv3_rapport r
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = r.fk_projet
        WHERE DATE(r.date_rapport) = CURDATE()
        ".($user_id ? "AND r.fk_user = ".$user_id : "")."
        AND r.entity = ".$conf->entity."
        ORDER BY r.heures_debut ASC";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    for ($i = 0; $i < $num; $i++) {
        $obj = $db->fetch_object($resql);

        $status = 'done';
        if ($obj->statut == 'brouillon') $status = 'in-progress';
        if (!$obj->heures_fin) $status = 'in-progress';

        $tasks[] = array(
            'time' => substr($obj->heures_debut, 0, 5),
            'title' => $obj->projet_title ? $obj->projet_title : $obj->zone_travail,
            'location' => $obj->zone_travail,
            'status' => $status
        );
    }
}

// Si pas de tâches, ajouter tâche par défaut
if (count($tasks) == 0) {
    $tasks[] = array(
        'time' => '08:00',
        'title' => 'En attente de rapport',
        'location' => 'Pas de rapport aujourd\'hui',
        'status' => 'pending'
    );
}

$data['tasks'] = $tasks;

// ============================================
// OBJECTIFS
// ============================================

// Objectif m² semaine
$objectif_m2 = !empty($conf->global->MV3_TV_GOAL_M2) ? $conf->global->MV3_TV_GOAL_M2 : 500;

// Rapports quotidiens
$sql = "SELECT COUNT(*) as nb
        FROM ".MAIN_DB_PREFIX."mv3_rapport
        WHERE WEEK(date_rapport) = WEEK(NOW())
        AND YEAR(date_rapport) = YEAR(NOW())
        ".($user_id ? "AND fk_user = ".$user_id : "")."
        AND entity = ".$conf->entity;
$resql = $db->query($sql);
$nb_rapports = $resql ? (int)$db->fetch_object($resql)->nb : 0;

$objectif_rapports = !empty($conf->global->MV3_TV_GOAL_RAPPORTS) ? $conf->global->MV3_TV_GOAL_RAPPORTS : 5;

// Qualité moyenne (si tu as un système de notation)
$qualite_moyenne = 9.0; // Par défaut

$data['objectives'] = array(
    array(
        'name' => 'm² cette semaine',
        'current' => $data['m2_week'],
        'target' => $objectif_m2
    ),
    array(
        'name' => 'Rapports cette semaine',
        'current' => $nb_rapports,
        'target' => $objectif_rapports
    ),
    array(
        'name' => 'Qualité moyenne',
        'current' => $qualite_moyenne,
        'target' => 9.0
    )
);

// ============================================
// CLASSEMENT ÉQUIPE (Top performers)
// ============================================

$leaderboard = array();

$sql = "SELECT
        u.firstname, u.lastname,
        SUM(r.surface_carrelee) as m2_week,
        COUNT(r.rowid) as nb_rapports
        FROM ".MAIN_DB_PREFIX."mv3_rapport r
        INNER JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = r.fk_user
        WHERE WEEK(r.date_rapport) = WEEK(NOW())
        AND YEAR(r.date_rapport) = YEAR(NOW())
        AND r.entity = ".$conf->entity."
        GROUP BY r.fk_user
        ORDER BY m2_week DESC
        LIMIT 5";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    for ($i = 0; $i < $num; $i++) {
        $obj = $db->fetch_object($resql);

        $badge = null;
        if ($obj->m2_week >= 200) $badge = '⚡'; // Speed demon
        if ($obj->m2_week >= 150) $badge = '⭐'; // Star
        if ($obj->nb_rapports >= 5) $badge = '📅'; // Regular

        $leaderboard[] = array(
            'name' => $obj->firstname.' '.$obj->lastname,
            'score' => (int)$obj->m2_week,
            'badge' => $badge
        );
    }
}

$data['leaderboard'] = $leaderboard;

// ============================================
// PHOTOS DE L'ÉQUIPE
// ============================================

$photos = array();

$sql = "SELECT
        rp.filepath, rp.filename, rp.description,
        r.date_rapport,
        p.title as projet_title
        FROM ".MAIN_DB_PREFIX."mv3_rapport_photo rp
        INNER JOIN ".MAIN_DB_PREFIX."mv3_rapport r ON r.rowid = rp.fk_rapport
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = r.fk_projet
        WHERE WEEK(r.date_rapport) = WEEK(NOW())
        AND YEAR(r.date_rapport) = YEAR(NOW())
        ".($user_id ? "AND r.fk_user = ".$user_id : "")."
        AND r.entity = ".$conf->entity."
        ORDER BY rp.date_upload DESC
        LIMIT 6";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    for ($i = 0; $i < $num; $i++) {
        $obj = $db->fetch_object($resql);

        // Construction de l'URL de la photo
        $photo_url = DOL_URL_ROOT.'/custom/mv3pro_portail/rapports/photo.php?file='.$obj->filepath;

        $photos[] = array(
            'url' => $photo_url,
            'title' => $obj->projet_title ? $obj->projet_title : ($obj->description ?: 'Chantier'),
            'date' => dol_print_date($db->jdate($obj->date_rapport), 'day')
        );
    }
}

// Photos par défaut si aucune
if (count($photos) == 0) {
    $photos[] = array(
        'url' => 'https://images.unsplash.com/photo-1615971677499-5467cbab01c0?w=400',
        'title' => 'Pas encore de photos',
        'date' => 'Cette semaine'
    );
}

$data['photos'] = $photos;

// ============================================
// MESSAGE DE MOTIVATION
// ============================================

$percent_objectif = $objectif_m2 > 0 ? round(($data['m2_week'] / $objectif_m2) * 100) : 0;

if ($percent_objectif >= 100) {
    $message = '🎉 OBJECTIF ATTEINT! Bravo à toute l\'équipe! 💪';
} elseif ($percent_objectif >= 80) {
    $message = '💪 Excellente semaine! Objectif à '.$percent_objectif.'%! Continuez comme ça!';
} elseif ($percent_objectif >= 50) {
    $message = '👍 Bon travail! Objectif à '.$percent_objectif.'%. On accélère!';
} else {
    $message = '🚀 C\'est parti! Objectif à '.$percent_objectif.'%. On peut le faire!';
}

$data['message'] = $message;

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
