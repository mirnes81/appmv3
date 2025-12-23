<?php
/**
 * API: Récupérer les projets d'un client
 */

// Session via Dolibarr
// Check via Dolibarr session

$res = 0;
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";
if (!$res && file_exists("../../../../../../main.inc.php")) $res = @include "../../../../../../main.inc.php";

header('Content-Type: application/json');

$client_id = GETPOST('client_id', 'int');

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT rowid, ref, title
        FROM ".MAIN_DB_PREFIX."projet
        WHERE fk_soc = ".(int)$client_id."
        AND entity IN (0,".$conf->entity.")
        ORDER BY ref DESC
        LIMIT 50";

$resql = $db->query($sql);
$projets = [];

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $projets[] = [
            'rowid' => $obj->rowid,
            'ref' => $obj->ref,
            'title' => $obj->title
        ];
    }
}

echo json_encode($projets);
$db->close();
?>
