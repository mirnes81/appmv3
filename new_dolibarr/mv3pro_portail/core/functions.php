<?php
/**
 * MV3 PRO Portail - Core Utility Functions
 *
 * Fonctions utilitaires centralisées
 */

if (!defined('MV3_CORE_FUNCTIONS')) {
    define('MV3_CORE_FUNCTIONS', true);
}

/**
 * Vérifie qu'une table existe, retourne liste vide si absente
 *
 * @param DoliDB $db Instance base de données
 * @param string $table_name Nom de la table (sans préfixe)
 * @param string $label Label pour message d'erreur
 * @return void (ou json_ok si table absente)
 */
if (!function_exists('mv3_check_table_or_empty')) {
    function mv3_check_table_or_empty($db, $table_name, $label = 'Table') {
    global $conf;

    $entity = isset($conf->entity) ? (int)$conf->entity : 1;

    $full_table = MAIN_DB_PREFIX . $table_name;

    $sql = "SHOW TABLES LIKE '".$db->escape($full_table)."'";
    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        error_log("[MV3 Core] Table $full_table n'existe pas, retour liste vide");

        json_ok([
            'data' => [
                'items' => [],
                'page' => 1,
                'limit' => 20,
                'total' => 0,
                'total_pages' => 0,
            ]
        ]);
        exit;
    }
    }
}

/**
 * Formate une date pour l'affichage
 *
 * @param string $date Date au format YYYY-MM-DD ou timestamp
 * @param string $format Format de sortie (défaut: d/m/Y)
 * @return string Date formatée
 */
if (!function_exists('mv3_format_date')) {
    function mv3_format_date($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '';
    }

    if (is_numeric($date)) {
        return date($format, $date);
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
    }
}

/**
 * Formate une heure pour l'affichage
 *
 * @param string $time Heure au format HH:MM:SS
 * @param string $format Format de sortie (défaut: H:i)
 * @return string Heure formatée
 */
if (!function_exists('mv3_format_time')) {
    function mv3_format_time($time, $format = 'H:i') {
    if (empty($time) || $time === '00:00:00') {
        return '';
    }

    $timestamp = strtotime($time);
    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
    }
}

/**
 * Calcule la durée entre deux heures
 *
 * @param string $heure_debut Heure de début (HH:MM:SS)
 * @param string $heure_fin Heure de fin (HH:MM:SS)
 * @return float Durée en heures (0 si invalide)
 */
if (!function_exists('mv3_calculate_duration')) {
    function mv3_calculate_duration($heure_debut, $heure_fin) {
        if (empty($heure_debut) || empty($heure_fin)) {
            return 0;
        }

        $start = strtotime($heure_debut);
        $end = strtotime($heure_fin);

        if ($end <= $start) {
            return 0;
        }

        return round(($end - $start) / 3600, 2);
    }
}

/**
 * Récupère le label d'un statut
 *
 * @param int $statut Code statut (0=brouillon, 1=validé, 2=soumis)
 * @return string Label du statut
 */
if (!function_exists('mv3_get_statut_label')) {
    function mv3_get_statut_label($statut) {
        $statut = (int)$statut;

        switch ($statut) {
            case 0:
                return 'brouillon';
            case 1:
                return 'valide';
            case 2:
                return 'soumis';
            default:
                return 'inconnu';
        }
    }
}

/**
 * Nettoie une chaîne pour utilisation SQL
 *
 * @param DoliDB $db Instance base de données
 * @param string $string Chaîne à nettoyer
 * @return string Chaîne nettoyée
 */
if (!function_exists('mv3_sql_escape')) {
    function mv3_sql_escape($db, $string) {
        return $db->escape($string);
    }
}

/**
 * Log une erreur dans le fichier de log MV3
 *
 * @param string $message Message d'erreur
 * @param string $context Contexte (ex: 'API', 'Auth', 'Upload')
 * @return void
 */
if (!function_exists('mv3_log_error')) {
    function mv3_log_error($message, $context = 'MV3') {
        error_log("[MV3 $context] $message");
    }
}

/**
 * Log une info dans le fichier de log MV3
 *
 * @param string $message Message d'information
 * @param string $context Contexte (ex: 'API', 'Auth', 'Upload')
 * @return void
 */
if (!function_exists('mv3_log_info')) {
    function mv3_log_info($message, $context = 'MV3') {
        error_log("[MV3 $context] $message");
    }
}

/**
 * Vérifie qu'un paramètre existe et n'est pas vide
 *
 * @param string $param_name Nom du paramètre
 * @param mixed $value Valeur du paramètre
 * @param string $error_message Message d'erreur personnalisé
 * @return void (ou json_error si invalide)
 */
if (!function_exists('mv3_require_param')) {
    function mv3_require_param($param_name, $value, $error_message = null) {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $message = $error_message ?? "Le paramètre '$param_name' est requis";
            json_error($message, 'INVALID_PARAMETER', 400);
        }
    }
}
