<?php
/**
 * Configuration MV3 PRO Portail - VERSION MINIMALE
 * Planning + PWA uniquement
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

$action = GETPOST('action', 'aZ09');

// Sauvegarde configuration
if ($action == 'save') {
    $pwa_url = GETPOST('MV3PRO_PWA_URL', 'alpha');

    dolibarr_set_const($db, 'MV3PRO_PWA_URL', $pwa_url, 'chaine', 0, '', $conf->entity);

    setEventMessages('Configuration sauvegardée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Valeurs actuelles
$pwa_url = $conf->global->MV3PRO_PWA_URL ?? '/custom/mv3pro_portail/pwa_dist/';
$full_pwa_url = dol_buildpath($pwa_url, 2);

// Header
llxHeader('', 'Configuration MV3 PRO Portail', '');

print load_fiche_titre('Configuration MV3 PRO Portail', '', 'fa-calendar');

?>

<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="save">

    <div class="div-table-responsive">
        <table class="noborder centpercent">
            <tr class="liste_titre">
                <td>Paramètre</td>
                <td>Valeur</td>
            </tr>

            <tr class="oddeven">
                <td>
                    <span class="fieldrequired">URL de la PWA</span>
                    <br><small>URL relative ou absolue de la Progressive Web App</small>
                </td>
                <td>
                    <input type="text" name="MV3PRO_PWA_URL" value="<?php echo dol_escape_htmltag($pwa_url); ?>" class="minwidth500">
                    <br><small><strong>URL complète :</strong> <a href="<?php echo $full_pwa_url; ?>" target="_blank"><?php echo $full_pwa_url; ?></a></small>
                </td>
            </tr>
        </table>
    </div>

    <div class="center" style="margin-top: 20px;">
        <input type="submit" class="button button-save" name="save" value="Enregistrer">
    </div>
</form>

<br>

<div class="info">
    <h3>ℹ️ Informations</h3>
    <ul>
        <li><strong>Module :</strong> MV3 PRO Portail v2.0.0-minimal</li>
        <li><strong>Fonctionnalités :</strong> Planning + PWA</li>
        <li><strong>API :</strong> <?php echo dol_buildpath('/custom/mv3pro_portail/api/v1/', 2); ?></li>
        <li><strong>PWA :</strong> <?php echo $full_pwa_url; ?></li>
        <li><strong>Documentation :</strong> <a href="https://github.com/yourusername/mv3pro_portail" target="_blank">GitHub</a></li>
    </ul>
</div>

<div class="info" style="background: #d1ecf1; border-color: #bee5eb; margin-top: 20px;">
    <h3>🚀 Démarrage rapide</h3>
    <ol>
        <li>Vérifiez que le module est activé (Accueil → Configuration → Modules/Applications)</li>
        <li>Accédez au planning via le menu <strong>MV-3 PRO → Planning</strong></li>
        <li>Ouvrez la PWA à l'adresse : <a href="<?php echo $full_pwa_url; ?>" target="_blank"><?php echo $full_pwa_url; ?></a></li>
        <li>Les techniciens peuvent se connecter avec leurs identifiants Dolibarr</li>
    </ol>
</div>

<?php

llxFooter();
$db->close();
