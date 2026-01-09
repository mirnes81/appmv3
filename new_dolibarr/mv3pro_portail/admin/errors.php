<?php
/**
 * Journal d'erreurs MV3 PRO Portail
 * Affiche toutes les erreurs enregistrées avec détails complets
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/../class/mv3_config.class.php';
require_once __DIR__.'/../class/mv3_error_logger.class.php';

// Droits admin requis
if (!$user->admin) {
    accessforbidden();
}

$mv3_config = new Mv3Config($db);
$error_logger = new Mv3ErrorLogger($db);

$action = GETPOST('action', 'alpha');
$debug_id = GETPOST('debug_id', 'alpha');

// Actions
if ($action == 'clear_all') {
    $sql = "TRUNCATE TABLE ".MAIN_DB_PREFIX."mv3_error_log";
    $db->query($sql);
    setEventMessages('Journal d\'erreurs vidé', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Statistiques erreurs
$error_stats = $error_logger->getStats(7);

// Header
llxHeader('', 'Journal d\'erreurs MV3 PRO', '');

print load_fiche_titre('Journal d\'erreurs MV3 PRO', '', 'fa-exclamation-triangle');

// Navigation tabs
$head = [
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/setup.php', 'Configuration', 'config'],
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/errors.php', 'Journal d\'erreurs ('.$error_stats['total'].')', 'errors'],
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/diagnostic.php', 'Diagnostic système', 'diagnostic'],
];

print dol_get_fiche_head($head, 'errors', '', -1);

// Actions
print '<div style="margin-bottom: 20px;">';
print '<a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/admin/setup.php" class="butAction">Retour à la configuration</a> ';
print '<a href="'.$_SERVER['PHP_SELF'].'?action=clear_all&token='.newToken().'" class="butActionDelete" onclick="return confirm(\'Êtes-vous sûr de vouloir vider tout le journal d\\\'erreurs ?\')">Vider le journal</a>';
print '</div>';

// Statistiques globales
if ($error_stats['total'] > 0) {
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<th colspan="4">📊 Statistiques (7 derniers jours)</th>';
    print '</tr>';

    print '<tr class="oddeven">';
    print '<td width="25%"><b>Total erreurs</b></td>';
    print '<td width="25%"><b>'.$error_stats['total'].'</b></td>';
    print '<td width="25%"><b>Types d\'erreurs</b></td>';
    print '<td width="25%"><b>'.count($error_stats['by_type']).'</b></td>';
    print '</tr>';

    // Erreurs par type
    if (!empty($error_stats['by_type'])) {
        print '<tr class="oddeven">';
        print '<td colspan="4">';
        print '<table style="width: 100%; margin: 0;">';
        print '<tr style="background: #f0f0f0;"><th>Type</th><th>Nombre</th><th>%</th></tr>';
        foreach ($error_stats['by_type'] as $type => $count) {
            $percent = round(($count / $error_stats['total']) * 100, 1);
            print '<tr><td>'.$type.'</td><td>'.$count.'</td><td>'.$percent.'%</td></tr>';
        }
        print '</table>';
        print '</td>';
        print '</tr>';
    }

    // Erreurs par status HTTP
    if (!empty($error_stats['by_status'])) {
        print '<tr class="oddeven">';
        print '<td colspan="4">';
        print '<table style="width: 100%; margin: 0;">';
        print '<tr style="background: #f0f0f0;"><th>Status HTTP</th><th>Nombre</th></tr>';
        foreach ($error_stats['by_status'] as $status => $count) {
            $status_label = $status == 0 ? 'N/A' : $status;
            $status_color = $status >= 500 ? 'red' : ($status >= 400 ? 'orange' : 'gray');
            print '<tr><td><span style="color: '.$status_color.';">'.$status_label.'</span></td><td>'.$count.'</td></tr>';
        }
        print '</table>';
        print '</td>';
        print '</tr>';
    }

    // Top 10 endpoints avec erreurs
    if (!empty($error_stats['by_endpoint'])) {
        print '<tr class="oddeven">';
        print '<td colspan="4">';
        print '<table style="width: 100%; margin: 0;">';
        print '<tr style="background: #f0f0f0;"><th>Endpoint</th><th>Nombre</th></tr>';
        foreach ($error_stats['by_endpoint'] as $endpoint => $count) {
            print '<tr><td><code>'.dol_escape_htmltag($endpoint).'</code></td><td>'.$count.'</td></tr>';
        }
        print '</table>';
        print '</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';

    print '<br>';
}

// Afficher une erreur spécifique si debug_id fourni
if ($debug_id) {
    $error_detail = $error_logger->getErrorByDebugId($debug_id);

    if ($error_detail) {
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';

        print '<tr class="liste_titre" style="background-color: #dc2626; color: white;">';
        print '<th colspan="2">🔍 Détail de l\'erreur : '.$debug_id.'</th>';
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td width="30%"><b>Debug ID</b></td>';
        print '<td><code style="font-size: 14px; font-weight: bold;">'.$error_detail['debug_id'].'</code></td>';
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td><b>Date</b></td>';
        print '<td>'.dol_print_date(strtotime($error_detail['date_error']), 'dayhour').'</td>';
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td><b>Type</b></td>';
        print '<td><span class="badge badge-danger">'.$error_detail['error_type'].'</span></td>';
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td><b>Status HTTP</b></td>';
        print '<td><span style="color: '.($error_detail['http_status'] >= 500 ? 'red' : 'orange').'; font-weight: bold; font-size: 16px;">'.$error_detail['http_status'].'</span></td>';
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td><b>Source</b></td>';
        print '<td><code>'.$error_detail['error_source'].'</code></td>';
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td><b>Endpoint</b></td>';
        print '<td><code>'.$error_detail['endpoint'].'</code> <span style="color: gray;">['.$error_detail['method'].']</span></td>';
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td><b>Message</b></td>';
        print '<td style="color: red; font-weight: bold;">'.$error_detail['error_message'].'</td>';
        print '</tr>';

        if ($error_detail['sql_error']) {
            print '<tr class="oddeven">';
            print '<td><b>Erreur SQL</b></td>';
            print '<td><pre style="background: #fef2f2; padding: 10px; border: 1px solid #fca5a5; border-radius: 4px; overflow-x: auto;">'.$error_detail['sql_error'].'</pre></td>';
            print '</tr>';
        }

        print '<tr class="oddeven">';
        print '<td><b>Utilisateur</b></td>';
        print '<td>'.$error_detail['user_login'].' (ID: '.$error_detail['user_id'].')</td>';
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td><b>IP</b></td>';
        print '<td>'.$error_detail['ip_address'].'</td>';
        print '</tr>';

        if ($error_detail['error_details']) {
            print '<tr class="oddeven">';
            print '<td><b>Détails</b></td>';
            print '<td><pre style="background: #f3f4f6; padding: 10px; border-radius: 4px; max-height: 300px; overflow: auto;">'.json_encode($error_detail['error_details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre></td>';
            print '</tr>';
        }

        if ($error_detail['request_data']) {
            print '<tr class="oddeven">';
            print '<td><b>Request Data</b></td>';
            print '<td><pre style="background: #f3f4f6; padding: 10px; border-radius: 4px; max-height: 300px; overflow: auto;">'.json_encode($error_detail['request_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre></td>';
            print '</tr>';
        }

        if ($error_detail['response_data']) {
            print '<tr class="oddeven">';
            print '<td><b>Response Data</b></td>';
            print '<td><pre style="background: #f3f4f6; padding: 10px; border-radius: 4px; max-height: 300px; overflow: auto;">'.json_encode($error_detail['response_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre></td>';
            print '</tr>';
        }

        if ($error_detail['stack_trace']) {
            print '<tr class="oddeven">';
            print '<td><b>Stack Trace</b></td>';
            print '<td><pre style="background: #f3f4f6; padding: 10px; border-radius: 4px; max-height: 400px; overflow: auto; font-family: monospace; font-size: 12px;">'.json_encode($error_detail['stack_trace'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre></td>';
            print '</tr>';
        }

        if ($error_detail['user_agent']) {
            print '<tr class="oddeven">';
            print '<td><b>User Agent</b></td>';
            print '<td><small>'.$error_detail['user_agent'].'</small></td>';
            print '</tr>';
        }

        print '</table>';
        print '</div>';

        print '<br>';
        print '<div style="text-align: center;">';
        print '<a href="'.$_SERVER['PHP_SELF'].'" class="butAction">Retour à la liste</a>';
        print '</div>';

        print '<br><br>';
    } else {
        print '<div class="warning">Erreur non trouvée avec le debug_id : '.$debug_id.'</div>';
    }
}

// Liste des erreurs récentes
$recent_errors = $error_logger->getRecentErrors(100);

if (!empty($recent_errors)) {
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<th>Date</th>';
    print '<th>Debug ID</th>';
    print '<th>Type</th>';
    print '<th>Message</th>';
    print '<th>Endpoint</th>';
    print '<th>Status</th>';
    print '<th>Utilisateur</th>';
    print '<th>Action</th>';
    print '</tr>';

    foreach ($recent_errors as $error) {
        print '<tr class="oddeven">';

        // Date
        print '<td style="white-space: nowrap;">'.dol_print_date(strtotime($error['date_error']), 'dayhour').'</td>';

        // Debug ID
        print '<td><code style="font-size: 11px;">'.$error['debug_id'].'</code></td>';

        // Type
        $type_color = 'gray';
        if (strpos($error['error_type'], 'SQL') !== false) $type_color = 'red';
        elseif (strpos($error['error_type'], 'AUTH') !== false) $type_color = 'orange';
        elseif (strpos($error['error_type'], 'API') !== false) $type_color = 'blue';
        print '<td><span style="color: '.$type_color.'; font-weight: bold;">'.$error['error_type'].'</span></td>';

        // Message
        $message = strlen($error['error_message']) > 60 ? substr($error['error_message'], 0, 60).'...' : $error['error_message'];
        print '<td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;" title="'.dol_escape_htmltag($error['error_message']).'">'.$message.'</td>';

        // Endpoint
        $endpoint = $error['endpoint'] ? basename($error['endpoint']) : 'N/A';
        print '<td><code style="font-size: 11px;">'.$endpoint.'</code></td>';

        // Status HTTP
        $status_color = $error['http_status'] >= 500 ? 'red' : ($error['http_status'] >= 400 ? 'orange' : 'gray');
        print '<td style="text-align: center;"><span style="color: '.$status_color.'; font-weight: bold;">'.$error['http_status'].'</span></td>';

        // Utilisateur
        print '<td>'.$error['user_login'].'</td>';

        // Action
        print '<td style="text-align: center;"><a href="'.$_SERVER['PHP_SELF'].'?debug_id='.$error['debug_id'].'" class="button">Détails</a></td>';

        print '</tr>';
    }

    print '</table>';
    print '</div>';
} else {
    print '<div class="info">✅ Aucune erreur enregistrée</div>';
}

print '<br>';

// Instructions
print '<div class="info">';
print '<h3>ℹ️ À propos du journal d\'erreurs</h3>';
print '<ul>';
print '<li><b>Debug ID :</b> Identifiant unique de l\'erreur (format: MV3-YYYYMMDD-XXXXXXXX)</li>';
print '<li><b>Type :</b> Catégorie de l\'erreur (SQL, AUTH, API, etc.)</li>';
print '<li><b>Status HTTP :</b> Code de réponse HTTP (500 = erreur serveur, 404 = non trouvé, etc.)</li>';
print '<li><b>Endpoint :</b> URL/fichier où l\'erreur s\'est produite</li>';
print '<li><b>Détails complets :</b> Cliquez sur "Détails" pour voir l\'erreur SQL complète, la stack trace, etc.</li>';
print '<li><b>Rétention :</b> Les logs sont conservés '.($error_log_retention_days = $mv3_config->get('ERROR_LOG_RETENTION_DAYS', 30)).' jours puis supprimés automatiquement</li>';
print '</ul>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
