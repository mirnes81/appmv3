<?php
/**
 * Page de signature client pour fiche sens de pose
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

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

llxHeader('', 'Signature Client', '');
?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.signature-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}

.signature-header {
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.signature-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.signature-subtitle {
    font-size: 16px;
    opacity: 0.9;
}

.signature-content {
    padding: 40px;
}

.info-box {
    background: #f0f9ff;
    border: 2px solid #0891b2;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #bae6fd;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #0c4a6e;
}

.info-value {
    color: #0f172a;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #0891b2;
    box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
}

.canvas-container {
    text-align: center;
    margin-bottom: 24px;
}

canvas {
    border: 3px solid #0891b2;
    border-radius: 12px;
    cursor: crosshair;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    touch-action: none;
}

.canvas-hint {
    font-size: 14px;
    color: #64748b;
    margin-top: 12px;
}

.button-group {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.btn {
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-clear {
    background: #64748b;
    color: white;
}

.btn-clear:hover {
    background: #475569;
}

.btn-submit {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
}

.btn-submit:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    transform: none;
}

.btn-cancel {
    background: #e2e8f0;
    color: #475569;
}

.btn-cancel:hover {
    background: #cbd5e1;
}
</style>

<div class="signature-container">
    <div class="signature-header">
        <div class="signature-title">✍️ Signature Client</div>
        <div class="signature-subtitle">Fiche de sens de pose</div>
    </div>

    <div class="signature-content">
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Référence :</span>
                <span class="info-value"><?php echo htmlspecialchars($fiche->ref); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Client :</span>
                <span class="info-value"><?php echo htmlspecialchars($fiche->client_name); ?></span>
            </div>
            <?php if ($fiche->site_address): ?>
            <div class="info-row">
                <span class="info-label">Chantier :</span>
                <span class="info-value"><?php echo htmlspecialchars($fiche->site_address); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <form id="signature-form" accept-charset="UTF-8">
            <div class="form-group">
                <label class="form-label">Nom du signataire *</label>
                <input type="text" name="sign_name" id="sign_name" class="form-input"
                       placeholder="Prénom et Nom" required>
            </div>

            <div class="form-group">
                <label class="form-label">Signature * (dessinez votre signature ci-dessous)</label>
                <div class="canvas-container">
                    <canvas id="signature-canvas" width="700" height="200"></canvas>
                    <div class="canvas-hint">
                        Dessinez votre signature avec la souris ou votre doigt
                    </div>
                </div>
            </div>

            <div class="button-group">
                <a href="view.php?id=<?php echo $id; ?>" class="btn btn-cancel">
                    ← Annuler
                </a>
                <button type="button" onclick="clearSignature()" class="btn btn-clear">
                    🗑️ Effacer
                </button>
                <button type="submit" id="submit-btn" class="btn btn-submit" disabled>
                    ✅ Valider la signature
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const canvas = document.getElementById('signature-canvas');
const ctx = canvas.getContext('2d');
const submitBtn = document.getElementById('submit-btn');

let isDrawing = false;
let lastX = 0;
let lastY = 0;
let hasSignature = false;

ctx.strokeStyle = '#0f172a';
ctx.lineWidth = 2;
ctx.lineCap = 'round';
ctx.lineJoin = 'round';

function startDrawing(e) {
    isDrawing = true;
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || e.touches[0].clientX) - rect.left;
    const y = (e.clientY || e.touches[0].clientY) - rect.top;
    lastX = x;
    lastY = y;
}

function draw(e) {
    if (!isDrawing) return;

    e.preventDefault();

    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX || e.touches[0].clientX) - rect.left;
    const y = (e.clientY || e.touches[0].clientY) - rect.top;

    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();

    lastX = x;
    lastY = y;
    hasSignature = true;
    checkFormValidity();
}

function stopDrawing() {
    isDrawing = false;
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

canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
canvas.addEventListener('mouseout', stopDrawing);

canvas.addEventListener('touchstart', startDrawing);
canvas.addEventListener('touchmove', draw);
canvas.addEventListener('touchend', stopDrawing);

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

    const submitBtn = document.getElementById('submit-btn');
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

            submitBtn.innerHTML = '⏳ Envoi ' + chunkNum + '/' + totalChunks + '...';

            const url = 'save_signature.php?id=<?php echo $id; ?>&name=' +
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
                const redirectUrl = result.split('|')[1];
                window.location.href = redirectUrl;
                return;
            }
        }
    } catch (error) {
        alert('❌ Erreur : ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '✅ Valider la signature';
    }
});
</script>

<?php
llxFooter();
?>
