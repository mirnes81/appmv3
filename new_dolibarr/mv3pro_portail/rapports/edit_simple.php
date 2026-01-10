<?php
/**
 * Édition rapport SIMPLIFIÉE - Version Carreleur Mobile
 * Formulaire optimisé avec boutons rapides
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->loadLangs(array("projects", "mv3pro_portail@mv3pro_portail"));

if (!$user->rights->mv3pro_portail->write) {
    accessforbidden();
}

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$error = '';
$is_admin = $user->admin || $user->rights->mv3pro_portail->admin;

// SAUVEGARDE
if ($action == 'save') {
    $fk_user = GETPOST('fk_user', 'int');
    $fk_projet = GETPOST('fk_projet', 'int');
    $fk_soc = GETPOST('fk_soc', 'int');
    $date_rapport = GETPOST('date_rapport', 'alpha');
    $type_lieu = GETPOST('type_lieu', 'alpha');
    $numero_lieu = GETPOST('numero_lieu', 'alpha');
    $zone_travail = GETPOST('zone_travail', 'alpha');
    $materiaux_utilises = GETPOST('materiaux_utilises', 'restricthtml');
    $heures_debut = GETPOST('heures_debut', 'alpha');
    $heures_fin = GETPOST('heures_fin', 'alpha');
    $surface_carrelee = GETPOST('surface_carrelee', 'alpha');
    $format_carreaux = GETPOST('format_carreaux', 'alpha');
    $type_pose = GETPOST('type_pose', 'alpha');
    $zone_pose = GETPOST('zone_pose', 'alpha');
    $travaux_realises = GETPOST('travaux_realises', 'restricthtml');
    $observations = GETPOST('observations', 'restricthtml');

    // Champs admin
    $description = GETPOST('description', 'restricthtml');
    $type_carrelage = GETPOST('type_carrelage', 'alpha');
    $colle_utilisee = GETPOST('colle_utilisee', 'alpha');
    $joint_utilise = GETPOST('joint_utilise', 'alpha');
    $materiel_manquant = GETPOST('materiel_manquant', 'restricthtml');
    $problemes_rencontres = GETPOST('problemes_rencontres', 'restricthtml');
    $avancement_pourcent = GETPOST('avancement_pourcent', 'int');

    // Gestion des photos supprimées
    $photos_to_delete = GETPOST('delete_photos', 'array');
    if ($photos_to_delete && $id > 0) {
        foreach ($photos_to_delete as $photo_id) {
            $sql_del = "SELECT path FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE rowid = ".(int)$photo_id." AND fk_rapport = ".(int)$id;
            $resql_del = $db->query($sql_del);
            if ($resql_del && $obj_del = $db->fetch_object($resql_del)) {
                if (file_exists(DOL_DATA_ROOT.$obj_del->path)) {
                    unlink(DOL_DATA_ROOT.$obj_del->path);
                }
            }
            $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE rowid = ".(int)$photo_id." AND fk_rapport = ".(int)$id;
            $db->query($sql_del);
        }
    }

    // Calculer le temps total
    $temps_total = 0;
    if ($heures_debut && $heures_fin) {
        $debut = strtotime($heures_debut);
        $fin = strtotime($heures_fin);
        if ($fin > $debut) {
            $temps_total = round(($fin - $debut) / 3600, 2);
        }
    }

    if ($id > 0) {
        // UPDATE
        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_rapport SET";
        $sql .= " fk_user = ".(int)$fk_user.",";
        $sql .= " fk_projet = ".($fk_projet ? (int)$fk_projet : "NULL").",";
        $sql .= " fk_soc = ".($fk_soc ? (int)$fk_soc : "NULL").",";
        $sql .= " date_rapport = '".$db->escape($date_rapport)."',";
        $sql .= " type_lieu = '".$db->escape($type_lieu)."',";
        $sql .= " numero_lieu = '".$db->escape($numero_lieu)."',";
        $sql .= " zone_travail = '".$db->escape($zone_travail)."',";
        $sql .= " materiaux_utilises = '".$db->escape($materiaux_utilises)."',";
        $sql .= " heures_debut = ".($heures_debut ? "'".$db->escape($heures_debut)."'" : "NULL").",";
        $sql .= " heures_fin = ".($heures_fin ? "'".$db->escape($heures_fin)."'" : "NULL").",";
        $sql .= " temps_total = ".$temps_total.",";
        $sql .= " surface_carrelee = ".($surface_carrelee ? (float)$surface_carrelee : "NULL").",";
        $sql .= " format_carreaux = '".$db->escape($format_carreaux)."',";
        $sql .= " type_pose = '".$db->escape($type_pose)."',";
        $sql .= " zone_pose = '".$db->escape($zone_pose)."',";
        $sql .= " travaux_realises = '".$db->escape($travaux_realises)."',";
        $sql .= " observations = '".$db->escape($observations)."'";

        if ($is_admin) {
            $sql .= ", description = '".$db->escape($description)."'";
            $sql .= ", type_carrelage = '".$db->escape($type_carrelage)."'";
            $sql .= ", colle_utilisee = '".$db->escape($colle_utilisee)."'";
            $sql .= ", joint_utilise = '".$db->escape($joint_utilise)."'";
            $sql .= ", materiel_manquant = '".$db->escape($materiel_manquant)."'";
            $sql .= ", problemes_rencontres = '".$db->escape($problemes_rencontres)."'";
            $sql .= ", avancement_pourcent = ".(int)$avancement_pourcent;
        }

        $sql .= " WHERE rowid = ".(int)$id;

        if ($db->query($sql)) {
            $rapport_id = $id;

            // Gestion photos
            if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                $upload_dir = DOL_DATA_ROOT.'/mv3pro_portail/rapports/'.$rapport_id;
                if (!is_dir($upload_dir)) {
                    dol_mkdir($upload_dir);
                }

                for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
                    if ($_FILES['photos']['error'][$i] === 0) {
                        $tmp_name = $_FILES['photos']['tmp_name'][$i];
                        $original_name = $_FILES['photos']['name'][$i];
                        $safe_name = dol_sanitizeFileName($original_name);
                        $unique_name = time().'_'.$i.'_'.$safe_name;
                        $target = $upload_dir.'/'.$unique_name;

                        if (move_uploaded_file($tmp_name, $target)) {
                            $relative_path = '/mv3pro_portail/rapports/'.$rapport_id.'/'.$unique_name;
                            $categorie = GETPOST('photo_categorie_'.$i, 'alpha');
                            $legende = GETPOST('photo_legende_'.$i, 'restricthtml');

                            $sql_photo = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport_photo";
                            $sql_photo .= " (fk_rapport, path, filename, position, categorie, legende)";
                            $sql_photo .= " VALUES (".$rapport_id.", '".$db->escape($relative_path)."', '".$db->escape($unique_name)."', ".$i.", '".$db->escape($categorie)."', '".$db->escape($legende)."')";
                            $db->query($sql_photo);
                        }
                    }
                }
            }

            header("Location: view.php?id=".$rapport_id."&success=1");
            exit;
        } else {
            $error = "Erreur lors de la modification: ".$db->lasterror();
        }
    } else {
        // INSERT
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport (";
        $sql .= "entity, fk_user, fk_projet, fk_soc, date_rapport, type_lieu, numero_lieu, zone_travail, heures_debut, heures_fin, temps_total,";
        $sql .= "surface_carrelee, format_carreaux, type_pose, zone_pose, travaux_realises, observations, statut";

        if ($is_admin) {
            $sql .= ", description, type_carrelage, colle_utilisee, joint_utilise, materiel_manquant, problemes_rencontres, avancement_pourcent";
        }

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
        $sql .= "'brouillon'";

        if ($is_admin) {
            $sql .= ", '".$db->escape($description)."'";
            $sql .= ", '".$db->escape($type_carrelage)."'";
            $sql .= ", '".$db->escape($colle_utilisee)."'";
            $sql .= ", '".$db->escape($joint_utilise)."'";
            $sql .= ", '".$db->escape($materiel_manquant)."'";
            $sql .= ", '".$db->escape($problemes_rencontres)."'";
            $sql .= ", ".(int)$avancement_pourcent;
        }

        $sql .= ")";

        if ($db->query($sql)) {
            $rapport_id = $db->last_insert_id(MAIN_DB_PREFIX.'mv3_rapport');

            // Gestion photos
            if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                $upload_dir = DOL_DATA_ROOT.'/mv3pro_portail/rapports/'.$rapport_id;
                dol_mkdir($upload_dir);

                for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
                    if ($_FILES['photos']['error'][$i] === 0) {
                        $tmp_name = $_FILES['photos']['tmp_name'][$i];
                        $original_name = $_FILES['photos']['name'][$i];
                        $safe_name = dol_sanitizeFileName($original_name);
                        $unique_name = time().'_'.$i.'_'.$safe_name;
                        $target = $upload_dir.'/'.$unique_name;

                        if (move_uploaded_file($tmp_name, $target)) {
                            $relative_path = '/mv3pro_portail/rapports/'.$rapport_id.'/'.$unique_name;
                            $categorie = GETPOST('photo_categorie_'.$i, 'alpha');
                            $legende = GETPOST('photo_legende_'.$i, 'restricthtml');

                            $sql_photo = "INSERT INTO ".MAIN_DB_PREFIX."mv3_rapport_photo";
                            $sql_photo .= " (fk_rapport, path, filename, position, categorie, legende)";
                            $sql_photo .= " VALUES (".$rapport_id.", '".$db->escape($relative_path)."', '".$db->escape($unique_name)."', ".$i.", '".$db->escape($categorie)."', '".$db->escape($legende)."')";
                            $db->query($sql_photo);
                        }
                    }
                }
            }

            header("Location: view.php?id=".$rapport_id."&success=1");
            exit;
        } else {
            $error = "Erreur lors de la création: ".$db->lasterror();
        }
    }
}

// Charger le rapport si modification
$rapport = null;
$photos = array();
if ($id > 0) {
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_rapport WHERE rowid = ".(int)$id." AND entity IN (".getEntity('project').")";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $rapport = $db->fetch_object($resql);

        // Charger les photos
        $sql_photos = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_rapport_photo WHERE fk_rapport = ".(int)$id." ORDER BY position";
        $resql_photos = $db->query($sql_photos);
        if ($resql_photos) {
            while ($obj_photo = $db->fetch_object($resql_photos)) {
                $photos[] = $obj_photo;
            }
        }
    }
}

// Charger les utilisateurs
$sql_users = "SELECT rowid, firstname, lastname FROM ".MAIN_DB_PREFIX."user WHERE statut = 1 AND entity IN (0,".$conf->entity.") ORDER BY lastname, firstname";
$resql_users = $db->query($sql_users);

// Charger les projets
$sql_projets = "SELECT p.rowid, p.ref, p.title, p.fk_soc FROM ".MAIN_DB_PREFIX."projet as p WHERE p.fk_statut = 1 AND p.entity IN (".getEntity('project').") ORDER BY p.ref";
$resql_projets = $db->query($sql_projets);

// Charger configuration pour affichage matériaux
$show_materiaux = 1; // Par défaut activé
$sql_config = "SELECT value FROM ".MAIN_DB_PREFIX."mv3_config WHERE name = 'RAPPORT_SHOW_MATERIAUX' AND entity = ".(int)$conf->entity;
$resql_config = $db->query($sql_config);
if ($resql_config && $db->num_rows($resql_config) > 0) {
    $obj_config = $db->fetch_object($resql_config);
    $show_materiaux = (int)$obj_config->value;
}

llxHeader('', ($id ? 'Modifier' : 'Créer').' un rapport');

$urllist = dol_buildpath('/mv3pro_portail/rapports/list.php', 1);
$titre = $id ? '✏️ Modifier le rapport' : '🏗️ Nouveau rapport';
print load_fiche_titre($titre, '<a class="butAction" href="'.$urllist.'">Retour à la liste</a>');

if ($error) {
    setEventMessages($error, null, 'errors');
}
?>

<style>
.mv3-simple-form {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.mv3-field {
    margin-bottom: 20px;
}
.mv3-label {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}
.mv3-label.required::after {
    content: ' *';
    color: #ef4444;
}
.mv3-input {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.2s;
}
.mv3-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
}
.mv3-quick-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 8px;
    margin-top: 8px;
}
.mv3-btn-quick {
    padding: 12px;
    border: 2px solid #cbd5e1;
    border-radius: 8px;
    background: #fff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.mv3-btn-quick:hover {
    background: #f1f5f9;
    border-color: #3b82f6;
}
.mv3-btn-quick.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}
.mv3-section {
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 20px;
}
.mv3-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.mv3-photo-simple {
    text-align: center;
    padding: 40px 20px;
    border: 3px dashed #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.3s;
}
.mv3-photo-simple:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}
.mv3-btn-group {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}
.mv3-btn {
    flex: 1;
    padding: 16px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.mv3-btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
}
.mv3-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16,185,129,0.3);
}
.mv3-btn-secondary {
    background: #f1f5f9;
    color: #64748b;
}
.mv3-time-display {
    background: #f0fdf4;
    border-left: 4px solid #10b981;
    padding: 16px;
    border-radius: 8px;
    text-align: center;
    margin-top: 12px;
}
.mv3-time-value {
    font-size: 28px;
    font-weight: 700;
    color: #10b981;
}
@media (max-width: 768px) {
    .mv3-quick-buttons {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<form method="POST" action="<?php echo $_SERVER["PHP_SELF"].($id ? '?id='.$id : ''); ?>" accept-charset="UTF-8" enctype="multipart/form-data" class="mv3-simple-form">
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="save">
    <?php if ($id) echo '<input type="hidden" name="id" value="'.$id.'">'; ?>

    <!-- Carreleur -->
    <div class="mv3-field">
        <label class="mv3-label required">👷 Carreleur</label>
        <select name="fk_user" class="mv3-input" required>
            <option value="">-- Sélectionner --</option>
            <?php
            while ($obj_user = $db->fetch_object($resql_users)) {
                $selected = '';
                if ($rapport && $rapport->fk_user == $obj_user->rowid) {
                    $selected = 'selected';
                } elseif (!$rapport && $obj_user->rowid == $user->id) {
                    $selected = 'selected';
                }
                echo '<option value="'.$obj_user->rowid.'" '.$selected.'>'.$obj_user->firstname.' '.$obj_user->lastname.'</option>';
            }
            ?>
        </select>
    </div>

    <!-- Date -->
    <div class="mv3-field">
        <label class="mv3-label required">📅 Date</label>
        <input type="date" name="date_rapport" class="mv3-input" value="<?php echo $rapport ? $rapport->date_rapport : date('Y-m-d'); ?>" required>
    </div>

    <!-- Projet -->
    <div class="mv3-field">
        <label class="mv3-label required">🏗️ Chantier / Projet</label>
        <select name="fk_projet" id="fk_projet" class="mv3-input" required onchange="loadProjectClients(this.value)">
            <option value="">-- Sélectionner --</option>
            <?php
            while ($obj_projet = $db->fetch_object($resql_projets)) {
                $selected = ($rapport && $rapport->fk_projet == $obj_projet->rowid) ? 'selected' : '';
                echo '<option value="'.$obj_projet->rowid.'" '.$selected.' data-socid="'.$obj_projet->fk_soc.'">'.$obj_projet->ref.' - '.dol_trunc($obj_projet->title, 40).'</option>';
            }
            ?>
        </select>
    </div>

    <!-- Client -->
    <div class="mv3-field" id="client_row" style="display:none;">
        <label class="mv3-label">🏢 Client</label>
        <?php $saved_client = ($rapport && $rapport->fk_soc) ? ' data-saved-value="'.$rapport->fk_soc.'"' : ''; ?>
        <select name="fk_soc" id="fk_soc" class="mv3-input"<?php echo $saved_client; ?>>
            <option value="">-- Sélectionner --</option>
        </select>
    </div>

    <!-- Type de lieu -->
    <div class="mv3-field">
        <label class="mv3-label required">🏠 Type de lieu</label>
        <select name="type_lieu" id="type_lieu" class="mv3-input" required onchange="updateZoneLabel()">
            <option value="">-- Sélectionner --</option>
            <?php
            $types_lieu = array('Villa' => '🏡 Villa', 'Appartement' => '🏢 Appartement', 'Bâtiment' => '🏗️ Bâtiment', 'Maison' => '🏠 Maison');
            foreach ($types_lieu as $val => $label) {
                $selected = ($rapport && isset($rapport->type_lieu) && $rapport->type_lieu == $val) ? 'selected' : '';
                echo '<option value="'.$val.'" '.$selected.'>'.$label.'</option>';
            }
            ?>
        </select>
    </div>

    <!-- Numéro/Nom du lieu -->
    <div class="mv3-field" id="numero_field" style="display:none;">
        <label class="mv3-label" id="numero_label">📌 Numéro/Nom</label>
        <input type="text" name="numero_lieu" id="numero_lieu" class="mv3-input" value="<?php echo ($rapport && isset($rapport->numero_lieu) ? dol_escape_htmltag($rapport->numero_lieu) : ''); ?>" placeholder="Ex: A12, Villa Rose..." oninput="updateZoneDisplay()">
    </div>

    <!-- Zone de travail -->
    <div class="mv3-field">
        <label class="mv3-label required">📍 Zone(s) de travail</label>
        <div class="mv3-quick-buttons" id="zone_buttons">
            <button type="button" class="mv3-btn-quick" data-zone="Salon" onclick="toggleZone('Salon')">🛋️ Salon</button>
            <button type="button" class="mv3-btn-quick" data-zone="Cuisine" onclick="toggleZone('Cuisine')">🍳 Cuisine</button>
            <button type="button" class="mv3-btn-quick" data-zone="Chambre" onclick="toggleZone('Chambre')">🛏️ Chambre</button>
            <button type="button" class="mv3-btn-quick" data-zone="Salle de bain" onclick="toggleZone('Salle de bain')">🛁 SDB</button>
            <button type="button" class="mv3-btn-quick" data-zone="Douche" onclick="toggleZone('Douche')">🚿 Douche</button>
            <button type="button" class="mv3-btn-quick" data-zone="WC" onclick="toggleZone('WC')">🚽 WC</button>
            <button type="button" class="mv3-btn-quick" data-zone="Couloir" onclick="toggleZone('Couloir')">🚪 Couloir</button>
            <button type="button" class="mv3-btn-quick" data-zone="Entrée" onclick="toggleZone('Entrée')">🚪 Entrée</button>
            <button type="button" class="mv3-btn-quick" data-zone="Terrasse" onclick="toggleZone('Terrasse')">🌴 Terrasse</button>
            <button type="button" class="mv3-btn-quick" data-zone="Balcon" onclick="toggleZone('Balcon')">🪴 Balcon</button>
            <button type="button" class="mv3-btn-quick" data-zone="Garage" onclick="toggleZone('Garage')">🚗 Garage</button>
            <button type="button" class="mv3-btn-quick" data-zone="Escalier" onclick="toggleZone('Escalier')">🪜 Escalier</button>
        </div>
        <input type="text" name="zone_autre" id="zone_autre" class="mv3-input" placeholder="Autre zone..." style="margin-top:8px;" oninput="updateZoneDisplay()">
        <input type="hidden" name="zone_travail" id="zone_travail_input" required>
        <input type="hidden" id="selected_zones" value="<?php echo ($rapport ? dol_escape_htmltag($rapport->zone_travail) : ''); ?>">
        <div style="margin-top:12px;padding:12px;background:#eff6ff;border-radius:8px;min-height:40px;font-weight:600;color:#1e40af;font-size:14px;" id="zone_preview">
            <?php echo ($rapport && $rapport->zone_travail) ? '✅ '.dol_escape_htmltag($rapport->zone_travail) : '👉 Sélectionnez type de lieu et zones'; ?>
        </div>
        <div style="font-size:12px;color:#64748b;margin-top:8px;">
            💡 Sélectionnez plusieurs zones et/ou ajoutez une zone personnalisée
        </div>
    </div>

    <!-- HEURES -->
    <div class="mv3-section">
        <div class="mv3-section-title">⏰ Horaires</div>

        <div class="mv3-field">
            <label class="mv3-label">Heure début</label>
            <input type="time" name="heures_debut" id="heures_debut" class="mv3-input" value="<?php echo ($rapport && $rapport->heures_debut ? substr($rapport->heures_debut, 0, 5) : '08:00'); ?>" onchange="calculateTime()">
            <div class="mv3-quick-buttons">
                <button type="button" class="mv3-btn-quick" onclick="setTime('heures_debut', '07:00')">7h</button>
                <button type="button" class="mv3-btn-quick" onclick="setTime('heures_debut', '08:00')">8h</button>
                <button type="button" class="mv3-btn-quick" onclick="setTime('heures_debut', '09:00')">9h</button>
            </div>
        </div>

        <div class="mv3-field">
            <label class="mv3-label">Heure fin</label>
            <input type="time" name="heures_fin" id="heures_fin" class="mv3-input" value="<?php echo ($rapport && $rapport->heures_fin ? substr($rapport->heures_fin, 0, 5) : '17:00'); ?>" onchange="calculateTime()">
            <div class="mv3-quick-buttons">
                <button type="button" class="mv3-btn-quick" onclick="setTime('heures_fin', '16:00')">16h</button>
                <button type="button" class="mv3-btn-quick" onclick="setTime('heures_fin', '17:00')">17h</button>
                <button type="button" class="mv3-btn-quick" onclick="setTime('heures_fin', '18:00')">18h</button>
            </div>
        </div>

        <div class="mv3-time-display">
            <div style="font-size:13px;color:#15803d;font-weight:600;margin-bottom:4px;">TEMPS TOTAL</div>
            <div id="temps_display" class="mv3-time-value">8.00 h</div>
        </div>

        <div class="mv3-quick-buttons" style="margin-top:12px; padding-top:12px; border-top:2px solid #e2e8f0; grid-template-columns: repeat(3, 1fr);">
            <button type="button" class="mv3-btn-quick" onclick="setJournee('07:00', '12:00')" style="background:#0891b2;color:white;padding:14px;">☀️ Matin<br>(7h-12h)</button>
            <button type="button" class="mv3-btn-quick" onclick="setJournee('13:00', '18:00')" style="background:#f59e0b;color:white;padding:14px;">🌆 Après-midi<br>(13h-18h)</button>
            <button type="button" class="mv3-btn-quick" onclick="setJournee('07:00', '18:00')" style="background:#8b5cf6;color:white;padding:14px;">🌞 Journée<br>(7h-18h)</button>
        </div>
    </div>

    <!-- M² POSÉS -->
    <div class="mv3-section" style="background:#f0fdf4;">
        <div class="mv3-section-title" style="color:#15803d;">📏 Surface carrelée (m²)</div>

        <input type="number" name="surface_carrelee" id="surface_carrelee" step="0.5" min="0" class="mv3-input" value="<?php echo ($rapport ? $rapport->surface_carrelee : ''); ?>" placeholder="0">

        <div class="mv3-quick-buttons">
            <button type="button" class="mv3-btn-quick" onclick="addSurface(5)">+5</button>
            <button type="button" class="mv3-btn-quick" onclick="addSurface(10)">+10</button>
            <button type="button" class="mv3-btn-quick" onclick="addSurface(15)">+15</button>
            <button type="button" class="mv3-btn-quick" onclick="addSurface(20)">+20</button>
            <button type="button" class="mv3-btn-quick" onclick="addSurface(30)">+30</button>
            <button type="button" class="mv3-btn-quick" onclick="setSurface(0)" style="color:#ef4444;">⟲ 0</button>
        </div>
    </div>

    <!-- FORMAT CARREAUX -->
    <div class="mv3-field">
        <label class="mv3-label">🔲 Format carreaux</label>
        <input type="text" name="format_carreaux" id="format_carreaux" class="mv3-input" value="<?php echo ($rapport ? dol_escape_htmltag($rapport->format_carreaux) : ''); ?>">
        <div class="mv3-quick-buttons">
            <button type="button" class="mv3-btn-quick" onclick="setFormat('20×20')">20×20</button>
            <button type="button" class="mv3-btn-quick" onclick="setFormat('30×30')">30×30</button>
            <button type="button" class="mv3-btn-quick" onclick="setFormat('30×60')">30×60</button>
            <button type="button" class="mv3-btn-quick" onclick="setFormat('60×60')">60×60</button>
            <button type="button" class="mv3-btn-quick" onclick="setFormat('120×60')">120×60</button>
        </div>
    </div>

    <!-- TYPE POSE -->
    <div class="mv3-field">
        <label class="mv3-label">⊞ Type de pose</label>
        <select name="type_pose" id="type_pose" class="mv3-input">
            <option value="">-- Choisir --</option>
            <?php
            $poses = array('Droite', 'Droite décalée', 'Diagonale', 'Chevron', 'Opus');
            foreach ($poses as $pose) {
                $selected = ($rapport && $rapport->type_pose == $pose) ? 'selected' : '';
                echo '<option value="'.$pose.'" '.$selected.'>'.$pose.'</option>';
            }
            ?>
        </select>
    </div>

    <!-- ZONE POSE -->
    <div class="mv3-field">
        <label class="mv3-label">📐 Zone de pose</label>
        <select name="zone_pose" id="zone_pose" class="mv3-input">
            <option value="">-- Choisir --</option>
            <?php
            $zones = array('Sol', 'Mur', 'Sol + Mur', 'Escalier');
            foreach ($zones as $zone) {
                $selected = ($rapport && $rapport->zone_pose == $zone) ? 'selected' : '';
                echo '<option value="'.$zone.'" '.$selected.'>'.$zone.'</option>';
            }
            ?>
        </select>
    </div>

    <!-- TRAVAUX RÉALISÉS -->
    <div class="mv3-field">
        <label class="mv3-label">📝 Travaux réalisés</label>
        <textarea name="travaux_realises" class="mv3-input" rows="4" placeholder="Décrivez les travaux effectués..."><?php echo ($rapport ? dol_escape_htmltag($rapport->travaux_realises) : ''); ?></textarea>
    </div>

    <?php if ($show_materiaux): ?>
    <!-- MATÉRIAUX UTILISÉS -->
    <div class="mv3-field">
        <label class="mv3-label">🧱 Matériaux / Produits utilisés</label>
        <textarea name="materiaux_utilises" class="mv3-input" rows="4" placeholder="Ex: Carrelage 60x60 gris, Colle X150, Joint Y200..."><?php echo ($rapport && isset($rapport->materiaux_utilises) ? dol_escape_htmltag($rapport->materiaux_utilises) : ''); ?></textarea>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">
            💡 Notez les matériaux et produits que vous avez utilisés aujourd'hui
        </div>
    </div>
    <?php endif; ?>

    <!-- OBSERVATIONS -->
    <div class="mv3-field">
        <label class="mv3-label">💬 Observations</label>
        <textarea name="observations" class="mv3-input" rows="3" placeholder="Remarques, problèmes..."><?php echo ($rapport ? dol_escape_htmltag($rapport->observations) : ''); ?></textarea>
    </div>

    <?php if ($is_admin): ?>
    <!-- CHAMPS ADMIN -->
    <div class="mv3-section" style="background:#fef3c7;border:2px solid #f59e0b;">
        <div class="mv3-section-title" style="color:#92400e;">🔐 CHAMPS ADMINISTRATEUR</div>

        <div class="mv3-field">
            <label class="mv3-label">Description générale</label>
            <textarea name="description" class="mv3-input" rows="2"><?php echo ($rapport ? dol_escape_htmltag($rapport->description) : ''); ?></textarea>
        </div>

        <div class="mv3-field">
            <label class="mv3-label">Type carrelage</label>
            <input type="text" name="type_carrelage" class="mv3-input" value="<?php echo ($rapport ? dol_escape_htmltag($rapport->type_carrelage) : ''); ?>">
        </div>

        <div class="mv3-field">
            <label class="mv3-label">Colle utilisée</label>
            <input type="text" name="colle_utilisee" class="mv3-input" value="<?php echo ($rapport ? dol_escape_htmltag($rapport->colle_utilisee) : ''); ?>">
        </div>

        <div class="mv3-field">
            <label class="mv3-label">Joint utilisé</label>
            <input type="text" name="joint_utilise" class="mv3-input" value="<?php echo ($rapport ? dol_escape_htmltag($rapport->joint_utilise) : ''); ?>">
        </div>

        <div class="mv3-field">
            <label class="mv3-label">Matériel manquant</label>
            <textarea name="materiel_manquant" class="mv3-input" rows="2"><?php echo ($rapport ? dol_escape_htmltag($rapport->materiel_manquant) : ''); ?></textarea>
        </div>

        <div class="mv3-field">
            <label class="mv3-label">Problèmes rencontrés</label>
            <textarea name="problemes_rencontres" class="mv3-input" rows="2"><?php echo ($rapport ? dol_escape_htmltag($rapport->problemes_rencontres) : ''); ?></textarea>
        </div>

        <div class="mv3-field">
            <label class="mv3-label">% Avancement</label>
            <input type="range" name="avancement_pourcent" id="avancement_pourcent" min="0" max="100" value="<?php echo ($rapport ? $rapport->avancement_pourcent : '0'); ?>" class="mv3-input" oninput="document.getElementById('avancement_display').textContent = this.value + '%'">
            <div style="text-align:center;font-size:20px;font-weight:700;color:#10b981;margin-top:8px;" id="avancement_display"><?php echo ($rapport ? $rapport->avancement_pourcent : '0'); ?>%</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PHOTOS -->
    <div class="mv3-section">
        <div class="mv3-section-title">📷 Photos (max 10)</div>

        <?php
        $remaining_slots = 10 - count($photos);

        if (count($photos) > 0) {
            echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;margin-bottom:16px;">';
            foreach ($photos as $photo) {
                $photo_url = DOL_URL_ROOT.'/custom/mv3pro_portail/rapports/photo.php?rapport_id='.$id.'&file='.urlencode(basename($photo->path));
                $cat_colors = array('avant'=>'#3b82f6', 'pendant'=>'#f59e0b', 'apres'=>'#10b981', 'probleme'=>'#ef4444');
                $cat_color = $cat_colors[$photo->categorie] ?? '#64748b';

                echo '<div style="position:relative;border:3px solid '.$cat_color.';border-radius:8px;overflow:hidden;">';
                echo '<img src="'.$photo_url.'" style="width:100%;height:100px;object-fit:cover;">';
                echo '<label style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.7);color:#fff;padding:4px;text-align:center;font-size:10px;cursor:pointer;">';
                echo '<input type="checkbox" name="delete_photos[]" value="'.$photo->rowid.'"> Supprimer</label>';
                echo '</div>';
            }
            echo '</div>';
        }

        if ($remaining_slots > 0) {
            echo '<div class="mv3-photo-simple" onclick="document.getElementById(\'photo_input\').click()">';
            echo '<div style="font-size:48px;margin-bottom:8px;">📷</div>';
            echo '<div style="font-size:16px;font-weight:600;color:#475569;">Prendre une photo</div>';
            echo '<div style="font-size:13px;color:#94a3b8;margin-top:4px;">Jusqu\'à '.$remaining_slots.' photos</div>';
            echo '</div>';
            echo '<input type="file" id="photo_input" name="photos[]" accept="image/*" capture="environment" multiple style="display:none;" onchange="previewPhotos(event)">';
            echo '<div id="photos_preview" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;margin-top:12px;"></div>';
        }
        ?>
    </div>

    <!-- BOUTONS -->
    <div class="mv3-btn-group">
        <a href="<?php echo $urllist; ?>" class="mv3-btn mv3-btn-secondary">❌ Annuler</a>
        <button type="submit" class="mv3-btn mv3-btn-primary">💾 Enregistrer</button>
    </div>
</form>

<script>
let photoCount = 0;
const maxPhotos = <?php echo $remaining_slots; ?>;
let clientsData = {};

function calculateTime() {
    const debut = document.getElementById('heures_debut').value;
    const fin = document.getElementById('heures_fin').value;
    if (debut && fin) {
        const d = new Date('2000-01-01 ' + debut);
        const f = new Date('2000-01-01 ' + fin);
        if (f > d) {
            const diff = (f - d) / 1000 / 3600;
            document.getElementById('temps_display').textContent = diff.toFixed(2) + ' h';
        }
    }
}

function setTime(field, value) {
    document.getElementById(field).value = value;
    calculateTime();
}

function setZone(zone) {
    document.querySelector('[name="zone_travail"]').value = zone;
}

// Sélection multiple des pièces
let selectedZones = [];

function updateZoneLabel() {
    const typeLieu = document.getElementById('type_lieu').value;
    const numeroField = document.getElementById('numero_field');
    const numeroLabel = document.getElementById('numero_label');
    const numeroLieu = document.getElementById('numero_lieu');

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

function toggleZone(zone) {
    const button = document.querySelector('[data-zone="' + zone + '"]');

    const index = selectedZones.indexOf(zone);
    if (index > -1) {
        selectedZones.splice(index, 1);
        button.style.background = '';
        button.style.color = '';
        button.style.borderColor = '';
    } else {
        selectedZones.push(zone);
        button.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        button.style.color = 'white';
        button.style.borderColor = '#10b981';
    }

    updateZoneDisplay();
}

function updateZoneDisplay() {
    const typeLieu = document.getElementById('type_lieu').value;
    const numeroLieu = document.getElementById('numero_lieu').value;
    const zoneAutre = document.getElementById('zone_autre').value;
    const preview = document.getElementById('zone_preview');
    const hidden = document.getElementById('zone_travail_input');

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

function setJournee(debut, fin) {
    document.getElementById('heures_debut').value = debut;
    document.getElementById('heures_fin').value = fin;
    calculateTime();
}

// Initialiser les zones sélectionnées au chargement
document.addEventListener('DOMContentLoaded', function() {
    const initialValue = document.getElementById('selected_zones').value;
    if (initialValue) {
        const zones = initialValue.split(',').map(z => z.trim());
        zones.forEach(function(zone) {
            selectedZones.push(zone);
            const button = document.querySelector('[data-zone="' + zone + '"]');
            if (button) {
                button.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                button.style.color = 'white';
                button.style.borderColor = '#10b981';
            }
        });
    }

    // Initialiser le champ type de lieu
    const typeLieu = document.getElementById('type_lieu').value;
    if (typeLieu) {
        updateZoneLabel();
    }
});

function addSurface(val) {
    const input = document.getElementById('surface_carrelee');
    input.value = (parseFloat(input.value) || 0) + val;
}

function setSurface(val) {
    document.getElementById('surface_carrelee').value = val;
}

function setFormat(format) {
    document.getElementById('format_carreaux').value = format;
}

function loadProjectClients(projetId) {
    const clientRow = document.getElementById('client_row');
    const clientSelect = document.getElementById('fk_soc');

    if (!projetId) {
        clientRow.style.display = 'none';
        clientSelect.innerHTML = '<option value="">-- Sélectionner --</option>';
        clientsData = {};
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('GET', '<?php echo DOL_URL_ROOT; ?>/custom/mv3pro_portail/rapports/ajax_client.php?projet_id=' + projetId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                console.log('AJAX Response:', data);

                if (data.success && data.clients && data.clients.length > 0) {
                    clientsData = {};
                    clientSelect.innerHTML = '<option value="">-- Sélectionner --</option>';

                    data.clients.forEach(function(client) {
                        clientsData[client.rowid] = client;
                        const option = document.createElement('option');
                        option.value = client.rowid;
                        option.textContent = client.nom;
                        clientSelect.appendChild(option);
                    });

                    clientRow.style.display = '';

                    const savedClientId = clientSelect.getAttribute('data-saved-value');
                    if (savedClientId && clientsData[savedClientId]) {
                        clientSelect.value = savedClientId;
                    } else if (data.clients.length === 1) {
                        clientSelect.value = data.clients[0].rowid;
                    }
                } else {
                    // AUCUN CLIENT TROUVÉ - Afficher erreur
                    clientsData = {};
                    clientSelect.innerHTML = '<option value="">⚠️ ' + (data.error || 'Aucun client avec devis accepté') + '</option>';
                    clientRow.style.display = '';

                    // Afficher une alerte visible
                    alert('⚠️ ATTENTION\n\n' + (data.error || 'Aucun client trouvé pour ce projet') + '\n\nVérifiez que :\n1. Le projet a des DEVIS\n2. Les devis sont ACCEPTÉS (signés)\n3. Les devis sont liés au projet');
                }
            } catch(e) {
                console.error('Erreur parsing JSON', e, xhr.responseText);
                alert('❌ Erreur lors du chargement des clients');
            }
        }
    };
    xhr.send();
}

function previewPhotos(event) {
    const files = Array.from(event.target.files);
    const container = document.getElementById('photos_preview');

    files.forEach((file, index) => {
        if (photoCount >= maxPhotos) {
            alert('Maximum ' + maxPhotos + ' photos');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.style.cssText = 'border:2px solid #3b82f6;border-radius:8px;overflow:hidden;';
            div.innerHTML = `
                <img src="${e.target.result}" style="width:100%;height:100px;object-fit:cover;">
                <select name="photo_categorie_${photoCount}" style="width:100%;padding:4px;font-size:11px;" required>
                    <option value="pendant">🟡 Pendant</option>
                    <option value="avant">🔵 Avant</option>
                    <option value="apres">🟢 Après</option>
                </select>
            `;
            container.appendChild(div);
            photoCount++;
        };
        reader.readAsDataURL(file);
    });
}

window.addEventListener('DOMContentLoaded', function() {
    const projetSelect = document.getElementById('fk_projet');
    if (projetSelect.value) {
        loadProjectClients(projetSelect.value);
    }
    calculateTime();
});
</script>

<?php
llxFooter();
?>
