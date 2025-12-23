<?php
/**
 * Liste des bons de régie - Version mobile
 */

require_once '../includes/session.php';
require_once '../../regie/class/regie.class.php';

global $db, $user, $conf;

checkMobileSession();

$user_role = 'user';
if ($user->admin) {
    $user_role = 'admin';
}

$search_status = GETPOST('search_status', 'int');

$sql = "SELECT r.*, p.ref as project_ref, p.title as project_title";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON r.fk_project = p.rowid";
$sql .= " WHERE r.entity = ".$conf->entity;

if ($user_role != 'admin') {
    $sql .= " AND (r.fk_user_author = ".(int)$user->id." OR r.fk_user_valid = ".(int)$user->id.")";
}

if ($search_status !== '' && $search_status >= 0) {
    $sql .= " AND r.status = ".(int)$search_status;
}

$sql .= " ORDER BY r.date_regie DESC, r.rowid DESC";
$sql .= " LIMIT 50";

$result = $db->query($sql);
$regies = array();

if ($result) {
    while ($obj = $db->fetch_object($result)) {
        $regies[] = $obj;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bons de régie</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        .filter-bar {
            padding: 15px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .filter-bar select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        .regie-card {
            background: white;
            margin: 10px;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .regie-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .regie-ref {
            font-weight: 700;
            font-size: 16px;
            color: #333;
        }
        .regie-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-0 { background: #ffeaa7; color: #fdcb6e; }
        .status-1 { background: #74b9ff; color: #0984e3; }
        .status-2 { background: #a29bfe; color: #6c5ce7; }
        .status-3 { background: #55efc4; color: #00b894; }
        .status-4 { background: #dfe6e9; color: #2d3436; }
        .regie-info {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        .regie-amount {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
            text-align: right;
            font-weight: 700;
            font-size: 18px;
            color: #00b894;
        }
        .fab {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            color: white;
            font-size: 24px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="page-header">
            <h1>📋 Bons de régie</h1>
        </div>

        <div class="filter-bar">
            <select id="filter-status" onchange="filterStatus(this.value)">
                <option value="">Tous les statuts</option>
                <option value="0" <?php echo $search_status === 0 ? 'selected' : ''; ?>>Brouillon</option>
                <option value="1" <?php echo $search_status === 1 ? 'selected' : ''; ?>>Validé</option>
                <option value="2" <?php echo $search_status === 2 ? 'selected' : ''; ?>>Envoyé</option>
                <option value="3" <?php echo $search_status === 3 ? 'selected' : ''; ?>>Signé</option>
                <option value="4" <?php echo $search_status === 4 ? 'selected' : ''; ?>>Facturé</option>
            </select>
        </div>

        <div class="content-area">
            <?php if (count($regies) > 0): ?>
                <?php foreach ($regies as $regie): ?>
                    <a href="view.php?id=<?php echo $regie->rowid; ?>" class="regie-card">
                        <div class="regie-header">
                            <div class="regie-ref"><?php echo $regie->ref; ?></div>
                            <div class="regie-status status-<?php echo $regie->status; ?>">
                                <?php
                                $statuses = ['Brouillon', 'Validé', 'Envoyé', 'Signé', 'Facturé'];
                                echo $statuses[$regie->status] ?? 'Inconnu';
                                ?>
                            </div>
                        </div>
                        <div class="regie-info">
                            <div><strong>Projet:</strong> <?php echo $regie->project_ref; ?></div>
                            <div><strong>Date:</strong> <?php echo dol_print_date($db->jdate($regie->date_regie), 'day'); ?></div>
                            <?php if ($regie->location_text): ?>
                                <div><strong>Lieu:</strong> <?php echo $regie->location_text; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="regie-amount">
                            <?php echo price($regie->total_ttc, 1, '', 1, -1, -1, 'CHF'); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #999;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📋</div>
                    <p>Aucun bon de régie</p>
                </div>
            <?php endif; ?>
        </div>

        <a href="new.php" class="fab">+</a>

        <?php include '../includes/bottom_nav.php'; ?>
    </div>

    <script>
        function filterStatus(status) {
            window.location.href = 'list.php?search_status=' + status;
        }
    </script>
</body>
</html>
