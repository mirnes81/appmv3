<?php
/**
 * Ajout de produits à une fiche sens de pose - Version Mobile
 * Avec recherche intelligente comme sur desktop
 */

$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";

if (!isset($_SESSION["dol_login"]) || empty($user->id)) {
    header("Location: ../index.php");
    exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$id = GETPOST('id', 'int');

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

// Function to get product image
function getProductImage($product_ref) {
    global $conf;

    if (empty($product_ref)) return null;

    $product_dir = $conf->product->dir_output.'/'.$product_ref;

    if (!is_dir($product_dir)) return null;

    $photo_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP');

    $files = scandir($product_dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array($ext, $photo_extensions)) {
            return array(
                'path' => $product_dir.'/'.$file,
                'url' => DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($product_ref.'/'.$file),
                'filename' => $file
            );
        }
    }

    return null;
}

// Handle AJAX search
if (GETPOST('action') == 'search_products') {
    header('Content-Type: application/json; charset=utf-8');
    $search = GETPOST('q', 'restricthtml');

    $results = array();

    if (strlen($search) >= 2) {
        $sql_search = "SELECT rowid, ref, label, description
                       FROM ".MAIN_DB_PREFIX."product
                       WHERE (ref LIKE '%".$db->escape($search)."%'
                       OR label LIKE '%".$db->escape($search)."%'
                       OR description LIKE '%".$db->escape($search)."%')
                       AND tosell = 1
                       ORDER BY ref ASC
                       LIMIT 20";

        $resql_search = $db->query($sql_search);
        if ($resql_search) {
            while ($obj = $db->fetch_object($resql_search)) {
                $photo = getProductImage($obj->ref);
                $results[] = array(
                    'id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'label' => $obj->label,
                    'description' => substr(strip_tags($obj->description), 0, 100),
                    'photo_url' => $photo ? $photo['url'] : null
                );
            }
        }
    }

    echo json_encode($results);
    exit;
}

// Handle add product
if (GETPOST('action') == 'add_product') {
    header('Content-Type: application/json; charset=utf-8');

    $fk_product = GETPOST('fk_product', 'int');
    $piece_name = GETPOST('piece_name', 'restricthtml');
    $quantite = GETPOST('quantite', 'restricthtml');
    $format = GETPOST('format', 'restricthtml');
    $pose = GETPOST('pose', 'restricthtml');
    $joint = GETPOST('joint', 'restricthtml');
    $orientation = GETPOST('orientation', 'alpha');
    $notes_piece = GETPOST('notes_piece', 'restricthtml');

    if (empty($fk_product)) {
        echo json_encode(['success' => false, 'error' => 'Produit requis']);
        exit;
    }

    if (empty($piece_name)) {
        echo json_encode(['success' => false, 'error' => 'Nom de la pièce requis']);
        exit;
    }

    $sql_order = "SELECT MAX(ordre) as max_ordre FROM ".MAIN_DB_PREFIX."mv3_sens_pose_pieces WHERE fk_sens_pose = ".(int)$id;
    $resql_order = $db->query($sql_order);
    $next_ordre = 1;
    if ($resql_order) {
        $obj_ordre = $db->fetch_object($resql_order);
        if ($obj_ordre && $obj_ordre->max_ordre) {
            $next_ordre = $obj_ordre->max_ordre + 1;
        }
    }

    $db->begin();

    $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose_pieces
                   (fk_sens_pose, fk_product, piece_name, quantite, format, pose, joint, orientation, notes_piece, ordre)
                   VALUES (".(int)$id.", ".(int)$fk_product.",
                   ".($piece_name ? "'".$db->escape($piece_name)."'" : "NULL").",
                   ".($quantite ? "'".$db->escape($quantite)."'" : "NULL").",
                   ".($format ? "'".$db->escape($format)."'" : "NULL").",
                   ".($pose ? "'".$db->escape($pose)."'" : "NULL").",
                   ".($joint ? "'".$db->escape($joint)."'" : "NULL").",
                   ".($orientation ? "'".$db->escape($orientation)."'" : "NULL").",
                   ".($notes_piece ? "'".$db->escape($notes_piece)."'" : "NULL").",
                   ".(int)$next_ordre.")";

    if ($db->query($sql_insert)) {
        $db->commit();
        echo json_encode(['success' => true, 'id' => $db->last_insert_id(MAIN_DB_PREFIX."mv3_sens_pose_pieces")]);
    } else {
        $db->rollback();
        echo json_encode(['success' => false, 'error' => $db->lasterror()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Ajouter des produits - <?php echo dol_escape_htmltag($fiche->ref); ?></title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        .search-box {
            position: sticky;
            top: 0;
            background: white;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            z-index: 100;
        }
        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: #0891b2;
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }
        .product-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background 0.2s;
        }
        .product-item:active {
            background: #f9fafb;
        }
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f3f4f6;
        }
        .product-image-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .product-info {
            flex: 1;
            min-width: 0;
        }
        .product-ref {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
        }
        .product-label {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .product-desc {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .loading {
            text-align: center;
            padding: 24px;
            color: #6b7280;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            padding: 20px;
            overflow-y: auto;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }
    </style>
</head>
<body>
    <div class="app-header">
        <a href="view.php?id=<?php echo $id; ?>" class="app-header-back">←</a>
        <div>
            <div class="app-header-title">➕ Ajouter produits</div>
            <div class="app-header-subtitle"><?php echo dol_escape_htmltag($fiche->ref); ?></div>
        </div>
    </div>

    <div class="search-box">
        <input type="text"
               class="search-input"
               id="searchInput"
               placeholder="🔍 Rechercher un produit (ref, nom...)">
    </div>

    <div id="resultsContainer">
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">
                Recherchez un produit
            </div>
            <div style="font-size: 14px;">
                Tapez au moins 2 caractères pour rechercher
            </div>
        </div>
    </div>

    <!-- Modal détails produit -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Détails du produit</div>
            </div>
            <div class="modal-body">
                <input type="hidden" id="selectedProductId">

                <div class="form-group">
                    <label class="form-label">Nom de la pièce *</label>
                    <input type="text" id="piece_name" class="form-input" required placeholder="Ex: Salon, Cuisine..." style="border: 2px solid #0891b2;">
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Obligatoire - Indiquez la pièce concernée</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Quantité *</label>
                    <input type="text" id="quantite" class="form-input" required placeholder="Ex: 25 m², 15 boîtes..." style="border: 2px solid #0891b2;">
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Obligatoire - Quantité à poser</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Format</label>
                    <input type="text" id="format" class="form-input" placeholder="Ex: 60x60 cm">
                </div>

                <div class="form-group">
                    <label class="form-label">Type de pose</label>
                    <select id="pose" class="form-input">
                        <option value="">-- Choisir --</option>
                        <option value="Collé">Collé</option>
                        <option value="Scellé">Scellé</option>
                        <option value="Sur plot">Sur plot</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Joint</label>
                    <input type="text" id="joint" class="form-input" placeholder="Ex: 3mm">
                </div>

                <div class="form-group">
                    <label class="form-label">Sens de pose</label>
                    <select id="orientation" class="form-input">
                        <option value="">-- Choisir --</option>
                        <option value="horizontal">Horizontal</option>
                        <option value="vertical">Vertical</option>
                        <option value="diagonal">Diagonal</option>
                        <option value="chevron">Chevron</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea id="notes_piece" class="form-input" rows="3" placeholder="Notes supplémentaires..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()" style="flex: 1;">
                    Annuler
                </button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()" style="flex: 1;">
                    ✓ Ajouter
                </button>
            </div>
        </div>
    </div>

    <script>
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const resultsContainer = document.getElementById('resultsContainer');
        const productModal = document.getElementById('productModal');

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                resultsContainer.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">
                            Recherchez un produit
                        </div>
                        <div style="font-size: 14px;">
                            Tapez au moins 2 caractères pour rechercher
                        </div>
                    </div>
                `;
                return;
            }

            resultsContainer.innerHTML = '<div class="loading">🔄 Recherche en cours...</div>';

            searchTimeout = setTimeout(() => {
                fetch('add_products.php?id=<?php echo $id; ?>&action=search_products&q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        if (data.length === 0) {
                            resultsContainer.innerHTML = `
                                <div class="empty-state">
                                    <div class="empty-state-icon">😕</div>
                                    <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">
                                        Aucun produit trouvé
                                    </div>
                                    <div style="font-size: 14px;">
                                        Essayez une autre recherche
                                    </div>
                                </div>
                            `;
                            return;
                        }

                        let html = '';
                        data.forEach(product => {
                            html += `
                                <div class="product-item" onclick="selectProduct(${product.id}, '${product.ref.replace(/'/g, "\\'")}', '${product.label.replace(/'/g, "\\'")}')">
                                    ${product.photo_url ?
                                        `<img src="${product.photo_url}" class="product-image" alt="${product.label}">` :
                                        '<div class="product-image-placeholder">📦</div>'
                                    }
                                    <div class="product-info">
                                        <div class="product-ref">${product.ref}</div>
                                        <div class="product-label">${product.label}</div>
                                        ${product.description ? `<div class="product-desc">${product.description}</div>` : ''}
                                    </div>
                                </div>
                            `;
                        });

                        resultsContainer.innerHTML = html;
                    })
                    .catch(err => {
                        resultsContainer.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">⚠️</div>
                                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px; color: #dc2626;">
                                    Erreur de recherche
                                </div>
                            </div>
                        `;
                    });
            }, 300);
        });

        function selectProduct(id, ref, label) {
            document.getElementById('selectedProductId').value = id;
            document.getElementById('modalTitle').textContent = ref + ' - ' + label;
            productModal.classList.add('active');
        }

        function closeModal() {
            productModal.classList.remove('active');
            document.getElementById('piece_name').value = '';
            document.getElementById('quantite').value = '';
            document.getElementById('format').value = '';
            document.getElementById('pose').value = '';
            document.getElementById('joint').value = '';
            document.getElementById('orientation').value = '';
            document.getElementById('notes_piece').value = '';
        }

        function saveProduct() {
            // Validation
            const piece_name = document.getElementById('piece_name').value.trim();
            const quantite = document.getElementById('quantite').value.trim();

            if (!piece_name) {
                alert('⚠️ Le nom de la pièce est obligatoire');
                document.getElementById('piece_name').focus();
                return;
            }

            if (!quantite) {
                alert('⚠️ La quantité est obligatoire');
                document.getElementById('quantite').focus();
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add_product');
            formData.append('fk_product', document.getElementById('selectedProductId').value);
            formData.append('piece_name', piece_name);
            formData.append('quantite', quantite);
            formData.append('format', document.getElementById('format').value);
            formData.append('pose', document.getElementById('pose').value);
            formData.append('joint', document.getElementById('joint').value);
            formData.append('orientation', document.getElementById('orientation').value);
            formData.append('notes_piece', document.getElementById('notes_piece').value);

            fetch('add_products.php?id=<?php echo $id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'view.php?id=<?php echo $id; ?>&added=1';
                } else {
                    alert('Erreur: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(err => {
                alert('Erreur de connexion');
            });
        }

        productModal.addEventListener('click', function(e) {
            if (e.target === productModal) {
                closeModal();
            }
        });
    </script>
</body>
</html>
