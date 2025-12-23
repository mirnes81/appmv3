<?php
/**
 * Voir matériel - Mobile
 */



$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";

if (!isset($_SESSION["dol_login"]) || empty($user->id)) {
    header("Location: ../index.php");
    exit;
}

$id = GETPOST('id', 'int');
$qrcode = GETPOST('qrcode', 'alpha');
$mode = GETPOST('mode', 'alpha');

if ($qrcode) {
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_materiel WHERE qrcode = '".$db->escape($qrcode)."' LIMIT 1";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        $id = $obj->rowid;
    }
}

$sql = "SELECT m.*, m.nom as label,
               (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."mv3_materiel_historique WHERE fk_materiel = m.rowid) as nb_usage,
               u.firstname as user_firstname,
               u.lastname as user_lastname,
               p.ref as projet_ref,
               p.title as projet_title
        FROM ".MAIN_DB_PREFIX."mv3_materiel m
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = m.fk_user_assigne
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = m.fk_projet_assigne
        WHERE m.rowid = ".(int)$id;

$resql = $db->query($sql);
$materiel = $db->fetch_object($resql);

if (!$materiel) {
    header('Location: list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title><?php echo dol_escape_htmltag($materiel->ref); ?> - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
</head>
<body>
    <div class="app-header">
        <div style="display:flex;align-items:center;gap:12px">
            <a href="list.php" style="color:white;font-size:24px;text-decoration:none">←</a>
            <div>
                <div class="app-header-title">🛠️ <?php echo dol_escape_htmltag($materiel->ref); ?></div>
                <div class="app-header-subtitle">Détails du matériel</div>
            </div>
        </div>
    </div>

    <div class="app-container">
        <!-- Affectation actuelle si en service -->
        <?php if ($materiel->fk_user_assigne || $materiel->fk_projet_assigne): ?>
        <div class="card" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border-left:4px solid #f59e0b">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                <div style="font-size:32px">👤</div>
                <div style="flex:1">
                    <div style="font-size:12px;color:#92400e;font-weight:600;margin-bottom:2px">ACTUELLEMENT AVEC</div>
                    <div style="font-size:18px;font-weight:700;color:#78350f">
                        <?php
                        if ($materiel->user_firstname || $materiel->user_lastname) {
                            echo dol_escape_htmltag($materiel->user_firstname.' '.$materiel->user_lastname);
                        } else {
                            echo "Non affecté";
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php if ($materiel->projet_title): ?>
            <div style="display:flex;align-items:center;gap:8px;padding:8px;background:rgba(255,255,255,0.5);border-radius:8px">
                <span style="font-size:20px">🏗️</span>
                <div style="flex:1">
                    <div style="font-size:11px;color:#92400e;font-weight:600">CHANTIER</div>
                    <div style="font-size:14px;font-weight:600;color:#78350f">
                        <?php echo dol_escape_htmltag($materiel->projet_ref ? $materiel->projet_ref.' - ' : '').$materiel->projet_title; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-title">ℹ️ Informations</div>
                <span class="card-badge badge-<?php
                    if ($materiel->statut == 'disponible') echo 'success';
                    elseif ($materiel->statut == 'en_service') echo 'warning';
                    elseif ($materiel->statut == 'maintenance') echo 'danger';
                    else echo 'secondary';
                ?>">
                    <?php
                    if ($materiel->statut == 'disponible') echo '🟢 Disponible';
                    elseif ($materiel->statut == 'en_service') echo '🟡 En service';
                    elseif ($materiel->statut == 'maintenance') echo '🔴 Maintenance';
                    else echo '⚫ Hors service';
                    ?>
                </span>
            </div>

            <div style="display:grid;gap:12px;margin-top:16px">
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">RÉFÉRENCE</div>
                    <div style="font-size:16px;font-weight:700"><?php echo dol_escape_htmltag($materiel->ref); ?></div>
                </div>

                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">DÉSIGNATION</div>
                    <div style="font-size:16px;font-weight:700"><?php echo dol_escape_htmltag($materiel->label); ?></div>
                </div>

                <?php if ($materiel->description): ?>
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">DESCRIPTION</div>
                    <div style="font-size:14px;line-height:1.6"><?php echo nl2br(dol_escape_htmltag($materiel->description)); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($materiel->qrcode): ?>
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">QR CODE</div>
                    <div style="font-size:16px;font-family:monospace"><?php echo dol_escape_htmltag($materiel->qrcode); ?></div>
                </div>
                <?php endif; ?>

                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">UTILISATIONS</div>
                    <div style="font-size:20px;font-weight:700;color:var(--primary)"><?php echo $materiel->nb_usage; ?> fois</div>
                </div>
            </div>
        </div>

        <!-- Actions disponibles -->
        <div class="card">
            <div class="card-title">⚡ Actions rapides</div>

            <?php
            $msg = GETPOST('msg', 'alpha');
            if ($msg == 'pris'): ?>
                <div style="background:#d1fae5;padding:12px;border-radius:8px;margin-bottom:16px;border-left:4px solid #10b981">
                    <div style="font-size:14px;color:#065f46;font-weight:600">✅ Matériel pris avec succès !</div>
                </div>
            <?php elseif ($msg == 'transfere'): ?>
                <div style="background:#dbeafe;padding:12px;border-radius:8px;margin-bottom:16px;border-left:4px solid #3b82f6">
                    <div style="font-size:14px;color:#1e40af;font-weight:600">✅ Matériel transféré avec succès !</div>
                </div>
            <?php endif; ?>

            <div style="display:grid;gap:12px">
                <?php if (!$materiel->fk_user_assigne || $materiel->statut == 'disponible'): ?>
                    <!-- Matériel disponible : on peut le prendre -->
                    <a href="action.php?action=prendre&id=<?php echo $id; ?>" class="btn" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px">
                        <span style="font-size:20px">📦</span>
                        <span>Prendre ce matériel</span>
                    </a>

                <?php elseif ($materiel->fk_user_assigne == $user_id): ?>
                    <!-- J'ai ce matériel : je peux le rendre ou le transférer -->
                    <a href="action.php?action=rendre&id=<?php echo $id; ?>" class="btn" style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);color:white;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px">
                        <span style="font-size:20px">🏠</span>
                        <span>Rendre au dépôt</span>
                    </a>

                    <a href="action.php?action=transferer&id=<?php echo $id; ?>" class="btn" style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);color:white;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px">
                        <span style="font-size:20px">🔄</span>
                        <span>Transférer à un collègue</span>
                    </a>

                <?php else: ?>
                    <!-- Un autre ouvrier a ce matériel -->
                    <?php if ($mode === 'reprendre'): ?>
                        <!-- Mode reprise : bouton pour reprendre directement -->
                        <a href="action.php?action=reprendre&id=<?php echo $id; ?>&from_user=<?php echo $materiel->fk_user_assigne; ?>" class="btn" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px">
                            <span style="font-size:20px">🔄</span>
                            <span>Reprendre de <?php echo dol_escape_htmltag($materiel->user_firstname); ?></span>
                        </a>
                        <div style="background:#dbeafe;padding:12px;border-radius:8px;text-align:center;margin-top:12px">
                            <div style="font-size:12px;color:#1e40af">
                                💡 Ce matériel sera transféré à votre nom
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="background:#fef3c7;padding:16px;border-radius:12px;text-align:center;border-left:4px solid #f59e0b">
                            <div style="font-size:14px;color:#92400e;font-weight:600;margin-bottom:4px">
                                Ce matériel est actuellement utilisé
                            </div>
                            <div style="font-size:13px;color:#78350f">
                                Contactez <?php echo dol_escape_htmltag($materiel->user_firstname.' '.$materiel->user_lastname); ?> si vous en avez besoin
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title">📊 Historique d'utilisation</div>

            <?php
            $sql_usage = "SELECT u.date_debut, u.date_fin, r.ref, s.nom
                         FROM ".MAIN_DB_PREFIX."mv3_materiel_usage u
                         LEFT JOIN ".MAIN_DB_PREFIX."mv3_rapport r ON r.rowid = u.fk_rapport
                         LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = r.fk_soc
                         WHERE u.fk_materiel = ".(int)$id."
                         ORDER BY u.date_debut DESC
                         LIMIT 10";
            $resql_usage = $db->query($sql_usage);

            if ($resql_usage && $db->num_rows($resql_usage) > 0) {
                while ($usage = $db->fetch_object($resql_usage)) {
                    echo '<div class="list-item" style="margin-top:8px">';
                    echo '<div class="list-item-icon">📅</div>';
                    echo '<div class="list-item-content">';
                    echo '<div class="list-item-title">'.dol_escape_htmltag($usage->nom ?: $usage->ref).'</div>';
                    echo '<div class="list-item-subtitle">';
                    echo dol_print_date($db->jdate($usage->date_debut), 'day');
                    if ($usage->date_fin) echo ' → '.dol_print_date($db->jdate($usage->date_fin), 'day');
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="empty-state">';
                echo '<div class="empty-state-icon">📭</div>';
                echo '<div class="empty-state-text">Aucune utilisation</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="../js/app.js"></script>
</body>
</html>
<?php $db->close(); ?>
