<?php
/**
 * Créer des notifications de test
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

$user_id = $user->id;

// Créer 5 notifications de test
$notifications = [
    [
        'type' => 'affectation',
        'titre' => 'Nouvelle affectation',
        'message' => 'Vous avez été affecté au chantier "Pose carrelage Mr. Dupont" demain à 9h00'
    ],
    [
        'type' => 'materiel',
        'titre' => 'Matériel à rendre',
        'message' => 'Le matériel "Carrelette électrique Pro" doit être rendu avant ce soir'
    ],
    [
        'type' => 'urgent',
        'titre' => 'Intervention urgente',
        'message' => 'SAV urgent à prévoir sur le chantier Marseille 8ème - Client mécontent'
    ],
    [
        'type' => 'info',
        'titre' => 'Nouveau document disponible',
        'message' => 'La fiche sens de pose "SP-2024-003" a été signée par le client'
    ],
    [
        'type' => 'rapport',
        'titre' => 'Rapport validé',
        'message' => 'Votre rapport de chantier R-2024-156 a été validé par le responsable'
    ]
];

$created = 0;
foreach ($notifications as $notif) {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_notifications
            (fk_user, type, titre, message, statut, date_creation, entity)
            VALUES (
                ".(int)$user_id.",
                '".$db->escape($notif['type'])."',
                '".$db->escape($notif['titre'])."',
                '".$db->escape($notif['message'])."',
                'non_lu',
                NOW(),
                ".(int)$conf->entity."
            )";

    if ($db->query($sql)) {
        $created++;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notifications</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
</head>
<body>
    <div class="app-header">
        <div>
            <div class="app-header-title">🔔 Notifications de Test</div>
        </div>
        <a href="../dashboard.php" style="color:white;font-size:20px;text-decoration:none">🏠</a>
    </div>

    <div class="app-container">
        <?php if ($created > 0): ?>
        <div class="card" style="background: #d1fae5; border-left: 4px solid #10b981;">
            <div style="font-weight: 700; color: #065f46; margin-bottom: 8px;">
                ✅ Succès !
            </div>
            <div style="color: #047857;">
                <?php echo $created; ?> notification<?php echo $created > 1 ? 's' : ''; ?> créée<?php echo $created > 1 ? 's' : ''; ?> pour l'utilisateur <strong><?php echo $user->login; ?></strong>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2 style="font-size: 16px; margin-bottom: 12px;">📋 Notifications créées :</h2>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($notifications as $notif): ?>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--border);">
                    <div style="font-weight: 700;"><?php echo $notif['titre']; ?></div>
                    <div style="font-size: 12px; color: var(--text-light);"><?php echo $notif['message']; ?></div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card">
            <h2 style="font-size: 16px; margin-bottom: 12px;">🔗 Actions :</h2>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="../dashboard.php" class="btn btn-primary">
                    🏠 Retour Dashboard
                </a>
                <a href="index.php" class="btn btn-secondary">
                    🔔 Voir toutes les notifications
                </a>
                <a href="create_test.php" class="btn btn-secondary">
                    🔄 Créer 5 nouvelles notifications
                </a>
            </div>
        </div>

        <?php
        // Afficher les dernières notifications de l'utilisateur
        $sql_recent = "SELECT n.rowid, n.titre, n.type, n.statut, n.date_creation
                       FROM ".MAIN_DB_PREFIX."mv3_notifications n
                       WHERE n.fk_user = ".(int)$user_id."
                       ORDER BY n.date_creation DESC
                       LIMIT 10";
        $resql = $db->query($sql_recent);

        if ($resql && $db->num_rows($resql) > 0):
        ?>
        <div class="card">
            <h2 style="font-size: 16px; margin-bottom: 12px;">📊 Vos 10 dernières notifications :</h2>
            <?php while ($n = $db->fetch_object($resql)):
                $badge_class = 'warning';
                $badge_text = 'Non lu';
                if ($n->statut == 'lu') {
                    $badge_class = 'info';
                    $badge_text = 'Lu';
                } elseif ($n->statut == 'traite') {
                    $badge_class = 'success';
                    $badge_text = 'Traité';
                }
            ?>
            <div style="padding: 8px 0; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; font-size: 13px;"><?php echo dol_escape_htmltag($n->titre); ?></div>
                    <div style="font-size: 11px; color: var(--text-light);">
                        <?php echo dol_print_date($db->jdate($n->date_creation), 'dayhour'); ?>
                    </div>
                </div>
                <span class="card-badge badge-<?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>
</body>
</html>
