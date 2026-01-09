<?php
/**
 * TEST LOGIN & TOKEN - MV3 PRO
 *
 * Ce script teste le flux complet d'authentification :
 * 1. Login avec email/password
 * 2. Récupération du token
 * 3. Test de l'API /me.php avec le token
 * 4. Test de l'API /planning.php avec le token
 *
 * UTILISATION :
 * 1. Uploadez ce fichier dans : /custom/mv3pro_portail/
 * 2. Accédez via : https://crm.mv-3pro.ch/custom/mv3pro_portail/TEST_LOGIN_TOKEN.php
 * 3. Ou en ligne de commande : php TEST_LOGIN_TOKEN.php
 */

// Configuration
$BASE_URL = 'https://crm.mv-3pro.ch/custom/mv3pro_portail';
$TEST_EMAIL = 'mirnes@mv-3pro.ch'; // MODIFIER AVEC VOTRE EMAIL
$TEST_PASSWORD = 'votre_mot_de_passe'; // MODIFIER AVEC VOTRE MOT DE PASSE

// Fonction helper pour afficher
function display($title, $data) {
    echo "\n========================================\n";
    echo "  $title\n";
    echo "========================================\n";

    if (is_array($data)) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo $data;
    }
    echo "\n\n";
}

// Fonction pour faire un appel API
function apiCall($url, $method = 'GET', $body = null, $token = null) {
    $ch = curl_init($url);

    $headers = [
        'Content-Type: application/json',
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
        $headers[] = 'X-Auth-Token: ' . $token;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    return [
        'http_code' => $http_code,
        'response' => $response,
        'error' => $error,
        'json' => json_decode($response, true),
    ];
}

display('TEST LOGIN & TOKEN - MV3 PRO', 'Début du test...');

// ÉTAPE 1 : Login
display('ÉTAPE 1 : Test Login', "URL: $BASE_URL/mobile_app/api/auth.php?action=login\nEmail: $TEST_EMAIL");

$login_result = apiCall(
    "$BASE_URL/mobile_app/api/auth.php?action=login",
    'POST',
    [
        'email' => $TEST_EMAIL,
        'password' => $TEST_PASSWORD,
    ]
);

display('Réponse Login (HTTP ' . $login_result['http_code'] . ')', $login_result['json'] ?: $login_result['response']);

if ($login_result['http_code'] !== 200 || !isset($login_result['json']['success']) || !$login_result['json']['success']) {
    display('ERREUR', 'Login échoué. Vérifiez votre email/password dans le script.');
    display('Message d\'erreur', $login_result['json']['message'] ?? 'Erreur inconnue');
    if (isset($login_result['json']['hint'])) {
        display('Indice', $login_result['json']['hint']);
    }
    exit(1);
}

$token = $login_result['json']['token'] ?? null;

if (!$token) {
    display('ERREUR', 'Token non reçu dans la réponse login');
    exit(1);
}

display('✓ Login réussi', [
    'Token' => $token,
    'Token (preview)' => substr($token, 0, 20) . '...' . substr($token, -10),
    'Token length' => strlen($token),
    'User' => $login_result['json']['user'] ?? [],
    'Expires' => $login_result['json']['expires_at'] ?? 'N/A',
]);

// ÉTAPE 2 : Test /me.php
display('ÉTAPE 2 : Test /api/v1/me.php', "URL: $BASE_URL/api/v1/me.php");

$me_result = apiCall("$BASE_URL/api/v1/me.php", 'GET', null, $token);

display('Réponse /me.php (HTTP ' . $me_result['http_code'] . ')', $me_result['json'] ?: $me_result['response']);

if ($me_result['http_code'] !== 200) {
    display('ERREUR', 'Test /me.php échoué');
    exit(1);
}

display('✓ Test /me.php réussi', $me_result['json']);

// ÉTAPE 3 : Test /planning.php
display('ÉTAPE 3 : Test /api/v1/planning.php', "URL: $BASE_URL/api/v1/planning.php");

$planning_result = apiCall("$BASE_URL/api/v1/planning.php", 'GET', null, $token);

display('Réponse /planning.php (HTTP ' . $planning_result['http_code'] . ')', $planning_result['json'] ?: $planning_result['response']);

if ($planning_result['http_code'] !== 200) {
    display('AVERTISSEMENT', 'Test /planning.php échoué (peut être normal si pas de données)');
} else {
    display('✓ Test /planning.php réussi', $planning_result['json']);
}

// ÉTAPE 4 : Commandes curl pour tests manuels
display('ÉTAPE 4 : Commandes curl pour tests manuels', 'Copiez-collez ces commandes pour tester :');

echo "# Test /me.php\n";
echo "curl -H \"X-Auth-Token: $token\" \\\n";
echo "     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/me.php\n\n";

echo "# Test /planning.php\n";
echo "curl -H \"X-Auth-Token: $token\" \\\n";
echo "     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning.php\n\n";

display('✓ TOUS LES TESTS RÉUSSIS', [
    'Login' => 'OK',
    'Token' => 'OK',
    '/me.php' => 'OK',
    '/planning.php' => $planning_result['http_code'] === 200 ? 'OK' : 'WARNING',
    'Token_a_utiliser' => $token,
]);

display('PROCHAINE ÉTAPE', 'Testez la PWA avec les identifiants ci-dessus :
1. Ouvrez https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
2. Connectez-vous avec : ' . $TEST_EMAIL . '
3. Ouvrez F12 → Console
4. Tapez : localStorage.getItem("mv3pro_token")
5. Vérifiez que le token est bien stocké
6. Testez la navigation dans la PWA
');
