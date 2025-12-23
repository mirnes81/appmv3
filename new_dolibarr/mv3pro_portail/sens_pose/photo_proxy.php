<?php
/**
 * Proxy pour afficher les photos depuis la BDD
 * Usage: photo_proxy.php?piece_id=123
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

if (!$res) die("Include of main fails");

$debug = GETPOST('debug', 'int');
$piece_id = GETPOST('piece_id', 'int');

if (!$piece_id) {
    http_response_code(400);
    die("Missing piece_id");
}

$sql = "SELECT photo_url, photo_filename, fk_product
        FROM ".MAIN_DB_PREFIX."mv3_sens_pose_pieces
        WHERE rowid = ".(int)$piece_id;

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) == 0) {
    http_response_code(404);
    die("Piece not found");
}

$piece = $db->fetch_object($resql);

if (empty($piece->photo_url)) {
    http_response_code(404);
    die("No photo for this piece");
}

$photo_url_clean = html_entity_decode($piece->photo_url);

if ($debug) {
    header('Content-Type: text/plain');
    echo "DEBUG INFO:\n";
    echo "piece_id: ".$piece_id."\n";
    echo "photo_url (original): ".$piece->photo_url."\n";
    echo "photo_url (decoded): ".$photo_url_clean."\n";
    echo "photo_filename: ".$piece->photo_filename."\n";
    echo "DOL_DATA_ROOT: ".DOL_DATA_ROOT."\n\n";
}

$photo_url_parsed = parse_url($photo_url_clean);

if ($debug) {
    echo "Parsed URL:\n";
    print_r($photo_url_parsed);
    echo "\n\n";
}

if (isset($photo_url_parsed['query'])) {
    parse_str($photo_url_parsed['query'], $params);

    if ($debug) {
        echo "Query params:\n";
        print_r($params);
        echo "\n\n";
    }

    if (isset($params['file']) && isset($params['modulepart'])) {
        $file_path = DOL_DATA_ROOT.'/'.$params['modulepart'].'/'.$params['file'];

        if ($debug) {
            echo "file_path: ".$file_path."\n";
            echo "file_exists: ".(file_exists($file_path) ? 'YES' : 'NO')."\n";
            exit;
        }

        if (file_exists($file_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);

            header('Content-Type: ' . $mime_type);
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: public, max-age=31536000');
            readfile($file_path);
            exit;
        } else {
            http_response_code(404);
            die("File not found on disk: ".$file_path);
        }
    }
}

http_response_code(400);
die("Cannot parse photo URL: ".$piece->photo_url);
?>
