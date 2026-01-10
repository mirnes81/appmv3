<?php
/**
 * MV3 PRO Portail - Core Authentication Functions
 *
 * Fonctions centralisées pour la gestion de l'authentification
 * Compatible avec mobile_token et session PHP classique
 */

if (!defined('MV3_CORE_AUTH')) {
    define('MV3_CORE_AUTH', true);
}

/**
 * Récupère l'ID utilisateur Dolibarr depuis l'objet auth
 *
 * @param array $auth Tableau d'authentification retourné par require_auth()
 * @return int ID utilisateur Dolibarr (0 si non trouvé)
 */
function mv3_get_dolibarr_user_id($auth) {
    if (empty($auth)) {
        return 0;
    }

    if (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->id)) {
        return (int)$auth['dolibarr_user']->id;
    }

    return 0;
}

/**
 * Vérifie si l'utilisateur est admin
 *
 * @param array $auth Tableau d'authentification retourné par require_auth()
 * @return bool true si admin, false sinon
 */
function mv3_is_admin($auth) {
    if (empty($auth)) {
        return false;
    }

    if (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->admin)) {
        return true;
    }

    return false;
}

/**
 * Vérifie que l'utilisateur est admin (erreur 403 si non admin)
 *
 * @param array $auth Tableau d'authentification retourné par require_auth()
 * @return void (ou json_error si pas admin)
 */
function mv3_require_admin($auth) {
    if (!mv3_is_admin($auth)) {
        json_error('Accès réservé aux administrateurs', 'FORBIDDEN', 403);
    }
}

/**
 * Récupère les informations utilisateur depuis auth
 *
 * @param array $auth Tableau d'authentification retourné par require_auth()
 * @return array Informations utilisateur (name, email, dolibarr_user_id, is_admin)
 */
function mv3_get_user_info($auth) {
    return [
        'name' => $auth['name'] ?? 'N/A',
        'email' => $auth['email'] ?? 'N/A',
        'dolibarr_user_id' => mv3_get_dolibarr_user_id($auth),
        'is_admin' => mv3_is_admin($auth),
        'mode' => $auth['mode'] ?? 'N/A',
        'is_unlinked' => $auth['is_unlinked'] ?? false,
    ];
}
