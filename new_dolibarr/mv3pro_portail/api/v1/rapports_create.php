<?php
/**
 * POST /api/v1/rapports_create.php
 *
 * Créer un nouveau rapport journalier
 *
 * Body JSON:
 * {
 *   "projet_id": 123,
 *   "date": "2025-01-07",
 *   "heure_debut": "08:00",
 *   "heure_fin": "16:00",
 *   "zones": ["Salle de bain", "Cuisine"],
 *   "surface_total": 20.5,
 *   "format": "30x60",
 *   "type_carrelage": "Grès cérame",
 *   "travaux_realises": "Description...",
 *   "observations": "Notes...",
 *   "gps_latitude": 48.8566,
 *   "gps_longitude": 2.3522,
 *   "gps_precision": 15,
 *   "meteo_temperature": 18,
 *   "meteo_condition": "Ensoleillé",
 *   "frais": {
 *     "type": "repas_midi",
 *     "montant": 15.00,
 *     "mode_paiement": "avance_ouvrier",
 *     "notes": "Restaurant..."
 *   }
 * }
 */

require_once __DIR__ . '/_bootstrap.php';

global $db, $conf;

// Méthode POST uniquement
require_method('POST');

// Authentification obligatoire
$auth = require_auth(true);

// Vérifier les droits d'écriture
require_rights('write', $auth);

// Récupérer le body JSON
$data = get_json_body(true);

// Support des deux formats de champs (PWA et ancienne API)
if (!isset($data['date']) && isset($data['date_rapport'])) {
    $data['date'] = $data['date_rapport'];
}
if (!isset($data['gps_latitude']) && isset($data['latitude'])) {
    $data['gps_latitude'] = $data['latitude'];
}
if (!isset($data['gps_longitude']) && isset($data['longitude'])) {
    $data['gps_longitude'] = $data['longitude'];
}
if (!isset($data['meteo_temperature']) && isset($data['temperature'])) {
    $data['meteo_temperature'] = $data['temperature'];
}
if (!isset($data['meteo_condition']) && isset($data['meteo'])) {
    $data['meteo_condition'] = $data['meteo'];
}
if (!isset($data['travaux_realises']) && isset($data['description'])) {
    $data['travaux_realises'] = $data['description'];
}

// Validation des champs obligatoires (projet_id est maintenant optionnel)
require_param($data['date'] ?? null, 'date');
require_param($data['heure_debut'] ?? null, 'heure_debut');
require_param($data['heure_fin'] ?? null, 'heure_fin');

// Validation format date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
    json_error('Format de date invalide. Utiliser YYYY-MM-DD', 'INVALID_DATE', 400);
}

// Validation format heures
if (!preg_match('/^\d{2}:\d{2}$/', $data['heure_debut']) || !preg_match('/^\d{2}:\d{2}$/', $data['heure_fin'])) {
    json_error('Format d\'heure invalide. Utiliser HH:MM', 'INVALID_TIME', 400);
}

// Récupérer l'ID utilisateur
$user_id = $auth['user_id'];

if (!$user_id) {
    json_error('Impossible de déterminer l\'ID utilisateur', 'NO_USER_ID', 400);
}

// Vérifier que le projet existe (si fourni)
$projet = null;
if (!empty($data['projet_id'])) {
    $sql_check = "SELECT p.rowid, p.fk_soc
                  FROM ".MAIN_DB_PREFIX."projet p
                  WHERE p.rowid = ".(int)$data['projet_id']."
                  AND p.entity = ".(int)$conf->entity;

    $resql_check = $db->query($sql_check);

    if (!$resql_check || $db->num_rows($resql_check) === 0) {
        json_error('Projet introuvable', 'PROJECT_NOT_FOUND', 404);
    }

    $projet = $db->fetch_object($resql_check);
}

// Démarrer transaction
$db->begin();

try {
    // Générer la référence
    $sql_max = "SELECT MAX(CAST(SUBSTRING(ref, 4) AS UNSIGNED)) as max_num
                FROM ".MAIN_DB_PREFIX."mv3_rapport
                WHERE ref LIKE 'RAP%'";

    $resql_max = $db->query($sql_max);
    $max_num = 0;

    if ($resql_max) {
        $obj = $db->fetch_object($resql_max);
        $max_num = (int)$obj->max_num;
    }

    $new_num = $max_num + 1;
    $ref = 'RAP' . str_pad($new_num, 6, '0', STR_PAD_LEFT);

    // Préparer les zones
    $zones_str = '';
    if (isset($data['zones']) && is_array($data['zones'])) {
        $zones_str = implode(', ', $data['zones']);
    }

    // Insertion du rapport
    $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport (
        ref, entity, fk_user, fk_projet, fk_soc, date_rapport,
        heure_debut, heure_fin, zones, surface_total,
        format, type_carrelage, travaux_realises, observations,
        statut, datec
    ) VALUES (
        '".$db->escape($ref)."',
        ".(int)$conf->entity.",
        ".(int)$user_id.",
        ".(!empty($data['projet_id']) ? (int)$data['projet_id'] : 'NULL').",
        ".($projet ? (int)$projet->fk_soc : 'NULL').",
        '".$db->escape($data['date'])."',
        '".$db->escape($data['heure_debut'])."',
        '".$db->escape($data['heure_fin'])."',
        '".$db->escape($zones_str)."',
        ".(float)($data['surface_total'] ?? 0).",
        '".$db->escape($data['format'] ?? '')."',
        '".$db->escape($data['type_carrelage'] ?? '')."',
        '".$db->escape($data['travaux_realises'] ?? '')."',
        '".$db->escape($data['observations'] ?? '')."',
        'draft',
        NOW()
    )";

    if (!$db->query($sql_insert)) {
        throw new Exception('Erreur lors de la création du rapport');
    }

    $rapport_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_rapport");

    // Ajouter GPS si fourni
    if (!empty($data['gps_latitude']) && !empty($data['gps_longitude'])) {
        $sql_gps = "UPDATE ".MAIN_DB_PREFIX."mv3_rapport
                    SET gps_latitude = ".(float)$data['gps_latitude'].",
                        gps_longitude = ".(float)$data['gps_longitude'].",
                        gps_precision = ".(float)($data['gps_precision'] ?? 0)."
                    WHERE rowid = ".(int)$rapport_id;

        $db->query($sql_gps);
    }

    // Ajouter météo si fournie
    if (!empty($data['meteo_temperature']) || !empty($data['meteo_condition'])) {
        $sql_meteo = "UPDATE ".MAIN_DB_PREFIX."mv3_rapport
                      SET meteo_temperature = ".(int)($data['meteo_temperature'] ?? 0).",
                          meteo_condition = '".$db->escape($data['meteo_condition'] ?? '')."'
                      WHERE rowid = ".(int)$rapport_id;

        $db->query($sql_meteo);
    }

    // Gérer les photos si fournies
    if (!empty($data['photos']) && is_array($data['photos'])) {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        // Créer le répertoire pour les photos du rapport
        $upload_dir = $conf->mv3pro_portail->dir_output . '/rapports/' . $rapport_id;
        if (!is_dir($upload_dir)) {
            dol_mkdir($upload_dir);
        }

        foreach ($data['photos'] as $index => $photo_base64) {
            // Extraire les données base64 (format: data:image/jpeg;base64,...)
            if (preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/i', $photo_base64, $matches)) {
                $extension = strtolower($matches[1]);
                if ($extension === 'jpg') $extension = 'jpeg';

                $image_data = base64_decode($matches[2]);

                if ($image_data !== false) {
                    $filename = 'photo_' . ($index + 1) . '_' . time() . '.' . $extension;
                    $filepath = $upload_dir . '/' . $filename;

                    if (file_put_contents($filepath, $image_data)) {
                        // Photo sauvegardée avec succès
                        @chmod($filepath, 0644);
                    }
                }
            }
        }
    }

    $frais_id = null;
    $frais_ref = null;

    // Créer les frais si fournis
    if (!empty($data['frais']) && is_array($data['frais'])) {
        $frais = $data['frais'];

        if (!empty($frais['type']) && !empty($frais['montant'])) {
            // Générer référence frais
            $sql_max_frais = "SELECT MAX(CAST(SUBSTRING(ref, 4) AS UNSIGNED)) as max_num
                             FROM ".MAIN_DB_PREFIX."mv3_frais
                             WHERE ref LIKE 'FRA%'";

            $resql_frais = $db->query($sql_max_frais);
            $max_frais = 0;

            if ($resql_frais) {
                $obj = $db->fetch_object($resql_frais);
                $max_frais = (int)$obj->max_num;
            }

            $new_frais_num = $max_frais + 1;
            $frais_ref = 'FRA' . str_pad($new_frais_num, 6, '0', STR_PAD_LEFT);

            // Déterminer le statut
            $mode_paiement = $frais['mode_paiement'] ?? 'avance_ouvrier';
            $statut_frais = ($mode_paiement === 'entreprise') ? 'reimbursed' : 'to_reimburse';

            // Insérer frais
            $sql_frais = "INSERT INTO ".MAIN_DB_PREFIX."mv3_frais (
                ref, entity, fk_user, fk_rapport, type, montant,
                mode_paiement, statut, notes, date_frais, datec
            ) VALUES (
                '".$db->escape($frais_ref)."',
                ".(int)$conf->entity.",
                ".(int)$user_id.",
                ".(int)$rapport_id.",
                '".$db->escape($frais['type'])."',
                ".(float)$frais['montant'].",
                '".$db->escape($mode_paiement)."',
                '".$db->escape($statut_frais)."',
                '".$db->escape($frais['notes'] ?? 'Lié au rapport '.$ref)."',
                '".$db->escape($data['date'])."',
                NOW()
            )";

            if ($db->query($sql_frais)) {
                $frais_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_frais");
            }
        }
    }

    // Commit transaction
    $db->commit();

    // Réponse succès
    $response = [
        'success' => true,
        'rapport' => [
            'id' => $rapport_id,
            'ref' => $ref,
            'url' => '/custom/mv3pro_portail/mobile_app/rapports/view.php?id='.$rapport_id
        ]
    ];

    if ($frais_id) {
        $response['frais'] = [
            'id' => $frais_id,
            'ref' => $frais_ref
        ];
    }

    json_ok($response, 201);

} catch (Exception $e) {
    // Rollback en cas d'erreur
    $db->rollback();
    json_error('Erreur lors de la création: ' . $e->getMessage(), 'CREATE_ERROR', 500);
}
