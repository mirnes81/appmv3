<?php
/**
 * Liste matériel - Mobile avec scan QR
 */



require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

$user_id = $user->id;
$search = GETPOST('search', 'alpha');
$action = GETPOST('action', 'alpha');

// Mode reprise : afficher seulement le matériel des collègues
$mode_reprendre = ($action === 'reprendre');

$sql = "SELECT m.rowid, m.ref, m.nom as label, m.statut, m.qrcode,
               u.rowid as user_id,
               u.firstname as user_firstname,
               u.lastname as user_lastname,
               p.ref as projet_ref,
               p.title as projet_title
        FROM ".MAIN_DB_PREFIX."mv3_materiel m
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = m.fk_user_assigne
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = m.fk_projet_assigne
        WHERE m.entity IN (0,".$conf->entity.")";

if ($mode_reprendre) {
    // Afficher seulement le matériel assigné aux collègues (pas à moi, pas disponible)
    $sql .= " AND m.fk_user_assigne IS NOT NULL AND m.fk_user_assigne != ".(int)$user_id;
    $sql .= " AND m.statut IN ('en_service', 'disponible')";
}

if ($search) {
    $sql .= " AND (m.ref LIKE '%".$db->escape($search)."%' OR m.nom LIKE '%".$db->escape($search)."%')";
}

$sql .= " ORDER BY m.ref LIMIT 100";
$resql = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Matériel - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
</head>
<body>
    <div class="app-header">
        <div>
            <div class="app-header-title"><?php echo $mode_reprendre ? '🔄 Reprendre matériel' : '🛠️ Matériel'; ?></div>
            <div class="app-header-subtitle"><?php echo $mode_reprendre ? 'Matériel des collègues' : 'Liste du matériel'; ?></div>
        </div>
        <?php if (!$mode_reprendre): ?>
        <button onclick="startQRScan()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer">📷</button>
        <?php endif; ?>
    </div>

    <div class="app-container">
        <div class="card">
            <input type="text" class="form-input" placeholder="🔍 Rechercher..." id="searchInput" value="<?php echo dol_escape_htmltag($search); ?>">
        </div>

        <?php
        if ($resql && $db->num_rows($resql) > 0) {
            while ($materiel = $db->fetch_object($resql)) {
                $statut_class = 'success';
                $statut_text = '🟢 Disponible';
                $statut_icon = '🟢';

                if ($materiel->statut == 'en_service') {
                    $statut_class = 'warning';
                    $statut_text = '🟡 En service';
                    $statut_icon = '🟡';
                } elseif ($materiel->statut == 'maintenance') {
                    $statut_class = 'danger';
                    $statut_text = '🔴 Maintenance';
                    $statut_icon = '🔴';
                } elseif ($materiel->statut == 'hors_service') {
                    $statut_class = 'secondary';
                    $statut_text = '⚫ Hors service';
                    $statut_icon = '⚫';
                }

                $url = $mode_reprendre ? 'view.php?id='.$materiel->rowid.'&mode=reprendre' : 'view.php?id='.$materiel->rowid;
                echo '<a href="'.$url.'" class="list-item">';
                echo '<div class="list-item-icon">🛠️</div>';
                echo '<div class="list-item-content">';
                echo '<div class="list-item-title">'.dol_escape_htmltag($materiel->ref).'</div>';
                echo '<div class="list-item-subtitle">'.dol_escape_htmltag($materiel->label).'</div>';

                // Afficher qui a le matériel si affecté
                if ($materiel->user_firstname || $materiel->user_lastname) {
                    echo '<div class="list-item-meta" style="margin-top:4px">';
                    echo '<span style="font-size:11px;color:#f59e0b;font-weight:600">';
                    echo '👤 '.dol_escape_htmltag($materiel->user_firstname.' '.$materiel->user_lastname);
                    if ($materiel->projet_title) {
                        echo ' • 🏗️ '.dol_escape_htmltag($materiel->projet_ref ? $materiel->projet_ref : $materiel->projet_title);
                    }
                    echo '</span>';
                    echo '</div>';
                }

                echo '<div class="list-item-meta">';
                echo '<span class="card-badge badge-'.$statut_class.'">'.$statut_text.'</span>';
                if ($mode_reprendre) {
                    echo ' <span style="margin-left:8px;background:#10b981;color:white;padding:3px 8px;border-radius:12px;font-size:11px;font-weight:700">🔄 Disponible</span>';
                }
                echo '</div>';
                echo '</div>';
                echo '<div style="color:var(--text-light)">→</div>';
                echo '</a>';
            }
        } else {
            echo '<div class="empty-state">';
            echo '<div class="empty-state-icon">📭</div>';
            echo '<div class="empty-state-text">Aucun matériel trouvé</div>';
            echo '</div>';
        }
        ?>

        <div id="qrScanModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.9);z-index:9999">
            <div style="position:relative;height:100%">
                <button onclick="stopQRScan()" style="position:absolute;top:20px;right:20px;background:white;border:none;width:40px;height:40px;border-radius:50%;font-size:24px;cursor:pointer;z-index:10000">×</button>
                <video id="qrVideo" class="qr-scanner" autoplay playsinline style="width:100%;height:100%;object-fit:cover"></video>
                <div style="position:absolute;bottom:40px;left:0;right:0;text-align:center;color:white;font-size:16px;font-weight:700">
                    Scannez le QR code du matériel
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="../js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const value = e.target.value;
            if (value.length >= 2 || value.length === 0) {
                window.location.href = '?search=' + encodeURIComponent(value);
            }
        });

        let scanning = false;
        let videoStream = null;

        async function startQRScan() {
            const modal = document.getElementById('qrScanModal');
            const video = document.getElementById('qrVideo');
            modal.style.display = 'block';
            scanning = true;

            try {
                videoStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' }
                });
                video.srcObject = videoStream;
                video.play();
                scanQR(video);
            } catch (err) {
                alert('Impossible d\'accéder à la caméra');
                stopQRScan();
            }
        }

        function scanQR(video) {
            if (!scanning) {
                return;
            }

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0);

                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);

                if (code) {
                    stopQRScan();
                    window.location.href = 'view.php?qrcode=' + encodeURIComponent(code.data);
                    return;
                }
            }

            requestAnimationFrame(() => scanQR(video));
        }

        function stopQRScan() {
            scanning = false;
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
                videoStream = null;
            }
            document.getElementById('qrScanModal').style.display = 'none';
        }
    </script>
</body>
</html>
<?php $db->close(); ?>
