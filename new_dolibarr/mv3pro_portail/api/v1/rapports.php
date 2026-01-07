<?php
/**
 * GET /api/v1/rapports.php
 *
 * Liste des rapports journaliers
 *
 * Paramètres:
 * - limit: Nombre de résultats (défaut: 20, max: 100)
 * - page: Page (défaut: 1)
 * - user_id: Filtrer par utilisateur (optionnel, admin uniquement)
 * - date_from: Date début (YYYY-MM-DD) (optionnel)
 * - date_to: Date fin (YYYY-MM-DD) (optionnel)
 */

require_once __DIR__ . '/_bootstrap.php';

global $db;

// Méthode GET uniquement
require_method('GET');

// Authentification obligatoire
$auth = require_auth(true);

// Récupérer les paramètres
$limit = (int)get_param('limit', 20);
$page = (int)get_param('page', 1);
$filter_user_id = get_param('user_id', null);
$date_from = get_param('date_from', null);
$date_to = get_param('date_to', null);

// Validation
if ($limit < 1) $limit = 20;
if ($limit > 100) $limit = 100;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// Construction de la requête
$where = [];
$where[] = "r.entity = ".(int)$conf->entity;

// Filtrer par utilisateur (sauf si admin)
if ($filter_user_id && !empty($auth['dolibarr_user']->admin)) {
    $where[] = "r.fk_user = ".(int)$filter_user_id;
} else {
    // Voir seulement ses propres rapports
    if ($auth['user_id']) {
        $where[] = "r.fk_user = ".(int)$auth['user_id'];
    }
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
        r.surface_total, r.fk_projet, r.fk_soc, r.zones, r.format, r.type_carrelage,
        r.travaux_realises, r.observations, r.statut,
        p.ref as projet_ref, p.title as projet_title,
        s.nom as client_nom,
        u.firstname, u.lastname,
        (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE fk_rapport = r.rowid) as nb_photos
        FROM ".MAIN_DB_PREFIX."mv3_rapport r
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = r.fk_projet
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = r.fk_soc
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = r.fk_user
        WHERE ".$where_clause."
        ORDER BY r.date_rapport DESC, r.rowid DESC
        LIMIT ".(int)$limit." OFFSET ".(int)$offset;

$resql = $db->query($sql);

if (!$resql) {
    json_error('Erreur lors de la récupération des rapports', 'DATABASE_ERROR', 500);
}

$rapports = [];

while ($obj = $db->fetch_object($resql)) {
    // Calculer les heures travaillées
    $heures = 0;
    if ($obj->heure_debut && $obj->heure_fin) {
        $start = strtotime($obj->heure_debut);
        $end = strtotime($obj->heure_fin);
        if ($end > $start) {
            $heures = round(($end - $start) / 3600, 2);
        }
    }

    $rapport = [
        'id' => (int)$obj->rowid,
        'ref' => $obj->ref,
        'date' => $obj->date_rapport,
        'heure_debut' => substr($obj->heure_debut, 0, 5),
        'heure_fin' => substr($obj->heure_fin, 0, 5),
        'heures' => $heures,
        'projet_id' => $obj->fk_projet ? (int)$obj->fk_projet : null,
        'projet_ref' => $obj->projet_ref,
        'projet_title' => $obj->projet_title,
        'client' => $obj->client_nom,
        'zones' => $obj->zones,
        'surface' => (float)$obj->surface_total,
        'format' => $obj->format,
        'type_carrelage' => $obj->type_carrelage,
        'travaux' => $obj->travaux_realises,
        'observations' => $obj->observations,
        'statut' => $obj->statut,
        'user' => trim($obj->firstname . ' ' . $obj->lastname),
        'has_photos' => (int)$obj->nb_photos > 0,
        'nb_photos' => (int)$obj->nb_photos,
        'url' => '/custom/mv3pro_portail/mobile_app/rapports/view.php?id='.$obj->rowid
    ];

    $rapports[] = $rapport;
}

json_ok([
    'rapports' => $rapports,
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'pages' => ceil($total / $limit)
]);
