<?php
/**
 * Dashboard mobile - MV3 PRO Portail
 */

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";

if (!isset($_SESSION['dol_login']) || empty($user->id)) {
    header('Location: index.php');
    exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$user_id = $user->id;
$username = $user->login;

$today = date('Y-m-d');

// Calculer le numéro de semaine
$week_number = (int)date('W');
if ($week_number == 0) $week_number = 52;

$sql_user = "SELECT firstname, lastname FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".(int)$user_id;
$resql_user = $db->query($sql_user);
$user_data = null;
if ($resql_user) {
    $user_data = $db->fetch_object($resql_user);
}
if (!$user_data) {
    $user_data = (object)['firstname' => $user->firstname, 'lastname' => $user->lastname];
}

$rapports_today = 0;
$sql_rapports_today = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."mv3_rapport
                       WHERE fk_user = ".(int)$user_id." AND DATE(date_rapport) = '".$db->escape($today)."'";
$resql_rapports = $db->query($sql_rapports_today);
if ($resql_rapports) {
    $obj = $db->fetch_object($resql_rapports);
    if ($obj) $rapports_today = $obj->total;
}

$events_today = 0;
$sql_events_today = "SELECT COUNT(DISTINCT a.id) as total
                     FROM ".MAIN_DB_PREFIX."actioncomm a
                     LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ac ON ac.id = a.fk_action
                     LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
                     WHERE (a.fk_user_author = ".(int)$user_id."
                            OR a.fk_user_action = ".(int)$user_id."
                            OR a.fk_user_done = ".(int)$user_id."
                            OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$user_id."))
                     AND ac.code IN ('AC_POS', 'AC_plan')
                     AND DATE(a.datep) = '".$db->escape($today)."'";
$resql_events = $db->query($sql_events_today);
if ($resql_events) {
    $obj = $db->fetch_object($resql_events);
    if ($obj) $events_today = $obj->total;
}

$next_event = null;
$sql_next_event = "SELECT a.label, a.datep, a.location, s.nom as client_nom
                   FROM ".MAIN_DB_PREFIX."actioncomm a
                   LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ac ON ac.id = a.fk_action
                   LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = a.fk_soc
                   LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
                   WHERE (a.fk_user_author = ".(int)$user_id."
                          OR a.fk_user_action = ".(int)$user_id."
                          OR a.fk_user_done = ".(int)$user_id."
                          OR (ar.element_type = 'user' AND ar.fk_element = ".(int)$user_id."))
                   AND ac.code IN ('AC_POS', 'AC_plan')
                   AND a.datep >= NOW()
                   ORDER BY a.datep ASC LIMIT 1";
$resql_next = $db->query($sql_next_event);
if ($resql_next) {
    $next_event = $db->fetch_object($resql_next);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Accueil - MV3 PRO Mobile</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="css/mobile_app.css">
</head>
<body>
    <div class="pull-to-refresh">
        <div class="spinner"></div>
    </div>

    <div class="app-header">
        <div>
            <div class="app-header-title">👋 Bonjour <?php echo dol_escape_htmltag($user_data->firstname); ?></div>
            <div class="app-header-subtitle">
                <?php echo dol_print_date(dol_now(), '%A %d %B %Y'); ?> • 📅 S<?php echo $week_number; ?>
            </div>
        </div>
        <a href="profil/index.php" style="color:white;font-size:20px;text-decoration:none">⚙️</a>
    </div>

    <div class="app-container">
        <!-- Notifications récentes -->
        <?php
        $sql_notifs = "SELECT n.rowid, n.titre, n.message, n.type, n.date_creation, n.statut, n.fk_object, n.object_type
                       FROM ".MAIN_DB_PREFIX."mv3_notifications n
                       WHERE n.fk_user = ".(int)$user_id."
                       AND n.statut = 'non_lu'
                       ORDER BY n.date_creation DESC
                       LIMIT 3";
        $resql_notifs = $db->query($sql_notifs);
        $has_notifs = ($resql_notifs && $db->num_rows($resql_notifs) > 0);
        ?>

        <?php if ($has_notifs): ?>
        <div class="card" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border:2px solid #f59e0b">
            <div class="card-header" style="border-bottom:1px solid rgba(245,158,11,0.2);padding-bottom:12px;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="font-size:20px">🔔</div>
                    <div class="card-title" style="color:#92400e">Nouvelles notifications</div>
                </div>
                <a href="notifications/" style="font-size:11px;color:#92400e;text-decoration:none;font-weight:600">Voir toutes →</a>
            </div>
            <?php while ($notif = $db->fetch_object($resql_notifs)):
                $type_icon = '📢';
                $type_color = '#f59e0b';
                if ($notif->type == 'affectation') {
                    $type_icon = '📅';
                    $type_color = '#3b82f6';
                } elseif ($notif->type == 'materiel') {
                    $type_icon = '🛠️';
                    $type_color = '#10b981';
                } elseif ($notif->type == 'urgent') {
                    $type_icon = '⚠️';
                    $type_color = '#ef4444';
                }
            ?>
            <a href="notifications/mark_read.php?id=<?php echo $notif->rowid; ?>&back=dashboard" style="text-decoration:none;display:block;background:white;border-radius:12px;padding:12px;margin-bottom:8px;border-left:4px solid <?php echo $type_color; ?>;position:relative">
                <div style="display:flex;align-items:start;gap:8px">
                    <div style="font-size:20px;margin-top:2px"><?php echo $type_icon; ?></div>
                    <div style="flex:1">
                        <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:4px">
                            <?php echo dol_escape_htmltag($notif->titre); ?>
                        </div>
                        <div style="font-size:13px;color:#475569;line-height:1.5">
                            <?php echo dol_escape_htmltag($notif->message); ?>
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:6px">
                            <?php
                            $notif_time = $db->jdate($notif->date_creation);
                            $diff = time() - $notif_time;
                            if ($diff < 3600) {
                                echo 'Il y a '.floor($diff/60).' min';
                            } elseif ($diff < 86400) {
                                echo 'Il y a '.floor($diff/3600).' h';
                            } else {
                                echo dol_print_date($notif_time, 'dayhour');
                            }
                            ?>
                        </div>
                    </div>
                    <div style="font-size:20px;color:<?php echo $type_color; ?>">→</div>
                </div>
                <div style="position:absolute;bottom:8px;right:8px;background:#f1f5f9;color:#64748b;padding:4px 8px;border-radius:6px;font-size:9px;font-weight:700">
                    Cliquer pour traiter
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <!-- Météo avec prévisions -->
        <div class="card" id="weatherCard">
            <div class="card-header" style="border-bottom:1px solid var(--border-color);padding-bottom:12px;margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="font-size:20px">☀️</div>
                    <div class="card-title">Météo</div>
                </div>
                <div id="weatherLocation" style="font-size:11px;color:var(--text-light);font-weight:600"></div>
            </div>

            <!-- Météo du jour -->
            <div style="background:linear-gradient(135deg,#e0f2fe 0%,#bae6fd 100%);border-radius:12px;padding:16px;margin-bottom:12px" id="weatherToday">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div style="flex:1">
                        <div style="font-size:11px;color:#075985;font-weight:700;margin-bottom:4px;letter-spacing:0.5px">AUJOURD'HUI</div>
                        <div id="weatherTemp" style="font-size:36px;font-weight:800;color:#0369a1">--°</div>
                        <div id="weatherDesc" style="font-size:13px;color:#475569;margin-top:4px;font-weight:600">Chargement...</div>
                        <div style="font-size:12px;color:#64748b;margin-top:6px">
                            <span id="weatherFeelsLike">--</span> •
                            <span id="weatherHumidity">--</span>
                        </div>
                    </div>
                    <div id="weatherIcon" style="font-size:72px">⏳</div>
                </div>
            </div>

            <!-- Prévisions 3 jours -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px" id="weatherForecast">
                <div style="background:white;border:2px solid var(--border-color);border-radius:12px;padding:12px;text-align:center">
                    <div style="font-size:12px;font-weight:700;color:var(--text-primary);margin-bottom:8px">...</div>
                    <div style="font-size:36px;margin-bottom:4px">⏳</div>
                    <div style="font-size:16px;font-weight:700;color:var(--primary);margin-bottom:4px">--° / --°</div>
                </div>
                <div style="background:white;border:2px solid var(--border-color);border-radius:12px;padding:12px;text-align:center">
                    <div style="font-size:12px;font-weight:700;color:var(--text-primary);margin-bottom:8px">...</div>
                    <div style="font-size:36px;margin-bottom:4px">⏳</div>
                    <div style="font-size:16px;font-weight:700;color:var(--primary);margin-bottom:4px">--° / --°</div>
                </div>
                <div style="background:white;border:2px solid var(--border-color);border-radius:12px;padding:12px;text-align:center">
                    <div style="font-size:12px;font-weight:700;color:var(--text-primary);margin-bottom:8px">...</div>
                    <div style="font-size:36px;margin-bottom:4px">⏳</div>
                    <div style="font-size:16px;font-weight:700;color:var(--primary);margin-bottom:4px">--° / --°</div>
                </div>
            </div>
        </div>

        <!-- Planning du jour -->
        <?php
        $sql_today = "SELECT DISTINCT a.id, a.label, a.datep, a.datep2, a.fulldayevent, a.location, a.note as note_private,
                           s.nom as client_nom, p.ref as projet_ref, p.title as projet_title, ac.code as type_code
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
                        (a.datep2 IS NOT NULL AND DATE(a.datep) <= '".$db->escape($today)."' AND DATE(a.datep2) >= '".$db->escape($today)."')
                        OR (DATE(a.datep) = '".$db->escape($today)."')
                    )
                    ORDER BY a.datep ASC";
        $resql_today = $db->query($sql_today);
        $has_events = ($resql_today && $db->num_rows($resql_today) > 0);
        ?>

        <?php if ($has_events): ?>
        <div class="card">
            <div class="card-header">
                <div class="card-title">📅 Planning du jour <span style="font-size:10px;color:#64748b;font-weight:600;background:#f1f5f9;padding:2px 6px;border-radius:8px;margin-left:4px">S<?php echo $week_number; ?></span></div>
                <a href="planning/index.php" style="color:var(--primary);font-size:11px;text-decoration:none;font-weight:600">Voir tout →</a>
            </div>

            <?php while ($event = $db->fetch_object($resql_today)):
                // Extraire le numéro SAV de la note privée
                $sav_number = '';
                if ($event->note_private && preg_match('/SAV[:\s]*([A-Z0-9\-]+)/i', $event->note_private, $matches)) {
                    $sav_number = $matches[1];
                }
            ?>
            <div class="list-item" style="margin-top:8px;cursor:pointer;position:relative;overflow:hidden" onclick="window.location='planning/index.php'">
                <?php if ($event->projet_ref): ?>
                <div style="position:absolute;top:0;right:0;background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);color:white;padding:4px 12px 4px 16px;border-bottom-left-radius:12px;font-size:9px;font-weight:700;letter-spacing:0.5px;box-shadow:0 2px 8px rgba(59,130,246,0.3)">
                    🏗️ <?php echo dol_escape_htmltag($event->projet_ref); ?>
                </div>
                <?php endif; ?>
                <div class="list-item-icon" style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%)">
                    <?php echo $event->fulldayevent ? '📅' : '⏰'; ?>
                </div>
                <div class="list-item-content">
                    <div class="list-item-title" style="<?php if($event->projet_ref) echo 'padding-right:70px;'; ?>"><?php echo dol_escape_htmltag($event->label); ?></div>
                    <div class="list-item-subtitle">
                        <?php if ($event->client_nom): ?>
                            🏢 <?php echo dol_escape_htmltag($event->client_nom); ?>
                        <?php endif; ?>
                        <?php if ($sav_number): ?>
                            <?php if ($event->client_nom) echo ' • '; ?>
                            <span style="background:#fef3c7;color:#92400e;padding:2px 6px;border-radius:8px;font-size:9px;font-weight:700">
                                📋 <?php echo dol_escape_htmltag($sav_number); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="list-item-meta">
                        <?php if (!$event->fulldayevent): ?>
                            ⏰ <?php echo dol_print_date($db->jdate($event->datep), '%H:%M'); ?>
                            <?php if ($event->datep2): ?>
                                - <?php echo dol_print_date($db->jdate($event->datep2), '%H:%M'); ?>
                            <?php endif; ?>
                        <?php else: ?>
                            📅 Toute la journée
                        <?php endif; ?>
                        <?php if ($event->location): ?>
                            • 📍 <?php echo dol_escape_htmltag($event->location); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="color:var(--text-light)">→</div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
            <div class="card" style="text-align:center">
                <div style="font-size:32px;font-weight:700;color:var(--primary)"><?php echo $rapports_today; ?></div>
                <div style="font-size:13px;color:var(--text-light);margin-top:4px">Rapports aujourd'hui</div>
            </div>
            <div class="card" style="text-align:center">
                <div style="font-size:32px;font-weight:700;color:var(--secondary)"><?php echo $events_today; ?></div>
                <div style="font-size:13px;color:var(--text-light);margin-top:4px">Affectations</div>
            </div>
        </div>

        <?php if ($next_event): ?>
        <div class="card" style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%)">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                <div style="width:48px;height:48px;background:white;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px">📅</div>
                <div style="flex:1">
                    <div style="font-size:12px;color:#1e40af;font-weight:600;margin-bottom:2px">PROCHAIN CHANTIER</div>
                    <div style="font-size:16px;font-weight:700;color:#1e293b"><?php echo dol_escape_htmltag($next_event->label); ?></div>
                </div>
            </div>
            <div style="font-size:13px;color:#475569">
                <?php if ($next_event->client_nom): ?>
                    <div>🏢 <?php echo dol_escape_htmltag($next_event->client_nom); ?></div>
                <?php endif; ?>
                <?php if ($next_event->location): ?>
                    <div>📍 <?php echo dol_escape_htmltag($next_event->location); ?></div>
                <?php endif; ?>
                <div>⏰ <?php echo dol_print_date($db->jdate($next_event->datep), 'dayhour'); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-title">🚀 Actions rapides</div>
            </div>

            <div style="display:grid;gap:8px">
                <a href="rapports/new.php" class="list-item">
                    <div class="list-item-icon" style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%)">
                        ➕
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title">Nouveau rapport</div>
                        <div class="list-item-subtitle">Créer un rapport de chantier</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </a>

                <a href="planning/index.php" class="list-item">
                    <div class="list-item-icon" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%)">
                        📅
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title">Mon planning</div>
                        <div class="list-item-subtitle">Voir mes affectations</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </a>

                <a href="/custom/mv3pro_portail/mobile_app/sens_pose/list.php" class="list-item">
                    <div class="list-item-icon" style="background:linear-gradient(135deg,#e0e7ff 0%,#c7d2fe 100%)">
                        🔲
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title">Sens de Pose</div>
                        <div class="list-item-subtitle">Fiches de validation carrelage</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </a>

                <a href="materiel/list.php" class="list-item">
                    <div class="list-item-icon" style="background:linear-gradient(135deg,#d1fae5 0%,#a7f3d0 100%)">
                        🛠️
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title">Matériel</div>
                        <div class="list-item-subtitle">Consulter le matériel</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </a>

                <a href="materiel/list.php?action=reprendre" class="list-item">
                    <div class="list-item-icon" style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%)">
                        🔄
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title">Reprendre matériel</div>
                        <div class="list-item-subtitle">Récupérer du matériel d'un collègue</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </a>

                <a href="regie/list.php" class="list-item">
                    <div class="list-item-icon" style="background:linear-gradient(135deg,#fce7f3 0%,#fbcfe8 100%)">
                        📝
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title">Bons de régie</div>
                        <div class="list-item-subtitle">Travaux supplémentaires</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">📊 Mes derniers rapports</div>
                <a href="rapports/list.php" style="font-size:14px;color:var(--primary);text-decoration:none;font-weight:600">Voir tout →</a>
            </div>

            <?php
            $sql_last_rapports = "SELECT r.rowid, r.ref, r.date_rapport, s.nom as client_nom, r.temps_total
                                  FROM ".MAIN_DB_PREFIX."mv3_rapport r
                                  LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = r.fk_soc
                                  WHERE r.fk_user = ".(int)$user_id."
                                  ORDER BY r.date_rapport DESC, r.rowid DESC
                                  LIMIT 3";
            $resql_last = $db->query($sql_last_rapports);

            if ($resql_last && $db->num_rows($resql_last) > 0) {
                while ($rapport = $db->fetch_object($resql_last)) {
                    echo '<a href="rapports/view.php?id='.$rapport->rowid.'" class="list-item">';
                    echo '<div class="list-item-icon">📋</div>';
                    echo '<div class="list-item-content">';
                    echo '<div class="list-item-title">'.dol_escape_htmltag($rapport->client_nom ?: $rapport->ref).'</div>';
                    echo '<div class="list-item-meta">'.dol_print_date($db->jdate($rapport->date_rapport), 'day');
                    if ($rapport->temps_total) echo ' • '.$rapport->temps_total.'h';
                    echo '</div>';
                    echo '</div>';
                    echo '<div style="color:var(--text-light)">→</div>';
                    echo '</a>';
                }
            } else {
                echo '<div class="empty-state">';
                echo '<div class="empty-state-icon">📭</div>';
                echo '<div class="empty-state-text">Aucun rapport</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <button class="fab" onclick="window.location.href='rapports/new.php'">
        ➕
    </button>

    <?php include 'includes/bottom_nav.php'; ?>

    <script src="js/app.js"></script>
    <script>
    // Rafraîchissement automatique des données toutes les 2 minutes
    let autoRefreshTimer = null;

    function startAutoRefresh() {
        // Rafraîchir toutes les 2 minutes
        autoRefreshTimer = setInterval(() => {
            console.log('[Dashboard] Auto-refresh des données...');

            // Vérifier si l'utilisateur est toujours sur la page
            if (!document.hidden) {
                // Recharger discrètement la page
                window.location.reload();
            }
        }, 2 * 60 * 1000); // 2 minutes
    }

    // Arrêter le rafraîchissement quand la page n'est pas visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            console.log('[Dashboard] Page cachée - pause auto-refresh');
            if (autoRefreshTimer) {
                clearInterval(autoRefreshTimer);
                autoRefreshTimer = null;
            }
        } else {
            console.log('[Dashboard] Page visible - reprise auto-refresh');
            startAutoRefresh();
            // Rafraîchir immédiatement quand l'utilisateur revient
            setTimeout(() => window.location.reload(), 500);
        }
    });

    // Démarrer le rafraîchissement automatique
    startAutoRefresh();

    // Message depuis le service worker (notification cliquée)
    navigator.serviceWorker?.addEventListener('message', (event) => {
        if (event.data.type === 'NOTIFICATION_CLICK') {
            console.log('[Dashboard] Navigation depuis notification:', event.data.url);
            window.location.href = event.data.url;
        }
    });


    // Charger la météo avec prévisions
    async function loadWeather() {
        if (!navigator.geolocation) {
            console.log('Géolocalisation non supportée');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                try {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;

                    const response = await fetch(`https://wttr.in/${lat},${lon}?format=j1`);
                    const data = await response.json();

                    if (data && data.current_condition && data.current_condition[0]) {
                        const current = data.current_condition[0];
                        const area = data.nearest_area && data.nearest_area[0];

                        // Icônes météo
                        const weatherIcons = {
                            'Sunny': '☀️',
                            'Clear': '🌙',
                            'Partly cloudy': '⛅',
                            'Cloudy': '☁️',
                            'Overcast': '☁️',
                            'Mist': '🌫️',
                            'Fog': '🌫️',
                            'Patchy rain possible': '🌦️',
                            'Patchy rain nearby': '🌦️',
                            'Light rain': '🌧️',
                            'Light rain shower': '🌧️',
                            'Moderate rain': '🌧️',
                            'Heavy rain': '⛈️',
                            'Thundery outbreaks possible': '⛈️',
                            'Patchy snow possible': '🌨️',
                            'Light snow': '❄️',
                            'Moderate snow': '❄️',
                            'Heavy snow': '❄️'
                        };

                        const getWeatherIcon = (desc) => {
                            return weatherIcons[desc] || '🌤️';
                        };

                        const weatherDesc = current.weatherDesc && current.weatherDesc[0] ? current.weatherDesc[0].value : '';
                        const icon = getWeatherIcon(weatherDesc);

                        // Traduire les descriptions
                        const weatherTranslations = {
                            'Sunny': 'Ensoleillé',
                            'Clear': 'Dégagé',
                            'Partly cloudy': 'Partiellement nuageux',
                            'Cloudy': 'Nuageux',
                            'Overcast': 'Couvert',
                            'Mist': 'Brume',
                            'Fog': 'Brouillard',
                            'Patchy rain possible': 'Pluie possible',
                            'Patchy rain nearby': 'Pluie à proximité',
                            'Light rain': 'Pluie légère',
                            'Light rain shower': 'Averses légères',
                            'Moderate rain': 'Pluie modérée',
                            'Heavy rain': 'Forte pluie',
                            'Thundery outbreaks possible': 'Orages possibles',
                            'Patchy snow possible': 'Neige possible',
                            'Light snow': 'Neige légère',
                            'Moderate snow': 'Neige modérée',
                            'Heavy snow': 'Forte neige'
                        };

                        const translatedDesc = weatherTranslations[weatherDesc] || weatherDesc;

                        // Afficher météo actuelle
                        document.getElementById('weatherLocation').textContent = area ? area.areaName[0].value : 'Ma position';
                        document.getElementById('weatherTemp').textContent = current.temp_C + '°';
                        document.getElementById('weatherDesc').textContent = translatedDesc;
                        document.getElementById('weatherFeelsLike').textContent = 'Ressenti ' + current.FeelsLikeC + '°';
                        document.getElementById('weatherHumidity').textContent = 'Humidité ' + current.humidity + '%';
                        document.getElementById('weatherIcon').textContent = icon;

                        // Afficher prévisions 3 jours
                        if (data.weather && data.weather.length >= 3) {
                            const forecastContainer = document.getElementById('weatherForecast');
                            forecastContainer.innerHTML = '';

                            const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

                            for (let i = 1; i <= 3; i++) {
                                const forecast = data.weather[i];
                                const date = new Date(forecast.date);
                                const dayName = dayNames[date.getDay()];

                                const avgDesc = forecast.hourly[4].weatherDesc[0].value;
                                const avgIcon = getWeatherIcon(avgDesc);
                                const avgTranslated = weatherTranslations[avgDesc] || avgDesc;

                                const forecastCard = `
                                    <div style="background:white;border:2px solid var(--border-color);border-radius:12px;padding:12px;text-align:center">
                                        <div style="font-size:12px;font-weight:700;color:var(--text-primary);margin-bottom:8px">${dayName}</div>
                                        <div style="font-size:36px;margin-bottom:4px">${avgIcon}</div>
                                        <div style="font-size:16px;font-weight:700;color:var(--primary);margin-bottom:4px">
                                            ${forecast.maxtempC}° / ${forecast.mintempC}°
                                        </div>
                                        <div style="font-size:10px;color:var(--text-light);line-height:1.3">
                                            ${avgTranslated.substring(0, 20)}
                                        </div>
                                    </div>
                                `;
                                forecastContainer.innerHTML += forecastCard;
                            }
                        }

                        // Carte déjà visible par défaut
                    }
                } catch (error) {
                    console.error('Erreur météo:', error);
                }
            },
            (error) => {
                console.log('Géolocalisation refusée ou erreur:', error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 600000
            }
        );
    }

    // Charger au démarrage
    window.addEventListener('load', () => {
        loadWeather();
    });
    </script>
</body>
</html>
<?php $db->close(); ?>
