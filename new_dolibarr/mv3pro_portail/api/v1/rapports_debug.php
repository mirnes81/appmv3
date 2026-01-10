<?php
/**
 * DEBUG /api/v1/rapports_debug.php
 *
 * Diagnostic pour comprendre pourquoi les rapports ne s'affichent pas
 */

require_once __DIR__ . '/_bootstrap.php';

global $db, $conf;

require_method('GET');

$auth = require_auth(true);

$entity = isset($conf->entity) ? (int)$conf->entity : 1;

// 1. Info utilisateur connecté
$user_info = [
    'mode' => $auth['mode'] ?? 'N/A',
    'user_id' => $auth['user_id'] ?? null,
    'mobile_user_id' => $auth['mobile_user_id'] ?? null,
    'email' => $auth['email'] ?? 'N/A',
    'name' => $auth['name'] ?? 'N/A',
    'is_unlinked' => $auth['is_unlinked'] ?? false,
    'dolibarr_user_id' => $auth['dolibarr_user'] ? $auth['dolibarr_user']->id : null,
    'is_admin' => $auth['dolibarr_user'] ? $auth['dolibarr_user']->admin : false,
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

// 4. Compter les rapports avec le filtre actuel
$filter_user_id = $auth['user_id'] ?? null;
$rapports_with_filter = 0;

if ($filter_user_id) {
    $sql_filtered = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."mv3_rapport
                     WHERE entity = ".$entity." AND fk_user = ".(int)$filter_user_id;
    $resql_filtered = $db->query($sql_filtered);
    if ($resql_filtered) {
        $obj = $db->fetch_object($resql_filtered);
        $rapports_with_filter = (int)$obj->total;
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
if ($total_rapports > 0 && $rapports_with_filter === 0) {
    $recommendation = "PROBLÈME DÉTECTÉ : Il y a {$total_rapports} rapport(s) dans l'entité, mais 0 visible avec le filtre user_id={$filter_user_id}. ";

    if ($user_info['is_unlinked']) {
        $recommendation .= "Le compte mobile n'est pas lié à un utilisateur Dolibarr. Vous devez lier ce compte dans /custom/mv3pro_portail/mobile_app/admin/manage_users.php";
    } elseif (!$filter_user_id) {
        $recommendation .= "Aucun user_id détecté dans l'authentification.";
    } else {
        $recommendation .= "Les rapports ne sont pas créés avec fk_user={$filter_user_id}. Solution: Modifier l'API pour afficher tous les rapports de l'entité (sans filtre par utilisateur) ou créer les rapports avec le bon fk_user.";
    }
}

$response = [
    'success' => true,
    'debug_info' => [
        'user_info' => $user_info,
        'entity' => $entity,
        'total_rapports' => $total_rapports,
        'rapports_by_user' => $rapports_by_user,
        'rapports_with_filter' => $rapports_with_filter,
        'filter_applied' => $filter_user_id ? "fk_user = {$filter_user_id}" : 'AUCUN (compte unlinked)',
        'recent_rapports' => $recent_rapports,
    ],
    'recommendation' => $recommendation,
    'solution' => 'Voir la recommandation ci-dessus ou modifier rapports.php pour ne pas filtrer par user_id',
];

json_ok($response);
