<?php
/**
 * Configuration API pour MV3 Pro Mobile
 * À copier dans: /custom/mv3pro_portail/api_mobile/
 */

define('DOLIBARR_DB_HOST', 'localhost');
define('DOLIBARR_DB_NAME', 'votre_base_dolibarr');
define('DOLIBARR_DB_USER', 'votre_utilisateur');
define('DOLIBARR_DB_PASS', 'votre_mot_de_passe');

define('JWT_SECRET', 'CHANGEZ_CETTE_CLE_SECRETE_PAR_UNE_VALEUR_ALEATOIRE');
define('JWT_EXPIRATION', 86400 * 7);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DOLIBARR_DB_HOST . ';dbname=' . DOLIBARR_DB_NAME . ';charset=utf8mb4',
                DOLIBARR_DB_USER,
                DOLIBARR_DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            exit();
        }
    }

    return $pdo;
}

function generateJWT($userId) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'exp' => time() + JWT_EXPIRATION,
        'iat' => time()
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function verifyJWT($token) {
    $tokenParts = explode('.', $token);

    if (count($tokenParts) !== 3) {
        return false;
    }

    $header = base64_decode($tokenParts[0]);
    $payload = base64_decode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    if ($base64UrlSignature !== $signatureProvided) {
        return false;
    }

    $payloadData = json_decode($payload, true);

    if ($payloadData['exp'] < time()) {
        return false;
    }

    return $payloadData['user_id'];
}

function requireAuth() {
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit();
    }

    $userId = verifyJWT($token);

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit();
    }

    return $userId;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function jsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit();
}
