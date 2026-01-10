<?php
/**
 * MV3 PRO Portail - Core Permissions Functions
 *
 * Fonctions centralisées pour la gestion des permissions
 */

if (!defined('MV3_CORE_PERMISSIONS')) {
    define('MV3_CORE_PERMISSIONS', true);
}

/**
 * Vérifie si l'utilisateur a le droit de voir un rapport
 *
 * @param array $auth Tableau d'authentification
 * @param int $rapport_user_id ID utilisateur du rapport
 * @return bool true si accès autorisé
 */
function mv3_can_view_rapport($auth, $rapport_user_id) {
    $dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
    $is_admin = mv3_is_admin($auth);

    if ($is_admin) {
        return true;
    }

    return ($dolibarr_user_id === (int)$rapport_user_id);
}

/**
 * Vérifie si l'utilisateur a le droit de modifier un rapport
 *
 * @param array $auth Tableau d'authentification
 * @param int $rapport_user_id ID utilisateur du rapport
 * @return bool true si modification autorisée
 */
function mv3_can_edit_rapport($auth, $rapport_user_id) {
    return mv3_can_view_rapport($auth, $rapport_user_id);
}

/**
 * Vérifie si l'utilisateur a le droit de supprimer un rapport
 *
 * @param array $auth Tableau d'authentification
 * @param int $rapport_user_id ID utilisateur du rapport
 * @return bool true si suppression autorisée
 */
function mv3_can_delete_rapport($auth, $rapport_user_id) {
    $is_admin = mv3_is_admin($auth);
    return $is_admin;
}

/**
 * Vérifie les permissions avec erreur 403 si refusé
 *
 * @param array $auth Tableau d'authentification
 * @param int $rapport_user_id ID utilisateur du rapport
 * @param string $action Action demandée (view, edit, delete)
 * @return void (ou json_error si refusé)
 */
function mv3_require_rapport_permission($auth, $rapport_user_id, $action = 'view') {
    $allowed = false;

    switch ($action) {
        case 'view':
            $allowed = mv3_can_view_rapport($auth, $rapport_user_id);
            break;
        case 'edit':
            $allowed = mv3_can_edit_rapport($auth, $rapport_user_id);
            break;
        case 'delete':
            $allowed = mv3_can_delete_rapport($auth, $rapport_user_id);
            break;
    }

    if (!$allowed) {
        json_error('Vous n\'avez pas les droits pour accéder à ce rapport', 'FORBIDDEN', 403);
    }
}
