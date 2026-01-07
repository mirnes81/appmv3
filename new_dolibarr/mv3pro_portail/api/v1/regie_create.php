<?php
/**
 * API v1 - Régie - Création
 *
 * POST /api/v1/regie_create.php
 *
 * Crée un nouveau bon de régie
 *
 * Body:
 * {
 *   "project_id": 123,
 *   "date_regie": "2025-01-07",
 *   "location": "Chantier XYZ",
 *   "type": "travaux",
 *   "note_public": "Note visible client",
 *   "note_private": "Note interne",
 *   "lines": [
 *     {
 *       "type": "time",           // time, material, option
 *       "description": "Pose carrelage",
 *       "qty": 8,
 *       "unit_price": 45.00,
 *       "tva_tx": 20
 *     }
 *   ]
 * }
 */

require_once __DIR__.'/_bootstrap.php';
require_once __DIR__.'/../../regie/class/regie.class.php';

// Auth requise
$auth = require_auth();
require_rights('write', $auth);

// Méthode POST uniquement
require_method('POST');

// Récupérer body JSON
$body = get_json_body(true);
$project_id = isset($body['project_id']) ? (int)$body['project_id'] : 0;
require_param($project_id, 'project_id');

$date_regie = isset($body['date_regie']) ? trim($body['date_regie']) : date('Y-m-d');
$location = isset($body['location']) ? trim($body['location']) : '';
$type = isset($body['type']) ? trim($body['type']) : 'travaux';
$note_public = isset($body['note_public']) ? trim($body['note_public']) : '';
$note_private = isset($body['note_private']) ? trim($body['note_private']) : '';
$lines = isset($body['lines']) && is_array($body['lines']) ? $body['lines'] : [];

// Vérifier que le projet existe
$sql = "SELECT p.rowid, p.ref, p.fk_soc";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " WHERE p.rowid = ".(int)$project_id;
$sql .= " AND p.entity IN (".getEntity('project').")";

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Projet non trouvé', 'PROJECT_NOT_FOUND', 404);
}

$projet = $db->fetch_object($resql);

// Créer l'objet Regie
$regie = new Regie($db);

$regie->entity = $conf->entity;
$regie->fk_project = $project_id;
$regie->fk_soc = $projet->fk_soc;
$regie->fk_user_author = $auth['user_id'];
$regie->date_regie = strtotime($date_regie);
$regie->location_text = $location;
$regie->type_regie = $type;
$regie->note_public = $note_public;
$regie->note_private = $note_private;
$regie->status = Regie::STATUS_DRAFT;

// Si pas de classe Regie complète, créer en SQL direct
if (!method_exists($regie, 'create')) {
    // Fallback: créer en SQL direct
    $ref = generate_regie_ref();

    $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."mv3_regie";
    $sql_insert .= " (ref, entity, fk_project, fk_soc, fk_user_author, date_regie,";
    $sql_insert .= "  location_text, type_regie, status, note_public, note_private,";
    $sql_insert .= "  date_creation, date_modification)";
    $sql_insert .= " VALUES (";
    $sql_insert .= " '".$db->escape($ref)."',";
    $sql_insert .= " ".(int)$conf->entity.",";
    $sql_insert .= " ".(int)$project_id.",";
    $sql_insert .= " ".(int)$projet->fk_soc.",";
    $sql_insert .= " ".(int)$auth['user_id'].",";
    $sql_insert .= " '".$db->escape($date_regie)."',";
    $sql_insert .= " '".$db->escape($location)."',";
    $sql_insert .= " '".$db->escape($type)."',";
    $sql_insert .= " 0,";
    $sql_insert .= " '".$db->escape($note_public)."',";
    $sql_insert .= " '".$db->escape($note_private)."',";
    $sql_insert .= " NOW(), NOW()";
    $sql_insert .= ")";

    $resql_insert = $db->query($sql_insert);

    if (!$resql_insert) {
        json_error('Erreur lors de la création de la régie', 'CREATE_ERROR', 500);
    }

    $regie_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_regie");
} else {
    // Utiliser la classe
    $result = $regie->create($user, 0);

    if ($result < 0) {
        json_error('Erreur lors de la création: ' . $regie->error, 'CREATE_ERROR', 500);
    }

    $regie_id = $regie->id;
    $ref = $regie->ref;
}

// Créer les lignes si présentes
$total_ht = 0;
$total_tva = 0;

foreach ($lines as $line) {
    if (!isset($line['description']) || !isset($line['qty']) || !isset($line['unit_price'])) {
        continue;
    }

    $line_type = isset($line['type']) ? $line['type'] : 'time';
    $description = trim($line['description']);
    $qty = (float)$line['qty'];
    $unit_price = (float)$line['unit_price'];
    $tva_tx = isset($line['tva_tx']) ? (float)$line['tva_tx'] : 20;

    $total_line_ht = $qty * $unit_price;
    $total_line_tva = $total_line_ht * ($tva_tx / 100);

    $sql_line = "INSERT INTO ".MAIN_DB_PREFIX."mv3_regie_line";
    $sql_line .= " (fk_regie, type, description, qty, unit_price, tva_tx, total_ht, total_tva)";
    $sql_line .= " VALUES (";
    $sql_line .= " ".(int)$regie_id.",";
    $sql_line .= " '".$db->escape($line_type)."',";
    $sql_line .= " '".$db->escape($description)."',";
    $sql_line .= " ".(float)$qty.",";
    $sql_line .= " ".(float)$unit_price.",";
    $sql_line .= " ".(float)$tva_tx.",";
    $sql_line .= " ".(float)$total_line_ht.",";
    $sql_line .= " ".(float)$total_line_tva;
    $sql_line .= ")";

    $db->query($sql_line);

    $total_ht += $total_line_ht;
    $total_tva += $total_line_tva;
}

// Mettre à jour les totaux
$total_ttc = $total_ht + $total_tva;

$sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_regie SET";
$sql_update .= " total_ht = ".(float)$total_ht.",";
$sql_update .= " total_tva = ".(float)$total_tva.",";
$sql_update .= " total_ttc = ".(float)$total_ttc;
$sql_update .= " WHERE rowid = ".(int)$regie_id;

$db->query($sql_update);

// Réponse
json_ok([
    'regie' => [
        'id' => $regie_id,
        'ref' => $ref,
        'url' => '/custom/mv3pro_portail/mobile_app/regie/view.php?id=' . $regie_id,
        'total_ht' => round($total_ht, 2),
        'total_ttc' => round($total_ttc, 2)
    ]
], 201);

/**
 * Génère une référence unique pour la régie
 */
function generate_regie_ref() {
    global $db;

    $year = date('Y');
    $month = date('m');

    // Compter les régies du mois
    $sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mv3_regie";
    $sql .= " WHERE YEAR(date_regie) = ".(int)$year;
    $sql .= " AND MONTH(date_regie) = ".(int)$month;

    $resql = $db->query($sql);
    $nb = 1;

    if ($resql) {
        $obj = $db->fetch_object($resql);
        $nb = (int)$obj->nb + 1;
    }

    return 'REG' . $year . $month . str_pad($nb, 4, '0', STR_PAD_LEFT);
}
