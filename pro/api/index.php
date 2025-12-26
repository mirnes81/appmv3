<?php
/**
 * Proxy API pour MV3 PRO PWA
 *
 * Ce proxy forward les requêtes depuis https://app.mv-3pro.ch/pro/api/
 * vers https://crm.mv-3pro.ch/custom/mv3pro_portail/api/
 *
 * Objectif: éviter les problèmes CORS en servant l'API sur le même domaine que la PWA
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$DOLIBARR_API_URL = 'https://crm.mv-3pro.ch/custom/mv3pro_portail/api';

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/pro/api', '', $path);

$target_url = $DOLIBARR_API_URL . $path;

if ($_SERVER['QUERY_STRING']) {
    $target_url .= '?' . $_SERVER['QUERY_STRING'];
}

$method = $_SERVER['REQUEST_METHOD'];

$headers = [];
foreach (getallheaders() as $name => $value) {
    if (strtolower($name) === 'host') continue;
    if (strtolower($name) === 'content-length') continue;
    $headers[] = "$name: $value";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

if ($method === 'POST' || $method === 'PUT') {
    $input = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
} elseif ($method === 'DELETE') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
}

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

if ($curl_error) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Erreur proxy',
        'message' => $curl_error,
        'target' => $target_url
    ]);
    exit;
}

http_response_code($http_code);
echo $response;
