<?php
/**
 * API v1 - Régie - Liste
 * GET /api/v1/regie.php
 *
 * Retourne la liste des bons de régie
 * Filtre automatiquement par utilisateur connecté selon son rôle
 *
 * Paramètres GET optionnels:
 *   - search_status: Filtrer par statut (0=Brouillon, 1=Validé, 2=Envoyé, 3=Signé, 4=Facturé)
 *   - date_from: Date début (format YYYY-MM-DD)
 *   - date_to: Date fin (format YYYY-MM-DD)
 *   - search: Recherche texte (ref, projet, lieu)
 *   - limit: Nombre max de résultats (défaut: 50, max: 200)
 *   - offset: Décalage pour pagination (défaut: 0)
 *
 * Retourne:
 *   {
 *     "success": true,
 *     "regies": [...],
 *     "total": 42,
 *     "limit": 50,
 *     "offset": 0
 *   }
 */

require_once __DIR__.'/_bootstrap.php';
require_once __DIR__ . '/../../core/init.php';

require_method('GET');
$auth = require_auth();

// Récupérer ID Dolibarr et statut admin via fonctions centralisées
$dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
$is_admin = mv3_is_admin($auth);

log_debug("Regie list endpoint", [
    'dolibarr_user_id' => $dolibarr_user_id,
    'is_admin' => $is_admin
]);

// Paramètres de filtrage
$search_status = get_param('search_status', '', 'GET');
$date_from = get_param('date_from', '', 'GET');
$date_to = get_param('date_to', '', 'GET');
$search = get_param('search', '', 'GET');
$limit = min((int)get_param('limit', 50, 'GET'), 200);
$offset = (int)get_param('offset', 0, 'GET');

// Si pas lié à un utilisateur Dolibarr et pas admin, retour vide
if ($dolibarr_user_id === 0 && !$is_admin) {
    json_ok([
        'regies' => [],
        'total' => 0,
        'limit' => $limit,
        'offset' => $offset,
        'reason' => 'account_unlinked'
    ]);
}

log_debug("Filters", [
    'is_admin' => $is_admin,
    'dolibarr_user_id' => $dolibarr_user_id,
    'search_status' => $search_status,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
]);

// Construction de la requête SQL
$sql = "SELECT r.rowid, r.ref, r.entity, r.fk_project, r.fk_soc,";
$sql .= " r.fk_user_author, r.fk_user_valid, r.fk_facture,";
$sql .= " r.date_regie, r.date_creation, r.date_validation,";
$sql .= " r.date_envoi, r.date_signature,";
$sql .= " r.location_text, r.type_regie, r.status,";
$sql .= " r.total_ht, r.total_tva, r.total_ttc,";
$sql .= " r.note_public, r.note_private,";
$sql .= " p.ref as project_ref, p.title as project_title,";
$sql .= " s.nom as client_name,";
$sql .= " u_author.lastname as author_lastname, u_author.firstname as author_firstname,";
$sql .= " u_valid.lastname as valid_lastname, u_valid.firstname as valid_firstname";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON r.fk_project = p.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON r.fk_soc = s.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u_author ON r.fk_user_author = u_author.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u_valid ON r.fk_user_valid = u_valid.rowid";
$sql .= " WHERE r.entity = ".$conf->entity;

// Filtrer par utilisateur (admin voit tout, employé voit ses régies)
if (!$is_admin && $dolibarr_user_id > 0) {
    $sql .= " AND (r.fk_user_author = ".$dolibarr_user_id." OR r.fk_user_valid = ".$dolibarr_user_id.")";
}

// Filtrer par statut
if ($search_status !== '' && $search_status >= 0) {
    $sql .= " AND r.status = ".(int)$search_status;
}

// Filtrer par date
if ($date_from) {
    $sql .= " AND r.date_regie >= '".$db->escape($date_from)."'";
}
if ($date_to) {
    $sql .= " AND r.date_regie <= '".$db->escape($date_to)."'";
}

// Recherche texte
if ($search) {
    $search_escaped = $db->escape($search);
    $sql .= " AND (r.ref LIKE '%".$search_escaped."%'";
    $sql .= " OR p.ref LIKE '%".$search_escaped."%'";
    $sql .= " OR p.title LIKE '%".$search_escaped."%'";
    $sql .= " OR r.location_text LIKE '%".$search_escaped."%')";
}

// Compter le total avant pagination
$sql_count = "SELECT COUNT(*) as total FROM (".str_replace("SELECT r.rowid,", "SELECT r.rowid", $sql).") as count_query";
$resql_count = $db->query($sql_count);
$total = 0;
if ($resql_count) {
    $obj_count = $db->fetch_object($resql_count);
    $total = (int)$obj_count->total;
}

// Ordre et pagination
$sql .= " ORDER BY r.date_regie DESC, r.rowid DESC";
$sql .= " LIMIT ".$limit." OFFSET ".$offset;

log_debug("Executing SQL query", ['sql' => $sql]);

$resql = $db->query($sql);

if (!$resql) {
    log_debug("SQL Error: ".$db->lasterror());
    json_error('Erreur lors de la récupération des régies', 'SQL_ERROR', 500, [
        'sql_error' => $db->lasterror()
    ]);
}

$regies = [];

while ($obj = $db->fetch_object($resql)) {
    $regies[] = [
        'id' => (int)$obj->rowid,
        'ref' => $obj->ref,
        'status' => (int)$obj->status,
        'status_label' => getRegieStatusLabel($obj->status),
        'date_regie' => $obj->date_regie,
        'date_creation' => $obj->date_creation,
        'date_validation' => $obj->date_validation,
        'date_envoi' => $obj->date_envoi,
        'date_signature' => $obj->date_signature,
        'project' => [
            'id' => (int)$obj->fk_project,
            'ref' => $obj->project_ref,
            'title' => $obj->project_title,
        ],
        'client' => [
            'id' => (int)$obj->fk_soc,
            'name' => $obj->client_name,
        ],
        'author' => [
            'id' => (int)$obj->fk_user_author,
            'name' => trim($obj->author_firstname.' '.$obj->author_lastname),
        ],
        'validator' => $obj->fk_user_valid ? [
            'id' => (int)$obj->fk_user_valid,
            'name' => trim($obj->valid_firstname.' '.$obj->valid_lastname),
        ] : null,
        'location_text' => $obj->location_text,
        'type_regie' => $obj->type_regie,
        'total_ht' => (float)$obj->total_ht,
        'total_tva' => (float)$obj->total_tva,
        'total_ttc' => (float)$obj->total_ttc,
        'note_public' => $obj->note_public,
        'fk_facture' => (int)$obj->fk_facture,
    ];
}

log_debug("Regies retrieved", ['count' => count($regies), 'total' => $total]);

json_ok([
    'regies' => $regies,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset,
]);

/**
 * Helper pour obtenir le label du statut
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
