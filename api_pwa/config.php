<?php
/**
 * Configuration de la connexion MySQL pour la PWA
 */

// Charger la configuration Dolibarr
$dolibarr_main_document_root = '/var/www/html/dolibarr/htdocs';
if (file_exists($dolibarr_main_document_root . '/master.inc.php')) {
    require_once $dolibarr_main_document_root . '/master.inc.php';
} else {
    // Configuration manuelle si Dolibarr n'est pas trouvé
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'dolibarr');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Connexion à la base de données
 */
function getDB() {
    global $dolibarr_main_db_host, $dolibarr_main_db_name,
           $dolibarr_main_db_user, $dolibarr_main_db_pass;

    try {
        $host = $dolibarr_main_db_host ?? DB_HOST ?? 'localhost';
        $dbname = $dolibarr_main_db_name ?? DB_NAME ?? 'dolibarr';
        $user = $dolibarr_main_db_user ?? DB_USER ?? 'root';
        $pass = $dolibarr_main_db_pass ?? DB_PASS ?? '';

        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur de connexion à la base de données']);
        exit;
    }
}

/**
 * Vérifier le token d'authentification
 */
function verifyToken() {
    $headers = getallheaders();
    $token = null;

    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Token manquant']);
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.user_id, u.email, u.dolibarr_user_id
        FROM llx_mv3_mobile_sessions s
        JOIN llx_mv3_mobile_users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $session = $stmt->fetch();

    if (!$session) {
        http_response_code(401);
        echo json_encode(['error' => 'Session invalide ou expirée']);
        exit;
    }

    return $session;
}

/**
 * Générer un token sécurisé
 */
function generateToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Réponse JSON
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Récupérer le corps de la requête JSON
 */
function getRequestBody() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}
