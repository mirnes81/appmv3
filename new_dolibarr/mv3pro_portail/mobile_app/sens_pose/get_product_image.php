<?php
/**
 * Proxy pour récupérer les images produits - Version Mobile
 * Contourne les problèmes de permissions
 */

$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";

// ACCESSIBLE À TOUS les utilisateurs authentifiés - AUCUNE restriction supplémentaire
if (!isset($_SESSION["dol_login"]) || empty($user->id)) {
    http_response_code(403);
    echo "Utilisateur non authentifié";
    exit;
}

// Toute personne connectée peut voir les images produits
// Pas de vérification de droits supplémentaires

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$product_ref = GETPOST('ref', 'alpha');
$fk_product = GETPOST('id', 'int');

if (empty($product_ref) && empty($fk_product)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Erreur: Paramètre 'ref' ou 'id' requis";
    exit;
}

// Get product reference if we have an ID
if (!empty($fk_product) && empty($product_ref)) {
    $product = new Product($db);
    if ($product->fetch($fk_product) > 0) {
        $product_ref = $product->ref;
    }
}

if (empty($product_ref)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "Erreur: Référence produit introuvable (fk_product: $fk_product)";
    exit;
}

// Find the image file
$product_dir = $conf->product->dir_output.'/'.$product_ref;

if (!is_dir($product_dir)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "Erreur: Répertoire produit introuvable\nChemin: $product_dir\nRéf: $product_ref";
    exit;
}

$photo_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP');
$image_file = null;

$files = @scandir($product_dir);
if ($files) {
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array($ext, $photo_extensions)) {
            $image_file = $product_dir.'/'.$file;
            break;
        }
    }
}

if (!$image_file || !file_exists($image_file)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    $file_list = $files ? implode(', ', array_filter($files, function($f) { return $f != '.' && $f != '..'; })) : 'aucun';
    echo "Erreur: Aucune image trouvée\nChemin: $product_dir\nRéf: $product_ref\nFichiers: $file_list";
    exit;
}

// Get mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $image_file);
finfo_close($finfo);

// Output the image
header('Content-Type: '.$mime_type);
header('Content-Length: '.filesize($image_file));
header('Cache-Control: public, max-age=86400'); // Cache 24h
header('Expires: '.gmdate('D, d M Y H:i:s', time() + 86400).' GMT');

readfile($image_file);
exit;
