<?php
/**
 * Liste des rapports - Mobile
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

$sql = "SELECT r.rowid, r.ref, r.date_rapport, r.temps_total, r.statut,
               s.nom as client_nom, p.ref as projet_ref,
               COUNT(DISTINCT ph.rowid) as nb_photos
        FROM ".MAIN_DB_PREFIX."mv3_rapport r
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = r.fk_soc
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = r.fk_projet
        LEFT JOIN ".MAIN_DB_PREFIX."mv3_rapport_photo ph ON ph.fk_rapport = r.rowid
        WHERE r.fk_user = ".(int)$user_id;

if ($search) {
    $sql .= " AND (s.nom LIKE '%".$db->escape($search)."%' OR r.ref LIKE '%".$db->escape($search)."%')";
}

$sql .= " GROUP BY r.rowid ORDER BY r.date_rapport DESC, r.rowid DESC LIMIT 100";

$resql = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Mes Rapports - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
</head>
<body>
    <div class="app-header">
        <div>
            <div class="app-header-title">📋 Mes Rapports</div>
            <div class="app-header-subtitle">Historique de vos rapports</div>
        </div>
    </div>

    <div class="app-container">
        <div class="card">
            <input type="text" class="form-input" placeholder="🔍 Rechercher..." id="searchInput" value="<?php echo dol_escape_htmltag($search); ?>">
        </div>

        <?php
        if ($resql && $db->num_rows($resql) > 0) {
            while ($rapport = $db->fetch_object($resql)) {
                $statut_class = 'info';
                $statut_text = 'Brouillon';
                if ($rapport->statut == 1) {
                    $statut_class = 'success';
                    $statut_text = 'Validé';
                }

                echo '<a href="view.php?id='.$rapport->rowid.'" class="list-item">';
                echo '<div class="list-item-icon">📋</div>';
                echo '<div class="list-item-content">';
                echo '<div class="list-item-title">'.dol_escape_htmltag($rapport->client_nom ?: $rapport->ref).'</div>';
                echo '<div class="list-item-subtitle">';
                if ($rapport->projet_ref) echo 'Projet: '.dol_escape_htmltag($rapport->projet_ref).' • ';
                echo dol_print_date($db->jdate($rapport->date_rapport), 'day');
                echo '</div>';
                echo '<div class="list-item-meta">';
                if ($rapport->temps_total) echo '⏱️ '.$rapport->temps_total.'h • ';
                if ($rapport->nb_photos > 0) echo '<span class="card-badge badge-warning">📸 '.$rapport->nb_photos.'</span> • ';
                echo '<span class="card-badge badge-'.$statut_class.'">'.$statut_text.'</span>';
                echo '</div>';
                echo '</div>';
                echo '<div style="color:var(--text-light)">→</div>';
                echo '</a>';
            }
        } else {
            echo '<div class="empty-state">';
            echo '<div class="empty-state-icon">📭</div>';
            echo '<div class="empty-state-text">Aucun rapport trouvé</div>';
            echo '</div>';
        }
        ?>
    </div>

    <button class="fab" onclick="window.location.href='new.php'">
        ➕
    </button>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="../js/app.js"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const value = e.target.value;
            if (value.length >= 3 || value.length === 0) {
                window.location.href = '?search=' + encodeURIComponent(value);
            }
        });
    </script>
</body>
</html>
<?php $db->close(); ?>
