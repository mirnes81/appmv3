<?php
/**
 * Fichier de test pour vérifier que l'API fonctionne
 * Accès: https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/test.php
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Répondre aux requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test de la connexion à la base de données
require_once 'config.php';

$response = [
    'status' => 'ok',
    'message' => 'API MV3 Pro Mobile fonctionne',
    'timestamp' => date('c'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'],
        'http_host' => $_SERVER['HTTP_HOST'],
    ],
    'headers_sent' => headers_list(),
];

// Test de connexion DB
try {
    $db = getDB();
    $response['database'] = 'connected';

    // Compter les utilisateurs
    $stmt = $db->query("SELECT COUNT(*) as count FROM llx_user WHERE statut = 1");
    $result = $stmt->fetch();
    $response['active_users'] = $result['count'];

} catch (Exception $e) {
    $response['database'] = 'error';
    $response['database_error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
