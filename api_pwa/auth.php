<?php
/**
 * API d'authentification
 * Endpoints: login, logout, verify
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'login';

try {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
        case 'logout':
            handleLogout();
            break;
        case 'verify':
            handleVerify();
            break;
        default:
            jsonResponse(['error' => 'Action inconnue'], 400);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

/**
 * Connexion utilisateur
 */
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $data = getRequestBody();
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        jsonResponse(['error' => 'Email et mot de passe requis'], 400);
    }

    $db = getDB();

    // Vérifier l'utilisateur
    $stmt = $db->prepare("
        SELECT id, email, password_hash, dolibarr_user_id, preferences
        FROM llx_mv3_mobile_users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        jsonResponse(['error' => 'Identifiants incorrects'], 401);
    }

    // Créer une session
    $token = generateToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt = $db->prepare("
        INSERT INTO llx_mv3_mobile_sessions (user_id, token, expires_at, device_info, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'],
        $token,
        $expiresAt,
        json_encode([
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]),
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);

    // Mettre à jour last_sync
    $stmt = $db->prepare("UPDATE llx_mv3_mobile_users SET last_sync = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'dolibarr_user_id' => $user['dolibarr_user_id'],
            'preferences' => json_decode($user['preferences'], true)
        ],
        'token' => $token,
        'expires_at' => $expiresAt
    ]);
}

/**
 * Déconnexion
 */
function handleLogout() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $session = verifyToken();

    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';
    preg_match('/Bearer\s+(.*)$/i', $auth, $matches);
    $token = $matches[1] ?? null;

    if ($token) {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM llx_mv3_mobile_sessions WHERE token = ?");
        $stmt->execute([$token]);
    }

    jsonResponse(['success' => true]);
}

/**
 * Vérifier la session
 */
function handleVerify() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonResponse(['error' => 'Méthode non autorisée'], 405);
    }

    $session = verifyToken();

    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, email, dolibarr_user_id, preferences, last_sync
        FROM llx_mv3_mobile_users
        WHERE id = ?
    ");
    $stmt->execute([$session['user_id']]);
    $user = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'dolibarr_user_id' => $user['dolibarr_user_id'],
            'preferences' => json_decode($user['preferences'], true),
            'last_sync' => $user['last_sync']
        ]
    ]);
}
