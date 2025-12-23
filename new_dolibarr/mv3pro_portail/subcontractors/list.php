<?php
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load('mv3pro_portail@mv3pro_portail');

$action = GETPOST('action', 'alpha');
$search_name = GETPOST('search_name', 'alpha');
$search_specialty = GETPOST('search_specialty', 'alpha');

llxHeader('', 'Gestion des Sous-Traitants', '', '', 0, 0, [], [], [], 'mod-mv3pro-subcontractors');

print load_fiche_titre('Sous-Traitants', '', 'user');

print '<div class="fichecenter">';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste noborder centpercent">';

print '<tr class="liste_titre">';
print '<th>Référence</th>';
print '<th>Nom</th>';
print '<th>Téléphone</th>';
print '<th>Spécialité</th>';
print '<th>Tarif</th>';
print '<th>Code PIN</th>';
print '<th class="center">Actif</th>';
print '<th class="center">Dernière connexion</th>';
print '<th class="right">Actions</th>';
print '</tr>';

print '<tr class="liste_titre">';
print '<td><input type="text" class="flat" name="search_ref" value="'.dol_escape_htmltag($search_name).'"></td>';
print '<td><input type="text" class="flat" name="search_name" value="'.dol_escape_htmltag($search_name).'"></td>';
print '<td></td>';
print '<td><input type="text" class="flat" name="search_specialty" value="'.dol_escape_htmltag($search_specialty).'"></td>';
print '<td colspan="5"></td>';
print '</tr>';

$sql = "SELECT s.rowid, s.ref, s.firstname, s.lastname, s.phone, s.specialty,";
$sql .= " s.rate_type, s.rate_amount, s.pin_code, s.active, s.last_login";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_subcontractors as s";
$sql .= " WHERE s.entity = ".$conf->entity;

if ($search_name) {
    $sql .= " AND (s.firstname LIKE '%".$db->escape($search_name)."%'";
    $sql .= " OR s.lastname LIKE '%".$db->escape($search_name)."%')";
}

if ($search_specialty) {
    $sql .= " AND s.specialty LIKE '%".$db->escape($search_specialty)."%'";
}

$sql .= " ORDER BY s.rowid DESC";

$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;

    while ($i < $num) {
        $obj = $db->fetch_object($resql);

        print '<tr class="oddeven">';

        print '<td class="nowraponall">';
        print '<a href="card.php?id='.$obj->rowid.'">'.$obj->ref.'</a>';
        print '</td>';

        print '<td>';
        print '<a href="card.php?id='.$obj->rowid.'">';
        print dol_escape_htmltag($obj->firstname.' '.$obj->lastname);
        print '</a>';
        print '</td>';

        print '<td>';
        print dol_print_phone($obj->phone);
        print '</td>';

        print '<td>';
        print dol_escape_htmltag($obj->specialty);
        print '</td>';

        print '<td>';
        $rate_label = [
            'm2' => 'm²',
            'hourly' => 'heure',
            'daily' => 'jour'
        ];
        print price($obj->rate_amount).' € / '.($rate_label[$obj->rate_type] ?? $obj->rate_type);
        print '</td>';

        print '<td class="center">';
        print '<span class="badge badge-info">'.$obj->pin_code.'</span>';
        print '</td>';

        print '<td class="center">';
        if ($obj->active) {
            print '<span class="badge badge-status4">Actif</span>';
        } else {
            print '<span class="badge badge-status9">Inactif</span>';
        }
        print '</td>';

        print '<td class="center nowraponall">';
        if ($obj->last_login) {
            print dol_print_date($db->jdate($obj->last_login), 'dayhour');
        } else {
            print '-';
        }
        print '</td>';

        print '<td class="right nowraponall">';
        print '<a class="butAction" href="card.php?id='.$obj->rowid.'">Modifier</a>';
        print '<a class="butAction" href="reports.php?id='.$obj->rowid.'">Rapports</a>';
        print '</td>';

        print '</tr>';
        $i++;
    }

    if ($num === 0) {
        print '<tr><td colspan="9" class="opacitymedium center">Aucun sous-traitant trouvé</td></tr>';
    }
} else {
    dol_print_error($db);
}

print '</table>';
print '</div>';

print '<div class="tabsAction">';
print '<a class="butAction" href="card.php?action=create">Nouveau sous-traitant</a>';
print '</div>';

print '</div>';

llxFooter();
$db->close();
