<?php
/**
 * Configuration App Mobile - MV3 PRO Portail
 *
 * Configuration centralisée pour l'application mobile
 * Usage: require_once __DIR__.'/../config/app_config.php';
 */

// Version de l'app
define('MV3_APP_VERSION', '1.1.0');
define('MV3_APP_NAME', 'MV3 PRO Mobile');

// URLs de base
define('MV3_BASE_URL', '/custom/mv3pro_portail');
define('MV3_MOBILE_BASE_URL', MV3_BASE_URL . '/mobile_app');
define('MV3_API_V1_URL', MV3_BASE_URL . '/api/v1');

// Chemins filesystem
define('MV3_MODULE_PATH', __DIR__ . '/../..');
define('MV3_MOBILE_PATH', MV3_MODULE_PATH . '/mobile_app');
define('MV3_SHARED_PATH', MV3_MOBILE_PATH . '/shared');

// Configuration PWA
$MV3_PWA_CONFIG = [
    'name' => MV3_APP_NAME,
    'short_name' => 'MV3 PRO',
    'version' => MV3_APP_VERSION,
    'description' => 'Application mobile pour les ouvriers MV3 Carrelage',
    'theme_color' => '#0891b2',
    'background_color' => '#f9fafb',
    'display' => 'standalone',
    'orientation' => 'portrait',
    'scope' => MV3_MOBILE_BASE_URL . '/',
    'start_url' => MV3_MOBILE_BASE_URL . '/dashboard_mobile.php',
    'icons' => [
        [
            'src' => MV3_BASE_URL . '/mobile_app/assets/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png'
        ],
        [
            'src' => MV3_BASE_URL . '/mobile_app/assets/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png'
        ]
    ]
];

// Configuration API
$MV3_API_CONFIG = [
    'base_url' => MV3_API_V1_URL,
    'timeout' => 30, // secondes
    'endpoints' => [
        'me' => '/me.php',
        'planning' => '/planning.php',
        'rapports' => '/rapports.php',
        'rapports_create' => '/rapports_create.php',
    ]
];

// Configuration authentification
$MV3_AUTH_CONFIG = [
    'session_lifetime' => 30 * 24 * 3600, // 30 jours
    'token_refresh_interval' => 7 * 24 * 3600, // 7 jours
    'login_url' => MV3_MOBILE_BASE_URL . '/login_mobile.php',
    'dashboard_url' => MV3_MOBILE_BASE_URL . '/dashboard_mobile.php',
    'max_login_attempts' => 5,
    'lockout_duration' => 15 * 60, // 15 minutes
];

// Configuration features (activation/désactivation fonctionnalités)
$MV3_FEATURES = [
    'rapports' => true,
    'regie' => true,
    'sens_pose' => true,
    'materiel' => true,
    'planning' => true,
    'notifications' => true,
    'gps' => true,
    'meteo' => true,
    'photos' => true,
    'signature' => true,
    'offline_mode' => false, // Future PWA
    'qrcode_scan' => false, // Future
    'voice_notes' => false, // Future
];

// Navigation menu
$MV3_NAVIGATION = [
    [
        'label' => 'Accueil',
        'icon' => '🏠',
        'url' => MV3_MOBILE_BASE_URL . '/dashboard_mobile.php',
        'key' => 'dashboard',
        'enabled' => true
    ],
    [
        'label' => 'Planning',
        'icon' => '📅',
        'url' => MV3_MOBILE_BASE_URL . '/planning/',
        'key' => 'planning',
        'enabled' => $MV3_FEATURES['planning']
    ],
    [
        'label' => 'Rapports',
        'icon' => '📋',
        'url' => MV3_MOBILE_BASE_URL . '/rapports/list.php',
        'key' => 'rapports',
        'enabled' => $MV3_FEATURES['rapports']
    ],
    [
        'label' => 'Régie',
        'icon' => '📝',
        'url' => MV3_MOBILE_BASE_URL . '/regie/list.php',
        'key' => 'regie',
        'enabled' => $MV3_FEATURES['regie']
    ],
    [
        'label' => 'Sens de Pose',
        'icon' => '🔷',
        'url' => MV3_MOBILE_BASE_URL . '/sens_pose/list.php',
        'key' => 'sens_pose',
        'enabled' => $MV3_FEATURES['sens_pose']
    ],
    [
        'label' => 'Matériel',
        'icon' => '🔧',
        'url' => MV3_MOBILE_BASE_URL . '/materiel/list.php',
        'key' => 'materiel',
        'enabled' => $MV3_FEATURES['materiel']
    ],
    [
        'label' => 'Notifications',
        'icon' => '🔔',
        'url' => MV3_MOBILE_BASE_URL . '/notifications/',
        'key' => 'notifications',
        'enabled' => $MV3_FEATURES['notifications'],
        'badge' => true
    ],
    [
        'label' => 'Profil',
        'icon' => '👤',
        'url' => MV3_MOBILE_BASE_URL . '/profil/',
        'key' => 'profil',
        'enabled' => true
    ],
];

/**
 * Récupérer la configuration PWA
 */
function mv3_get_pwa_config() {
    global $MV3_PWA_CONFIG;
    return $MV3_PWA_CONFIG;
}

/**
 * Récupérer la configuration API
 */
function mv3_get_api_config() {
    global $MV3_API_CONFIG;
    return $MV3_API_CONFIG;
}

/**
 * Récupérer la configuration auth
 */
function mv3_get_auth_config() {
    global $MV3_AUTH_CONFIG;
    return $MV3_AUTH_CONFIG;
}

/**
 * Vérifier si une feature est activée
 */
function mv3_is_feature_enabled($feature) {
    global $MV3_FEATURES;
    return isset($MV3_FEATURES[$feature]) && $MV3_FEATURES[$feature];
}

/**
 * Récupérer le menu de navigation
 */
function mv3_get_navigation() {
    global $MV3_NAVIGATION;
    // Filtrer uniquement les items enabled
    return array_filter($MV3_NAVIGATION, function($item) {
        return $item['enabled'];
    });
}

/**
 * Générer l'URL complète d'un endpoint API v1
 */
function mv3_api_url($endpoint) {
    return MV3_API_V1_URL . $endpoint;
}

/**
 * Vérifier la version de l'app
 */
function mv3_check_version() {
    return [
        'current' => MV3_APP_VERSION,
        'name' => MV3_APP_NAME,
        'api_available' => file_exists($_SERVER['DOCUMENT_ROOT'] . MV3_API_V1_URL . '/_bootstrap.php')
    ];
}
