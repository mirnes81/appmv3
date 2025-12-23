<?php
/**
 * API v2 - Récupère TOUTES les lignes du devis dans l'ordre exact
 * Conserve la structure hiérarchique complète
 */

header('Content-Type: application/json; charset=utf-8');
ob_start();

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

ob_clean();

if (!$res) {
    echo json_encode(['error' => 'Include of main fails']);
    exit;
}

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$fk_devis = GETPOST('fk_devis', 'int');

if (!$fk_devis) {
    echo json_encode(['error' => 'Devis ID required']);
    exit;
}

$propal = new Propal($db);
$result = $propal->fetch($fk_devis);

if ($result <= 0) {
    echo json_encode(['error' => 'Devis not found']);
    exit;
}

$all_lines = [];
$current_section_index = null;

foreach ($propal->lines as $line) {

    // ===== TRAITER LES TITRES / TEXTES (product_type = 9) =====
    if ($line->product_type == 9) {
        $label = strip_tags($line->label ?? '');
        $label = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $label = trim($label);

        $desc = strip_tags($line->desc ?? '');
        $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc = trim($desc);

        $description = strip_tags($line->description ?? '');
        $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = trim($description);

        // Ignorer les sous-totaux
        if (stripos($label, 'sous-total') !== false || stripos($desc, 'sous-total') !== false) {
            continue;
        }

        $section_title = !empty($label) ? $label : $desc;

        if (!empty($section_title)) {
            // Chercher si une section avec ce titre existe déjà
            $found = false;
            foreach ($all_lines as $idx => $existing) {
                if ($existing['type'] == 'section' && $existing['title'] === $section_title) {
                    $found = true;
                    $current_section_index = $idx;
                    // Ajouter la description si elle n'existe pas déjà
                    if (!empty($description) && !in_array($description, $all_lines[$idx]['texts'])) {
                        $all_lines[$idx]['texts'][] = $description;
                    }
                    break;
                }
            }

            // Si pas trouvée, créer une nouvelle section
            if (!$found) {
                $current_section_index = count($all_lines);
                $all_lines[] = [
                    'type' => 'section',
                    'title' => $section_title,
                    'subtitles' => [],
                    'texts' => !empty($description) ? [$description] : [],
                    'products' => []
                ];
            }
        }

        continue;
    }

    // ===== TRAITER LES PRODUITS (product_type = 0) =====

    // Ignorer les lignes négatives ou sans quantité
    if ($line->total_ht < 0 || $line->subprice < 0 || $line->qty <= 0) {
        continue;
    }

    // Récupérer les infos du produit
    $format = '';
    $reference = '';
    $product_label = '';
    $photo_url = '';
    $photo_filename = '';
    $unite = '';

    if ($line->fk_product > 0) {
        $product = new Product($db);
        $product->fetch($line->fk_product);
        $reference = $product->ref;
        $product_label = $product->label;

        // Unité
        $unite_code = $product->fk_unit ? $product->fk_unit : ($line->fk_unit ? $line->fk_unit : 0);
        if ($unite_code == 0) {
            $unite = 'u';
        } elseif ($unite_code == -3) {
            $unite = 'm²';
        } elseif ($unite_code == -1) {
            $unite = 'ml';
        } else {
            $sql_unit = "SELECT short_label FROM ".MAIN_DB_PREFIX."c_units WHERE rowid = ".(int)$unite_code;
            $resql_unit = $db->query($sql_unit);
            if ($resql_unit && $db->num_rows($resql_unit) > 0) {
                $obj_unit = $db->fetch_object($resql_unit);
                $unite = $obj_unit->short_label;
            } else {
                $unite = 'u';
            }
        }

        // Format
        if (!empty($product->array_options['options_format'])) {
            $format = $product->array_options['options_format'];
        } elseif (preg_match('/(\d+\s*[xX×]\s*\d+\s*cm)/i', $product->label, $matches)) {
            $format = $matches[1];
        } elseif (preg_match('/(\d+\s*[xX×]\s*\d+)/i', $product->label, $matches)) {
            $format = $matches[1] . ' cm';
        }

        // Photo
        $sql_photo = "SELECT filename, filepath
                      FROM ".MAIN_DB_PREFIX."ecm_files
                      WHERE src_object_type = 'product'
                      AND src_object_id = ".(int)$line->fk_product."
                      AND filename IS NOT NULL
                      AND (
                          filename LIKE '%.jpg'
                          OR filename LIKE '%.jpeg'
                          OR filename LIKE '%.png'
                          OR filename LIKE '%.gif'
                          OR filename LIKE '%.JPG'
                          OR filename LIKE '%.JPEG'
                          OR filename LIKE '%.PNG'
                      )
                      ORDER BY position ASC, date_c ASC
                      LIMIT 1";

        $resql_photo = $db->query($sql_photo);
        if ($resql_photo && $db->num_rows($resql_photo) > 0) {
            $obj_photo = $db->fetch_object($resql_photo);
            $photo_filename = $obj_photo->filename;
            $photo_url = DOL_URL_ROOT.'/document.php?modulepart=product&file='.urlencode($product->ref.'/'.$obj_photo->filename);
        }
    }

    // Extraire format depuis description si pas trouvé
    if (empty($format) && preg_match('/(\d+\s*[xX×]\s*\d+\s*cm)/i', $line->desc, $matches)) {
        $format = $matches[1];
    } elseif (empty($format) && preg_match('/(\d+\s*[xX×]\s*\d+)/i', $line->desc, $matches)) {
        $format = $matches[1] . ' cm';
    }

    $description_clean = strip_tags($line->desc);
    $description_clean = html_entity_decode($description_clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description_clean = preg_replace('/\s+/', ' ', $description_clean);
    $description_clean = trim($description_clean);

    $label_clean = strip_tags($line->label);
    $label_clean = html_entity_decode($label_clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $label_clean = trim($label_clean);

    // Créer l'objet produit
    $product_data = [
        'type' => 'product',
        'rowid' => $line->rowid,
        'description' => $description_clean,
        'label' => $label_clean,
        'qty' => $line->qty,
        'unite' => $unite,
        'reference' => $reference,
        'product_label' => $product_label,
        'format' => $format,
        'photo_url' => $photo_url,
        'photo_filename' => $photo_filename,
        'fk_product' => $line->fk_product
    ];

    // Ajouter le produit à la section courante
    if ($current_section_index !== null) {
        $all_lines[$current_section_index]['products'][] = $product_data;
    } else {
        // Pas de section, ajouter directement
        $all_lines[] = $product_data;
    }
}

// Compter les photos
$nb_photos_total = 0;
foreach ($all_lines as $line) {
    if ($line['type'] == 'product' && !empty($line['photo_url'])) {
        $nb_photos_total++;
    } elseif ($line['type'] == 'section') {
        foreach ($line['products'] as $prod) {
            if (!empty($prod['photo_url'])) {
                $nb_photos_total++;
            }
        }
    }
}

$response = [
    'success' => true,
    'devis' => [
        'ref' => $propal->ref,
        'ref_client' => $propal->ref_client,
        'date' => dol_print_date($propal->date, '%d/%m/%Y')
    ],
    'all_lines' => $all_lines,
    'nb_lignes' => count($all_lines),
    'nb_photos' => $nb_photos_total
];

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
