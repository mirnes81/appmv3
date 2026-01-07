<?php
/**
 * API v1 - Rapports - Détail
 *
 * GET /api/v1/rapports_view.php?id=123
 *
 * Retourne le détail d'un rapport avec:
 * - Informations du rapport
 * - Photos liées
 * - Frais liés
 * - GPS
 * - Auteur
 * - Projet/Tiers si disponible
 */

require_once __DIR__.'/_bootstrap.php';

// Auth requise
$auth = require_auth();

// Méthode GET uniquement
require_method('GET');

// Paramètres
$rapport_id = (int)get_param('id', 0);
require_param($rapport_id, 'id');

// Récupérer le rapport
$sql = "SELECT r.*,
        u.lastname, u.firstname, u.login,
        p.ref as projet_ref, p.title as projet_title,
        t.nom as tiers_nom, t.code_client";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_rapport as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as t ON t.rowid = p.fk_soc";
$sql .= " WHERE r.rowid = ".(int)$rapport_id;
$sql .= " AND r.entity IN (".getEntity('project').")";

$resql = $db->query($sql);

if (!$resql) {
    json_error('Erreur lors de la récupération du rapport', 'DATABASE_ERROR', 500);
}

if ($db->num_rows($resql) === 0) {
    json_error('Rapport non trouvé', 'NOT_FOUND', 404);
}

$rapport = $db->fetch_object($resql);

// Vérifier les droits d'accès
// Les workers ne peuvent voir que leurs propres rapports
if (!empty($auth['rights']['worker']) &&
    empty($auth['rights']['read']) &&
    $rapport->fk_user != $auth['user_id']) {
    json_error('Accès refusé à ce rapport', 'FORBIDDEN', 403);
}

// Récupérer les photos
$sql_photos = "SELECT rowid, filepath, filename, description, ordre, date_upload";
$sql_photos .= " FROM ".MAIN_DB_PREFIX."mv3_rapport_photo";
$sql_photos .= " WHERE fk_rapport = ".(int)$rapport_id;
$sql_photos .= " ORDER BY ordre ASC, date_upload ASC";

$resql_photos = $db->query($sql_photos);
$photos = [];

if ($resql_photos) {
    while ($photo = $db->fetch_object($resql_photos)) {
        $photos[] = [
            'id' => $photo->rowid,
            'filename' => $photo->filename,
            'description' => $photo->description,
            'ordre' => (int)$photo->ordre,
            'date_upload' => $photo->date_upload,
            'url' => '/custom/mv3pro_portail/document.php?modulepart=mv3pro_portail&file='.urlencode($photo->filepath)
        ];
    }
}

// Récupérer les frais liés (si table existe)
$frais = [];
$sql_check = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."mv3_frais'";
$resql_check = $db->query($sql_check);

if ($resql_check && $db->num_rows($resql_check) > 0) {
    $sql_frais = "SELECT rowid, type, montant, mode_paiement, justificatif, date_frais, statut";
    $sql_frais .= " FROM ".MAIN_DB_PREFIX."mv3_frais";
    $sql_frais .= " WHERE fk_rapport = ".(int)$rapport_id;
    $sql_frais .= " ORDER BY date_frais DESC";

    $resql_frais = $db->query($sql_frais);
    if ($resql_frais) {
        while ($f = $db->fetch_object($resql_frais)) {
            $frais[] = [
                'id' => $f->rowid,
                'type' => $f->type,
                'montant' => (float)$f->montant,
                'mode_paiement' => $f->mode_paiement,
                'justificatif' => $f->justificatif,
                'date_frais' => $f->date_frais,
                'statut' => $f->statut
            ];
        }
    }
}

// Construire la réponse
$data = [
    'rapport' => [
        'id' => $rapport->rowid,
        'date_rapport' => $rapport->date_rapport,
        'zone_travail' => $rapport->zone_travail,
        'description' => $rapport->description,
        'heures_debut' => $rapport->heures_debut,
        'heures_fin' => $rapport->heures_fin,
        'temps_total' => (float)$rapport->temps_total,
        'travaux_realises' => $rapport->travaux_realises,
        'observations' => $rapport->observations,
        'statut' => $rapport->statut,
        'date_creation' => $rapport->date_creation,
        'date_modification' => $rapport->date_modification,
        'auteur' => [
            'id' => $rapport->fk_user,
            'login' => $rapport->login,
            'nom' => trim($rapport->firstname.' '.$rapport->lastname)
        ],
        'projet' => $rapport->fk_projet ? [
            'id' => $rapport->fk_projet,
            'ref' => $rapport->projet_ref,
            'title' => $rapport->projet_title,
            'client' => $rapport->tiers_nom
        ] : null,
        'photos_count' => count($photos),
        'frais_count' => count($frais)
    ],
    'photos' => $photos,
    'frais' => $frais
];

// Si GPS dispo (colonnes ajoutées dans add_features)
if (isset($rapport->gps_latitude)) {
    $data['rapport']['gps'] = [
        'latitude' => (float)$rapport->gps_latitude,
        'longitude' => (float)$rapport->gps_longitude,
        'precision' => (float)$rapport->gps_precision
    ];
}

// Si météo dispo
if (isset($rapport->meteo_temperature)) {
    $data['rapport']['meteo'] = [
        'temperature' => (float)$rapport->meteo_temperature,
        'condition' => $rapport->meteo_condition
    ];
}

json_ok($data);
