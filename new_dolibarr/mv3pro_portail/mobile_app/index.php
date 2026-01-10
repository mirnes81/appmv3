<?php
/**
 * Redirection automatique vers la nouvelle PWA React
 *
 * L'ancienne version PHP mobile est remplacée par la nouvelle PWA React
 *
 * Nouvelle URL: /custom/mv3pro_portail/pwa_dist/
 */

// Exception: Ne pas rediriger les API (elles sont encore utilisées par la PWA)
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

if (strpos($request_uri, '/mobile_app/api/') !== false) {
    // C'est une requête API, ne pas rediriger
    // Laisser PHP traiter normalement
    return;
}

if (strpos($request_uri, '/mobile_app/includes/') !== false) {
    // C'est un fichier include utilisé par les API, ne pas rediriger
    return;
}

// Redirection permanente (301) vers la nouvelle PWA
header('Location: /custom/mv3pro_portail/pwa_dist/', true, 301);
exit;
