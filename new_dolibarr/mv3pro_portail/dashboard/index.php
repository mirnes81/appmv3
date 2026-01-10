<?php
/**
 * Dashboard MV-3 PRO
 * Vue d'ensemble Planning + Statistiques
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Vérifier droits
if (!$user->rights->mv3pro_portail->read) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$entity = isset($conf->entity) ? $conf->entity : 1;

// Header
llxHeader('', 'Dashboard MV-3 PRO', '');

print load_fiche_titre('Dashboard MV-3 PRO', '', 'fa-chart-line');

// Période par défaut : cette semaine
$now = dol_now();
$start_week = dol_get_first_day_week(dol_print_date($now, '%d'), dol_print_date($now, '%m'), dol_print_date($now, '%Y'));
$end_week = dol_time_plus_duree($start_week, 6, 'd');

// Statistiques planning
$sql = "SELECT COUNT(*) as total,
        SUM(CASE WHEN datep >= '".$db->idate($start_week)."' AND datep <= '".$db->idate($end_week)."' THEN 1 ELSE 0 END) as cette_semaine,
        SUM(CASE WHEN datep >= '".$db->idate($now)."' AND datep < '".$db->idate(dol_time_plus_duree($now, 1, 'd'))."' THEN 1 ELSE 0 END) as aujourd_hui,
        SUM(CASE WHEN datep > '".$db->idate($now)."' THEN 1 ELSE 0 END) as a_venir,
        SUM(CASE WHEN datep < '".$db->idate($now)."' THEN 1 ELSE 0 END) as passes
        FROM ".MAIN_DB_PREFIX."actioncomm
        WHERE entity = ".$entity."
        AND percent >= 0";

$resql = $db->query($sql);
$stats = $db->fetch_object($resql);

// Statistiques par utilisateur (techniciens)
$sql_users = "SELECT u.rowid, u.login, u.firstname, u.lastname,
              COUNT(a.id) as nb_events,
              SUM(CASE WHEN a.datep >= '".$db->idate($start_week)."' AND a.datep <= '".$db->idate($end_week)."' THEN 1 ELSE 0 END) as cette_semaine
              FROM ".MAIN_DB_PREFIX."user u
              LEFT JOIN ".MAIN_DB_PREFIX."actioncomm a ON a.fk_user_action = u.rowid AND a.entity = ".$entity."
              WHERE u.entity IN (0, ".$entity.")
              AND u.statut = 1
              GROUP BY u.rowid
              HAVING nb_events > 0
              ORDER BY nb_events DESC
              LIMIT 10";

$resql_users = $db->query($sql_users);
$users_stats = [];
while ($obj = $db->fetch_object($resql_users)) {
    $users_stats[] = $obj;
}
?>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dashboard-widget {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.dashboard-widget h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dashboard-widget .widget-value {
    font-size: 36px;
    font-weight: bold;
    color: #0891b2;
    margin: 10px 0;
}

.dashboard-widget .widget-label {
    font-size: 13px;
    color: #666;
}

.dashboard-widget.widget-primary { border-left: 4px solid #0891b2; }
.dashboard-widget.widget-success { border-left: 4px solid #10b981; }
.dashboard-widget.widget-warning { border-left: 4px solid #f59e0b; }
.dashboard-widget.widget-info { border-left: 4px solid #3b82f6; }

.users-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.users-list li {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.users-list li:last-child {
    border-bottom: none;
}

.user-name {
    font-weight: 600;
    color: #333;
}

.user-events {
    background: #e0f2fe;
    color: #0891b2;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.quick-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
</style>

<!-- Statistiques globales -->
<div class="dashboard-grid">
    <div class="dashboard-widget widget-primary">
        <h3><span class="fa fa-calendar fa-fw"></span> Aujourd'hui</h3>
        <div class="widget-value"><?php echo $stats->aujourd_hui; ?></div>
        <div class="widget-label">Événements prévus</div>
    </div>

    <div class="dashboard-widget widget-success">
        <h3><span class="fa fa-calendar-week fa-fw"></span> Cette semaine</h3>
        <div class="widget-value"><?php echo $stats->cette_semaine; ?></div>
        <div class="widget-label">Événements planifiés</div>
    </div>

    <div class="dashboard-widget widget-info">
        <h3><span class="fa fa-clock fa-fw"></span> À venir</h3>
        <div class="widget-value"><?php echo $stats->a_venir; ?></div>
        <div class="widget-label">Événements futurs</div>
    </div>

    <div class="dashboard-widget widget-warning">
        <h3><span class="fa fa-check-circle fa-fw"></span> Total</h3>
        <div class="widget-value"><?php echo $stats->total; ?></div>
        <div class="widget-label">Tous les événements</div>
    </div>
</div>

<!-- Actions rapides -->
<div class="quick-actions">
    <a href="<?php echo DOL_URL_ROOT; ?>/comm/action/card.php?action=create&backtopage=<?php echo urlencode($_SERVER['PHP_SELF']); ?>" class="button">
        <span class="fa fa-plus-circle"></span> Nouvel événement
    </a>
    <a href="<?php echo DOL_URL_ROOT; ?>/comm/action/index.php?mainmenu=agenda" class="button">
        <span class="fa fa-calendar"></span> Voir le planning
    </a>
    <a href="<?php echo $conf->global->MV3PRO_PWA_URL ?? '/custom/mv3pro_portail/pwa_dist/'; ?>" target="_blank" class="button">
        <span class="fa fa-mobile-alt"></span> Ouvrir PWA
    </a>
</div>

<!-- Activité par technicien -->
<?php if (!empty($users_stats)) { ?>
<div style="margin-top: 30px;">
    <div class="dashboard-widget">
        <h3><span class="fa fa-users fa-fw"></span> Activité par technicien (cette semaine)</h3>
        <ul class="users-list">
            <?php foreach ($users_stats as $user_stat) { ?>
                <li>
                    <span class="user-name">
                        <?php echo dol_escape_htmltag($user_stat->firstname.' '.$user_stat->lastname); ?>
                        <small style="color: #999;">(<?php echo $user_stat->login; ?>)</small>
                    </span>
                    <span class="user-events"><?php echo $user_stat->cette_semaine; ?> événement(s)</span>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>
<?php } ?>

<!-- Planning des 7 prochains jours -->
<div style="margin-top: 30px;">
    <h3><span class="fa fa-list fa-fw"></span> Planning des 7 prochains jours</h3>

    <?php
    $sql_upcoming = "SELECT a.id, a.label, a.datep, a.datep2, a.fk_user_action,
                     u.firstname, u.lastname, u.login,
                     s.nom as societe
                     FROM ".MAIN_DB_PREFIX."actioncomm a
                     LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = a.fk_user_action
                     LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = a.fk_soc
                     WHERE a.entity = ".$entity."
                     AND a.datep >= '".$db->idate($now)."'
                     AND a.datep <= '".$db->idate(dol_time_plus_duree($now, 7, 'd'))."'
                     ORDER BY a.datep ASC, a.datep2 ASC
                     LIMIT 20";

    $resql_upcoming = $db->query($sql_upcoming);

    if ($resql_upcoming && $db->num_rows($resql_upcoming) > 0) {
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th>Date</th>';
        print '<th>Événement</th>';
        print '<th>Technicien</th>';
        print '<th>Client</th>';
        print '<th></th>';
        print '</tr>';

        while ($event = $db->fetch_object($resql_upcoming)) {
            print '<tr class="oddeven">';

            // Date
            print '<td style="white-space: nowrap;">';
            print '<span class="fa fa-calendar fa-fw" style="color: #0891b2;"></span> ';
            print dol_print_date($db->jdate($event->datep), 'day');
            if ($event->datep2) {
                print ' <span style="color: #999;">-</span> ';
                print dol_print_date($db->jdate($event->datep2), 'hour');
            }
            print '</td>';

            // Événement
            print '<td><strong>'.dol_escape_htmltag($event->label).'</strong></td>';

            // Technicien
            print '<td>';
            if ($event->fk_user_action > 0) {
                print dol_escape_htmltag($event->firstname.' '.$event->lastname);
            } else {
                print '<span style="color: #999;">-</span>';
            }
            print '</td>';

            // Client
            print '<td>';
            if ($event->societe) {
                print dol_escape_htmltag($event->societe);
            } else {
                print '<span style="color: #999;">-</span>';
            }
            print '</td>';

            // Actions
            print '<td class="center">';
            print '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?id='.$event->id.'" class="button">';
            print '<span class="fa fa-eye"></span>';
            print '</a>';
            print '</td>';

            print '</tr>';
        }

        print '</table>';
    } else {
        print '<div class="info" style="padding: 20px; text-align: center; color: #666;">';
        print '<span class="fa fa-calendar-times fa-3x" style="opacity: 0.3; margin-bottom: 10px;"></span><br>';
        print 'Aucun événement prévu dans les 7 prochains jours.';
        print '</div>';
    }
    ?>
</div>

<!-- Lien vers PWA -->
<div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); border-radius: 8px; color: white; text-align: center;">
    <h3 style="margin: 0 0 10px 0; color: white;">
        <span class="fa fa-mobile-alt fa-fw"></span> Application Mobile
    </h3>
    <p style="margin: 0 0 15px 0; opacity: 0.9;">
        Accédez au planning depuis votre smartphone avec notre PWA
    </p>
    <a href="<?php echo $conf->global->MV3PRO_PWA_URL ?? '/custom/mv3pro_portail/pwa_dist/'; ?>" target="_blank"
       class="button" style="background: white; color: #0891b2; border: none; font-weight: 600;">
        <span class="fa fa-external-link-alt"></span> Ouvrir la PWA
    </a>
</div>

<?php

llxFooter();
$db->close();
