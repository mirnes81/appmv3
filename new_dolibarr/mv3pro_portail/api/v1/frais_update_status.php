<?php
/**
 * API v1 - Frais - Mise à jour statut
 *
 * POST /api/v1/frais_update_status.php
 *
 * Met à jour le statut d'un ou plusieurs frais
 * Réservé aux managers/admin
 *
 * Body:
 * {
 *   "id": 123,              // ID unique
 *   // OU
 *   "ids": [123, 124, 125], // IDs multiples
 *   "statut": "reimbursed"  // to_reimburse, reimbursed, rejected
 * }
 */

require_once __DIR__.'/_bootstrap.php';

// Auth requise + droits validate
$auth = require_auth();
require_rights('validate', $auth);

// Méthode POST uniquement
require_method('POST');

// Récupérer body JSON
$body = get_json_body(true);
$ids = [];

if (isset($body['id'])) {
    $ids = [(int)$body['id']];
} elseif (isset($body['ids']) && is_array($body['ids'])) {
    $ids = array_map('intval', $body['ids']);
} else {
    json_error('Paramètre "id" ou "ids" requis', 'MISSING_PARAMETER', 400);
}

$statut = isset($body['statut']) ? trim($body['statut']) : '';
require_param($statut, 'statut');

// Valider statut
$statuts_valides = ['to_reimburse', 'reimbursed', 'rejected'];
if (!in_array($statut, $statuts_valides)) {
    json_error('Statut invalide. Valeurs autorisées: ' . implode(', ', $statuts_valides), 'INVALID_STATUS', 400);
}

// Vérifier si table existe
$sql_check = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."mv3_frais'";
$resql_check = $db->query($sql_check);

if (!$resql_check || $db->num_rows($resql_check) === 0) {
    json_error('La table des frais n\'existe pas', 'TABLE_NOT_FOUND', 404);
}

// Mettre à jour chaque frais
$updated = [];
$errors = [];

foreach ($ids as $frais_id) {
    if ($frais_id <= 0) {
        continue;
    }

    // Vérifier que le frais existe
    $sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_frais";
    $sql_check .= " WHERE rowid = ".(int)$frais_id;
    $sql_check .= " AND entity IN (".getEntity('project').")";

    $resql_check = $db->query($sql_check);

    if (!$resql_check || $db->num_rows($resql_check) === 0) {
        $errors[] = "Frais #$frais_id non trouvé";
        continue;
    }

    // Mise à jour
    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_frais SET";
    $sql .= " statut = '".$db->escape($statut)."'";

    // Si statut = reimbursed, ajouter date_remboursement
    if ($statut === 'reimbursed') {
        $sql .= ", date_remboursement = NOW()";
    }

    $sql .= " WHERE rowid = ".(int)$frais_id;

    $resql = $db->query($sql);

    if ($resql) {
        $updated[] = $frais_id;
    } else {
        $errors[] = "Erreur lors de la mise à jour du frais #$frais_id";
    }
}

// Réponse
$response = [
    'updated' => count($updated),
    'ids' => $updated,
    'statut' => $statut
];

if (!empty($errors)) {
    $response['errors'] = $errors;
}

if (count($updated) === 0) {
    json_error('Aucun frais n\'a pu être mis à jour', 'UPDATE_FAILED', 400);
}

json_ok($response);
