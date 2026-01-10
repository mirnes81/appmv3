<?php
/**
 * API Liste des projets (pour création rapport)
 * GET /api/v1/reports_projects.php?search=...
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

require_once '../../core/init.php';
require_once '../../lib/api.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auth
$user = mv3_check_auth();

// Paramètres
$search = GETPOST('search', 'alpha');
$limit = GETPOST('limit', 'int') ?: 50;

// Requête projets
$sql = "SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " WHERE p.entity IN (".getEntity('project').")";
$sql .= " AND p.fk_statut = 1"; // Seulement projets ouverts

if (!empty($search)) {
    $sql .= " AND (p.ref LIKE '%".$db->escape($search)."%'";
    $sql .= " OR p.title LIKE '%".$db->escape($search)."%')";
}

$sql .= " ORDER BY p.title ASC";
$sql .= $db->plimit($limit);

$resql = $db->query($sql);

if (!$resql) {
    mv3_json_error($db->lasterror(), 500, 'DB_ERROR');
}

$projects = array();
$num = $db->num_rows($resql);

for ($i = 0; $i < $num; $i++) {
    $obj = $db->fetch_object($resql);

    // Charger tiers si existe
    $thirdparty_name = '';
    if ($obj->fk_soc > 0) {
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $soc = new Societe($db);
        if ($soc->fetch($obj->fk_soc) > 0) {
            $thirdparty_name = $soc->name;
        }
    }

    $projects[] = array(
        'id' => (int)$obj->rowid,
        'ref' => $obj->ref,
        'title' => $obj->title,
        'thirdparty_name' => $thirdparty_name
    );
}

mv3_json_success($projects);
