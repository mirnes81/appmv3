<?php
require_once '../includes/session.php';
require_once '../../regie/class/regie.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

global $db, $user, $conf;

checkMobileSession();

$id = GETPOST('id', 'int');

$regie = new Regie($db);
$result = $regie->fetch($id);

if ($result <= 0) {
    header('Location: list.php');
    exit;
}

$project = new Project($db);
$project->fetch($regie->fk_project);

$statuses = [
    0 => 'Brouillon',
    1 => 'Validé',
    2 => 'Envoyé',
    3 => 'Signé',
    4 => 'Facturé',
    9 => 'Annulé'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de régie <?php echo $regie->ref; ?></title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        .info-card {
            background: white;
            margin: 15px;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #666;
            font-weight: 600;
        }
        .info-value {
            color: #333;
            text-align: right;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-0 { background: #ffeaa7; color: #fdcb6e; }
        .status-1 { background: #74b9ff; color: #0984e3; }
        .status-2 { background: #a29bfe; color: #6c5ce7; }
        .status-3 { background: #55efc4; color: #00b894; }
        .status-4 { background: #dfe6e9; color: #2d3436; }
        .action-buttons {
            padding: 15px;
            display: flex;
            gap: 10px;
        }
        .btn-action {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        .btn-edit {
            background: #74b9ff;
            color: white;
        }
        .btn-delete {
            background: #ff7675;
            color: white;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="page-header">
            <a href="list.php" class="back-btn">←</a>
            <h1><?php echo $regie->ref; ?></h1>
        </div>

        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Statut</span>
                <span class="info-value">
                    <span class="status-badge status-<?php echo $regie->status; ?>">
                        <?php echo $statuses[$regie->status] ?? 'Inconnu'; ?>
                    </span>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Projet</span>
                <span class="info-value"><?php echo $project->ref.' - '.$project->title; ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Date</span>
                <span class="info-value"><?php echo dol_print_date($db->jdate($regie->date_regie), 'day'); ?></span>
            </div>

            <?php if ($regie->location_text): ?>
            <div class="info-row">
                <span class="info-label">Lieu</span>
                <span class="info-value"><?php echo $regie->location_text; ?></span>
            </div>
            <?php endif; ?>

            <?php if ($regie->type_regie): ?>
            <div class="info-row">
                <span class="info-label">Type</span>
                <span class="info-value"><?php echo $regie->type_regie; ?></span>
            </div>
            <?php endif; ?>

            <?php if ($regie->note_public): ?>
            <div class="info-row">
                <span class="info-label">Observations</span>
                <span class="info-value"><?php echo nl2br($regie->note_public); ?></span>
            </div>
            <?php endif; ?>

            <div class="info-row">
                <span class="info-label">Total HT</span>
                <span class="info-value" style="font-weight: 700; color: #00b894;">
                    <?php echo price($regie->total_ht, 1, '', 1, -1, -1, 'CHF'); ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Total TTC</span>
                <span class="info-value" style="font-weight: 700; font-size: 18px; color: #00b894;">
                    <?php echo price($regie->total_ttc, 1, '', 1, -1, -1, 'CHF'); ?>
                </span>
            </div>
        </div>

        <?php if ($regie->status == 0): ?>
        <div class="action-buttons">
            <a href="edit.php?id=<?php echo $regie->rowid; ?>" class="btn-action btn-edit">
                ✏️ Modifier
            </a>
            <a href="delete.php?id=<?php echo $regie->rowid; ?>" class="btn-action btn-delete"
               onclick="return confirm('Supprimer ce bon de régie ?');">
                🗑️ Supprimer
            </a>
        </div>
        <?php endif; ?>

        <?php include '../includes/bottom_nav.php'; ?>
    </div>
</body>
</html>
