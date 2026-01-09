<?php
/**
 * DEBUG Planning - Vérifier le lien utilisateur mobile <-> Dolibarr
 *
 * Ce script aide à diagnostiquer pourquoi un utilisateur ne voit pas ses événements
 */

require_once __DIR__ . '/_bootstrap.php';

global $db, $conf;

// Authentification obligatoire
$auth = require_auth(true);

$from = get_param('from', date('Y-m-d'));
$to = get_param('to', date('Y-m-d', strtotime('+30 days')));

// Informations sur l'utilisateur connecté
$result = [
    'user_info' => [
        'auth_mode' => $auth['mode'],
        'mobile_user_id' => $auth['mobile_user_id'] ?? null,
        'dolibarr_user_id' => $auth['user_id'] ?? null,
        'email' => $auth['email'] ?? null,
        'name' => $auth['name'] ?? null,
        'is_unlinked' => $auth['is_unlinked'] ?? false,
    ],
];

// Si compte unlinked, arrêter ici
if (empty($auth['user_id'])) {
    $result['error'] = 'Compte non lié à un utilisateur Dolibarr';
    $result['solution'] = 'Modifier la table llx_mv3_mobile_users pour renseigner dolibarr_user_id';
    json_ok($result);
}

$dolibarr_user_id = (int)$auth['user_id'];

// 1. Vérifier l'utilisateur Dolibarr
$sql = "SELECT rowid, login, lastname, firstname, email, statut
        FROM ".MAIN_DB_PREFIX."user
        WHERE rowid = ".$dolibarr_user_id;

$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    $dol_user = $db->fetch_object($resql);
    $result['dolibarr_user'] = [
        'rowid' => (int)$dol_user->rowid,
        'login' => $dol_user->login,
        'lastname' => $dol_user->lastname,
        'firstname' => $dol_user->firstname,
        'email' => $dol_user->email,
        'statut' => (int)$dol_user->statut,
        'statut_label' => $dol_user->statut == 1 ? 'Actif' : 'Inactif',
    ];
} else {
    $result['dolibarr_user'] = null;
    $result['error'] = 'Utilisateur Dolibarr non trouvé avec ID = '.$dolibarr_user_id;
}

// 2. Chercher les événements où l'utilisateur est auteur
$sql = "SELECT COUNT(*) as nb
        FROM ".MAIN_DB_PREFIX."actioncomm
        WHERE fk_user_author = ".$dolibarr_user_id."
        AND entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1);

$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $result['events_stats']['as_author'] = (int)$obj->nb;
}

// 3. Chercher les événements où l'utilisateur est assigné (fk_user_action)
$sql = "SELECT COUNT(*) as nb
        FROM ".MAIN_DB_PREFIX."actioncomm
        WHERE fk_user_action = ".$dolibarr_user_id."
        AND entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1);

$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $result['events_stats']['as_action_user'] = (int)$obj->nb;
}

// 4. Chercher les événements où l'utilisateur est dans les ressources
$sql = "SELECT COUNT(DISTINCT a.id) as nb
        FROM ".MAIN_DB_PREFIX."actioncomm a
        INNER JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
        WHERE ar.element_type = 'user'
        AND ar.fk_element = ".$dolibarr_user_id."
        AND a.entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1);

$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $result['events_stats']['as_resource'] = (int)$obj->nb;
}

// 5. Total événements dans la période
$sql = "SELECT COUNT(DISTINCT a.id) as nb
        FROM ".MAIN_DB_PREFIX."actioncomm a
        LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
        WHERE (a.fk_user_author = ".$dolibarr_user_id."
               OR a.fk_user_action = ".$dolibarr_user_id."
               OR a.fk_user_done = ".$dolibarr_user_id."
               OR (ar.element_type = 'user' AND ar.fk_element = ".$dolibarr_user_id."))
        AND a.entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1)."
        AND (
            (a.datep2 IS NOT NULL AND DATE(a.datep) <= '".$db->escape($to)."' AND DATE(a.datep2) >= '".$db->escape($from)."')
            OR (a.datep2 IS NULL AND DATE(a.datep) >= '".$db->escape($from)."' AND DATE(a.datep) <= '".$db->escape($to)."')
        )";

$resql = $db->query($sql);
if ($resql) {
    $obj = $db->fetch_object($resql);
    $result['events_stats']['total_in_period'] = (int)$obj->nb;
}

// 6. Exemples d'événements dans la période (max 5)
$note_private_field = mv3_select_column($db, 'actioncomm', 'note_private', '', 'a');

$sql = "SELECT DISTINCT a.id, a.label, a.datep, a.datep2,
        a.fk_user_author, a.fk_user_action, a.fk_user_done,
        ".$note_private_field.",
        s.nom as client_nom,
        p.ref as projet_ref, p.title as projet_title
        FROM ".MAIN_DB_PREFIX."actioncomm a
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = a.fk_soc
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = a.fk_project
        LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
        WHERE (a.fk_user_author = ".$dolibarr_user_id."
               OR a.fk_user_action = ".$dolibarr_user_id."
               OR a.fk_user_done = ".$dolibarr_user_id."
               OR (ar.element_type = 'user' AND ar.fk_element = ".$dolibarr_user_id."))
        AND a.entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1)."
        AND (
            (a.datep2 IS NOT NULL AND DATE(a.datep) <= '".$db->escape($to)."' AND DATE(a.datep2) >= '".$db->escape($from)."')
            OR (a.datep2 IS NULL AND DATE(a.datep) >= '".$db->escape($from)."' AND DATE(a.datep) <= '".$db->escape($to)."')
        )
        ORDER BY a.datep DESC
        LIMIT 5";

$resql = $db->query($sql);
if ($resql) {
    $events = [];
    while ($obj = $db->fetch_object($resql)) {
        $events[] = [
            'id' => (int)$obj->id,
            'label' => $obj->label,
            'datep' => $obj->datep,
            'datep2' => $obj->datep2,
            'client' => $obj->client_nom,
            'projet' => $obj->projet_title ? ($obj->projet_ref ? $obj->projet_ref.' - ' : '').$obj->projet_title : null,
            'fk_user_author' => (int)$obj->fk_user_author,
            'fk_user_action' => (int)$obj->fk_user_action,
            'fk_user_done' => (int)$obj->fk_user_done,
            'note_private' => $obj->note_private,
        ];
    }
    $result['events_samples'] = $events;
}

// 7. Diagnostic
$result['diagnostic'] = [];

if (empty($auth['user_id'])) {
    $result['diagnostic'][] = [
        'type' => 'ERROR',
        'message' => 'Le compte mobile n\'est pas lié à un utilisateur Dolibarr',
        'solution' => 'UPDATE llx_mv3_mobile_users SET dolibarr_user_id = [ID_DOLIBARR] WHERE rowid = '.$auth['mobile_user_id'],
    ];
}

if (!isset($result['dolibarr_user']) || !$result['dolibarr_user']) {
    $result['diagnostic'][] = [
        'type' => 'ERROR',
        'message' => 'L\'utilisateur Dolibarr n\'existe pas ou est supprimé',
        'solution' => 'Vérifier que l\'utilisateur existe dans Dolibarr avec ID = '.$dolibarr_user_id,
    ];
} elseif ($result['dolibarr_user']['statut'] != 1) {
    $result['diagnostic'][] = [
        'type' => 'WARNING',
        'message' => 'L\'utilisateur Dolibarr est inactif',
        'solution' => 'Activer l\'utilisateur dans Dolibarr > Menu > Utilisateurs',
    ];
}

$total = $result['events_stats']['total_in_period'] ?? 0;
if ($total == 0) {
    $result['diagnostic'][] = [
        'type' => 'WARNING',
        'message' => 'Aucun événement trouvé pour cet utilisateur dans la période',
        'explanation' => [
            'Vérifier dans Dolibarr > Agenda que les événements sont bien créés',
            'Vérifier que les événements sont bien assignés à l\'utilisateur (champ "Assigné à")',
            'Vérifier les dates de début/fin des événements',
        ],
    ];
} else {
    $result['diagnostic'][] = [
        'type' => 'OK',
        'message' => $total . ' événement(s) trouvé(s) dans la période du ' . $from . ' au ' . $to,
    ];
}

// Retourner le rapport complet
json_ok($result);
