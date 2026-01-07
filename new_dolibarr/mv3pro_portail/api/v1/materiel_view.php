<?php
/**
 * API v1 - Matériel - Détail
 * GET /api/v1/materiel_view.php?id=123
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$id = (int)get_param('id', 0);
require_param($id, 'id');

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_materiel WHERE rowid = ".$id;
$sql .= " AND entity IN (".getEntity('product').")";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) json_error('Non trouvé', 'NOT_FOUND', 404);

$obj = $db->fetch_object($resql);
json_ok(['materiel' => [
    'id' => $obj->rowid,
    'nom' => $obj->nom,
    'reference' => $obj->reference,
    'type' => $obj->type,
    'statut' => $obj->statut,
    'quantite' => (int)$obj->quantite,
    'description' => $obj->description
]]);
