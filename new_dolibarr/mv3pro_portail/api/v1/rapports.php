<?php
/**
 * GET /api/v1/rapports.php
 *
 * Liste des rapports journaliers
 *
 * Paramètres:
 * - limit: Nombre de résultats (défaut: 20, max: 100)
 * - page: Page (défaut: 1)
 * - search: Recherche dans client_nom, ref, projet_ref (optionnel)
 * - statut: Filtrer par statut: all|brouillon|valide|soumis (défaut: all)
 * - from: Date début (YYYY-MM-DD) (optionnel)
 * - to: Date fin (YYYY-MM-DD) (optionnel)
 * - user_id: Filtrer par utilisateur (optionnel, admin uniquement)
 */

require_once __DIR__ . '/_bootstrap.php';

global $db, $conf;

// Méthode GET uniquement
require_method('GET');

// Authentification obligatoire
$auth = require_auth(true);

// Récupérer les paramètres
$limit = (int)get_param('limit', 20);
$page = (int)get_param('page', 1);
$search = get_param('search', '');
$statut = get_param('statut', 'all');
$date_from = get_param('from', get_param('date_from', null));
$date_to = get_param('to', get_param('date_to', null));
$filter_user_id = get_param('user_id', null);

// Validation
if ($limit < 1) $limit = 20;
if ($limit > 100) $limit = 100;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// Vérifier si la table existe (retourne liste vide si absent)
mv3_check_table_or_empty($db, 'mv3_rapport', 'Rapports');

// Construction de la requête
$where = [];
// Filtrer par entité (défaut: 1 si non défini)
$entity = isset($conf->entity) ? (int)$conf->entity : 1;
$where[] = "r.entity = ".$entity;

// Récupérer le vrai ID Dolibarr et le statut admin
$dolibarr_user_id = (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->id)) ? (int)$auth['dolibarr_user']->id : 0;
$is_admin = (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->admin));

// Filtrer par utilisateur selon le rôle
if ($is_admin) {
    // Admin : peut voir tous les rapports ou filtrer par employé
    if ($filter_user_id) {
        $where[] = "r.fk_user = ".(int)$filter_user_id;
    }
    // Sinon pas de filtre sur fk_user → voit tous les rapports de l'entité
} else {
    // Employé : voit uniquement ses propres rapports
    if ($dolibarr_user_id > 0) {
        $where[] = "r.fk_user = ".$dolibarr_user_id;
    } else {
        // Pas d'utilisateur Dolibarr lié, retourner vide
        $where[] = "1 = 0";
    }
}

// Filtrer par recherche
if (!empty($search)) {
    $search_escaped = $db->escape($search);
    $where[] = "(s.nom LIKE '%".$search_escaped."%' OR r.ref LIKE '%".$search_escaped."%' OR p.ref LIKE '%".$search_escaped."%')";
}

// Filtrer par statut
if ($statut !== 'all') {
    $statut_value = 0;
    if ($statut === 'valide') $statut_value = 1;
    elseif ($statut === 'soumis') $statut_value = 2;
    $where[] = "r.statut = ".(int)$statut_value;
}

// Filtrer par dates
if ($date_from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $where[] = "DATE(r.date_rapport) >= '".$db->escape($date_from)."'";
}
if ($date_to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $where[] = "DATE(r.date_rapport) <= '".$db->escape($date_to)."'";
}

$where_clause = implode(' AND ', $where);

// Compter le total
$sql_count = "SELECT COUNT(*) as total
              FROM ".MAIN_DB_PREFIX."mv3_rapport r
              WHERE ".$where_clause;

$resql_count = $db->query($sql_count);
$total = 0;

if ($resql_count) {
    $obj = $db->fetch_object($resql_count);
    $total = (int)$obj->total;
}

// Récupérer les rapports
$sql = "SELECT r.rowid, r.ref, r.date_rapport, r.heure_debut, r.heure_fin,
        r.surface_total, r.fk_projet, r.fk_soc, r.fk_user, r.zones, r.format, r.type_carrelage,
        r.travaux_realises, r.observations, r.statut,
        p.ref as projet_ref, p.title as projet_title,
        s.nom as client_nom,
        u.firstname, u.lastname,
        COUNT(DISTINCT rp.rowid) as nb_photos
        FROM ".MAIN_DB_PREFIX."mv3_rapport r
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = r.fk_projet
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = r.fk_soc
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = r.fk_user
        LEFT JOIN ".MAIN_DB_PREFIX."mv3_rapport_photo rp ON rp.fk_rapport = r.rowid
        WHERE ".$where_clause."
        GROUP BY r.rowid
        ORDER BY r.date_rapport DESC, r.rowid DESC
        LIMIT ".(int)$limit." OFFSET ".(int)$offset;

$resql = $db->query($sql);

if (!$resql) {
    $error_msg = 'Erreur lors de la récupération des rapports';
    $db_error = $db->lasterror();
    if ($db_error) {
        $error_msg .= ': ' . $db_error;
    }
    error_log('[MV3 Rapports] SQL Error: ' . $error_msg);
    error_log('[MV3 Rapports] SQL Query: ' . $sql);

    // Retourner format standard même en erreur avec items vide
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error_code' => 'DATABASE_ERROR',
        'message' => $error_msg,
        'data' => [
            'items' => [],
            'page' => $page,
            'limit' => $limit,
            'total' => 0,
            'total_pages' => 0,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rapports = [];

while ($obj = $db->fetch_object($resql)) {
    // Calculer les heures travaillées (temps_total)
    $temps_total = 0;
    if ($obj->heure_debut && $obj->heure_fin) {
        $start = strtotime($obj->heure_debut);
        $end = strtotime($obj->heure_fin);
        if ($end > $start) {
            $temps_total = round(($end - $start) / 3600, 2);
        }
    }

    // Statut en texte
    $statut_text = 'brouillon';
    if ($obj->statut == 1) $statut_text = 'valide';
    elseif ($obj->statut == 2) $statut_text = 'soumis';

    $rapport = [
        'rowid' => (int)$obj->rowid,
        'ref' => $obj->ref,
        'date_rapport' => $obj->date_rapport,
        'temps_total' => $temps_total,
        'statut' => (int)$obj->statut,
        'statut_text' => $statut_text,
        'client_nom' => $obj->client_nom,
        'projet_ref' => $obj->projet_ref,
        'projet_title' => $obj->projet_title,
        'nb_photos' => (int)$obj->nb_photos,
    ];

    $rapports[] = $rapport;
}

// Retourner avec format standard API v1 (enveloppé dans data)
json_ok([
    'data' => [
        'items' => $rapports,
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $limit > 0 ? ceil($total / $limit) : 0,
    ]
]);
