<?php
/**
 * API Helpers
 * Fonctions utilitaires pour les endpoints API
 */

function setupApiHeaders($allowedMethods = 'GET, POST, PUT, DELETE, OPTIONS')
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: ' . $allowedMethods);
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function jsonResponse($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonSuccess($data = [], $message = null)
{
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    jsonResponse($response, 200);
}

function jsonError($message, $code = 400, $extraData = [])
{
    $response = [
        'success' => false,
        'message' => $message
    ];
    if (!empty($extraData)) {
        $response = array_merge($response, $extraData);
    }
    jsonResponse($response, $code);
}

function getJsonInput()
{
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!$data && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
    }

    return $data ?: [];
}

function getBearerToken()
{
    $token = null;

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }

    return $token;
}

function requireJsonInput($requiredFields = [])
{
    $data = getJsonInput();

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            jsonError("Le champ '$field' est requis", 400);
        }
    }

    return $data;
}
