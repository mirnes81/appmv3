<?php
/**
 * Liste des fiches sens de pose - Mobile
 */

$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";

if (!isset($_SESSION["dol_login"]) || empty($user->id)) {
    header("Location: ../index.php");
    exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$user_id = $user->id;
$search = GETPOST('search', 'alpha');
$statut_filter = GETPOST('statut', 'alpha');

$sql = "SELECT sp.rowid, sp.ref, sp.client_name, sp.site_address, sp.statut,
               sp.date_creation, sp.signature_date,
               COUNT(spp.rowid) as nb_pieces,
               u.firstname, u.lastname
        FROM ".MAIN_DB_PREFIX."mv3_sens_pose sp
        LEFT JOIN ".MAIN_DB_PREFIX."mv3_sens_pose_pieces spp ON spp.fk_sens_pose = sp.rowid
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = sp.fk_user_create
        WHERE 1=1";

if (isset($conf->entity)) {
    $sql .= " AND sp.entity = ".(int)$conf->entity;
}

if ($search) {
    $sql .= " AND (sp.client_name LIKE '%".$db->escape($search)."%' OR sp.ref LIKE '%".$db->escape($search)."%' OR sp.site_address LIKE '%".$db->escape($search)."%')";
}

if ($statut_filter) {
    $sql .= " AND sp.statut = '".$db->escape($statut_filter)."'";
}

$sql .= " GROUP BY sp.rowid, sp.ref, sp.client_name, sp.site_address, sp.statut, sp.date_creation, sp.signature_date, u.firstname, u.lastname
          ORDER BY sp.date_creation DESC
          LIMIT 200";

$resql = $db->query($sql);
$total_fiches = 0;
if ($resql) {
    $total_fiches = $db->num_rows($resql);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Fiches Sens de Pose - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
</head>
<body>
    <div class="app-header">
        <div>
            <div class="app-header-title">🔲 Sens de Pose</div>
            <div class="app-header-subtitle">Fiches de validation carrelage</div>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="new_from_devis.php" class="btn-fab" style="background: #059669; font-size: 16px;">📋</a>
            <a href="new.php" class="btn-fab">+</a>
        </div>
    </div>

    <div class="app-container">
        <div class="card">
            <input type="text" class="form-input" placeholder="🔍 Rechercher..." id="searchInput" value="<?php echo dol_escape_htmltag($search); ?>">
        </div>

        <div class="card">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                <a href="?statut=" class="btn <?php echo empty($statut_filter) ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px; font-size: 12px;">
                    Tous
                </a>
                <a href="?statut=brouillon" class="btn <?php echo $statut_filter == 'brouillon' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px; font-size: 12px;">
                    Brouillon
                </a>
                <a href="?statut=envoye" class="btn <?php echo $statut_filter == 'envoye' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px; font-size: 12px;">
                    Envoyé
                </a>
                <a href="?statut=signe" class="btn <?php echo $statut_filter == 'signe' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px; font-size: 12px;">
                    Signé
                </a>
            </div>
        </div>

        <?php if ($total_fiches > 0): ?>
        <div class="card" style="background: #e0f2fe; border-left: 4px solid #0891b2; padding: 12px;">
            <div style="font-size: 12px; color: #0369a1; font-weight: 600;">
                📊 <?php echo $total_fiches; ?> fiche<?php echo $total_fiches > 1 ? 's' : ''; ?> disponible<?php echo $total_fiches > 1 ? 's' : ''; ?>
                <?php if ($statut_filter): ?>
                    (statut: <?php echo dol_escape_htmltag($statut_filter); ?>)
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        if ($resql && $db->num_rows($resql) > 0) {
            while ($fiche = $db->fetch_object($resql)) {
                $statut_icon = '📝';
                $statut_class = 'warning';
                $statut_text = 'Brouillon';

                if ($fiche->statut == 'envoye') {
                    $statut_icon = '📧';
                    $statut_class = 'info';
                    $statut_text = 'Envoyé';
                } elseif ($fiche->statut == 'signe') {
                    $statut_icon = '✍️';
                    $statut_class = 'success';
                    $statut_text = 'Signé';
                }

                echo '<a href="view.php?id='.$fiche->rowid.'" class="list-item">';
                echo '<div class="list-item-icon">'.$statut_icon.'</div>';
                echo '<div class="list-item-content">';
                echo '<div class="list-item-title">'.dol_escape_htmltag($fiche->ref).'</div>';
                echo '<div class="list-item-subtitle">';
                echo '<strong>'.dol_escape_htmltag($fiche->client_name).'</strong>';
                if ($fiche->site_address) {
                    echo '<br>📍 '.dol_escape_htmltag(substr($fiche->site_address, 0, 40));
                    if (strlen($fiche->site_address) > 40) echo '...';
                }
                echo '</div>';
                echo '<div class="list-item-meta">';
                echo dol_print_date($db->jdate($fiche->date_creation), 'day');
                if ($fiche->nb_pieces > 0) {
                    echo ' • <span class="card-badge badge-info">🔲 '.$fiche->nb_pieces.' pièce'.($fiche->nb_pieces > 1 ? 's' : '').'</span>';
                }
                echo ' • <span class="card-badge badge-'.$statut_class.'">'.$statut_text.'</span>';
                echo '</div>';
                echo '</div>';
                echo '<div style="color:var(--text-light)">→</div>';
                echo '</a>';
            }
        } else {
            echo '<div class="empty-state">';
            echo '<div class="empty-state-icon">📭</div>';
            echo '<div class="empty-state-text">';
            if ($search || $statut_filter) {
                echo 'Aucune fiche trouvée';
            } else {
                echo 'Aucune fiche de sens de pose';
            }
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const search = this.value;
            const statut = '<?php echo $statut_filter; ?>';
            const url = new URL(window.location.href);
            url.searchParams.set('search', search);
            if (statut) url.searchParams.set('statut', statut);
            window.location.href = url.toString();
        }, 500);
    });
    </script>
</body>
</html>
