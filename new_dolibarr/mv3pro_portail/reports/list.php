<?php
/**
 * Page liste des rapports (backend Dolibarr)
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once __DIR__.'/../class/report.class.php';

// Droits
if (!$user->rights->mv3pro_portail->reports_create) {
    accessforbidden();
}

// Forcer le menu principal et sous-menu
$_GET['mainmenu'] = 'mv3pro';
$_GET['leftmenu'] = 'mv3pro_reports';

// Paramètres
$action = GETPOST('action', 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_project = GETPOST('search_project', 'int');
$search_status = GETPOST('search_status', 'int');
$limit = GETPOST('limit', 'int') ?: $conf->liste_limit;
$page = GETPOST('page', 'int');
if ($page < 0) $page = 0;
$offset = $limit * $page;

// Actions
if ($action == 'delete' && !empty($user->admin)) {
    $id = GETPOST('id', 'int');
    $report = new Report($db);
    if ($report->fetch($id) > 0) {
        $report->delete($user);
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// En-tête
llxHeader('', 'Rapports Chantier', '');

print load_fiche_titre('Rapports Chantier', '', 'fa-file-alt');

// Formulaire recherche
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<div class="fichecenter">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>Référence</td>';
print '<td>Projet</td>';
print '<td>Statut</td>';
print '<td></td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><input type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'" size="10"></td>';
print '<td><input type="text" name="search_project" value="'.dol_escape_htmltag($search_project).'" size="5"></td>';
print '<td>';
print '<select name="search_status">';
print '<option value="">Tous</option>';
print '<option value="0"'.($search_status === '0' ? ' selected' : '').'>Brouillon</option>';
print '<option value="1"'.($search_status === '1' ? ' selected' : '').'>Soumis</option>';
print '<option value="2"'.($search_status === '2' ? ' selected' : '').'>Validé</option>';
print '</select>';
print '</td>';
print '<td><button type="submit" class="button">Rechercher</button></td>';
print '</tr>';
print '</table>';
print '</div>';
print '</form>';

print '<br>';

// Bouton nouveau (redirige vers PWA)
$pwa_url = !empty($conf->global->MV3PRO_PWA_URL) ? $conf->global->MV3PRO_PWA_URL : '/custom/mv3pro_portail/pwa_dist/';
print '<div class="tabsAction">';
print '<a href="'.$pwa_url.'#/rapports/new" class="butAction" target="_blank">Nouveau Rapport (PWA)</a>';
print '</div>';

// Liste rapports
$sql = "SELECT r.rowid, r.ref, r.fk_project, r.fk_user_author, r.date_report, r.duration_minutes, r.status";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_report as r";
$sql .= " WHERE r.entity IN (".getEntity('mv3_report').")";

if (!empty($search_ref)) {
    $sql .= " AND r.ref LIKE '%".$db->escape($search_ref)."%'";
}

if ($search_project > 0) {
    $sql .= " AND r.fk_project = ".(int)$search_project;
}

if ($search_status !== '') {
    $sql .= " AND r.status = ".(int)$search_status;
}

if (empty($user->admin) && empty($user->rights->mv3pro_portail->reports_readall)) {
    $sql .= " AND r.fk_user_author = ".(int)$user->id;
}

$sql .= $db->order('r.date_report', 'DESC');
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Ref</th>';
    print '<th>Projet</th>';
    print '<th>Auteur</th>';
    print '<th>Date</th>';
    print '<th class="right">Durée (h)</th>';
    print '<th>Statut</th>';
    print '<th></th>';
    print '</tr>';

    $i = 0;
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        // Projet
        $project_label = '';
        if ($obj->fk_project > 0) {
            $project = new Project($db);
            if ($project->fetch($obj->fk_project) > 0) {
                $project_label = $project->ref.' - '.$project->title;
            }
        }

        // Auteur
        $author = new User($db);
        $author->fetch($obj->fk_user_author);

        // Statut
        $report_temp = new Report($db);
        $report_temp->status = $obj->status;
        $status_label = $report_temp->getLibStatut();

        print '<tr class="oddeven">';
        print '<td><strong>'.$obj->ref.'</strong></td>';
        print '<td>'.dol_escape_htmltag($project_label).'</td>';
        print '<td>'.$author->getFullName($langs).'</td>';
        print '<td>'.dol_print_date($db->jdate($obj->date_report), 'day').'</td>';
        print '<td class="right">'.($obj->duration_minutes ? round($obj->duration_minutes / 60, 2) : '-').'</td>';
        print '<td>'.$status_label.'</td>';
        print '<td class="right">';
        print '<a href="'.$pwa_url.'#/rapports/'.$obj->rowid.'" target="_blank" class="button">Voir</a> ';
        if (!empty($user->admin)) {
            print '<a href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$obj->rowid.'&token='.newToken().'" class="button" onclick="return confirm(\'Confirmer suppression ?\')">Suppr</a>';
        }
        print '</td>';
        print '</tr>';

        $i++;
    }

    print '</table>';

    $db->free($resql);
} else {
    dol_print_error($db);
}

print '<br>';
print '<div class="info">';
print '<strong>Note :</strong> Pour créer, modifier et voir les détails des rapports, utilisez la PWA mobile.';
print '</div>';

llxFooter();
$db->close();
