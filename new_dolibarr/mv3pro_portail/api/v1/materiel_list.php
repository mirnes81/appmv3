<?php
/**
 * API v1 - Matériel - Liste
 * GET /api/v1/materiel_list.php
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_materiel WHERE entity IN (".getEntity('product').")";
$sql .= " ORDER BY nom ASC";

$resql = $db->query($sql);
if (!$resql) json_error('Erreur BDD', 'DATABASE_ERROR', 500);

$list = [];
while ($obj = $db->fetch_object($resql)) {
    $list[] = [
        'id' => $obj->rowid,
        'nom' => $obj->nom,
        'reference' => $obj->reference,
        'type' => $obj->type,
        'statut' => $obj->statut,
        'quantite' => (int)$obj->quantite
    ];
}

json_ok(['materiel' => $list, 'count' => count($list)]);
