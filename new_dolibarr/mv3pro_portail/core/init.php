<?php
/**
 * MV3 PRO Portail - Core Initialization
 *
 * Ce fichier charge toutes les fonctions centralisées du module
 * Il doit être inclus dans tous les endpoints API qui utilisent les fonctions mv3_*()
 *
 * Usage:
 *   require_once __DIR__ . '/../../core/init.php';
 *
 * Fonctions disponibles après inclusion:
 *   - mv3_get_dolibarr_user_id()
 *   - mv3_is_admin()
 *   - mv3_require_admin()
 *   - mv3_get_user_info()
 *   - mv3_can_view_rapport()
 *   - mv3_can_edit_rapport()
 *   - mv3_can_delete_rapport()
 *   - mv3_require_rapport_permission()
 *   - mv3_check_table_or_empty()
 *   - mv3_format_date()
 *   - mv3_format_time()
 *   - mv3_calculate_duration()
 *   - mv3_get_statut_label()
 *   - mv3_sql_escape()
 *   - mv3_log_error()
 *   - mv3_log_info()
 *   - mv3_require_param()
 */

if (!defined('MV3_CORE_INIT')) {
    define('MV3_CORE_INIT', true);

    $core_dir = __DIR__;

    require_once $core_dir . '/auth.php';
    require_once $core_dir . '/permissions.php';
    require_once $core_dir . '/functions.php';
}
