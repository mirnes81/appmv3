<?php
/**
 * Script de création et réparation des répertoires
 * Crée automatiquement tous les répertoires nécessaires pour l'upload
 */

// Charger Dolibarr
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);

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

// Vérifier l'authentification
if (!$user || !$user->id) {
    die("Erreur: Vous devez être connecté");
}

// Charger les librairies
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réparation des Répertoires</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
        }
        h1 { color: #00ff00; border-bottom: 2px solid #00ff00; padding-bottom: 10px; }
        h2 { color: #ffff00; margin-top: 30px; }
        .ok { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffff00; }
        .info { color: #00ffff; }
        .box {
            background: #2d2d2d;
            padding: 15px;
            margin: 10px 0;
            border-left: 5px solid #00ff00;
            border-radius: 5px;
        }
        .box.error { border-left-color: #ff0000; }
        .box.warning { border-left-color: #ffff00; }
        pre { background: #1a1a1a; padding: 10px; border: 1px solid #444; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #00ff00;
            color: #000;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 10px 5px;
        }
    </style>
</head>
<body>
    <h1>🔧 Réparation des Répertoires - MV3 PRO</h1>

    <div class="box">
        <strong>Utilisateur:</strong> <?php echo htmlspecialchars($user->firstname . ' ' . $user->lastname); ?> (ID: <?php echo $user->id; ?>)
    </div>

    <h2>📁 Création des Répertoires</h2>

    <?php
    $base_dir = DOL_DATA_ROOT . '/documents/mv3pro_portail';

    $directories = [
        $base_dir,
        $base_dir . '/temp',
        $base_dir . '/rapports',
        $base_dir . '/planning',
        $base_dir . '/regie',
        $base_dir . '/sens_pose',
    ];

    echo "<div class='box'>";
    echo "<strong>Répertoire de base:</strong> <span class='info'>" . htmlspecialchars($base_dir) . "</span><br><br>";

    $all_ok = true;
    $created = [];
    $failed = [];

    foreach ($directories as $dir) {
        $dir_name = str_replace($base_dir, '', $dir);
        if (empty($dir_name)) $dir_name = '/';

        echo "📂 <strong>" . htmlspecialchars($dir_name) . "</strong>: ";

        if (is_dir($dir)) {
            echo "<span class='ok'>✅ Existe déjà</span>";
            if (is_writable($dir)) {
                echo " | <span class='ok'>✅ Écriture OK</span>";
            } else {
                echo " | <span class='error'>❌ Pas d'écriture</span>";
                $all_ok = false;
            }
            echo "<br>";
        } else {
            echo "<span class='warning'>⚠️ N'existe pas</span> → ";

            $result = dol_mkdir($dir);

            if ($result >= 0 && is_dir($dir)) {
                echo "<span class='ok'>✅ CRÉÉ avec succès</span>";
                if (is_writable($dir)) {
                    echo " | <span class='ok'>✅ Écriture OK</span>";
                } else {
                    echo " | <span class='error'>❌ Pas d'écriture</span>";
                    $all_ok = false;
                }
                $created[] = $dir;
                echo "<br>";
            } else {
                echo "<span class='error'>❌ ÉCHEC de création</span><br>";
                $failed[] = $dir;
                $all_ok = false;
            }
        }
    }

    echo "</div>";

    if (count($created) > 0) {
        echo "<div class='box'>";
        echo "<span class='ok'>✅ " . count($created) . " répertoire(s) créé(s) avec succès</span>";
        echo "</div>";
    }

    if (count($failed) > 0) {
        echo "<div class='box error'>";
        echo "<span class='error'>❌ " . count($failed) . " répertoire(s) n'ont pas pu être créés:</span><br><br>";
        foreach ($failed as $dir) {
            echo "- " . htmlspecialchars($dir) . "<br>";
        }
        echo "</div>";
    }
    ?>

    <h2>🧪 Test de Création d'un Sous-répertoire</h2>

    <?php
    $test_event_id = 74049;
    $test_dir = $base_dir . '/planning/' . $test_event_id;

    echo "<div class='box'>";
    echo "<strong>Test avec Event ID:</strong> <span class='info'>" . $test_event_id . "</span><br>";
    echo "<strong>Répertoire:</strong> <span class='info'>" . htmlspecialchars($test_dir) . "</span><br><br>";

    if (is_dir($test_dir)) {
        echo "<span class='ok'>✅ Le répertoire existe déjà</span><br>";
        if (is_writable($test_dir)) {
            echo "<span class='ok'>✅ Écriture OK</span><br>";
        } else {
            echo "<span class='error'>❌ Pas d'écriture</span><br>";
        }
    } else {
        echo "<span class='warning'>⚠️ Le répertoire n'existe pas</span><br><br>";
        echo "Tentative de création...<br>";

        $result = dol_mkdir($test_dir);

        if ($result >= 0 && is_dir($test_dir)) {
            echo "<span class='ok'>✅ SUCCÈS! Répertoire créé</span><br>";
            if (is_writable($test_dir)) {
                echo "<span class='ok'>✅ Écriture OK</span><br>";
            } else {
                echo "<span class='error'>❌ Pas d'écriture</span><br>";
            }
        } else {
            echo "<span class='error'>❌ ÉCHEC de création</span><br>";
            echo "<span class='error'>Code d'erreur: " . $result . "</span><br>";
        }
    }
    echo "</div>";
    ?>

    <h2>📊 Résultat Global</h2>

    <?php if ($all_ok && count($failed) === 0): ?>
        <div class="box">
            <span class='ok'>✅ <strong>TOUT EST OK!</strong></span><br><br>
            Tous les répertoires sont créés et accessibles en écriture.<br>
            Vous pouvez maintenant tester l'upload de photos.
        </div>

        <a href="live_debug_session.php" class="btn">🎯 Tester l'Upload</a>

    <?php else: ?>
        <div class="box error">
            <span class='error'>❌ <strong>DES PROBLÈMES SUBSISTENT</strong></span><br><br>
            Certains répertoires n'ont pas pu être créés ou ne sont pas accessibles en écriture.<br>
            Vous devez corriger les permissions manuellement.
        </div>

        <h2>📝 Commandes de Réparation Manuelle</h2>

        <div class="box warning">
            <strong>Exécutez ces commandes SSH en tant que root:</strong>
        </div>

        <pre># Créer les répertoires manquants
sudo mkdir -p <?php echo $base_dir; ?>/planning
sudo mkdir -p <?php echo $base_dir; ?>/rapports
sudo mkdir -p <?php echo $base_dir; ?>/regie
sudo mkdir -p <?php echo $base_dir; ?>/sens_pose
sudo mkdir -p <?php echo $base_dir; ?>/temp

# Définir les permissions (775 = rwxrwxr-x)
sudo chmod -R 775 <?php echo $base_dir; ?>

# Définir le propriétaire (remplacez www-data si nécessaire)
sudo chown -R www-data:www-data <?php echo $base_dir; ?>

# Alternative: utiliser l'utilisateur PHP actuel
sudo chown -R <?php echo get_current_user(); ?>:<?php echo get_current_user(); ?> <?php echo $base_dir; ?>

# Vérifier les permissions
ls -lah <?php echo $base_dir; ?>
</pre>

        <a href="fix_directories.php" class="btn">🔄 Recharger</a>
        <a href="diagnostic_upload_permissions.php" class="btn">📊 Diagnostic Complet</a>

    <?php endif; ?>

    <h2>🔗 Actions Disponibles</h2>

    <a href="live_debug_session.php" class="btn">🎯 Monitor Upload</a>
    <a href="diagnostic_upload_permissions.php" class="btn">📊 Diagnostic</a>
    <a href="fix_directories.php" class="btn">🔄 Recharger</a>

</body>
</html>
