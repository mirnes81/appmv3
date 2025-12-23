<?php
/**
 * Création fiche sens de pose - Version Mobile
 */

$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";

if (!isset($_SESSION["dol_login"]) || empty($user->id)) {
    header("Location: ../index.php");
    exit;
}

$action = GETPOST('action', 'alpha');
$error_msg = '';
$success = false;

if ($action == 'create') {
    $client_name = GETPOST('client_name', 'restricthtml');
    $site_address = GETPOST('site_address', 'restricthtml');
    $notes = GETPOST('notes', 'restricthtml');

    if (empty($client_name)) {
        $error_msg = "Le nom du client est obligatoire";
    } else {
        $db->begin();

        $year = date('Y');
        $sql_count = "SELECT MAX(CAST(SUBSTRING_INDEX(ref, '-', -1) AS UNSIGNED)) as max_num
                      FROM ".MAIN_DB_PREFIX."mv3_sens_pose
                      WHERE ref LIKE 'POSE-".$year."-%'";
        $resql_count = $db->query($sql_count);
        $next_num = 1;
        if ($resql_count) {
            $obj_count = $db->fetch_object($resql_count);
            if ($obj_count && $obj_count->max_num) {
                $next_num = $obj_count->max_num + 1;
            }
        }
        $new_ref = 'POSE-'.$year.'-'.str_pad($next_num, 4, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose
                (entity, ref, client_name, site_address, notes, fk_user_create, statut, date_creation)
                VALUES (".$conf->entity.", '".$new_ref."', '".$db->escape($client_name)."',
                '".$db->escape($site_address)."', '".$db->escape($notes)."',
                ".(int)$user->id.", 'brouillon', NOW())";

        if ($db->query($sql)) {
            $new_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_sens_pose");
            $db->commit();
            $success = true;
            header("Location: view.php?id=".$new_id."&created=1");
            exit;
        } else {
            $db->rollback();
            $error_msg = "Erreur lors de la création: ".$db->lasterror();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Nouvelle Fiche - Sens de Pose</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
</head>
<body>
    <div class="app-header">
        <a href="list.php" class="app-header-back">←</a>
        <div>
            <div class="app-header-title">🔲 Nouvelle fiche</div>
            <div class="app-header-subtitle">Sens de pose carrelage</div>
        </div>
    </div>

    <div class="app-container">
        <?php if ($error_msg): ?>
        <div class="card" style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px;">
            <div style="color: #dc2626; font-size: 14px;">
                ⚠️ <?php echo dol_escape_htmltag($error_msg); ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="new.php" accept-charset="UTF-8">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">

            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Informations chantier</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nom du client *</label>
                    <input type="text"
                           name="client_name"
                           class="form-input"
                           placeholder="Ex: Jean Dupont"
                           required
                           value="<?php echo GETPOST('client_name', 'restricthtml'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse du chantier</label>
                    <input type="text"
                           name="site_address"
                           class="form-input"
                           placeholder="Ex: 123 Rue de la Paix, 75001 Paris"
                           value="<?php echo GETPOST('site_address', 'restricthtml'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes"
                              class="form-input"
                              rows="4"
                              placeholder="Notes supplémentaires..."><?php echo GETPOST('notes', 'restricthtml'); ?></textarea>
                </div>
            </div>

            <div class="card" style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px;">
                <div style="font-size: 12px; color: #1e40af;">
                    ℹ️ Une fois créée, vous pourrez ajouter les pièces et produits à la fiche.
                </div>
            </div>

            <div class="form-actions">
                <a href="list.php" class="btn btn-secondary" style="flex: 1;">
                    Annuler
                </a>
                <button type="submit" class="btn btn-primary" style="flex: 2;">
                    ✅ Créer la fiche
                </button>
            </div>
        </form>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>
</body>
</html>
