<?php
require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    jsonError('Email and password are required');
}

$email = $input['email'];
$password = $input['password'];

$db = getDB();

$stmt = $db->prepare("
    SELECT
        u.rowid as id,
        u.login,
        u.lastname,
        u.firstname,
        u.email,
        u.pass_crypted,
        u.phone
    FROM llx_user u
    WHERE u.email = :email
    AND u.statut = 1
    LIMIT 1
");

$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    jsonError('Invalid credentials', 401);
}

if (!password_verify($password, $user['pass_crypted'])) {
    jsonError('Invalid credentials', 401);
}

$token = generateJWT($user['id']);

$userData = [
    'id' => uniqid('user_', true),
    'dolibarr_user_id' => $user['id'],
    'email' => $user['email'],
    'name' => trim($user['firstname'] . ' ' . $user['lastname']),
    'phone' => $user['phone'],
    'biometric_enabled' => false,
    'preferences' => [
        'theme' => 'auto',
        'notifications' => true,
        'autoSave' => true,
        'cameraQuality' => 'high',
        'voiceLanguage' => 'fr-FR'
    ],
    'last_sync' => date('c')
];

jsonResponse([
    'user' => $userData,
    'token' => $token
]);
