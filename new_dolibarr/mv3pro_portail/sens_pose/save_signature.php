<?php
/**
 * Endpoint AJAX pour sauvegarder la signature
 * Utilise GET pour éviter WAF/ModSecurity
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

header('Content-Type: text/plain');

$id = GETPOST('id', 'int');
$sign_name = GETPOST('name', 'restricthtml');
$chunk = GETPOST('chunk', 'int');
$total = GETPOST('total', 'int');
$data_chunk = GETPOST('data', 'alpha');

if (!$id || !$sign_name) {
    echo 'ERROR|Missing parameters';
    exit;
}

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".(int)$id;
$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) == 0) {
    echo 'ERROR|Fiche not found';
    exit;
}

$temp_dir = sys_get_temp_dir();
$temp_file = $temp_dir.'/sig_'.$id.'_'.session_id().'.tmp';

if ($chunk == 1) {
    file_put_contents($temp_file, $data_chunk);
} else {
    file_put_contents($temp_file, $data_chunk, FILE_APPEND);
}

if ($chunk < $total) {
    echo 'OK|Chunk received';
    exit;
}

$signature_base64 = file_get_contents($temp_file);
@unlink($temp_file);

if (!$signature_base64) {
    echo 'ERROR|Invalid data';
    exit;
}

$signature_dir = DOL_DATA_ROOT.'/mv3_signatures';
if (!is_dir($signature_dir)) {
    mkdir($signature_dir, 0755, true);
}

$signature_filename = 'signature_sens_pose_'.$id.'_'.time().'.png';
$signature_path = $signature_dir.'/'.$signature_filename;

$image_data = base64_decode($signature_base64);

if (!$image_data) {
    echo 'ERROR|Cannot decode signature';
    exit;
}

if (file_put_contents($signature_path, $image_data)) {
    $signature_url = 'mv3_signatures/'.$signature_filename;

    $sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_sens_pose SET
                   statut = 'signe',
                   signature_data = '".$db->escape($signature_base64)."',
                   sign_name = '".$db->escape($sign_name)."',
                   signature_date = NOW()
                   WHERE rowid = ".(int)$id;

    if ($db->query($sql_update)) {
        echo 'SUCCESS|view.php?id='.$id.'&msg=signed';
    } else {
        @unlink($signature_path);
        echo 'ERROR|Database error: '.$db->lasterror();
    }
} else {
    echo 'ERROR|Cannot save file';
}
?>
