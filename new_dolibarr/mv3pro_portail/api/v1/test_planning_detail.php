<?php
/**
 * Test API planning_view.php
 * Usage: /api/v1/test_planning_detail.php?id=123
 */

require_once __DIR__.'/_bootstrap.php';

$id = (int)get_param('id', 0);

if (!$id) {
    echo "<h1>Test Planning Detail API</h1>";
    echo "<p>Usage: ?id=XXXX</p>";
    echo "<p>Exemple: <a href='?id=1'>?id=1</a></p>";
    exit;
}

echo "<h1>Test Planning Detail #$id</h1>";
echo "<hr>";

// Test 1: Vérifier l'existence de l'événement
echo "<h2>1. Test existence événement</h2>";
$sql = "SELECT id, label FROM ".MAIN_DB_PREFIX."actioncomm WHERE id = ".$id;
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    $event = $db->fetch_object($resql);
    $db->free($resql);
    echo "<p style='color: green'>✅ Événement trouvé: " . htmlspecialchars($event->label) . "</p>";
} else {
    if ($resql) {
        $db->free($resql);
    }
    echo "<p style='color: red'>❌ Événement non trouvé</p>";
    echo "<p>SQL: " . htmlspecialchars($sql) . "</p>";
    exit;
}

// Test 2: Vérifier les fichiers
echo "<h2>2. Test fichiers joints</h2>";
$upload_dir = DOL_DATA_ROOT.'/actioncomm/'.$id;
echo "<p>Répertoire: <code>" . htmlspecialchars($upload_dir) . "</code></p>";

if (is_dir($upload_dir)) {
    echo "<p style='color: green'>✅ Répertoire existe</p>";
    $files = scandir($upload_dir);
    $real_files = array_filter($files, function($f) use ($upload_dir) {
        return $f !== '.' && $f !== '..' && !is_dir($upload_dir.'/'.$f);
    });

    if (count($real_files) > 0) {
        echo "<p style='color: green'>✅ " . count($real_files) . " fichier(s) trouvé(s)</p>";
        echo "<ul>";
        foreach ($real_files as $file) {
            $size = filesize($upload_dir.'/'.$file);
            $mime = mime_content_type($upload_dir.'/'.$file);
            echo "<li>" . htmlspecialchars($file) . " (" . $mime . ", " . number_format($size/1024, 2) . " KB)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange'>⚠️ Répertoire vide</p>";
    }
} else {
    echo "<p style='color: orange'>⚠️ Répertoire n'existe pas</p>";
}

// Test 3: Appel API complet
echo "<h2>3. Test API planning_view.php</h2>";
echo "<p>API URL: <code>/api/v1/planning_view.php?id=$id</code></p>";
echo "<p><a href='planning_view.php?id=$id' target='_blank'>Ouvrir l'API</a></p>";

echo "<hr>";
echo "<p><a href='../../../pwa_dist/#/planning/$id'>Ouvrir dans la PWA</a></p>";
