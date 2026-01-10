<?php
/**
 * AJAX - Récupération des clients liés à un projet
 * Inclut le client principal + tous les contacts/acheteurs liés
 */

require_once __DIR__ . '/../mobile_app/includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../mobile_app/includes/api_helpers.php';
require_once __DIR__ . '/../mobile_app/includes/auth_helpers.php';
require_once __DIR__ . '/../mobile_app/includes/db_helpers.php';

loadDolibarr();
setupApiHeaders();

global $db, $user;

requireUserRights('mv3pro_portail', 'read');

$projet_id = GETPOST('projet_id', 'int');
$client_id = GETPOST('client_id', 'int');
$debug = GETPOST('debug', 'int');

if (!$projet_id) {
    echo json_encode(array('success' => false, 'error' => 'Projet ID manquant'));
    exit;
}

// Si on demande les infos d'un client spécifique
if ($client_id) {
    $sql = "SELECT s.rowid, s.nom, s.address, s.zip, s.town, s.phone, s.email";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql .= " WHERE s.rowid = ".(int)$client_id;
    $sql .= " AND s.entity IN (".getEntity('societe').")";

    $resql = $db->query($sql);

    if ($resql && $obj = $db->fetch_object($resql)) {
        jsonSuccess(['client' => formatClientData($obj)]);
    } else {
        jsonError('Client non trouvé', 404);
    }
}

// Récupérer les clients qui ont des DEVIS ACCEPTÉS OU FACTURES dans le projet
$clients = array();
$debug_info = array();

// Requête 1 : clients avec devis acceptés/facturés liés au projet
// Statut 2 = Signé, 3 = Pas commencé, 4 = Facturé partiellement, 5 = Facturé totalement
$sql = "SELECT DISTINCT s.rowid, s.nom, s.address, s.zip, s.town, s.phone, s.email";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."propal as pr ON pr.fk_soc = s.rowid";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."element_element as ee ON (";
$sql .= "   (ee.fk_source = pr.rowid AND ee.sourcetype = 'propal' AND ee.targettype = 'project' AND ee.fk_target = ".(int)$projet_id.")";
$sql .= "   OR (ee.fk_target = pr.rowid AND ee.targettype = 'propal' AND ee.sourcetype = 'project' AND ee.fk_source = ".(int)$projet_id.")";
$sql .= " )";
$sql .= " WHERE pr.fk_statut IN (2,3,4,5)"; // Accepté, en cours, ou facturé
$sql .= " AND s.entity IN (".getEntity('societe').")";
$sql .= " AND s.client IN (1,2,3)"; // Seulement les clients

if ($debug) {
    $debug_info['sql1'] = $sql;
}

$resql = $db->query($sql);
if ($debug) {
    $debug_info['sql1_rows'] = $resql ? $db->num_rows($resql) : 0;
    $debug_info['sql1_error'] = $resql ? null : $db->lasterror();
}
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $clients[] = formatClientData($obj);
    }
}

// Alternative : Si pas de devis liés via element_element, chercher les devis du projet directement
// (cas où le projet a un fk_projet sur le devis)
$sql2 = "SELECT DISTINCT s.rowid, s.nom, s.address, s.zip, s.town, s.phone, s.email";
$sql2 .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql2 .= " INNER JOIN ".MAIN_DB_PREFIX."propal as pr ON pr.fk_soc = s.rowid";
$sql2 .= " WHERE pr.fk_projet = ".(int)$projet_id;
$sql2 .= " AND pr.fk_statut IN (2,3,4,5)"; // Accepté, en cours, ou facturé
$sql2 .= " AND s.entity IN (".getEntity('societe').")";
$sql2 .= " AND s.client IN (1,2,3)";

if ($debug) {
    $debug_info['sql2'] = $sql2;
}

$resql2 = $db->query($sql2);
if ($debug) {
    $debug_info['sql2_rows'] = $resql2 ? $db->num_rows($resql2) : 0;
    $debug_info['sql2_error'] = $resql2 ? null : $db->lasterror();
}
if ($resql2) {
    while ($obj = $db->fetch_object($resql2)) {
        // Éviter les doublons
        $already_added = false;
        foreach ($clients as $existing) {
            if ($existing['rowid'] == $obj->rowid) {
                $already_added = true;
                break;
            }
        }

        if (!$already_added) {
            $clients[] = formatClientData($obj);
        }
    }
}

// Si toujours aucun client, chercher via les FACTURES liées au projet
if (count($clients) == 0) {
    $sql3 = "SELECT DISTINCT s.rowid, s.nom, s.address, s.zip, s.town, s.phone, s.email";
    $sql3 .= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql3 .= " INNER JOIN ".MAIN_DB_PREFIX."facture as f ON f.fk_soc = s.rowid";
    $sql3 .= " WHERE f.fk_projet = ".(int)$projet_id;
    $sql3 .= " AND f.fk_statut IN (1,2)"; // Validée ou payée
    $sql3 .= " AND s.entity IN (".getEntity('societe').")";
    $sql3 .= " AND s.client IN (1,2,3)";

    if ($debug) {
        $debug_info['sql3'] = $sql3;
    }

    $resql3 = $db->query($sql3);
    if ($debug) {
        $debug_info['sql3_rows'] = $resql3 ? $db->num_rows($resql3) : 0;
        $debug_info['sql3_error'] = $resql3 ? null : $db->lasterror();
    }

    if ($resql3) {
        while ($obj = $db->fetch_object($resql3)) {
            $clients[] = formatClientData($obj);
        }
    }
}

$response = [
    'success' => count($clients) > 0,
    'clients' => $clients,
    'count' => count($clients)
];

if (!$response['success']) {
    $response['error'] = 'Aucun client trouvé pour ce projet (aucun devis accepté/facturé)';
}

if ($debug) {
    $response['debug'] = $debug_info;
}

jsonResponse($response);
