<?php
/**
 * API pour récupérer les produits d'un devis avec leurs photos
 * Utilisé dans edit_pieces.php pour sélectionner produit + photo + format
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

if (!$res) die(json_encode(['error' => 'Include of main fails']));

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

header('Content-Type: application/json');

$fk_sens_pose = GETPOST('fk_sens_pose', 'int');

if (!$fk_sens_pose) {
    echo json_encode(['error' => 'ID fiche sens de pose required']);
    exit;
}

$sql = "SELECT fk_projet, fk_client FROM ".MAIN_DB_PREFIX."mv3_sens_pose WHERE rowid = ".(int)$fk_sens_pose;
$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) == 0) {
    echo json_encode(['error' => 'Fiche not found']);
    exit;
}

$fiche = $db->fetch_object($resql);
$fk_projet = $fiche->fk_projet;
$fk_client = $fiche->fk_client;

if (!$fk_projet && !$fk_client) {
    echo json_encode(['error' => 'Aucun projet ou client lié à cette fiche']);
    exit;
}

$produits = [];

$sql_devis = "SELECT DISTINCT
    prd.fk_product,
    prd.description,
    prd.label,
    pr.ref as devis_ref,
    pr.rowid as devis_id
FROM ".MAIN_DB_PREFIX."propal pr
LEFT JOIN ".MAIN_DB_PREFIX."propaldet prd ON prd.fk_propal = pr.rowid
WHERE prd.fk_product > 0";

if ($fk_client > 0) {
    $sql_devis .= " AND pr.fk_soc = ".(int)$fk_client;
}

if ($fk_projet > 0) {
    $sql_devis .= " AND pr.fk_projet = ".(int)$fk_projet;
}

$sql_devis .= " ORDER BY pr.datep DESC, prd.rang ASC";

$resql_devis = $db->query($sql_devis);

if ($resql_devis) {
    $products_seen = [];

    while ($obj = $db->fetch_object($resql_devis)) {
        if (in_array($obj->fk_product, $products_seen)) {
            continue;
        }
        $products_seen[] = $obj->fk_product;

        $product = new Product($db);
        $product->fetch($obj->fk_product);

        $format = '';
        if (!empty($product->array_options['options_format'])) {
            $format = $product->array_options['options_format'];
        } elseif (preg_match('/(\d+\s*[xX×]\s*\d+\s*cm)/i', $product->label, $matches)) {
            $format = $matches[1];
        } elseif (preg_match('/(\d+\s*[xX×]\s*\d+)/i', $product->label, $matches)) {
            $format = $matches[1] . ' cm';
        }

        if (empty($format) && preg_match('/(\d+\s*[xX×]\s*\d+\s*cm)/i', $obj->description, $matches)) {
            $format = $matches[1];
        } elseif (empty($format) && preg_match('/(\d+\s*[xX×]\s*\d+)/i', $obj->description, $matches)) {
            $format = $matches[1] . ' cm';
        }

        $photo_url = '';
        $photo_filename = '';

        $sql_photo = "SELECT filename, filepath
                      FROM ".MAIN_DB_PREFIX."ecm_files
                      WHERE src_object_type = 'product'
                      AND src_object_id = ".(int)$obj->fk_product."
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

        $produits[] = [
            'fk_product' => $obj->fk_product,
            'reference' => $product->ref,
            'label' => $product->label,
            'description' => $obj->description,
            'format' => $format,
            'photo_url' => $photo_url,
            'photo_filename' => $photo_filename,
            'devis_ref' => $obj->devis_ref,
            'has_photo' => !empty($photo_url)
        ];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($produits),
    'produits' => $produits
]);

$db->close();
