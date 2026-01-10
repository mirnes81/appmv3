<?php
/**
 * Monitor Live Upload avec Session Dolibarr
 * Utilise directement la session Dolibarr pour l'authentification
 */

// Charger Dolibarr
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists(__DIR__ . "/../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../main.inc.php";
}
if (!$res && file_exists(__DIR__ . "/../../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../../main.inc.php";
}

if (!$res) {
    die("Erreur: Impossible de charger Dolibarr");
}

global $db, $conf, $user;

// Vérifier l'authentification
$isAuth = ($user && $user->id > 0);
$userName = $isAuth ? $user->firstname . ' ' . $user->lastname : 'Non connecté';
$userLogin = $isAuth ? $user->login : '';
$userId = $isAuth ? $user->id : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Upload - Session Dolibarr</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .subtitle {
            color: rgba(255,255,255,0.9);
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-status {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .auth-status.connected {
            border-left: 5px solid #10b981;
        }
        .auth-status.disconnected {
            border-left: 5px solid #ef4444;
        }
        .auth-icon {
            font-size: 40px;
        }
        .auth-info h3 {
            color: #1f2937;
            margin-bottom: 5px;
        }
        .auth-info p {
            color: #6b7280;
            font-size: 14px;
        }
        .panel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .panel h2 {
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        .status.waiting {
            background: #f59e0b;
        }
        .status.success {
            background: #10b981;
        }
        .status.error {
            background: #ef4444;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #4b5563;
            font-weight: 600;
        }
        input[type="number"], input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        input[type="number"]:focus, input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .log-container {
            background: #1f2937;
            color: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .log-entry {
            margin-bottom: 8px;
            padding: 8px;
            border-left: 3px solid transparent;
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
        }
        .log-entry.info {
            border-left-color: #3b82f6;
        }
        .log-entry.success {
            border-left-color: #10b981;
        }
        .log-entry.error {
            border-left-color: #ef4444;
        }
        .log-entry.warning {
            border-left-color: #f59e0b;
        }
        .log-time {
            color: #9ca3af;
            font-size: 11px;
            margin-right: 10px;
        }
        .response-panel {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #667eea;
        }
        .response-panel pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .clear-btn {
            background: #6b7280;
            padding: 8px 16px;
            width: auto;
            display: inline-block;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .warning-box p {
            color: #92400e;
            margin: 0;
        }
        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-box p {
            color: #991b1b;
            margin: 0;
        }
        .error-box a {
            color: #991b1b;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Upload Photos</h1>
        <p class="subtitle">Monitor Live avec Session Dolibarr</p>

        <?php if ($isAuth): ?>
            <div class="auth-status connected">
                <div class="auth-icon">✅</div>
                <div class="auth-info">
                    <h3>Connecté</h3>
                    <p><strong><?php echo htmlspecialchars($userName); ?></strong> (<?php echo htmlspecialchars($userLogin); ?>)</p>
                    <p>User ID: <?php echo $userId; ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="auth-status disconnected">
                <div class="auth-icon">❌</div>
                <div class="auth-info">
                    <h3>Non connecté</h3>
                    <p>Vous devez être connecté à Dolibarr pour utiliser ce monitor</p>
                </div>
            </div>

            <div class="error-box">
                <p><strong>⚠️ Vous devez d'abord vous connecter à Dolibarr</strong></p>
                <p>Cliquez ici pour vous connecter: <a href="/index.php" target="_blank">Se connecter</a></p>
                <p>Puis rechargez cette page.</p>
            </div>
        <?php endif; ?>

        <?php if ($isAuth): ?>
        <div class="panel">
            <h2>
                <span class="status waiting" id="status"></span>
                Test d'Upload
            </h2>

            <form id="uploadForm">
                <div class="form-group">
                    <label for="eventId">Event ID</label>
                    <input type="number" id="eventId" name="eventId" value="74049" required>
                </div>

                <div class="form-group">
                    <label for="file">Photo (Image uniquement)</label>
                    <input type="file" id="file" name="file" accept="image/*" required>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar"></div>
                </div>

                <button type="submit" id="submitBtn">
                    📤 Uploader la Photo
                </button>
            </form>
        </div>

        <div class="panel">
            <h2>📊 Statistiques</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value" id="totalUploads">0</div>
                    <div class="stat-label">Total Uploads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="successCount">0</div>
                    <div class="stat-label">Succès</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="errorCount">0</div>
                    <div class="stat-label">Erreurs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="avgTime">0ms</div>
                    <div class="stat-label">Temps Moyen</div>
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>📋 Logs en Direct</h2>
            <button class="clear-btn" onclick="clearLogs()">🗑️ Effacer les logs</button>
            <div class="log-container" id="logContainer">
                <div class="log-entry info">
                    <span class="log-time">[00:00:00]</span>
                    <span>En attente d'un upload...</span>
                </div>
            </div>
        </div>

        <div class="panel" id="responsePanel" style="display: none;">
            <h2>📦 Dernière Réponse</h2>
            <div class="response-panel">
                <pre id="responseContent"></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($isAuth): ?>
    <script>
        const API_URL = '/custom/mv3pro_portail/api/v1/planning_upload_photo_session.php';

        let stats = {
            total: 0,
            success: 0,
            error: 0,
            times: []
        };

        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const time = new Date().toLocaleTimeString();
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.innerHTML = `<span class="log-time">[${time}]</span><span>${message}</span>`;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateStatus(type) {
            const status = document.getElementById('status');
            status.className = `status ${type}`;
        }

        function updateStats() {
            document.getElementById('totalUploads').textContent = stats.total;
            document.getElementById('successCount').textContent = stats.success;
            document.getElementById('errorCount').textContent = stats.error;

            if (stats.times.length > 0) {
                const avg = stats.times.reduce((a, b) => a + b, 0) / stats.times.length;
                document.getElementById('avgTime').textContent = Math.round(avg) + 'ms';
            }
        }

        function showResponse(data) {
            const panel = document.getElementById('responsePanel');
            const content = document.getElementById('responseContent');
            content.textContent = JSON.stringify(data, null, 2);
            panel.style.display = 'block';
        }

        function clearLogs() {
            const logContainer = document.getElementById('logContainer');
            logContainer.innerHTML = '<div class="log-entry info"><span class="log-time">[' +
                new Date().toLocaleTimeString() + ']</span><span>Logs effacés</span></div>';
        }

        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const progressBar = document.getElementById('progressBar');
            const eventId = document.getElementById('eventId').value;
            const fileInput = document.getElementById('file');
            const file = fileInput.files[0];

            if (!file) {
                addLog('❌ Aucun fichier sélectionné', 'error');
                return;
            }

            submitBtn.disabled = true;
            updateStatus('waiting');
            progressBar.style.width = '0%';

            addLog(`🚀 Début de l'upload: ${file.name} (${(file.size / 1024).toFixed(2)} KB)`, 'info');
            addLog(`📋 Event ID: ${eventId}`, 'info');
            addLog(`👤 Utilisateur: <?php echo addslashes($userName); ?> (ID: <?php echo $userId; ?>)`, 'info');

            const formData = new FormData();
            formData.append('file', file);
            formData.append('id', eventId);

            const startTime = Date.now();

            try {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        progressBar.style.width = percent + '%';
                        addLog(`📊 Progression: ${Math.round(percent)}%`, 'info');
                    }
                });

                xhr.addEventListener('load', () => {
                    const elapsed = Date.now() - startTime;
                    stats.total++;
                    stats.times.push(elapsed);

                    if (xhr.status === 201 || xhr.status === 200) {
                        stats.success++;
                        updateStatus('success');
                        addLog(`✅ Upload réussi en ${elapsed}ms`, 'success');

                        try {
                            const response = JSON.parse(xhr.responseText);
                            addLog(`📦 Réponse: ${JSON.stringify(response)}`, 'success');
                            showResponse(response);
                        } catch (e) {
                            addLog(`⚠️ Impossible de parser la réponse JSON`, 'warning');
                            addLog(`📄 Réponse brute: ${xhr.responseText}`, 'info');
                            showResponse({ raw: xhr.responseText });
                        }
                    } else {
                        stats.error++;
                        updateStatus('error');
                        addLog(`❌ Erreur HTTP ${xhr.status}`, 'error');
                        addLog(`📄 Réponse: ${xhr.responseText}`, 'error');
                        showResponse({ error: xhr.responseText, status: xhr.status });
                    }

                    updateStats();
                    submitBtn.disabled = false;
                    progressBar.style.width = '100%';
                });

                xhr.addEventListener('error', () => {
                    stats.total++;
                    stats.error++;
                    updateStatus('error');
                    addLog(`❌ Erreur réseau`, 'error');
                    updateStats();
                    submitBtn.disabled = false;
                });

                addLog(`🌐 Envoi vers: ${API_URL}`, 'info');
                addLog(`🔐 Utilisation de la session Dolibarr active`, 'info');
                xhr.open('POST', API_URL);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                // Les cookies de session sont automatiquement inclus
                xhr.withCredentials = true;

                xhr.send(formData);

            } catch (error) {
                stats.total++;
                stats.error++;
                updateStatus('error');
                addLog(`❌ Exception: ${error.message}`, 'error');
                updateStats();
                submitBtn.disabled = false;
            }
        });

        // Log initial
        addLog('🎯 Monitor prêt. Sélectionnez une image et cliquez sur "Uploader"', 'info');
        addLog('✅ Session Dolibarr active détectée', 'success');
    </script>
    <?php endif; ?>
</body>
</html>
