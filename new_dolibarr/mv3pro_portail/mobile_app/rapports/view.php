<?php
/**
 * Voir rapport - Mobile
 */



$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";

if (!isset($_SESSION["dol_login"]) || empty($user->id)) {
    header("Location: ../index.php");
    exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$id = GETPOST('id', 'int');
$user_id = $user->id;

$sql = "SELECT r.*, s.nom as client_nom, s.rowid as client_id,
               p.ref as projet_ref, p.title as projet_title,
               u.firstname, u.lastname
        FROM ".MAIN_DB_PREFIX."mv3_rapport r
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = r.fk_soc
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = r.fk_projet
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = r.fk_user
        WHERE r.rowid = ".(int)$id."
        AND r.fk_user = ".(int)$user_id;

$resql = $db->query($sql);
$rapport = $db->fetch_object($resql);

if (!$rapport) {
    header('Location: list.php');
    exit;
}

// Récupérer les photos depuis la base
$sql_photos = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE fk_rapport = ".(int)$id;
$check_col = $db->query("SHOW COLUMNS FROM ".MAIN_DB_PREFIX."mv3_rapport_photo LIKE 'position'");
if ($db->num_rows($check_col) > 0) {
    $sql_photos .= " ORDER BY position ASC";
} else {
    $sql_photos .= " ORDER BY ordre ASC";
}
$resql_photos = $db->query($sql_photos);
$photos = [];
if ($resql_photos) {
    while ($photo = $db->fetch_object($resql_photos)) {
        $photos[] = $photo;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title><?php echo dol_escape_htmltag($rapport->ref); ?> - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        .photo-preview-item { position: relative; }
    </style>
</head>
<body>
    <div class="app-header">
        <div style="display:flex;align-items:center;gap:12px">
            <a href="list.php" style="color:white;font-size:24px;text-decoration:none">←</a>
            <div>
                <div class="app-header-title"><?php echo dol_escape_htmltag($rapport->ref); ?></div>
                <div class="app-header-subtitle"><?php echo dol_print_date($db->jdate($rapport->date_rapport), 'day'); ?></div>
            </div>
        </div>
        <a href="edit.php?id=<?php echo $id; ?>" style="color:white;font-size:20px;text-decoration:none">✏️</a>
    </div>

    <div class="app-container">
        <div class="card">
            <div class="card-header">
                <div class="card-title">ℹ️ Informations</div>
                <span class="card-badge badge-<?php echo $rapport->statut == 1 ? 'success' : 'info'; ?>">
                    <?php echo $rapport->statut == 1 ? 'Validé' : 'Brouillon'; ?>
                </span>
            </div>

            <div style="display:grid;gap:12px">
                <?php if ($rapport->client_nom): ?>
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">CLIENT</div>
                    <div style="font-size:16px;font-weight:700">🏢 <?php echo dol_escape_htmltag($rapport->client_nom); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($rapport->projet_ref): ?>
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">PROJET</div>
                    <div style="font-size:16px;font-weight:700">📁 <?php echo dol_escape_htmltag($rapport->projet_ref); ?></div>
                    <?php if ($rapport->projet_title): ?>
                    <div style="font-size:13px;color:var(--text-light)"><?php echo dol_escape_htmltag($rapport->projet_title); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">DATE</div>
                    <div style="font-size:16px;font-weight:700">📅 <?php echo dol_print_date($db->jdate($rapport->date_rapport), 'day'); ?></div>
                </div>

                <?php if ($rapport->temps_total): ?>
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">TEMPS TOTAL</div>
                    <div style="font-size:16px;font-weight:700">⏱️ <?php echo $rapport->temps_total; ?> heures</div>
                </div>
                <?php endif; ?>

                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">OUVRIER</div>
                    <div style="font-size:16px;font-weight:700">👤 <?php echo dol_escape_htmltag($rapport->firstname.' '.$rapport->lastname); ?></div>
                </div>
            </div>
        </div>

        <?php if ($rapport->description): ?>
        <div class="card">
            <div class="card-title">📝 Description</div>
            <div style="margin-top:12px;line-height:1.6;color:var(--text)">
                <?php echo nl2br(dol_escape_htmltag($rapport->description)); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($photos)): ?>
        <div class="card">
            <div class="card-title">📸 Photos (<?php echo count($photos); ?>)</div>
            <div class="photo-preview" style="margin-top:12px">
                <?php foreach ($photos as $photo):
                    $photo_url = 'photo.php?id='.$photo->rowid;
                ?>
                <div class="photo-preview-item" onclick="window.open('<?php echo $photo_url; ?>', '_blank')">
                    <img src="<?php echo $photo_url; ?>" alt="Photo">
                    <?php if (!empty($photo->categorie)): ?>
                        <span style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.7);color:white;padding:4px 8px;border-radius:4px;font-size:12px;">
                            <?php
                            $cat_icons = ['avant' => '🔵', 'pendant' => '🟡', 'apres' => '🟢'];
                            echo isset($cat_icons[$photo->categorie]) ? $cat_icons[$photo->categorie] : '';
                            echo ' '.ucfirst($photo->categorie);
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <a href="../../rapports/pdf.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-primary">
            <span>📄 Télécharger PDF</span>
        </a>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="../js/app.js"></script>
</body>
</html>
<?php $db->close(); ?>
