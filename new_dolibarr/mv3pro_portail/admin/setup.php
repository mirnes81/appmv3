<?php
/**
 * Configuration MV3 PRO Portail
 * Page de configuration complète avec mode DEV sécurisé
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

// Actions
if ($action == 'save') {
    $db->begin();

    // Sauvegarder tous les paramètres
    $mv3_config->set('API_BASE_URL', GETPOST('api_base_url', 'alpha'));
    $mv3_config->set('PWA_BASE_URL', GETPOST('pwa_base_url', 'alpha'));
    $mv3_config->set('DEV_MODE_ENABLED', GETPOST('dev_mode_enabled', 'alpha') ? '1' : '0');
    $mv3_config->set('DEBUG_CONSOLE_ENABLED', GETPOST('debug_console_enabled', 'alpha') ? '1' : '0');
    $mv3_config->set('SERVICE_WORKER_CACHE_ENABLED', GETPOST('service_worker_cache_enabled', 'alpha') ? '1' : '0');
    $mv3_config->set('PLANNING_ACCESS_POLICY', GETPOST('planning_access_policy', 'alpha'));
    $mv3_config->set('ERROR_LOG_RETENTION_DAYS', GETPOST('error_log_retention_days', 'int'));

    $db->commit();

    setEventMessages('Configuration sauvegardée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if ($action == 'clean_logs') {
    $days = $mv3_config->get('ERROR_LOG_RETENTION_DAYS', 30);
    $error_logger->cleanOldLogs($days);
    setEventMessages('Anciens logs nettoyés', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Récupération des valeurs actuelles
$api_base_url = $mv3_config->get('API_BASE_URL', '/custom/mv3pro_portail/api/v1/');
$pwa_base_url = $mv3_config->get('PWA_BASE_URL', '/custom/mv3pro_portail/pwa_dist/');
$dev_mode_enabled = $mv3_config->get('DEV_MODE_ENABLED', '0') === '1';
$debug_console_enabled = $mv3_config->get('DEBUG_CONSOLE_ENABLED', '0') === '1';
$service_worker_cache_enabled = $mv3_config->get('SERVICE_WORKER_CACHE_ENABLED', '1') === '1';
$planning_access_policy = $mv3_config->get('PLANNING_ACCESS_POLICY', 'employee_own_only');
$error_log_retention_days = $mv3_config->get('ERROR_LOG_RETENTION_DAYS', 30);

// Construire les URLs complètes
$full_pwa_url = dol_buildpath($pwa_base_url, 2);
$full_api_url = dol_buildpath($api_base_url, 2);

// Statistiques erreurs
$error_stats = $error_logger->getStats(7);

// Header
llxHeader('', 'Configuration MV3 PRO Portail', '');

print load_fiche_titre('Configuration MV3 PRO Portail', '', 'fa-cogs');

// Navigation tabs
$head = [
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/setup.php', 'Configuration', 'config'],
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/errors.php', 'Journal d\'erreurs ('.$error_stats['total'].')', 'errors'],
    [DOL_URL_ROOT.'/custom/mv3pro_portail/admin/diagnostic.php', 'Diagnostic système', 'diagnostic'],
];

print dol_get_fiche_head($head, 'config', '', -1);

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

// Section Liens rapides
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">🔗 Liens rapides</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td colspan="2" style="padding: 20px; text-align: center;">';
print '<a href="'.$full_pwa_url.'" target="_blank" class="butAction" style="margin: 5px;">📱 Ouvrir PWA</a> ';
print '<a href="'.$full_pwa_url.'#/debug" target="_blank" class="butAction" style="margin: 5px;">🐛 Debug/Diagnostic PWA</a> ';
print '<a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/mobile_app/admin/manage_users.php" class="butAction" style="margin: 5px;">👥 Gestion utilisateurs mobiles</a> ';
print '<a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/admin/errors.php" class="butAction" style="margin: 5px;">📋 Journal d\'erreurs</a> ';
print '<a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/admin/diagnostic.php" class="butAction" style="margin: 5px;">🔍 Diagnostic complet</a>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Section URLs de base
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">⚙️ URLs de base</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="50%">URL de base API<br><small>Chemin relatif à la racine Dolibarr</small></td>';
print '<td><input type="text" name="api_base_url" value="'.dol_escape_htmltag($api_base_url).'" size="80" class="flat minwidth500"></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>URL de base PWA<br><small>Chemin relatif à la racine Dolibarr</small></td>';
print '<td><input type="text" name="pwa_base_url" value="'.dol_escape_htmltag($pwa_base_url).'" size="80" class="flat minwidth500"></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>URL complète PWA</td>';
print '<td><code>'.$full_pwa_url.'</code> <a href="'.$full_pwa_url.'" target="_blank">🔗 Tester</a></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>URL complète API</td>';
print '<td><code>'.$full_api_url.'</code></td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Section Mode DEV sécurisé
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre'.($dev_mode_enabled ? ' warningbg' : '').'">';
print '<th colspan="2">🚧 Mode Développement (DEV) - Sécurisé</th>';
print '</tr>';

if ($dev_mode_enabled) {
    print '<tr class="oddeven" style="background-color: #fff3cd;">';
    print '<td colspan="2" style="padding: 15px;">';
    print '<div style="font-weight: bold; color: #856404; font-size: 14px;">⚠️ ATTENTION : Mode DEV activé</div>';
    print '<div style="color: #856404; margin-top: 8px;">L\'application PWA est accessible UNIQUEMENT aux administrateurs. Les employés voient une page de maintenance.</div>';
    print '</td>';
    print '</tr>';
}

print '<tr class="oddeven">';
print '<td width="50%">';
print '<b>Activer le mode DEV</b><br>';
print '<small style="color: #666;">Quand activé :<br>';
print '• PWA accessible uniquement aux admins<br>';
print '• Employés voient "Application en maintenance"<br>';
print '• API bloque les endpoints pour non-admins<br>';
print '• Logs debug détaillés activés</small>';
print '</td>';
print '<td>';
print '<input type="checkbox" name="dev_mode_enabled" value="1" '.($dev_mode_enabled ? 'checked' : '').'>';
print ' <span style="font-weight: bold; color: '.($dev_mode_enabled ? 'orange' : 'green').';">'.($dev_mode_enabled ? '🚧 MODE DEV ON' : '✅ MODE PRODUCTION').'</span>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Activer les logs console PWA<br><small>Active console.log() dans la PWA pour debug</small></td>';
print '<td><input type="checkbox" name="debug_console_enabled" value="1" '.($debug_console_enabled ? 'checked' : '').'></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Activer le cache Service Worker<br><small>Désactiver pour forcer le rechargement à chaque visite</small></td>';
print '<td><input type="checkbox" name="service_worker_cache_enabled" value="1" '.($service_worker_cache_enabled ? 'checked' : '').'></td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Section Planning
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">📅 Politique d\'accès Planning</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="50%">Politique d\'accès<br><small>Définit qui voit quoi dans le planning</small></td>';
print '<td>';
print '<select name="planning_access_policy" class="flat minwidth300">';
print '<option value="all" '.($planning_access_policy == 'all' ? 'selected' : '').'>Tous les utilisateurs voient tout</option>';
print '<option value="employee_own_only" '.($planning_access_policy == 'employee_own_only' ? 'selected' : '').'>Admin voit tout / Employé voit seulement ses RDV</option>';
print '<option value="admin_only" '.($planning_access_policy == 'admin_only' ? 'selected' : '').'>Admin uniquement</option>';
print '</select>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Section Logs et maintenance
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">📋 Logs et maintenance</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="50%">Rétention des logs d\'erreurs<br><small>Nombre de jours avant suppression automatique</small></td>';
print '<td><input type="number" name="error_log_retention_days" value="'.$error_log_retention_days.'" min="1" max="365" class="flat width100"> jours</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Erreurs enregistrées (7 derniers jours)</td>';
print '<td><b>'.$error_stats['total'].'</b> erreurs <a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/admin/errors.php" class="button">[Voir le journal]</a></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Nettoyer les anciens logs</td>';
print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=clean_logs&token='.newToken().'" class="button">Nettoyer les logs > '.$error_log_retention_days.' jours</a></td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Boutons action
print '<div class="center">';
print '<input type="submit" class="button button-save" value="💾 Enregistrer la configuration">';
print '</div>';

print '</form>';

print '<br><br>';

// Section informations système
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">ℹ️ Informations système</th>';
print '</tr>';

// Compter les utilisateurs mobiles
$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."mv3_mobile_users WHERE is_active = 1";
$resql = $db->query($sql);
$nb_users = 0;
if ($resql) {
    $obj = $db->fetch_object($resql);
    $nb_users = $obj->nb;
}

print '<tr class="oddeven">';
print '<td width="50%">Utilisateurs mobiles actifs</td>';
print '<td><b>'.$nb_users.'</b> <a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/mobile_app/admin/manage_users.php" class="button">[Gérer]</a></td>';
print '</tr>';

// Version PWA
$version_file = DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/pwa_dist/index.html';
$pwa_version = file_exists($version_file) ? 'Build du '.date('Y-m-d H:i', filemtime($version_file)) : 'Non trouvée';

print '<tr class="oddeven">';
print '<td>Version PWA</td>';
print '<td>'.$pwa_version.'</td>';
print '</tr>';

// Statut API
$api_path = DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/api/v1/index.php';
$api_status = file_exists($api_path) ? '<span style="color: green">✓ Opérationnelle</span>' : '<span style="color: red">✗ Non trouvée</span>';

print '<tr class="oddeven">';
print '<td>Statut API v1</td>';
print '<td>'.$api_status.'</td>';
print '</tr>';

// Tables base de données
$tables_required = [
    'llx_mv3_mobile_users',
    'llx_mv3_rapport',
    'llx_mv3_materiel',
    'llx_mv3_notifications',
    'llx_mv3_config',
    'llx_mv3_error_log'
];

$tables_ok = 0;
$tables_missing = [];
foreach ($tables_required as $table) {
    $sql = "SHOW TABLES LIKE '".$table."'";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $tables_ok++;
    } else {
        $tables_missing[] = $table;
    }
}

print '<tr class="oddeven">';
print '<td>Tables base de données</td>';
print '<td>'.$tables_ok.' / '.count($tables_required).' tables présentes';
if ($tables_ok < count($tables_required)) {
    print '<br><span style="color: orange;">Tables manquantes: '.implode(', ', $tables_missing).'</span>';
    print '<br><a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/sql/INSTRUCTIONS_INSTALLATION.md" target="_blank" class="button">[Voir installation SQL]</a>';
}
print '</td>';
print '</tr>';

// Erreurs par type (stats)
if (!empty($error_stats['by_type'])) {
    print '<tr class="oddeven">';
    print '<td>Erreurs par type (7j)</td>';
    print '<td><ul style="margin: 0;">';
    foreach ($error_stats['by_type'] as $type => $count) {
        print '<li><b>'.$type.':</b> '.$count.'</li>';
    }
    print '</ul></td>';
    print '</tr>';
}

print '</table>';
print '</div>';

print '<br>';

// Section aide rapide
print '<div class="info">';
print '<h3>📚 Aide rapide</h3>';
print '<ul>';
print '<li><b>Mode DEV :</b> Activez pour tester en toute sécurité. Les employés ne pourront pas accéder à la PWA pendant vos tests.</li>';
print '<li><b>Créer un utilisateur mobile :</b> Cliquez sur "Gestion utilisateurs mobiles" puis "Créer un nouvel utilisateur"</li>';
print '<li><b>Voir les erreurs :</b> Allez dans "Journal d\'erreurs" pour voir toutes les erreurs avec debug_id et détails SQL</li>';
print '<li><b>Diagnostic complet :</b> Utilisez "Diagnostic système" pour tester automatiquement toutes les pages et endpoints</li>';
print '<li><b>Debug PWA :</b> Ouvrez "Debug/Diagnostic PWA" pour voir les tests en temps réel côté frontend</li>';
print '</ul>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
