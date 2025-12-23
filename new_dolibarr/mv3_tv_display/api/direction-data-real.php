<?php
/**
 * API pour récupérer les données RÉELLES du mode Direction
 * Utilise les vraies données de MV3pro_portail + Dolibarr
 */

require_once '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

header('Content-Type: application/json');

$data = array();

// ============================================
// CHIFFRE D'AFFAIRES
// ============================================

// CA Total (Factures validées)
$sql = "SELECT SUM(total_ttc) as ca_total
        FROM ".MAIN_DB_PREFIX."facture
        WHERE fk_statut >= 1
        AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $data['ca_total'] = (float)$obj->ca_total;
} else {
    $data['ca_total'] = 0;
}

// CA ce mois
$sql = "SELECT SUM(total_ttc) as ca_mois
        FROM ".MAIN_DB_PREFIX."facture
        WHERE fk_statut >= 1
        AND MONTH(datef) = MONTH(NOW())
        AND YEAR(datef) = YEAR(NOW())
        AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $data['ca_mois'] = (float)$obj->ca_mois;
} else {
    $data['ca_mois'] = 0;
}

// CA aujourd'hui
$sql = "SELECT SUM(total_ttc) as ca_jour
        FROM ".MAIN_DB_PREFIX."facture
        WHERE fk_statut >= 1
        AND DATE(datef) = CURDATE()
        AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $data['ca_jour'] = (float)$obj->ca_jour;
} else {
    $data['ca_jour'] = 0;
}

// Objectif CA
$data['ca_objectif'] = !empty($conf->global->MV3_TV_GOAL_CA_MOIS) ? $conf->global->MV3_TV_GOAL_CA_MOIS : 300000;

// Marge globale
$sql = "SELECT SUM(total_ttc) as ca, SUM(total_ht) as ht
        FROM ".MAIN_DB_PREFIX."facture
        WHERE fk_statut >= 1
        AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $data['marge_globale'] = ($obj->ca > 0) ? round((($obj->ca - $obj->ht) / $obj->ca) * 100, 1) : 0;
} else {
    $data['marge_globale'] = 0;
}

// ============================================
// PROJETS
// ============================================

$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."projet WHERE fk_statut = 1 AND entity = ".$conf->entity;
$resql = $db->query($sql);
$data['projets_actifs'] = $resql ? (int)$db->fetch_object($resql)->nb : 0;

$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."projet WHERE fk_statut = 1 AND (datee IS NULL OR datee >= CURDATE()) AND entity = ".$conf->entity;
$resql = $db->query($sql);
$data['projets_on_track'] = $resql ? (int)$db->fetch_object($resql)->nb : 0;

$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."projet WHERE fk_statut = 1 AND datee < CURDATE() AND entity = ".$conf->entity;
$resql = $db->query($sql);
$data['projets_retard'] = $resql ? (int)$db->fetch_object($resql)->nb : 0;

// ============================================
// PRODUCTION (m²)
// ============================================

$sql = "SELECT SUM(surface_carrelee) as m2_mois
        FROM ".MAIN_DB_PREFIX."mv3_rapport
        WHERE MONTH(date_rapport) = MONTH(NOW())
        AND YEAR(date_rapport) = YEAR(NOW())
        AND entity = ".$conf->entity;
$resql = $db->query($sql);
$data['m2_mois'] = $resql ? (float)$db->fetch_object($resql)->m2_mois : 0;

$sql = "SELECT SUM(surface_carrelee) as m2_jour
        FROM ".MAIN_DB_PREFIX."mv3_rapport
        WHERE DATE(date_rapport) = CURDATE()
        AND entity = ".$conf->entity;
$resql = $db->query($sql);
$data['m2_jour'] = $resql ? (float)$db->fetch_object($resql)->m2_jour : 0;

$sql = "SELECT AVG(daily_m2) as m2_moyen FROM (
            SELECT SUM(surface_carrelee) as daily_m2
            FROM ".MAIN_DB_PREFIX."mv3_rapport
            WHERE MONTH(date_rapport) = MONTH(NOW())
            AND YEAR(date_rapport) = YEAR(NOW())
            AND entity = ".$conf->entity."
            GROUP BY DATE(date_rapport)
        ) as stats";
$resql = $db->query($sql);
$data['m2_moyen'] = $resql ? (float)$db->fetch_object($resql)->m2_moyen : 0;

// ============================================
// ÉQUIPES
// ============================================

$sql = "SELECT COUNT(DISTINCT rowid) as nb FROM ".MAIN_DB_PREFIX."user WHERE statut = 1 AND entity = ".$conf->entity;
$resql = $db->query($sql);
$data['ouvriers_total'] = $resql ? (int)$db->fetch_object($resql)->nb : 0;

$sql = "SELECT COUNT(DISTINCT fk_user) as nb FROM ".MAIN_DB_PREFIX."mv3_rapport WHERE DATE(date_rapport) = CURDATE() AND entity = ".$conf->entity;
$resql = $db->query($sql);
$data['equipes_actives'] = $resql ? (int)$db->fetch_object($resql)->nb : 0;

$data['taux_presence'] = ($data['ouvriers_total'] > 0) ? round(($data['equipes_actives'] / $data['ouvriers_total']) * 100) : 0;

// ============================================
// ÉVOLUTION (7 derniers jours)
// ============================================

$evolution = array('labels' => array(), 'values' => array(), 'target' => array());

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = strftime('%a', strtotime($date));

    $sql = "SELECT SUM(total_ttc) as ca FROM ".MAIN_DB_PREFIX."facture WHERE DATE(datef) = '".$db->escape($date)."' AND fk_statut >= 1 AND entity = ".$conf->entity;
    $resql = $db->query($sql);
    $ca = $resql ? (float)$db->fetch_object($resql)->ca : 0;

    $evolution['labels'][] = $label;
    $evolution['values'][] = $ca;
    $evolution['target'][] = 50000;
}

$data['evolution'] = $evolution;

// ============================================
// ALERTES
// ============================================

$alerts = array();

$sql = "SELECT s.*, p.title as projet_title
        FROM ".MAIN_DB_PREFIX."mv3_signalement s
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = s.fk_projet
        WHERE s.statut = 'ouvert'
        AND s.priorite IN ('haute', 'critique')
        AND s.entity = ".$conf->entity."
        ORDER BY s.date_creation DESC
        LIMIT 5";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    for ($i = 0; $i < $num; $i++) {
        $obj = $db->fetch_object($resql);
        $alerts[] = array(
            'severity' => $obj->priorite == 'critique' ? 'critical' : 'warning',
            'icon' => '⚠️',
            'title' => $obj->titre,
            'message' => substr($obj->description, 0, 80),
            'time' => 'Il y a '.human_time_diff($db->jdate($obj->date_creation), time())
        );
    }
}

$data['alerts'] = $alerts;

// ============================================
// PROJETS EN COURS
// ============================================

$projects = array();

$sql = "SELECT p.rowid, p.title, p.datee, p.budget_amount
        FROM ".MAIN_DB_PREFIX."projet p
        WHERE p.fk_statut = 1
        AND p.entity = ".$conf->entity."
        ORDER BY p.dateo DESC
        LIMIT 8";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    for ($i = 0; $i < $num; $i++) {
        $obj = $db->fetch_object($resql);

        $status = 'on-track';
        if ($obj->datee && $db->jdate($obj->datee) < time()) {
            $status = 'delayed';
        } elseif ($obj->datee && $db->jdate($obj->datee) < strtotime('+7 days')) {
            $status = 'at-risk';
        }

        $sql_m2 = "SELECT SUM(surface_carrelee) as done FROM ".MAIN_DB_PREFIX."mv3_rapport WHERE fk_projet = ".$obj->rowid;
        $res_m2 = $db->query($sql_m2);
        $progress = 0;
        if ($res_m2 && $obj_m2 = $db->fetch_object($res_m2)) {
            $progress = min(100, round(($obj_m2->done / 100) * 100));
        }

        $projects[] = array(
            'name' => $obj->title,
            'status' => $status,
            'progress' => $progress,
            'value' => (float)$obj->budget_amount,
            'deadline' => $obj->datee ? date('d M', $db->jdate($obj->datee)) : 'N/A'
        );
    }
}

$data['projects'] = $projects;

// ============================================
// FACTURES IMPAYÉES
// ============================================

$unpaid_invoices = array(
    'clients' => array(),
    'fournisseurs' => array(),
    'total_clients' => array('nombre' => 0, 'montant' => 0),
    'total_fournisseurs' => array('nombre' => 0, 'montant' => 0)
);

// Factures clients impayées (statut = 1 = validée, paye = 0 = non payée)
$sql = "SELECT
        COUNT(*) as nb_clients,
        SUM(total_ttc) as montant_clients,
        YEAR(datef) as annee
        FROM ".MAIN_DB_PREFIX."facture
        WHERE fk_statut = 1
        AND paye = 0
        AND entity = ".$conf->entity."
        GROUP BY YEAR(datef)
        ORDER BY annee DESC";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    for ($i = 0; $i < $num; $i++) {
        $obj = $db->fetch_object($resql);
        $unpaid_invoices['clients'][] = array(
            'annee' => (int)$obj->annee,
            'nombre' => (int)$obj->nb_clients,
            'montant' => (float)$obj->montant_clients
        );
    }
}

// Factures fournisseurs impayées (statut = 1 = validée, paye = 0 = non payée)
$sql = "SELECT
        COUNT(*) as nb_fournisseurs,
        SUM(total_ttc) as montant_fournisseurs,
        YEAR(datef) as annee
        FROM ".MAIN_DB_PREFIX."facture_fourn
        WHERE fk_statut = 1
        AND paye = 0
        AND entity = ".$conf->entity."
        GROUP BY YEAR(datef)
        ORDER BY annee DESC";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    for ($i = 0; $i < $num; $i++) {
        $obj = $db->fetch_object($resql);
        $unpaid_invoices['fournisseurs'][] = array(
            'annee' => (int)$obj->annee,
            'nombre' => (int)$obj->nb_fournisseurs,
            'montant' => (float)$obj->montant_fournisseurs
        );
    }
}

// Totaux globaux (toutes années confondues)
$sql = "SELECT COUNT(*) as nb, SUM(total_ttc) as montant FROM ".MAIN_DB_PREFIX."facture WHERE fk_statut = 1 AND paye = 0 AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $unpaid_invoices['total_clients'] = array(
        'nombre' => (int)$obj->nb,
        'montant' => (float)$obj->montant
    );
}

$sql = "SELECT COUNT(*) as nb, SUM(total_ttc) as montant FROM ".MAIN_DB_PREFIX."facture_fourn WHERE fk_statut = 1 AND paye = 0 AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql && $obj = $db->fetch_object($resql)) {
    $unpaid_invoices['total_fournisseurs'] = array(
        'nombre' => (int)$obj->nb,
        'montant' => (float)$obj->montant
    );
}

$data['unpaid_invoices'] = $unpaid_invoices;

// Helper function
function human_time_diff($from, $to) {
    $diff = $to - $from;
    if ($diff < 3600) return round($diff/60).' min';
    if ($diff < 86400) return round($diff/3600).' h';
    return round($diff/86400).' j';
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
