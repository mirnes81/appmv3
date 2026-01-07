<?php
/**
 * API v1 - Matériel - Action
 * POST /api/v1/materiel_action.php
 * Body: {"id": 123, "action": "reserve", "quantite": 2}
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_rights('write', $auth);
require_method('POST');

$body = get_json_body(true);
$id = isset($body['id']) ? (int)$body['id'] : 0;
$action = isset($body['action']) ? trim($body['action']) : '';
$quantite = isset($body['quantite']) ? (int)$body['quantite'] : 1;

require_param($id, 'id');
require_param($action, 'action');

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_materiel WHERE rowid = ".$id;
$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) json_error('Non trouvé', 'NOT_FOUND', 404);

if ($action === 'reserve') {
    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_materiel SET statut = 'reserve' WHERE rowid = ".$id;
} elseif ($action === 'disponible') {
    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_materiel SET statut = 'disponible' WHERE rowid = ".$id;
} else {
    json_error('Action invalide', 'INVALID_ACTION', 400);
}

$db->query($sql);
json_ok(['success' => true, 'action' => $action]);
