<?php
/**
 * Nouveau rapport - Version PROFESSIONNELLE avec toutes les fonctionnalités avancées
 * - Mode hors-ligne (PWA)
 * - Géolocalisation GPS
 * - Reconnaissance vocale
 * - Templates rapides
 * - Timer avec pauses
 * - Auto-sauvegarde
 * - Photos avec watermark
 * - Validation intelligente
 * - Stats temps réel
 * - Météo
 * - QR Code scan
 * - Copie rapport précédent
 */

require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

$user_id = $user->id;
$error = '';
$action = GETPOST('action', 'alpha');

if ($action == 'create') {
    $fk_user = $user_id;
    $fk_projet = GETPOST('fk_projet', 'int');
    $fk_soc = GETPOST('fk_soc', 'int');
    $date_rapport = GETPOST('date_rapport', 'alpha');
    $zone_travail = GETPOST('zone_travail', 'alpha');
    $heures_debut = GETPOST('heures_debut', 'alpha');
    $heures_fin = GETPOST('heures_fin', 'alpha');
    $surface_carrelee = GETPOST('surface_carrelee', 'alpha');
    $format_carreaux = GETPOST('format_carreaux', 'alpha');
    $type_pose = GETPOST('type_pose', 'alpha');
    $zone_pose = GETPOST('zone_pose', 'alpha');
    $travaux_realises = GETPOST('travaux_realises', 'restricthtml');
    $observations = GETPOST('observations', 'restricthtml');

    $gps_latitude = GETPOST('gps_latitude', 'alpha');
    $gps_longitude = GETPOST('gps_longitude', 'alpha');
    $gps_accuracy = GETPOST('gps_accuracy', 'alpha');
    $meteo_temperature = GETPOST('meteo_temperature', 'alpha');
    $meteo_condition = GETPOST('meteo_condition', 'alpha');

    $temps_total = 0;
    if ($heures_debut && $heures_fin) {
        $debut = strtotime($heures_debut);
        $fin = strtotime($heures_fin);
        if ($fin > $debut) {
            $temps_total = round(($fin - $debut) / 3600, 2);
        }
    }

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport (";
    $sql .= "entity, fk_user, fk_projet, fk_soc, date_rapport, zone_travail, heures_debut, heures_fin, temps_total,";
    $sql .= "surface_carrelee, format_carreaux, type_pose, zone_pose, travaux_realises, observations,";
    $sql .= "gps_latitude, gps_longitude, gps_accuracy, meteo_temperature, meteo_condition,";
    $sql .= "statut, date_creation";
    $sql .= ") VALUES (";
    $sql .= $conf->entity.",";
    $sql .= (int)$fk_user.",";
    $sql .= ($fk_projet ? (int)$fk_projet : "NULL").",";
    $sql .= ($fk_soc ? (int)$fk_soc : "NULL").",";
    $sql .= "'".$db->escape($date_rapport)."',";
    $sql .= "'".$db->escape($zone_travail)."',";
    $sql .= ($heures_debut ? "'".$db->escape($heures_debut)."'" : "NULL").",";
    $sql .= ($heures_fin ? "'".$db->escape($heures_fin)."'" : "NULL").",";
    $sql .= $temps_total.",";
    $sql .= ($surface_carrelee ? (float)$surface_carrelee : "NULL").",";
    $sql .= "'".$db->escape($format_carreaux)."',";
    $sql .= "'".$db->escape($type_pose)."',";
    $sql .= "'".$db->escape($zone_pose)."',";
    $sql .= "'".$db->escape($travaux_realises)."',";
    $sql .= "'".$db->escape($observations)."',";
    $sql .= ($gps_latitude ? "'".$db->escape($gps_latitude)."'" : "NULL").",";
    $sql .= ($gps_longitude ? "'".$db->escape($gps_longitude)."'" : "NULL").",";
    $sql .= ($gps_accuracy ? (float)$gps_accuracy : "NULL").",";
    $sql .= ($meteo_temperature ? (float)$meteo_temperature : "NULL").",";
    $sql .= ($meteo_condition ? "'".$db->escape($meteo_condition)."'" : "NULL").",";
    $sql .= "0,";
    $sql .= "NOW()";
    $sql .= ")";

    if ($db->query($sql)) {
        $rapport_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_rapport");

        $ref = 'RAP'.str_pad($rapport_id, 6, '0', STR_PAD_LEFT);
        $db->query("UPDATE ".MAIN_DB_PREFIX."mv3_rapport SET ref = '".$ref."' WHERE rowid = ".$rapport_id);

        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
            $upload_dir = DOL_DATA_ROOT.'/mv3pro_portail/rapports/'.$rapport_id;
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['photos']['tmp_name'][$i];
                    $ext = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'photo_'.time().'_'.$i.'.'.$ext;
                    $target = $upload_dir.'/'.$filename;

                    if (move_uploaded_file($tmp_name, $target)) {
                        $relative_path = '/mv3pro_portail/rapports/'.$rapport_id.'/'.$filename;
                        $categorie = GETPOST('photo_categorie_'.$i, 'alpha') ?: 'pendant';

                        $sql_photo = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport_photo";
                        $sql_photo .= " (fk_rapport, path, filename, position, categorie)";
                        $sql_photo .= " VALUES (".$rapport_id.", '".$db->escape($relative_path)."', '".$db->escape($filename)."', ".$i.", '".$db->escape($categorie)."')";
                        $db->query($sql_photo);
                    }
                }
            }
        }

        $frais_type = GETPOST('frais_type', 'alpha');
        $frais_montant = GETPOST('frais_montant', 'alpha');
        $frais_mode = GETPOST('frais_mode', 'alpha');

        if ($frais_type && $frais_montant) {
            $frais_statut = ($frais_mode == 'company_paid') ? 'reimbursed' : 'to_reimburse';

            $sql_frais = "INSERT INTO ".MAIN_DB_PREFIX."mv3_frais (";
            $sql_frais .= "entity, fk_user, fk_projet, date_frais, type, montant, mode_paiement, statut, note_private, date_creation";
            $sql_frais .= ") VALUES (";
            $sql_frais .= $conf->entity.",";
            $sql_frais .= (int)$fk_user.",";
            $sql_frais .= ($fk_projet ? (int)$fk_projet : "NULL").",";
            $sql_frais .= "'".$db->escape($date_rapport)."',";
            $sql_frais .= "'".$db->escape($frais_type)."',";
            $sql_frais .= (float)$frais_montant.",";
            $sql_frais .= "'".$db->escape($frais_mode)."',";
            $sql_frais .= "'".$db->escape($frais_statut)."',";
            $sql_frais .= "'Associé au rapport ".$ref."',";
            $sql_frais .= "NOW()";
            $sql_frais .= ")";

            if ($db->query($sql_frais)) {
                $frais_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_frais");
                $frais_ref = 'FRA'.str_pad($frais_id, 6, '0', STR_PAD_LEFT);
                $db->query("UPDATE ".MAIN_DB_PREFIX."mv3_frais SET ref = '".$frais_ref."' WHERE rowid = ".$frais_id);

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
                        $db->query("UPDATE ".MAIN_DB_PREFIX."mv3_frais SET photo_path = '".$db->escape($relative_path)."' WHERE rowid = ".$frais_id);
                    }
                }
            }
        }

        header('Location: view.php?id='.$rapport_id);
        exit;
    } else {
        $error = "Erreur: ".$db->lasterror();
    }
}

$sql_projets = "SELECT rowid, ref, title FROM ".MAIN_DB_PREFIX."projet WHERE entity = ".$conf->entity." AND fk_statut = 1 ORDER BY ref DESC LIMIT 100";
$resql_projets = $db->query($sql_projets);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Nouveau Rapport PRO - MV3</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        .zone-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 12px 0; }
        .zone-btn { padding: 12px 8px; border: 2px solid #cbd5e1; border-radius: 8px; background: #fff; font-size: 13px; font-weight: 600; text-align: center; cursor: pointer; transition: all 0.2s; }
        .zone-btn:active { transform: scale(0.95); }
        .zone-btn.selected { background: var(--primary); border-color: var(--primary); color: #fff; }
        .quick-btns { display: grid; grid-template-columns: repeat(auto-fit, minmax(60px, 1fr)); gap: 6px; margin-top: 8px; }
        .quick-btn { padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; background: #fff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .quick-btn:active { background: var(--primary); color: #fff; }
        .zone-preview { margin-top: 12px; padding: 12px; background: #eff6ff; border-radius: 8px; min-height: 40px; font-weight: 600; color: #1e40af; font-size: 14px; }
        .time-info { margin-top: 8px; padding: 10px; background: #eff6ff; border-radius: 6px; text-align: center; font-weight: 700; color: var(--primary); }
        .photo-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 12px; }
        .photo-item { position: relative; border: 2px solid var(--primary); border-radius: 8px; overflow: hidden; }
        .photo-item img { width: 100%; height: 120px; object-fit: cover; }
        .photo-item select { width: 100%; padding: 6px; font-size: 12px; border: none; background: #f1f5f9; }
        .photo-item .remove { position: absolute; top: 4px; right: 4px; width: 28px; height: 28px; background: #ef4444; color: #fff; border: none; border-radius: 50%; font-size: 18px; cursor: pointer; }
        .feature-btn { padding: 12px; border: 2px solid var(--primary); border-radius: 8px; background: #fff; font-weight: 600; color: var(--primary); cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .feature-btn:active { background: var(--primary); color: #fff; }
        .time-input-wrapper { display: grid; grid-template-columns: 1fr auto 1fr; gap: 12px; align-items: center; margin-top: 16px; }
        .time-display { display: flex; align-items: center; gap: 8px; padding: 16px; background: #f8fafc; border: 2px solid #cbd5e1; border-radius: 12px; }
        .time-display input { flex: 1; font-size: 20px; font-weight: 700; text-align: center; border: none; background: transparent; color: #0891b2; }
        .time-arrows { display: flex; flex-direction: column; gap: 4px; }
        .arrow-btn { width: 32px; height: 32px; border: none; background: #0891b2; color: white; border-radius: 6px; font-size: 18px; cursor: pointer; font-weight: bold; }
        .arrow-btn:active { background: #0e7490; transform: scale(0.95); }
        .separator { font-size: 32px; font-weight: 700; color: #64748b; }
        .duration-box { margin-top: 16px; padding: 20px; background: linear-gradient(135deg, #dcfce7 0%, #86efac 100%); border-radius: 12px; text-align: center; }
        .duration-value { font-size: 36px; font-weight: 800; color: #166534; margin: 8px 0; }
        .duration-label { font-size: 14px; font-weight: 600; color: #15803d; }
        .preset-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 16px; }
        .preset-btn { padding: 14px; background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%); border: none; border-radius: 10px; color: white; font-weight: 700; font-size: 15px; cursor: pointer; box-shadow: 0 2px 8px rgba(8,145,178,0.3); }
        .preset-btn:active { transform: scale(0.97); }
        .increment-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-top: 12px; }
        .inc-btn { padding: 10px; background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 8px; font-weight: 700; color: #475569; cursor: pointer; }
        .inc-btn.plus { background: #dcfce7; border-color: #86efac; color: #166534; }
        .inc-btn.minus { background: #fee2e2; border-color: #fca5a5; color: #991b1b; }
        .inc-btn:active { transform: scale(0.95); }
        .direct-hours { margin-top: 12px; padding: 16px; background: #fef3c7; border-radius: 10px; border: 2px solid #fbbf24; }
        .direct-hours input { width: 80px; padding: 12px; font-size: 24px; font-weight: 700; text-align: center; border: 2px solid #f59e0b; border-radius: 8px; background: white; color: #92400e; }
        .direct-hours label { display: block; margin-bottom: 8px; font-weight: 700; color: #92400e; }
        .gps-info { background: #dcfce7; padding: 12px; border-radius: 8px; margin-top: 12px; font-size: 13px; color: #166534; }
        .offline-indicator { position: fixed; bottom: 80px; right: 20px; background: #f59e0b; color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; display: none; z-index: 1000; }
        .offline-indicator.show { display: block; }
        #autoSaveIndicator { position: fixed; top: 60px; right: 20px; background: #10b981; color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; opacity: 0; transition: opacity 0.3s; z-index: 1000; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.show { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 100%; max-height: 80vh; overflow-y: auto; }
        .template-item { padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
        .template-item:hover, .template-item:active { border-color: var(--primary); background: #eff6ff; }
        .voice-btn { position: relative; }
        .voice-btn.listening { animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
    </style>
</head>
<body>
    <div class="offline-indicator" id="offlineIndicator">📡 Mode hors-ligne</div>
    <div id="autoSaveIndicator"></div>

    <div class="app-header">
        <div style="display:flex;align-items:center;gap:12px">
            <a href="list.php" style="color:white;font-size:24px;text-decoration:none">←</a>
            <div>
                <div class="app-header-title">✨ Nouveau Rapport PRO</div>
                <div class="app-header-subtitle">Version complète avec toutes les features</div>
            </div>
        </div>
    </div>

    <div class="app-container">
        <?php if ($error): ?>
            <div class="card" style="background:#fee2e2;color:#991b1b;">
                <strong>❌ <?php echo $error; ?></strong>
            </div>
        <?php endif; ?>

        <div id="statsWidget"></div>
        <div id="weatherWidget"></div>

        <div class="card">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 16px;">
                <button type="button" class="feature-btn" onclick="showTemplates()">
                    <span>📋</span> Templates
                </button>
                <button type="button" class="feature-btn" onclick="showCopyRapport()">
                    <span>📝</span> Copier
                </button>
                <button type="button" class="feature-btn" onclick="scanQRCode()">
                    <span>📱</span> Scanner QR
                </button>
                <button type="button" class="feature-btn" onclick="captureGPS()">
                    <span>📍</span> GPS
                </button>
            </div>
        </div>


        <form method="POST" enctype="multipart/form-data" id="rapportForm" accept-charset="UTF-8">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="gps_latitude" id="gpsLatitude">
            <input type="hidden" name="gps_longitude" id="gpsLongitude">
            <input type="hidden" name="gps_accuracy" id="gpsAccuracy">
            <input type="hidden" name="meteo_temperature" id="meteoTemperature">
            <input type="hidden" name="meteo_condition" id="meteoCondition">

            <div class="card">
                <div class="card-title">📋 Informations générales</div>

                <div class="form-group">
                    <label class="form-label">🏗️ Projet *</label>
                    <select name="fk_projet" class="form-input" required id="projetSelect" onchange="loadProjectClients(this.value)">
                        <option value="">-- Sélectionner un projet --</option>
                        <?php
                        if ($resql_projets) {
                            while ($projet = $db->fetch_object($resql_projets)) {
                                echo '<option value="'.$projet->rowid.'">'.$projet->ref.' - '.dol_trunc($projet->title, 40).'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group" id="clientGroup" style="display:none">
                    <label class="form-label">🏢 Client *</label>
                    <select name="fk_soc" class="form-input" id="clientSelect">
                        <option value="">-- Choisir un projet d'abord --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">📅 Date *</label>
                    <input type="date" name="date_rapport" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">🏠 Type de lieu *</label>
                    <select name="type_lieu" id="typeLieu" class="form-input" required onchange="updateZoneLabel()">
                        <option value="">-- Sélectionner --</option>
                        <option value="Villa">🏡 Villa</option>
                        <option value="Appartement">🏢 Appartement</option>
                        <option value="Bâtiment">🏗️ Bâtiment</option>
                        <option value="Maison">🏠 Maison</option>
                    </select>
                </div>

                <div class="form-group" id="numeroField" style="display:none">
                    <label class="form-label" id="numeroLabel">📌 Numéro/Nom</label>
                    <input type="text" name="numero_lieu" id="numeroLieu" class="form-input" placeholder="Ex: A12, Villa Rose...">
                </div>

                <div class="form-group">
                    <label class="form-label">📍 Zone(s) de travail *</label>
                    <div class="zone-grid">
                        <button type="button" class="zone-btn" data-zone="Salon" onclick="toggleZone(this)">🛋️ Salon</button>
                        <button type="button" class="zone-btn" data-zone="Cuisine" onclick="toggleZone(this)">🍳 Cuisine</button>
                        <button type="button" class="zone-btn" data-zone="Chambre" onclick="toggleZone(this)">🛏️ Chambre</button>
                        <button type="button" class="zone-btn" data-zone="Salle de bain" onclick="toggleZone(this)">🛁 SDB</button>
                        <button type="button" class="zone-btn" data-zone="Douche" onclick="toggleZone(this)">🚿 Douche</button>
                        <button type="button" class="zone-btn" data-zone="WC" onclick="toggleZone(this)">🚽 WC</button>
                        <button type="button" class="zone-btn" data-zone="Couloir" onclick="toggleZone(this)">🚪 Couloir</button>
                        <button type="button" class="zone-btn" data-zone="Entrée" onclick="toggleZone(this)">🚪 Entrée</button>
                        <button type="button" class="zone-btn" data-zone="Terrasse" onclick="toggleZone(this)">🌴 Terrasse</button>
                        <button type="button" class="zone-btn" data-zone="Balcon" onclick="toggleZone(this)">🪴 Balcon</button>
                        <button type="button" class="zone-btn" data-zone="Garage" onclick="toggleZone(this)">🚗 Garage</button>
                        <button type="button" class="zone-btn" data-zone="Escalier" onclick="toggleZone(this)">🪜 Escalier</button>
                    </div>
                    <input type="text" name="zone_autre" id="zoneAutre" class="form-input" placeholder="Autre zone..." style="margin-top:8px;" oninput="updateZoneDisplay()">
                    <input type="hidden" name="zone_travail" id="zoneTravail" required>
                    <div class="zone-preview" id="zonePreview">👉 Sélectionnez type de lieu et zones</div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">💰 Frais du jour (optionnel)</div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
                    <button type="button" class="zone-btn" id="fraisRepasBtn" onclick="selectFrais('meal_lunch', this)">
                        <div style="font-size: 28px;">🍽️</div>
                        <div style="margin-top: 4px;">Repas midi</div>
                    </button>
                    <button type="button" class="zone-btn" id="fraisEssenceBtn" onclick="selectFrais('fuel', this)">
                        <div style="font-size: 28px;">⛽</div>
                        <div style="margin-top: 4px;">Essence</div>
                    </button>
                </div>

                <input type="hidden" name="frais_type" id="fraisType">

                <div id="fraisDetails" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">💳 Mode de paiement</label>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="zone-btn" id="modeCo" onclick="selectFraisMode('company_paid')" style="flex: 1;">
                                🏢 Payé<br>entreprise
                            </button>
                            <button type="button" class="zone-btn selected" id="modeWorker" onclick="selectFraisMode('worker_advance')" style="flex: 1;">
                                👤 Avancé<br>ouvrier
                            </button>
                        </div>
                        <input type="hidden" name="frais_mode" id="fraisMode" value="worker_advance">
                    </div>

                    <div class="form-group">
                        <label class="form-label">💰 Montant (CHF)</label>
                        <input type="number" name="frais_montant" id="fraisMontant" class="form-input" step="0.50" min="0" value="19.00" style="font-size: 24px; font-weight: 700; text-align: center; color: #0891b2;">
                        <div class="quick-btns">
                            <button type="button" class="quick-btn" onclick="setFraisMontant(15)">15</button>
                            <button type="button" class="quick-btn" onclick="setFraisMontant(19)">19</button>
                            <button type="button" class="quick-btn" onclick="setFraisMontant(25)">25</button>
                            <button type="button" class="quick-btn" onclick="setFraisMontant(50)">50</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">📸 Photo du ticket (optionnel)</label>
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('fraisPhotoInput').click()" style="background: #10b981;">
                            <span>📷 Prendre photo du ticket</span>
                        </button>
                        <input type="file" id="fraisPhotoInput" name="frais_photo" accept="image/*" capture="environment" style="display:none" onchange="previewFraisPhoto(event)">
                        <div id="fraisPhotoPreview" style="margin-top:12px;display:none;">
                            <img id="fraisPhotoImg" style="width:100%;max-width:200px;border-radius:8px;border:2px solid #10b981;">
                        </div>
                    </div>

                    <button type="button" class="btn" onclick="cancelFrais()" style="background:#64748b;margin-top:8px;">
                        <span>❌ Annuler le frais</span>
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-title">⏱️ Saisie des heures - ULTRA FACILE</div>

                <div class="preset-grid">
                    <button type="button" class="preset-btn" onclick="setPreset('demi-journee-matin')">
                        ☀️ Demi-journée<br>Matin (4h)
                    </button>
                    <button type="button" class="preset-btn" onclick="setPreset('demi-journee-aprem')">
                        🌆 Demi-journée<br>Après-midi (4h)
                    </button>
                    <button type="button" class="preset-btn" onclick="setPreset('journee-complete')">
                        🌞 Journée<br>Complète (8h)
                    </button>
                    <button type="button" class="preset-btn" onclick="setPreset('maintenant')">
                        🕐 Commencer<br>Maintenant
                    </button>
                </div>

                <div style="margin-top:24px;">
                    <label class="form-label">Ajuster les heures manuellement</label>

                    <div class="time-input-wrapper">
                        <div>
                            <div style="text-align:center;font-size:12px;font-weight:600;color:#64748b;margin-bottom:6px;">DÉBUT</div>
                            <div class="time-display">
                                <input type="time" name="heures_debut" id="heuresDebut" onchange="calculateDuration()">
                            </div>
                            <div class="increment-grid">
                                <button type="button" class="inc-btn plus" onclick="adjustTime('heuresDebut', 15)">+15min</button>
                                <button type="button" class="inc-btn plus" onclick="adjustTime('heuresDebut', 30)">+30min</button>
                                <button type="button" class="inc-btn minus" onclick="adjustTime('heuresDebut', -15)">-15min</button>
                                <button type="button" class="inc-btn minus" onclick="adjustTime('heuresDebut', -30)">-30min</button>
                            </div>
                        </div>

                        <div class="separator">→</div>

                        <div>
                            <div style="text-align:center;font-size:12px;font-weight:600;color:#64748b;margin-bottom:6px;">FIN</div>
                            <div class="time-display">
                                <input type="time" name="heures_fin" id="heuresFin" onchange="calculateDuration()">
                            </div>
                            <div class="increment-grid">
                                <button type="button" class="inc-btn plus" onclick="adjustTime('heuresFin', 15)">+15min</button>
                                <button type="button" class="inc-btn plus" onclick="adjustTime('heuresFin', 30)">+30min</button>
                                <button type="button" class="inc-btn minus" onclick="adjustTime('heuresFin', -15)">-15min</button>
                                <button type="button" class="inc-btn minus" onclick="adjustTime('heuresFin', -30)">-30min</button>
                            </div>
                        </div>
                    </div>

                    <div class="duration-box" id="durationBox" style="display:none;">
                        <div class="duration-label">DURÉE TOTALE</div>
                        <div class="duration-value" id="durationValue">0h00</div>
                    </div>

                    <div class="direct-hours">
                        <label>⚡ Saisie rapide: nombre d'heures</label>
                        <div style="display:flex;align-items:center;gap:12px;justify-content:center;">
                            <button type="button" class="arrow-btn" onclick="adjustDirectHours(-0.5)">−</button>
                            <input type="number" id="directHours" step="0.5" min="0" max="24" value="0" onchange="applyDirectHours()">
                            <button type="button" class="arrow-btn" onclick="adjustDirectHours(0.5)">+</button>
                        </div>
                        <div style="margin-top:8px;text-align:center;font-size:12px;color:#78350f;">
                            Définit automatiquement début/fin selon l'heure actuelle
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-title">🔲 Détails carrelage</div>

                <div class="form-group">
                    <label class="form-label">📏 Surface carrelée (m²)</label>
                    <input type="number" name="surface_carrelee" id="surfaceCarrelee" step="0.5" min="0" class="form-input" placeholder="0">
                    <div class="quick-btns">
                        <button type="button" class="quick-btn" onclick="addSurface(5)">+5</button>
                        <button type="button" class="quick-btn" onclick="addSurface(10)">+10</button>
                        <button type="button" class="quick-btn" onclick="addSurface(15)">+15</button>
                        <button type="button" class="quick-btn" onclick="addSurface(20)">+20</button>
                        <button type="button" class="quick-btn" onclick="addSurface(30)">+30</button>
                        <button type="button" class="quick-btn" onclick="setSurface(0)" style="color:#ef4444;">⟲ 0</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">🔲 Format carreaux</label>
                    <input type="text" name="format_carreaux" id="formatCarreaux" class="form-input" placeholder="Ex: 30×60">
                    <div class="quick-btns">
                        <button type="button" class="quick-btn" onclick="setFormat('20×20')">20×20</button>
                        <button type="button" class="quick-btn" onclick="setFormat('30×30')">30×30</button>
                        <button type="button" class="quick-btn" onclick="setFormat('30×60')">30×60</button>
                        <button type="button" class="quick-btn" onclick="setFormat('60×60')">60×60</button>
                        <button type="button" class="quick-btn" onclick="setFormat('120×60')">120×60</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">⊞ Type de pose</label>
                    <select name="type_pose" class="form-input">
                        <option value="">-- Choisir --</option>
                        <option value="Droite">Droite</option>
                        <option value="Droite décalée">Droite décalée</option>
                        <option value="Diagonale">Diagonale</option>
                        <option value="Chevron">Chevron</option>
                        <option value="Opus">Opus</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">📐 Zone de pose</label>
                    <select name="zone_pose" class="form-input">
                        <option value="">-- Choisir --</option>
                        <option value="Sol">Sol</option>
                        <option value="Mur">Mur</option>
                        <option value="Sol + Mur">Sol + Mur</option>
                        <option value="Escalier">Escalier</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">📝 Travaux réalisés</label>
                    <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <textarea name="travaux_realises" id="travauxRealises" class="form-input" rows="4" placeholder="Décrivez les travaux effectués..." style="flex: 1;"></textarea>
                        <button type="button" class="feature-btn voice-btn" id="voiceBtn" onclick="startVoiceRecognition()" style="flex-direction: column; min-width: 60px;">
                            <span style="font-size: 24px;">🎤</span>
                            <span style="font-size: 11px;">Dicter</span>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">💬 Observations</label>
                    <textarea name="observations" class="form-input" rows="3" placeholder="Remarques, problèmes..."></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-title">📸 Photos (max 10)</div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px;">
                    <button type="button" class="feature-btn" onclick="capturePhotoWithCategory('avant')" style="background: #3b82f6; color: white; border: none;">
                        📷 Avant
                    </button>
                    <button type="button" class="feature-btn" onclick="capturePhotoWithCategory('pendant')" style="background: #f59e0b; color: white; border: none;">
                        📷 Pendant
                    </button>
                    <button type="button" class="feature-btn" onclick="capturePhotoWithCategory('apres')" style="background: #10b981; color: white; border: none;">
                        📷 Après
                    </button>
                </div>

                <button type="button" class="btn btn-primary" onclick="document.getElementById('photoInput').click()">
                    <span>📷 Ajouter des photos</span>
                </button>

                <input type="file" id="photoInput" name="photos[]" accept="image/*" capture="environment" multiple style="display:none" onchange="previewPhotos(event)">

                <div class="photo-grid" id="photoGrid"></div>
            </div>

            <div id="gpsInfo"></div>

            <button type="submit" class="btn btn-primary" onclick="return validateBeforeSubmit(event)">
                <span>💾 Créer le rapport</span>
            </button>
        </form>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="../js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script src="js/offline-manager.js"></script>
    <script src="js/gps-manager.js"></script>
    <script src="js/voice-recognition.js"></script>
    <script src="js/templates-manager.js"></script>
    <script src="js/draft-manager.js"></script>
    <script src="js/camera-manager.js"></script>
    <script src="js/validation-manager.js"></script>
    <script src="js/stats-manager.js"></script>
    <script src="js/weather-manager.js"></script>
    <script src="js/qrcode-manager.js"></script>

    <script>
        let selectedZones = [];
        let photoCount = 0;
        const maxPhotos = 10;
        let allPhotos = [];
        let currentPhotoCategory = 'pendant';

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker-rapports.js')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.log('Service Worker error:', err));
        }

        window.addEventListener('load', async () => {
            const form = document.getElementById('rapportForm');
            if (window.draftManager) {
                window.draftManager.init(form);
            }

            if (window.statsManager) {
                await window.statsManager.displayStatsWidget('statsWidget');
            }

            if (window.weatherManager) {
                await window.weatherManager.displayWeather('weatherWidget');
                const weather = window.weatherManager.getWeatherForReport();
                if (weather) {
                    document.getElementById('meteoTemperature').value = weather.temperature;
                    document.getElementById('meteoCondition').value = weather.condition;
                }
            }

            window.addEventListener('offline', () => {
                document.getElementById('offlineIndicator').classList.add('show');
            });

            window.addEventListener('online', () => {
                document.getElementById('offlineIndicator').classList.remove('show');
            });

            if (!navigator.onLine) {
                document.getElementById('offlineIndicator').classList.add('show');
            }
        });

        function setPreset(type) {
            const debut = document.getElementById('heuresDebut');
            const fin = document.getElementById('heuresFin');
            const now = new Date();

            switch(type) {
                case 'demi-journee-matin':
                    debut.value = '07:00';
                    fin.value = '11:00';
                    break;
                case 'demi-journee-aprem':
                    debut.value = '13:00';
                    fin.value = '17:00';
                    break;
                case 'journee-complete':
                    debut.value = '07:00';
                    fin.value = '15:00';
                    break;
                case 'maintenant':
                    const h = now.getHours().toString().padStart(2, '0');
                    const m = now.getMinutes().toString().padStart(2, '0');
                    debut.value = h + ':' + m;
                    fin.value = '';
                    break;
            }
            calculateDuration();
        }

        function adjustTime(inputId, minutes) {
            const input = document.getElementById(inputId);
            if (!input.value) {
                const now = new Date();
                input.value = now.getHours().toString().padStart(2, '0') + ':' +
                              now.getMinutes().toString().padStart(2, '0');
            }

            const [h, m] = input.value.split(':').map(Number);
            const date = new Date(2000, 0, 1, h, m);
            date.setMinutes(date.getMinutes() + minutes);

            input.value = date.getHours().toString().padStart(2, '0') + ':' +
                          date.getMinutes().toString().padStart(2, '0');
            calculateDuration();
        }

        function adjustDirectHours(delta) {
            const input = document.getElementById('directHours');
            const newVal = Math.max(0, Math.min(24, parseFloat(input.value || 0) + delta));
            input.value = newVal;
            applyDirectHours();
        }

        function applyDirectHours() {
            const hours = parseFloat(document.getElementById('directHours').value || 0);
            if (hours <= 0) {
                return;
            }

            const now = new Date();
            const debut = new Date(now);
            const fin = new Date(now);
            fin.setHours(fin.getHours() + Math.floor(hours));
            fin.setMinutes(fin.getMinutes() + (hours % 1) * 60);

            document.getElementById('heuresDebut').value =
                debut.getHours().toString().padStart(2, '0') + ':' +
                debut.getMinutes().toString().padStart(2, '0');

            document.getElementById('heuresFin').value =
                fin.getHours().toString().padStart(2, '0') + ':' +
                fin.getMinutes().toString().padStart(2, '0');

            calculateDuration();
        }

        function calculateDuration() {
            const debut = document.getElementById('heuresDebut').value;
            const fin = document.getElementById('heuresFin').value;
            const box = document.getElementById('durationBox');
            const value = document.getElementById('durationValue');

            if (debut && fin) {
                const d = new Date('2000-01-01 ' + debut);
                const f = new Date('2000-01-01 ' + fin);
                const diff = (f - d) / 1000 / 3600;

                if (diff > 0) {
                    const h = Math.floor(diff);
                    const m = Math.round((diff - h) * 60);
                    value.textContent = h + 'h' + m.toString().padStart(2, '0');
                    box.style.display = 'block';
                    document.getElementById('directHours').value = diff.toFixed(1);
                } else {
                    box.style.display = 'none';
                }
            } else {
                box.style.display = 'none';
            }
        }

        async function captureGPS() {
            try {
                const position = await window.gpsManager.captureLocation();

                document.getElementById('gpsLatitude').value = position.latitude;
                document.getElementById('gpsLongitude').value = position.longitude;
                document.getElementById('gpsAccuracy').value = position.accuracy;

                const gpsInfo = document.getElementById('gpsInfo');
                gpsInfo.innerHTML = `
                    <div class="gps-info">
                        <div style="font-weight: 600; margin-bottom: 4px;">📍 Position capturée</div>
                        <div style="font-size: 12px;">${position.display}</div>
                        <div style="font-size: 12px;">Précision: ${position.accuracy}</div>
                        <a href="${position.link}" target="_blank" style="color: #166534; text-decoration: underline; font-size: 12px;">Voir sur Google Maps</a>
                    </div>
                `;
            } catch (error) {
                alert('Erreur GPS: ' + error);
            }
        }

        async function scanQRCode() {
            if (!window.qrcodeManager.isSupported()) {
                alert('Scan QR non supporté sur cet appareil');
                return;
            }

            window.qrcodeManager.showScanModal((data) => {
                const parsed = window.qrcodeManager.parseProjectQR(data);

                if (parsed.valid) {
                    document.getElementById('projetSelect').value = parsed.projetId;
                    loadProjectClients(parsed.projetId);

                    if (parsed.typeLieu) {
                        document.getElementById('typeLieu').value = parsed.typeLieu;
                        updateZoneLabel();
                    }

                    if (parsed.numeroLieu) {
                        document.getElementById('numeroLieu').value = parsed.numeroLieu;
                        updateZoneDisplay();
                    }

                    alert('✅ Projet chargé depuis QR Code!');
                } else {
                    alert('❌ ' + parsed.error);
                }
            });
        }

        function showTemplates() {
            const templates = window.templatesManager.getAll();

            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';

            let templatesHtml = '';
            for (const [key, template] of Object.entries(templates)) {
                templatesHtml += `
                    <div class="template-item" onclick="applyTemplate('${key}')">
                        <div style="font-weight: 600; margin-bottom: 4px;">${template.name}</div>
                        <div style="font-size: 12px; color: #64748b;">
                            ${template.surface_carrelee}m² | ${template.format_carreaux} | ${template.type_pose}
                        </div>
                    </div>
                `;
            }

            modal.innerHTML = `
                <div class="modal-content">
                    <h3 style="margin: 0 0 16px 0; color: #0891b2;">📋 Templates rapides</h3>
                    ${templatesHtml}
                    <button onclick="this.closest('.modal-overlay').remove()" style="width: 100%; padding: 12px; background: #64748b; color: white; border: none; border-radius: 8px; font-weight: 600; margin-top: 16px; cursor: pointer;">
                        Fermer
                    </button>
                </div>
            `;

            document.body.appendChild(modal);
        }

        function applyTemplate(key) {
            const form = document.getElementById('rapportForm');
            window.templatesManager.applyTemplate(key, form);
            document.querySelector('.modal-overlay').remove();
        }

        async function showCopyRapport() {
            try {
                const response = await fetch('api/copy-rapport.php?action=list');
                const data = await response.json();

                if (!data.success || data.rapports.length === 0) {
                    alert('Aucun rapport précédent trouvé');
                    return;
                }

                const modal = document.createElement('div');
                modal.className = 'modal-overlay show';

                let rapportsHtml = '';
                data.rapports.forEach(rapport => {
                    rapportsHtml += `
                        <div class="template-item" onclick="copyRapport(${rapport.rowid})">
                            <div style="font-weight: 600; margin-bottom: 4px;">${rapport.ref} - ${rapport.date}</div>
                            <div style="font-size: 12px; color: #64748b;">
                                ${rapport.zone} | ${rapport.surface}m² | ${rapport.projet}
                            </div>
                        </div>
                    `;
                });

                modal.innerHTML = `
                    <div class="modal-content">
                        <h3 style="margin: 0 0 16px 0; color: #0891b2;">📝 Copier un rapport</h3>
                        <div style="margin-bottom: 16px; padding: 12px; background: #fef3c7; border-radius: 8px; font-size: 13px; color: #92400e;">
                            ℹ️ Seules les données sont copiées (pas la date ni les photos)
                        </div>
                        ${rapportsHtml}
                        <button onclick="this.closest('.modal-overlay').remove()" style="width: 100%; padding: 12px; background: #64748b; color: white; border: none; border-radius: 8px; font-weight: 600; margin-top: 16px; cursor: pointer;">
                            Fermer
                        </button>
                    </div>
                `;

                document.body.appendChild(modal);
            } catch (error) {
                alert('Erreur: ' + error);
            }
        }

        async function copyRapport(id) {
            try {
                const response = await fetch(`api/copy-rapport.php?action=get&id=${id}`);
                const data = await response.json();

                if (!data.success) {
                    alert('Erreur: ' + data.error);
                    return;
                }

                const rapport = data.rapport;
                const form = document.getElementById('rapportForm');

                if (rapport.fk_projet) {
                    form.querySelector('[name="fk_projet"]').value = rapport.fk_projet;
                    await loadProjectClients(rapport.fk_projet);
                    if (rapport.fk_soc) {
                        setTimeout(() => {
                            form.querySelector('[name="fk_soc"]').value = rapport.fk_soc;
                        }, 500);
                    }
                }

                if (rapport.zone_travail) {
                    form.querySelector('[name="zone_travail"]').value = rapport.zone_travail;
                }
                if (rapport.heures_debut) {
                    form.querySelector('[name="heures_debut"]').value = rapport.heures_debut;
                }
                if (rapport.heures_fin) {
                    form.querySelector('[name="heures_fin"]').value = rapport.heures_fin;
                }
                if (rapport.surface_carrelee) {
                    form.querySelector('[name="surface_carrelee"]').value = rapport.surface_carrelee;
                }
                if (rapport.format_carreaux) {
                    form.querySelector('[name="format_carreaux"]').value = rapport.format_carreaux;
                }
                if (rapport.type_pose) {
                    form.querySelector('[name="type_pose"]').value = rapport.type_pose;
                }
                if (rapport.zone_pose) {
                    form.querySelector('[name="zone_pose"]').value = rapport.zone_pose;
                }
                if (rapport.travaux_realises) {
                    form.querySelector('[name="travaux_realises"]').value = rapport.travaux_realises;
                }
                if (rapport.observations) {
                    form.querySelector('[name="observations"]').value = rapport.observations;
                }

                document.querySelector('.modal-overlay').remove();
                alert('✅ Rapport copié avec succès!');

            } catch (error) {
                alert('Erreur: ' + error);
            }
        }

        async function startVoiceRecognition() {
            if (!window.voiceRecognition.isSupported()) {
                alert('Reconnaissance vocale non supportée');
                return;
            }

            const voiceBtn = document.getElementById('voiceBtn');
            voiceBtn.classList.add('listening');

            await window.voiceRecognition.startListening(
                (transcript, confidence) => {
                    voiceBtn.classList.remove('listening');

                    const parsed = window.voiceRecognition.parseWorkDescription(transcript);

                    const travauxInput = document.getElementById('travauxRealises');
                    travauxInput.value = transcript;

                    if (parsed.surface) {
                        document.getElementById('surfaceCarrelee').value = parsed.surface;
                    }
                    if (parsed.format) {
                        document.getElementById('formatCarreaux').value = parsed.format;
                    }
                    if (parsed.type_pose) {
                        document.querySelector('[name="type_pose"]').value = parsed.type_pose;
                    }

                    alert(`✅ Reconnaissance vocale terminée\n\nTranscription: "${transcript}"\n\nConfiance: ${Math.round(confidence * 100)}%`);
                },
                (error) => {
                    voiceBtn.classList.remove('listening');
                    alert('Erreur: ' + error);
                }
            );
        }

        async function capturePhotoWithCategory(category) {
            try {
                const photo = await window.cameraManager.captureWithCategory(category);
                addPhotoToGrid(photo);
            } catch (error) {
                console.error('Erreur photo:', error);
            }
        }

        function addPhotoToGrid(photoData) {
            if (allPhotos.length >= maxPhotos) {
                alert('Maximum ' + maxPhotos + ' photos');
                return;
            }

            allPhotos.push(photoData.file);
            const photoIndex = allPhotos.length - 1;

            const grid = document.getElementById('photoGrid');
            const div = document.createElement('div');
            div.className = 'photo-item';
            div.dataset.index = photoIndex;
            div.innerHTML = `
                <img src="${photoData.preview}">
                <select name="photo_categorie_${photoIndex}">
                    <option value="pendant" ${photoData.category === 'pendant' ? 'selected' : ''}>🟡 Pendant</option>
                    <option value="avant" ${photoData.category === 'avant' ? 'selected' : ''}>🔵 Avant</option>
                    <option value="apres" ${photoData.category === 'apres' ? 'selected' : ''}>🟢 Après</option>
                </select>
                <button type="button" class="remove" onclick="removePhoto(${photoIndex})">×</button>
            `;
            grid.appendChild(div);
        }

        function loadProjectClients(projetId) {
            const clientSelect = document.getElementById('clientSelect');
            const clientGroup = document.getElementById('clientGroup');

            if (!projetId) {
                clientGroup.style.display = 'none';
                return;
            }

            fetch('<?php echo DOL_URL_ROOT; ?>/custom/mv3pro_portail/rapports/ajax_client.php?projet_id=' + projetId)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.clients && data.clients.length > 0) {
                        clientSelect.innerHTML = '<option value="">-- Sélectionner --</option>';
                        data.clients.forEach(client => {
                            clientSelect.innerHTML += `<option value="${client.rowid}">${client.nom}</option>`;
                        });
                        if (data.clients.length === 1) {
                            clientSelect.value = data.clients[0].rowid;
                        }
                        clientGroup.style.display = '';
                    } else {
                        clientSelect.innerHTML = '<option value="">⚠️ Aucun client avec devis accepté</option>';
                        clientGroup.style.display = '';
                    }
                })
                .catch(e => console.error('Erreur:', e));
        }

        function updateZoneLabel() {
            const typeLieu = document.getElementById('typeLieu').value;
            const numeroField = document.getElementById('numeroField');
            const numeroLabel = document.getElementById('numeroLabel');
            const numeroLieu = document.getElementById('numeroLieu');

            if (typeLieu) {
                numeroField.style.display = '';
                if (typeLieu === 'Villa') {
                    numeroLabel.textContent = '🏡 Nom de la villa';
                    numeroLieu.placeholder = 'Ex: Villa Rose, Villa des Fleurs...';
                } else if (typeLieu === 'Appartement') {
                    numeroLabel.textContent = '🏢 Numéro d\'appartement';
                    numeroLieu.placeholder = 'Ex: A12, 3ème étage droite...';
                } else if (typeLieu === 'Bâtiment') {
                    numeroLabel.textContent = '🏗️ Référence bâtiment';
                    numeroLieu.placeholder = 'Ex: Bât A, Bloc C...';
                } else {
                    numeroLabel.textContent = '🏠 Référence';
                    numeroLieu.placeholder = 'Ex: Maison principale...';
                }
            } else {
                numeroField.style.display = 'none';
            }
            updateZoneDisplay();
        }

        function toggleZone(btn) {
            const zone = btn.getAttribute('data-zone');
            if (btn.classList.contains('selected')) {
                btn.classList.remove('selected');
                selectedZones = selectedZones.filter(z => z !== zone);
            } else {
                btn.classList.add('selected');
                selectedZones.push(zone);
            }
            updateZoneDisplay();
        }

        function updateZoneDisplay() {
            const typeLieu = document.getElementById('typeLieu').value;
            const numeroLieu = document.getElementById('numeroLieu').value;
            const zoneAutre = document.getElementById('zoneAutre').value;
            const preview = document.getElementById('zonePreview');
            const hidden = document.getElementById('zoneTravail');

            let parts = [];
            if (typeLieu) {
                let prefix = typeLieu;
                if (numeroLieu) {
                    prefix += ' ' + numeroLieu;
                }
                parts.push(prefix);
            }
            if (selectedZones.length > 0) {
                parts.push(selectedZones.join(', '));
            }
            if (zoneAutre) {
                parts.push(zoneAutre);
            }

            const finalText = parts.join(' - ');
            if (finalText) {
                preview.innerHTML = '✅ ' + finalText;
                hidden.value = finalText;
            } else {
                preview.innerHTML = '👉 Sélectionnez type de lieu et zones';
                hidden.value = '';
            }
        }

        document.getElementById('numeroLieu').addEventListener('input', updateZoneDisplay);

        function addSurface(val) {
            const input = document.getElementById('surfaceCarrelee');
            input.value = (parseFloat(input.value) || 0) + val;
        }

        function setSurface(val) {
            document.getElementById('surfaceCarrelee').value = val;
        }

        function setFormat(val) {
            document.getElementById('formatCarreaux').value = val;
        }

        function previewPhotos(event) {
            const files = Array.from(event.target.files);
            const grid = document.getElementById('photoGrid');
            const photoInput = document.getElementById('photoInput');

            files.forEach((file) => {
                if (allPhotos.length >= maxPhotos) {
                    alert('Maximum ' + maxPhotos + ' photos');
                    return;
                }

                allPhotos.push(file);
                const photoIndex = allPhotos.length - 1;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'photo-item';
                    div.dataset.index = photoIndex;
                    div.innerHTML = `
                        <img src="${e.target.result}">
                        <select name="photo_categorie_${photoIndex}">
                            <option value="pendant">🟡 Pendant</option>
                            <option value="avant">🔵 Avant</option>
                            <option value="apres">🟢 Après</option>
                        </select>
                        <button type="button" class="remove" onclick="removePhoto(${photoIndex})">×</button>
                    `;
                    grid.appendChild(div);
                };
                reader.readAsDataURL(file);
            });

            event.target.value = '';
        }

        function removePhoto(index) {
            allPhotos[index] = null;
            document.querySelector(`[data-index="${index}"]`).remove();
        }

        async function validateBeforeSubmit(event) {
            event.preventDefault();

            const formData = {
                surface_carrelee: document.getElementById('surfaceCarrelee').value,
                heures_debut: document.getElementById('heuresDebut').value,
                heures_fin: document.getElementById('heuresFin').value,
                format_carreaux: document.getElementById('formatCarreaux').value,
                travaux_realises: document.getElementById('travauxRealises').value,
                zone_travail: document.getElementById('zoneTravail').value,
                type_lieu: document.getElementById('typeLieu').value,
                photoCount: allPhotos.filter(p => p !== null).length
            };

            const result = window.validationManager.validate(formData);

            if (result.hasWarnings) {
                const shouldContinue = await window.validationManager.showValidationResults(result);
                if (!shouldContinue) {
                    return false;
                }
            }

            submitForm();
            return false;
        }

        function submitForm() {
            const photoInput = document.getElementById('photoInput');
            const dt = new DataTransfer();

            allPhotos.forEach(file => {
                if (file) {
                    dt.items.add(file);
                }
            });

            photoInput.files = dt.files;

            if (window.draftManager) {
                window.draftManager.clearDraft();
            }

            document.getElementById('rapportForm').submit();
        }

        function selectFrais(type, btn) {
            document.getElementById('fraisType').value = type;
            document.getElementById('fraisDetails').style.display = 'block';

            document.getElementById('fraisRepasBtn').classList.remove('selected');
            document.getElementById('fraisEssenceBtn').classList.remove('selected');
            btn.classList.add('selected');

            if (type === 'meal_lunch') {
                document.getElementById('fraisMontant').value = '19.00';
            } else if (type === 'fuel') {
                document.getElementById('fraisMontant').value = '50.00';
            }
        }

        function selectFraisMode(mode) {
            document.getElementById('fraisMode').value = mode;

            document.getElementById('modeCo').classList.remove('selected');
            document.getElementById('modeWorker').classList.remove('selected');

            if (mode === 'company_paid') {
                document.getElementById('modeCo').classList.add('selected');
            } else {
                document.getElementById('modeWorker').classList.add('selected');
            }
        }

        function setFraisMontant(amount) {
            document.getElementById('fraisMontant').value = amount.toFixed(2);
        }

        function previewFraisPhoto(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('fraisPhotoImg').src = e.target.result;
                    document.getElementById('fraisPhotoPreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }

        function cancelFrais() {
            document.getElementById('fraisType').value = '';
            document.getElementById('fraisDetails').style.display = 'none';
            document.getElementById('fraisRepasBtn').classList.remove('selected');
            document.getElementById('fraisEssenceBtn').classList.remove('selected');
            document.getElementById('fraisPhotoInput').value = '';
            document.getElementById('fraisPhotoPreview').style.display = 'none';
        }
    </script>
</body>
</html>
<?php $db->close(); ?>
