<?php
/**
 * Actions matériel - Prendre, Rendre, Transférer
 */

// Désactiver CSRF uniquement pour GET (affichage formulaire)
// Les POST gardent la vérification via token
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    define('NOCSRFCHECK', 1);
}

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$target_user = GETPOST('target_user', 'int');
$from_user = GETPOST('from_user', 'int');

$user_id = $user->id;

if (!$id) {
    header('Location: list.php');
    exit;
}

$sql = "SELECT m.*,
               u.firstname as user_firstname, u.lastname as user_lastname,
               p.ref as projet_ref, p.title as projet_title
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

if ($action == 'prendre' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $db->begin();

    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_materiel
            SET fk_user_assigne = ".(int)$user_id.",
                statut = 'en_service',
                date_modification = NOW()
            WHERE rowid = ".(int)$id;

    if ($db->query($sql)) {
        $sql_hist = "INSERT INTO ".MAIN_DB_PREFIX."mv3_materiel_historique
                    (entity, fk_materiel, type_evenement, nouveau_statut, fk_user_nouveau, fk_user_action, commentaire, date_evenement)
                    VALUES (".$conf->entity.", ".(int)$id.", 'affectation_user', 'en_service', ".(int)$user_id.", ".(int)$user_id.", 'Pris du dépôt', NOW())";
        $db->query($sql_hist);

        $db->commit();
        header('Location: view.php?id='.$id.'&msg=pris');
    } else {
        $db->rollback();
        header('Location: view.php?id='.$id.'&error=1');
    }
    exit;
}

if ($action == 'rendre_photo') {
    $upload_dir = $conf->mv3pro_portail->dir_output.'/materiel_depot';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $filename = 'retour_'.date('YmdHis').'_'.$id.'_'.uniqid().'.jpg';
        $filepath = $upload_dir.'/'.$filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
            $db->begin();

            $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_materiel
                    SET fk_user_assigne = NULL,
                        statut = 'disponible',
                        date_modification = NOW()
                    WHERE rowid = ".(int)$id;

            if ($db->query($sql)) {
                $commentaire = "Rendu au dépôt - Photo: ".$filename;
                if (GETPOST('commentaire', 'restricthtml')) {
                    $commentaire .= " - ".GETPOST('commentaire', 'restricthtml');
                }

                $sql_hist = "INSERT INTO ".MAIN_DB_PREFIX."mv3_materiel_historique
                            (entity, fk_materiel, type_evenement, ancien_statut, nouveau_statut, fk_user_ancien, fk_user_action, commentaire, date_evenement)
                            VALUES (".$conf->entity.", ".(int)$id.", 'liberation', 'en_service', 'disponible', ".(int)$user_id.", ".(int)$user_id.", '".$db->escape($commentaire)."', NOW())";
                $db->query($sql_hist);

                $db->commit();
                header('Location: list.php?msg=rendu');
            } else {
                $db->rollback();
                header('Location: view.php?id='.$id.'&error=1');
            }
        }
    }
    exit;
}

if ($action == 'transferer' && $_SERVER['REQUEST_METHOD'] == 'POST' && $target_user) {
    $db->begin();

    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_materiel
            SET fk_user_assigne = ".(int)$target_user.",
                statut = 'en_service',
                date_modification = NOW()
            WHERE rowid = ".(int)$id;

    if ($db->query($sql)) {
        $sql_user = "SELECT firstname, lastname FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".(int)$target_user;
        $res_user = $db->query($sql_user);
        $target_user_obj = $db->fetch_object($res_user);
        $target_name = $target_user_obj->firstname.' '.$target_user_obj->lastname;

        $sql_hist = "INSERT INTO ".MAIN_DB_PREFIX."mv3_materiel_historique
                    (entity, fk_materiel, type_evenement, fk_user_ancien, fk_user_nouveau, fk_user_action, commentaire, date_evenement)
                    VALUES (".$conf->entity.", ".(int)$id.", 'affectation_user', ".(int)$user_id.", ".(int)$target_user.", ".(int)$user_id.", 'Transféré à ".$db->escape($target_name)."', NOW())";
        $db->query($sql_hist);

        $db->commit();
        header('Location: view.php?id='.$id.'&msg=transfere');
    } else {
        $db->rollback();
        header('Location: view.php?id='.$id.'&error=1');
    }
    exit;
}

if ($action == 'reprendre_confirm' && $_SERVER['REQUEST_METHOD'] == 'POST' && $from_user) {
    $commentaire = GETPOST('commentaire', 'alpha');
    $db->begin();

    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_materiel
            SET fk_user_assigne = ".(int)$user_id.",
                statut = 'en_service',
                date_modification = NOW()
            WHERE rowid = ".(int)$id;

    if ($db->query($sql)) {
        $sql_from = "SELECT firstname, lastname FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".(int)$from_user;
        $res_from = $db->query($sql_from);
        $from_user_obj = $db->fetch_object($res_from);
        $from_name = $from_user_obj->firstname.' '.$from_user_obj->lastname;

        $hist_comment = 'Repris de '.$from_name;
        if ($commentaire) {
            $hist_comment .= ' - '.$commentaire;
        }

        $sql_hist = "INSERT INTO ".MAIN_DB_PREFIX."mv3_materiel_historique
                    (entity, fk_materiel, type_evenement, fk_user_ancien, fk_user_nouveau, fk_user_action, commentaire, date_evenement)
                    VALUES (".$conf->entity.", ".(int)$id.", 'reprise_collegue', ".(int)$from_user.", ".(int)$user_id.", ".(int)$user_id.", '".$db->escape($hist_comment)."', NOW())";
        $db->query($sql_hist);

        $db->commit();
        header('Location: view.php?id='.$id.'&msg=pris');
    } else {
        $db->rollback();
        header('Location: view.php?id='.$id.'&error=1');
    }
    exit;
}

$sql_users = "SELECT rowid, firstname, lastname
              FROM ".MAIN_DB_PREFIX."user
              WHERE entity IN (0,".$conf->entity.")
              AND statut = 1
              AND rowid != ".(int)$user_id."
              ORDER BY lastname, firstname";
$resql_users = $db->query($sql_users);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Action - <?php echo dol_escape_htmltag($materiel->ref); ?></title>
    <link rel="stylesheet" href="../css/mobile_app.css">
</head>
<body>
    <div class="app-header">
        <div style="display:flex;align-items:center;gap:12px">
            <a href="view.php?id=<?php echo $id; ?>" style="color:white;font-size:24px;text-decoration:none">←</a>
            <div>
                <div class="app-header-title"><?php echo dol_escape_htmltag($materiel->ref); ?></div>
                <div class="app-header-subtitle">Action sur le matériel</div>
            </div>
        </div>
    </div>

    <div class="app-container">
        <?php if ($action == 'prendre'): ?>

        <div class="card" style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%);border-left:4px solid #3b82f6">
            <div style="text-align:center;padding:20px">
                <div style="font-size:64px;margin-bottom:16px">📦</div>
                <div style="font-size:20px;font-weight:700;color:#1e40af;margin-bottom:8px">Prendre ce matériel ?</div>
                <div style="font-size:14px;color:#1e3a8a;margin-bottom:4px">
                    <?php echo dol_escape_htmltag($materiel->ref); ?> - <?php echo dol_escape_htmltag($materiel->nom); ?>
                </div>
            </div>
        </div>

        <form method="POST" action="?action=prendre&id=<?php echo $id; ?>" accept-charset="UTF-8">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <button type="submit" class="btn" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;width:100%">
                ✅ Confirmer - Je prends ce matériel
            </button>
        </form>

        <a href="view.php?id=<?php echo $id; ?>" class="btn" style="background:var(--card);color:var(--text);border:1px solid var(--border);text-align:center;display:block">
            ❌ Annuler
        </a>

        <?php elseif ($action == 'rendre'): ?>

        <div class="card" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border-left:4px solid #f59e0b">
            <div style="text-align:center;padding:20px">
                <div style="font-size:64px;margin-bottom:16px">🏠</div>
                <div style="font-size:20px;font-weight:700;color:#92400e;margin-bottom:8px">Rendre au dépôt</div>
                <div style="font-size:14px;color:#78350f;margin-bottom:4px">
                    <?php echo dol_escape_htmltag($materiel->ref); ?> - <?php echo dol_escape_htmltag($materiel->nom); ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="font-size:13px;color:var(--text-light);margin-bottom:16px;line-height:1.6">
                📸 Prenez une photo du matériel au dépôt pour valider le retour. Le responsable du dépôt pourra vérifier.
            </div>

            <form method="POST" action="?action=rendre_photo&id=<?php echo $id; ?>" accept-charset="UTF-8" enctype="multipart/form-data" id="rendreForm">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <div style="margin-bottom:16px">
                    <label style="font-size:12px;font-weight:600;color:var(--text);display:block;margin-bottom:8px">
                        PHOTO DU MATÉRIEL AU DÉPÔT *
                    </label>
                    <input type="file" name="photo" accept="image/*" capture="environment" required
                           class="form-input" id="photoInput"
                           style="padding:12px;border:2px dashed var(--border);border-radius:12px">
                    <div id="photoPreview" style="margin-top:12px;display:none">
                        <img id="previewImage" style="width:100%;border-radius:12px;max-height:300px;object-fit:cover">
                    </div>
                </div>

                <div style="margin-bottom:16px">
                    <label style="font-size:12px;font-weight:600;color:var(--text);display:block;margin-bottom:8px">
                        COMMENTAIRE (optionnel)
                    </label>
                    <textarea name="commentaire" class="form-input" rows="3" placeholder="État du matériel, observations..."></textarea>
                </div>

                <button type="submit" class="btn" style="background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);color:white;width:100%">
                    ✅ Confirmer le retour au dépôt
                </button>
            </form>
        </div>

        <a href="view.php?id=<?php echo $id; ?>" class="btn" style="background:var(--card);color:var(--text);border:1px solid var(--border);text-align:center;display:block">
            ❌ Annuler
        </a>

        <?php elseif ($action == 'transferer'): ?>

        <div class="card" style="background:linear-gradient(135deg,#e0e7ff 0%,#c7d2fe 100%);border-left:4px solid #6366f1">
            <div style="text-align:center;padding:20px">
                <div style="font-size:64px;margin-bottom:16px">🔄</div>
                <div style="font-size:20px;font-weight:700;color:#3730a3;margin-bottom:8px">Transférer à un collègue</div>
                <div style="font-size:14px;color:#312e81;margin-bottom:4px">
                    <?php echo dol_escape_htmltag($materiel->ref); ?> - <?php echo dol_escape_htmltag($materiel->nom); ?>
                </div>
            </div>
        </div>

        <div class="card">
            <form method="POST" action="?action=transferer&id=<?php echo $id; ?>" accept-charset="UTF-8">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <div style="margin-bottom:16px">
                    <label style="font-size:12px;font-weight:600;color:var(--text);display:block;margin-bottom:8px">
                        TRANSFÉRER À *
                    </label>
                    <select name="target_user" required class="form-input">
                        <option value="">-- Sélectionner un ouvrier --</option>
                        <?php
                        while ($usr = $db->fetch_object($resql_users)) {
                            echo '<option value="'.$usr->rowid.'">'.dol_escape_htmltag($usr->firstname.' '.$usr->lastname).'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div style="background:#fef3c7;padding:12px;border-radius:8px;margin-bottom:16px">
                    <div style="font-size:13px;color:#92400e;line-height:1.6">
                        ℹ️ Le matériel sera immédiatement affecté à la personne sélectionnée. L'historique sera conservé.
                    </div>
                </div>

                <button type="submit" class="btn" style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);color:white;width:100%">
                    ✅ Confirmer le transfert
                </button>
            </form>
        </div>

        <a href="view.php?id=<?php echo $id; ?>" class="btn" style="background:var(--card);color:var(--text);border:1px solid var(--border);text-align:center;display:block">
            ❌ Annuler
        </a>

        <?php elseif ($action == 'reprendre'): ?>

        <div class="card" style="background:linear-gradient(135deg,#d1fae5 0%,#a7f3d0 100%);border-left:4px solid #10b981">
            <div style="text-align:center;padding:20px">
                <div style="font-size:64px;margin-bottom:16px">🔄</div>
                <div style="font-size:20px;font-weight:700;color:#065f46;margin-bottom:8px">Reprendre matériel</div>
                <div style="font-size:14px;color:#064e3b;margin-bottom:4px">
                    <?php echo dol_escape_htmltag($materiel->ref); ?> - <?php echo dol_escape_htmltag($materiel->nom); ?>
                </div>
                <div style="font-size:13px;color:#047857;margin-top:8px">
                    Actuellement chez : <?php echo dol_escape_htmltag($materiel->user_firstname.' '.$materiel->user_lastname); ?>
                </div>
            </div>
        </div>

        <div class="card">
            <form method="POST" action="?action=reprendre_confirm&id=<?php echo $id; ?>" accept-charset="UTF-8"&from_user=<?php echo $from_user; ?>">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <div style="background:#dbeafe;padding:16px;border-radius:12px;margin-bottom:16px">
                    <div style="font-size:13px;color:#1e40af;line-height:1.6">
                        ℹ️ En cliquant sur "Confirmer", ce matériel sera transféré à votre nom. Votre collègue sera notifié de la reprise.
                    </div>
                </div>

                <div style="margin-bottom:16px">
                    <label style="font-size:12px;font-weight:600;color:var(--text);display:block;margin-bottom:8px">
                        MOTIF (optionnel)
                    </label>
                    <textarea name="commentaire" class="form-input" rows="3" placeholder="Raison de la reprise..."></textarea>
                </div>

                <button type="submit" class="btn" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;width:100%">
                    ✅ Confirmer la reprise
                </button>
            </form>
        </div>

        <a href="list.php?action=reprendre" class="btn" style="background:var(--card);color:var(--text);border:1px solid var(--border);text-align:center;display:block">
            ❌ Annuler
        </a>

        <?php endif; ?>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="../js/app.js"></script>
    <script>
        document.getElementById('photoInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('photoPreview').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
<?php $db->close(); ?>
