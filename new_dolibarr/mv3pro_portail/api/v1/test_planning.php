<?php
/**
 * Test direct de l'API planning
 * Accès : /custom/mv3pro_portail/api/v1/test_planning.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Test API Planning</h1>";

echo "<h2>1. Test chargement bootstrap</h2>";
try {
    require_once __DIR__ . '/_bootstrap.php';
    echo "✅ Bootstrap chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur bootstrap: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>2. Test variables globales</h2>";
global $db, $conf, $user;

if (isset($db)) {
    echo "✅ \$db existe<br>";
} else {
    echo "❌ \$db n'existe pas<br>";
}

if (isset($conf)) {
    echo "✅ \$conf existe<br>";
    if (isset($conf->entity)) {
        echo "✅ \$conf->entity = " . $conf->entity . "<br>";
    } else {
        echo "⚠️ \$conf->entity n'existe pas<br>";
    }
} else {
    echo "❌ \$conf n'existe pas<br>";
}

if (isset($user)) {
    echo "✅ \$user existe (ID: " . ($user->id ?? 'N/A') . ")<br>";
} else {
    echo "⚠️ \$user n'existe pas (normal en mode NOLOGIN)<br>";
}

echo "<h2>3. Test authentification</h2>";
try {
    $auth = require_auth(false);
    if ($auth) {
        echo "✅ Auth OK<br>";
        echo "Mode: " . ($auth['mode'] ?? 'N/A') . "<br>";
        echo "User ID: " . ($auth['user_id'] ?? 'null') . "<br>";
        echo "Mobile User ID: " . ($auth['mobile_user_id'] ?? 'N/A') . "<br>";
        echo "Is unlinked: " . ($auth['is_unlinked'] ? 'OUI' : 'NON') . "<br>";
    } else {
        echo "⚠️ Pas d'authentification (normal si pas de token)<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur auth: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Test requête SQL</h2>";
$from = date('Y-m-d');
$to = date('Y-m-d', strtotime('+7 days'));
$user_id = $auth['user_id'] ?? 1;

if (!$user_id) {
    echo "⚠️ Pas de user_id, impossible de tester la requête SQL<br>";
    echo "<p>Pour tester avec un compte lié, connectez-vous d'abord dans la PWA</p>";
} else {
    echo "User ID utilisé pour le test: $user_id<br>";
    echo "Dates: du $from au $to<br><br>";

    $sql = "SELECT DISTINCT a.id, a.label, a.datep, a.datep2
            FROM ".MAIN_DB_PREFIX."actioncomm a
            LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ac ON ac.id = a.fk_action
            LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
            WHERE (a.fk_user_author = ".(int)$user_id."
                   OR a.fk_user_action = ".(int)$user_id."
                   OR a.fk_user_done = ".(int)$user_id."
                   OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$user_id."))
            AND a.entity = ".((isset($conf->entity) && $conf->entity > 0) ? (int)$conf->entity : 1)."
            AND (ac.code IN ('AC_POS', 'AC_plan') OR ac.code IS NULL)
            AND (
                (a.datep2 IS NOT NULL AND DATE(a.datep) <= '".$db->escape($to)."' AND DATE(a.datep2) >= '".$db->escape($from)."')
                OR (a.datep2 IS NULL AND DATE(a.datep) >= '".$db->escape($from)."' AND DATE(a.datep) <= '".$db->escape($to)."')
            )
            ORDER BY a.datep ASC
            LIMIT 10";

    echo "<pre style='background:#f5f5f5; padding:10px; overflow-x:auto;'>";
    echo htmlspecialchars($sql);
    echo "</pre>";

    $resql = $db->query($sql);

    if (!$resql) {
        echo "❌ Erreur SQL: " . $db->lasterror() . "<br>";
    } else {
        $num = $db->num_rows($resql);
        echo "✅ Requête réussie : $num événement(s) trouvé(s)<br>";

        if ($num > 0) {
            echo "<h3>Résultats :</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Label</th><th>Date début</th><th>Date fin</th></tr>";
            while ($obj = $db->fetch_object($resql)) {
                echo "<tr>";
                echo "<td>" . $obj->id . "</td>";
                echo "<td>" . htmlspecialchars($obj->label) . "</td>";
                echo "<td>" . $obj->datep . "</td>";
                echo "<td>" . ($obj->datep2 ?: 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

echo "<hr>";
echo "<p><a href='planning.php?from=$from&to=$to'>Tester l'API planning.php</a></p>";
