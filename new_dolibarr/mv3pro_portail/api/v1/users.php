<?php
/**
 * GET /api/v1/users.php
 *
 * Liste des utilisateurs Dolibarr actifs (pour filtres admin)
 * Accessible uniquement aux administrateurs
 */

require_once __DIR__ . '/_bootstrap.php';

global $db, $conf;

// Méthode GET uniquement
require_method('GET');

// Authentification obligatoire
$auth = require_auth(true);

// Récupérer le statut admin
$is_admin = (!empty($auth['dolibarr_user']) && !empty($auth['dolibarr_user']->admin));

// Vérifier que l'utilisateur est admin
if (!$is_admin) {
    json_error('Accès réservé aux administrateurs', 'FORBIDDEN', 403);
}

// Récupérer l'entité
$entity = isset($conf->entity) ? (int)$conf->entity : 1;

// Récupérer les utilisateurs actifs de l'entité
$sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.email, u.admin, u.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE u.entity = ".$entity;
$sql .= " AND u.statut = 1"; // Seulement utilisateurs actifs
$sql .= " ORDER BY u.lastname ASC, u.firstname ASC";

$resql = $db->query($sql);

if (!$resql) {
    log_error('SQL_ERROR', 'Failed to fetch users', [
        'sql' => $sql,
        'db_error' => $db->lasterror()
    ]);
    json_error('Erreur lors de la récupération des utilisateurs', 'DATABASE_ERROR', 500);
}

$users = [];
while ($obj = $db->fetch_object($resql)) {
    $users[] = [
        'id' => (int)$obj->rowid,
        'login' => $obj->login,
        'firstname' => $obj->firstname,
        'lastname' => $obj->lastname,
        'name' => trim($obj->firstname . ' ' . $obj->lastname),
        'email' => $obj->email,
        'admin' => (int)$obj->admin === 1,
    ];
}
$db->free($resql);

log_debug('users.php success', [
    'count' => count($users),
    'admin_user' => $auth['dolibarr_user']->id
]);

// Retourner avec format standard API v1
json_ok([
    'data' => [
        'users' => $users,
        'count' => count($users)
    ]
]);
