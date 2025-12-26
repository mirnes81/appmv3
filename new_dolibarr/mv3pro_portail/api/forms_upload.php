<?php
/**
 * Upload de photos pour un formulaire
 *
 * POST /forms/upload
 * Header: X-Auth-Token: ...
 * Body: {form_id, photos: [{data: base64, filename, description}]}
 * Returns: {"success": true, "uploaded": 3}
 */

require_once __DIR__ . '/cors_config.php';
require_once __DIR__ . '/auth_helper.php';

header('Content-Type: application/json');
setCorsHeaders();
handleCorsPreflightRequest();

require_once '../../main.inc.php';

$user = checkAuth();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

$form_id = (int)($input['form_id'] ?? 0);
$photos = $input['photos'] ?? [];

if (!$form_id) {
    http_response_code(400);
    echo json_encode(['error' => 'form_id requis']);
    exit;
}

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_rapport";
$sql .= " WHERE rowid = ".$form_id;
$sql .= " AND entity = ".$conf->entity;

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Formulaire non trouvé']);
    exit;
}

$upload_dir = DOL_DATA_ROOT.'/mv3pro_portail/rapports/'.$form_id;
if (!is_dir($upload_dir)) {
    dol_mkdir($upload_dir);
}

$uploaded_count = 0;

foreach ($photos as $index => $photo) {
    $photo_data = $photo['data'] ?? '';
    $photo_name = $photo['filename'] ?? 'photo_'.$index.'.jpg';
    $photo_desc = $photo['description'] ?? '';

    if (preg_match('/^data:image\/(\w+);base64,/', $photo_data, $type)) {
        $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
        $photo_data = base64_decode($photo_data);

        if ($photo_data === false) {
            continue;
        }

        $file_name = 'photo_'.time().'_'.$index.'.jpg';
        $file_path = $upload_dir.'/'.$file_name;

        if (file_put_contents($file_path, $photo_data)) {
            $relative_path = 'mv3pro_portail/rapports/'.$form_id.'/'.$file_name;

            $sql_photo = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport_photo (";
            $sql_photo .= "fk_rapport, filepath, filename, description, ordre, date_upload";
            $sql_photo .= ") VALUES (";
            $sql_photo .= (int)$form_id.",";
            $sql_photo .= "'".$db->escape($relative_path)."',";
            $sql_photo .= "'".$db->escape($file_name)."',";
            $sql_photo .= "'".$db->escape($photo_desc)."',";
            $sql_photo .= (int)$index.",";
            $sql_photo .= "NOW()";
            $sql_photo .= ")";

            if ($db->query($sql_photo)) {
                $uploaded_count++;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'uploaded' => $uploaded_count,
    'message' => $uploaded_count.' photo(s) uploadée(s)'
]);
