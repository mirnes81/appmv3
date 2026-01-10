<?php
/**
 * API: Récupérer les projets d'un client
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
setupApiHeaders();

global $db, $conf;

$client_id = GETPOST('client_id', 'int');

if (!$client_id) {
    jsonResponse([]);
}

$sql = "SELECT rowid, ref, title
        FROM ".MAIN_DB_PREFIX."projet
        WHERE fk_soc = ".(int)$client_id."
        AND entity IN (0,".$conf->entity.")
        ORDER BY ref DESC
        LIMIT 50";

$projets = executeQuery($db, $sql);

if ($projets === false) {
    jsonError('Erreur lors de la récupération des projets', 500);
}

$result = [];
foreach ($projets as $obj) {
    $result[] = [
        'rowid' => $obj->rowid,
        'ref' => $obj->ref,
        'title' => $obj->title
    ];
}

jsonResponse($result);
?>
