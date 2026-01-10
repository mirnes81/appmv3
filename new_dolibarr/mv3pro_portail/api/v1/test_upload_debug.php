<?php
/**
 * Script de debug pour tester l'environnement d'upload
 * Accès: /custom/mv3pro_portail/api/v1/test_upload_debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Test Upload Debug</h1>";
echo "<pre>";

echo "=== 1. Vérification PHP Upload ===\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "\n";
echo "\n";

echo "=== 2. Chargement Bootstrap ===\n";
try {
    require_once __DIR__ . '/_bootstrap.php';
    echo "✓ Bootstrap chargé\n";
} catch (Exception $e) {
    echo "✗ ERREUR Bootstrap: " . $e->getMessage() . "\n";
    exit;
}
echo "\n";

echo "=== 3. Vérification Globals ===\n";
global $db, $conf, $user;
echo "DB: " . (isset($db) && $db ? "✓ OK" : "✗ MANQUANT") . "\n";
echo "Conf: " . (isset($conf) && $conf ? "✓ OK" : "✗ MANQUANT") . "\n";
echo "User: " . (isset($user) && $user ? "✓ OK (ID: " . $user->id . ")" : "✗ MANQUANT") . "\n";
echo "\n";

echo "=== 4. Vérification DOL_DOCUMENT_ROOT ===\n";
echo "DOL_DOCUMENT_ROOT: " . (defined('DOL_DOCUMENT_ROOT') ? DOL_DOCUMENT_ROOT : "✗ NON DÉFINI") . "\n";
echo "Existe: " . (is_dir(DOL_DOCUMENT_ROOT) ? "✓ OUI" : "✗ NON") . "\n";
echo "\n";

echo "=== 5. Vérification Module mv3pro_portail ===\n";
echo "Module activé: " . (isset($conf->mv3pro_portail) ? "✓ OUI" : "✗ NON") . "\n";
echo "dir_output défini: " . (isset($conf->mv3pro_portail->dir_output) ? "✓ OUI" : "✗ NON") . "\n";
if (isset($conf->mv3pro_portail->dir_output)) {
    echo "dir_output: " . $conf->mv3pro_portail->dir_output . "\n";
    echo "Dir existe: " . (is_dir($conf->mv3pro_portail->dir_output) ? "✓ OUI" : "✗ NON") . "\n";
    echo "Dir writable: " . (is_writable($conf->mv3pro_portail->dir_output) ? "✓ OUI" : "✗ NON") . "\n";
}
echo "\n";

echo "=== 6. Test Création Répertoire ===\n";
if (isset($conf->mv3pro_portail->dir_output)) {
    $test_dir = $conf->mv3pro_portail->dir_output . '/planning_test_' . time();
    echo "Test dir: " . $test_dir . "\n";

    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    echo "files.lib.php chargé: ✓\n";

    $result = dol_mkdir($test_dir);
    echo "dol_mkdir result: " . $result . "\n";
    echo "Dir créé: " . (is_dir($test_dir) ? "✓ OUI" : "✗ NON") . "\n";

    if (is_dir($test_dir)) {
        // Tester écriture
        $test_file = $test_dir . '/test.txt';
        $write_result = file_put_contents($test_file, 'test');
        echo "Écriture fichier: " . ($write_result !== false ? "✓ OK" : "✗ ÉCHEC") . "\n";

        // Nettoyer
        if (file_exists($test_file)) unlink($test_file);
        rmdir($test_dir);
        echo "Nettoyage: ✓\n";
    }
}
echo "\n";

echo "=== 7. Test Chargement ActionComm ===\n";
try {
    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
    echo "✓ actioncomm.class.php chargé\n";
    $test_action = new ActionComm($db);
    echo "✓ Classe ActionComm instanciée\n";
} catch (Exception $e) {
    echo "✗ ERREUR: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== 8. Test Authentification ===\n";
try {
    $auth = require_auth(false);
    if ($auth) {
        echo "✓ Utilisateur authentifié\n";
        echo "  User ID: " . $auth['user_id'] . "\n";
        echo "  Login: " . $auth['login'] . "\n";
    } else {
        echo "✗ Non authentifié\n";
    }
} catch (Exception $e) {
    echo "✗ ERREUR Auth: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== 9. Vérification Base de Données ===\n";
if ($db) {
    $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."actioncomm";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        echo "✓ Connexion DB OK\n";
        echo "  Nombre d'événements: " . $obj->nb . "\n";
    } else {
        echo "✗ ERREUR SQL: " . $db->lasterror() . "\n";
    }

    // Test table ecm_files
    $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."ecm_files";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        echo "✓ Table ecm_files OK\n";
        echo "  Nombre de fichiers: " . $obj->nb . "\n";
    } else {
        echo "✗ ERREUR ecm_files: " . $db->lasterror() . "\n";
    }
}
echo "\n";

echo "=== 10. Permissions Système ===\n";
echo "User PHP: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()) . "\n";
echo "Temp dir: " . sys_get_temp_dir() . "\n";
echo "Temp writable: " . (is_writable(sys_get_temp_dir()) ? "✓ OUI" : "✗ NON") . "\n";
echo "\n";

echo "</pre>";

echo "<hr>";
echo "<h2>Test Upload Manuel</h2>";
echo '<form method="POST" enctype="multipart/form-data">';
echo '  Event ID: <input type="number" name="event_id" value="74049"><br><br>';
echo '  Fichier: <input type="file" name="test_file" accept="image/*"><br><br>';
echo '  <button type="submit">Tester Upload</button>';
echo '</form>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<pre>";
    echo "=== TEST UPLOAD ===\n";
    echo "Event ID: " . $_POST['event_id'] . "\n";
    echo "Fichier: " . print_r($_FILES['test_file'], true);

    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $event_id = (int)$_POST['event_id'];
        $file = $_FILES['test_file'];

        $upload_dir = $conf->mv3pro_portail->dir_output . '/planning/' . $event_id;
        echo "Upload dir: " . $upload_dir . "\n";

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        dol_mkdir($upload_dir);

        $dest = $upload_dir . '/' . basename($file['name']);
        echo "Destination: " . $dest . "\n";

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            echo "✓ UPLOAD RÉUSSI!\n";
            echo "Fichier existe: " . (file_exists($dest) ? "OUI" : "NON") . "\n";
            echo "Taille: " . filesize($dest) . " bytes\n";
        } else {
            echo "✗ ÉCHEC move_uploaded_file\n";
            echo "Erreur: " . error_get_last()['message'] . "\n";
        }
    } else {
        echo "✗ Erreur upload: " . $_FILES['test_file']['error'] . "\n";
    }
    echo "</pre>";
}
