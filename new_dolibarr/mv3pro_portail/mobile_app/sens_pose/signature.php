<?php
/**
 * Page de signature client pour fiche sens de pose - Version Mobile
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$id = GETPOST('id', 'int');

if (!$id) {
    header('Location: list.php');
    exit;
}

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".(int)$id;
$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) == 0) {
    header('Location: list.php');
    exit;
}

$fiche = $db->fetch_object($resql);

if ($fiche->statut == 'signe') {
    header('Location: view.php?id='.$id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Signature - <?php echo dol_escape_htmltag($fiche->ref); ?></title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
    .signature-canvas-container {
        position: relative;
        width: 100%;
        margin: 16px 0;
        background: white;
        border: 3px solid #0891b2;
        border-radius: 12px;
        overflow: hidden;
    }

    #signature-canvas {
        display: block;
        width: 100%;
        height: 200px;
        touch-action: none;
        cursor: crosshair;
    }

    .canvas-hint {
        padding: 8px 12px;
        background: #f0f9ff;
        border-top: 2px solid #0891b2;
        font-size: 12px;
        color: #0c4a6e;
        text-align: center;
    }

    .button-row {
        display: flex;
        gap: 8px;
        margin-top: 16px;
    }

    .button-row .btn {
        flex: 1;
        text-align: center;
        padding: 14px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 15px;
        border: none;
        cursor: pointer;
    }

    .btn-clear {
        background: #64748b;
        color: white;
    }

    .btn-submit {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-submit:disabled {
        background: #cbd5e1;
        color: #94a3b8;
    }
    </style>
</head>
<body>
    <div class="app-header">
        <a href="view.php?id=<?php echo $id; ?>" class="app-header-back">←</a>
        <div>
            <div class="app-header-title">✍️ Signature</div>
            <div class="app-header-subtitle"><?php echo dol_escape_htmltag($fiche->ref); ?></div>
        </div>
    </div>

    <div class="app-container" style="padding-bottom: 20px;">
        <div class="card" style="background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); color: white;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Client</div>
            <div style="font-size: 18px; font-weight: 700;"><?php echo htmlspecialchars($fiche->client_name); ?></div>
            <?php if ($fiche->site_address): ?>
            <div style="font-size: 13px; margin-top: 8px; opacity: 0.9;">
                📍 <?php echo htmlspecialchars($fiche->site_address); ?>
            </div>
            <?php endif; ?>
        </div>

        <form id="signature-form" accept-charset="UTF-8">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">✍️ Informations signataire</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nom du signataire *</label>
                    <input type="text"
                           name="sign_name"
                           id="sign_name"
                           class="form-input"
                           placeholder="Prénom et Nom"
                           required
                           autocomplete="name">
                </div>

                <div class="form-group">
                    <label class="form-label">Signature * (dessinez ci-dessous)</label>
                    <div class="signature-canvas-container">
                        <canvas id="signature-canvas"></canvas>
                        <div class="canvas-hint">
                            ✏️ Dessinez votre signature avec votre doigt
                        </div>
                    </div>
                </div>

                <div class="button-row">
                    <button type="button" onclick="clearSignature()" class="btn btn-clear">
                        🗑️ Effacer
                    </button>
                    <button type="submit" id="submit-btn" class="btn btn-submit" disabled>
                        ✅ Valider
                    </button>
                </div>
            </div>
        </form>

        <div class="card" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px;">
            <div style="font-size: 13px; color: #92400e;">
                <strong>⚠️ Important :</strong> Cette signature engage le client et confirme la validation des informations.
            </div>
        </div>
    </div>

    <script>
    const canvas = document.getElementById('signature-canvas');
    const ctx = canvas.getContext('2d');
    const submitBtn = document.getElementById('submit-btn');
    const container = canvas.parentElement;

    // Set canvas size based on container
    function resizeCanvas() {
        const rect = container.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = 200;

        ctx.strokeStyle = '#0f172a';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;
    let hasSignature = false;

    function getCoordinates(e) {
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches ? e.touches[0] : e;
        return {
            x: touch.clientX - rect.left,
            y: touch.clientY - rect.top
        };
    }

    function startDrawing(e) {
        e.preventDefault();
        isDrawing = true;
        const coords = getCoordinates(e);
        lastX = coords.x;
        lastY = coords.y;
    }

    function draw(e) {
        if (!isDrawing) {
            return;
        }
        e.preventDefault();

        const coords = getCoordinates(e);

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(coords.x, coords.y);
        ctx.stroke();

        lastX = coords.x;
        lastY = coords.y;
        hasSignature = true;
        checkFormValidity();
    }

    function stopDrawing(e) {
        if (isDrawing) {
            e.preventDefault();
            isDrawing = false;
        }
    }

    function clearSignature() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasSignature = false;
        checkFormValidity();
    }

    function checkFormValidity() {
        const nameField = document.getElementById('sign_name');
        const isValid = hasSignature && nameField.value.trim().length > 0;
        submitBtn.disabled = !isValid;
    }

    // Mouse events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);

    // Touch events
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing, { passive: false });

    document.getElementById('sign_name').addEventListener('input', checkFormValidity);

    document.getElementById('signature-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!hasSignature) {
            alert('⚠️ Veuillez signer avant de valider');
            return;
        }

        const signatureName = document.getElementById('sign_name').value.trim();
        if (!signatureName) {
            alert('⚠️ Veuillez entrer votre nom');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Enregistrement...';

        try {
            const signatureData = canvas.toDataURL('image/png');
            const base64Data = signatureData.replace(/^data:image\/png;base64,/, '');

            const chunkSize = 4000;
            const totalChunks = Math.ceil(base64Data.length / chunkSize);

            for (let i = 0; i < totalChunks; i++) {
                const chunk = base64Data.substr(i * chunkSize, chunkSize);
                const chunkNum = i + 1;

                submitBtn.innerHTML = '⏳ Envoi ' + chunkNum + '/' + totalChunks;

                const url = '../../sens_pose/save_signature.php?id=<?php echo $id; ?>&name=' +
                            encodeURIComponent(signatureName) +
                            '&chunk=' + chunkNum +
                            '&total=' + totalChunks +
                            '&data=' + encodeURIComponent(chunk);

                const response = await fetch(url, { method: 'GET' });
                const result = await response.text();

                if (result.startsWith('ERROR')) {
                    throw new Error(result.split('|')[1] || 'Erreur inconnue');
                }

                if (chunkNum === totalChunks && result.startsWith('SUCCESS')) {
                    window.location.href = 'view.php?id=<?php echo $id; ?>&signed=1';
                    return;
                }
            }
        } catch (error) {
            alert('❌ Erreur : ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '✅ Valider';
        }
    });
    </script>
</body>
</html>
