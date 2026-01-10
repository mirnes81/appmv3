<?php
/**
 * Planning mobile - MV3 PRO
 */



require_once __DIR__ . '/../includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/html_helpers.php';
require_once __DIR__ . '/../includes/db_helpers.php';

loadDolibarr();
requireMobileSession('../login_mobile.php');

global $db, $user;

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$user_id = $user->id;
$week_offset = GETPOST('week', 'int') ?: 0;

// Utiliser dol_now() pour avoir la date correcte du serveur
$now = dol_now();
// Calculer le lundi de la semaine courante
$day_of_week = (int)dol_print_date($now, '%w'); // 0=dimanche, 1=lundi, ...
if ($day_of_week == 0) $day_of_week = 7; // Dimanche = 7
$days_since_monday = $day_of_week - 1;
$current_monday = $now - ($days_since_monday * 24 * 3600);
$current_monday = dol_mktime(0, 0, 0, dol_print_date($current_monday, '%m'), dol_print_date($current_monday, '%d'), dol_print_date($current_monday, '%Y'));
// IMPORTANT: Appliquer l'offset AVANT de calculer le numéro de semaine
$current_monday = $current_monday + ($week_offset * 7 * 24 * 3600);

// Calculer le numéro de semaine APRÈS l'offset (utiliser date() natif pour fiabilité)
$week_number = (int)date('W', $current_monday);
if ($week_number == 0) {
    // Si la semaine 0, c'est en fait la dernière semaine de l'année précédente
    $week_number = 52;
}

// Créer les 7 jours de la semaine
$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = $current_monday + ($i * 24 * 3600);
}

// Si on est sur la semaine courante (week_offset == 0), réorganiser pour mettre aujourd'hui en premier
if ($week_offset == 0) {
    $today_timestamp = dol_mktime(0, 0, 0, dol_print_date($now, '%m'), dol_print_date($now, '%d'), dol_print_date($now, '%Y'));
    $reordered_days = [];
    $other_days = [];

    foreach ($days as $day) {
        if ($day == $today_timestamp) {
            array_unshift($reordered_days, $day); // Aujourd'hui en premier
        } else {
            $other_days[] = $day;
        }
    }

    $days = array_merge($reordered_days, $other_days);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Planning S<?php echo $week_number; ?> - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css?v=<?php echo time(); ?>">
    <style>
        .week-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(59,130,246,0.15);
        }
        .week-nav-btn {
            width: 40px;
            height: 40px;
            background: white;
            color: var(--primary);
            border: none;
            border-radius: 50%;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .week-nav-btn:active {
            transform: scale(0.95);
        }
        .week-title-container {
            text-align: center;
            flex: 1;
        }
        .week-number {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .week-title {
            font-weight: 700;
            font-size: 13px;
            color: #1e293b;
        }
        .day-card {
            background: var(--card);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .day-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border);
        }
        .day-header-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }
        .day-header-week {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 10px;
        }
        .event-item {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid #10b981;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: 0.2s;
            position: relative;
            overflow: hidden;
        }
        .event-item:active {
            transform: scale(0.98);
            opacity: 0.8;
        }
        .event-title {
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 4px;
        }
        .event-meta {
            font-size: 10px;
            color: #475569;
        }
        .event-project-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 4px 12px 4px 16px;
            border-bottom-left-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(59,130,246,0.3);
        }

        /* Modal mobile */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            animation: fadeIn 0.2s;
        }
        .modal-overlay.active {
            display: flex;
            align-items: flex-end;
        }
        .modal-sheet {
            background: white;
            width: 100%;
            max-height: 85vh;
            border-radius: 20px 20px 0 0;
            overflow-y: auto;
            animation: slideUp 0.3s;
        }
        .modal-header {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            padding: 20px;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            flex: 1;
            padding-right: 10px;
            line-height: 1.3;
        }
        .modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 24px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .modal-section:last-child {
            border-bottom: none;
        }
        .modal-label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .modal-value {
            font-size: 15px;
            color: #1e293b;
            font-weight: 600;
            line-height: 1.5;
        }
        .modal-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            background: #dbeafe;
            color: #1e40af;
            margin-top: 4px;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="app-header">
        <div>
            <div class="app-header-title">📅 Mon Planning</div>
            <div class="app-header-subtitle">Mes affectations de la semaine</div>
        </div>
    </div>

    <div class="app-container">
        <div class="week-nav">
            <button class="week-nav-btn" onclick="window.location.href='?week=<?php echo $week_offset - 1; ?>&t=<?php echo time(); ?>'">◄</button>
            <div class="week-title-container">
                <div class="week-number">📅 SEMAINE <?php echo $week_number; ?></div>
                <div class="week-title">
                    <?php echo dol_print_date($days[0], '%d/%m').' - '.dol_print_date($days[6], '%d/%m/%Y'); ?>
                </div>
            </div>
            <button class="week-nav-btn" onclick="window.location.href='?week=<?php echo $week_offset + 1; ?>&t=<?php echo time(); ?>'">►</button>
        </div>

        <?php
        $day_names = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

        foreach ($days as $i => $day) {
            $day_date = date('Y-m-d', $day);
            $is_today = ($day_date === date('Y-m-d'));

            // Calculer le vrai jour de la semaine (0=lundi, 6=dimanche)
            $day_of_week = (int)date('N', $day) - 1; // N = 1 (lundi) à 7 (dimanche)
            $day_name = $day_names[$day_of_week];

            // Calculer le numéro de semaine pour ce jour (utiliser date() natif)
            $day_week_number = (int)date('W', $day);
            if ($day_week_number == 0) $day_week_number = 52;

            echo '<div class="day-card">';
            echo '<div class="day-header">';
            echo '<div class="day-header-title">';
            echo $day_name.' '.dol_print_date($day, '%d/%m');
            if ($is_today) echo ' <span class="card-badge badge-info">Aujourd\'hui</span>';
            echo '</div>';
            echo '<div class="day-header-week">S'.$day_week_number.'</div>';
            echo '</div>';

            $sql = "SELECT DISTINCT a.id, a.label, a.datep, a.datep2, a.fulldayevent, a.location, a.note as note_private,
                           s.nom as client_nom, p.ref as projet_ref, p.title as projet_title, ac.code as type_code,
                           (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."ecm_files ef
                            WHERE ef.src_object_type = 'actioncomm' AND ef.src_object_id = a.id) as nb_files
                    FROM ".MAIN_DB_PREFIX."actioncomm a
                    LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ac ON ac.id = a.fk_action
                    LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = a.fk_soc
                    LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = a.fk_project
                    LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
                    WHERE (a.fk_user_author = ".(int)$user_id."
                           OR a.fk_user_action = ".(int)$user_id."
                           OR a.fk_user_done = ".(int)$user_id."
                           OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$user_id."))
                    AND a.entity IN (".getEntity('actioncomm').")
                    AND ac.code IN ('AC_POS', 'AC_plan')
                    AND (
                        (a.datep2 IS NOT NULL AND DATE(a.datep) <= '".$db->escape($day_date)."' AND DATE(a.datep2) >= '".$db->escape($day_date)."')
                        OR (DATE(a.datep) = '".$db->escape($day_date)."')
                    )
                    ORDER BY a.datep";
            $resql = $db->query($sql);

            if ($resql && $db->num_rows($resql) > 0) {
                while ($event = $db->fetch_object($resql)) {
                    // Extraire le numéro SAV
                    $sav_number = '';
                    if ($event->note_private && preg_match('/SAV[:\s]*([A-Z0-9\-]+)/i', $event->note_private, $matches)) {
                        $sav_number = $matches[1];
                    }

                    echo '<div class="event-item" onclick="openModal('.$event->id.')">';

                    // Badge projet en coin
                    if ($event->projet_ref) {
                        echo '<div class="event-project-badge">🏗️ '.dol_escape_htmltag($event->projet_ref).'</div>';
                    }

                    // Titre
                    echo '<div class="event-title" style="'.($event->projet_ref ? 'padding-right:70px;' : '').'">'.dol_escape_htmltag($event->label).'</div>';

                    // Badges en ligne
                    echo '<div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;flex-wrap:wrap">';

                    // Badge SAV
                    if ($sav_number) {
                        echo '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:700">📋 '.$sav_number.'</span>';
                    }

                    // Badge documents/images
                    if ($event->nb_files > 0) {
                        echo '<span style="background:#10b981;color:white;padding:2px 8px;border-radius:10px;font-size:9px;font-weight:700;display:flex;align-items:center;gap:4px">';
                        echo '📎 '.$event->nb_files;
                        echo '</span>';
                    }

                    echo '</div>';

                    echo '<div class="event-meta">';
                    if ($event->client_nom) echo '🏢 '.dol_escape_htmltag($event->client_nom);
                    if (!$event->fulldayevent) {
                        if ($event->client_nom) echo ' • ';
                        echo '⏰ '.dol_print_date($db->jdate($event->datep), '%H:%M');
                    } else {
                        if ($event->client_nom) echo ' • ';
                        echo '📅 Toute la journée';
                    }
                    if ($event->location) echo ' • 📍 '.dol_escape_htmltag($event->location);
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div style="text-align:center;padding:20px;color:var(--text-light);font-size:14px">';
                echo 'Aucune affectation';
                echo '</div>';
            }

            echo '</div>';
        }
        ?>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <!-- Modal détails -->
    <div class="modal-overlay" id="eventModal" role="dialog" aria-modal="true" tabindex="-1" ontouchstart="if(event.target === this) closeModal()" onclick="if(event.target === this) closeModal()" onkeydown="if(event.key === 'Escape') closeModal()">
        <div class="modal-sheet">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle"></div>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script src="../js/app.js"></script>
    <script>
    function openModal(eventId) {
        console.log('Opening modal for event ID:', eventId);

        // URL relative vers l'API dans le dossier parent
        const url = '../../planning/get_event.php?id=' + eventId;
        console.log('Fetching URL:', url);

        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log('Response data:', data);

                if (data.error) {
                    console.error('Error from API:', data);
                    alert('Erreur: ' + data.error + '\nEvent ID: ' + eventId);
                    return;
                }

                document.getElementById('modalTitle').innerHTML = data.label;

                let html = '';

                if (data.type_label) {
                    html += '<div class="modal-section">';
                    html += '<div class="modal-label">Type</div>';
                    html += '<div class="modal-value"><span class="modal-badge">' + data.type_label + '</span></div>';
                    html += '</div>';
                }

                if (data.client_nom) {
                    html += '<div class="modal-section">';
                    html += '<div class="modal-label">🏢 Client</div>';
                    html += '<div class="modal-value">' + data.client_nom + '</div>';
                    html += '</div>';
                }

                if (data.projet_ref || data.projet_title) {
                    html += '<div class="modal-section">';
                    html += '<div class="modal-label">🏗️ Projet / Chantier</div>';
                    html += '<div class="modal-value">';
                    if (data.projet_ref) html += data.projet_ref + ' - ';
                    html += data.projet_title || '';
                    html += '</div>';
                    html += '</div>';
                }

                html += '<div class="modal-section">';
                html += '<div class="modal-label">📅 Date et heure</div>';
                html += '<div class="modal-value">';
                if (data.date_start) {
                    html += '🕐 ' + data.date_start;
                    if (data.date_end && data.date_end !== data.date_start) {
                        html += '<br>🕐 Fin: ' + data.date_end;
                    }
                }
                if (data.fulldayevent) {
                    html += '<br><span class="modal-badge" style="background:#fef3c7;color:#92400e">Journée entière</span>';
                }
                html += '</div>';
                html += '</div>';

                if (data.location) {
                    html += '<div class="modal-section">';
                    html += '<div class="modal-label">📍 Localisation</div>';
                    html += '<div class="modal-value">' + data.location + '</div>';
                    html += '</div>';
                }

                if (data.note) {
                    html += '<div class="modal-section">';
                    html += '<div class="modal-label">📝 Description</div>';
                    html += '<div class="modal-value" style="white-space:pre-line">' + data.note + '</div>';
                    html += '</div>';
                }

                if (data.users && data.users.length > 0) {
                    html += '<div class="modal-section">';
                    html += '<div class="modal-label">👷 Ouvriers affectés</div>';
                    html += '<div class="modal-value">';
                    data.users.forEach(function(user) {
                        html += '• ' + user + '<br>';
                    });
                    html += '</div>';
                    html += '</div>';
                }

                // Fichiers joints avec aperçu
                if (data.files && data.files.length > 0) {
                    html += '<div class="modal-section">';
                    html += '<div class="modal-label">📎 Fichiers joints (' + data.files.length + ')</div>';
                    html += '<div class="modal-value">';

                    data.files.forEach(function(file) {
                        html += '<div style="margin-bottom:16px;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">';

                        // Aperçu si image ou PDF
                        if (file.preview_url) {
                            if (file.is_image) {
                                html += '<div style="margin-bottom:12px;text-align:center;background:#fff;padding:8px;border-radius:6px">';
                                html += '<img src="' + file.preview_url + '" alt="' + file.filename + '" ';
                                html += 'style="max-width:100%;max-height:300px;border-radius:4px;cursor:pointer" ';
                                html += 'onclick="window.open(\'' + file.download_url + '\', \'_blank\')" />';
                                html += '</div>';
                            } else if (file.is_pdf) {
                                html += '<div style="margin-bottom:12px;text-align:center;background:#fff;padding:8px;border-radius:6px">';
                                html += '<iframe src="' + file.preview_url + '" style="width:100%;height:300px;border:1px solid #e2e8f0;border-radius:4px"></iframe>';
                                html += '</div>';
                            }
                        }

                        // Infos fichier
                        html += '<div style="display:flex;flex-direction:column;gap:8px">';
                        html += '<div style="font-weight:600;color:#1e293b;font-size:14px">';
                        html += file.is_image ? '🖼️ ' : (file.is_pdf ? '📄 ' : '📎 ');
                        html += file.filename;
                        html += '</div>';
                        if (file.date) {
                            html += '<div style="font-size:12px;color:#64748b">Ajouté le ' + file.date + '</div>';
                        }
                        html += '<a href="' + file.download_url + '" target="_blank" style="display:inline-block;padding:10px 16px;background:#10b981;color:white;text-decoration:none;border-radius:6px;font-size:13px;font-weight:600;text-align:center">⬇️ Télécharger</a>';
                        html += '</div>';

                        html += '</div>';
                    });

                    html += '</div>';
                    html += '</div>';
                }

                document.getElementById('modalBody').innerHTML = html;
                document.getElementById('eventModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du chargement des détails');
            });
    }

    function closeModal() {
        document.getElementById('eventModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // Rafraîchissement automatique toutes les 3 minutes
    let autoRefreshTimer = setInterval(() => {
        if (!document.hidden) {
            console.log('[Planning] Auto-refresh des données...');
            window.location.reload();
        }
    }, 3 * 60 * 1000); // 3 minutes

    // Pause auto-refresh quand la page n'est pas visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            console.log('[Planning] Page cachée - pause auto-refresh');
            if (autoRefreshTimer) {
                clearInterval(autoRefreshTimer);
                autoRefreshTimer = null;
            }
        } else {
            console.log('[Planning] Page visible - reprise auto-refresh');
            autoRefreshTimer = setInterval(() => {
                if (!document.hidden) {
                    window.location.reload();
                }
            }, 3 * 60 * 1000);
            // Rafraîchir immédiatement quand l'utilisateur revient
            setTimeout(() => window.location.reload(), 500);
        }
    });
    </script>
</body>
</html>
<?php $db->close(); ?>
