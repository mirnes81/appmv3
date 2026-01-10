<?php
/**
 * API Générique - Récupération d'un objet Dolibarr
 *
 * GET /api/v1/object/get.php?type=actioncomm&id=74049
 *
 * Retourne: détails + extrafields + fichiers
 *
 * Types supportés: actioncomm, task, project
 */

// Bootstrap Dolibarr
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
    $res = include "../../../../main.inc.php";
}

if (!$res) {
    die(json_encode(['error' => 'Dolibarr main.inc.php not found']));
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier que l'utilisateur est connecté
if (!$user || !$user->id) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

// Charger le helper
require_once DOL_DOCUMENT_ROOT . '/custom/mv3pro_portail/class/object_helper.class.php';

// Récupérer les paramètres
$type = GETPOST('type', 'alpha');
$id = GETPOST('id', 'int');

// Validation
if (empty($type) || empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants: type et id requis']);
    exit;
}

// Vérifier que le type est supporté
if (!in_array($type, ObjectHelper::getSupportedTypes())) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Type non supporté: ' . $type,
        'supported_types' => ObjectHelper::getSupportedTypes()
    ]);
    exit;
}

try {
    // Créer le helper
    $helper = new ObjectHelper($db, $user);

    // Récupérer l'objet
    $data = $helper->getObject($type, $id);

    if ($data === false) {
        http_response_code(404);
        echo json_encode(['error' => $helper->error ?? 'Objet non trouvé']);
        exit;
    }

    // Extraire les données pour le JSON (ne pas renvoyer l'objet Dolibarr complet)
    $object = $data['object'];
    $response = [
        'id' => $data['id'],
        'ref' => $data['ref'],
        'label' => $data['label'],
        'type' => $data['type'],
        'extrafields' => $data['extrafields'] ?? [],
        'files' => $data['files'] ?? [],
    ];

    // Ajouter des infos spécifiques selon le type
    switch ($type) {
        case 'actioncomm':
            $response['datep'] = $object->datep ?? null;
            $response['datef'] = $object->datef ?? null;
            $response['location'] = $object->location ?? '';
            $response['note'] = $object->note_private ?? '';
            $response['userownerid'] = $object->userownerid ?? 0;
            $response['fk_project'] = $object->fk_project ?? 0;
            break;

        case 'task':
            $response['date_start'] = $object->date_start ?? null;
            $response['date_end'] = $object->date_end ?? null;
            $response['progress'] = $object->progress ?? 0;
            $response['fk_project'] = $object->fk_project ?? 0;
            break;

        case 'project':
            $response['date_start'] = $object->date_start ?? null;
            $response['date_end' => $object->date_end ?? null;
            $response['description'] = $object->description ?? '';
            break;
    }

    // Statistiques sur les fichiers
    $response['files_count'] = count($data['files']);
    $response['photos_count'] = count(array_filter($data['files'], function($f) {
        return $f['is_image'];
    }));

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
