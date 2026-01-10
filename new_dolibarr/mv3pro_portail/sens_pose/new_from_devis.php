<?php
/**
 * Création fiche sens de pose depuis un devis
 * Avec système en cascade : Projet → Clients → Devis → Données
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$action = GETPOST('action', 'alpha');

if ($action == 'create_from_devis') {
    header('Content-Type: application/json; charset=utf-8');
    ob_start();
    $fk_projet = GETPOST('fk_projet', 'int');
    $fk_client = GETPOST('fk_client', 'int');
    $fk_devis = GETPOST('fk_devis', 'int');
    $devis_data_json = GETPOST('devis_data', 'restricthtml');

    if (empty($fk_client)) {
        ob_clean();
        echo json_encode(['error' => 'Client requis']);
        ob_end_flush();
        exit;
    }

    $devis_data = json_decode($devis_data_json, true);

    $client = new Societe($db);
    $client->fetch($fk_client);

    $projet_name = '';
    if ($fk_projet > 0) {
        $projet = new Project($db);
        $projet->fetch($fk_projet);
        $projet_name = $projet->ref . ' - ' . $projet->title;
    }

    $db->begin();

    $year = date('Y');
    $sql_count = "SELECT MAX(CAST(SUBSTRING_INDEX(ref, '-', -1) AS UNSIGNED)) as max_num
                  FROM ".MAIN_DB_PREFIX."mv3_sens_pose
                  WHERE ref LIKE 'POSE-".$year."-%'";
    $resql_count = $db->query($sql_count);
    $next_num = 1;
    if ($resql_count) {
        $obj_count = $db->fetch_object($resql_count);
        if ($obj_count && $obj_count->max_num) {
            $next_num = $obj_count->max_num + 1;
        }
    }
    $new_ref = 'POSE-'.$year.'-'.str_pad($next_num, 4, '0', STR_PAD_LEFT);

    $client_name = $client->name;
    $internal_ref = $devis_data['devis']['ref'] ?? '';
    $site_address = $client->address . ', ' . $client->zip . ' ' . $client->town;
    $notes = 'Créé depuis devis ' . $internal_ref . ($projet_name ? ' - Projet: ' . $projet_name : '');

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose
            (entity, ref, client_name, internal_ref, site_address, notes, fk_projet, fk_client, fk_user_create, statut)
            VALUES (".$conf->entity.", '".$new_ref."', '".$db->escape($client_name)."', '".$db->escape($internal_ref)."',
            '".$db->escape($site_address)."', '".$db->escape($notes)."',
            ".($fk_projet > 0 ? (int)$fk_projet : "NULL").", ".(int)$fk_client.",
            ".(int)$user->id.", 'brouillon')";

    if ($db->query($sql)) {
        $new_id = $db->last_insert_id(MAIN_DB_PREFIX."mv3_sens_pose");

        $debug_log = [];
        $all_detected_pieces = [];
        $debug_log[] = "=== DÉBUT CRÉATION PIÈCES PAR SECTION ===";
        $debug_log[] = "Nombre de lignes reçues: " . (isset($devis_data['all_lines']) ? count($devis_data['all_lines']) : 0);

        if (isset($devis_data['all_lines']) && is_array($devis_data['all_lines'])) {
            $ordre = 1;

            foreach ($devis_data['all_lines'] as $idx => $line) {
                $debug_log[] = "--- Ligne $idx (type: " . ($line['type'] ?? 'unknown') . ") ---";

                if ($line['type'] == 'section') {
                    // C'est une SECTION avec un titre
                    $nom_piece = $line['title'];
                    $debug_log[] = "Section détectée: '$nom_piece'";
                    $debug_log[] = "Nombre de produits dans cette section: " . count($line['products']);

                    // Construire le texte de la pièce
                    $texte_complet = '';

                    // Ajouter les sous-titres s'il y en a
                    if (!empty($line['subtitles'])) {
                        $texte_complet .= "Zones:\n" . implode("\n", $line['subtitles']) . "\n\n";
                    }

                    // Ajouter les textes descriptifs
                    if (!empty($line['texts'])) {
                        $texte_complet .= implode("\n", $line['texts']) . "\n\n";
                    }

                    // Ajouter les informations des produits
                    foreach ($line['products'] as $prod) {
                        if (!empty($prod['product_label']) || !empty($prod['label'])) {
                            $texte_complet .= "• " . ($prod['product_label'] ?: $prod['label']) . "\n";
                        }
                        if (!empty($prod['reference'])) {
                            $texte_complet .= "  Réf: " . $prod['reference'] . "\n";
                        }
                        if (!empty($prod['format'])) {
                            $texte_complet .= "  Format: " . $prod['format'] . "\n";
                        }
                        if (!empty($prod['qty'])) {
                            $texte_complet .= "  Quantité: " . $prod['qty'] . " " . ($prod['unite'] ?? '') . "\n";
                        }
                        $texte_complet .= "\n";
                    }

                    $texte_complet = trim($texte_complet);

                    // Récupérer la photo du premier produit
                    $photo_url = '';
                    $photo_filename = '';
                    $fk_product = null;
                    $format = '';
                    $reference = '';

                    if (!empty($line['products'])) {
                        $first_prod = $line['products'][0];
                        $photo_url = $first_prod['photo_url'] ?? '';
                        $photo_filename = $first_prod['photo_filename'] ?? '';
                        $fk_product = !empty($first_prod['fk_product']) ? (int)$first_prod['fk_product'] : null;
                        $format = $first_prod['format'] ?? '';
                        $reference = $first_prod['reference'] ?? '';
                    }

                    $debug_log[] = "Création pièce '$nom_piece' avec " . count($line['products']) . " produit(s)";

                    // Créer la pièce
                    $sql_piece = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose_pieces
                                (fk_sens_pose, nom, format, photo_url, photo_filename, photo_reference, fk_product,
                                 qty, unite, piece_text, product_ref, product_label, ordre)
                                VALUES (".(int)$new_id.", '".$db->escape($nom_piece)."', '".$db->escape($format)."',
                                '".$db->escape($photo_url)."', '".$db->escape($photo_filename)."',
                                '".$db->escape($internal_ref)."',
                                ".($fk_product ? (int)$fk_product : "NULL").",
                                0, '', '".$db->escape($texte_complet)."', '".$db->escape($reference)."',
                                '".$db->escape($nom_piece)."', ".(int)$ordre.")";

                    $result_piece = $db->query($sql_piece);
                    if ($result_piece) {
                        $debug_log[] = "✓ Pièce '$nom_piece' créée (ordre: $ordre)";
                        $all_detected_pieces[] = $nom_piece;
                        $ordre++;
                    } else {
                        $debug_log[] = "✗ ERREUR: " . $db->lasterror();
                    }

                } elseif ($line['type'] == 'product') {
                    // Produit standalone (sans section)
                    $nom_piece = $line['product_label'] ?: $line['label'] ?: 'Produit ' . ($idx + 1);
                    $debug_log[] = "Produit standalone: '$nom_piece'";

                    $texte_complet = '';
                    if (!empty($line['reference'])) {
                        $texte_complet .= "Référence: " . $line['reference'] . "\n";
                    }
                    if (!empty($line['format'])) {
                        $texte_complet .= "Format: " . $line['format'] . "\n";
                    }
                    if (!empty($line['qty'])) {
                        $texte_complet .= "Quantité: " . $line['qty'] . " " . ($line['unite'] ?? '') . "\n";
                    }

                    $texte_complet = trim($texte_complet);

                    $sql_piece = "INSERT INTO ".MAIN_DB_PREFIX."mv3_sens_pose_pieces
                                (fk_sens_pose, nom, format, photo_url, photo_filename, photo_reference, fk_product,
                                 qty, unite, piece_text, product_ref, product_label, ordre)
                                VALUES (".(int)$new_id.", '".$db->escape($nom_piece)."',
                                '".$db->escape($line['format'] ?? '')."',
                                '".$db->escape($line['photo_url'] ?? '')."',
                                '".$db->escape($line['photo_filename'] ?? '')."',
                                '".$db->escape($internal_ref)."',
                                ".(!empty($line['fk_product']) ? (int)$line['fk_product'] : "NULL").",
                                ".(float)($line['qty'] ?? 0).",
                                '".$db->escape($line['unite'] ?? '')."',
                                '".$db->escape($texte_complet)."',
                                '".$db->escape($line['reference'] ?? '')."',
                                '".$db->escape($line['product_label'] ?? '')."',
                                ".(int)$ordre.")";

                    $result_piece = $db->query($sql_piece);
                    if ($result_piece) {
                        $debug_log[] = "✓ Produit '$nom_piece' créé (ordre: $ordre)";
                        $all_detected_pieces[] = $nom_piece;
                        $ordre++;
                    } else {
                        $debug_log[] = "✗ ERREUR: " . $db->lasterror();
                    }
                }
            }

            $debug_log[] = "=== RÉSULTAT FINAL ===";
            $debug_log[] = "Nombre de pièces créées: " . count($all_detected_pieces);
        }

        file_put_contents('/tmp/sens_pose_debug.log', implode("\n", $debug_log) . "\n\n", FILE_APPEND);

        $db->commit();

        ob_clean();
        $debug_log[] = "Pièces finales créées: " . implode(', ', $all_detected_pieces);

        echo json_encode([
            'success' => true,
            'id' => $new_id,
            'redirect' => 'edit_pieces.php?id='.$new_id.'&detected_pieces='.urlencode(implode(',', $all_detected_pieces)),
            'nb_pieces_created' => count($all_detected_pieces),
            'pieces_names' => $all_detected_pieces,
            'detected_pieces' => $all_detected_pieces,
            'debug_log' => $debug_log
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ob_end_flush();
        exit;
    } else {
        $db->rollback();

        ob_clean();
        echo json_encode(['error' => 'Erreur lors de la création : '.$db->lasterror()]);
        ob_end_flush();
        exit;
    }
}

llxHeader('', 'Nouvelle fiche depuis devis');
?>

<style>
:root {
    --primary: #0891b2;
    --primary-dark: #0e7490;
}

.wizard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.wizard-steps {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.wizard-step {
    flex: 1;
    padding: 20px;
    background: #f1f5f9;
    border-radius: 10px;
    border: 3px solid #e2e8f0;
    position: relative;
    transition: all 0.3s;
}

.wizard-step.active {
    background: white;
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2);
}

.wizard-step.completed {
    background: #ecfdf5;
    border-color: #10b981;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #cbd5e1;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 10px;
}

.wizard-step.active .step-number {
    background: var(--primary);
}

.wizard-step.completed .step-number {
    background: #10b981;
}

.step-title {
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 5px;
}

.step-description {
    font-size: 13px;
    color: #64748b;
}

.search-box {
    position: relative;
    margin-bottom: 20px;
}

.search-input {
    width: 100%;
    padding: 15px 20px;
    font-size: 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
}

.autocomplete-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 10px 10px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 100;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.autocomplete-item {
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s;
}

.autocomplete-item:hover {
    background: #f8fafc;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.list-container {
    display: none;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}

.list-container.active {
    display: grid;
}

.list-item {
    padding: 20px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.list-item:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.15);
    transform: translateY(-2px);
}

.list-item.selected {
    border-color: var(--primary);
    background: #f0f9ff;
}

.item-title {
    font-weight: 600;
    font-size: 16px;
    color: #0f172a;
    margin-bottom: 8px;
}

.item-meta {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 4px;
}

.item-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #e0f2fe;
    color: var(--primary);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 8px;
    margin-top: 8px;
}

.devis-details {
    display: none;
    margin-top: 30px;
    padding: 24px;
    background: white;
    border-radius: 12px;
    border: 2px solid #0891b2;
}

.devis-details.active {
    display: block;
}

.details-header {
    font-size: 20px;
    font-weight: bold;
    color: var(--primary);
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e2e8f0;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.detail-section {
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
}

.detail-section-title {
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 12px;
    font-size: 15px;
}

.ligne-item {
    padding: 12px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    margin-bottom: 8px;
}

.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
}

.photo-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #e2e8f0;
}

.photo-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
}

.btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

.actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
}
</style>

<div class="wizard-container">
    <div class="page-header">
        <h1 style="margin:0; font-size:28px;">🎯 Nouvelle fiche sens de pose depuis devis</h1>
        <p style="margin:8px 0 0 0; opacity:0.9; font-size:15px;">
            Sélectionnez un projet, puis un client, puis un devis pour importer automatiquement les données
        </p>
    </div>

    <div class="wizard-steps">
        <div class="wizard-step active" id="step1">
            <div class="step-number">1</div>
            <div class="step-title">🔍 Rechercher un projet</div>
            <div class="step-description">Tapez quelques lettres pour rechercher</div>
        </div>
        <div class="wizard-step" id="step2">
            <div class="step-number">2</div>
            <div class="step-title">👥 Choisir un client</div>
            <div class="step-description">Clients liés au projet</div>
        </div>
        <div class="wizard-step" id="step3">
            <div class="step-number">3</div>
            <div class="step-title">📄 Choisir un devis</div>
            <div class="step-description">Devis du client sélectionné</div>
        </div>
        <div class="wizard-step" id="step4">
            <div class="step-number">4</div>
            <div class="step-title">✅ Créer la fiche</div>
            <div class="step-description">Avec données et photos</div>
        </div>
    </div>

    <div id="step1-content">
        <div class="search-box">
            <input type="text"
                   id="projet-search"
                   class="search-input"
                   placeholder="🔍 Rechercher un projet par référence, titre ou client..."
                   autocomplete="off">
            <div id="projet-results" class="autocomplete-results" style="display:none;"></div>
        </div>

        <div class="empty-state">
            <div class="empty-state-icon">🏗️</div>
            <div style="font-size:18px; font-weight:600; margin-bottom:8px;">Commencez par rechercher un projet</div>
            <div style="font-size:14px;">Tapez au moins 2 caractères pour lancer la recherche</div>
        </div>
    </div>

    <div id="step2-content" style="display:none;">
        <div id="clients-list" class="list-container"></div>
    </div>

    <div id="step3-content" style="display:none;">
        <div id="devis-list" class="list-container"></div>
    </div>

    <div id="step4-content" class="devis-details">
        <div class="details-header">📋 Détails du devis sélectionné</div>
        <div id="devis-info"></div>
        <div class="actions">
            <button type="button" class="btn btn-secondary" onclick="goToStep(3)">← Retour aux devis</button>
            <button type="button" class="btn btn-primary" onclick="createFiche()">✅ Créer la fiche sens de pose</button>
        </div>
    </div>
</div>

<script>
let selectedProjet = null;
let selectedClient = null;
let selectedDevis = null;
let devisDetails = null;

let searchTimeout = null;
const projetSearch = document.getElementById('projet-search');
const projetResults = document.getElementById('projet-results');

projetSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const search = this.value.trim();

    if (search.length < 2) {
        projetResults.style.display = 'none';
        return;
    }

    searchTimeout = setTimeout(() => {
        fetch('api_search_projets.php?search=' + encodeURIComponent(search))
            .then(r => r.json())
            .then(data => {
                if (data.projets && data.projets.length > 0) {
                    projetResults.innerHTML = data.projets.map(p => `
                        <div class="autocomplete-item" onclick="selectProjet(${p.rowid}, '${p.ref}', '${escapeHtml(p.title)}', '${escapeHtml(p.label)}')">
                            <div style="font-weight:600;">${p.ref} - ${escapeHtml(p.title)}</div>
                            ${p.client_name ? '<div style="font-size:13px; color:#64748b; margin-top:4px;">Client: ' + escapeHtml(p.client_name) + '</div>' : ''}
                        </div>
                    `).join('');
                    projetResults.style.display = 'block';
                } else {
                    projetResults.innerHTML = '<div class="autocomplete-item">Aucun projet trouvé</div>';
                    projetResults.style.display = 'block';
                }
            });
    }, 300);
});

function selectProjet(rowid, ref, title, label) {
    selectedProjet = { rowid, ref, title, label };
    projetSearch.value = label;
    projetResults.style.display = 'none';

    document.getElementById('step1').classList.add('completed');
    document.getElementById('step1').classList.remove('active');
    document.getElementById('step2').classList.add('active');

    goToStep(2);
    loadClients(rowid);
}

function loadClients(projetId) {
    const container = document.getElementById('clients-list');
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🔄</div><div>Chargement des clients...</div></div>';
    container.classList.add('active');

    fetch('api_get_clients_projet.php?fk_projet=' + projetId)
        .then(r => r.json())
        .then(data => {
            if (data.clients && data.clients.length > 0) {
                container.innerHTML = data.clients.map(c => `
                    <div class="list-item" onclick="selectClient(${c.rowid}, '${escapeHtml(c.nom)}')">
                        <div class="item-title">${escapeHtml(c.nom)}</div>
                        ${c.code_client ? '<div class="item-meta">Code: ' + c.code_client + '</div>' : ''}
                        <div>
                            <span class="item-badge">📄 ${c.nb_devis} devis</span>
                            <span class="item-badge">📦 ${c.nb_commandes} commandes</span>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">👥</div><div>Aucun client trouvé pour ce projet</div></div>';
            }
        });
}

function selectClient(rowid, nom) {
    selectedClient = { rowid, nom };

    document.querySelectorAll('#clients-list .list-item').forEach(el => el.classList.remove('selected'));
    event.target.closest('.list-item').classList.add('selected');

    document.getElementById('step2').classList.add('completed');
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step3').classList.add('active');

    goToStep(3);
    loadDevis(rowid);
}

function loadDevis(clientId) {
    const container = document.getElementById('devis-list');
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">🔄</div><div>Chargement des devis...</div></div>';
    container.classList.add('active');

    fetch('api_get_devis_client.php?fk_client=' + clientId + '&fk_projet=' + selectedProjet.rowid)
        .then(r => r.json())
        .then(data => {
            if (data.devis && data.devis.length > 0) {
                container.innerHTML = data.devis.map(d => `
                    <div class="list-item" onclick="selectDevis(${d.rowid}, '${d.ref}', '${escapeHtml(d.ref_client || '')}')">
                        <div class="item-title">${d.ref}</div>
                        ${d.ref_client ? '<div class="item-meta">📝 Ref client: ' + escapeHtml(d.ref_client) + '</div>' : ''}
                        <div class="item-meta">📅 ${d.date}</div>
                        <div>
                            <span class="item-badge">📋 ${d.nb_lignes} lignes</span>
                            <span class="item-badge">📸 ${d.nb_photos} photos</span>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📄</div><div>Aucun devis trouvé</div></div>';
            }
        });
}

function selectDevis(rowid, ref, ref_client) {
    selectedDevis = { rowid, ref, ref_client };

    document.querySelectorAll('#devis-list .list-item').forEach(el => el.classList.remove('selected'));
    event.target.closest('.list-item').classList.add('selected');

    document.getElementById('step3').classList.add('completed');
    document.getElementById('step3').classList.remove('active');
    document.getElementById('step4').classList.add('active');

    loadDevisDetails(rowid);
}

function loadDevisDetails(devisId) {
    fetch('api_get_devis_details_v2.php?fk_devis=' + devisId)
        .then(r => r.json())
        .then(data => {
            devisDetails = data;
            displayDevisDetails(data);
            document.getElementById('step4-content').classList.add('active');
            goToStep(4);
        });
}

function displayDevisDetails(data) {
    const container = document.getElementById('devis-info');

    let html = '<div class="details-grid">';

    html += '<div class="detail-section">';
    html += '<div class="detail-section-title">📋 Structure du devis (' + data.nb_lignes + ')</div>';

    // Parcourir la nouvelle structure all_lines
    if (data.all_lines && data.all_lines.length > 0) {
        data.all_lines.forEach((line, idx) => {

            if (line.type === 'section') {
                // C'EST UNE SECTION
                const countText = line.products && line.products.length > 0
                    ? ` <span style="background:rgba(255,255,255,0.2); padding:4px 12px; border-radius:12px; font-size:13px;">${line.products.length} produit${line.products.length > 1 ? 's' : ''}</span>`
                    : '';

                // Afficher le header de la section
                html += `
                    <div style="margin-top:${idx > 0 ? '24px' : '12px'}; margin-bottom:12px; background:white; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                        <div style="padding:16px; background:linear-gradient(135deg, #0891b2 0%, #0e7490 100%);">
                            <div style="font-size:18px; font-weight:700; color:white; display:flex; align-items:center; gap:8px;">
                                <span style="font-size:22px;">📍</span>
                                ${escapeHtml(line.title)}
                                ${countText}
                            </div>
                `;

                // Sous-titres
                if (line.subtitles && line.subtitles.length > 0) {
                    html += '<div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">';
                    line.subtitles.forEach(st => {
                        html += `<span style="background:rgba(251, 191, 36, 0.3); color:#fef3c7; padding:6px 14px; border-radius:16px; font-size:13px; border:1px solid rgba(251, 191, 36, 0.5);">🔸 ${escapeHtml(st)}</span>`;
                    });
                    html += '</div>';
                }

                // Textes
                if (line.texts && line.texts.length > 0) {
                    html += `<div style="margin-top:12px; padding:16px; background:rgba(0,0,0,0.15); border-left:3px solid #fbbf24; border-radius:6px; color:#fef3c7; font-size:14px; line-height:1.6;">`;
                    html += '<strong style="color:#fde68a; display:block; margin-bottom:8px;">📋 Informations:</strong>';
                    html += escapeHtml(line.texts.join('\n')).replace(/\n/g, '<br>');
                    html += '</div>';
                }

                html += '</div>'; // Fin du header bleu

                // Produits de la section (EN DEHORS de la zone bleue)
                if (line.products && line.products.length > 0) {
                    html += '<div style="padding:16px; background:white;">';
                    line.products.forEach(prod => {
                        html += `
                            <div style="display:flex; gap:16px; padding:16px; background:#f8fafc; border-radius:8px; margin-bottom:12px; border:2px solid #e2e8f0;">
                        `;

                        // Photo
                        if (prod.photo_url) {
                            html += `
                                <div style="flex-shrink:0;">
                                    <img src="${prod.photo_url}"
                                         style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:2px solid #e2e8f0;"
                                         alt="${escapeHtml(prod.photo_filename || '')}"
                                         title="${escapeHtml(prod.photo_filename || '')}">
                                </div>
                            `;
                        } else {
                            html += `
                                <div style="flex-shrink:0; width:100px; height:100px; background:#e2e8f0; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:32px;">
                                    🖼️
                                </div>
                            `;
                        }

                        // Infos produit
                        html += '<div style="flex:1;">';
                        html += `<div style="font-size:16px; font-weight:600; color:#0f172a; margin-bottom:8px;">${escapeHtml(prod.product_label || prod.label)}</div>`;

                        if (prod.reference) {
                            html += `<span style="display:inline-block; background:#0891b2; color:white; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; margin-bottom:8px;">Réf: ${escapeHtml(prod.reference)}</span>`;
                        }

                        html += '<div style="display:flex; gap:16px; font-size:14px; color:#64748b; margin-top:8px;">';
                        if (prod.format) {
                            html += `<span>📐 ${escapeHtml(prod.format)}</span>`;
                        }
                        html += `<span>📦 ${prod.qty} ${escapeHtml(prod.unite || '')}</span>`;
                        html += '</div>';

                        html += '</div>'; // Fin infos produit
                        html += '</div>'; // Fin ligne produit
                    });
                    html += '</div>'; // Fin liste produits
                }

                html += '</div>'; // Fin section complète

            } else if (line.type === 'product') {
                // PRODUIT STANDALONE (sans section)
                html += `
                    <div style="background:white; border-radius:12px; padding:20px; margin-bottom:16px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                        <div style="display:flex; gap:16px;">
                `;

                // Photo
                if (line.photo_url) {
                    html += `
                        <div style="flex-shrink:0;">
                            <img src="${line.photo_url}"
                                 style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:2px solid #e2e8f0;"
                                 alt="${escapeHtml(line.photo_filename || '')}">
                        </div>
                    `;
                } else {
                    html += `
                        <div style="flex-shrink:0; width:100px; height:100px; background:#e2e8f0; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:32px;">
                            🖼️
                        </div>
                    `;
                }

                // Infos
                html += '<div style="flex:1;">';
                html += `<div style="font-size:16px; font-weight:600; color:#0f172a; margin-bottom:8px;">${escapeHtml(line.product_label || line.label)}</div>`;

                if (line.reference) {
                    html += `<span style="display:inline-block; background:#0891b2; color:white; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600; margin-bottom:8px;">Réf: ${escapeHtml(line.reference)}</span>`;
                }

                html += '<div style="display:flex; gap:16px; font-size:14px; color:#64748b; margin-top:8px;">';
                if (line.format) {
                    html += `<span>📐 ${escapeHtml(line.format)}</span>`;
                }
                html += `<span>📦 ${line.qty} ${escapeHtml(line.unite || '')}</span>`;
                html += '</div>';

                html += '</div>'; // Fin infos
                html += '</div>'; // Fin flex
                html += '</div>'; // Fin standalone
            }
        });
    } else {
        html += '<div style="text-align:center; padding:60px 20px; color:#94a3b8;"><div style="font-size:48px; margin-bottom:16px;">📭</div><div>Aucune ligne trouvée</div></div>';
    }

    html += '</div>'; // Fin detail-section

    html += '<div class="detail-section">';
    html += '<div class="detail-section-title">📊 Résumé</div>';
    html += `
        <div style="padding:12px; background:white; border-radius:6px; margin-bottom:8px;">
            <div style="font-size:14px; color:#64748b; margin-bottom:4px;">Référence devis</div>
            <div style="font-size:18px; font-weight:bold; color:#0891b2;">${escapeHtml(data.devis.ref)}</div>
            ${data.devis.ref_client ? '<div style="font-size:14px; color:#64748b; margin-top:8px;">Ref client: <strong>' + escapeHtml(data.devis.ref_client) + '</strong></div>' : ''}
        </div>
        <div style="padding:12px; background:white; border-radius:6px; margin-bottom:8px;">
            <div style="font-size:14px; color:#64748b; margin-bottom:4px;">Total lignes</div>
            <div style="font-size:20px; font-weight:bold; color:#0891b2;">${data.nb_lignes}</div>
        </div>
        <div style="padding:12px; background:white; border-radius:6px;">
            <div style="font-size:14px; color:#64748b; margin-bottom:4px;">Lignes avec photo</div>
            <div style="font-size:20px; font-weight:bold; color:#10b981;">${data.nb_photos}</div>
        </div>
    `;
    html += '</div>';

    html += '</div>';

    container.innerHTML = html;
}

function goToStep(step) {
    document.getElementById('step1-content').style.display = step === 1 ? 'block' : 'none';
    document.getElementById('step2-content').style.display = step === 2 ? 'block' : 'none';
    document.getElementById('step3-content').style.display = step === 3 ? 'block' : 'none';
    document.getElementById('step4-content').style.display = step === 4 ? 'block' : 'none';
}

function createFiche() {
    if (!confirm('Créer une fiche sens de pose avec les données de ce devis ?')) {
        return;
    }

    console.log('📊 Données du devis à envoyer:', devisDetails);
    console.log('📋 Nombre de lignes:', devisDetails.lignes?.length || 0);
    if (devisDetails.lignes) {
        devisDetails.lignes.forEach((ligne, idx) => {
            console.log(`Ligne ${idx}:`, {
                label: ligne.label,
                description: ligne.description,
                format: ligne.format,
                photo_url: ligne.photo_url
            });
        });
    }

    const formData = new FormData();
    formData.append('action', 'create_from_devis');
    formData.append('fk_projet', selectedProjet.rowid);
    formData.append('fk_client', selectedClient.rowid);
    formData.append('fk_devis', selectedDevis.rowid);
    formData.append('devis_data', JSON.stringify(devisDetails));
    formData.append('token', '<?php echo newToken(); ?>');

    fetch('new_from_devis.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        console.log('📝 Réponse du serveur:', data);

        // AFFICHER LE DEBUG COMPLET
        if (data.debug_log) {
            console.log('🔍 LOG DÉTAILLÉ:');
            data.debug_log.forEach(line => console.log(line));
        }

        if (data.success) {
            let message = '✅ Fiche créée avec succès !\n\n';
            message += '🔢 Référence: ' + data.id + '\n';
            if (data.nb_pieces_created > 0 && Array.isArray(data.pieces_names)) {
                message += '🎯 ' + data.nb_pieces_created + ' pièce(s) créée(s) automatiquement :\n';
                message += data.pieces_names.map(n => '  • ' + n).join('\n');
            } else {
                message += '⚠️ Aucune pièce créée automatiquement.\nVérifiez les descriptions du devis.';
            }

            // Afficher aussi l'alert avec le debug
            if (data.nb_pieces_created === 0 && data.debug_log) {
                message += '\n\n📋 Debug disponible dans la console (F12)';
            }

            alert(message);
            window.location.href = data.redirect || 'list.php';
        } else {
            alert('Erreur: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la création : ' + error);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-box')) {
        projetResults.style.display = 'none';
    }
});
</script>

<?php
llxFooter();
$db->close();
?>
