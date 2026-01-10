<?php
/**
 * Diagnostic des permissions d'upload
 * Vérifie tous les répertoires et permissions nécessaires
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
$isAuth = ($user && $user->id > 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Permissions Upload</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #00ff00;
            margin-bottom: 20px;
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
        }
        h2 {
            color: #ffff00;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .status.ok {
            background: #0f5132;
            border-left: 5px solid #00ff00;
        }
        .status.error {
            background: #58151c;
            border-left: 5px solid #ff0000;
            color: #ff0000;
        }
        .status.warning {
            background: #664d03;
            border-left: 5px solid #ffff00;
            color: #ffff00;
        }
        .info {
            background: #0c4a6e;
            padding: 15px;
            margin: 10px 0;
            border-left: 5px solid #0ea5e9;
            border-radius: 5px;
        }
        .code {
            background: #2d2d2d;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            overflow-x: auto;
            border-left: 3px solid #666;
        }
        .value {
            color: #00ffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #444;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #2d2d2d;
            color: #ffff00;
        }
        td {
            background: #1a1a1a;
        }
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
        .btn:hover {
            background: #00cc00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnostic Permissions Upload - MV3 PRO</h1>

        <?php if (!$isAuth): ?>
            <div class="status error">
                ❌ Vous devez être connecté à Dolibarr pour utiliser ce diagnostic
            </div>
            <a href="/index.php" class="btn">Se connecter</a>
        <?php else: ?>

        <div class="info">
            ✅ Connecté en tant que: <strong><?php echo htmlspecialchars($user->firstname . ' ' . $user->login); ?></strong>
        </div>

        <h2>📁 Configuration des Répertoires</h2>

        <?php
        // Vérifier la configuration du module
        $checks = [];

        // 1. Vérifier DOL_DATA_ROOT
        $checks[] = [
            'name' => 'DOL_DATA_ROOT',
            'value' => DOL_DATA_ROOT,
            'exists' => defined('DOL_DATA_ROOT'),
            'is_dir' => is_dir(DOL_DATA_ROOT),
            'writable' => is_writable(DOL_DATA_ROOT),
            'perms' => fileperms(DOL_DATA_ROOT)
        ];

        // 2. Vérifier documents
        $docs_dir = DOL_DATA_ROOT . '/documents';
        $checks[] = [
            'name' => 'documents/',
            'value' => $docs_dir,
            'exists' => true,
            'is_dir' => is_dir($docs_dir),
            'writable' => is_writable($docs_dir),
            'perms' => is_dir($docs_dir) ? fileperms($docs_dir) : null
        ];

        // 3. Vérifier mv3pro_portail
        $mv3_dir = DOL_DATA_ROOT . '/documents/mv3pro_portail';
        $checks[] = [
            'name' => 'mv3pro_portail/',
            'value' => $mv3_dir,
            'exists' => true,
            'is_dir' => is_dir($mv3_dir),
            'writable' => is_writable($mv3_dir),
            'perms' => is_dir($mv3_dir) ? fileperms($mv3_dir) : null
        ];

        // 4. Vérifier planning
        $planning_dir = DOL_DATA_ROOT . '/documents/mv3pro_portail/planning';
        $checks[] = [
            'name' => 'planning/',
            'value' => $planning_dir,
            'exists' => true,
            'is_dir' => is_dir($planning_dir),
            'writable' => is_writable($planning_dir),
            'perms' => is_dir($planning_dir) ? fileperms($planning_dir) : null
        ];

        // 5. Vérifier conf
        $has_conf = isset($conf->mv3pro_portail);
        $has_dir_output = isset($conf->mv3pro_portail->dir_output);
        $dir_output = $has_dir_output ? $conf->mv3pro_portail->dir_output : 'NON DÉFINI';

        $checks[] = [
            'name' => '$conf->mv3pro_portail->dir_output',
            'value' => $dir_output,
            'exists' => $has_conf,
            'is_dir' => $has_dir_output && is_dir($dir_output),
            'writable' => $has_dir_output && is_writable($dir_output),
            'perms' => ($has_dir_output && is_dir($dir_output)) ? fileperms($dir_output) : null
        ];
        ?>

        <table>
            <thead>
                <tr>
                    <th>Répertoire</th>
                    <th>Chemin</th>
                    <th>Existe?</th>
                    <th>Écriture?</th>
                    <th>Permissions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): ?>
                <tr>
                    <td><?php echo htmlspecialchars($check['name']); ?></td>
                    <td><span class="value"><?php echo htmlspecialchars($check['value']); ?></span></td>
                    <td><?php echo $check['is_dir'] ? '✅ OUI' : '❌ NON'; ?></td>
                    <td><?php echo $check['writable'] ? '✅ OUI' : '❌ NON'; ?></td>
                    <td><?php echo $check['perms'] ? substr(sprintf('%o', $check['perms']), -4) : 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>🛠️ Informations Système</h2>

        <div class="code">
            <strong>Utilisateur PHP:</strong> <span class="value"><?php echo htmlspecialchars(get_current_user()); ?></span><br>
            <strong>UID PHP:</strong> <span class="value"><?php echo posix_getuid(); ?></span><br>
            <strong>GID PHP:</strong> <span class="value"><?php echo posix_getgid(); ?></span><br>
            <strong>Safe Mode:</strong> <span class="value"><?php echo ini_get('safe_mode') ? 'ON' : 'OFF'; ?></span><br>
            <strong>Open Basedir:</strong> <span class="value"><?php echo ini_get('open_basedir') ?: 'NON DÉFINI'; ?></span><br>
        </div>

        <h2>🧪 Test de Création de Répertoire</h2>

        <?php
        $test_event_id = 74049;
        $test_dir = DOL_DATA_ROOT . '/documents/mv3pro_portail/planning/' . $test_event_id;

        echo "<div class='code'>";
        echo "Test avec Event ID: <span class='value'>$test_event_id</span><br>";
        echo "Répertoire cible: <span class='value'>$test_dir</span><br><br>";

        // Test 1: Vérifier si existe
        if (is_dir($test_dir)) {
            echo "✅ Le répertoire existe déjà<br>";
            echo "Permissions: <span class='value'>" . substr(sprintf('%o', fileperms($test_dir)), -4) . "</span><br>";
            echo "Écriture: " . (is_writable($test_dir) ? "✅ OUI" : "❌ NON") . "<br>";
        } else {
            echo "ℹ️ Le répertoire n'existe pas encore<br>";

            // Test 2: Essayer de créer
            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

            echo "<br>Tentative de création avec dol_mkdir()...<br>";
            $result = dol_mkdir($test_dir);

            if ($result >= 0) {
                echo "✅ <strong>SUCCÈS!</strong> Le répertoire a été créé<br>";
                echo "Permissions: <span class='value'>" . substr(sprintf('%o', fileperms($test_dir)), -4) . "</span><br>";
                echo "Écriture: " . (is_writable($test_dir) ? "✅ OUI" : "❌ NON") . "<br>";
            } else {
                echo "❌ <strong>ÉCHEC!</strong> Impossible de créer le répertoire<br>";
                echo "Code d'erreur: <span class='value'>$result</span><br>";
            }
        }
        echo "</div>";
        ?>

        <h2>📋 Commandes de Réparation</h2>

        <?php
        $all_ok = true;
        foreach ($checks as $check) {
            if (!$check['is_dir'] || !$check['writable']) {
                $all_ok = false;
                break;
            }
        }

        if (!$all_ok):
        ?>
            <div class="status error">
                ❌ Des problèmes de permissions ont été détectés
            </div>

            <div class="info">
                <strong>📝 Exécutez ces commandes SSH en tant que root:</strong>
            </div>

            <div class="code">
# Créer les répertoires manquants<br>
sudo mkdir -p <?php echo DOL_DATA_ROOT; ?>/documents/mv3pro_portail/planning<br>
<br>
# Définir les permissions correctes (775 = rwxrwxr-x)<br>
sudo chmod 775 <?php echo DOL_DATA_ROOT; ?>/documents/mv3pro_portail<br>
sudo chmod 775 <?php echo DOL_DATA_ROOT; ?>/documents/mv3pro_portail/planning<br>
<br>
# Définir le propriétaire (www-data est l'utilisateur Apache/Nginx)<br>
sudo chown -R www-data:www-data <?php echo DOL_DATA_ROOT; ?>/documents/mv3pro_portail<br>
<br>
# Vérifier les permissions<br>
ls -lah <?php echo DOL_DATA_ROOT; ?>/documents/mv3pro_portail/<br>
            </div>

            <div class="info">
                <strong>⚠️ Alternative si www-data ne fonctionne pas:</strong>
            </div>

            <div class="code">
# Essayez avec l'utilisateur PHP actuel<br>
sudo chown -R <?php echo htmlspecialchars(get_current_user()); ?>:<?php echo htmlspecialchars(get_current_user()); ?> <?php echo DOL_DATA_ROOT; ?>/documents/mv3pro_portail<br>
            </div>

        <?php else: ?>
            <div class="status ok">
                ✅ Toutes les permissions sont correctes!
            </div>

            <div class="info">
                Le système est prêt pour l'upload. Si l'erreur persiste, vérifiez:
                <ul>
                    <li>Les logs du serveur web</li>
                    <li>Les restrictions SELinux (si activé)</li>
                    <li>Les quotas de disque</li>
                </ul>
            </div>
        <?php endif; ?>

        <h2>🔗 Actions</h2>

        <a href="live_debug_session.php" class="btn">🔙 Retour au Monitor</a>
        <a href="diagnostic_upload_permissions.php" class="btn">🔄 Recharger le Diagnostic</a>

        <h2>📊 Détails Techniques Complets</h2>

        <div class="code">
            <?php
            echo "<strong>Configuration Dolibarr:</strong><br>";
            echo "DOL_DOCUMENT_ROOT: <span class='value'>" . DOL_DOCUMENT_ROOT . "</span><br>";
            echo "DOL_DATA_ROOT: <span class='value'>" . DOL_DATA_ROOT . "</span><br>";
            echo "DOL_MAIN_DATA_ROOT: <span class='value'>" . (defined('DOL_MAIN_DATA_ROOT') ? DOL_MAIN_DATA_ROOT : 'NON DÉFINI') . "</span><br>";
            echo "<br>";

            echo "<strong>Configuration Module:</strong><br>";
            echo "Module installé: " . (isset($conf->mv3pro_portail) ? "✅ OUI" : "❌ NON") . "<br>";
            if (isset($conf->mv3pro_portail)) {
                echo "dir_output défini: " . (isset($conf->mv3pro_portail->dir_output) ? "✅ OUI" : "❌ NON") . "<br>";
                if (isset($conf->mv3pro_portail->dir_output)) {
                    echo "dir_output valeur: <span class='value'>" . $conf->mv3pro_portail->dir_output . "</span><br>";
                }
            }
            echo "<br>";

            echo "<strong>Espace Disque:</strong><br>";
            $free = disk_free_space(DOL_DATA_ROOT);
            $total = disk_total_space(DOL_DATA_ROOT);
            echo "Espace libre: <span class='value'>" . round($free / 1024 / 1024 / 1024, 2) . " GB</span><br>";
            echo "Espace total: <span class='value'>" . round($total / 1024 / 1024 / 1024, 2) . " GB</span><br>";
            ?>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>
