<?php
/**
 * API v1 - Régie - Création
 * POST /api/v1/regie_create.php
 *
 * Crée un nouveau bon de régie
 *
 * Body JSON requis:
 * {
 *   "fk_project": 123,              // ID du projet (obligatoire)
 *   "date_regie": "2026-01-09",     // Date du bon (optionnel, défaut: aujourd'hui)
 *   "location_text": "Chantier A",  // Lieu (optionnel)
 *   "type_regie": "Installation",   // Type de régie (optionnel)
 *   "note_public": "Observations",  // Note publique (optionnel)
 *   "note_private": "Note interne", // Note privée (optionnel)
 *   "lines": [                      // Lignes (optionnel)
 *     {
 *       "line_type": "time",        // Type: time, material, other
 *       "description": "Main d'oeuvre",
 *       "qty": 8,
 *       "unit": "hour",
 *       "price_unit": 75.00,
 *       "tva_tx": 8.1
 *     }
 *   ]
 * }
 *
 * Retourne:
 *   {
 *     "success": true,
 *     "regie_id": 42,
 *     "ref": "BR-202601-0042"
 *   }
 */

require_once __DIR__.'/_bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/regie/class/regie.class.php';

require_method('POST');
$auth = require_auth();

log_debug("Regie create endpoint - user_id: ".$auth['user_id']);

// Récupérer les données JSON
$data = get_json_body(true);

// Valider les champs obligatoires
require_param($data['fk_project'] ?? null, 'fk_project');

$fk_project = (int)$data['fk_project'];

// Vérifier que le projet existe
$sql = "SELECT p.rowid, p.fk_soc FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " WHERE p.rowid = ".$fk_project;
$sql .= " AND p.entity = ".$conf->entity;

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) === 0) {
    json_error('Projet non trouvé', 'PROJECT_NOT_FOUND', 404, [
        'fk_project' => $fk_project,
        'hint' => 'Le projet spécifié n\'existe pas ou n\'est pas accessible'
    ]);
}

$project = $db->fetch_object($resql);

// Charger l'utilisateur Dolibarr pour la création
$dol_user = null;
if (!empty($auth['dolibarr_user'])) {
    $dol_user = $auth['dolibarr_user'];
} else if (!empty($auth['user_id'])) {
    $dol_user = new User($db);
    if ($dol_user->fetch($auth['user_id']) <= 0) {
        json_error('Utilisateur Dolibarr non trouvé', 'USER_NOT_FOUND', 403);
    }
}

if (!$dol_user) {
    json_error('Utilisateur Dolibarr requis pour créer une régie', 'USER_REQUIRED', 403, [
        'hint' => 'Votre compte mobile doit être lié à un utilisateur Dolibarr'
    ]);
}

// Créer l'objet Regie
$regie = new Regie($db);

$regie->fk_project = $fk_project;
$regie->fk_soc = (int)$project->fk_soc;

// Date de la régie
if (!empty($data['date_regie'])) {
    $date_parsed = strtotime($data['date_regie']);
    if ($date_parsed === false) {
        json_error('Format de date invalide pour date_regie', 'INVALID_DATE', 400, [
            'date_regie' => $data['date_regie'],
            'expected_format' => 'YYYY-MM-DD'
        ]);
    }
    $regie->date_regie = $date_parsed;
} else {
    $regie->date_regie = dol_now();
}

// Autres champs optionnels
$regie->location_text = $data['location_text'] ?? '';
$regie->type_regie = $data['type_regie'] ?? '';
$regie->note_public = $data['note_public'] ?? '';
$regie->note_private = $data['note_private'] ?? '';

log_debug("Creating regie", [
    'fk_project' => $fk_project,
    'fk_soc' => $regie->fk_soc,
    'date_regie' => date('Y-m-d', $regie->date_regie),
    'author_id' => $dol_user->id,
]);

// Créer la régie
$result = $regie->create($dol_user);

if ($result < 0) {
    log_debug("Failed to create regie: ".$regie->error);
    json_error('Erreur lors de la création de la régie: '.$regie->error, 'CREATE_ERROR', 500, [
        'error_message' => $regie->error,
        'sql_error' => $db->lasterror()
    ]);
}

log_debug("Regie created successfully", [
    'regie_id' => $result,
    'ref' => $regie->ref
]);

// Ajouter les lignes si fournies
if (!empty($data['lines']) && is_array($data['lines'])) {
    foreach ($data['lines'] as $line_data) {
        if (empty($line_data['line_type']) || empty($line_data['description'])) {
            continue; // Skip invalid lines
        }

        $line_options = [
            'unit' => $line_data['unit'] ?? 'unit',
            'tva_tx' => $line_data['tva_tx'] ?? 8.1,
        ];

        $line_result = $regie->addline(
            $line_data['line_type'],
            $line_data['description'],
            $line_data['qty'] ?? 1,
            $line_data['price_unit'] ?? 0,
            $line_options
        );

        if ($line_result < 0) {
            log_debug("Warning: Failed to add line", [
                'regie_id' => $result,
                'line_type' => $line_data['line_type']
            ]);
        }
    }

    // Recharger pour avoir les totaux à jour
    $regie->fetch($result);
}

json_ok([
    'regie_id' => $result,
    'ref' => $regie->ref,
    'total_ht' => (float)$regie->total_ht,
    'total_tva' => (float)$regie->total_tva,
    'total_ttc' => (float)$regie->total_ttc,
], 201);
