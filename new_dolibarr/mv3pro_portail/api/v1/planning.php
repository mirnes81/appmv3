<?php
/**
 * GET /api/v1/planning.php
 *
 * Retourne les événements du planning
 *
 * Paramètres:
 * - from: Date début (YYYY-MM-DD) - défaut: aujourd'hui
 * - to: Date fin (YYYY-MM-DD) - défaut: aujourd'hui
 */

require_once __DIR__ . '/_bootstrap.php';

global $db;

// Méthode GET uniquement
require_method('GET');

// Authentification obligatoire
$auth = require_auth(true);

// Récupérer les paramètres
$from = get_param('from', date('Y-m-d'));
$to = get_param('to', date('Y-m-d'));

// Validation des dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    json_error('Format de date invalide. Utiliser YYYY-MM-DD', 'INVALID_DATE_FORMAT', 400);
}

// Récupérer l'ID utilisateur Dolibarr
$user_id = $auth['user_id'];

// Si compte unlinked, retourner planning vide pour l'instant
if (!$user_id) {
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$events = [];

// Requête SQL pour récupérer les événements
$sql = "SELECT DISTINCT a.id, a.label, a.datep, a.datep2, a.fulldayevent, a.location,
        a.note_private, a.percent,
        s.nom as client_nom, s.rowid as client_id,
        p.ref as projet_ref, p.title as projet_title, p.rowid as projet_id
        FROM ".MAIN_DB_PREFIX."actioncomm a
        LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ac ON ac.id = a.fk_action
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = a.fk_soc
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = a.fk_project
        LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
        WHERE (a.fk_user_author = ".(int)$user_id."
               OR a.fk_user_action = ".(int)$user_id."
               OR a.fk_user_done = ".(int)$user_id."
               OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$user_id."))
        AND a.entity = ".(isset($conf->entity) ? (int)$conf->entity : 1)
        AND (ac.code IN ('AC_POS', 'AC_plan') OR ac.code IS NULL)
        AND (
            (a.datep2 IS NOT NULL AND DATE(a.datep) <= '".$db->escape($to)."' AND DATE(a.datep2) >= '".$db->escape($from)."')
            OR (a.datep2 IS NULL AND DATE(a.datep) >= '".$db->escape($from)."' AND DATE(a.datep) <= '".$db->escape($to)."')
        )
        ORDER BY a.datep ASC";

$resql = $db->query($sql);

if (!$resql) {
    json_error('Erreur lors de la récupération du planning', 'DATABASE_ERROR', 500);
}

while ($obj = $db->fetch_object($resql)) {
    $event = [
        'id' => (int)$obj->id,
        'label' => $obj->label,
        'datep' => $obj->datep, // Frontend attend 'datep'
        'datef' => $obj->datep2 ?: null,
        'date_start' => $obj->datep, // Alias
        'date_end' => $obj->datep2 ?: $obj->datep, // Alias
        'client_nom' => $obj->client_nom, // Frontend attend 'client_nom'
        'client' => $obj->client_nom, // Alias
        'client_id' => $obj->client_id ? (int)$obj->client_id : null,
        'projet' => $obj->projet_title ? ($obj->projet_ref ? $obj->projet_ref.' - ' : '').$obj->projet_title : null,
        'projet_id' => $obj->projet_id ? (int)$obj->projet_id : null,
        'projet_ref' => $obj->projet_ref,
        'location' => $obj->location,
        'type' => 'actioncomm',
        'status' => $obj->percent == 100 ? 'done' : 'pending',
        'fullday' => (bool)$obj->fulldayevent,
        'percent' => (int)$obj->percent,
        'notes' => $obj->note_private
    ];

    $events[] = $event;
}

// Retourner directement le tableau d'événements (sans wrapper)
// Le frontend attend PlanningEvent[] directement
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
