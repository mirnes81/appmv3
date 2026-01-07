<?php
/**
 * API v1 - Frais - Liste
 *
 * GET /api/v1/frais_list.php?month=YYYY-MM&user_id=123
 *
 * Liste des frais pour un mois donné
 * - Workers: uniquement leurs propres frais
 * - Managers/Admin: tous les frais ou filtrés par user_id
 */

require_once __DIR__.'/_bootstrap.php';

// Auth requise
$auth = require_auth();

// Méthode GET uniquement
require_method('GET');

// Paramètres
$month = get_param('month', date('Y-m')); // YYYY-MM
$user_id = (int)get_param('user_id', 0);
$statut = get_param('statut', ''); // '', 'to_reimburse', 'reimbursed', 'rejected'

// Valider format mois
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    json_error('Format de mois invalide. Utiliser YYYY-MM', 'INVALID_MONTH', 400);
}

// Vérifier si table existe
$sql_check = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."mv3_frais'";
$resql_check = $db->query($sql_check);

if (!$resql_check || $db->num_rows($resql_check) === 0) {
    json_error('La table des frais n\'existe pas. Feature non activée.', 'TABLE_NOT_FOUND', 404);
}

// Construction de la requête
$sql = "SELECT f.*,
        u.lastname, u.firstname, u.login,
        r.date_rapport, r.fk_projet,
        p.ref as projet_ref, p.title as projet_title";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_frais as f";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = f.fk_user";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."mv3_rapport as r ON r.rowid = f.fk_rapport";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_projet";
$sql .= " WHERE 1=1";
$sql .= " AND f.entity IN (".getEntity('project').")";

// Filtre mois
$sql .= " AND DATE_FORMAT(f.date_frais, '%Y-%m') = '".$db->escape($month)."'";

// Filtre user_id
if ($user_id > 0) {
    // Vérifier droits: seuls les managers peuvent voir les frais d'autres users
    if ($user_id != $auth['user_id'] && empty($auth['rights']['validate'])) {
        json_error('Vous ne pouvez voir que vos propres frais', 'FORBIDDEN', 403);
    }
    $sql .= " AND f.fk_user = ".(int)$user_id;
} else {
    // Si pas de user_id spécifié
    if (!empty($auth['rights']['worker']) && empty($auth['rights']['validate'])) {
        // Worker: uniquement ses propres frais
        $sql .= " AND f.fk_user = ".(int)$auth['user_id'];
    }
    // Manager/Admin: tous les frais (pas de filtre)
}

// Filtre statut
if ($statut !== '') {
    $sql .= " AND f.statut = '".$db->escape($statut)."'";
}

// Ordre
$sql .= " ORDER BY f.date_frais DESC, f.rowid DESC";

$resql = $db->query($sql);

if (!$resql) {
    json_error('Erreur lors de la récupération des frais', 'DATABASE_ERROR', 500);
}

$frais_list = [];
$total = 0;
$total_by_status = [
    'to_reimburse' => 0,
    'reimbursed' => 0,
    'rejected' => 0
];

while ($frais = $db->fetch_object($resql)) {
    $item = [
        'id' => $frais->rowid,
        'type' => $frais->type,
        'montant' => (float)$frais->montant,
        'mode_paiement' => $frais->mode_paiement,
        'description' => $frais->description,
        'justificatif' => $frais->justificatif,
        'date_frais' => $frais->date_frais,
        'statut' => $frais->statut,
        'date_remboursement' => $frais->date_remboursement,
        'user' => [
            'id' => $frais->fk_user,
            'login' => $frais->login,
            'nom' => trim($frais->firstname . ' ' . $frais->lastname)
        ],
        'rapport' => $frais->fk_rapport ? [
            'id' => $frais->fk_rapport,
            'date' => $frais->date_rapport,
            'projet' => $frais->projet_ref ? [
                'id' => $frais->fk_projet,
                'ref' => $frais->projet_ref,
                'title' => $frais->projet_title
            ] : null
        ] : null
    ];

    $frais_list[] = $item;

    $total += (float)$frais->montant;

    if (isset($total_by_status[$frais->statut])) {
        $total_by_status[$frais->statut] += (float)$frais->montant;
    }
}

// Réponse
json_ok([
    'month' => $month,
    'count' => count($frais_list),
    'total' => round($total, 2),
    'total_by_status' => [
        'to_reimburse' => round($total_by_status['to_reimburse'], 2),
        'reimbursed' => round($total_by_status['reimbursed'], 2),
        'rejected' => round($total_by_status['rejected'], 2)
    ],
    'frais' => $frais_list
]);
