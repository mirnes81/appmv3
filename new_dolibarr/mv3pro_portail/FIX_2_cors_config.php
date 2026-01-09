<?php
/**
 * Configuration CORS centralisée pour les APIs - VERSION CORRIGÉE
 *
 * IMPORTANT: En production, restreindre Access-Control-Allow-Origin
 * à votre domaine spécifique au lieu de "*"
 */

// Liste des domaines autorisés (whitelist)
// Modifier cette liste selon votre environnement
$allowed_origins = [
    // Exemples à adapter :
    // 'https://votre-domaine.com',
    // 'https://app.votre-domaine.com',
    // 'http://localhost:8080',
];

/**
 * Définir les headers CORS de manière sécurisée
 *
 * @param array $allowed_origins Liste des domaines autorisés (optionnel)
 * @return void
 */
function setCorsHeaders($allowed_origins = []) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Si une whitelist est définie, valider l'origine
    if (!empty($allowed_origins)) {
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            // Origine non autorisée - ne pas définir le header
            // Ou utiliser un domaine par défaut
            return;
        }
    } else {
        // Mode développement - accepter toutes les origines
        // ATTENTION: À restreindre en production !
        header('Access-Control-Allow-Origin: *');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    // ✅ CORRECTION : Ajout de X-Auth-Token et X-MV3-Debug
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-MV3-Debug, X-Client-Info, Apikey');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

/**
 * Gérer les requêtes OPTIONS (preflight)
 *
 * @return void
 */
function handleCorsPreflightRequest() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
