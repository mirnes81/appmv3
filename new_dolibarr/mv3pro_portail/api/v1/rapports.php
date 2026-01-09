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

global $db, $conf;

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

// Vérifier si la table existe (retourne liste vide si absent)
mv3_check_table_or_empty($db, 'mv3_rapport', 'Rapports');

// Construction de la requête
$where = [];
// Filtrer par entité (défaut: 1 si non défini)
$entity = isset($conf->entity) ? (int)$conf->entity : 1;
$where[] = "r.entity = ".$entity;

// Filtrer par utilisateur (sauf si admin)
if ($filter_user_id && !empty($auth['dolibarr_user']->admin)) {
    $where[] = "r.fk_user = ".(int)$filter_user_id;
} else {
    // Voir seulement ses propres rapports
    if ($auth['user_id']) {
        $where[] = "r.fk_user = ".(int)$auth['user_id'];
    } elseif (!empty($auth['mobile_user_id'])) {
        // Si compte unlinked, filtrer par mobile_user_id via une table de correspondance
        // Pour l'instant, retourner une liste vide pour les comptes unlinked
        $where[] = "1 = 0"; // Pas de résultats pour comptes non liés
    } else {
        // Pas d'utilisateur identifié, retourner vide
        $where[] = "1 = 0";
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

    // Retourner un tableau vide plutôt qu'une erreur 500
    http_response_code(200);
    echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
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
        'rowid' => (int)$obj->rowid,
        'id' => (int)$obj->rowid, // Alias pour compatibilité
        'ref' => $obj->ref,
        'date_rapport' => $obj->date_rapport,
        'date' => $obj->date_rapport, // Alias pour compatibilité
        'heure_debut' => $obj->heure_debut ? substr($obj->heure_debut, 0, 5) : null,
        'heure_fin' => $obj->heure_fin ? substr($obj->heure_fin, 0, 5) : null,
        'heures' => $heures,
        'fk_user' => $obj->fk_user ? (int)$obj->fk_user : null,
        'projet_id' => $obj->fk_projet ? (int)$obj->fk_projet : null,
        'fk_projet' => $obj->fk_projet ? (int)$obj->fk_projet : null,
        'projet_ref' => $obj->projet_ref,
        'projet_nom' => $obj->projet_title, // Frontend attend projet_nom
        'projet_title' => $obj->projet_title,
        'client' => $obj->client_nom,
        'zones' => $obj->zones,
        'surface' => (float)$obj->surface_total,
        'format' => $obj->format,
        'type_carrelage' => $obj->type_carrelage,
        'travaux' => $obj->travaux_realises,
        'description' => $obj->travaux_realises, // Alias
        'observations' => $obj->observations,
        'statut' => $obj->statut,
        'user' => trim($obj->firstname . ' ' . $obj->lastname),
        'has_photos' => (int)$obj->nb_photos > 0,
        'nb_photos' => (int)$obj->nb_photos,
        'url' => '/custom/mv3pro_portail/mobile_app/rapports/view.php?id='.$obj->rowid
    ];

    $rapports[] = $rapport;
}

// Retourner directement le tableau de rapports (sans wrapper)
// Le frontend attend Rapport[] directement
// Note: headers déjà envoyés par _bootstrap.php
http_response_code(200);
echo json_encode($rapports, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
