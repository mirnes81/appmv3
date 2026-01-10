<?php
/**
 * Dolibarr Bootstrap Helper
 * Centralise le chargement de Dolibarr pour tous les fichiers
 */

function loadDolibarr($defines = [])
{
    foreach ($defines as $define => $value) {
        if (!defined($define)) {
            define($define, $value);
        }
    }

    $res = 0;
    $paths = [
        __DIR__ . "/../../../main.inc.php",
        __DIR__ . "/../../../../main.inc.php",
        __DIR__ . "/../../../../../main.inc.php",
        __DIR__ . "/../../../../../../main.inc.php"
    ];

    foreach ($paths as $path) {
        if (!$res && file_exists($path)) {
            $res = @include_once $path;
            if ($res) break;
        }
    }

    if (!$res) {
        http_response_code(500);
        if (isset($defines['NOLOGIN']) && $defines['NOLOGIN']) {
            echo json_encode(['success' => false, 'message' => 'Impossible de charger Dolibarr'], JSON_UNESCAPED_UNICODE);
        } else {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erreur</title></head><body>';
            echo '<h1>Erreur de configuration</h1><p>Impossible de charger Dolibarr</p></body></html>';
        }
        exit;
    }

    return $res;
}
