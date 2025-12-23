<?php
/**
 * API pour récupérer les devis d'un client dans un projet
 */

header('Content-Type: application/json; charset=utf-8');
ob_start();

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

ob_clean();

if (!$res) {
    echo json_encode(['error' => 'Include of main fails']);
    exit;
}

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

$fk_client = GETPOST('fk_client', 'int');
$fk_projet = GETPOST('fk_projet', 'int');

if (!$fk_client) {
    echo json_encode(['error' => 'Client ID required']);
    exit;
}

$devis = [];

$sql = "SELECT
        pr.rowid,
        pr.ref,
        pr.ref_client,
        pr.datep as date_propal,
        pr.fk_statut,
        pr.total_ht,
        pr.total_ttc,
        COUNT(DISTINCT prd.rowid) as nb_lignes,
        COUNT(DISTINCT ecm.rowid) as nb_photos
        FROM ".MAIN_DB_PREFIX."propal pr
        LEFT JOIN ".MAIN_DB_PREFIX."propaldet prd ON prd.fk_propal = pr.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."ecm_files ecm ON (
            ecm.src_object_type = 'propal'
            AND ecm.src_object_id = pr.rowid
            AND ecm.filename IS NOT NULL
            AND (
                ecm.filename LIKE '%.jpg'
                OR ecm.filename LIKE '%.jpeg'
                OR ecm.filename LIKE '%.png'
                OR ecm.filename LIKE '%.gif'
                OR ecm.filename LIKE '%.JPG'
                OR ecm.filename LIKE '%.JPEG'
                OR ecm.filename LIKE '%.PNG'
            )
        )
        WHERE pr.fk_soc = ".(int)$fk_client."
        AND pr.entity = ".$conf->entity;

if ($fk_projet > 0) {
    $sql .= " AND pr.fk_projet = ".(int)$fk_projet;
}

$sql .= " GROUP BY pr.rowid, pr.ref, pr.ref_client, pr.datep, pr.fk_statut, pr.total_ht, pr.total_ttc
        ORDER BY pr.datep DESC
        LIMIT 50";

$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $propal = new Propal($db);
        $propal->fetch($obj->rowid);

        $statut_label = $propal->getLibStatut(1);

        $devis[] = [
            'rowid' => $obj->rowid,
            'ref' => $obj->ref,
            'ref_client' => $obj->ref_client,
            'date' => dol_print_date($obj->date_propal, 'day'),
            'statut' => $obj->fk_statut,
            'statut_label' => strip_tags($statut_label),
            'nb_lignes' => (int)$obj->nb_lignes,
            'nb_photos' => (int)$obj->nb_photos,
            'label' => $obj->ref . ($obj->ref_client ? ' (Ref: '.$obj->ref_client.')' : '') . ' - ' . dol_print_date($obj->date_propal, 'day') . ' (' . (int)$obj->nb_lignes . ' lignes, ' . (int)$obj->nb_photos . ' photos)'
        ];
    }
}

ob_clean();

echo json_encode([
    'success' => true,
    'count' => count($devis),
    'devis' => $devis
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

ob_end_flush();

$db->close();
