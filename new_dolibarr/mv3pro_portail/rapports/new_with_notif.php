<?php
/**
 * Exemple d'intégration des notifications dans la création de rapport
 * À copier dans rapports/new.php après la ligne 74 (après insertion rapport)
 */

// APRÈS L'INSERTION DU RAPPORT (ligne 74)
if ($db->query($sql)) {
    $rapport_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_rapport");

    // ===== NOTIFICATIONS =====
    // Charger le système de notifications
    require_once __DIR__.'/../core/notifications.php';

    // Récupérer les infos du projet pour la notification
    $sql_projet = "SELECT ref FROM ".MAIN_DB_PREFIX."projet WHERE rowid = ".(int)$fk_projet;
    $resql_projet = $db->query($sql_projet);
    $projet_ref = '';
    if ($resql_projet && $db->num_rows($resql_projet) > 0) {
        $obj_projet = $db->fetch_object($resql_projet);
        $projet_ref = $obj_projet->ref;
    }

    // Notifier les managers/chefs de projet
    notifyNewRapport($db, $rapport_id, $fk_user, $projet_ref);
    // ===== FIN NOTIFICATIONS =====

    // Gestion photos (code existant...)
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        // ... reste du code photos
    }

    header("Location: view.php?id=".$rapport_id."&created=1");
    exit;
}
?>
