<?php
/**
 * Script pour obtenir un token de debug
 * À utiliser UNIQUEMENT en développement
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔐 Obtenir un Token de Debug</h1>";

// Charger Dolibarr
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists(__DIR__ . "/../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../main.inc.php";
}
if (!$res && file_exists(__DIR__ . "/../../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../../main.inc.php";
}

if (!$res) {
    die("Erreur: Impossible de charger Dolibarr");
}

global $db, $conf, $user;

echo "<div style='background: #f3f4f6; padding: 20px; border-radius: 8px; font-family: monospace;'>";

// Vérifier si l'utilisateur est connecté via session Dolibarr
if ($user && $user->id > 0) {
    echo "<h2 style='color: #10b981;'>✓ Vous êtes connecté!</h2>";
    echo "<p><strong>User ID:</strong> " . $user->id . "</p>";
    echo "<p><strong>Login:</strong> " . $user->login . "</p>";
    echo "<p><strong>Nom:</strong> " . $user->firstname . " " . $user->lastname . "</p>";

    // Générer un token de debug (basé sur la session)
    $debug_token = base64_encode($user->login . ':' . session_id());

    echo "<hr>";
    echo "<h2>📋 Token de Debug</h2>";
    echo "<p style='background: white; padding: 15px; border-radius: 4px; word-break: break-all;'>";
    echo "<strong>Token:</strong><br>";
    echo "<code style='color: #667eea; font-size: 14px;'>" . $debug_token . "</code>";
    echo "</p>";

    echo "<hr>";
    echo "<h2>💡 Comment l'utiliser dans le Monitor Live</h2>";
    echo "<ol style='line-height: 2;'>";
    echo "<li>Ouvrez la console du navigateur (F12)</li>";
    echo "<li>Tapez: <code style='background: white; padding: 2px 8px; border-radius: 4px;'>localStorage.setItem('auth_token', '" . $debug_token . "')</code></li>";
    echo "<li>Appuyez sur Entrée</li>";
    echo "<li>Rechargez la page</li>";
    echo "<li>Testez l'upload!</li>";
    echo "</ol>";

    echo "<hr>";
    echo "<h2>🚀 Ou testez directement ici</h2>";
    echo '<form method="POST" enctype="multipart/form-data" style="background: white; padding: 20px; border-radius: 8px; margin-top: 15px;">';
    echo '  <label>Event ID:</label><br>';
    echo '  <input type="number" name="event_id" value="74049" style="width: 100%; padding: 10px; margin: 10px 0; border: 2px solid #e5e7eb; border-radius: 4px;"><br>';
    echo '  <label>Photo:</label><br>';
    echo '  <input type="file" name="test_photo" accept="image/*" style="width: 100%; padding: 10px; margin: 10px 0;"><br>';
    echo '  <button type="submit" style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">📤 Uploader</button>';
    echo '</form>';

} else {
    echo "<h2 style='color: #ef4444;'>✗ Vous n'êtes pas connecté</h2>";
    echo "<p>Vous devez d'abord vous connecter à Dolibarr.</p>";
    echo "<p><a href='/index.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>Se connecter à Dolibarr</a></p>";
}

echo "</div>";

// Test d'upload si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_photo']) && $user && $user->id > 0) {
    echo "<div style='background: #1f2937; color: white; padding: 20px; border-radius: 8px; margin-top: 20px; font-family: monospace;'>";
    echo "<h2>📊 Résultat du Test</h2>";

    $event_id = (int)$_POST['event_id'];
    $file = $_FILES['test_photo'];

    echo "<p>Event ID: " . $event_id . "</p>";
    echo "<p>Fichier: " . $file['name'] . "</p>";
    echo "<p>Taille: " . round($file['size'] / 1024, 2) . " KB</p>";

    if ($file['error'] === UPLOAD_ERR_OK) {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $upload_dir = $conf->mv3pro_portail->dir_output . '/planning/' . $event_id;
        echo "<p>Répertoire: " . $upload_dir . "</p>";

        dol_mkdir($upload_dir);

        $filename = basename($file['name']);
        $dest = $upload_dir . '/' . $filename;

        echo "<p>Destination: " . $dest . "</p>";

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            echo "<p style='color: #10b981; font-size: 18px; font-weight: bold;'>✓ UPLOAD RÉUSSI!</p>";
            echo "<p>Fichier existe: " . (file_exists($dest) ? "OUI" : "NON") . "</p>";
            echo "<p>Taille sur disque: " . filesize($dest) . " bytes</p>";

            // Tester l'insertion dans ecm_files
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_files (
                label, entity, filepath, filename,
                src_object_type, src_object_id, fullpath_orig,
                position, date_c, fk_user_c
            ) VALUES (
                '".$db->escape($file['name'])."',
                ".(int)$conf->entity.",
                '".$db->escape('mv3pro_portail/planning/' . $event_id)."',
                '".$db->escape($filename)."',
                'actioncomm',
                ".(int)$event_id.",
                '".$db->escape($file['name'])."',
                0,
                '".$db->idate(dol_now())."',
                ".(int)$user->id."
            )";

            $resql = $db->query($sql);
            if ($resql) {
                echo "<p style='color: #10b981;'>✓ Entrée ajoutée dans ecm_files</p>";
            } else {
                echo "<p style='color: #ef4444;'>✗ Erreur SQL: " . $db->lasterror() . "</p>";
            }

        } else {
            echo "<p style='color: #ef4444; font-size: 18px; font-weight: bold;'>✗ ÉCHEC UPLOAD</p>";
            echo "<p>Erreur: " . error_get_last()['message'] . "</p>";
        }
    } else {
        echo "<p style='color: #ef4444;'>Erreur upload PHP: " . $file['error'] . "</p>";
    }

    echo "</div>";
}
