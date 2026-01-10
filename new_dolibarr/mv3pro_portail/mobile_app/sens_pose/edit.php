<?php
/**
 * Édition fiche sens de pose - Version Mobile COMPLETE avec édition pièces
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$error_msg = '';
$success_msg = '';

if (!$id) {
    header('Location: list.php');
    exit;
}

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".(int)$id;
$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) == 0) {
    header('Location: list.php');
    exit;
}

$fiche = $db->fetch_object($resql);

// ========== ACTIONS ==========

// Action: Update fiche info
if ($action == 'update') {
    $client_name = GETPOST('client_name', 'restricthtml');
    $site_address = GETPOST('site_address', 'restricthtml');
    $telephone = GETPOST('telephone', 'restricthtml');
    $notes = GETPOST('notes', 'restricthtml');

    if (empty($client_name)) {
        $error_msg = "Le nom du client est obligatoire";
    } else {
        $db->begin();

        $sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_sens_pose SET
                client_name = '".$db->escape($client_name)."',
                site_address = '".$db->escape($site_address)."',
                telephone = '".$db->escape($telephone)."',
                notes = '".$db->escape($notes)."',
                fk_user_modif = ".(int)$user->id.",
                date_modification = NOW()
                WHERE rowid = ".(int)$id;

        if ($db->query($sql_update)) {
            $db->commit();
            $success_msg = "Informations mises à jour avec succès";
            $fiche = $db->fetch_object($db->query("SELECT * FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".(int)$id));
        } else {
            $db->rollback();
            $error_msg = "Erreur lors de la modification: ".$db->lasterror();
        }
    }
}

// Action: Update piece text (AJAX)
if ($action == 'update_piece_text') {
    header('Content-Type: application/json');

    $piece_id = GETPOST('piece_id', 'int');
    $piece_text = $_POST['piece_text'];

    if (empty($piece_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID de piece manquant']);
        exit;
    }

    $piece_text_clean = strip_tags($piece_text);

    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_sens_pose_pieces
            SET piece_text = '".$db->escape($piece_text_clean)."'
            WHERE rowid = ".(int)$piece_id."
            AND fk_sens_pose = ".(int)$id;

    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Texte mis a jour', 'text' => $piece_text_clean]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $db->lasterror()]);
    }
    exit;
}

// Action: Add piece
if ($action == 'add_piece') {
    $noms = GETPOST('noms', 'restricthtml');
    $type_pose = GETPOST('type_pose', 'restricthtml');
    $sens = GETPOST('sens', 'restricthtml');
    $format = GETPOST('format', 'restricthtml');
    $epaisseur = GETPOST('epaisseur', 'restricthtml');
    $joint_ciment = GETPOST('joint_ciment', 'restricthtml');
    $joint_ciment_color = GETPOST('joint_ciment_color', 'restricthtml');
    $joint_silicone = GETPOST('joint_silicone', 'restricthtml');
    $joint_silicone_color = GETPOST('joint_silicone_color', 'restricthtml');
    $plinthes = GETPOST('plinthes', 'restricthtml');
    $plinthes_hauteur = GETPOST('plinthes_hauteur', 'restricthtml');
    $profil = GETPOST('profil', 'restricthtml');
    $profil_finition = GETPOST('profil_finition', 'restricthtml');
    $credence = GETPOST('credence', 'restricthtml');
    $credence_hauteur = GETPOST('credence_hauteur', 'restricthtml');
    $jusqu_au_plafond = GETPOST('jusqu_au_plafond', 'restricthtml');
    $remarques = GETPOST('remarques', 'restricthtml');
    $piece_text = GETPOST('piece_text', 'restricthtml');
    $photo_reference = GETPOST('photo_reference', 'restricthtml');
    $photo_url = GETPOST('photo_url', 'restricthtml');
    $photo_filename = GETPOST('photo_filename', 'restricthtml');
    $fk_product = GETPOST('fk_product', 'int');

    $pieces_list = array_filter(explode(',', $noms));

    if (count($pieces_list) > 0) {
        $db->begin();
        $success_count = 0;

        foreach ($pieces_list as $nom) {
            $nom = trim($nom);
            if (empty($nom)) continue;

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose_pieces
                    (entity, fk_sens_pose, nom, type_pose, sens, format, epaisseur,
                     joint_ciment, joint_ciment_color, joint_silicone, joint_silicone_color,
                     plinthes, plinthes_hauteur, profil, profil_finition,
                     credence, credence_hauteur, jusqu_au_plafond, remarques, piece_text,
                     photo_reference, photo_url, photo_filename, fk_product, ordre)
                    VALUES (".$conf->entity.", ".(int)$id.",
                    '".$db->escape($nom)."', '".$db->escape($type_pose)."', '".$db->escape($sens)."',
                    '".$db->escape($format)."', '".$db->escape($epaisseur)."',
                    '".$db->escape($joint_ciment)."', '".$db->escape($joint_ciment_color)."',
                    '".$db->escape($joint_silicone)."', '".$db->escape($joint_silicone_color)."',
                    '".$db->escape($plinthes)."', '".$db->escape($plinthes_hauteur)."',
                    '".$db->escape($profil)."', '".$db->escape($profil_finition)."',
                    '".$db->escape($credence)."', '".$db->escape($credence_hauteur)."',
                    '".$db->escape($jusqu_au_plafond)."', '".$db->escape($remarques)."', '".$db->escape($piece_text)."',
                    '".$db->escape($photo_reference)."', '".$db->escape($photo_url)."', '".$db->escape($photo_filename)."', ".(int)$fk_product.", 0)";

            if ($db->query($sql)) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            $db->commit();
            $success_msg = "$success_count pièce(s) ajoutée(s) avec succès";
        } else {
            $db->rollback();
            $error_msg = "Erreur lors de l'ajout: " . $db->lasterror();
        }
    } else {
        $error_msg = "Sélectionnez au moins une pièce";
    }
}

// Action: Update piece
if ($action == 'update_piece') {
    $piece_id = GETPOST('piece_id', 'int');
    $nom = GETPOST('nom', 'restricthtml');
    $type_pose = GETPOST('type_pose', 'restricthtml');
    $sens = GETPOST('sens', 'restricthtml');
    $format = GETPOST('format', 'restricthtml');
    $epaisseur = GETPOST('epaisseur', 'restricthtml');
    $joint_ciment = GETPOST('joint_ciment', 'restricthtml');
    $joint_ciment_color = GETPOST('joint_ciment_color', 'restricthtml');
    $joint_silicone = GETPOST('joint_silicone', 'restricthtml');
    $joint_silicone_color = GETPOST('joint_silicone_color', 'restricthtml');
    $plinthes = GETPOST('plinthes', 'restricthtml');
    $plinthes_hauteur = GETPOST('plinthes_hauteur', 'restricthtml');
    $profil = GETPOST('profil', 'restricthtml');
    $profil_finition = GETPOST('profil_finition', 'restricthtml');
    $credence = GETPOST('credence', 'restricthtml');
    $credence_hauteur = GETPOST('credence_hauteur', 'restricthtml');
    $jusqu_au_plafond = GETPOST('jusqu_au_plafond', 'restricthtml');
    $remarques = GETPOST('remarques', 'restricthtml');
    $piece_text = GETPOST('piece_text', 'restricthtml');
    $photo_reference = GETPOST('photo_reference', 'restricthtml');
    $photo_url = GETPOST('photo_url', 'restricthtml');
    $photo_filename = GETPOST('photo_filename', 'restricthtml');
    $fk_product = GETPOST('fk_product', 'int');

    $photo_updates = "";
    if (!empty($photo_reference) || !empty($photo_url) || !empty($photo_filename) || $fk_product > 0) {
        if (!empty($photo_reference)) $photo_updates .= ", photo_reference = '".$db->escape($photo_reference)."'";
        if (!empty($photo_url)) $photo_updates .= ", photo_url = '".$db->escape($photo_url)."'";
        if (!empty($photo_filename)) $photo_updates .= ", photo_filename = '".$db->escape($photo_filename)."'";
        if ($fk_product > 0) $photo_updates .= ", fk_product = ".(int)$fk_product;
    }

    $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_sens_pose_pieces SET
            nom = '".$db->escape($nom)."',
            type_pose = '".$db->escape($type_pose)."',
            sens = '".$db->escape($sens)."',
            format = '".$db->escape($format)."',
            epaisseur = '".$db->escape($epaisseur)."',
            joint_ciment = '".$db->escape($joint_ciment)."',
            joint_ciment_color = '".$db->escape($joint_ciment_color)."',
            joint_silicone = '".$db->escape($joint_silicone)."',
            joint_silicone_color = '".$db->escape($joint_silicone_color)."',
            plinthes = '".$db->escape($plinthes)."',
            plinthes_hauteur = '".$db->escape($plinthes_hauteur)."',
            profil = '".$db->escape($profil)."',
            profil_finition = '".$db->escape($profil_finition)."',
            credence = '".$db->escape($credence)."',
            credence_hauteur = '".$db->escape($credence_hauteur)."',
            jusqu_au_plafond = '".$db->escape($jusqu_au_plafond)."',
            remarques = '".$db->escape($remarques)."',
            piece_text = '".$db->escape($piece_text)."'
            ".$photo_updates."
            WHERE rowid = ".(int)$piece_id;

    if ($db->query($sql)) {
        $success_msg = "Pièce modifiée avec succès";
    } else {
        $error_msg = "Erreur lors de la modification: " . $db->lasterror();
    }
}

// Action: Delete piece
if ($action == 'delete_piece') {
    $piece_id = GETPOST('piece_id', 'int');
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_sens_pose_pieces WHERE rowid = ".(int)$piece_id." AND fk_sens_pose = ".(int)$id;
    if ($db->query($sql)) {
        $success_msg = "Pièce supprimée avec succès";
    } else {
        $error_msg = "Erreur lors de la suppression";
    }
}

// Function to get product image
function getProductImage($product_ref, $fk_product = null) {
    global $conf, $db;

    if (empty($product_ref) && empty($fk_product)) return null;

    if (empty($product_ref) && !empty($fk_product)) {
        $product = new Product($db);
        if ($product->fetch($fk_product) > 0) {
            $product_ref = $product->ref;
        } else {
            return null;
        }
    }

    if (empty($product_ref)) return null;

    $product_dir = $conf->product->dir_output.'/'.$product_ref;
    if (!is_dir($product_dir)) return null;

    $photo_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP');
    $files = @scandir($product_dir);
    if (!$files) return null;

    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array($ext, $photo_extensions)) {
            return array(
                'path' => $product_dir.'/'.$file,
                'url' => DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($product_ref.'/'.$file),
                'url_proxy' => 'get_product_image.php?ref='.urlencode($product_ref),
                'filename' => $file,
                'ref' => $product_ref
            );
        }
    }

    return null;
}

$sql_pieces = "SELECT p.*, pr.label as product_label, pr.ref as product_ref
               FROM ".MAIN_DB_PREFIX."mv3_sens_pose_pieces p
               LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = p.fk_product
               WHERE p.fk_sens_pose = ".(int)$id."
               ORDER BY p.ordre ASC, p.rowid ASC";
$resql_pieces = $db->query($sql_pieces);
$nb_pieces = $resql_pieces ? $db->num_rows($resql_pieces) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Modifier <?php echo dol_escape_htmltag($fiche->ref); ?> - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        .piece-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .piece-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .piece-name {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .piece-actions {
            display: flex;
            gap: 8px;
        }
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        .piece-info {
            display: grid;
            gap: 8px;
            font-size: 13px;
        }
        .piece-row {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .piece-label {
            color: #64748b;
            min-width: 100px;
        }
        .piece-value {
            color: #0f172a;
            font-weight: 500;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            margin: 20px;
            border-radius: 12px;
            padding: 20px;
            max-width: 600px;
            margin: 20px auto;
        }
        .modal-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #0f172a;
        }
        .modal-close {
            float: right;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="app-header">
        <a href="view.php?id=<?php echo $id; ?>" class="app-header-back">←</a>
        <div>
            <div class="app-header-title">✏️ Modifier</div>
            <div class="app-header-subtitle"><?php echo dol_escape_htmltag($fiche->ref); ?></div>
        </div>
    </div>

    <div class="app-container" style="padding-bottom: 80px;">
        <?php if ($error_msg): ?>
        <div class="card" style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px; margin-bottom: 12px;">
            <div style="color: #dc2626; font-size: 14px;">
                ⚠️ <?php echo dol_escape_htmltag($error_msg); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <div class="card" style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 12px; margin-bottom: 12px;">
            <div style="color: #065f46; font-size: 14px;">
                ✅ <?php echo dol_escape_htmltag($success_msg); ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="edit.php?id=<?php echo $id; ?>" accept-charset="UTF-8">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">

            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Informations générales</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nom du client *</label>
                    <input type="text"
                           name="client_name"
                           class="form-input"
                           required
                           value="<?php echo dol_escape_htmltag($fiche->client_name); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse du chantier</label>
                    <input type="text"
                           name="site_address"
                           class="form-input"
                           placeholder="Ex: 123 Rue de la Paix, 75001 Paris"
                           value="<?php echo dol_escape_htmltag($fiche->site_address); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel"
                           name="telephone"
                           class="form-input"
                           placeholder="Ex: 06 12 34 56 78"
                           value="<?php echo dol_escape_htmltag($fiche->telephone); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes"
                              class="form-input"
                              rows="4"
                              placeholder="Notes supplémentaires..."><?php echo dol_escape_htmltag($fiche->notes); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        ✅ Enregistrer les informations
                    </button>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-header">
                <div class="card-title">🔲 Pièces et produits (<?php echo $nb_pieces; ?>)</div>
            </div>

            <?php if ($nb_pieces > 0): ?>
                <?php
                $piece_num = 1;
                while ($piece = $db->fetch_object($resql_pieces)):
                ?>
                <div class="piece-card">
                    <div class="piece-header">
                        <div class="piece-name"><?php echo $piece_num; ?>. <?php echo dol_escape_htmltag($piece->nom); ?></div>
                        <div class="piece-actions">
                            <button class="btn-icon btn-edit" onclick="editPiece(<?php echo $piece->rowid; ?>)">✏️</button>
                            <button class="btn-icon btn-delete" onclick="if(confirm('Supprimer cette pièce ?')) location.href='edit.php?id=<?php echo $id; ?>&action=delete_piece&piece_id=<?php echo $piece->rowid; ?>'">🗑️</button>
                        </div>
                    </div>

                    <div class="piece-info">
                        <?php if (!empty($piece->product_label)): ?>
                        <div class="piece-row">
                            <div class="piece-label">Produit:</div>
                            <div class="piece-value"><?php echo dol_escape_htmltag($piece->product_ref.' - '.$piece->product_label); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($piece->type_pose)): ?>
                        <div class="piece-row">
                            <div class="piece-label">Type de pose:</div>
                            <div class="piece-value"><?php echo dol_escape_htmltag($piece->type_pose); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($piece->sens)): ?>
                        <div class="piece-row">
                            <div class="piece-label">Sens:</div>
                            <div class="piece-value"><?php echo dol_escape_htmltag($piece->sens); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($piece->format)): ?>
                        <div class="piece-row">
                            <div class="piece-label">Format:</div>
                            <div class="piece-value"><?php echo dol_escape_htmltag($piece->format); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($piece->piece_text)): ?>
                        <div class="piece-row">
                            <div class="piece-label">Description:</div>
                            <div class="piece-value"><?php echo nl2br(dol_escape_htmltag($piece->piece_text)); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $piece_num++;
                endwhile;
                ?>
            <?php else: ?>
                <div style="padding: 20px; text-align: center; color: #64748b;">
                    Aucune pièce configurée
                </div>
            <?php endif; ?>

            <a href="../../sens_pose/edit_pieces.php?id=<?php echo $id; ?>" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 12px;">
                ➕ Gérer les pièces (Desktop)
            </a>
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary" style="flex: 1;">
                ← Retour
            </a>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
    function editPiece(pieceId) {
        // Pour l'instant, redirection vers desktop
        window.location.href = '../../sens_pose/edit_pieces.php?id=<?php echo $id; ?>';
    }
    </script>
</body>
</html>
