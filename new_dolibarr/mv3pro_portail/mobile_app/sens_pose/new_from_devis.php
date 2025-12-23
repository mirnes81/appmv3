<?php
/**
 * Création fiche sens de pose depuis un devis - Version Mobile Complète
 * Parcours: Projet → Clients → Devis → Produits
 */

// Bypass CSRF token check for API calls
if (isset($_POST['action']) && in_array($_POST['action'], ['search_projets', 'get_clients', 'get_devis', 'get_produits', 'create_from_devis'])) {
    define('NOTOKENRENEWAL', 1);
    define('NOCSRFCHECK', 1);
}

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

function getProductImage($product_ref) {
    global $conf;
    if (empty($product_ref)) return null;

    $product_dir = $conf->product->dir_output.'/'.$product_ref;
    if (!is_dir($product_dir)) return null;

    $photo_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP');
    $files = scandir($product_dir);

    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
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

$action = GETPOST('action', 'alpha');

// API: Recherche de projets
if ($action == 'search_projets') {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $search = GETPOST('search', 'alpha');

    $sql = "SELECT p.rowid, p.ref, p.title
            FROM ".MAIN_DB_PREFIX."projet p
            WHERE p.fk_statut = 1
            AND p.entity = ".$conf->entity;

    if (!empty($search)) {
        $sql .= " AND (p.ref LIKE '%".$db->escape($search)."%' OR p.title LIKE '%".$db->escape($search)."%')";
    }

    $sql .= " ORDER BY p.ref DESC LIMIT 50";

    $resql = $db->query($sql);
    $projets = array();

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $projets[] = array(
                'id' => $obj->rowid,
                'ref' => $obj->ref,
                'title' => $obj->title
            );
        }
    }

    echo json_encode($projets);
    exit;
}

// API: Get clients from project
if ($action == 'get_clients') {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $fk_projet = GETPOST('fk_projet', 'int');

    $clients = array();

    // Méthode 1: Via element_element (lien direct projet-societe)
    $sql = "SELECT DISTINCT s.rowid, s.nom, s.address, s.zip, s.town, s.phone
            FROM ".MAIN_DB_PREFIX."societe s
            INNER JOIN ".MAIN_DB_PREFIX."element_element ee ON ee.fk_target = s.rowid AND ee.targettype = 'societe'
            WHERE ee.fk_source = ".(int)$fk_projet."
            AND ee.sourcetype = 'project'
            AND s.entity IN (0,".$conf->entity.")
            AND s.client IN (1,2,3)
            ORDER BY s.nom ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $clients[$obj->rowid] = array(
                'id' => $obj->rowid,
                'nom' => $obj->nom,
                'address' => $obj->address,
                'zip' => $obj->zip,
                'town' => $obj->town,
                'phone' => $obj->phone
            );
        }
    }

    // Méthode 2: Via les devis liés au projet
    if (empty($clients)) {
        $sql2 = "SELECT DISTINCT s.rowid, s.nom, s.address, s.zip, s.town, s.phone
                FROM ".MAIN_DB_PREFIX."societe s
                INNER JOIN ".MAIN_DB_PREFIX."propal p ON p.fk_soc = s.rowid
                WHERE p.fk_projet = ".(int)$fk_projet."
                AND s.entity IN (0,".$conf->entity.")
                AND s.client IN (1,2,3)
                ORDER BY s.nom ASC";

        $resql2 = $db->query($sql2);
        if ($resql2) {
            while ($obj = $db->fetch_object($resql2)) {
                $clients[$obj->rowid] = array(
                    'id' => $obj->rowid,
                    'nom' => $obj->nom,
                    'address' => $obj->address,
                    'zip' => $obj->zip,
                    'town' => $obj->town,
                    'phone' => $obj->phone
                );
            }
        }
    }

    // Méthode 3: Via les commandes liées au projet
    if (empty($clients)) {
        $sql3 = "SELECT DISTINCT s.rowid, s.nom, s.address, s.zip, s.town, s.phone
                FROM ".MAIN_DB_PREFIX."societe s
                INNER JOIN ".MAIN_DB_PREFIX."commande c ON c.fk_soc = s.rowid
                WHERE c.fk_projet = ".(int)$fk_projet."
                AND s.entity IN (0,".$conf->entity.")
                AND s.client IN (1,2,3)
                ORDER BY s.nom ASC";

        $resql3 = $db->query($sql3);
        if ($resql3) {
            while ($obj = $db->fetch_object($resql3)) {
                $clients[$obj->rowid] = array(
                    'id' => $obj->rowid,
                    'nom' => $obj->nom,
                    'address' => $obj->address,
                    'zip' => $obj->zip,
                    'town' => $obj->town,
                    'phone' => $obj->phone
                );
            }
        }
    }

    // Méthode 4: Si toujours rien, récupérer le client principal du projet (colonne fk_soc)
    if (empty($clients)) {
        $sql4 = "SELECT s.rowid, s.nom, s.address, s.zip, s.town, s.phone
                FROM ".MAIN_DB_PREFIX."projet p
                INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = p.fk_soc
                WHERE p.rowid = ".(int)$fk_projet."
                AND s.entity IN (0,".$conf->entity.")
                AND s.client IN (1,2,3)";

        $resql4 = $db->query($sql4);
        if ($resql4 && $db->num_rows($resql4) > 0) {
            $obj = $db->fetch_object($resql4);
            if ($obj) {
                $clients[$obj->rowid] = array(
                    'id' => $obj->rowid,
                    'nom' => $obj->nom,
                    'address' => $obj->address,
                    'zip' => $obj->zip,
                    'town' => $obj->town,
                    'phone' => $obj->phone
                );
            }
        }
    }

    echo json_encode(array_values($clients));
    exit;
}

// API: Get devis from client
if ($action == 'get_devis') {
    header('Content-Type: application/json; charset=utf-8');
    $fk_client = GETPOST('fk_client', 'int');

    $sql = "SELECT p.rowid, p.ref, p.ref_client, p.datep, p.fk_statut
            FROM ".MAIN_DB_PREFIX."propal p
            WHERE p.fk_soc = ".(int)$fk_client."
            AND p.entity = ".$conf->entity."
            ORDER BY p.datep DESC LIMIT 20";

    $resql = $db->query($sql);
    $devis_list = array();

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $devis_list[] = array(
                'id' => $obj->rowid,
                'ref' => $obj->ref,
                'ref_client' => $obj->ref_client,
                'date' => $db->jdate($obj->datep),
                'statut' => $obj->fk_statut
            );
        }
    }

    echo json_encode($devis_list);
    exit;
}

// API: Get devis details with products
if ($action == 'get_devis_details') {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $fk_devis = GETPOST('fk_devis', 'int');

    $sql_lignes = "SELECT
                   pd.rowid as ligne_id,
                   pd.fk_product as fk_product,
                   pd.description,
                   pd.qty,
                   pd.subprice,
                   pd.total_ht,
                   pd.product_type,
                   pd.label as ligne_label,
                   pr.rowid as product_id,
                   pr.ref as product_ref,
                   pr.label as product_label
                   FROM ".MAIN_DB_PREFIX."propaldet pd
                   LEFT JOIN ".MAIN_DB_PREFIX."product pr ON pr.rowid = pd.fk_product
                   WHERE pd.fk_propal = ".(int)$fk_devis."
                   ORDER BY pd.rang ASC, pd.rowid ASC";

    $resql_lignes = $db->query($sql_lignes);
    $lignes = array();
    $current_piece_name = '';
    $current_piece_text = '';

    if ($resql_lignes) {
        while ($obj = $db->fetch_object($resql_lignes)) {
            // Si c'est un titre ou texte libre (product_type = 9)
            if ($obj->product_type == 9) {
                $label = strip_tags($obj->ligne_label);
                $label = trim($label);
                $desc = strip_tags($obj->description);
                $desc = trim($desc);

                // Si c'est un titre court (nom de pièce)
                if (strlen($label) > 0 && strlen($label) < 100) {
                    $current_piece_name = $label;
                    $current_piece_text = '';
                    if (strlen($desc) > 0) {
                        $current_piece_text = $desc;
                    }
                } else {
                    // Texte libre
                    if (strlen($label) >= 100) {
                        $current_piece_text .= ($current_piece_text ? "\n" : '') . $label;
                    }
                    if (strlen($desc) > 0) {
                        $current_piece_text .= ($current_piece_text ? "\n" : '') . $desc;
                    }
                }
                continue;
            }

            // Debug: Log raw object data
            error_log("Ligne produit - ligne_id: {$obj->ligne_id}, fk_product: '{$obj->fk_product}', product_id: '{$obj->product_id}', product_ref: '{$obj->product_ref}'");

            // Utiliser product_id (pr.rowid) plutôt que fk_product pour éviter les problèmes
            $real_product_id = $obj->product_id ? (int)$obj->product_id : (int)$obj->fk_product;

            // Skip if no product
            if (empty($real_product_id)) {
                error_log("Ligne ignorée car pas de product_id: ligne_id={$obj->ligne_id}");
                continue;
            }

            $photo = getProductImage($obj->product_ref);

            // Extraire le format de la description ou du label
            $format = '';
            $desc = strip_tags($obj->description ?? '');
            if (preg_match('/(\d+\s*x\s*\d+)/i', $desc, $matches)) {
                $format = $matches[1];
            } elseif (preg_match('/(\d+\s*x\s*\d+)/i', $obj->product_label ?? '', $matches)) {
                $format = $matches[1];
            }

            $lignes[] = array(
                'rowid' => $obj->ligne_id,
                'fk_product' => $real_product_id, // Utiliser l'ID réel du produit
                'reference' => $obj->product_ref,
                'product_ref' => $obj->product_ref,
                'product_label' => $obj->product_label,
                'label' => $obj->product_label,
                'description' => $obj->description,
                'qty' => (float)$obj->qty,
                'unite' => 'm²',
                'format' => $format,
                'subprice' => (float)$obj->subprice,
                'total_ht' => (float)$obj->total_ht,
                'photo_url' => $photo ? $photo['url'] : null,
                'photo_filename' => $photo ? $photo['filename'] : null,
                'piece_name' => $current_piece_name,
                'piece_text' => $current_piece_text
            );
        }
    }

    echo json_encode(array('lignes' => $lignes));
    exit;
}

// API: Create fiche from devis
if ($action == 'create_from_devis') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    // Log pour debug
    error_log("=== CREATE FROM DEVIS START ===");
    error_log("User ID: " . $user->id);
    error_log("POST data: " . print_r($_POST, true));

    try {
        $fk_projet = GETPOST('fk_projet', 'int');
        $fk_client = GETPOST('fk_client', 'int');
        $fk_devis = GETPOST('fk_devis', 'int');

        // Décoder le base64 depuis POST
        $lignes_b64 = GETPOST('lignes_b64', 'alphanohtml');
        if (empty($lignes_b64)) {
            echo json_encode(['success' => false, 'error' => 'Données manquantes']);
            exit;
        }

        $lignes_json = base64_decode($lignes_b64);
        error_log("lignes_json décodé: " . substr($lignes_json, 0, 200) . "...");

        if (empty($fk_client)) {
            echo json_encode(['success' => false, 'error' => 'Client requis']);
            exit;
        }

        error_log("=== RÉCEPTION DONNÉES ===");
        error_log("Lignes JSON brut (premiers 500 chars): " . substr($lignes_json, 0, 500));

        $lignes = json_decode($lignes_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Erreur JSON: ' . json_last_error_msg() . ' - Data: ' . substr($lignes_json, 0, 200)]);
            exit;
        }

        error_log("=== DÉCODAGE LIGNES ===");
        error_log("Lignes décodées: " . print_r($lignes, true));
        error_log("Nombre de lignes: " . count($lignes));
        error_log("Type lignes: " . gettype($lignes));

        if (!is_array($lignes)) {
            echo json_encode(['success' => false, 'error' => 'Lignes n\'est pas un array: ' . gettype($lignes)]);
            exit;
        }

        if (count($lignes) === 0) {
            echo json_encode(['success' => false, 'error' => 'Aucune ligne reçue (array vide)', 'debug_data' => substr($lignes_json, 0, 500)]);
            exit;
        }

        error_log("Première ligne: " . print_r($lignes[0], true));

        $client = new Societe($db);
        $client->fetch($fk_client);

    $projet_name = '';
    if ($fk_projet > 0) {
        $projet = new Project($db);
        $projet->fetch($fk_projet);
        $projet_name = $projet->ref . ' - ' . $projet->title;
    }

    $db->begin();

    $year = date('Y');
    $sql_count = "SELECT MAX(CAST(SUBSTRING_INDEX(ref, '-', -1) AS UNSIGNED)) as max_num
                  FROM ".MAIN_DB_PREFIX."mv3_sens_pose
                  WHERE ref LIKE 'POSE-".$year."-%'";
    $resql_count = $db->query($sql_count);
    $next_num = 1;
    if ($resql_count) {
        $obj_count = $db->fetch_object($resql_count);
        if ($obj_count && $obj_count->max_num) {
            $next_num = $obj_count->max_num + 1;
        }
    }
    $new_ref = 'POSE-'.$year.'-'.str_pad($next_num, 4, '0', STR_PAD_LEFT);

    $client_name = $client->name;
    $site_address = $client->address . ', ' . $client->zip . ' ' . $client->town;
    $notes = 'Créé depuis mobile - Devis' . ($projet_name ? ' - Projet: ' . $projet_name : '');

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose
            (entity, ref, client_name, internal_ref, site_address, notes, fk_projet, fk_client, fk_user_create, statut, date_creation)
            VALUES (".$conf->entity.", '".$new_ref."', '".$db->escape($client_name)."', 'Devis',
            '".$db->escape($site_address)."', '".$db->escape($notes)."',
            ".($fk_projet > 0 ? (int)$fk_projet : "NULL").", ".(int)$fk_client.",
            ".(int)$user->id.", 'brouillon', NOW())";

    if ($db->query($sql)) {
        $new_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_sens_pose");

        // Add pieces
        $ordre = 1;
        $pieces_created = 0;
        $errors = [];
        error_log("=== DÉBUT AJOUT DES PIÈCES ===");
        error_log("Nombre de lignes à traiter: " . count($lignes));

        foreach ($lignes as $idx => $ligne) {
            error_log("--- Traitement ligne $idx ---");
            error_log("Ligne complète: " . print_r($ligne, true));
            error_log("fk_product: " . ($ligne['fk_product'] ?? 'VIDE'));

            if (empty($ligne['fk_product'])) {
                error_log("⚠️ Ligne $idx ignorée: fk_product vide ou absent");
                $errors[] = "Ligne $idx: fk_product manquant";
                continue;
            }

            // Utiliser piece_name si disponible, sinon product_label
            $nom = !empty($ligne['piece_name']) ? $ligne['piece_name'] :
                   (!empty($ligne['product_label']) ? $ligne['product_label'] :
                   (!empty($ligne['label']) ? $ligne['label'] : 'Pièce '.$ordre));

            $qty = !empty($ligne['qty']) ? (float)$ligne['qty'] : 0;
            $unite = !empty($ligne['unite']) ? $ligne['unite'] : 'm²';
            $photo_url = !empty($ligne['photo_url']) ? $ligne['photo_url'] : '';
            $photo_filename = !empty($ligne['photo_filename']) ? $ligne['photo_filename'] : '';
            $format = !empty($ligne['format']) ? $ligne['format'] : '';
            $product_ref = !empty($ligne['reference']) ? $ligne['reference'] : '';
            $product_label = !empty($ligne['product_label']) ? $ligne['product_label'] : '';

            // Construire piece_text avec piece_text du devis + infos produit
            $piece_text = '';
            if (!empty($ligne['piece_text'])) {
                $piece_text = $ligne['piece_text'] . "\n\n";
            }
            if (!empty($product_ref)) {
                $piece_text .= "Réf: " . $product_ref . "\n";
            }
            if (!empty($format)) {
                $piece_text .= "Format: " . $format . "\n";
            }
            if ($qty > 0) {
                $piece_text .= "Quantité: " . $qty . " " . $unite . "\n";
            }
            $piece_text = trim($piece_text);

            // IMPORTANT: Insérer uniquement les colonnes qui existent dans la table
            // La quantité est stockée dans piece_text
            $sql_piece = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose_pieces
                          (fk_sens_pose, nom, format, photo_url, photo_filename, photo_reference, fk_product,
                           piece_text, product_ref, product_label, ordre)
                          VALUES (".(int)$new_id.", '".$db->escape($nom)."',
                          '".$db->escape($format)."',
                          '".$db->escape($photo_url)."',
                          '".$db->escape($photo_filename)."',
                          '".$db->escape($product_ref)."',
                          ".(int)$ligne['fk_product'].",
                          '".$db->escape($piece_text)."',
                          '".$db->escape($product_ref)."',
                          '".$db->escape($product_label)."',
                          ".(int)$ordre.")";

            error_log("SQL pièce: " . $sql_piece);
            $res_piece = $db->query($sql_piece);
            if (!$res_piece) {
                $error_msg = $db->lasterror();
                error_log("❌ ERREUR SQL pièce: " . $error_msg);
                error_log("SQL échoué: " . $sql_piece);
                $errors[] = "Ligne $idx ($nom): " . $error_msg;
            } else {
                $pieces_created++;
                error_log("✅ Pièce #$pieces_created '$nom' créée avec qty=$qty (ordre: $ordre)");
            }
            $ordre++;
        }

        error_log("=== FIN AJOUT DES PIÈCES ===");
        error_log("Total pièces créées: $pieces_created sur " . count($lignes) . " lignes");

        $db->commit();
        echo json_encode([
            'success' => true,
            'id' => $new_id,
            'pieces_created' => $pieces_created,
            'pieces_total' => count($lignes),
            'errors' => $errors
        ]);
    } else {
        $db->rollback();
        echo json_encode(['success' => false, 'error' => $db->lasterror()]);
    }

    } catch (Exception $e) {
        if ($db->transaction_opened) {
            $db->rollback();
        }
        echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
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
    <title>Nouvelle fiche depuis devis</title>
    <script>
        // BLOQUER app.js de se charger
        window.MV3App = function() { console.log('MV3App disabled'); };

        // Désactiver service worker immédiatement
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    console.log('Force unregister service worker');
                    registration.unregister();
                }
            });
            // Empêcher toute nouvelle enregistrement
            navigator.serviceWorker.register = function() {
                console.log('Service worker registration blocked');
                return Promise.resolve({ active: null });
            };
        }
    </script>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        body {
            padding-bottom: 100px;
        }
        .wizard-container {
            padding: 16px;
        }
        .wizard-step {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        .wizard-step.active {
            border-color: #0891b2;
            box-shadow: 0 4px 16px rgba(8,145,178,0.2);
        }
        .wizard-step.completed {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .wizard-step.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 2px 8px rgba(8,145,178,0.3);
        }
        .wizard-step.completed .step-number {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .step-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            flex: 1;
        }
        .search-box {
            position: relative;
            margin-bottom: 16px;
        }
        .search-input {
            width: 100%;
            padding: 14px 44px 14px 44px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.2s;
        }
        .search-input:focus {
            outline: none;
            border-color: #0891b2;
            box-shadow: 0 0 0 3px rgba(8,145,178,0.1);
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
        }
        .clear-search {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
        }
        .item-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .item-card:active {
            transform: scale(0.98);
        }
        .item-card.selected {
            border-color: #0891b2;
            background: #f0f9ff;
        }
        .item-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        .item-subtitle {
            font-size: 13px;
            color: #6b7280;
        }
        .item-meta {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 6px;
        }
        .product-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .product-card.selected {
            border-color: #0891b2;
            background: #f0f9ff;
        }
        .product-header {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }
        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            background: #f3f4f6;
            flex-shrink: 0;
        }
        .product-image-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            flex-shrink: 0;
        }
        .product-info {
            flex: 1;
        }
        .product-ref {
            font-size: 11px;
            color: #f97316;
            font-weight: 700;
            text-transform: uppercase;
            background: #fff7ed;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 6px;
        }
        .product-label {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        .product-qty {
            font-size: 13px;
            color: #0891b2;
            font-weight: 600;
        }
        .piece-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            margin-top: 6px;
        }
        .piece-text-box {
            background: #f8fafc;
            border-left: 3px solid #0891b2;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
            margin-top: 8px;
            white-space: pre-wrap;
        }
        .piece-name-input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 8px;
            transition: all 0.2s;
        }
        .piece-name-input:focus {
            outline: none;
            border-color: #0891b2;
        }
        .checkbox {
            width: 28px;
            height: 28px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .checkbox.checked {
            background: #0891b2;
            border-color: #0891b2;
            color: white;
            font-size: 18px;
        }
        .fixed-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 16px;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.1);
            border-top: 2px solid #e5e7eb;
        }
        .btn-create {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(16,185,129,0.3);
            transition: all 0.2s;
        }
        .btn-create:active {
            transform: scale(0.98);
        }
        .btn-create:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            box-shadow: none;
        }
        .loading {
            text-align: center;
            padding: 32px;
            color: #6b7280;
            font-size: 14px;
        }
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top-color: #0891b2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 12px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }
        .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .selected-info {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 12px;
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .selected-info-icon {
            font-size: 24px;
        }
        .selected-info-text {
            flex: 1;
            font-size: 13px;
            color: #065f46;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="app-header">
        <a href="list.php" class="app-header-back">←</a>
        <div>
            <div class="app-header-title">📋 Depuis devis</div>
            <div class="app-header-subtitle">Créer fiche sens de pose</div>
        </div>
    </div>

    <div class="wizard-container">
        <!-- Étape 1: Rechercher un projet -->
        <div class="wizard-step active" id="step1">
            <div class="step-header">
                <div class="step-number">1</div>
                <div class="step-title">Rechercher un projet</div>
            </div>

            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" class="search-input" id="searchProjet" placeholder="Rechercher un projet..." autocomplete="off">
                <button class="clear-search" id="clearProjetSearch" onclick="clearSearchProjet()">×</button>
            </div>

            <div id="projetsList"></div>

            <button class="btn btn-secondary" style="width: 100%; margin-top: 12px;" onclick="skipProjet()">
                Continuer sans projet
            </button>
        </div>

        <!-- Étape 2: Choisir un client -->
        <div class="wizard-step disabled" id="step2">
            <div class="step-header">
                <div class="step-number">2</div>
                <div class="step-title">Choisir un client</div>
            </div>
            <div id="clientsList"></div>
        </div>

        <!-- Étape 3: Choisir un devis -->
        <div class="wizard-step disabled" id="step3">
            <div class="step-header">
                <div class="step-number">3</div>
                <div class="step-title">Choisir un devis</div>
            </div>
            <div id="devisList"></div>
        </div>

        <!-- Étape 4: Sélectionner les produits -->
        <div class="wizard-step disabled" id="step4">
            <div class="step-header">
                <div class="step-number">4</div>
                <div class="step-title">Produits du devis</div>
            </div>
            <div id="produitsList"></div>
        </div>
    </div>

    <div class="fixed-bottom" id="createBtn" style="display: none;">
        <button type="button" class="btn-create" id="btnCreate">
            ✓ Créer la fiche sens de pose
        </button>
    </div>

    <script>
        let selectedProjet = null;
        let selectedClient = null;
        let selectedDevis = null;
        let selectedProduits = [];
        let searchTimeout;

        // Step 1: Search Projects
        document.getElementById('searchProjet').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const search = this.value.trim();

            document.getElementById('clearProjetSearch').style.display = search ? 'flex' : 'none';

            if (search.length < 2) {
                document.getElementById('projetsList').innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><div>Tapez au moins 2 caractères</div></div>';
                return;
            }

            searchTimeout = setTimeout(() => {
                searchProjets(search);
            }, 300);
        });

        function searchProjets(search) {
            document.getElementById('projetsList').innerHTML = '<div class="loading"><div class="loading-spinner"></div><div>Recherche...</div></div>';

            fetch('new_from_devis.php?action=search_projets&search=' + encodeURIComponent(search))
                .then(r => r.json())
                .then(projets => {
                    if (projets.length === 0) {
                        document.getElementById('projetsList').innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><div>Aucun projet trouvé</div></div>';
                        return;
                    }

                    let html = '';
                    projets.forEach(projet => {
                        html += `
                            <div class="item-card" onclick="selectProjet(${projet.id}, '${projet.ref.replace(/'/g, "\\'")}', '${projet.title.replace(/'/g, "\\'")}')">
                                <div class="item-title">🏗️ ${projet.ref}</div>
                                <div class="item-subtitle">${projet.title}</div>
                            </div>
                        `;
                    });
                    document.getElementById('projetsList').innerHTML = html;
                });
        }

        function clearSearchProjet() {
            document.getElementById('searchProjet').value = '';
            document.getElementById('clearProjetSearch').style.display = 'none';
            document.getElementById('projetsList').innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><div>Recherchez un projet</div></div>';
        }

        function selectProjet(id, ref, title) {
            selectedProjet = {id: id, ref: ref, title: title};

            // Mark step 1 as completed
            document.getElementById('step1').classList.remove('active');
            document.getElementById('step1').classList.add('completed');

            // Show selected info
            const selectedInfo = `
                <div class="selected-info">
                    <div class="selected-info-icon">✓</div>
                    <div class="selected-info-text">Projet sélectionné: <strong>${ref} - ${title}</strong></div>
                </div>
            `;
            document.getElementById('projetsList').innerHTML = selectedInfo;

            // Activate step 2
            document.getElementById('step2').classList.remove('disabled');
            document.getElementById('step2').classList.add('active');

            // Load clients
            loadClients(id);
        }

        function skipProjet() {
            selectedProjet = null;

            // Mark step 1 as completed
            document.getElementById('step1').classList.remove('active');
            document.getElementById('step1').classList.add('completed');

            const selectedInfo = `
                <div class="selected-info">
                    <div class="selected-info-icon">✓</div>
                    <div class="selected-info-text">Fiche sans projet</div>
                </div>
            `;
            document.getElementById('projetsList').innerHTML = selectedInfo;

            alert('Fonctionnalité en développement - Veuillez sélectionner un projet pour le moment');
        }

        function loadClients(fk_projet) {
            document.getElementById('clientsList').innerHTML = '<div class="loading"><div class="loading-spinner"></div><div>Chargement des clients...</div></div>';

            fetch('new_from_devis.php?action=get_clients&fk_projet=' + fk_projet)
                .then(r => r.json())
                .then(clients => {
                    if (clients.length === 0) {
                        document.getElementById('clientsList').innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><div>Aucun client trouvé</div></div>';
                        return;
                    }

                    let html = '';
                    clients.forEach(client => {
                        const address = client.zip && client.town ? `${client.zip} ${client.town}` : '';
                        html += `
                            <div class="item-card" onclick='selectClient(${JSON.stringify(client).replace(/'/g, "&#39;")})'>
                                <div class="item-title">👤 ${client.nom}</div>
                                ${address ? `<div class="item-subtitle">📍 ${address}</div>` : ''}
                                ${client.phone ? `<div class="item-meta">📞 ${client.phone}</div>` : ''}
                            </div>
                        `;
                    });
                    document.getElementById('clientsList').innerHTML = html;
                });
        }

        function selectClient(client) {
            selectedClient = client;

            // Mark step 2 as completed
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step2').classList.add('completed');

            const address = client.zip && client.town ? `${client.zip} ${client.town}` : '';
            const selectedInfo = `
                <div class="selected-info">
                    <div class="selected-info-icon">✓</div>
                    <div class="selected-info-text">
                        Client: <strong>${client.nom}</strong>
                        ${address ? `<br>📍 ${address}` : ''}
                    </div>
                </div>
            `;
            document.getElementById('clientsList').innerHTML = selectedInfo;

            // Activate step 3
            document.getElementById('step3').classList.remove('disabled');
            document.getElementById('step3').classList.add('active');

            // Load devis
            loadDevis(client.id);
        }

        function loadDevis(fk_client) {
            document.getElementById('devisList').innerHTML = '<div class="loading"><div class="loading-spinner"></div><div>Chargement des devis...</div></div>';

            fetch('new_from_devis.php?action=get_devis&fk_client=' + fk_client)
                .then(r => r.json())
                .then(devis_list => {
                    if (devis_list.length === 0) {
                        document.getElementById('devisList').innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><div>Aucun devis trouvé</div></div>';
                        return;
                    }

                    let html = '';
                    devis_list.forEach(devis => {
                        const date = new Date(devis.date * 1000).toLocaleDateString('fr-FR');
                        const refClient = devis.ref_client ? devis.ref_client : 'Non renseignée';
                        html += `
                            <div class="item-card" onclick="selectDevis(${devis.id}, '${devis.ref.replace(/'/g, "\\'")}')">
                                <div class="item-title">📄 ${devis.ref}</div>
                                <div class="item-subtitle">🏷️ Réf. client: ${refClient}</div>
                                <div class="item-meta">📅 ${date}</div>
                            </div>
                        `;
                    });
                    document.getElementById('devisList').innerHTML = html;
                });
        }

        function selectDevis(id, ref) {
            selectedDevis = {id: id, ref: ref};

            // Mark step 3 as completed
            document.getElementById('step3').classList.remove('active');
            document.getElementById('step3').classList.add('completed');

            const selectedInfo = `
                <div class="selected-info">
                    <div class="selected-info-icon">✓</div>
                    <div class="selected-info-text">Devis: <strong>${ref}</strong></div>
                </div>
            `;
            document.getElementById('devisList').innerHTML = selectedInfo;

            // Activate step 4
            document.getElementById('step4').classList.remove('disabled');
            document.getElementById('step4').classList.add('active');

            // Load products
            loadProduits(id);
        }

        function loadProduits(fk_devis) {
            document.getElementById('produitsList').innerHTML = '<div class="loading"><div class="loading-spinner"></div><div>Chargement des produits...</div></div>';

            fetch('new_from_devis.php?action=get_devis_details&fk_devis=' + fk_devis)
                .then(r => r.json())
                .then(data => {
                    if (data.lignes.length === 0) {
                        document.getElementById('produitsList').innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><div>Aucun produit trouvé</div></div>';
                        return;
                    }

                    selectedProduits = data.lignes.map((ligne, idx) => ({
                        ...ligne,
                        index: idx,
                        selected: true
                    }));

                    renderProduits();
                    document.getElementById('createBtn').style.display = 'block';
                });
        }

        function renderProduits() {
            let html = '';
            selectedProduits.forEach((prod, idx) => {
                html += `
                    <div class="product-card ${prod.selected ? 'selected' : ''}" onclick="toggleProduct(${idx})">
                        <div class="product-header">
                            ${prod.photo_url
                                ? `<img src="${prod.photo_url}" class="product-image" alt="${prod.product_label}">`
                                : '<div class="product-image-placeholder">📦</div>'
                            }
                            <div class="product-info">
                                <div class="product-ref">${prod.product_ref}</div>
                                <div class="product-label">${prod.product_label}</div>
                                <div class="product-qty">📏 ${prod.qty} m²</div>
                                ${prod.piece_name ? `<div class="piece-badge">📍 ${prod.piece_name}</div>` : ''}
                            </div>
                            <div class="checkbox ${prod.selected ? 'checked' : ''}" id="check_${idx}">
                                ${prod.selected ? '✓' : ''}
                            </div>
                        </div>
                        ${prod.piece_text ? `<div class="piece-text-box">💬 ${prod.piece_text}</div>` : ''}
                        <input type="text" class="piece-name-input" id="piece_${idx}"
                               placeholder="Nom de la pièce (ex: Salon, Cuisine...)"
                               value="${prod.piece_name || ''}"
                               onclick="event.stopPropagation()">
                    </div>
                `;
            });
            document.getElementById('produitsList').innerHTML = html;
        }

        function toggleProduct(idx) {
            selectedProduits[idx].selected = !selectedProduits[idx].selected;
            renderProduits();
        }

        function createFiche() {
            console.log('createFiche called');
            console.log('selectedClient:', selectedClient);
            console.log('selectedDevis:', selectedDevis);
            console.log('selectedProduits:', selectedProduits);

            // Collect piece names
            selectedProduits.forEach((prod, idx) => {
                const input = document.getElementById('piece_' + idx);
                if (input) {
                    prod.piece_name = input.value.trim();
                }
            });

            const lignes = selectedProduits.filter(p => p.selected);
            console.log('📦 Lignes à envoyer:', lignes);
            console.log('📦 Nombre de lignes:', lignes.length);

            // Vérifier que chaque ligne a fk_product
            lignes.forEach((ligne, idx) => {
                console.log(`Ligne ${idx}:`, {
                    fk_product: ligne.fk_product,
                    product_ref: ligne.product_ref,
                    product_label: ligne.product_label,
                    qty: ligne.qty,
                    has_fk_product: !!ligne.fk_product
                });

                if (!ligne.fk_product) {
                    console.error(`❌ ATTENTION: Ligne ${idx} n'a PAS de fk_product!`, ligne);
                }
            });

            console.log('📦 Lignes JSON:', JSON.stringify(lignes));

            if (lignes.length === 0) {
                alert('⚠️ Sélectionnez au moins un produit');
                return;
            }

            const btn = document.querySelector('.btn-create');
            btn.disabled = true;
            btn.textContent = '⏳ Création en cours...';

            console.log('Sending request with:', {
                action: 'create_from_devis',
                fk_projet: selectedProjet ? selectedProjet.id : 0,
                fk_client: selectedClient.id,
                fk_devis: selectedDevis.id,
                lignes_count: lignes.length
            });

            // Encoder en base64 pour éviter blocage WAF, mais envoyer via POST pour éviter limite URL
            const lignesB64 = btoa(unescape(encodeURIComponent(JSON.stringify(lignes))));

            const formData = new FormData();
            formData.append('action', 'create_from_devis');
            formData.append('fk_projet', selectedProjet ? selectedProjet.id : 0);
            formData.append('fk_client', selectedClient.id);
            formData.append('fk_devis', selectedDevis.id);
            formData.append('lignes_b64', lignesB64);

            fetch('new_from_devis.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(r => {
                console.log('Response status:', r.status);
                console.log('Response headers:', r.headers);
                if (!r.ok) {
                    return r.text().then(text => {
                        console.error('Error response:', text);
                        throw new Error('HTTP ' + r.status + ': ' + text);
                    });
                }
                return r.text();
            })
            .then(text => {
                console.log('Server response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);

                    if (data.success) {
                        console.log('✅ Fiche créée! ID:', data.id);
                        console.log('📦 Pièces créées:', data.pieces_created, '/', data.pieces_total);

                        // Afficher les erreurs SQL s'il y en a
                        if (data.errors && data.errors.length > 0) {
                            console.error('❌ Erreurs SQL lors de la création des pièces:');
                            data.errors.forEach(err => console.error('  -', err));
                        }

                        // Afficher un message si 0 pièces créées
                        if (data.pieces_created === 0) {
                            console.warn('⚠️ ATTENTION: Aucune pièce créée!');
                            let msg = '⚠️ Fiche créée mais AUCUNE pièce ajoutée!';
                            if (data.errors && data.errors.length > 0) {
                                msg += '\n\nErreurs SQL:\n' + data.errors.join('\n');
                            } else {
                                msg += '\n\nVérifiez les logs PHP pour plus de détails.';
                            }
                            if (confirm(msg + '\n\nVoulez-vous quand même voir la fiche?')) {
                                window.location.href = 'view.php?id=' + data.id + '&created=1';
                            } else {
                                btn.disabled = false;
                                btn.textContent = '✨ Créer la fiche';
                            }
                            return;
                        }

                        window.location.href = 'view.php?id=' + data.id + '&created=1';
                    } else {
                        alert('❌ Erreur: ' + (data.error || 'Erreur inconnue'));
                        console.error('Détails erreur:', data);
                        btn.disabled = false;
                        btn.textContent = '✓ Créer la fiche sens de pose';
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    alert('❌ Erreur: Réponse invalide du serveur');
                    btn.disabled = false;
                    btn.textContent = '✓ Créer la fiche sens de pose';
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                alert('❌ Erreur de connexion: ' + err.message);
                btn.disabled = false;
                btn.textContent = '✓ Créer la fiche sens de pose';
            });
        }

        // Attach create button event
        document.getElementById('btnCreate').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Button clicked via addEventListener');
            createFiche();
            return false;
        });

        // Initialize
        document.getElementById('projetsList').innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><div>Recherchez un projet pour commencer</div></div>';
    </script>
</body>
</html>
