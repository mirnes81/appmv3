<?php
/**
 * Édition des pièces - Étape 2
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

if (!$id) {
    header('Location: list.php');
    exit;
}

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".(int)$id;
$resql = $db->query($sql);
$fiche = $db->fetch_object($resql);

if ($action == 'update_piece_text') {
    header('Content-Type: application/json');

    $piece_id = GETPOST('piece_id', 'int');
    $piece_text = GETPOST('piece_text', 'restricthtml');

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
            header('Location: edit_pieces.php?id='.$id.'&msg=added&count='.$success_count);
            exit;
        } else {
            $db->rollback();
            $error = "Erreur SQL: " . $db->lasterror();
        }
    } else {
        $error = "Sélectionnez au moins une pièce";
    }
}

if ($action == 'update_piece') {
    $piece_id = GETPOST('piece_id', 'int');
    $nom = GETPOST('noms', 'restricthtml');
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
        header('Location: edit_pieces.php?id='.$id.'&msg=updated');
        exit;
    } else {
        $error = "Erreur lors de la mise à jour: " . $db->lasterror();
    }
}

if ($action == 'delete_piece') {
    $piece_id = GETPOST('piece_id', 'int');
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_sens_pose_pieces WHERE rowid = ".(int)$piece_id;
    $db->query($sql);
    header('Location: edit_pieces.php?id='.$id.'&msg=deleted');
    exit;
}

$sql_pieces = "SELECT p.*, pr.label as product_label, pr.ref as product_ref
               FROM ".MAIN_DB_PREFIX."mv3_sens_pose_pieces p
               LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = p.fk_product
               WHERE p.fk_sens_pose = ".(int)$id."
               ORDER BY p.ordre ASC, p.rowid ASC";
$resql_pieces = $db->query($sql_pieces);

llxHeader('', 'Édition pièces');
?>

<style>
:root {
    --primary: #0891b2;
    --primary-dark: #0e7490;
}

.sens-pose-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.step-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
}

.step {
    display: flex;
    align-items: center;
    gap: 12px;
}

.step-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.step-active .step-number {
    background: var(--primary);
    color: white;
}

.step-inactive .step-number {
    background: #e2e8f0;
    color: #94a3b8;
}

.step-label {
    font-size: 14px;
    font-weight: 600;
}

.step-active .step-label {
    color: var(--primary);
}

.step-inactive .step-label {
    color: #94a3b8;
}

.step-separator {
    width: 40px;
    height: 2px;
    background: #e2e8f0;
}

.pieces-list {
    display: grid;
    gap: 16px;
    margin-bottom: 30px;
}

.piece-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.piece-info {
    flex: 1;
}

.piece-name {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 8px;
}

.piece-details {
    font-size: 14px;
    color: #64748b;
}

.piece-actions {
    display: flex;
    gap: 8px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-danger {
    background: #fee2e2;
    color: #991b1b;
}

.form-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.form-section-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.form-input, .form-textarea, .form-select {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
}

.form-textarea {
    min-height: 80px;
    resize: vertical;
}

.type-pose-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.piece-btn {
    padding: 14px 12px;
    border: 2px solid #eab308;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
    color: #854d0e;
}

.piece-btn:hover {
    background: #fef3c7;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(234, 179, 8, 0.3);
}

.piece-btn.active {
    border-color: #854d0e;
    background: #eab308;
    color: white;
}

.type-pose-btn {
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
}

.type-pose-btn:hover {
    border-color: var(--primary);
    background: #f0fdfa;
}

.type-pose-btn.active {
    border-color: var(--primary);
    background: var(--primary);
    color: white;
}

.type-pose-btn-img {
    padding: 16px;
    border: 3px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    cursor: pointer;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
    min-height: 120px;
}

.type-pose-btn-img:hover {
    border-color: var(--primary);
    background: #f0fdfa;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2);
}

.type-pose-btn-img.active {
    border-color: var(--primary);
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    color: white;
    box-shadow: 0 6px 16px rgba(8, 145, 178, 0.4);
}

.type-pose-btn-img.active svg rect,
.type-pose-btn-img.active svg polygon {
    fill: white !important;
    stroke: rgba(255, 255, 255, 0.6) !important;
}

.type-pose-btn-img span {
    font-size: 12px;
    font-weight: 600;
    margin-top: 4px;
}

.color-preset-btn {
    padding: 8px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s;
    color: #0f172a;
}

.color-preset-btn:hover {
    border-color: var(--primary);
    background: #f0fdfa;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(8,145,178,0.15);
}

.color-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 2px solid #e2e8f0;
}

.color-input-group {
    display: flex;
    gap: 12px;
    align-items: end;
}

.color-input-group input[type="text"] {
    flex: 1;
}

.color-input-group input[type="color"] {
    width: 50px;
    height: 42px;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    cursor: pointer;
}

.alert-success {
    padding: 12px 16px;
    background: #d1fae5;
    border-left: 4px solid #10b981;
    border-radius: 8px;
    color: #065f46;
    margin-bottom: 20px;
}

.empty-state {
    text-align: center;
    padding: 40px;
    background: #f8fafc;
    border-radius: 12px;
    border: 2px dashed #cbd5e1;
    margin-bottom: 30px;
}
</style>

<div class="sens-pose-container">
    <div class="page-header">
        <div class="page-title">🔲 <?php echo htmlspecialchars($fiche->ref); ?> - Édition des pièces</div>
        <div class="page-subtitle">Étape 2 : Ajoutez les pièces et leurs caractéristiques</div>
    </div>

    <div class="step-indicator">
        <div class="step step-inactive">
            <div class="step-number">1</div>
            <div class="step-label">Informations</div>
        </div>
        <div class="step-separator"></div>
        <div class="step step-active">
            <div class="step-number">2</div>
            <div class="step-label">Pièces</div>
        </div>
        <div class="step-separator"></div>
        <div class="step step-inactive">
            <div class="step-number">3</div>
            <div class="step-label">Signature</div>
        </div>
    </div>

    <?php if (GETPOST('msg') == 'added'): ?>
    <div class="alert-success">✅ Pièce ajoutée avec succès</div>
    <?php endif; ?>

    <?php if (GETPOST('msg') == 'deleted'): ?>
    <div class="alert-success">✅ Pièce supprimée</div>
    <?php endif; ?>

    <?php if (GETPOST('msg') == 'added'):
        $count = GETPOST('count', 'int');
        if ($count > 1): ?>
            <div class="alert-success">✅ <?php echo $count; ?> pièces ajoutées avec succès !</div>
        <?php else: ?>
            <div class="alert-success">✅ Pièce ajoutée avec succès !</div>
        <?php endif;
    endif; ?>

    <div class="form-section-title">📋 Pièces ajoutées</div>

    <?php if ($db->num_rows($resql_pieces) == 0): ?>
        <div class="empty-state">
            <div style="font-size:48px;margin-bottom:12px">📦</div>
            <div style="font-size:16px;font-weight:600;color:#475569;margin-bottom:8px">Aucune pièce ajoutée</div>
            <div style="font-size:14px;color:#64748b">Commencez par ajouter votre première pièce ci-dessous</div>
        </div>
    <?php else: ?>
        <div class="pieces-list">
            <?php while ($piece = $db->fetch_object($resql_pieces)): ?>
            <div class="piece-card" style="display:block;">
                <div style="display:flex; gap:16px; align-items:flex-start;">
                    <?php if (!empty($piece->photo_url)):
                        $photo_display_url = html_entity_decode($piece->photo_url);
                        if (!preg_match('/^https?:\/\//', $photo_display_url)) {
                            if (strpos($photo_display_url, '/') !== 0) {
                                $photo_display_url = '/' . $photo_display_url;
                            }
                            $photo_display_url = DOL_MAIN_URL_ROOT . $photo_display_url;
                        }
                    ?>
                    <div style="flex-shrink:0;">
                        <img src="<?php echo htmlspecialchars($photo_display_url); ?>"
                             style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:2px solid #e2e8f0;"
                             alt="Photo carrelage"
                             onerror="console.error('Photo error:', this.src); this.style.display='none';">
                    </div>
                    <?php endif; ?>
                    <div class="piece-info" style="flex:1;">
                        <div class="piece-name"><?php echo htmlspecialchars($piece->nom); ?></div>

                        <div class="piece-details" style="margin-top:6px;">
                            <?php if (!empty($piece->format)): ?>
                                📏 <?php echo htmlspecialchars($piece->format); ?>
                            <?php endif; ?>
                            <?php if ($piece->type_pose): ?>
                                <?php if (!empty($piece->format)) echo ' • '; ?>
                                <?php echo htmlspecialchars($piece->type_pose); ?>
                            <?php endif; ?>
                            <?php if ($piece->sens): ?>
                                • <?php echo htmlspecialchars($piece->sens); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="piece-actions" style="display:flex; gap:8px; flex-shrink:0;">
                        <button type="button" onclick="editPiece(<?php echo $piece->rowid; ?>)" class="btn btn-primary" style="background:#0891b2; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">
                            ✏️ Modifier
                        </button>
                        <a href="?action=delete_piece&id=<?php echo $id; ?>&piece_id=<?php echo $piece->rowid; ?>&token=<?php echo newToken(); ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Supprimer cette pièce ?')">
                            🗑️ Supprimer
                        </a>
                    </div>
                </div>

                <?php if (!empty($piece->piece_text)): ?>
                <div style="position:relative; font-size:13px; color:#475569; margin:12px 0 0 0; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; line-height:1.7;">
                    <button type="button" onclick="editPieceText(<?php echo $piece->rowid; ?>)" style="position:absolute; top:8px; right:8px; background:#0891b2; color:white; border:none; padding:4px 10px; border-radius:4px; cursor:pointer; font-size:11px;">
                        ✏️ Modifier
                    </button>
                    <div id="piece-text-display-<?php echo $piece->rowid; ?>">
                        <?php
                        $formatted_text = htmlspecialchars($piece->piece_text);
                        $formatted_text = nl2br($formatted_text);
                        echo $formatted_text;
                        ?>
                    </div>
                    <div id="piece-text-edit-<?php echo $piece->rowid; ?>" style="display:none;">
                        <textarea id="piece-text-input-<?php echo $piece->rowid; ?>" style="width:100%; min-height:80px; padding:8px; border:1px solid #cbd5e1; border-radius:4px; font-size:13px; font-family:inherit;"><?php echo htmlspecialchars($piece->piece_text); ?></textarea>
                        <div style="margin-top:8px; display:flex; gap:8px;">
                            <button type="button" onclick="savePieceText(<?php echo $piece->rowid; ?>)" style="background:#0891b2; color:white; border:none; padding:6px 14px; border-radius:4px; cursor:pointer; font-size:12px;">
                                💾 Enregistrer
                            </button>
                            <button type="button" onclick="cancelEditPieceText(<?php echo $piece->rowid; ?>)" style="background:#6b7280; color:white; border:none; padding:6px 14px; border-radius:4px; cursor:pointer; font-size:12px;">
                                ✕ Annuler
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>

        <script>
        const piecesData = {
            <?php
            $sql_for_js = "SELECT p.*, pr.label as product_label, pr.ref as product_ref
                           FROM ".MAIN_DB_PREFIX."mv3_sens_pose_pieces p
                           LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = p.fk_product
                           WHERE p.fk_sens_pose = ".(int)$id."
                           ORDER BY p.ordre ASC, p.rowid ASC";
            $resql_for_js = $db->query($sql_for_js);
            $first = true;
            while ($piece = $db->fetch_object($resql_for_js)):
                if (!$first) echo ',';
                $first = false;
            ?>
            <?php echo $piece->rowid; ?>: <?php echo json_encode([
                'rowid' => $piece->rowid,
                'nom' => $piece->nom,
                'type_pose' => $piece->type_pose,
                'sens' => $piece->sens,
                'format' => $piece->format,
                'epaisseur' => $piece->epaisseur,
                'joint_ciment' => $piece->joint_ciment,
                'joint_ciment_color' => $piece->joint_ciment_color,
                'joint_silicone' => $piece->joint_silicone,
                'joint_silicone_color' => $piece->joint_silicone_color,
                'plinthes' => $piece->plinthes,
                'plinthes_hauteur' => $piece->plinthes_hauteur,
                'profil' => $piece->profil,
                'profil_finition' => $piece->profil_finition,
                'credence' => $piece->credence,
                'credence_hauteur' => $piece->credence_hauteur,
                'jusqu_au_plafond' => $piece->jusqu_au_plafond,
                'piece_text' => $piece->piece_text,
                'remarques' => $piece->remarques,
                'photo_reference' => $piece->photo_reference,
                'photo_url' => $piece->photo_url,
                'photo_filename' => $piece->photo_filename,
                'fk_product' => $piece->fk_product
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            <?php endwhile; ?>
        };
        console.log('📊 piecesData loaded:', piecesData);
        </script>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background:#fee2e2; border:2px solid #ef4444; color:#991b1b; padding:16px; border-radius:8px; margin-bottom:20px;">
            <strong>❌ Erreur:</strong> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?id=<?php echo $id; ?>" accept-charset="UTF-8"&action=add_piece" class="form-card" id="piece-form">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="piece_id" id="edit-piece-id" value="">

        <div class="form-section-title" id="form-title">➕ Ajouter une pièce</div>

        <!-- ÉTAPE 1: Sélection des PIÈCES (multiple) -->
        <div style="margin-bottom:24px; padding:20px; background:#fefce8; border-radius:10px; border:2px solid #eab308;">
            <div style="font-weight:700; color:#854d0e; margin-bottom:12px; font-size:16px;">
                📍 ÉTAPE 1 : Sélectionnez les pièces
            </div>
            <div style="font-size:13px; color:#713f12; margin-bottom:8px; padding:10px; background:#fef9c3; border-radius:6px; border:1px solid #fde047;">
                ✨ <strong>Multi-sélection :</strong> Cliquez sur PLUSIEURS pièces pour créer UNE SEULE fiche avec le même carrelage et sens de pose !
            </div>
            <div style="font-size:12px; color:#92400e; margin-bottom:16px; font-style:italic;">
                💡 Exemple : Cuisine + Salon + Couloir = 1 fiche pour 3 pièces avec le même carrelage
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px;">
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Cuisine')">
                    🍳 Cuisine
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Salle de bain')">
                    🚿 Salle de bain
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'WC')">
                    🚽 WC
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Séjour')">
                    🛋️ Séjour
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Entrée')">
                    🚪 Entrée
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Couloir')">
                    ↔️ Couloir
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Chambre')">
                    🛏️ Chambre
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Bureau')">
                    💼 Bureau
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Terrasse')">
                    🌿 Terrasse
                </button>
                <button type="button" class="piece-btn" onclick="togglePiece(this, 'Garage')">
                    🚗 Garage
                </button>
            </div>

            <div style="margin-top:16px; padding:12px; background:white; border-radius:8px; border:2px solid #eab308;">
                <div style="font-size:13px; font-weight:600; color:#854d0e; margin-bottom:8px;">
                    📋 Pièces sélectionnées : <span id="selected-count" style="color:#ca8a04;">0</span>
                </div>
                <div id="selected-pieces-display" style="font-size:14px; color:#0f172a; min-height:24px;">
                    Aucune pièce sélectionnée
                </div>
                <input type="hidden" name="noms" id="input-noms" required>
            </div>
        </div>

        <!-- ÉTAPE 2: Sélection du CARRELAGE -->
        <div style="margin-bottom:24px; padding:20px; background:#f0f9ff; border-radius:10px; border:2px solid #0891b2;">
            <div style="font-weight:700; color:#0e7490; margin-bottom:12px; font-size:16px;">
                🎨 ÉTAPE 2 : Choisissez le carrelage
            </div>
            <div style="font-size:13px; color:#0c4a6e; margin-bottom:16px;">
                Sélectionnez un carrelage depuis vos devis avec photo et format automatiques
            </div>

            <button type="button" class="btn btn-secondary" onclick="openProduitGallery()" style="width:100%; margin-bottom:16px;">
                📦 Ouvrir la galerie de produits
            </button>

            <div id="selected-produit-preview" style="display:none; padding:12px; background:white; border-radius:6px; border:1px solid #cbd5e1;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <img id="preview-produit-img" src="" style="width:60px; height:60px; object-fit:cover; border-radius:4px; border:1px solid #e2e8f0;">
                    <div style="flex:1;">
                        <div style="font-weight:600; color:#0f172a; font-size:14px;" id="preview-produit-label"></div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;" id="preview-produit-ref"></div>
                        <div style="font-size:12px; color:#0891b2; font-weight:600; margin-top:2px;" id="preview-produit-format"></div>
                    </div>
                    <button type="button" onclick="clearProduit()" style="background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:13px;">
                        ✕
                    </button>
                </div>
            </div>

            <div style="margin-top:12px;">
                <div style="font-size:13px; color:#64748b; margin-bottom:8px;">Ou saisissez manuellement :</div>
                <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px;">
                    <div>
                        <label class="form-label">Format carrelage</label>
                        <input type="text" name="format" id="input-format" class="form-input" placeholder="Ex: 60×60 cm">
                    </div>
                    <div>
                        <label class="form-label">Épaisseur (mm)</label>
                        <input type="text" name="epaisseur" id="input-epaisseur" class="form-input" placeholder="Ex: 10">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Type de pose</label>
            <div class="type-pose-grid">
                <button type="button" class="type-pose-btn-img" onclick="selectTypePose(this, 'Droite')">
                    <svg viewBox="0 0 100 60" style="width:100%; height:60px;">
                        <rect x="5" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="35" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="65" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="5" y="20" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="35" y="20" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="65" y="20" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="5" y="35" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="35" y="35" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="65" y="35" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                    </svg>
                    <span>Droite</span>
                </button>
                <button type="button" class="type-pose-btn-img" onclick="selectTypePose(this, 'Décalée 1/2')">
                    <svg viewBox="0 0 100 60" style="width:100%; height:60px;">
                        <rect x="5" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="35" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="65" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="17.5" y="20" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="47.5" y="20" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="77.5" y="20" width="15" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="5" y="35" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="35" y="35" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="65" y="35" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                    </svg>
                    <span>Décalée 1/2</span>
                </button>
                <button type="button" class="type-pose-btn-img" onclick="selectTypePose(this, 'Décalée 1/3')">
                    <svg viewBox="0 0 100 60" style="width:100%; height:60px;">
                        <rect x="5" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="35" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="65" y="5" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="13.3" y="20" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="43.3" y="20" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="73.3" y="20" width="20" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="21.6" y="35" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="51.6" y="35" width="25" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <rect x="81.6" y="35" width="12" height="10" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                    </svg>
                    <span>Décalée 1/3</span>
                </button>
                <button type="button" class="type-pose-btn-img" onclick="selectTypePose(this, 'Diagonale')">
                    <svg viewBox="0 0 100 60" style="width:100%; height:60px;">
                        <polygon points="5,5 18,5 35,22 22,22" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="22,5 35,5 52,22 39,22" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="39,5 52,5 69,22 56,22" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="5,22 18,22 35,39 22,39" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="22,22 35,22 52,39 39,39" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="39,22 52,22 69,39 56,39" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                    </svg>
                    <span>Diagonale</span>
                </button>
                <button type="button" class="type-pose-btn-img" onclick="selectTypePose(this, 'Chevron 45°')">
                    <svg viewBox="0 0 100 60" style="width:100%; height:60px;">
                        <polygon points="20,5 30,15 20,25 10,15" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="40,5 50,15 40,25 30,15" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="60,5 70,15 60,25 50,15" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="20,25 30,35 20,45 10,35" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="40,25 50,35 40,45 30,35" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="60,25 70,35 60,45 50,35" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                    </svg>
                    <span>Chevron 45°</span>
                </button>
                <button type="button" class="type-pose-btn-img" onclick="selectTypePose(this, 'Chevron 60°')">
                    <svg viewBox="0 0 100 60" style="width:100%; height:60px;">
                        <polygon points="20,8 30,20 22,32 12,20" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="38,8 48,20 40,32 30,20" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="56,8 66,20 58,32 48,20" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                        <polygon points="74,8 84,20 76,32 66,20" fill="#94a3b8" stroke="#64748b" stroke-width="1"/>
                    </svg>
                    <span>Chevron 60°</span>
                </button>
            </div>
            <input type="hidden" name="type_pose" id="type_pose">
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">Joint ciment Laticrete</label>
            <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
                <button type="button" class="color-preset-btn" onclick="setJointCiment('01 Bright White', '#FFFFFF')">
                    <span class="color-dot" style="background:#FFFFFF; border:1px solid #ccc;"></span>
                    <span>01 Bright White</span>
                </button>
                <button type="button" class="color-preset-btn" onclick="setJointCiment('89 Smoke Grey', '#7B7F84')">
                    <span class="color-dot" style="background:#7B7F84;"></span>
                    <span>89 Smoke Grey</span>
                </button>
                <button type="button" class="color-preset-btn" onclick="setJointCiment('60 Charcoal', '#54585A')">
                    <span class="color-dot" style="background:#54585A;"></span>
                    <span>60 Charcoal</span>
                </button>
                <button type="button" class="color-preset-btn" onclick="setJointCiment('09 Natural Grey', '#C0C0C0')">
                    <span class="color-dot" style="background:#C0C0C0;"></span>
                    <span>09 Natural Grey</span>
                </button>
            </div>
            <div class="color-input-group">
                <input type="text" name="joint_ciment" id="joint_ciment" class="form-input" placeholder="Ex: Laticrete 89 Smoke Grey">
                <input type="color" name="joint_ciment_color" id="joint_ciment_color" value="#7B7F84">
            </div>
        </div>

        <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">Joint silicone Laticrete</label>
            <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
                <button type="button" class="color-preset-btn" onclick="setJointSilicone('S10 White', '#FFFFFF')">
                    <span class="color-dot" style="background:#FFFFFF; border:1px solid #ccc;"></span>
                    <span>S10 White</span>
                </button>
                <button type="button" class="color-preset-btn" onclick="setJointSilicone('S89 Smoke Grey', '#7B7F84')">
                    <span class="color-dot" style="background:#7B7F84;"></span>
                    <span>S89 Smoke Grey</span>
                </button>
                <button type="button" class="color-preset-btn" onclick="setJointSilicone('S60 Charcoal', '#54585A')">
                    <span class="color-dot" style="background:#54585A;"></span>
                    <span>S60 Charcoal</span>
                </button>
                <button type="button" class="color-preset-btn" onclick="setJointSilicone('CLEAR Transparent', 'transparent')">
                    <span class="color-dot" style="background:repeating-linear-gradient(45deg, #f0f0f0, #f0f0f0 5px, #fff 5px, #fff 10px); border:1px solid #ccc;"></span>
                    <span>CLEAR Transparent</span>
                </button>
            </div>
            <div class="color-input-group">
                <input type="text" name="joint_silicone" id="joint_silicone" class="form-input" placeholder="Ex: S10 White">
                <input type="color" name="joint_silicone_color" id="joint_silicone_color" value="#FFFFFF">
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Plinthes</label>
                <select name="plinthes" class="form-select">
                    <option value="Non">Non</option>
                    <option value="Origine">Origine (carrelage)</option>
                    <option value="Coupées">Coupées sur mesure</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Hauteur plinthes (mm)</label>
                <input type="text" name="plinthes_hauteur" class="form-input" placeholder="Ex: 80">
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Profilés</label>
                <select name="profil" class="form-select">
                    <option value="">Aucun</option>
                    <option value="Angle">Angle</option>
                    <option value="Nez de marche">Nez de marche</option>
                    <option value="Joint de dilatation">Joint de dilatation</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Finition profilés</label>
                <input type="text" name="profil_finition" class="form-input" placeholder="Ex: Noir mat 10 mm">
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Crédence</label>
                <select name="credence" class="form-select">
                    <option value="Non">Non</option>
                    <option value="Oui">Oui</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Hauteur crédence (cm)</label>
                <input type="text" name="credence_hauteur" class="form-input" placeholder="Ex: 60">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Jusqu'au plafond (SDB/WC)</label>
            <select name="jusqu_au_plafond" class="form-select">
                <option value="Non">Non</option>
                <option value="Oui">Oui</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">📸 Photo de référence (optionnel)</label>
            <button type="button" class="btn btn-secondary" onclick="openPhotoGallery()">
                🖼️ Choisir une photo du devis/client
            </button>
            <div id="selected-photo-preview" style="display:none; margin-top:12px; padding:12px; background:#f8fafc; border-radius:8px; border:2px solid #0891b2;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <img id="preview-img" src="" style="width:80px; height:80px; object-fit:cover; border-radius:6px;">
                    <div style="flex:1;">
                        <div style="font-weight:600; color:#0f172a;" id="preview-filename"></div>
                        <div style="font-size:13px; color:#64748b;" id="preview-ref"></div>
                    </div>
                    <button type="button" onclick="clearPhoto()" style="background:#ef4444; color:white; border:none; padding:8px 12px; border-radius:6px; cursor:pointer;">
                        ✕ Supprimer
                    </button>
                </div>
            </div>
            <input type="hidden" name="photo_reference" id="photo_reference">
            <input type="hidden" name="photo_url" id="photo_url">
            <input type="hidden" name="photo_filename" id="photo_filename">
            <input type="hidden" name="fk_product" id="fk_product">
        </div>

        <input type="hidden" name="piece_text" id="piece_text_hidden">

        <div class="form-group">
            <label class="form-label">Remarques</label>
            <textarea name="remarques" class="form-textarea" placeholder="Notes spécifiques pour cette pièce..."></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                ➕ Ajouter cette pièce
            </button>
        </div>
    </form>

    <div id="produit-gallery-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; overflow:auto;">
        <div style="max-width:1200px; margin:40px auto; background:white; border-radius:12px; padding:24px; position:relative;">
            <button onclick="closeProduitGallery()" style="position:absolute; top:16px; right:16px; background:#ef4444; color:white; border:none; padding:10px 16px; border-radius:6px; cursor:pointer; font-weight:600;">
                ✕ Fermer
            </button>

            <h2 style="margin:0 0 20px 0; color:#0891b2; font-size:24px;">
                📦 Sélectionner un produit du devis
            </h2>

            <div id="produits-loading" style="text-align:center; padding:40px;">
                <div style="font-size:18px; color:#64748b;">
                    🔄 Chargement des produits...
                </div>
            </div>

            <div id="produits-grid" style="display:none; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px; margin-top:20px;">
            </div>

            <div id="produits-empty" style="display:none; text-align:center; padding:40px; color:#64748b;">
                <div style="font-size:48px; margin-bottom:12px;">📦</div>
                <div style="font-size:18px; font-weight:600; margin-bottom:8px;">Aucun produit trouvé</div>
                <div style="font-size:14px;">
                    Aucun produit trouvé dans les devis de ce client/projet.<br>
                    Assurez-vous d'avoir sélectionné un client ou projet dans le formulaire.
                </div>
            </div>
        </div>
    </div>

    <div id="photo-gallery-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; overflow:auto;">
        <div style="max-width:1200px; margin:40px auto; background:white; border-radius:12px; padding:24px; position:relative;">
            <button onclick="closePhotoGallery()" style="position:absolute; top:16px; right:16px; background:#ef4444; color:white; border:none; padding:10px 16px; border-radius:6px; cursor:pointer; font-weight:600;">
                ✕ Fermer
            </button>

            <h2 style="margin:0 0 20px 0; color:#0891b2; font-size:24px;">
                📸 Sélectionner une photo du client
            </h2>

            <div id="photos-loading" style="text-align:center; padding:40px;">
                <div style="font-size:18px; color:#64748b;">
                    🔄 Chargement des photos...
                </div>
            </div>

            <div id="photos-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:16px; margin-top:20px;">
            </div>

            <div id="photos-empty" style="display:none; text-align:center; padding:40px; color:#64748b;">
                <div style="font-size:48px; margin-bottom:12px;">📷</div>
                <div style="font-size:18px; font-weight:600; margin-bottom:8px;">Aucune photo trouvée</div>
                <div style="font-size:14px;">
                    Aucune photo n'a été trouvée dans les devis ou commandes de ce client/projet.<br>
                    Assurez-vous d'avoir sélectionné un client ou projet dans le formulaire.
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:space-between">
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            ← Retour à la fiche
        </a>
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-primary">
            Terminer et voir la fiche →
        </a>
    </div>
</div>

<script>
let selectedPieces = [];

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const detectedPieces = urlParams.get('detected_pieces');

    console.log('=== DEBUG MODE ÉTAPE 4 ===');
    console.log('URL complète:', window.location.href);
    console.log('Paramètre detected_pieces brut:', detectedPieces);

    if (detectedPieces) {
        const pieces = detectedPieces.split(',').map(p => p.trim()).filter(p => p.length > 0);
        console.log('Pièces détectées après split:', pieces);

        const buttonMapping = {
            'SOL': null,
            'MURS': null,
            'MUR': null,
            'MURS SDD': null,
            'CRÉDANCE': null,
            'CREDANCE': null,
            'Cuisine': 'Cuisine',
            'Salle de bain': 'Salle de bain',
            'WC': 'WC',
            'Séjour': 'Séjour',
            'Entrée': 'Entrée',
            'Couloir': 'Couloir',
            'Chambre': 'Chambre',
            'Bureau': 'Bureau',
            'Terrasse': 'Terrasse',
            'Garage': 'Garage'
        };

        console.log('Mapping des boutons:', buttonMapping);

        pieces.forEach(piece => {
            console.log(`Traitement de la pièce: "${piece}"`);
            const btnName = buttonMapping[piece];
            console.log(`  -> Nom du bouton mappé: "${btnName}"`);

            if (btnName) {
                const buttons = document.querySelectorAll('.piece-btn');
                console.log(`  -> Nombre de boutons trouvés: ${buttons.length}`);

                buttons.forEach(btn => {
                    console.log(`    -> Texte du bouton: "${btn.textContent}"`);
                    if (btn.textContent.includes(btnName)) {
                        console.log(`    -> MATCH! Activation du bouton "${btnName}"`);
                        btn.classList.add('active');
                        if (!selectedPieces.includes(btnName)) {
                            selectedPieces.push(btnName);
                        }
                    }
                });
            } else {
                console.log(`  -> Pas de mapping pour "${piece}" (probablement SOL, MURS, etc.)`);
            }
        });

        console.log('Pièces sélectionnées finales:', selectedPieces);
        updateSelectedPiecesDisplay();
    } else {
        console.log('Aucune pièce détectée dans l\'URL');
    }
});

function togglePiece(btn, nom) {
    if (btn.classList.contains('active')) {
        btn.classList.remove('active');
        selectedPieces = selectedPieces.filter(p => p !== nom);
    } else {
        btn.classList.add('active');
        selectedPieces.push(nom);
    }
    updateSelectedPiecesDisplay();
}

function updateSelectedPiecesDisplay() {
    const count = selectedPieces.length;
    document.getElementById('selected-count').textContent = count;

    if (count === 0) {
        document.getElementById('selected-pieces-display').textContent = 'Aucune pièce sélectionnée';
        document.getElementById('input-noms').value = '';
    } else {
        document.getElementById('selected-pieces-display').innerHTML =
            selectedPieces.map(p => `<span style="display:inline-block; background:#fef3c7; padding:4px 10px; border-radius:6px; margin:2px; font-weight:600; color:#854d0e;">${p}</span>`).join(' ');
        document.getElementById('input-noms').value = selectedPieces.join(',');
    }
}

function selectTypePose(btn, value) {
    document.querySelectorAll('.type-pose-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.type-pose-btn-img').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('type_pose').value = value;
}

function openPhotoGallery() {
    const modal = document.getElementById('photo-gallery-modal');
    const loading = document.getElementById('photos-loading');
    const grid = document.getElementById('photos-grid');
    const empty = document.getElementById('photos-empty');

    modal.style.display = 'block';
    loading.style.display = 'block';
    grid.innerHTML = '';
    grid.style.display = 'none';
    empty.style.display = 'none';

    const fk_client = <?php echo $fiche->fk_client ? (int)$fiche->fk_client : 0; ?>;
    const fk_projet = <?php echo $fiche->fk_projet ? (int)$fiche->fk_projet : 0; ?>;

    if (!fk_client && !fk_projet) {
        loading.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    fetch('api_get_photos.php?fk_client=' + fk_client + '&fk_projet=' + fk_projet)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';

            if (data.success && data.photos && data.photos.length > 0) {
                grid.style.display = 'grid';

                data.photos.forEach(photo => {
                    const card = document.createElement('div');
                    card.style.cssText = 'border:2px solid #e2e8f0; border-radius:8px; overflow:hidden; cursor:pointer; transition:all 0.2s;';
                    card.onmouseover = function() { this.style.borderColor = '#0891b2'; this.style.transform = 'scale(1.02)'; };
                    card.onmouseout = function() { this.style.borderColor = '#e2e8f0'; this.style.transform = 'scale(1)'; };
                    card.onclick = function() { selectPhoto(photo); };

                    const ref = photo.propal_ref || photo.commande_ref || 'Document';

                    let photoUrl = photo.url;
                    if (!photoUrl.match(/^https?:\/\//)) {
                        photoUrl = '<?php echo DOL_URL_ROOT; ?>' + photoUrl;
                    }

                    card.innerHTML = `
                        <img src="${photoUrl}" style="width:100%; height:180px; object-fit:cover;" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27200%27 height=%27180%27%3E%3Crect fill=%27%23e2e8f0%27 width=%27200%27 height=%27180%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 text-anchor=%27middle%27 dy=%27.3em%27 fill=%27%2364748b%27 font-size=%2716%27%3E📷%3C/text%3E%3C/svg%3E';">
                        <div style="padding:12px;">
                            <div style="font-size:13px; font-weight:600; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${photo.filename}</div>
                            <div style="font-size:12px; color:#64748b; margin-top:4px;">${ref}</div>
                        </div>
                    `;

                    grid.appendChild(card);
                });
            } else {
                empty.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            loading.style.display = 'none';
            empty.style.display = 'block';
        });
}

function closePhotoGallery() {
    document.getElementById('photo-gallery-modal').style.display = 'none';
}

function selectPhoto(photo) {
    const ref = photo.propal_ref || photo.commande_ref || 'Document';

    document.getElementById('photo_reference').value = ref;
    document.getElementById('photo_url').value = photo.url;
    document.getElementById('photo_filename').value = photo.filename;

    let photoUrl = photo.url;
    if (!photoUrl.match(/^https?:\/\//)) {
        photoUrl = '<?php echo DOL_URL_ROOT; ?>' + photoUrl;
    }

    document.getElementById('preview-img').src = photoUrl;
    document.getElementById('preview-filename').textContent = photo.filename;
    document.getElementById('preview-ref').textContent = 'Source: ' + ref;
    document.getElementById('selected-photo-preview').style.display = 'block';

    closePhotoGallery();
}

function clearPhoto() {
    document.getElementById('photo_reference').value = '';
    document.getElementById('photo_url').value = '';
    document.getElementById('photo_filename').value = '';
    document.getElementById('fk_product').value = '';
    document.getElementById('selected-photo-preview').style.display = 'none';
}

function openProduitGallery() {
    const modal = document.getElementById('produit-gallery-modal');
    const loading = document.getElementById('produits-loading');
    const grid = document.getElementById('produits-grid');
    const empty = document.getElementById('produits-empty');

    modal.style.display = 'block';
    loading.style.display = 'block';
    grid.innerHTML = '';
    grid.style.display = 'none';
    empty.style.display = 'none';

    const ficheId = <?php echo (int)$id; ?>;

    fetch('api_get_produits_devis.php?fk_sens_pose=' + ficheId)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';

            if (data.success && data.produits && data.produits.length > 0) {
                grid.style.display = 'grid';

                data.produits.forEach(produit => {
                    const card = document.createElement('div');
                    card.style.cssText = 'border:2px solid #e2e8f0; border-radius:8px; overflow:hidden; cursor:pointer; transition:all 0.2s; background:white;';
                    card.onmouseover = function() { this.style.borderColor = '#0891b2'; this.style.transform = 'translateY(-2px)'; this.style.boxShadow = '0 4px 12px rgba(8,145,178,0.2)'; };
                    card.onmouseout = function() { this.style.borderColor = '#e2e8f0'; this.style.transform = 'translateY(0)'; this.style.boxShadow = 'none'; };
                    card.onclick = function() { selectProduit(produit); };

                    let photoHtml = '';
                    if (produit.photo_url) {
                        let photoUrl = produit.photo_url;
                        if (!photoUrl.match(/^https?:\/\//)) {
                            photoUrl = '<?php echo DOL_URL_ROOT; ?>' + photoUrl;
                        }
                        photoHtml = `<img src="${photoUrl}" style="width:100%; height:160px; object-fit:cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="display:none; width:100%; height:160px; background:#f1f5f9; align-items:center; justify-content:center; font-size:48px;">📦</div>`;
                    } else {
                        photoHtml = `<div style="width:100%; height:160px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:48px;">📦</div>`;
                    }

                    card.innerHTML = `
                        ${photoHtml}
                        <div style="padding:12px;">
                            <div style="font-size:14px; font-weight:600; color:#0f172a; margin-bottom:6px;">${escapeHtml(produit.label)}</div>
                            <div style="font-size:12px; color:#64748b; margin-bottom:4px;">📦 Réf: ${produit.reference}</div>
                            ${produit.format ? '<div style="font-size:12px; color:#0891b2; font-weight:600; margin-bottom:4px;">📏 ' + produit.format + '</div>' : ''}
                            ${produit.quantite ? '<div style="font-size:13px; color:#f97316; font-weight:700; margin-bottom:4px;">🎯 ' + produit.quantite + '</div>' : ''}
                            <div style="font-size:11px; color:#94a3b8; margin-top:6px;">Devis: ${produit.devis_ref}</div>
                            ${produit.has_photo ? '<div style="font-size:11px; color:#10b981; margin-top:4px;">✅ Photo disponible</div>' : '<div style="font-size:11px; color:#f59e0b; margin-top:4px;">⚠️ Sans photo</div>'}
                        </div>
                    `;

                    grid.appendChild(card);
                });
            } else {
                empty.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            loading.style.display = 'none';
            empty.style.display = 'block';
        });
}

function closeProduitGallery() {
    document.getElementById('produit-gallery-modal').style.display = 'none';
}

function selectProduit(produit) {
    document.getElementById('input-format').value = produit.format || '';

    if (produit.photo_url) {
        document.getElementById('photo_reference').value = produit.devis_ref;
        document.getElementById('photo_url').value = produit.photo_url;
        document.getElementById('photo_filename').value = produit.photo_filename;
        document.getElementById('fk_product').value = produit.fk_product || '';

        let photoUrl = produit.photo_url;
        if (!photoUrl.match(/^https?:\/\//)) {
            photoUrl = '<?php echo DOL_URL_ROOT; ?>' + photoUrl;
        }

        document.getElementById('preview-produit-img').src = photoUrl;
        document.getElementById('preview-produit-label').textContent = produit.label;
        document.getElementById('preview-produit-ref').textContent = 'Réf: ' + produit.reference;
        document.getElementById('preview-produit-format').textContent = produit.format ? '📏 ' + produit.format : '';
        document.getElementById('selected-produit-preview').style.display = 'block';

        document.getElementById('preview-img').src = photoUrl;
        document.getElementById('preview-filename').textContent = produit.photo_filename;
        document.getElementById('preview-ref').textContent = 'Source: ' + produit.devis_ref;
        document.getElementById('selected-photo-preview').style.display = 'block';
    }

    closeProduitGallery();
}

function clearProduit() {
    document.getElementById('selected-produit-preview').style.display = 'none';
    document.getElementById('input-format').value = '';
    clearPhoto();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function editPiece(pieceId) {
    const piece = piecesData[pieceId];
    if (!piece) {
        console.error('❌ Pièce introuvable:', pieceId);
        console.log('piecesData:', piecesData);
        return;
    }

    console.log('✅ Édition de la pièce:', piece);

    document.getElementById('form-title').textContent = '✏️ Modifier la pièce';
    document.getElementById('piece-form').action = '?id=<?php echo $id; ?>&action=update_piece';
    document.getElementById('edit-piece-id').value = pieceId;

    document.querySelector('[name="noms"]').value = piece.nom;
    document.querySelector('[name="type_pose"]').value = piece.type_pose || '';
    document.querySelector('[name="format"]').value = piece.format || '';
    document.querySelector('[name="epaisseur"]').value = piece.epaisseur || '';
    document.querySelector('[name="joint_ciment"]').value = piece.joint_ciment || '';
    document.querySelector('[name="joint_ciment_color"]').value = piece.joint_ciment_color || '';
    document.querySelector('[name="joint_silicone"]').value = piece.joint_silicone || '';
    document.querySelector('[name="joint_silicone_color"]').value = piece.joint_silicone_color || '';
    document.querySelector('[name="plinthes"]').value = piece.plinthes || '';
    document.querySelector('[name="plinthes_hauteur"]').value = piece.plinthes_hauteur || '';
    document.querySelector('[name="profil"]').value = piece.profil || '';
    document.querySelector('[name="profil_finition"]').value = piece.profil_finition || '';
    document.querySelector('[name="credence"]').value = piece.credence || '';
    document.querySelector('[name="credence_hauteur"]').value = piece.credence_hauteur || '';
    document.querySelector('[name="jusqu_au_plafond"]').value = piece.jusqu_au_plafond || '';
    document.querySelector('[name="piece_text"]').value = piece.piece_text || '';
    document.querySelector('[name="remarques"]').value = piece.remarques || '';

    if (piece.photo_url) {
        document.getElementById('photo_reference').value = piece.photo_reference || '';
        document.getElementById('photo_url').value = piece.photo_url;
        document.getElementById('photo_filename').value = piece.photo_filename || '';
        document.getElementById('fk_product').value = piece.fk_product || '';

        let photoUrl = piece.photo_url;
        if (!photoUrl.match(/^https?:\/\//)) {
            photoUrl = '<?php echo DOL_URL_ROOT; ?>' + photoUrl;
        }

        document.getElementById('preview-img').src = photoUrl;
        document.getElementById('preview-filename').textContent = piece.photo_filename || '';
        document.getElementById('preview-ref').textContent = 'Source: ' + (piece.photo_reference || '');
        document.getElementById('selected-photo-preview').style.display = 'block';
    }

    document.querySelectorAll('.type-pose-btn, .type-pose-btn-img').forEach(btn => {
        btn.classList.remove('active');
        const btnText = btn.textContent.trim() || btn.querySelector('span')?.textContent.trim();
        if (btnText === piece.type_pose) {
            btn.classList.add('active');
        }
    });

    document.getElementById('piece-form').scrollIntoView({ behavior: 'smooth' });

    const submitBtn = document.querySelector('#piece-form button[type="submit"]');
    if (submitBtn) {
        submitBtn.textContent = '💾 Mettre à jour';

        if (!document.getElementById('cancel-edit-btn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.id = 'cancel-edit-btn';
            cancelBtn.textContent = '❌ Annuler';
            cancelBtn.style.cssText = 'background:#64748b; color:white; border:none; padding:12px 24px; border-radius:8px; cursor:pointer; font-size:16px; font-weight:600; margin-left:12px;';
            cancelBtn.onclick = cancelEdit;
            submitBtn.parentNode.appendChild(cancelBtn);
        }
    }
}

function cancelEdit() {
    document.getElementById('form-title').textContent = '➕ Ajouter une pièce';
    document.getElementById('piece-form').action = '?id=<?php echo $id; ?>&action=add_piece';
    document.getElementById('edit-piece-id').value = '';
    document.getElementById('piece-form').reset();
    clearPhoto();

    const submitBtn = document.querySelector('#piece-form button[type="submit"]');
    if (submitBtn) {
        submitBtn.textContent = '➕ Ajouter la/les pièce(s)';
    }

    const cancelBtn = document.getElementById('cancel-edit-btn');
    if (cancelBtn) {
        cancelBtn.remove();
    }

    selectedPieces = [];
    updateSelectedPiecesDisplay();
}

function setJointCiment(nom, color) {
    const inputName = document.querySelector('[name="joint_ciment"]') || document.getElementById('joint_ciment');
    const inputColor = document.querySelector('[name="joint_ciment_color"]') || document.getElementById('joint_ciment_color');

    if (inputName) {
        inputName.value = 'Laticrete ' + nom;
    }
    if (inputColor && color !== 'transparent') {
        inputColor.value = color;
    }
}

function setJointSilicone(nom, color) {
    const inputName = document.querySelector('[name="joint_silicone"]') || document.getElementById('joint_silicone');
    const inputColor = document.querySelector('[name="joint_silicone_color"]') || document.getElementById('joint_silicone_color');

    if (inputName) {
        inputName.value = 'Laticrete ' + nom;
    }
    if (inputColor && color !== 'transparent') {
        inputColor.value = color;
    }
}

function editPieceText(pieceId) {
    document.getElementById('piece-text-display-' + pieceId).style.display = 'none';
    document.getElementById('piece-text-edit-' + pieceId).style.display = 'block';
}

function cancelEditPieceText(pieceId) {
    document.getElementById('piece-text-display-' + pieceId).style.display = 'block';
    document.getElementById('piece-text-edit-' + pieceId).style.display = 'none';
}

async function savePieceText(pieceId) {
    const textarea = document.getElementById('piece-text-input-' + pieceId);
    const newText = textarea.value;

    try {
        const formData = new FormData();
        formData.append('action', 'update_piece_text');
        formData.append('piece_id', pieceId);
        formData.append('piece_text', newText);
        formData.append('id', <?php echo $id; ?>);
        formData.append('token', '<?php echo newToken(); ?>');

        const response = await fetch('?id=<?php echo $id; ?>', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            const displayDiv = document.getElementById('piece-text-display-' + pieceId);
            displayDiv.innerHTML = newText.replace(/\n/g, '<br>');
            cancelEditPieceText(pieceId);

            const successMsg = document.createElement('div');
            successMsg.style.cssText = 'position:fixed; top:20px; right:20px; background:#10b981; color:white; padding:12px 20px; border-radius:8px; z-index:9999; font-weight:600;';
            successMsg.textContent = '✓ Texte mis à jour avec succès';
            document.body.appendChild(successMsg);
            setTimeout(() => successMsg.remove(), 3000);
        } else {
            throw new Error(result.error || 'Erreur lors de la mise à jour');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour du texte: ' + error.message);
    }
}

</script>

<?php
llxFooter();
$db->close();
?>
