<?php
/**
 * API pour récupérer les détails d'un devis : lignes, photos, formats
 */

// Définir le header JSON AVANT tout include
header('Content-Type: application/json; charset=utf-8');

// Capturer toute sortie HTML non désirée
ob_start();

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

// Nettoyer le buffer HTML
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

$lignes = [];
$current_piece_name = ''; // Pour stocker le dernier titre rencontré
$current_piece_text = ''; // Pour stocker le texte libre associé
$current_sub_titles = []; // Pour accumuler les sous-titres

$keywords = [
    'sol' => 'SOL',
    'murs sdd' => 'MURS SDD',
    'mur sdd' => 'MURS SDD',
    'murs' => 'MURS',
    'mur' => 'MUR',
    'credance' => 'CRÉDANCE',
    'crédance' => 'CRÉDANCE',
    'credence' => 'CRÉDANCE',
    'crédence' => 'CRÉDANCE',
    'cuisine' => 'Cuisine',
    'salle de bain' => 'Salle de bain',
    'sdb' => 'Salle de bain',
    'wc' => 'WC',
    'toilette' => 'WC',
    'salon' => 'Salon',
    'séjour' => 'Séjour',
    'chambre' => 'Chambre',
    'entrée' => 'Entrée',
    'hall' => 'Hall',
    'couloir' => 'Couloir',
    'garage' => 'Garage',
    'buanderie' => 'Buanderie',
    'cellier' => 'Cellier',
    'dressing' => 'Dressing',
    'bureau' => 'Bureau',
    'terrasse' => 'Terrasse',
    'balcon' => 'Balcon',
    'véranda' => 'Véranda',
    'cave' => 'Cave',
    'sous-sol' => 'Sous-sol',
];

foreach ($propal->lines as $line) {
    // Si c'est un titre/sous-total ou texte libre (product_type = 9)
    if ($line->product_type == 9) {
        $label = strip_tags($line->label);
        $label = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $label = trim($label);

        $desc = strip_tags($line->desc);
        $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc = trim($desc);

        // Vérifier le type de ligne spéciale
        // special_code: 0 ou null = texte libre, 1 = titre, 2 = sous-total
        $is_titre = (isset($line->special_code) && $line->special_code == 1);
        $is_texte_libre = (!isset($line->special_code) || $line->special_code == 0);

        // Si c'est un TITRE (special_code = 1)
        if ($is_titre && strlen($label) > 0 && strlen($label) < 100) {
            // Liste des mots-clés qui indiquent un sous-titre (pas une nouvelle pièce)
            $sous_titres = ['PLINTHES', 'PLINTHE', 'INTÉRIEUR', 'INTERIEUR', 'RESTE', 'MURS',
                           'COUPER', 'POSE', 'HAUTEUR', 'PLAFOND'];

            // Vérifier si c'est un sous-titre
            $is_sous_titre = false;
            foreach ($sous_titres as $st) {
                if (stripos($label, $st) !== false) {
                    $is_sous_titre = true;
                    break;
                }
            }

            // Si c'est un sous-titre, l'ajouter à la liste des sous-titres
            if ($is_sous_titre) {
                $current_sub_titles[] = $label;

                // Si le sous-titre a une description, la mettre dans piece_text
                if (strlen($desc) > 0) {
                    $current_piece_text .= ($current_piece_text ? "\n" : '') . $desc;
                }
            } else {
                // Sinon, c'est une NOUVELLE PIÈCE principale
                $current_piece_name = $label;
                $current_sub_titles = []; // Reset les sous-titres
                $current_piece_text = ''; // Reset le texte pour la nouvelle pièce

                // Capturer aussi la description du titre
                if (strlen($desc) > 0) {
                    $current_piece_text = $desc;
                }
            }
        } else if ($is_texte_libre) {
            // Si c'est une LIGNE DE TEXTE (special_code = 0 ou null)
            // On l'ajoute au piece_text
            if (strlen($label) > 0) {
                $current_piece_text .= ($current_piece_text ? "\n" : '') . $label;
            }
            if (strlen($desc) > 0) {
                $current_piece_text .= ($current_piece_text ? "\n" : '') . $desc;
            }
        } else {
            // Autres cas (labels longs, etc.)
            if (strlen($label) >= 100) {
                $current_piece_text .= ($current_piece_text ? "\n" : '') . $label;
            }
            if (strlen($desc) > 0 && strlen($label) < 100) {
                $current_piece_text .= ($current_piece_text ? "\n" : '') . $desc;
            }
        }

        continue;
    }

    if ($line->total_ht < 0 || $line->subprice < 0 || $line->qty <= 0) {
        continue;
    }

    $product = null;
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

        if (!empty($product->array_options['options_format'])) {
            $format = $product->array_options['options_format'];
        } elseif (preg_match('/(\d+\s*[xX×]\s*\d+\s*cm)/i', $product->label, $matches)) {
            $format = $matches[1];
        } elseif (preg_match('/(\d+\s*[xX×]\s*\d+)/i', $product->label, $matches)) {
            $format = $matches[1] . ' cm';
        }

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

    // Construire le nom de pièce complet : titre principal + tous les sous-titres
    $detected_piece = $current_piece_name;

    // Ajouter tous les sous-titres accumulés
    if (!empty($current_sub_titles)) {
        if (!empty($detected_piece)) {
            $detected_piece .= ' ' . implode(' ', $current_sub_titles);
        } else {
            $detected_piece = implode(' ', $current_sub_titles);
        }
    }

    // Si toujours vide, essayer de détecter via mots-clés
    if (empty($detected_piece)) {
        $full_text = strtolower($label_clean . ' ' . $description_clean);

        foreach ($keywords as $keyword => $piece_name) {
            if (stripos($full_text, $keyword) !== false) {
                $detected_piece = $piece_name;
                break;
            }
        }
    }

    // Si toujours vide, essayer d'extraire depuis piece_text (prendre la première ligne/mot)
    if (empty($detected_piece) && !empty($current_piece_text)) {
        // Prendre la première ligne du piece_text (avant un saut de ligne ou un espace multiple)
        $first_line = trim(preg_split('/[\n\r]+/', $current_piece_text)[0]);
        // Limiter à 50 caractères maximum
        if (strlen($first_line) > 0 && strlen($first_line) <= 50) {
            $detected_piece = $first_line;
        }
    }

    $lignes[] = [
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
        'fk_product' => $line->fk_product,
        'piece_name' => $current_piece_name,  // Titre principal seulement
        'piece_text' => $current_piece_text,   // Texte libre associé
        'sub_titles' => $current_sub_titles    // Tous les sous-titres (gardés pour toute la section)
    ];

    // NE PAS reset les sous-titres ici, ils restent valides jusqu'au prochain titre principal
}

$nb_photos_total = 0;
foreach ($lignes as $ligne) {
    if (!empty($ligne['photo_url'])) {
        $nb_photos_total++;
    }
}

// Nettoyer tout buffer restant avant d'envoyer le JSON
ob_clean();

echo json_encode([
    'success' => true,
    'devis' => [
        'rowid' => $propal->id,
        'ref' => $propal->ref,
        'ref_client' => $propal->ref_client,
        'date' => dol_print_date($propal->date, 'day')
    ],
    'lignes' => $lignes,
    'nb_lignes' => count($lignes),
    'nb_photos' => $nb_photos_total
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Envoyer le buffer et fermer
ob_end_flush();

$db->close();
