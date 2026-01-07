<?php
/**
 * API v1 - Régie - Détail
 *
 * GET /api/v1/regie_view.php?id=123
 *
 * Retourne le détail d'un bon de régie avec:
 * - Informations du bon
 * - Lignes (temps, matériel, options)
 * - Photos
 * - Signature
 * - Projet/Client
 */

require_once __DIR__.'/_bootstrap.php';

// Auth requise
$auth = require_auth();

// Méthode GET uniquement
require_method('GET');

// Paramètres
$regie_id = (int)get_param('id', 0);
require_param($regie_id, 'id');

// Récupérer la régie
$sql = "SELECT r.*,
        u.lastname, u.firstname, u.login,
        p.ref as projet_ref, p.title as projet_title,
        s.nom as client_nom, s.email as client_email, s.address, s.zip, s.town";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_regie as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user_author";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_project";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = r.fk_soc";
$sql .= " WHERE r.rowid = ".(int)$regie_id;
$sql .= " AND r.entity IN (".getEntity('project').")";

$resql = $db->query($sql);

if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Régie non trouvée', 'NOT_FOUND', 404);
}

$regie = $db->fetch_object($resql);

// Récupérer les lignes
$sql_lines = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_regie_line";
$sql_lines .= " WHERE fk_regie = ".(int)$regie_id;
$sql_lines .= " ORDER BY rowid ASC";

$resql_lines = $db->query($sql_lines);
$lines = [];

if ($resql_lines) {
    while ($line = $db->fetch_object($resql_lines)) {
        $lines[] = [
            'id' => $line->rowid,
            'type' => $line->type,
            'description' => $line->description,
            'qty' => (float)$line->qty,
            'unit_price' => (float)$line->unit_price,
            'tva_tx' => (float)$line->tva_tx,
            'total_ht' => (float)$line->total_ht,
            'total_tva' => (float)$line->total_tva,
            'total_ttc' => (float)($line->total_ht + $line->total_tva)
        ];
    }
}

// Récupérer les photos
$sql_photos = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_regie_photo";
$sql_photos .= " WHERE fk_regie = ".(int)$regie_id;
$sql_photos .= " ORDER BY date_upload ASC";

$resql_photos = $db->query($sql_photos);
$photos = [];

if ($resql_photos) {
    while ($photo = $db->fetch_object($resql_photos)) {
        $photos[] = [
            'id' => $photo->rowid,
            'filename' => $photo->filename,
            'description' => $photo->description,
            'date_upload' => $photo->date_upload,
            'url' => '/custom/mv3pro_portail/document.php?modulepart=mv3pro_portail&file='.urlencode($photo->filepath)
        ];
    }
}

// Construire la réponse
$data = [
    'regie' => [
        'id' => $regie->rowid,
        'ref' => $regie->ref,
        'date_regie' => $regie->date_regie,
        'location' => $regie->location_text,
        'type' => $regie->type_regie,
        'status' => (int)$regie->status,
        'status_label' => getRegieStatusLabel((int)$regie->status),
        'total_ht' => (float)$regie->total_ht,
        'total_tva' => (float)$regie->total_tva,
        'total_ttc' => (float)$regie->total_ttc,
        'tva_tx' => (float)$regie->tva_tx,
        'note_public' => $regie->note_public,
        'note_private' => $regie->note_private,
        'date_creation' => $regie->date_creation,
        'date_modification' => $regie->date_modification,
        'date_validation' => $regie->date_validation,
        'date_envoi' => $regie->date_envoi,
        'date_signature' => $regie->date_signature,
        'auteur' => [
            'id' => $regie->fk_user_author,
            'login' => $regie->login,
            'nom' => trim($regie->firstname.' '.$regie->lastname)
        ],
        'projet' => $regie->fk_project ? [
            'id' => $regie->fk_project,
            'ref' => $regie->projet_ref,
            'title' => $regie->projet_title
        ] : null,
        'client' => $regie->fk_soc ? [
            'id' => $regie->fk_soc,
            'nom' => $regie->client_nom,
            'email' => $regie->client_email,
            'address' => $regie->address,
            'zip' => $regie->zip,
            'town' => $regie->town
        ] : null,
        'signature' => $regie->date_signature ? [
            'date' => $regie->date_signature,
            'latitude' => (float)$regie->sign_latitude,
            'longitude' => (float)$regie->sign_longitude,
            'ip' => $regie->sign_ip
        ] : null
    ],
    'lines' => $lines,
    'photos' => $photos
];

json_ok($data);

function getRegieStatusLabel($status) {
    $labels = [
        0 => 'Brouillon',
        1 => 'Validé',
        2 => 'Envoyé',
        3 => 'Signé',
        4 => 'Facturé',
        9 => 'Annulé'
    ];
    return $labels[$status] ?? 'Inconnu';
}
