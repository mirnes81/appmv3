<?php
/**
 * DEBUG /api/v1/rapports_debug.php
 *
 * Diagnostic pour comprendre pourquoi les rapports ne s'affichent pas
 */

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
];

json_ok($response);
