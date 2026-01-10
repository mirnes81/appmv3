<?php
/**
 * Nouveau rapport - Mobile COMPLET avec toutes les options
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
    $type_lieu = GETPOST('type_lieu', 'alpha');
    $numero_lieu = GETPOST('numero_lieu', 'alpha');
    $zone_travail = GETPOST('zone_travail', 'alpha');
    $heures_debut = GETPOST('heures_debut', 'alpha');
    $heures_fin = GETPOST('heures_fin', 'alpha');
    $surface_carrelee = GETPOST('surface_carrelee', 'alpha');
    $format_carreaux = GETPOST('format_carreaux', 'alpha');
    $type_pose = GETPOST('type_pose', 'alpha');
    $zone_pose = GETPOST('zone_pose', 'alpha');
    $travaux_realises = GETPOST('travaux_realises', 'restricthtml');
    $observations = GETPOST('observations', 'restricthtml');

    $temps_total = 0;
    if ($heures_debut && $heures_fin) {
        $debut = strtotime($heures_debut);
        $fin = strtotime($heures_fin);
        if ($fin > $debut) {
            $temps_total = round(($fin - $debut) / 3600, 2);
        }
    }

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport (";
    $sql .= "entity, fk_user, fk_projet, fk_soc, date_rapport, type_lieu, numero_lieu, zone_travail, heures_debut, heures_fin, temps_total,";
    $sql .= "surface_carrelee, format_carreaux, type_pose, zone_pose, travaux_realises, observations, statut, date_creation";
    $sql .= ") VALUES (";
    $sql .= $conf->entity.",";
    $sql .= (int)$fk_user.",";
    $sql .= ($fk_projet ? (int)$fk_projet : "NULL").",";
    $sql .= ($fk_soc ? (int)$fk_soc : "NULL").",";
    $sql .= "'".$db->escape($date_rapport)."',";
    $sql .= "'".$db->escape($type_lieu)."',";
    $sql .= "'".$db->escape($numero_lieu)."',";
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
    <title>Nouveau Rapport - MV3 PRO Mobile</title>
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
    </style>
</head>
<body>
    <div class="app-header">
        <div style="display:flex;align-items:center;gap:12px">
            <a href="list.php" style="color:white;font-size:24px;text-decoration:none">←</a>
            <div>
                <div class="app-header-title">➕ Nouveau Rapport</div>
                <div class="app-header-subtitle">Rapport complet de chantier</div>
            </div>
        </div>
    </div>

    <div class="app-container">
        <?php if ($error): ?>
            <div class="card" style="background:#fee2e2;color:#991b1b;">
                <strong>❌ <?php echo $error; ?></strong>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="rapportForm" accept-charset="UTF-8">
            <input type="hidden" name="token" value="<?php echo newToken(); ?>">
            <input type="hidden" name="action" value="create">

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
                <div class="card-title">⏱️ Heures de travail</div>

                <div class="form-group">
                    <label class="form-label">🕐 Début</label>
                    <input type="time" name="heures_debut" id="heuresDebut" class="form-input" onchange="calculateTime()">
                    <div class="quick-btns" style="margin-top:8px;">
                        <button type="button" class="quick-btn" onclick="setTime('heuresDebut', '07:00')">7h</button>
                        <button type="button" class="quick-btn" onclick="setTime('heuresDebut', '07:30')">7h30</button>
                        <button type="button" class="quick-btn" onclick="setTime('heuresDebut', '08:00')">8h</button>
                        <button type="button" class="quick-btn" onclick="setTime('heuresDebut', '08:30')">8h30</button>
                        <button type="button" class="quick-btn" onclick="setTime('heuresDebut', '09:00')">9h</button>
                        <button type="button" class="quick-btn" onclick="setTimeNow('heuresDebut')" style="background:#10b981;color:white;">🕐 Maintenant</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">🕐 Fin</label>
                    <input type="time" name="heures_fin" id="heuresFin" class="form-input" onchange="calculateTime()">
                    <div class="quick-btns" style="margin-top:8px;">
                        <button type="button" class="quick-btn" onclick="setTime('heuresFin', '12:00')">12h</button>
                        <button type="button" class="quick-btn" onclick="setTime('heuresFin', '16:00')">16h</button>
                        <button type="button" class="quick-btn" onclick="setTime('heuresFin', '17:00')">17h</button>
                        <button type="button" class="quick-btn" onclick="setTime('heuresFin', '18:00')">18h</button>
                        <button type="button" class="quick-btn" onclick="setTime('heuresFin', '19:00')">19h</button>
                        <button type="button" class="quick-btn" onclick="setTimeNow('heuresFin')" style="background:#10b981;color:white;">🕐 Maintenant</button>
                    </div>
                </div>

                <div class="time-info" id="timeInfo"></div>

                <div class="quick-btns" style="margin-top:12px; padding-top:12px; border-top:2px solid #e2e8f0;">
                    <button type="button" class="quick-btn" onclick="setJournee('07:00', '12:00')" style="background:#0891b2;color:white;">☀️ Matin (7h-12h)</button>
                    <button type="button" class="quick-btn" onclick="setJournee('13:00', '18:00')" style="background:#f59e0b;color:white;">🌆 Après-midi (13h-18h)</button>
                    <button type="button" class="quick-btn" onclick="setJournee('07:00', '18:00')" style="background:#8b5cf6;color:white;">🌞 Journée (7h-18h)</button>
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
                    <textarea name="travaux_realises" class="form-input" rows="4" placeholder="Décrivez les travaux effectués..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">💬 Observations</label>
                    <textarea name="observations" class="form-input" rows="3" placeholder="Remarques, problèmes..."></textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-title">📸 Photos (max 10)</div>

                <button type="button" class="btn btn-primary" onclick="document.getElementById('photoInput').click()">
                    <span>📷 Ajouter des photos</span>
                </button>

                <input type="file" id="photoInput" name="photos[]" accept="image/*" capture="environment" multiple style="display:none" onchange="previewPhotos(event)">

                <div class="photo-grid" id="photoGrid"></div>
            </div>

            <button type="submit" class="btn btn-primary">
                <span>💾 Créer le rapport</span>
            </button>
        </form>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="../js/app.js"></script>
    <script>
        let selectedZones = [];
        let photoCount = 0;
        const maxPhotos = 10;
        let allPhotos = [];

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
                        alert('⚠️ Aucun client trouvé pour ce projet.\nVérifiez que le projet a des devis acceptés.');
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

        function setTime(inputId, time) {
            document.getElementById(inputId).value = time;
            calculateTime();
        }

        function setTimeNow(inputId) {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            document.getElementById(inputId).value = hours + ':' + minutes;
            calculateTime();
        }

        function setJournee(debut, fin) {
            document.getElementById('heuresDebut').value = debut;
            document.getElementById('heuresFin').value = fin;
            calculateTime();
        }

        function calculateTime() {
            const debut = document.getElementById('heuresDebut').value;
            const fin = document.getElementById('heuresFin').value;
            const info = document.getElementById('timeInfo');

            if (debut && fin) {
                const d = new Date('2000-01-01 ' + debut);
                const f = new Date('2000-01-01 ' + fin);
                const diff = (f - d) / 1000 / 3600;

                if (diff > 0) {
                    const hours = Math.floor(diff);
                    const minutes = Math.round((diff - hours) * 60);
                    info.innerHTML = '<div style="padding:12px;background:#dcfce7;border-radius:8px;color:#166534;font-weight:600;text-align:center;margin-top:12px;">⏱️ Durée totale : ' + hours + 'h' + (minutes > 0 ? minutes.toString().padStart(2, '0') : '00') + '</div>';
                } else {
                    info.innerHTML = '<div style="padding:12px;background:#fee2e2;border-radius:8px;color:#991b1b;font-weight:600;text-align:center;margin-top:12px;">⚠️ L\'heure de fin doit être après l\'heure de début</div>';
                }
            } else {
                info.innerHTML = '';
            }
        }

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

        // Ajouter les photos au formulaire avant soumission
        document.getElementById('rapportForm').addEventListener('submit', function(e) {
            const photoInput = document.getElementById('photoInput');
            const dt = new DataTransfer();

            allPhotos.forEach(file => {
                if (file) {
                    dt.items.add(file);
                }
            });

            photoInput.files = dt.files;
            console.log('Fichiers à envoyer:', photoInput.files.length);
        });

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
