<?php
define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

global $db, $user, $conf;

echo "<h1>Test Configuration Régie</h1>";

echo "<h2>1. Connexion base de données</h2>";
if ($db) {
    echo "✅ Connexion OK<br>";
} else {
    echo "❌ Pas de connexion DB<br>";
}

echo "<h2>2. Tables existantes</h2>";
$tables_to_check = [
    'llx_mv3_regie',
    'llx_mv3_regie_line',
    'llx_mv3_regie_type',
    'llx_mv3_regie_photo'
];

foreach ($tables_to_check as $table) {
    $sql = "SHOW TABLES LIKE '".$table."'";
    $result = $db->query($sql);
    if ($result && $db->num_rows($result) > 0) {
        echo "✅ Table $table existe<br>";

        $sql_count = "SELECT COUNT(*) as nb FROM ".$table;
        $res_count = $db->query($sql_count);
        if ($res_count) {
            $obj = $db->fetch_object($res_count);
            echo "&nbsp;&nbsp;&nbsp;→ $obj->nb enregistrements<br>";
        }
    } else {
        echo "❌ Table $table n'existe PAS<br>";
    }
}

echo "<h2>3. Types de régie</h2>";
$sql = "SELECT * FROM llx_mv3_regie_type WHERE active = 1 ORDER BY position";
$result = $db->query($sql);
if ($result) {
    while ($obj = $db->fetch_object($result)) {
        echo "- $obj->code: $obj->label<br>";
    }
} else {
    echo "❌ Erreur: " . $db->lasterror() . "<br>";
}

echo "<h2>4. Projets actifs</h2>";
$sql = "SELECT rowid, ref, title FROM ".MAIN_DB_PREFIX."projet WHERE entity = ".$conf->entity." AND fk_statut = 1 ORDER BY ref DESC LIMIT 5";
$result = $db->query($sql);
if ($result) {
    $nb = $db->num_rows($result);
    echo "✅ $nb projets actifs trouvés<br>";
    while ($obj = $db->fetch_object($result)) {
        echo "- $obj->ref: $obj->title<br>";
    }
} else {
    echo "❌ Erreur: " . $db->lasterror() . "<br>";
}

echo "<h2>5. Test classe Regie</h2>";
require_once '../../regie/class/regie.class.php';
$regie = new Regie($db);
echo "✅ Classe Regie chargée<br>";

echo "<h2>6. Utilisateur</h2>";
if ($user && $user->id > 0) {
    echo "✅ Utilisateur: $user->login (ID: $user->id)<br>";
} else {
    echo "❌ Pas d'utilisateur connecté<br>";
}
?>
