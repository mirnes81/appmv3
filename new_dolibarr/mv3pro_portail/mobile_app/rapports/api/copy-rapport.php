<?php
$res = 0;
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";
if (!$res && file_exists("../../../../../../main.inc.php")) $res = @include "../../../../../../main.inc.php";

if (!isset($_SESSION['dol_login']) || empty($user->id)) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');

$user_id = $user->id;
$action = GETPOST('action', 'alpha');

if ($action === 'list') {
    $sql = "SELECT
        r.rowid,
        r.ref,
        r.date_rapport,
        r.zone_travail,
        r.surface_carrelee,
        r.format_carreaux,
        p.ref as projet_ref,
        s.nom as client_nom
    FROM ".MAIN_DB_PREFIX."mv3_rapport r
    LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = r.fk_projet
    LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = r.fk_soc
    WHERE r.fk_user = ".(int)$user_id."
    ORDER BY r.date_rapport DESC
    LIMIT 10";

    $resql = $db->query($sql);
    $rapports = [];

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $rapports[] = [
                'rowid' => $obj->rowid,
                'ref' => $obj->ref,
                'date' => date('d/m/Y', strtotime($obj->date_rapport)),
                'zone' => $obj->zone_travail,
                'surface' => $obj->surface_carrelee,
                'format' => $obj->format_carreaux,
                'projet' => $obj->projet_ref,
                'client' => $obj->client_nom
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'rapports' => $rapports
    ]);
    exit;
}

if ($action === 'get' && GETPOST('id', 'int')) {
    $rapport_id = GETPOST('id', 'int');

    $sql = "SELECT *
    FROM ".MAIN_DB_PREFIX."mv3_rapport
    WHERE rowid = ".(int)$rapport_id."
    AND fk_user = ".(int)$user_id;

    $resql = $db->query($sql);

    if ($resql && $db->num_rows($resql) > 0) {
        $rapport = $db->fetch_object($resql);

        $data = [
            'success' => true,
            'rapport' => [
                'fk_projet' => $rapport->fk_projet,
                'fk_soc' => $rapport->fk_soc,
                'zone_travail' => $rapport->zone_travail,
                'heures_debut' => $rapport->heures_debut,
                'heures_fin' => $rapport->heures_fin,
                'surface_carrelee' => $rapport->surface_carrelee,
                'format_carreaux' => $rapport->format_carreaux,
                'type_pose' => $rapport->type_pose,
                'zone_pose' => $rapport->zone_pose,
                'travaux_realises' => $rapport->travaux_realises,
                'observations' => $rapport->observations
            ]
        ];

        echo json_encode($data);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Rapport non trouvé'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Action invalide'
    ]);
}

$db->close();
