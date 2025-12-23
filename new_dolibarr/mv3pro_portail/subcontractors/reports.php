<?php
require '../../../main.inc.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

if (!$id) {
    header('Location: list.php');
    exit;
}

$sql = "SELECT firstname, lastname, ref FROM ".MAIN_DB_PREFIX."mv3_subcontractors";
$sql .= " WHERE rowid = ".(int)$id;
$sql .= " AND entity = ".$conf->entity;

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) {
    header('Location: list.php');
    exit;
}

$subcontractor = $db->fetch_object($resql);

if ($action === 'validate') {
    $report_id = GETPOST('report_id', 'int');

    $sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_subcontractor_reports";
    $sql_update .= " SET status = 2, validated_by = ".$user->id.", validation_date = NOW()";
    $sql_update .= " WHERE rowid = ".(int)$report_id;
    $sql_update .= " AND fk_subcontractor = ".(int)$id;

    if ($db->query($sql_update)) {
        setEventMessages('Rapport validé avec succès', null, 'mesgs');
    } else {
        setEventMessages('Erreur lors de la validation', null, 'errors');
    }

    header('Location: reports.php?id='.$id);
    exit;
}

llxHeader('', 'Rapports - '.$subcontractor->firstname.' '.$subcontractor->lastname);

print load_fiche_titre(
    'Rapports de '.$subcontractor->firstname.' '.$subcontractor->lastname,
    '<a class="butAction" href="list.php">Retour à la liste</a>',
    'user'
);

print '<div class="fichecenter">';

$sql_stats = "SELECT";
$sql_stats .= " COUNT(*) as total_reports,";
$sql_stats .= " SUM(surface_m2) as total_m2,";
$sql_stats .= " SUM(hours_worked) as total_hours,";
$sql_stats .= " SUM(amount_calculated) as total_amount,";
$sql_stats .= " SUM(CASE WHEN status = 2 THEN amount_calculated ELSE 0 END) as validated_amount";
$sql_stats .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_reports";
$sql_stats .= " WHERE fk_subcontractor = ".(int)$id;
$sql_stats .= " AND MONTH(report_date) = MONTH(CURDATE())";
$sql_stats .= " AND YEAR(report_date) = YEAR(CURDATE())";

$resql_stats = $db->query($sql_stats);
$stats = $db->fetch_object($resql_stats);

print '<div class="div-table-responsive" style="margin-bottom: 20px;">';
print '<table class="tagtable liste noborder centpercent">';
print '<tr class="liste_titre"><th colspan="5">Statistiques du mois</th></tr>';
print '<tr class="oddeven">';
print '<td>Rapports: <strong>'.$stats->total_reports.'</strong></td>';
print '<td>Surface: <strong>'.number_format($stats->total_m2, 2).' m²</strong></td>';
print '<td>Heures: <strong>'.number_format($stats->total_hours, 2).' h</strong></td>';
print '<td>Montant total: <strong>'.price($stats->total_amount).' €</strong></td>';
print '<td>Validé: <strong>'.price($stats->validated_amount).' €</strong></td>';
print '</tr>';
print '</table>';
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste noborder centpercent">';

print '<tr class="liste_titre">';
print '<th>Référence</th>';
print '<th>Date</th>';
print '<th>Type de travail</th>';
print '<th class="right">Surface (m²)</th>';
print '<th class="right">Heures</th>';
print '<th class="right">Montant</th>';
print '<th>Statut</th>';
print '<th class="center">Photos</th>';
print '<th class="right">Actions</th>';
print '</tr>';

$sql_reports = "SELECT r.rowid, r.ref, r.report_date, r.work_type,";
$sql_reports .= " r.surface_m2, r.hours_worked, r.amount_calculated,";
$sql_reports .= " r.status, r.photo_count";
$sql_reports .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_reports as r";
$sql_reports .= " WHERE r.fk_subcontractor = ".(int)$id;
$sql_reports .= " ORDER BY r.report_date DESC, r.rowid DESC";

$resql_reports = $db->query($sql_reports);

if ($resql_reports) {
    $num = $db->num_rows($resql_reports);
    $i = 0;

    while ($i < $num) {
        $obj = $db->fetch_object($resql_reports);

        print '<tr class="oddeven">';

        print '<td class="nowraponall">';
        print '<a href="report_view.php?id='.$obj->rowid.'">'.$obj->ref.'</a>';
        print '</td>';

        print '<td>';
        print dol_print_date($db->jdate($obj->report_date), 'day');
        print '</td>';

        print '<td>';
        print dol_escape_htmltag($obj->work_type);
        print '</td>';

        print '<td class="right">';
        print number_format($obj->surface_m2, 2);
        print '</td>';

        print '<td class="right">';
        print number_format($obj->hours_worked, 2);
        print '</td>';

        print '<td class="right">';
        print price($obj->amount_calculated);
        print '</td>';

        print '<td>';
        $status_badges = [
            0 => '<span class="badge badge-status1">Brouillon</span>',
            1 => '<span class="badge badge-status3">Soumis</span>',
            2 => '<span class="badge badge-status4">Validé</span>',
            3 => '<span class="badge badge-status6">Facturé</span>',
            9 => '<span class="badge badge-status9">Rejeté</span>'
        ];
        print $status_badges[$obj->status] ?? 'Inconnu';
        print '</td>';

        print '<td class="center">';
        print '<span class="badge">'.$obj->photo_count.' photos</span>';
        print '</td>';

        print '<td class="right nowraponall">';
        print '<a class="butAction" href="report_view.php?id='.$obj->rowid.'">Voir</a>';
        if ($obj->status == 1) {
            print '<a class="butAction" href="?id='.$id.'&action=validate&report_id='.$obj->rowid.'">Valider</a>';
        }
        print '</td>';

        print '</tr>';
        $i++;
    }

    if ($num === 0) {
        print '<tr><td colspan="9" class="opacitymedium center">Aucun rapport trouvé</td></tr>';
    }
}

print '</table>';
print '</div>';

print '</div>';

llxFooter();
$db->close();
