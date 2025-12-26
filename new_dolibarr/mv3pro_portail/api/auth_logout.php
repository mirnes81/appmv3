<?php
/**
 * Endpoint déconnexion
 *
 * POST /auth/logout
 * Header: X-Auth-Token: ...
 * Returns: {"success": true}
 */

require_once __DIR__ . '/cors_config.php';
header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

echo json_encode([
    'success' => true,
    'message' => 'Déconnexion réussie'
]);
