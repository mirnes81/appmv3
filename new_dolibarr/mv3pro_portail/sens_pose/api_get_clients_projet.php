<?php
/**
 * API pour récupérer les clients d'un projet
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

$fk_projet = GETPOST('fk_projet', 'int');

if (!$fk_projet) {
    echo json_encode(['error' => 'Projet ID required']);
    exit;
}

$clients = [];

$sql = "SELECT DISTINCT
        s.rowid,
        s.nom,
        s.code_client,
        COUNT(DISTINCT pr.rowid) as nb_devis,
        COUNT(DISTINCT c.rowid) as nb_commandes
        FROM ".MAIN_DB_PREFIX."societe s
        LEFT JOIN ".MAIN_DB_PREFIX."propal pr ON (
            pr.fk_soc = s.rowid
            AND pr.fk_projet = ".(int)$fk_projet."
        )
        LEFT JOIN ".MAIN_DB_PREFIX."commande c ON (
            c.fk_soc = s.rowid
            AND c.fk_projet = ".(int)$fk_projet."
        )
        WHERE (pr.rowid IS NOT NULL OR c.rowid IS NOT NULL)
        GROUP BY s.rowid, s.nom, s.code_client
        ORDER BY s.nom ASC";

$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $clients[] = [
            'rowid' => $obj->rowid,
            'nom' => $obj->nom,
            'code_client' => $obj->code_client,
            'nb_devis' => (int)$obj->nb_devis,
            'nb_commandes' => (int)$obj->nb_commandes,
            'label' => $obj->nom . ' (' . $obj->nb_devis . ' devis, ' . $obj->nb_commandes . ' commandes)'
        ];
    }
}

ob_clean();

echo json_encode([
    'success' => true,
    'count' => count($clients),
    'clients' => $clients
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

ob_end_flush();

$db->close();
