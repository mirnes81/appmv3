<?php
/**
 * API v1 - Documentation Index
 *
 * Page d'accueil de l'API avec liste des endpoints disponibles
 */

require_once __DIR__ . '/_bootstrap.php';

// Page HTML simple avec la documentation
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API v1 - MV3 PRO Portail</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0891b2;
            margin-bottom: 10px;
        }
        .version {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        h2 {
            color: #0891b2;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        .endpoint {
            background: #f9fafb;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #0891b2;
            border-radius: 4px;
        }
        .method {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            margin-right: 10px;
        }
        .method.get { background: #10b981; color: white; }
        .method.post { background: #f59e0b; color: white; }
        .endpoint-url {
            font-family: 'Courier New', monospace;
            color: #1f2937;
        }
        .endpoint-desc {
            color: #6b7280;
            margin-top: 5px;
            font-size: 14px;
        }
        .auth-modes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .auth-card {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #bae6fd;
        }
        .auth-card h3 {
            color: #0891b2;
            margin-bottom: 10px;
        }
        .status-ok {
            color: #10b981;
            font-weight: bold;
        }
        .links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .links a {
            color: #0891b2;
            text-decoration: none;
            margin-right: 20px;
        }
        .links a:hover {
            text-decoration: underline;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 API v1 - MV3 PRO Portail</h1>
        <div class="version">Version 1.0.0 | Module Dolibarr | JSON REST API</div>

        <p><span class="status-ok">✓ API Opérationnelle</span></p>

        <h2>📋 Endpoints Disponibles</h2>

        <h3 style="margin-top: 20px; color: #374151;">Authentification</h3>

        <div class="endpoint">
            <span class="method get">GET</span>
            <span class="endpoint-url">/api/v1/me.php</span>
            <div class="endpoint-desc">Informations de l'utilisateur connecté</div>
        </div>

        <h3 style="margin-top: 20px; color: #374151;">Planning</h3>

        <div class="endpoint">
            <span class="method get">GET</span>
            <span class="endpoint-url">/api/v1/planning.php?from=YYYY-MM-DD&to=YYYY-MM-DD</span>
            <div class="endpoint-desc">Liste des événements du planning pour une période donnée</div>
        </div>

        <h3 style="margin-top: 20px; color: #374151;">Rapports</h3>

        <div class="endpoint">
            <span class="method get">GET</span>
            <span class="endpoint-url">/api/v1/rapports.php?limit=20&page=1</span>
            <div class="endpoint-desc">Liste paginée des rapports journaliers</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span>
            <span class="endpoint-url">/api/v1/rapports_create.php</span>
            <div class="endpoint-desc">Créer un nouveau rapport (JSON body)</div>
        </div>

        <h2>🔐 Modes d'Authentification Supportés</h2>

        <div class="auth-modes">
            <div class="auth-card">
                <h3>Mode A: Session Dolibarr</h3>
                <p>Utilisateur connecté via l'interface Dolibarr standard.</p>
                <p style="margin-top: 10px;"><strong>Header:</strong> Aucun (cookie session)</p>
                <p><strong>Usage:</strong> Admin, Chef</p>
            </div>

            <div class="auth-card">
                <h3>Mode B: Token Mobile</h3>
                <p>Token obtenu via login mobile indépendant.</p>
                <p style="margin-top: 10px;"><strong>Header:</strong> <code>Authorization: Bearer &lt;token&gt;</code></p>
                <p><strong>Usage:</strong> Ouvriers, App mobile</p>
            </div>

            <div class="auth-card">
                <h3>Mode C: Token API</h3>
                <p>Token API ancien (compatibilité).</p>
                <p style="margin-top: 10px;"><strong>Header:</strong> <code>X-Auth-Token: &lt;token&gt;</code></p>
                <p><strong>Usage:</strong> Intégrations externes</p>
            </div>
        </div>

        <h2>📚 Documentation</h2>

        <p>Consultez les documents suivants pour plus d'informations:</p>

        <div class="links">
            <a href="README.md" target="_blank">📖 Documentation complète</a>
            <a href="MIGRATION.md" target="_blank">🔄 Guide de migration</a>
            <a href="/custom/mv3pro_portail/" target="_blank">🏠 Retour module</a>
        </div>

        <h2>🧪 Test Rapide</h2>

        <p>Pour tester l'API, ouvrez la console développeur et exécutez:</p>

        <pre style="background: #1f2937; color: #f9fafb; padding: 20px; border-radius: 6px; overflow-x: auto; margin-top: 10px;">
// Test avec session Dolibarr active
fetch('/custom/mv3pro_portail/api/v1/me.php')
  .then(r => r.json())
  .then(console.log);</pre>

        <h2>✅ Status API</h2>

        <div style="background: #f0fdf4; padding: 20px; border-radius: 6px; border: 1px solid #bbf7d0; margin-top: 20px;">
            <p><strong>Base URL:</strong> <code>/custom/mv3pro_portail/api/v1/</code></p>
            <p style="margin-top: 10px;"><strong>Format:</strong> JSON (UTF-8)</p>
            <p><strong>CORS:</strong> Configuré</p>
            <p><strong>Endpoints actifs:</strong> 4</p>
            <p><strong>Compatibilité:</strong> Anciens endpoints préservés</p>
        </div>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 14px;">
            <p>MV3 PRO Portail - Module Dolibarr v1.1.0</p>
        </div>
    </div>
</body>
</html>
