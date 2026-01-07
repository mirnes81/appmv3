<?php
/**
 * GET /api/v1/me.php
 *
 * Retourne les informations de l'utilisateur connecté
 */

require_once __DIR__ . '/_bootstrap.php';

// Méthode GET uniquement
require_method('GET');

// Authentification obligatoire
$auth = require_auth(true);

// Construire la réponse
$response = [
    'success' => true,
    'user' => [
        'id' => $auth['user_id'] ?? null,
        'login' => $auth['login'] ?? null,
        'name' => $auth['name'] ?? '',
        'email' => $auth['email'] ?? '',
        'role' => $auth['role'] ?? 'user',
        'auth_mode' => $auth['mode'],
        'rights' => $auth['rights'] ?? []
    ]
];

// Si mode mobile, ajouter l'ID utilisateur mobile
if ($auth['mode'] === 'mobile_token' && isset($auth['mobile_user_id'])) {
    $response['user']['mobile_user_id'] = $auth['mobile_user_id'];
}

// Si utilisateur Dolibarr disponible, ajouter plus de détails
if (isset($auth['dolibarr_user']) && $auth['dolibarr_user']) {
    $dol_user = $auth['dolibarr_user'];
    $response['user']['phone'] = $dol_user->office_phone ?: $dol_user->user_mobile;
    $response['user']['entity'] = $dol_user->entity;
    $response['user']['admin'] = $dol_user->admin;
}

json_ok($response);
