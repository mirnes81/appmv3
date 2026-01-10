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
require_once __DIR__ . '/../../core/init.php';

// Auth requise
$auth = require_auth(true);

// Méthode GET uniquement
require_method('GET');

// Récupérer le vrai ID Dolibarr et le statut admin via fonctions centralisées
$dolibarr_user_id = mv3_get_dolibarr_user_id($auth);
$is_admin = mv3_is_admin($auth);

// Vérifier que l'utilisateur a un dolibarr_user_id (sauf si admin)
if ($dolibarr_user_id === 0 && !$is_admin) {
    json_error(
        'Compte non lié à un utilisateur Dolibarr',
        'ACCOUNT_UNLINKED',
        403,
        ['hint' => 'Contactez un administrateur pour lier votre compte']
    );
}

// Paramètres
$rapport_id = (int)get_param('id', 0);
require_param($rapport_id, 'id');

// Vérifier que la table existe
if (!mv3_table_exists($db, 'mv3_rapport')) {
    json_error('Table rapports introuvable', 'TABLE_NOT_FOUND', 404);
}

log_debug('rapports_view.php called', [
    'user_id' => $dolibarr_user_id,
    'rapport_id' => $rapport_id,
]);

// Récupérer le rapport avec VERIFICATION DE SECURITE (fk_user = dolibarr_user_id)
$sql = "SELECT r.*,
        u.lastname, u.firstname, u.login,
        p.ref as projet_ref, p.title as projet_title, p.rowid as projet_id,
        t.nom as tiers_nom, t.code_client, t.rowid as client_id";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_rapport as r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = r.fk_user";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = r.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as t ON t.rowid = p.fk_soc";
$sql .= " WHERE r.rowid = ".(int)$rapport_id;

// SECURITE: employé ne voit que ses rapports, admin voit tout
if (!$is_admin) {
    $sql .= " AND r.fk_user = ".$dolibarr_user_id;
}

log_debug('rapports_view.php SQL query', ['sql' => $sql]);

$resql = $db->query($sql);

if (!$resql) {
    log_error('SQL_ERROR', 'Failed to fetch rapport', [
        'rapport_id' => $rapport_id,
        'sql' => $sql,
        'db_error' => $db->lasterror()
    ]);
    json_error('Erreur lors de la récupération du rapport', 'DATABASE_ERROR', 500);
}

if ($db->num_rows($resql) === 0) {
    log_debug('rapports_view.php rapport not found or access denied', [
        'rapport_id' => $rapport_id,
        'user_id' => $dolibarr_user_id
    ]);
    json_error('Rapport introuvable ou accès refusé', 'NOT_FOUND', 404);
}

$rapport = $db->fetch_object($resql);
$db->free($resql);

// Récupérer les photos avec ordre correct (position ou ordre)
$photos = [];

if (mv3_table_exists($db, 'mv3_rapport_photo')) {
    // Vérifier quelle colonne d'ordre existe
    $has_position = mv3_column_exists($db, 'mv3_rapport_photo', 'position');
    $has_categorie = mv3_column_exists($db, 'mv3_rapport_photo', 'categorie');
    $order_column = $has_position ? 'position' : 'ordre';

    $sql_photos = "SELECT rowid, filepath, filename, filesize, ".
                  ($has_categorie ? "categorie, " : "")
                  .$order_column." as position, date_creation";
    $sql_photos .= " FROM ".MAIN_DB_PREFIX."mv3_rapport_photo";
    $sql_photos .= " WHERE fk_rapport = ".(int)$rapport_id;
    $sql_photos .= " ORDER BY ".$order_column." ASC";

    log_debug('rapports_view.php loading photos', ['sql' => $sql_photos]);

    $resql_photos = $db->query($sql_photos);

    if ($resql_photos) {
        while ($photo = $db->fetch_object($resql_photos)) {
            // Construire l'URL de la photo
            $photo_url = DOL_URL_ROOT . '/custom/mv3pro_portail/mobile_app/rapports/photo.php?id=' . $photo->rowid;

            // Catégorie avec emoji
            $categorie_label = '';
            $categorie_value = $has_categorie ? $photo->categorie : '';
            if (!empty($categorie_value)) {
                $cat_icons = [
                    'avant' => '🔵 Avant',
                    'pendant' => '🟡 Pendant',
                    'apres' => '🟢 Après'
                ];
                $categorie_label = $cat_icons[$categorie_value] ?? ucfirst($categorie_value);
            }

            $photos[] = [
                'id' => (int)$photo->rowid,
                'categorie' => $categorie_value,
                'categorie_label' => $categorie_label,
                'filename' => $photo->filename,
                'filesize' => $photo->filesize ? (int)$photo->filesize : null,
                'position' => (int)$photo->position,
                'url' => $photo_url,
                'date_creation' => $photo->date_creation ?? null,
            ];
        }
        $db->free($resql_photos);
    }

    log_debug('rapports_view.php photos loaded', ['count' => count($photos)]);
}

// Statut en texte
$statut_text = 'brouillon';
if ($rapport->statut == 1) $statut_text = 'valide';
elseif ($rapport->statut == 2) $statut_text = 'soumis';

// Calculer temps_total si heures disponibles
$temps_total = 0;
if (isset($rapport->heure_debut) && isset($rapport->heure_fin)) {
    $start = strtotime($rapport->heure_debut);
    $end = strtotime($rapport->heure_fin);
    if ($end > $start) {
        $temps_total = round(($end - $start) / 3600, 2);
    }
} elseif (isset($rapport->temps_total)) {
    $temps_total = (float)$rapport->temps_total;
}

// Construire la réponse
$rapport_data = [
    'rowid' => (int)$rapport->rowid,
    'ref' => $rapport->ref ?? 'RAPPORT-'.$rapport->rowid,
    'date_rapport' => $rapport->date_rapport,
    'temps_total' => $temps_total,
    'statut' => (int)$rapport->statut,
    'statut_text' => $statut_text,
    'description' => $rapport->description ?? $rapport->travaux_realises ?? '',
    'travaux_realises' => $rapport->travaux_realises ?? '',
    'observations' => $rapport->observations ?? '',
    'client' => [
        'id' => $rapport->client_id ? (int)$rapport->client_id : null,
        'nom' => $rapport->tiers_nom,
    ],
    'projet' => [
        'id' => $rapport->projet_id ? (int)$rapport->projet_id : null,
        'ref' => $rapport->projet_ref,
        'title' => $rapport->projet_title,
    ],
    'auteur' => [
        'id' => (int)$rapport->fk_user,
        'nom' => trim($rapport->firstname . ' ' . $rapport->lastname),
        'login' => $rapport->login,
    ],
    'date_creation' => $rapport->date_creation ?? null,
    'date_modification' => $rapport->tms ?? $rapport->date_modification ?? null,
];

// Si GPS dispo
if (isset($rapport->gps_latitude)) {
    $rapport_data['gps'] = [
        'latitude' => (float)$rapport->gps_latitude,
        'longitude' => (float)$rapport->gps_longitude,
        'precision' => (float)$rapport->gps_precision
    ];
}

// Si météo dispo
if (isset($rapport->meteo_temperature)) {
    $rapport_data['meteo'] = [
        'temperature' => (float)$rapport->meteo_temperature,
        'condition' => $rapport->meteo_condition
    ];
}

// Générer l'URL du PDF
$pdf_url = DOL_URL_ROOT . '/custom/mv3pro_portail/rapports/pdf.php?id=' . $rapport_id;

log_debug('rapports_view.php success', [
    'rapport_id' => $rapport_id,
    'nb_photos' => count($photos)
]);

// Retourner la réponse (enveloppé dans data)
json_ok([
    'data' => [
        'rapport' => $rapport_data,
        'photos' => $photos,
        'pdf_url' => $pdf_url,
    ]
]);
