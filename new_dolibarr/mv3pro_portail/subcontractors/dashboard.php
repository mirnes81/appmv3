<?php
require '../../../main.inc.php';

llxHeader('', 'Tableau de Bord Sous-Traitants');

print load_fiche_titre('Tableau de Bord Sous-Traitants', '', 'user');

print '<div class="fichecenter">';

$sql_today = "SELECT s.firstname, s.lastname, s.ref,";
$sql_today .= " r.rowid as report_id, r.ref as report_ref, r.surface_m2, r.amount_calculated, r.status";
$sql_today .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractors as s";
$sql_today .= " LEFT JOIN ".MAIN_DB_PREFIX."mv3_subcontractor_reports as r";
$sql_today .= " ON r.fk_subcontractor = s.rowid AND r.report_date = CURDATE()";
$sql_today .= " WHERE s.active = 1";
$sql_today .= " AND s.entity = ".$conf->entity;
$sql_today .= " ORDER BY s.lastname ASC";

$resql_today = $db->query($sql_today);

print '<div class="div-table-responsive" style="margin-bottom: 30px;">';
print '<table class="tagtable liste noborder centpercent">';
print '<tr class="liste_titre"><th colspan="5">🟢 Activité Aujourd\'hui</th></tr>';
print '<tr class="liste_titre">';
print '<th>Sous-Traitant</th>';
print '<th>Rapport</th>';
print '<th class="right">Surface (m²)</th>';
print '<th class="right">Montant</th>';
print '<th>Statut</th>';
print '</tr>';

if ($resql_today) {
    $num = $db->num_rows($resql_today);
    $i = 0;
    $has_reports = false;
    $no_reports = [];

    while ($i < $num) {
        $obj = $db->fetch_object($resql_today);

        if ($obj->report_id) {
            $has_reports = true;
            print '<tr class="oddeven">';
            print '<td><a href="reports.php?id='.$obj->ref.'">'.$obj->firstname.' '.$obj->lastname.'</a></td>';
            print '<td><a href="report_view.php?id='.$obj->report_id.'">'.$obj->report_ref.'</a></td>';
            print '<td class="right">'.number_format($obj->surface_m2, 2).'</td>';
            print '<td class="right">'.price($obj->amount_calculated).' €</td>';

            $status_badges = [
                0 => '<span class="badge badge-status1">Brouillon</span>',
                1 => '<span class="badge badge-status3">Soumis</span>',
                2 => '<span class="badge badge-status4">Validé</span>'
            ];
            print '<td>'.$status_badges[$obj->status].'</td>';
            print '</tr>';
        } else {
            $no_reports[] = $obj;
        }
        $i++;
    }

    if (!$has_reports && empty($no_reports)) {
        print '<tr><td colspan="5" class="center opacitymedium">Aucune activité aujourd\'hui</td></tr>';
    }

    if (!empty($no_reports)) {
        print '<tr class="liste_titre"><td colspan="5" style="background-color: #fef3c7;">⚠️ Sans rapport aujourd\'hui</td></tr>';
        foreach ($no_reports as $obj) {
            print '<tr class="oddeven" style="background-color: #fffbeb;">';
            print '<td>'.$obj->firstname.' '.$obj->lastname.'</td>';
            print '<td colspan="4"><span class="opacitymedium">Aucun rapport soumis</span></td>';
            print '</tr>';
        }
    }
}

print '</table>';
print '</div>';

$sql_month = "SELECT";
$sql_month .= " COUNT(DISTINCT r.fk_subcontractor) as active_subcontractors,";
$sql_month .= " COUNT(r.rowid) as total_reports,";
$sql_month .= " SUM(r.surface_m2) as total_m2,";
$sql_month .= " SUM(r.amount_calculated) as total_amount,";
$sql_month .= " SUM(CASE WHEN r.status = 2 THEN r.amount_calculated ELSE 0 END) as validated_amount,";
$sql_month .= " SUM(CASE WHEN r.status = 1 THEN 1 ELSE 0 END) as pending_validation";
$sql_month .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractor_reports as r";
$sql_month .= " WHERE MONTH(r.report_date) = MONTH(CURDATE())";
$sql_month .= " AND YEAR(r.report_date) = YEAR(CURDATE())";
$sql_month .= " AND r.entity = ".$conf->entity;

$resql_month = $db->query($sql_month);
$stats = $db->fetch_object($resql_month);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste noborder centpercent">';
print '<tr class="liste_titre"><th colspan="6">📊 Statistiques du Mois</th></tr>';
print '<tr class="oddeven">';
print '<td><strong>Sous-traitants actifs:</strong><br/>'.$stats->active_subcontractors.'</td>';
print '<td><strong>Total rapports:</strong><br/>'.$stats->total_reports.'</td>';
print '<td><strong>Surface totale:</strong><br/>'.number_format($stats->total_m2, 2).' m²</td>';
print '<td><strong>Montant total:</strong><br/>'.price($stats->total_amount).' €</td>';
print '<td><strong>Montant validé:</strong><br/>'.price($stats->validated_amount).' €</td>';
print '<td style="background-color: #fef3c7;"><strong>En attente validation:</strong><br/>'.$stats->pending_validation.'</td>';
print '</tr>';
print '</table>';
print '</div>';

if ($stats->pending_validation > 0) {
    print '<div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">';
    print '<strong>⚠️ Attention:</strong> '.$stats->pending_validation.' rapport(s) en attente de validation';
    print '</div>';
}

$sql_top = "SELECT s.firstname, s.lastname, s.ref,";
$sql_top .= " COUNT(r.rowid) as report_count,";
$sql_top .= " SUM(r.surface_m2) as total_m2,";
$sql_top .= " SUM(r.amount_calculated) as total_amount";
$sql_top .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractors as s";
$sql_top .= " INNER JOIN ".MAIN_DB_PREFIX."mv3_subcontractor_reports as r ON r.fk_subcontractor = s.rowid";
$sql_top .= " WHERE MONTH(r.report_date) = MONTH(CURDATE())";
$sql_top .= " AND YEAR(r.report_date) = YEAR(CURDATE())";
$sql_top .= " AND s.entity = ".$conf->entity;
$sql_top .= " GROUP BY s.rowid";
$sql_top .= " ORDER BY total_m2 DESC";
$sql_top .= " LIMIT 5";

$resql_top = $db->query($sql_top);

print '<div class="div-table-responsive" style="margin-top: 30px;">';
print '<table class="tagtable liste noborder centpercent">';
print '<tr class="liste_titre"><th colspan="4">🏆 Top Performeurs du Mois</th></tr>';
print '<tr class="liste_titre">';
print '<th>Sous-Traitant</th>';
print '<th class="right">Rapports</th>';
print '<th class="right">Surface (m²)</th>';
print '<th class="right">Montant</th>';
print '</tr>';

if ($resql_top) {
    $num = $db->num_rows($resql_top);
    if ($num > 0) {
        $i = 0;
        while ($i < $num) {
            $obj = $db->fetch_object($resql_top);
            print '<tr class="oddeven">';
            print '<td>';
            if ($i === 0) {
                print '🥇 ';
            }
            elseif ($i === 1) print '🥈 ';
            elseif ($i === 2) print '🥉 ';
            print $obj->firstname.' '.$obj->lastname;
            print '</td>';
            print '<td class="right">'.$obj->report_count.'</td>';
            print '<td class="right">'.number_format($obj->total_m2, 2).'</td>';
            print '<td class="right">'.price($obj->total_amount).' €</td>';
            print '</tr>';
            $i++;
        }
    } else {
        print '<tr><td colspan="4" class="center opacitymedium">Aucune donnée disponible</td></tr>';
    }
}

print '</table>';
print '</div>';

print '</div>';

print '<script>setTimeout(function(){ window.location.reload(); }, 60000);</script>';

llxFooter();
$db->close();
