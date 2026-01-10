<?php
/**
 * Visualisation fiche sens de pose - Mobile
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$id = GETPOST('id', 'int');

if (!$id) {
    header('Location: list.php');
    exit;
}

$sql = "SELECT sp.*, u.firstname, u.lastname,
               s.nom as client_societe, s.phone as client_phone, s.email as client_email,
               p.ref as projet_ref,
               pe.proprietaire, pe.etage, pe.appartement
        FROM ".MAIN_DB_PREFIX."mv3_sens_pose sp
        LEFT JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = sp.fk_user_create
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = sp.fk_client
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = sp.fk_projet
        LEFT JOIN ".MAIN_DB_PREFIX."propal pr ON pr.fk_projet = sp.fk_projet AND pr.fk_soc = sp.fk_client
        LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields pe ON pe.fk_object = pr.rowid
        WHERE sp.rowid = ".(int)$id."
        ORDER BY pr.datec DESC
        LIMIT 1";

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) == 0) {
    header('Location: list.php');
    exit;
}

$fiche = $db->fetch_object($resql);

// Function to get product image - ACCESSIBLE À TOUS sans restrictions
function getProductImage($product_ref, $fk_product = null) {
    global $conf, $db;

    if (empty($product_ref) && empty($fk_product)) return null;

    // If we only have fk_product, get the ref
    if (empty($product_ref) && !empty($fk_product)) {
        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
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
            // DOUBLE URL: viewimage.php de Dolibarr + proxy de secours
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

$statut_icon = '📝';
$statut_class = 'warning';
$statut_text = 'Brouillon';

if ($fiche->statut == 'envoye') {
    $statut_icon = '📧';
    $statut_class = 'info';
    $statut_text = 'Envoyé';
} elseif ($fiche->statut == 'signe') {
    $statut_icon = '✍️';
    $statut_class = 'success';
    $statut_text = 'Signé';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title><?php echo dol_escape_htmltag($fiche->ref); ?> - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
    .info-grid {
        display: grid;
        gap: 12px;
    }
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .info-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-light);
        letter-spacing: 0.5px;
    }
    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
    }
    .piece-card {
        background: white;
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
    }
    .piece-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
    }
    .piece-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
    }
    .piece-reference {
        font-size: 12px;
        color: var(--text-light);
        background: var(--bg-light);
        padding: 4px 8px;
        border-radius: 6px;
    }
    .piece-details {
        display: grid;
        gap: 8px;
    }
    .piece-detail {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }
    .piece-detail strong {
        min-width: 100px;
        color: var(--text-light);
    }
    .orientation-box {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: var(--bg-light);
        border-radius: 8px;
        margin-top: 8px;
    }
    .orientation-arrow {
        font-size: 24px;
    }
    .signature-box {
        background: var(--bg-light);
        border: 2px dashed var(--border-color);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .signature-img {
        max-width: 100%;
        height: auto;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        background: white;
    }
    .photo-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-top: 12px;
    }
    .photo-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid var(--border-color);
        cursor: pointer;
    }
    .photo-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .photo-label {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 8px;
        font-size: 11px;
        font-weight: 600;
    }
    </style>
</head>
<body>
    <div class="app-header">
        <a href="list.php" style="color: white; text-decoration: none; font-size: 20px;">←</a>
        <div>
            <div class="app-header-title"><?php echo dol_escape_htmltag($fiche->ref); ?></div>
            <div class="app-header-subtitle">Fiche sens de pose</div>
        </div>
        <div>
            <span class="card-badge badge-<?php echo $statut_class; ?>" style="font-size: 11px;">
                <?php echo $statut_icon.' '.$statut_text; ?>
            </span>
        </div>
    </div>

    <div class="app-container" style="padding-bottom: 80px;">
        <?php if (GETPOST('updated', 'int')): ?>
        <div class="card" style="background: #d1fae5; border-left: 4px solid #10b981; padding: 12px; margin-bottom: 16px;">
            <div style="font-size: 14px; color: #065f46; font-weight: 600;">
                ✅ Fiche mise à jour avec succès !
            </div>
        </div>
        <?php endif; ?>

        <?php if (GETPOST('signed', 'int')): ?>
        <div class="card" style="background: #d1fae5; border-left: 4px solid #10b981; padding: 12px; margin-bottom: 16px;">
            <div style="font-size: 14px; color: #065f46; font-weight: 600;">
                ✅ Signature enregistrée avec succès !
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-title">📋 Informations Client</div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="info-item" style="background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); padding: 16px; border-radius: 12px; color: white;">
                    <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600;">👤 Client</div>
                    <div style="font-size: 16px; font-weight: 700; line-height: 1.4;"><?php echo htmlspecialchars($fiche->client_name, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <?php if ($fiche->site_address): ?>
                <div class="info-item" style="border-left: 4px solid #0891b2; padding-left: 12px;">
                    <div class="info-label" style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <span style="font-size: 14px;">📍</span>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Adresse du client</span>
                    </div>
                    <div class="info-value" style="font-size: 14px; color: #1e293b; font-weight: 500; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($fiche->site_address, ENT_QUOTES, 'UTF-8')); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($fiche->proprietaire): ?>
                <div class="info-item" style="border-left: 4px solid #10b981; padding-left: 12px;">
                    <div class="info-label" style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <span style="font-size: 14px;">🏠</span>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Propriétaire</span>
                    </div>
                    <div class="info-value" style="font-size: 14px; color: #1e293b; font-weight: 500;"><?php echo htmlspecialchars($fiche->proprietaire, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($fiche->etage || $fiche->appartement): ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <?php if ($fiche->etage): ?>
                    <div class="info-item" style="background: #f1f5f9; padding: 12px; border-radius: 10px;">
                        <div class="info-label" style="display: flex; align-items: center; gap: 4px; margin-bottom: 6px;">
                            <span style="font-size: 13px;">🔢</span>
                            <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Étage</span>
                        </div>
                        <div class="info-value" style="font-size: 15px; color: #1e293b; font-weight: 600;"><?php echo htmlspecialchars($fiche->etage, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($fiche->appartement): ?>
                    <div class="info-item" style="background: #f1f5f9; padding: 12px; border-radius: 10px;">
                        <div class="info-label" style="display: flex; align-items: center; gap: 4px; margin-bottom: 6px;">
                            <span style="font-size: 13px;">🚪</span>
                            <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Appt/Villa</span>
                        </div>
                        <div class="info-value" style="font-size: 15px; color: #1e293b; font-weight: 600;"><?php echo htmlspecialchars($fiche->appartement, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php
                $phone = $fiche->telephone ?: $fiche->client_phone;
                if ($phone):
                ?>
                <div class="info-item" style="border-left: 4px solid #0891b2; padding-left: 12px;">
                    <div class="info-label" style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <span style="font-size: 14px;">📞</span>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Téléphone</span>
                    </div>
                    <div class="info-value">
                        <a href="tel:<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" style="color: #0891b2; font-size: 16px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 8px 0;">
                            <span style="font-size: 18px;">📱</span>
                            <span><?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                $email = $fiche->email ?: $fiche->client_email;
                if ($email):
                ?>
                <div class="info-item" style="border-left: 4px solid #10b981; padding-left: 12px;">
                    <div class="info-label" style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                        <span style="font-size: 14px;">📧</span>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Email</span>
                    </div>
                    <div class="info-value">
                        <a href="mailto:<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" style="color: #10b981; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; padding: 8px 0; word-break: break-all;">
                            <span style="font-size: 18px;">✉️</span>
                            <span><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 8px;">
                    <div class="info-item" style="background: #fef3c7; padding: 12px; border-radius: 10px; border: 1px solid #fcd34d;">
                        <div class="info-label" style="display: flex; align-items: center; gap: 4px; margin-bottom: 6px;">
                            <span style="font-size: 13px;">📅</span>
                            <span style="font-size: 10px; font-weight: 700; color: #92400e; text-transform: uppercase;">Création</span>
                        </div>
                        <div class="info-value" style="font-size: 12px; color: #78350f; font-weight: 600; line-height: 1.3;"><?php echo dol_print_date($db->jdate($fiche->date_creation), 'dayhour'); ?></div>
                    </div>

                    <?php if ($fiche->signature_date): ?>
                    <div class="info-item" style="background: #d1fae5; padding: 12px; border-radius: 10px; border: 1px solid #6ee7b7;">
                        <div class="info-label" style="display: flex; align-items: center; gap: 4px; margin-bottom: 6px;">
                            <span style="font-size: 13px;">✅</span>
                            <span style="font-size: 10px; font-weight: 700; color: #065f46; text-transform: uppercase;">Signée</span>
                        </div>
                        <div class="info-value" style="font-size: 12px; color: #047857; font-weight: 600; line-height: 1.3;"><?php echo dol_print_date($db->jdate($fiche->signature_date), 'dayhour'); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($fiche->notes): ?>
        <div class="card">
            <div class="card-header">
                <div class="card-title">📝 Notes</div>
            </div>
            <div style="color: var(--text-primary); line-height: 1.6;">
                <?php echo nl2br(dol_escape_htmltag($fiche->notes)); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="card-title">🔲 Pièces et Carrelages</div>
                <?php if ($fiche->statut != 'signe'): ?>
                <a href="add_products.php?id=<?php echo $id; ?>" style="background: #0891b2; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600;">
                    ➕ Ajouter
                </a>
                <?php endif; ?>
            </div>

            <?php
            if ($resql_pieces && $db->num_rows($resql_pieces) > 0) {
                while ($piece = $db->fetch_object($resql_pieces)) {
                    echo '<div class="piece-card">';

                    // Display piece name (nom from DB)
                    if (!empty($piece->nom)) {
                        echo '<div style="font-size: 18px; font-weight: 700; color: #0891b2; margin-bottom: 12px;">';
                        echo dol_escape_htmltag($piece->nom);
                        echo '</div>';
                    }

                    // Display piece_text if exists (details/description)
                    if (!empty($piece->piece_text)) {
                        echo '<div style="font-size: 13px; color: #475569; margin-bottom: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; line-height: 1.7;">';
                        echo nl2br(dol_escape_htmltag($piece->piece_text));
                        echo '</div>';
                    }

                    echo '<div class="piece-details">';

                    // Extract quantity from piece_text if not in separate column
                    $quantite_display = '';
                    if (!empty($piece->quantite)) {
                        $quantite_display = $piece->quantite;
                    } elseif (!empty($piece->piece_text)) {
                        // Debug: log piece_text to understand format
                        error_log("DEBUG piece_text for piece #{$piece->rowid}: " . $piece->piece_text);

                        // Try multiple patterns to extract quantity
                        if (preg_match('/Quantité[:\s]+([0-9.,]+)\s*m[²2]/iu', $piece->piece_text, $matches)) {
                            $quantite_display = $matches[1] . ' m²';
                            error_log("DEBUG: Extracted quantity (pattern 1): " . $quantite_display);
                        } elseif (preg_match('/([0-9.,]+)\s*m[²2]/i', $piece->piece_text, $matches)) {
                            $quantite_display = $matches[1] . ' m²';
                            error_log("DEBUG: Extracted quantity (pattern 2): " . $quantite_display);
                        } else {
                            error_log("DEBUG: No quantity pattern matched");
                        }
                    }

                    // Display product reference and quantity on same row
                    if ($piece->product_ref || !empty($quantite_display)) {
                        echo '<div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 8px;">';

                        if ($piece->product_ref) {
                            echo '<div style="flex: 1; min-width: 150px; background: #fff7ed; padding: 8px 12px; border-radius: 8px; border-left: 3px solid #f97316;">';
                            echo '<div style="font-size: 11px; color: #ea580c; font-weight: 600; margin-bottom: 2px;">RÉFÉRENCE</div>';
                            echo '<div style="font-weight: 700; color: #9a3412;">'.dol_escape_htmltag($piece->product_ref).'</div>';
                            echo '</div>';
                        }

                        if (!empty($quantite_display)) {
                            echo '<div style="flex: 1; min-width: 100px; background: #f0f9ff; padding: 8px 12px; border-radius: 8px; border-left: 3px solid #0891b2;">';
                            echo '<div style="font-size: 11px; color: #0891b2; font-weight: 600; margin-bottom: 2px;">QUANTITÉ</div>';
                            echo '<div style="font-size: 15px; font-weight: 700; color: #0c4a6e;">'.dol_escape_htmltag($quantite_display).'</div>';
                            echo '</div>';
                        }

                        echo '</div>';
                    }

                    // Display product label if linked to a product
                    if ($piece->product_label) {
                        echo '<div class="piece-detail">';
                        echo '<strong>Produit:</strong> '.dol_escape_htmltag($piece->product_label);
                        echo '</div>';
                    }

                    if (!empty($piece->format)) {
                        echo '<div class="piece-detail">';
                        echo '<strong>📏 Format:</strong> '.dol_escape_htmltag($piece->format);
                        echo '</div>';
                    }

                    if (!empty($piece->type_pose)) {
                        echo '<div class="piece-detail">';
                        echo '<strong>🔨 Type de pose:</strong> '.dol_escape_htmltag($piece->type_pose);
                        echo '</div>';
                    }

                    if (!empty($piece->sens)) {
                        echo '<div class="piece-detail">';
                        echo '<strong>↔️ Sens:</strong> '.dol_escape_htmltag($piece->sens);
                        echo '</div>';
                    }

                    if (!empty($piece->epaisseur)) {
                        echo '<div class="piece-detail">';
                        echo '<strong>📐 Épaisseur:</strong> '.dol_escape_htmltag($piece->epaisseur);
                        echo '</div>';
                    }

                    // Joint ciment
                    if (!empty($piece->joint_ciment)) {
                        echo '<div class="piece-detail">';
                        echo '<strong>🔲 Joint ciment:</strong> '.dol_escape_htmltag($piece->joint_ciment);
                        if (!empty($piece->joint_ciment_color)) {
                            echo ' <span style="display: inline-block; width: 16px; height: 16px; background: '.dol_escape_htmltag($piece->joint_ciment_color).'; border: 1px solid #ccc; border-radius: 3px; vertical-align: middle;"></span>';
                        }
                        echo '</div>';
                    }

                    // Joint silicone
                    if (!empty($piece->joint_silicone)) {
                        echo '<div class="piece-detail">';
                        echo '<strong>🔳 Joint silicone:</strong> '.dol_escape_htmltag($piece->joint_silicone);
                        if (!empty($piece->joint_silicone_color)) {
                            echo ' <span style="display: inline-block; width: 16px; height: 16px; background: '.dol_escape_htmltag($piece->joint_silicone_color).'; border: 1px solid #ccc; border-radius: 3px; vertical-align: middle;"></span>';
                        }
                        echo '</div>';
                    }

                    // Plinthes
                    if (!empty($piece->plinthes)) {
                        echo '<div class="piece-detail">';
                        echo '<strong>📐 Plinthes:</strong> '.dol_escape_htmltag($piece->plinthes);
                        if (!empty($piece->plinthes_hauteur)) {
                            echo ' ('.dol_escape_htmltag($piece->plinthes_hauteur).')';
                        }
                        echo '</div>';
                    }

                    // Profil
                    if (!empty($piece->profil)) {
                        echo '<div class="piece-detail">';
                        echo '<strong>🔧 Profil:</strong> '.dol_escape_htmltag($piece->profil);
                        if (!empty($piece->profil_finition)) {
                            echo ' - '.dol_escape_htmltag($piece->profil_finition);
                        }
                        echo '</div>';
                    }

                    // Crédence (only show if not "Non")
                    if (!empty($piece->credence) && $piece->credence != 'Non') {
                        echo '<div class="piece-detail">';
                        echo '<strong>🎨 Crédence:</strong> '.dol_escape_htmltag($piece->credence);
                        if (!empty($piece->credence_hauteur)) {
                            echo ' ('.dol_escape_htmltag($piece->credence_hauteur).')';
                        }
                        echo '</div>';
                    }

                    // Jusqu'au plafond
                    if (!empty($piece->jusqu_au_plafond) && $piece->jusqu_au_plafond != 'Non') {
                        echo '<div class="piece-detail" style="background: #ede9fe; padding: 8px 12px; border-radius: 8px;">';
                        echo '<strong style="color: #7c3aed;">⬆️ Jusqu\'au plafond:</strong> <span style="font-weight: 700; color: #5b21b6;">'.dol_escape_htmltag($piece->jusqu_au_plafond).'</span>';
                        echo '</div>';
                    }


                    // Display remarques if exists
                    if (!empty($piece->remarques)) {
                        echo '<div class="piece-detail" style="margin-top: 8px; padding: 10px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 6px;">';
                        echo '<strong style="color: #b45309;">💡 Remarques:</strong><br>';
                        echo '<span style="color: #78350f;">'.nl2br(dol_escape_htmltag($piece->remarques)).'</span>';
                        echo '</div>';
                    }

                    // Display photo if exists - Priorités multiples
                    $has_photo = false;
                    $photo_display_url = '';
                    $photo_source = '';

                    // PRIORITÉ 1: Photo directe de la pièce (photo_url)
                    if (!empty($piece->photo_url)) {
                        $photo_display_url = html_entity_decode($piece->photo_url);
                        if (!preg_match('/^https?:\/\//', $photo_display_url)) {
                            if (strpos($photo_display_url, '/') !== 0) {
                                $photo_display_url = '/' . $photo_display_url;
                            }
                            $photo_display_url = DOL_MAIN_URL_ROOT . $photo_display_url;
                        }
                        $has_photo = true;
                        $photo_source = 'photo_url';
                    }

                    // PRIORITÉ 2: Photo filename de la pièce
                    if (!$has_photo && !empty($piece->photo_filename)) {
                        $photo_display_url = DOL_URL_ROOT.'/viewimage.php?modulepart=mv3_sens_pose&file='.urlencode($piece->photo_filename);
                        $has_photo = true;
                        $photo_source = 'photo_filename';
                    }

                    // PRIORITÉ 3: Photo du produit via product_ref
                    $photo_proxy_url = '';
                    if (!$has_photo && !empty($piece->product_ref)) {
                        $product_image = getProductImage($piece->product_ref, null);
                        if ($product_image) {
                            $photo_display_url = $product_image['url'];
                            $photo_proxy_url = $product_image['url_proxy'];
                            $has_photo = true;
                            $photo_source = 'product_ref';
                        }
                    }

                    // PRIORITÉ 4: Photo du produit via fk_product
                    if (!$has_photo && !empty($piece->fk_product)) {
                        $product_image = getProductImage(null, $piece->fk_product);
                        if ($product_image) {
                            $photo_display_url = $product_image['url'];
                            $photo_proxy_url = $product_image['url_proxy'];
                            $has_photo = true;
                            $photo_source = 'fk_product';
                        }
                    }

                    if ($has_photo) {
                        echo '<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">';
                        echo '<div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-light); margin-bottom: 8px;">📸 Photo du produit</div>';

                        echo '<div style="width: 100%; max-width: 300px; position: relative; background: #f8fafc; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0;">';
                        // Fallback automatique: essaye d'abord viewimage.php, puis le proxy
                        $onerror = !empty($photo_proxy_url) ? "this.src='".htmlspecialchars($photo_proxy_url, ENT_QUOTES)."'; this.onerror=function(){this.parentElement.innerHTML='<div style=\\'padding: 40px; text-align: center; background: #fef2f2; color: #991b1b;\\'><strong style=\\'font-size: 14px;\\'>❌ Image non disponible</strong><br><span style=\\'font-size: 12px; color: #7f1d1d;\\'>Le fichier n\\'existe pas</span></div>'};" : "this.parentElement.innerHTML='<div style=\\'padding: 40px; text-align: center; background: #fef2f2; color: #991b1b;\\'><strong style=\\'font-size: 14px;\\'>❌ Image non disponible</strong><br><span style=\\'font-size: 12px; color: #7f1d1d;\\'>Le fichier n\\'existe pas</span></div>';";
                        echo '<img src="'.htmlspecialchars($photo_display_url).'" alt="Photo produit" loading="lazy" style="width: 100%; height: auto; object-fit: cover; display: block;" onerror="'.$onerror.'">';
                        echo '</div>';
                        echo '</div>';
                    } else {
                        echo '<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color);">';
                        echo '<div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-light); margin-bottom: 8px;">📸 Photo du produit</div>';
                        echo '<div style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 12px; border: 2px dashed #cbd5e1; color: #64748b;">';
                        echo '<div style="font-size: 40px; margin-bottom: 8px;">📷</div>';
                        echo '<strong style="font-size: 14px;">Aucune photo disponible</strong><br>';
                        echo '<span style="font-size: 12px;">Ajoutez une photo du produit depuis le desktop</span>';
                        echo '</div>';
                        echo '</div>';
                    }

                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="empty-state">';
                echo '<div class="empty-state-icon">🔲</div>';
                echo '<div class="empty-state-text">Aucune pièce</div>';
                echo '</div>';
            }
            ?>
        </div>

        <?php if ($fiche->signature_data): ?>
        <div class="card">
            <div class="card-header">
                <div class="card-title">✍️ Signature Client</div>
            </div>
            <div class="signature-box">
                <img src="data:image/png;base64,<?php echo $fiche->signature_data; ?>"
                     class="signature-img"
                     alt="Signature client">
                <?php if ($fiche->sign_name): ?>
                    <div style="margin-top: 8px; font-size: 14px; font-weight: 600; color: var(--text-primary);">
                        <?php echo dol_escape_htmltag($fiche->sign_name); ?>
                    </div>
                <?php endif; ?>
                <?php if ($fiche->signature_date): ?>
                    <div style="margin-top: 4px; font-size: 13px; color: var(--text-light);">
                        Signé le <?php echo dol_print_date($db->jdate($fiche->signature_date), 'dayhour'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">⚡ Actions</div>
            </div>

            <div style="display: grid; gap: 12px;">
                <!-- PDF - Tous les utilisateurs -->
                <a href="../../sens_pose/pdf.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-primary" style="width: 100%; text-align: center;">
                    📄 Voir le PDF
                </a>

                <!-- Modifier - Tous les utilisateurs -->
                <a href="edit.php?id=<?php echo $id; ?>" class="btn" style="width: 100%; text-align: center; background: #dbeafe; color: #1e40af;">
                    ✏️ Modifier la fiche
                </a>

                <!-- Signature client - Si pas encore signée -->
                <?php if ($fiche->statut != 'signe'): ?>
                <a href="signature.php?id=<?php echo $id; ?>" class="btn" style="width: 100%; text-align: center; background: #d1fae5; color: #065f46;">
                    ✍️ Faire signer le client
                </a>
                <?php endif; ?>

                <?php if ($user->admin || $user->rights->mv3pro_portail->admin): ?>

                <!-- Debug images - Admin uniquement -->
                <a href="debug_images.php?id=<?php echo $id; ?>" class="btn" style="width: 100%; text-align: center; background: #fef3c7; color: #92400e;">
                    🔍 Debug images
                </a>

                <!-- Envoyer email - Admin uniquement -->
                <a href="../../sens_pose/send_email.php?id=<?php echo $id; ?>" class="btn" style="width: 100%; text-align: center; background: #d1fae5; color: #065f46;">
                    📧 Envoyer par email
                </a>

                <!-- Supprimer - Admin uniquement -->
                <a href="../../sens_pose/list.php?action=delete&id=<?php echo $id; ?>&token=<?php echo newToken(); ?>"
                   class="btn"
                   style="width: 100%; text-align: center; background: #fee2e2; color: #991b1b;"
                   onclick="return confirm('⚠️ Supprimer cette fiche ?\n\nCette action est irréversible.')">
                    🗑️ Supprimer
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>
</body>
</html>
