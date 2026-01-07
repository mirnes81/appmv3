<?php
/**
 * API v1 - Frais - Export CSV
 *
 * GET /api/v1/frais_export_csv.php?month=YYYY-MM
 *
 * Exporte les frais en format CSV
 * Réservé aux managers/admin
 */

require_once __DIR__.'/_bootstrap.php';

// Auth requise + droits validate
$auth = require_auth();
require_rights('validate', $auth);

// Méthode GET uniquement
require_method('GET');

// Paramètres
$month = get_param('month', date('Y-m')); // YYYY-MM

// Valider format mois
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    json_error('Format de mois invalide. Utiliser YYYY-MM', 'INVALID_MONTH', 400);
}

// Vérifier si table existe
$sql_check = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."mv3_frais'";
$resql_check = $db->query($sql_check);

if (!$resql_check || $db->num_rows($resql_check) === 0) {
    json_error('La table des frais n\'existe pas', 'TABLE_NOT_FOUND', 404);
}

// Récupérer les frais
$sql = "SELECT f.*,
        u.lastname, u.firstname, u.login,
        r.date_rapport,
        p.ref as projet_ref, p.title as projet_title";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_frais as f";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = f.fk_user";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."mv3_rapport as r ON r.rowid = f.fk_rapport";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_projet";
$sql .= " WHERE 1=1";
$sql .= " AND f.entity IN (".getEntity('project').")";
$sql .= " AND DATE_FORMAT(f.date_frais, '%Y-%m') = '".$db->escape($month)."'";
$sql .= " ORDER BY f.date_frais ASC, u.lastname ASC";

$resql = $db->query($sql);

if (!$resql) {
    json_error('Erreur lors de la récupération des frais', 'DATABASE_ERROR', 500);
}

// Headers pour téléchargement CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="frais_' . $month . '.csv"');

// BOM UTF-8 pour Excel
echo "\xEF\xBB\xBF";

// En-têtes CSV
$headers = [
    'ID',
    'Date',
    'Employé',
    'Type',
    'Montant',
    'Mode paiement',
    'Description',
    'Projet',
    'Statut',
    'Date remboursement'
];

// Écrire en-têtes
$output = fopen('php://output', 'w');
fputcsv($output, $headers, ';');

// Écrire les données
$total = 0;

while ($frais = $db->fetch_object($resql)) {
    $row = [
        $frais->rowid,
        $frais->date_frais,
        trim($frais->firstname . ' ' . $frais->lastname),
        $frais->type,
        number_format((float)$frais->montant, 2, ',', ''),
        $frais->mode_paiement,
        $frais->description,
        $frais->projet_ref ? $frais->projet_ref . ' - ' . $frais->projet_title : '',
        $frais->statut,
        $frais->date_remboursement ? $frais->date_remboursement : ''
    ];

    fputcsv($output, $row, ';');

    $total += (float)$frais->montant;
}

// Ligne de total
fputcsv($output, ['', '', '', 'TOTAL', number_format($total, 2, ',', '')], ';');

fclose($output);
exit;
