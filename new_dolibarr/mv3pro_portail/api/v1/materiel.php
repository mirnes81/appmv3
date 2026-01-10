<?php
/**
 * API v1 - Matériel
 * GET /api/v1/materiel.php - Liste du matériel
 */

require_once __DIR__.'/_bootstrap.php';

require_method('GET');
$auth = require_auth();

try {
    // Vérifier si la table existe
    if (!mv3_table_exists($db, 'mv3_materiel')) {
        log_debug('Table mv3_materiel not found, returning empty array');
        json_ok(['materiel' => []]);
    }

    // Liste du matériel
    $sql = "SELECT m.rowid, m.ref, m.label, m.type, m.status, m.date_creation,
                   m.fk_user, u.login as user_login, u.firstname, u.lastname
            FROM " . MAIN_DB_PREFIX . "mv3_materiel as m
            LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = m.fk_user
            WHERE m.entity = " . (int)$conf->entity;

    // Filtre par utilisateur si non admin
    if (empty($auth['dolibarr_user']->admin)) {
        $sql .= " AND m.fk_user = " . (int)$auth['user_id'];
    }

    $sql .= " ORDER BY m.date_creation DESC LIMIT 100";

    $resql = $db->query($sql);
    $materiel = [];

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $materiel[] = [
                'id' => (int)$obj->rowid,
                'ref' => $obj->ref,
                'label' => $obj->label,
                'type' => $obj->type,
                'status' => (int)$obj->status,
                'date' => $obj->date_creation,
                'user' => [
                    'login' => $obj->user_login,
                    'name' => trim($obj->firstname . ' ' . $obj->lastname)
                ]
            ];
        }
    }

    json_ok(['materiel' => $materiel]);

} catch (Exception $e) {
    log_error('MATERIEL_ERROR', $e->getMessage());
    json_error('Erreur serveur: ' . $e->getMessage(), 'SERVER_ERROR', 500);
}
