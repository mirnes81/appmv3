<?php
/**
 * Rapport Helpers
 * Fonctions utilitaires pour la création et gestion des rapports
 */

function calculateWorkDuration($heures_debut, $heures_fin)
{
    $temps_total = 0;
    if ($heures_debut && $heures_fin) {
        $debut = strtotime($heures_debut);
        $fin = strtotime($heures_fin);
        if ($fin > $debut) {
            $temps_total = round(($fin - $debut) / 3600, 2);
        }
    }
    return $temps_total;
}

function createRapport($db, $conf, $data)
{
    global $user;

    $temps_total = calculateWorkDuration($data['heures_debut'] ?? null, $data['heures_fin'] ?? null);

    $fields = [
        'entity' => $conf->entity,
        'fk_user' => (int)$data['fk_user'],
        'fk_projet' => $data['fk_projet'] ? (int)$data['fk_projet'] : null,
        'fk_soc' => $data['fk_soc'] ? (int)$data['fk_soc'] : null,
        'date_rapport' => $data['date_rapport'],
        'zone_travail' => $data['zone_travail'] ?? '',
        'heures_debut' => $data['heures_debut'] ?? null,
        'heures_fin' => $data['heures_fin'] ?? null,
        'temps_total' => $temps_total,
        'surface_carrelee' => $data['surface_carrelee'] ? (float)$data['surface_carrelee'] : null,
        'format_carreaux' => $data['format_carreaux'] ?? '',
        'type_pose' => $data['type_pose'] ?? '',
        'zone_pose' => $data['zone_pose'] ?? '',
        'travaux_realises' => $data['travaux_realises'] ?? '',
        'observations' => $data['observations'] ?? '',
        'statut' => 0,
        'date_creation' => 'NOW()'
    ];

    if (isset($data['type_lieu'])) {
        $fields['type_lieu'] = $data['type_lieu'];
    }
    if (isset($data['numero_lieu'])) {
        $fields['numero_lieu'] = $data['numero_lieu'];
    }
    if (isset($data['gps_latitude'])) {
        $fields['gps_latitude'] = $data['gps_latitude'] ? $data['gps_latitude'] : null;
    }
    if (isset($data['gps_longitude'])) {
        $fields['gps_longitude'] = $data['gps_longitude'] ? $data['gps_longitude'] : null;
    }
    if (isset($data['gps_accuracy'])) {
        $fields['gps_accuracy'] = $data['gps_accuracy'] ? (float)$data['gps_accuracy'] : null;
    }
    if (isset($data['meteo_temperature'])) {
        $fields['meteo_temperature'] = $data['meteo_temperature'] ? (float)$data['meteo_temperature'] : null;
    }
    if (isset($data['meteo_condition'])) {
        $fields['meteo_condition'] = $data['meteo_condition'] ? $data['meteo_condition'] : null;
    }

    $rapport_id = insertRecord($db, 'mv3_rapport', $fields);

    if ($rapport_id) {
        $ref = 'RAP'.str_pad($rapport_id, 6, '0', STR_PAD_LEFT);
        updateRecord($db, 'mv3_rapport', ['ref' => $ref], ['rowid' => $rapport_id]);
    }

    return $rapport_id;
}

function processRapportPhotos($db, $rapport_id)
{
    if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
        return [];
    }

    $upload_dir = DOL_DATA_ROOT.'/mv3pro_portail/rapports/'.$rapport_id;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $uploaded = [];

    for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
        if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['photos']['tmp_name'][$i];
            $ext = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
            $filename = 'photo_'.time().'_'.$i.'.'.$ext;
            $target = $upload_dir.'/'.$filename;

            if (move_uploaded_file($tmp_name, $target)) {
                $relative_path = '/mv3pro_portail/rapports/'.$rapport_id.'/'.$filename;
                $categorie = GETPOST('photo_categorie_'.$i, 'alpha') ?: 'pendant';

                $photo_data = [
                    'fk_rapport' => $rapport_id,
                    'path' => $relative_path,
                    'filename' => $filename,
                    'position' => $i,
                    'categorie' => $categorie
                ];

                $photo_id = insertRecord($db, 'mv3_rapport_photo', $photo_data);
                if ($photo_id) {
                    $uploaded[] = $photo_id;
                }
            }
        }
    }

    return $uploaded;
}

function processFrais($db, $conf, $fk_user, $fk_projet, $date_rapport, $rapport_ref)
{
    $frais_type = GETPOST('frais_type', 'alpha');
    $frais_montant = GETPOST('frais_montant', 'alpha');
    $frais_mode = GETPOST('frais_mode', 'alpha');

    if (!$frais_type || !$frais_montant) {
        return null;
    }

    $frais_statut = ($frais_mode == 'company_paid') ? 'reimbursed' : 'to_reimburse';

    $frais_data = [
        'entity' => $conf->entity,
        'fk_user' => (int)$fk_user,
        'fk_projet' => $fk_projet ? (int)$fk_projet : null,
        'date_frais' => $date_rapport,
        'type' => $frais_type,
        'montant' => (float)$frais_montant,
        'mode_paiement' => $frais_mode,
        'statut' => $frais_statut,
        'note_private' => 'Associé au rapport '.$rapport_ref,
        'date_creation' => 'NOW()'
    ];

    $frais_id = insertRecord($db, 'mv3_frais', $frais_data);

    if ($frais_id) {
        $frais_ref = 'FRA'.str_pad($frais_id, 6, '0', STR_PAD_LEFT);
        updateRecord($db, 'mv3_frais', ['ref' => $frais_ref], ['rowid' => $frais_id]);

        if (isset($_FILES['frais_photo']) && $_FILES['frais_photo']['error'] === UPLOAD_ERR_OK) {
            $frais_upload_dir = DOL_DATA_ROOT.'/mv3pro_portail/frais/'.$frais_id;
            if (!is_dir($frais_upload_dir)) {
                mkdir($frais_upload_dir, 0755, true);
            }

            $ext = pathinfo($_FILES['frais_photo']['name'], PATHINFO_EXTENSION);
            $filename = 'ticket_'.time().'.'.$ext;
            $target = $frais_upload_dir.'/'.$filename;

            if (move_uploaded_file($_FILES['frais_photo']['tmp_name'], $target)) {
                $relative_path = '/mv3pro_portail/frais/'.$frais_id.'/'.$filename;
                updateRecord($db, 'mv3_frais', ['photo_path' => $relative_path], ['rowid' => $frais_id]);
            }
        }
    }

    return $frais_id;
}
