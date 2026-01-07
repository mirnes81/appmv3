<?php
/**
 * Fichier de test interne - NE PAS UTILISER EN PRODUCTION
 *
 * Ce fichier permet de tester rapidement les fonctions du bootstrap
 * Accessible uniquement en local ou avec IP autorisée
 */

// Sécurité: bloquer en production
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Accès refusé. Tests uniquement en local.');
}

require_once __DIR__ . '/_bootstrap.php';

// Test d'authentification
$auth = require_auth(false);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API v1 - Tests</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #1f2937;
            color: #f9fafb;
        }
        .section {
            background: #374151;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        pre {
            background: #1f2937;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        h2 { color: #60a5fa; }
    </style>
</head>
<body>
    <h1>🧪 Tests API v1</h1>

    <div class="section">
        <h2>1. Bootstrap</h2>
        <?php if (isset($db) && is_object($db)): ?>
            <p class="success">✓ Dolibarr chargé</p>
            <p>Database: <?= get_class($db) ?></p>
        <?php else: ?>
            <p class="error">✗ Erreur chargement Dolibarr</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>2. Authentification</h2>
        <?php if ($auth): ?>
            <p class="success">✓ Utilisateur authentifié</p>
            <pre><?= json_encode($auth, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
        <?php else: ?>
            <p class="warning">⚠ Pas d'utilisateur connecté</p>
            <p>Pour tester:</p>
            <ul>
                <li>Connectez-vous à Dolibarr d'abord</li>
                <li>Ou utilisez: <code>Authorization: Bearer &lt;token&gt;</code></li>
                <li>Ou utilisez: <code>X-Auth-Token: &lt;token&gt;</code></li>
            </ul>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>3. Helpers disponibles</h2>
        <p class="success">✓ json_ok($data, $code)</p>
        <p class="success">✓ json_error($message, $code, $http_code)</p>
        <p class="success">✓ require_method($methods)</p>
        <p class="success">✓ get_param($name, $default, $method)</p>
        <p class="success">✓ get_json_body($required)</p>
        <p class="success">✓ require_auth($required)</p>
        <p class="success">✓ require_rights($rights, $auth_data)</p>
        <p class="success">✓ require_param($value, $name)</p>
    </div>

    <div class="section">
        <h2>4. Configuration</h2>
        <pre><?php
        echo "Entity: " . ($conf->entity ?? 'N/A') . "\n";
        echo "DB Prefix: " . MAIN_DB_PREFIX . "\n";
        echo "Dolibarr Root: " . DOL_DOCUMENT_ROOT . "\n";
        ?></pre>
    </div>

    <div class="section">
        <h2>5. Tests Endpoints</h2>
        <p>Ouvrez la console développeur et exécutez:</p>
        <pre>
// Test ME
fetch('/custom/mv3pro_portail/api/v1/me.php')
  .then(r => r.json())
  .then(console.log);

// Test Planning (aujourd'hui)
const today = new Date().toISOString().split('T')[0];
fetch(`/custom/mv3pro_portail/api/v1/planning.php?from=${today}&to=${today}`)
  .then(r => r.json())
  .then(console.log);

// Test Rapports
fetch('/custom/mv3pro_portail/api/v1/rapports.php?limit=5')
  .then(r => r.json())
  .then(console.log);
        </pre>
    </div>

    <div class="section">
        <h2>6. CORS</h2>
        <?php if (function_exists('setCorsHeaders')): ?>
            <p class="success">✓ Fonctions CORS disponibles</p>
        <?php else: ?>
            <p class="error">✗ CORS non configuré</p>
        <?php endif; ?>
    </div>

    <p style="margin-top: 40px; text-align: center; opacity: 0.5;">
        ⚠ Ce fichier est pour les tests locaux uniquement
    </p>
</body>
</html>
