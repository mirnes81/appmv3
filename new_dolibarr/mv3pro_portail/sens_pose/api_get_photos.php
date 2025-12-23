<?php
/**
 * API pour récupérer les photos des devis d'un client/projet
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

if (!$res) die(json_encode(['error' => 'Include of main fails']));

header('Content-Type: application/json');

$fk_client = GETPOST('fk_client', 'int');
$fk_projet = GETPOST('fk_projet', 'int');

if (!$fk_client && !$fk_projet) {
    echo json_encode(['error' => 'Client ou projet requis']);
    exit;
}

$photos = [];

$sql = "SELECT DISTINCT
        ec.ref as ecm_ref,
        ec.fullpath_orig,
        ec.filename,
        ec.filepath,
        ec.label,
        ec.gen_or_uploaded,
        ec.extraparams,
        p.ref as propal_ref,
        p.rowid as propal_id
        FROM ".MAIN_DB_PREFIX."ecm_files ec
        LEFT JOIN ".MAIN_DB_PREFIX."propal p ON (
            ec.src_object_type = 'propal'
            AND ec.src_object_id = p.rowid
        )
        WHERE 1=1
        AND ec.filename IS NOT NULL
        AND ec.filename != ''
        AND (
            ec.filename LIKE '%.jpg'
            OR ec.filename LIKE '%.jpeg'
            OR ec.filename LIKE '%.png'
            OR ec.filename LIKE '%.gif'
            OR ec.filename LIKE '%.JPG'
            OR ec.filename LIKE '%.JPEG'
            OR ec.filename LIKE '%.PNG'
        )";

if ($fk_client > 0) {
    $sql .= " AND p.fk_soc = ".(int)$fk_client;
}

if ($fk_projet > 0) {
    $sql .= " AND p.fk_projet = ".(int)$fk_projet;
}

$sql .= " ORDER BY ec.date_c DESC LIMIT 100";

$resql = $db->query($sql);

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $doc_url = DOL_URL_ROOT.'/document.php?modulepart=propal&file='.urlencode($obj->propal_ref.'/'.$obj->filename);

        $photos[] = [
            'id' => $obj->ecm_ref,
            'filename' => $obj->filename,
            'label' => $obj->label ?: $obj->filename,
            'url' => $doc_url,
            'propal_ref' => $obj->propal_ref,
            'propal_id' => $obj->propal_id,
            'filepath' => $obj->filepath,
            'fullpath' => $obj->fullpath_orig
        ];
    }
}

$sql2 = "SELECT DISTINCT
        ec.ref as ecm_ref,
        ec.fullpath_orig,
        ec.filename,
        ec.filepath,
        ec.label,
        c.ref as commande_ref,
        c.rowid as commande_id
        FROM ".MAIN_DB_PREFIX."ecm_files ec
        LEFT JOIN ".MAIN_DB_PREFIX."commande c ON (
            ec.src_object_type = 'order'
            AND ec.src_object_id = c.rowid
        )
        WHERE 1=1
        AND ec.filename IS NOT NULL
        AND ec.filename != ''
        AND (
            ec.filename LIKE '%.jpg'
            OR ec.filename LIKE '%.jpeg'
            OR ec.filename LIKE '%.png'
            OR ec.filename LIKE '%.gif'
            OR ec.filename LIKE '%.JPG'
            OR ec.filename LIKE '%.JPEG'
            OR ec.filename LIKE '%.PNG'
        )";

if ($fk_client > 0) {
    $sql2 .= " AND c.fk_soc = ".(int)$fk_client;
}

if ($fk_projet > 0) {
    $sql2 .= " AND c.fk_projet = ".(int)$fk_projet;
}

$sql2 .= " ORDER BY ec.date_c DESC LIMIT 100";

$resql2 = $db->query($sql2);

if ($resql2) {
    while ($obj = $db->fetch_object($resql2)) {
        $doc_url = DOL_URL_ROOT.'/document.php?modulepart=commande&file='.urlencode($obj->commande_ref.'/'.$obj->filename);

        $photos[] = [
            'id' => $obj->ecm_ref,
            'filename' => $obj->filename,
            'label' => $obj->label ?: $obj->filename,
            'url' => $doc_url,
            'commande_ref' => $obj->commande_ref,
            'commande_id' => $obj->commande_id,
            'filepath' => $obj->filepath,
            'fullpath' => $obj->fullpath_orig
        ];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($photos),
    'photos' => $photos
]);

$db->close();
