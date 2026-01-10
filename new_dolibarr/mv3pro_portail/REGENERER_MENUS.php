<?php
/**
 * Script pour régénérer les menus du module MV-3 PRO
 * À exécuter UNE SEULE FOIS après modification du module
 */

$res = 0;
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}

if (!$res) {
    die("Erreur: Impossible de charger main.inc.php");
}

// Vérifier les droits admin
if (!$user->admin) {
    accessforbidden("Vous devez être administrateur");
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Régénération Menus MV-3 PRO</title>";
echo "<style>body{font-family:Arial;padding:40px;max-width:800px;margin:0 auto;}";
echo ".success{color:#10b981;font-weight:bold;}.error{color:#ef4444;font-weight:bold;}";
echo ".info{background:#e0f2fe;padding:15px;border-radius:8px;margin:20px 0;}";
echo "pre{background:#f3f4f6;padding:15px;border-radius:4px;overflow:auto;}</style></head><body>";

echo "<h1>🔄 Régénération des menus MV-3 PRO</h1>";

echo "<div class='info'>";
echo "<strong>📋 Ce script va :</strong><br>";
echo "1. Supprimer les anciens menus du module<br>";
echo "2. Régénérer les menus depuis la configuration<br>";
echo "3. Vider le cache<br>";
echo "</div>";

// 1. Supprimer les anciens menus du module
echo "<h2>1️⃣ Suppression des anciens menus</h2>";
$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'mv3pro_portail'";
$resql = $db->query($sql);
if ($resql) {
    $deleted = $db->affected_rows($resql);
    echo "<p class='success'>✅ $deleted ancien(s) menu(s) supprimé(s)</p>";
} else {
    echo "<p class='error'>❌ Erreur: ".$db->lasterror()."</p>";
}

// 2. Réactiver le module pour régénérer les menus
echo "<h2>2️⃣ Régénération des menus</h2>";

require_once DOL_DOCUMENT_ROOT.'/core/modules/modMv3pro_portail.class.php';
$module = new modMv3pro_portail($db);

// Insérer les nouveaux menus
if (!empty($module->menu)) {
    $menus_added = 0;
    foreach ($module->menu as $menu) {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."menu (";
        $sql .= "module, type, mainmenu, leftmenu, fk_menu, ";
        $sql .= "titre, prefix, url, langs, position, ";
        $sql .= "enabled, perms, target, user, entity";
        $sql .= ") VALUES (";
        $sql .= "'mv3pro_portail', ";
        $sql .= "'".$db->escape($menu['type'])."', ";
        $sql .= "'".$db->escape($menu['mainmenu'])."', ";
        $sql .= "'".$db->escape($menu['leftmenu'] ?? '')."', ";
        $sql .= "'".$db->escape($menu['fk_menu'] ?? '')."', ";
        $sql .= "'".$db->escape($menu['titre'])."', ";
        $sql .= "'".$db->escape($menu['prefix'] ?? '')."', ";
        $sql .= "'".$db->escape($menu['url'])."', ";
        $sql .= "'".$db->escape($menu['langs'] ?? '')."', ";
        $sql .= intval($menu['position']).", ";
        $sql .= "'".$db->escape($menu['enabled'] ?? '1')."', ";
        $sql .= "'".$db->escape($menu['perms'] ?? '1')."', ";
        $sql .= "'".$db->escape($menu['target'] ?? '')."', ";
        $sql .= intval($menu['user'] ?? 2).", ";
        $sql .= intval($conf->entity);
        $sql .= ")";

        $resql = $db->query($sql);
        if ($resql) {
            $menus_added++;
            echo "<p class='success'>✅ Menu ajouté: ".$menu['titre']." (".$menu['type'].")</p>";
        } else {
            echo "<p class='error'>❌ Erreur menu ".$menu['titre'].": ".$db->lasterror()."</p>";
        }
    }
    echo "<p class='success'><strong>✅ $menus_added menu(s) créé(s)</strong></p>";
} else {
    echo "<p class='error'>❌ Aucun menu trouvé dans la configuration du module</p>";
}

// 3. Vider le cache
echo "<h2>3️⃣ Vidage du cache</h2>";
if (function_exists('clearstatcache')) {
    clearstatcache();
    echo "<p class='success'>✅ Cache PHP vidé</p>";
}

// Supprimer fichiers cache menu
$cache_dir = DOL_DATA_ROOT.'/admin/temp';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir.'/menu_*.cache');
    $deleted_files = 0;
    foreach ($files as $file) {
        if (unlink($file)) {
            $deleted_files++;
        }
    }
    echo "<p class='success'>✅ $deleted_files fichier(s) cache menu supprimé(s)</p>";
}

// 4. Vérification finale
echo "<h2>4️⃣ Vérification</h2>";
$sql = "SELECT rowid, type, titre, mainmenu, leftmenu, url FROM ".MAIN_DB_PREFIX."menu ";
$sql .= "WHERE module = 'mv3pro_portail' ORDER BY type, position";
$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);
    echo "<p class='success'><strong>✅ $num menu(s) dans la base de données</strong></p>";
    echo "<pre>";
    while ($obj = $db->fetch_object($resql)) {
        echo sprintf("%-6s | %-15s | %-12s | %-15s | %s\n",
            $obj->type,
            $obj->titre,
            $obj->mainmenu,
            $obj->leftmenu ?: '-',
            $obj->url
        );
    }
    echo "</pre>";
}

// Instructions finales
echo "<div class='info' style='background:#d1fae5;'>";
echo "<h3>✅ TERMINÉ !</h3>";
echo "<p><strong>Prochaines étapes :</strong></p>";
echo "<ol>";
echo "<li><strong>Rafraîchir votre navigateur</strong> (Ctrl + F5 ou Cmd + Shift + R)</li>";
echo "<li><strong>Cliquer sur \"MV-3 PRO\"</strong> dans le menu du haut</li>";
echo "<li>Vous devriez voir le menu de gauche avec :<ul>";
echo "<li>📊 Dashboard</li>";
echo "<li>📅 Planning</li>";
echo "<li>📄 Rapports</li>";
echo "</ul></li>";
echo "</ol>";
echo "<p><strong>⚠️ Si le menu n'apparaît toujours pas :</strong></p>";
echo "<ol>";
echo "<li>Aller dans <strong>Configuration → Modules/Applications</strong></li>";
echo "<li>Chercher <strong>MV-3 PRO Portail</strong></li>";
echo "<li>Le <strong>désactiver</strong> puis le <strong>réactiver</strong></li>";
echo "<li>Revenir sur cette page et réexécuter ce script</li>";
echo "</ol>";
echo "</div>";

echo "<p style='text-align:center;margin-top:40px;'>";
echo "<a href='/custom/mv3pro_portail/dashboard/index.php' style='display:inline-block;padding:15px 30px;background:#0891b2;color:white;text-decoration:none;border-radius:8px;font-weight:bold;'>";
echo "➡️ Aller au Dashboard";
echo "</a>";
echo "</p>";

echo "</body></html>";

$db->close();
