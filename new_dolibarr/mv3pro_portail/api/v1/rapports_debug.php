<?php
/**
 * DEBUG /api/v1/rapports_debug.php
 *
 * Diagnostic pour comprendre pourquoi les rapports ne s'affichent pas
 */

// 🔥 GESTIONNAIRE ANTI-500 - CAPTURE TOUTES LES ERREURS ET RETOURNE JSON
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function json_fail($code, $msg, $extra = []) {
    if (!headers_sent()) {
        http_response_code($code);
    }
    echo json_encode(array_merge(['success'=>false,'error'=>$msg], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

set_exception_handler(function($e){
    error_log('[MV3 EXCEPTION rapports_debug.php] ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    json_fail(500, 'exception', ['message'=>$e->getMessage(), 'file'=>basename($e->getFile()), 'line'=>$e->getLine()]);
});

register_shutdown_function(function(){
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('[MV3 FATAL rapports_debug.php] ' . $err['message'] . ' at ' . $err['file'] . ':' . $err['line']);
        json_fail(500, 'fatal_error', ['message'=>$err['message'], 'file'=>basename($err['file']), 'line'=>$err['line']]);
    }
});
// FIN GESTIONNAIRE ANTI-500

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../core/init.php';

global $db, $conf;

require_method('GET');

$auth = require_auth(true);

$entity = isset($conf->entity) ? (int)$conf->entity : 1;

// Récupérer le vrai ID Dolibarr et le statut admin via fonctions centralisées
$dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
$is_admin = mv3_is_admin($auth);

// 1. Info utilisateur connecté
$user_info = [
    'mode' => $auth['mode'] ?? 'N/A',
    'OLD_user_id' => $auth['user_id'] ?? null,
    'mobile_user_id' => $auth['mobile_user_id'] ?? null,
    'email' => $auth['email'] ?? 'N/A',
    'name' => $auth['name'] ?? 'N/A',
    'is_unlinked' => $auth['is_unlinked'] ?? false,
    'dolibarr_user_id' => $dolibarr_user_id,
    'is_admin' => $is_admin,
    'auth_keys' => array_keys($auth),
];

// 2. Compter les rapports totaux dans l'entité
$sql_total = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."mv3_rapport WHERE entity = ".$entity;
$resql_total = $db->query($sql_total);
$total_rapports = 0;
if ($resql_total) {
    $obj = $db->fetch_object($resql_total);
    $total_rapports = (int)$obj->total;
}

// 3. Compter les rapports par utilisateur
$sql_by_user = "SELECT fk_user, COUNT(*) as nb FROM ".MAIN_DB_PREFIX."mv3_rapport WHERE entity = ".$entity." GROUP BY fk_user";
$resql_by_user = $db->query($sql_by_user);
$rapports_by_user = [];
if ($resql_by_user) {
    while ($obj = $db->fetch_object($resql_by_user)) {
        $rapports_by_user[(int)$obj->fk_user] = (int)$obj->nb;
    }
}

// 4. Compter les rapports avec le filtre DOLIBARR actuel
$rapports_with_filter = 0;
$rapports_with_old_filter = 0;

if ($dolibarr_user_id > 0) {
    $sql_filtered = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."mv3_rapport
                     WHERE entity = ".$entity." AND fk_user = ".$dolibarr_user_id;
    $resql_filtered = $db->query($sql_filtered);
    if ($resql_filtered) {
        $obj = $db->fetch_object($resql_filtered);
        $rapports_with_filter = (int)$obj->total;
    }
}

// Compter aussi avec l'ancien user_id pour comparaison
if (!empty($auth['user_id'])) {
    $sql_old = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."mv3_rapport
                WHERE entity = ".$entity." AND fk_user = ".(int)$auth['user_id'];
    $resql_old = $db->query($sql_old);
    if ($resql_old) {
        $obj = $db->fetch_object($resql_old);
        $rapports_with_old_filter = (int)$obj->total;
    }
}

// 5. Lister les 5 derniers rapports (sans filtre)
$sql_recent = "SELECT r.rowid, r.ref, r.date_rapport, r.fk_user, r.fk_projet,
               u.login as user_login, u.firstname, u.lastname,
               p.title as projet_title
               FROM ".MAIN_DB_PREFIX."mv3_rapport r
               LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = r.fk_user
               LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = r.fk_projet
               WHERE r.entity = ".$entity."
               ORDER BY r.date_rapport DESC, r.rowid DESC
               LIMIT 5";

$resql_recent = $db->query($sql_recent);
$recent_rapports = [];
if ($resql_recent) {
    while ($obj = $db->fetch_object($resql_recent)) {
        $recent_rapports[] = [
            'rowid' => (int)$obj->rowid,
            'ref' => $obj->ref,
            'date_rapport' => $obj->date_rapport,
            'fk_user' => (int)$obj->fk_user,
            'user_login' => $obj->user_login,
            'user_name' => trim($obj->firstname . ' ' . $obj->lastname),
            'projet_title' => $obj->projet_title,
        ];
    }
}

// 6. Recommandation
$recommendation = '';
if ($total_rapports > 0 && $rapports_with_filter === 0 && !$is_admin) {
    $recommendation = "⚠️ PROBLÈME : Il y a {$total_rapports} rapport(s) dans l'entité, mais 0 visible avec le filtre fk_user={$dolibarr_user_id}. ";

    if ($user_info['is_unlinked']) {
        $recommendation .= "Le compte mobile n'est pas lié à un utilisateur Dolibarr. Vous devez lier ce compte dans /custom/mv3pro_portail/mobile_app/admin/manage_users.php";
    } elseif ($dolibarr_user_id === 0) {
        $recommendation .= "Aucun dolibarr_user_id détecté dans l'authentification.";
    } else {
        $recommendation .= "Les rapports ne sont pas créés avec fk_user={$dolibarr_user_id}. Vérifiez que les rapports ont le bon fk_user.";
    }
} elseif ($is_admin) {
    $recommendation = "✅ Utilisateur ADMIN détecté : peut voir tous les rapports de l'entité ({$total_rapports} au total).";
} elseif ($rapports_with_filter > 0) {
    $recommendation = "✅ {$rapports_with_filter} rapport(s) visible(s) pour cet utilisateur.";
}

// 7. DIAGNOSTIC STRUCTURE TABLE - Vérifier colonnes existantes
$table_name = MAIN_DB_PREFIX . 'mv3_rapport';
$sql_columns = "SHOW COLUMNS FROM " . $table_name;
$resql_columns = $db->query($sql_columns);

$existing_columns = [];
$column_details = [];
if ($resql_columns) {
    while ($col = $db->fetch_object($resql_columns)) {
        $existing_columns[] = $col->Field;
        $column_details[$col->Field] = [
            'type' => $col->Type,
            'null' => $col->Null,
            'key' => $col->Key,
            'default' => $col->Default,
            'extra' => $col->Extra,
        ];
    }
}

// Colonnes attendues par l'API rapports.php
$expected_columns = [
    'rowid',
    'ref',
    'entity',
    'date_rapport',
    'heure_debut',      // ← Problème détecté !
    'heure_fin',        // ← Peut-être aussi manquante
    'duree_heures',
    'fk_user',
    'fk_projet',
    'fk_task',
    'description',
    'type_travail',
    'statut',
    'date_creation',
    'date_modification',
];

// Comparer colonnes attendues vs existantes
$missing_columns = array_diff($expected_columns, $existing_columns);
$extra_columns = array_diff($existing_columns, $expected_columns);

// 8. TEST RÉEL DE LA REQUÊTE API - Capturer l'erreur exacte
$api_test_result = [
    'success' => false,
    'error' => null,
    'sql_error' => null,
    'sql_query' => null,
];

try {
    // Simuler la requête exacte de rapports.php
    $test_sql = "SELECT rowid, ref, date_rapport, heure_debut, heure_fin, duree_heures,
                 fk_user, fk_projet, fk_task, description, type_travail, statut,
                 date_creation, date_modification
                 FROM " . $table_name . "
                 WHERE entity = " . $entity;

    if (!$is_admin && $dolibarr_user_id > 0) {
        $test_sql .= " AND fk_user = " . $dolibarr_user_id;
    }

    $test_sql .= " ORDER BY date_rapport DESC, rowid DESC LIMIT 5";

    $api_test_result['sql_query'] = $test_sql;

    $resql_test = $db->query($test_sql);

    if ($resql_test) {
        $api_test_result['success'] = true;
        $api_test_result['rows_returned'] = $db->num_rows($resql_test);
    } else {
        $api_test_result['error'] = $db->lasterror();
        $api_test_result['sql_error'] = $db->lasterrno();
    }

} catch (Exception $e) {
    $api_test_result['error'] = $e->getMessage();
}

// 9. GÉNÉRER SQL DE CORRECTION AUTOMATIQUE
$fix_sql = [];
if (!empty($missing_columns)) {
    foreach ($missing_columns as $col) {
        switch ($col) {
            case 'heure_debut':
                $fix_sql[] = "ALTER TABLE {$table_name} ADD COLUMN heure_debut TIME DEFAULT NULL AFTER date_rapport;";
                break;
            case 'heure_fin':
                $fix_sql[] = "ALTER TABLE {$table_name} ADD COLUMN heure_fin TIME DEFAULT NULL AFTER heure_debut;";
                break;
            case 'duree_heures':
                $fix_sql[] = "ALTER TABLE {$table_name} ADD COLUMN duree_heures DECIMAL(10,2) DEFAULT 0 AFTER heure_fin;";
                break;
            case 'type_travail':
                $fix_sql[] = "ALTER TABLE {$table_name} ADD COLUMN type_travail VARCHAR(50) DEFAULT NULL AFTER description;";
                break;
            case 'date_modification':
                $fix_sql[] = "ALTER TABLE {$table_name} ADD COLUMN date_modification DATETIME DEFAULT NULL;";
                break;
            default:
                $fix_sql[] = "-- Ajouter manuellement : {$col}";
        }
    }
}

$response = [
    'success' => true,
    'debug_info' => [
        'user_info' => $user_info,
        'entity' => $entity,
        'total_rapports_in_entity' => $total_rapports,
        'rapports_by_user' => $rapports_by_user,
        'rapports_with_NEW_filter' => $rapports_with_filter,
        'rapports_with_OLD_filter' => $rapports_with_old_filter,
        'filter_applied' => $dolibarr_user_id > 0 ? "fk_user = {$dolibarr_user_id} (Dolibarr ID)" : 'AUCUN',
        'recent_rapports' => $recent_rapports,
    ],
    'recommendation' => $recommendation,
    'comparison' => [
        'old_system' => "auth['user_id'] = " . ($auth['user_id'] ?? 'NULL') . " → {$rapports_with_old_filter} rapport(s)",
        'new_system' => "dolibarr_user_id = {$dolibarr_user_id} → {$rapports_with_filter} rapport(s)",
    ],
    'table_structure' => [
        'table_name' => $table_name,
        'total_columns' => count($existing_columns),
        'existing_columns' => $existing_columns,
        'column_details' => $column_details,
        'expected_columns' => $expected_columns,
        'missing_columns' => array_values($missing_columns),
        'extra_columns' => array_values($extra_columns),
        'has_issues' => !empty($missing_columns),
    ],
    'api_test' => $api_test_result,
    'fix_sql' => $fix_sql,
    'diagnostic_summary' => [
        'table_exists' => !empty($existing_columns),
        'all_columns_present' => empty($missing_columns),
        'api_query_works' => $api_test_result['success'],
        'ready_for_production' => empty($missing_columns) && $api_test_result['success'],
    ],
];

json_ok($response);
