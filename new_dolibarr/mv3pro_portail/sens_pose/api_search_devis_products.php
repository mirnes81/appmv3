<?php
/**
 * API pour rechercher intelligemment les produits d'un devis
 * par nom de pièce (ex: "Cuisine" trouve les produits posés en cuisine)
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

if (!$res) {
    die(json_encode(['error' => 'Include main fails']));
}

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

header('Content-Type: application/json');

$fk_sens_pose = GETPOST('fk_sens_pose', 'int');
$piece_name = GETPOST('piece_name', 'alpha');

if (empty($fk_sens_pose)) {
    die(json_encode(['error' => 'fk_sens_pose requis']));
}

// Récupérer la fiche sens de pose pour obtenir le fk_client
$sql = "SELECT fk_client, fk_projet FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".(int)$fk_sens_pose;
$resql = $db->query($sql);
if (!$resql) {
    die(json_encode(['error' => 'Fiche non trouvée']));
}

$fiche = $db->fetch_object($resql);
if (!$fiche || !$fiche->fk_client) {
    die(json_encode(['error' => 'Client non trouvé']));
}

// Récupérer les devis du client
$sql_devis = "SELECT p.rowid, p.ref, p.ref_client
              FROM ".MAIN_DB_PREFIX."propal as p
              WHERE p.fk_soc = ".(int)$fiche->fk_client;

if ($fiche->fk_projet > 0) {
    $sql_devis .= " AND p.fk_projet = ".(int)$fiche->fk_projet;
}

$sql_devis .= " AND p.fk_statut IN (1, 2, 3, 4)
                ORDER BY p.datep DESC
                LIMIT 5";

$resql_devis = $db->query($sql_devis);
if (!$resql_devis) {
    die(json_encode(['error' => 'Erreur recherche devis']));
}

$products_found = [];

// Pour chaque devis
while ($devis_obj = $db->fetch_object($resql_devis)) {
    $propal = new Propal($db);
    $propal->fetch($devis_obj->rowid);

    // Pour chaque ligne du devis
    foreach ($propal->lines as $line) {
        if ($line->fk_product <= 0) {
            continue;
        }
        if ($line->product_type == 9) {
            continue;
        }
        if ($line->total_ht < 0 || $line->qty <= 0) {
            continue;
        }

        // Chercher si le nom de la pièce est dans la description ou le label
        $description = strtolower($line->desc ?? '');
        $label = strtolower($line->label ?? '');
        $search_term = strtolower($piece_name);

        $match_score = 0;

        // Score de correspondance
        if (!empty($piece_name)) {
            if (stripos($description, $search_term) !== false) {
                $match_score += 10;
            }
            if (stripos($label, $search_term) !== false) {
                $match_score += 10;
            }

            // Mots clés
            $keywords = explode(' ', $search_term);
            foreach ($keywords as $keyword) {
                if (strlen($keyword) < 3) {
                    continue;
                }
                if (stripos($description, $keyword) !== false) {
                    $match_score += 3;
                }
                if (stripos($label, $keyword) !== false) {
                    $match_score += 3;
                }
            }
        } else {
            // Si pas de nom de pièce, retourner tous les produits avec photos
            $match_score = 1;
        }

        if ($match_score > 0) {
            $product = new Product($db);
            $product->fetch($line->fk_product);

            $photo_url = '';
            $photo_filename = '';

            // Récupérer la photo du produit
            $sql_photo = "SELECT rowid, photo, filename
                         FROM ".MAIN_DB_PREFIX."ecm_files
                         WHERE ref = '".$db->escape($product->ref)."'
                         AND entity = ".$conf->entity."
                         AND (filename LIKE '%.jpg' OR filename LIKE '%.jpeg' OR filename LIKE '%.png' OR filename LIKE '%.gif')
                         ORDER BY position ASC
                         LIMIT 1";

            $resql_photo = $db->query($sql_photo);
            if ($resql_photo && $db->num_rows($resql_photo) > 0) {
                $photo_obj = $db->fetch_object($resql_photo);
                $photo_filename = $photo_obj->filename;
                $photo_url = DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$conf->entity.'&file='.urlencode($product->ref.'/'.$photo_filename);
            }

            // Extraire le format
            $format = '';
            if (preg_match('/(\d+\s*[xX×]\s*\d+)\s*cm/i', $line->desc, $matches)) {
                $format = str_replace(['x', 'X', '×'], '×', $matches[1]) . ' cm';
            }

            $products_found[] = [
                'fk_product' => $line->fk_product,
                'label' => $line->label,
                'description' => $line->desc,
                'ref' => $product->ref,
                'format' => $format,
                'photo_url' => $photo_url,
                'photo_filename' => $photo_filename,
                'devis_ref' => $propal->ref,
                'match_score' => $match_score
            ];
        }
    }
}

// Trier par score de correspondance
usort($products_found, function($a, $b) {
    return $b['match_score'] - $a['match_score'];
});

// Retourner les 5 meilleurs résultats
$products_found = array_slice($products_found, 0, 5);

die(json_encode([
    'success' => true,
    'products' => $products_found,
    'count' => count($products_found),
    'search_term' => $piece_name
]));
