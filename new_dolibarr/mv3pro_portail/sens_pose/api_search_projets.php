<?php
/**
 * API pour rechercher des projets (autocomplete)
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

$search = GETPOST('search', 'alpha');

if (empty($search)) {
    echo json_encode(['error' => 'Search term required', 'projets' => []]);
    exit;
}

$projets = [];

$sql = "SELECT
        p.rowid,
        p.ref,
        p.title,
        p.fk_soc,
        s.nom as client_name
        FROM ".MAIN_DB_PREFIX."projet p
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = p.fk_soc
        WHERE p.entity = ".$conf->entity."
        AND (
            p.ref LIKE '%".$db->escape($search)."%'
            OR p.title LIKE '%".$db->escape($search)."%'
            OR s.nom LIKE '%".$db->escape($search)."%'
        )
        ORDER BY p.ref DESC
        LIMIT 20";

$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $projets[] = [
            'rowid' => $obj->rowid,
            'ref' => $obj->ref,
            'title' => $obj->title,
            'client_name' => $obj->client_name,
            'label' => $obj->ref . ' - ' . $obj->title . ($obj->client_name ? ' (' . $obj->client_name . ')' : '')
        ];
    }
}

ob_clean();

echo json_encode([
    'success' => true,
    'count' => count($projets),
    'projets' => $projets
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

ob_end_flush();

$db->close();
