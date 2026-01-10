<?php
/**
 * API v1 - Sens de Pose
 * GET /api/v1/sens_pose.php - Liste des sens de pose
 * POST /api/v1/sens_pose.php - Créer un sens de pose
 */

require_once __DIR__.'/_bootstrap.php';
require_once __DIR__ . '/../../core/init.php';

require_method(['GET', 'POST']);
$auth = require_auth();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Vérifier si la table existe
        if (!mv3_table_exists($db, 'mv3_sens_pose')) {
            log_debug('Table mv3_sens_pose not found, returning empty array');
            json_ok(['sens_pose' => []]);
        }

        // Liste des sens de pose
        $sql = "SELECT s.rowid, s.ref, s.date_creation, s.fk_project, s.fk_user,
                       p.ref as projet_ref, p.title as projet_title,
                       u.login as user_login, u.firstname, u.lastname
                FROM " . MAIN_DB_PREFIX . "mv3_sens_pose as s
                LEFT JOIN " . MAIN_DB_PREFIX . "projet as p ON p.rowid = s.fk_project
                LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = s.fk_user
                WHERE s.entity = " . (int)$conf->entity;

        // Filtre par utilisateur (admin voit tout, employé voit ses sens de pose)
        $user_filter = mv3_get_user_filter_sql($auth, 's.fk_user');
        if (!empty($user_filter)) {
            $sql .= " AND " . $user_filter;
        }

        $sql .= " ORDER BY s.date_creation DESC LIMIT 100";

        $resql = $db->query($sql);
        $sens_pose = [];

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $sens_pose[] = [
                    'id' => (int)$obj->rowid,
                    'ref' => $obj->ref,
                    'date' => $obj->date_creation,
                    'projet' => [
                        'ref' => $obj->projet_ref,
                        'title' => $obj->projet_title
                    ],
                    'user' => [
                        'login' => $obj->user_login,
                        'name' => trim($obj->firstname . ' ' . $obj->lastname)
                    ]
                ];
            }
        }

        json_ok(['sens_pose' => $sens_pose]);

    } elseif ($method === 'POST') {
        // Créer un sens de pose
        json_error('Création de sens de pose non implémentée pour le moment', 'NOT_IMPLEMENTED', 501);
    }

} catch (Exception $e) {
    log_error('SENS_POSE_ERROR', $e->getMessage());
    json_error('Erreur serveur: ' . $e->getMessage(), 'SERVER_ERROR', 500);
}
