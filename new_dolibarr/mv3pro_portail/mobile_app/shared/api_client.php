<?php
/**
 * API Client Helper - Wrapper pour appeler l'API v1
 *
 * Usage:
 *   require_once __DIR__.'/../shared/api_client.php';
 *   $result = api_get('/planning.php?from=2025-01-07');
 *   $result = api_post('/rapports_create.php', $data);
 */

// Configuration
define('API_V1_BASE_URL', '/custom/mv3pro_portail/api/v1');

/**
 * Effectue une requête GET vers l'API v1
 *
 * @param string $endpoint Ex: '/me.php', '/planning.php?from=2025-01-07'
 * @param array $headers Headers additionnels (optionnel)
 * @return array ['success' => bool, 'data' => mixed, 'error' => string]
 */
function api_get($endpoint, $headers = []) {
    return api_request('GET', $endpoint, null, $headers);
}

/**
 * Effectue une requête POST vers l'API v1
 *
 * @param string $endpoint Ex: '/rapports_create.php'
 * @param array $data Données à envoyer
 * @param array $headers Headers additionnels (optionnel)
 * @return array ['success' => bool, 'data' => mixed, 'error' => string]
 */
function api_post($endpoint, $data, $headers = []) {
    return api_request('POST', $endpoint, $data, $headers);
}

/**
 * Effectue une requête vers l'API v1 (interne)
 *
 * @param string $method GET, POST, PUT, DELETE
 * @param string $endpoint
 * @param mixed $data
 * @param array $headers
 * @return array
 */
function api_request($method, $endpoint, $data = null, $headers = []) {
    // Construire l'URL complète
    $url = API_V1_BASE_URL . $endpoint;

    // Si on est en contexte PHP (pas JS), on peut faire un include direct
    // pour éviter HTTP overhead en local

    // Pour l'instant, retourner une structure pour usage futur
    // La vraie implémentation viendra avec la PWA React

    return [
        'success' => false,
        'error' => 'api_client.php est un placeholder. Utilisez fetch() depuis JavaScript ou incluez directement l\'endpoint PHP.',
        'url' => $url,
        'method' => $method,
        'data' => $data
    ];
}

/**
 * Construit l'URL complète d'un endpoint API v1
 *
 * @param string $endpoint
 * @return string
 */
function api_url($endpoint) {
    return API_V1_BASE_URL . $endpoint;
}

/**
 * Vérifie si l'API v1 est disponible
 *
 * @return bool
 */
function api_is_available() {
    $bootstrap_file = $_SERVER['DOCUMENT_ROOT'] . API_V1_BASE_URL . '/_bootstrap.php';
    return file_exists($bootstrap_file);
}

/**
 * Exemple d'utilisation JavaScript à inclure dans les pages
 *
 * Usage dans vos pages:
 * <script src="/custom/mv3pro_portail/mobile_app/shared/api_client.js"></script>
 */
function api_client_js_snippet() {
    return <<<'JS'
<script>
// Helper JavaScript pour appeler l'API v1
const API_BASE = '/custom/mv3pro_portail/api/v1';

// Récupérer le token d'auth depuis localStorage ou cookie
function getAuthToken() {
    return localStorage.getItem('mobile_auth_token') || '';
}

// Requête GET
async function apiGet(endpoint) {
    const token = getAuthToken();
    const response = await fetch(API_BASE + endpoint, {
        headers: {
            'Authorization': token ? 'Bearer ' + token : '',
        }
    });
    return await response.json();
}

// Requête POST
async function apiPost(endpoint, data) {
    const token = getAuthToken();
    const response = await fetch(API_BASE + endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': token ? 'Bearer ' + token : '',
        },
        body: JSON.stringify(data)
    });
    return await response.json();
}

// Exemples:
// const user = await apiGet('/me.php');
// const events = await apiGet('/planning.php?from=2025-01-07');
// const result = await apiPost('/rapports_create.php', {projet_id: 123, ...});
</script>
JS;
}
