<?php
/**
 * Configuration PWA MV3 PRO
 * Page de configuration complète pour la PWA mobile
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Droits admin requis
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'alpha');

// Actions
if ($action == 'save') {
    $db->begin();

    // URLs
    dolibarr_set_const($db, 'MV3PRO_PWA_PUBLIC_URL', GETPOST('pwa_url', 'alpha'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'MV3PRO_API_BASE_URL', GETPOST('api_url', 'alpha'), 'chaine', 0, '', $conf->entity);

    // Mode debug
    $debug_enabled = GETPOST('debug_enabled', 'alpha') ? '1' : '0';
    dolibarr_set_const($db, 'MV3PRO_DEBUG_ENABLED', $debug_enabled, 'chaine', 0, '', $conf->entity);

    // Créer ou supprimer le fichier debug.flag
    $debug_flag_path = DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/debug.flag';
    if ($debug_enabled == '1') {
        file_put_contents($debug_flag_path, date('Y-m-d H:i:s'));
    } else {
        if (file_exists($debug_flag_path)) {
            unlink($debug_flag_path);
        }
    }

    // Sécurité
    dolibarr_set_const($db, 'MV3PRO_ADMIN_ONLY', GETPOST('admin_only', 'alpha') ? '1' : '0', 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'MV3PRO_PASSWORD_MIN_LENGTH', GETPOST('password_min_length', 'int'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'MV3PRO_PASSWORD_REQUIRE_SPECIAL', GETPOST('password_special', 'alpha') ? '1' : '0', 'chaine', 0, '', $conf->entity);

    // Planning
    dolibarr_set_const($db, 'MV3PRO_PLANNING_ADMIN_VIEW_ALL', GETPOST('planning_admin_all', 'alpha') ? '1' : '0', 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'MV3PRO_PLANNING_EMPLOYEE_OWN_ONLY', GETPOST('planning_employee_own', 'alpha') ? '1' : '0', 'chaine', 0, '', $conf->entity);

    $db->commit();

    setEventMessages('Configuration sauvegardée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Récupération des valeurs actuelles
$pwa_url = $conf->global->MV3PRO_PWA_PUBLIC_URL ?: 'https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/';
$api_url = $conf->global->MV3PRO_API_BASE_URL ?: 'https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/';
$debug_enabled = !empty($conf->global->MV3PRO_DEBUG_ENABLED);
$debug_flag_exists = file_exists(DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/debug.flag');
$admin_only = !empty($conf->global->MV3PRO_ADMIN_ONLY);
$password_min_length = $conf->global->MV3PRO_PASSWORD_MIN_LENGTH ?: 8;
$password_special = !empty($conf->global->MV3PRO_PASSWORD_REQUIRE_SPECIAL);
$planning_admin_all = !empty($conf->global->MV3PRO_PLANNING_ADMIN_VIEW_ALL) || !isset($conf->global->MV3PRO_PLANNING_ADMIN_VIEW_ALL);
$planning_employee_own = !empty($conf->global->MV3PRO_PLANNING_EMPLOYEE_OWN_ONLY);

// Header
llxHeader('', 'Configuration PWA MV3 PRO', '');

print load_fiche_titre('Configuration PWA MV3 PRO', '', 'fa-cogs');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

// Onglet URLs et Liens rapides
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">URLs et Accès rapides</th>';
print '</tr>';

// URL PWA
print '<tr class="oddeven">';
print '<td width="50%">URL PWA publique</td>';
print '<td><input type="text" name="pwa_url" value="'.dol_escape_htmltag($pwa_url).'" size="80" class="flat minwidth500"></td>';
print '</tr>';

// URL API
print '<tr class="oddeven">';
print '<td>URL API base</td>';
print '<td><input type="text" name="api_url" value="'.dol_escape_htmltag($api_url).'" size="80" class="flat minwidth500"></td>';
print '</tr>';

// Liens rapides
print '<tr class="oddeven">';
print '<td>Liens rapides</td>';
print '<td>';
print '<a href="'.$pwa_url.'" target="_blank" class="butAction">Ouvrir PWA</a> ';
print '<a href="'.$pwa_url.'#/debug" target="_blank" class="butAction">Debug PWA</a> ';
print '<a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/mobile_app/admin/manage_users.php" class="butAction">Gestion utilisateurs</a>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Onglet Mode Debug
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">Mode Debug</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="50%">Activer le mode debug';
print '<br><small>Active le fichier debug.flag et les logs détaillés dans l\'API</small>';
print '</td>';
print '<td>';
print '<input type="checkbox" name="debug_enabled" value="1" '.($debug_enabled ? 'checked' : '').'>';
print ' <span style="color: '.($debug_flag_exists ? 'green' : 'red').'">●</span> ';
print 'Fichier debug.flag : '.($debug_flag_exists ? '<b>ACTIF</b>' : 'Inactif');
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Chemin du fichier debug</td>';
print '<td><code>/custom/mv3pro_portail/debug.flag</code></td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Onglet Sécurité
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">Paramètres de sécurité</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="50%">Accès admin uniquement';
print '<br><small>Seuls les administrateurs peuvent créer des utilisateurs mobiles</small>';
print '</td>';
print '<td><input type="checkbox" name="admin_only" value="1" '.($admin_only ? 'checked' : '').'></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Longueur minimale du mot de passe</td>';
print '<td><input type="number" name="password_min_length" value="'.$password_min_length.'" min="6" max="20" class="flat width50"></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Exiger caractères spéciaux';
print '<br><small>Le mot de passe doit contenir au moins un caractère spécial</small>';
print '</td>';
print '<td><input type="checkbox" name="password_special" value="1" '.($password_special ? 'checked' : '').'></td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Onglet Planning
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">Paramètres d\'affichage Planning</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td width="50%">Admin voit tous les rendez-vous';
print '<br><small>Les administrateurs voient le planning de tous les employés</small>';
print '</td>';
print '<td><input type="checkbox" name="planning_admin_all" value="1" '.($planning_admin_all ? 'checked' : '').'></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Employé voit seulement ses RDV';
print '<br><small>Filtre automatique par dolibarr_user_id pour les employés</small>';
print '</td>';
print '<td><input type="checkbox" name="planning_employee_own" value="1" '.($planning_employee_own ? 'checked' : '').'></td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Boutons action
print '<div class="center">';
print '<input type="submit" class="button button-save" value="Enregistrer la configuration">';
print ' ';
print '<a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/mobile_app/admin/manage_users.php" class="butAction">Créer un utilisateur mobile</a>';
print '</div>';

print '</form>';

print '<br><br>';

// Section informations système
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<th colspan="2">Informations système</th>';
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
print '<td><b>'.$nb_users.'</b> <a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/mobile_app/admin/manage_users.php">[Gérer]</a></td>';
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
$tables_required = array(
    'llx_mv3_mobile_users',
    'llx_mv3_rapport',
    'llx_mv3_materiel',
    'llx_mv3_notifications'
);

$tables_ok = 0;
foreach ($tables_required as $table) {
    $sql = "SHOW TABLES LIKE '".$table."'";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $tables_ok++;
    }
}

print '<tr class="oddeven">';
print '<td>Tables base de données</td>';
print '<td>'.$tables_ok.' / '.count($tables_required).' tables présentes';
if ($tables_ok < count($tables_required)) {
    print ' <a href="'.DOL_URL_ROOT.'/custom/mv3pro_portail/sql/INSTRUCTIONS_INSTALLATION.md" target="_blank">[Voir installation SQL]</a>';
}
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Section aide rapide
print '<div class="info">';
print '<h3>Aide rapide</h3>';
print '<ul>';
print '<li><b>Créer un utilisateur mobile :</b> Allez dans "Gestion utilisateurs mobiles" puis cliquez sur "Créer un nouvel utilisateur"</li>';
print '<li><b>Réinitialiser un mot de passe :</b> Dans la liste des utilisateurs, cliquez sur l\'icône "Modifier" puis changez le mot de passe</li>';
print '<li><b>Activer le mode debug :</b> Cochez la case "Mode debug" ci-dessus et sauvegardez. Les logs seront visibles dans la page Debug de la PWA</li>';
print '<li><b>Tester la PWA :</b> Cliquez sur "Ouvrir PWA" pour accéder à l\'application mobile</li>';
print '<li><b>Voir les logs debug :</b> Cliquez sur "Debug PWA" pour voir les informations de diagnostic</li>';
print '</ul>';
print '</div>';

llxFooter();
$db->close();
