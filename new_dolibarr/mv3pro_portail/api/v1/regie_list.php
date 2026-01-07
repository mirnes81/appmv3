<?php
/**
 * API v1 - Régie - Liste
 *
 * GET /api/v1/regie_list.php?limit=50&page=1&status=
 *
 * Liste des bons de régie
 */

require_once __DIR__.'/_bootstrap.php';

// Auth requise
$auth = require_auth();

// Méthode GET uniquement
require_method('GET');

// Paramètres
$limit = (int)get_param('limit', 50);
$page = (int)get_param('page', 1);
$status = get_param('status', '');
$project_id = (int)get_param('project_id', 0);

if ($limit > 100) $limit = 100;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// Construction de la requête
$sql = "SELECT r.rowid, r.ref, r.entity, r.fk_project, r.fk_soc, r.fk_user_author,";
$sql .= " r.date_regie, r.date_creation, r.location_text, r.type_regie, r.status,";
$sql .= " r.total_ht, r.total_tva, r.total_ttc,";
$sql .= " p.ref as projet_ref, p.title as projet_title,";
$sql .= " s.nom as client_nom,";
$sql .= " u.lastname, u.firstname";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_project";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = r.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user_author";
$sql .= " WHERE r.entity IN (".getEntity('project').")";

// Filtre statut
if ($status !== '') {
    $sql .= " AND r.status = ".(int)$status;
}

// Filtre projet
if ($project_id > 0) {
    $sql .= " AND r.fk_project = ".(int)$project_id;
}

// Ordre et pagination
$sql .= " ORDER BY r.date_regie DESC, r.rowid DESC";
$sql .= " LIMIT ".(int)$limit." OFFSET ".(int)$offset;

$resql = $db->query($sql);

if (!$resql) {
    json_error('Erreur lors de la récupération des régies', 'DATABASE_ERROR', 500);
}

$regie_list = [];

while ($regie = $db->fetch_object($resql)) {
    $item = [
        'id' => $regie->rowid,
        'ref' => $regie->ref,
        'date_regie' => $regie->date_regie,
        'location' => $regie->location_text,
        'type' => $regie->type_regie,
        'status' => (int)$regie->status,
        'status_label' => getRegieStatusLabel((int)$regie->status),
        'total_ht' => (float)$regie->total_ht,
        'total_tva' => (float)$regie->total_tva,
        'total_ttc' => (float)$regie->total_ttc,
        'projet' => $regie->fk_project ? [
            'id' => $regie->fk_project,
            'ref' => $regie->projet_ref,
            'title' => $regie->projet_title
        ] : null,
        'client' => $regie->fk_soc ? [
            'id' => $regie->fk_soc,
            'nom' => $regie->client_nom
        ] : null,
        'auteur' => [
            'id' => $regie->fk_user_author,
            'nom' => trim($regie->firstname . ' ' . $regie->lastname)
        ],
        'url' => '/custom/mv3pro_portail/mobile_app/regie/view.php?id=' . $regie->rowid
    ];

    $regie_list[] = $item;
}

// Compter le total
$sql_count = "SELECT COUNT(rowid) as total";
$sql_count .= " FROM ".MAIN_DB_PREFIX."mv3_regie as r";
$sql_count .= " WHERE r.entity IN (".getEntity('project').")";

if ($status !== '') {
    $sql_count .= " AND r.status = ".(int)$status;
}
if ($project_id > 0) {
    $sql_count .= " AND r.fk_project = ".(int)$project_id;
}

$resql_count = $db->query($sql_count);
$total = 0;

if ($resql_count) {
    $obj = $db->fetch_object($resql_count);
    $total = (int)$obj->total;
}

// Réponse
json_ok([
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'pages' => ceil($total / $limit),
    'regie' => $regie_list
]);

/**
 * Helper pour label statut
 */
function getRegieStatusLabel($status) {
    $labels = [
        0 => 'Brouillon',
        1 => 'Validé',
        2 => 'Envoyé',
        3 => 'Signé',
        4 => 'Facturé',
        9 => 'Annulé'
    ];
    return $labels[$status] ?? 'Inconnu';
}
