<?php
/**
 * Dashboard mobile avec authentification indépendante
 */

require_once __DIR__ . '/includes/dolibarr_bootstrap.php';
require_once __DIR__ . '/includes/auth_helpers.php';
require_once __DIR__ . '/includes/html_helpers.php';
require_once __DIR__ . '/includes/db_helpers.php';

loadDolibarr([
    'NOCSRFCHECK' => 1,
    'NOREQUIREMENU' => 1,
    'NOIPCHECK' => 1,
    'NOREQUIREUSER' => 1
]);

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once __DIR__.'/includes/session_mobile.php';

global $db;

// Vérifier l'authentification
$mobile_user = requireMobileAuth();

if (!$mobile_user) {
    header('Location: login_mobile.php');
    exit;
}

$user_id = $mobile_user->dolibarr_user_id ?? null;
$mobile_user_id = $mobile_user->user_rowid;
$username = $mobile_user->firstname.' '.$mobile_user->lastname;

$today = date('Y-m-d');

// Calculer le numéro de semaine
$week_number = (int)date('W');
if ($week_number == 0) $week_number = 52;

// Compter les rapports du jour (si lié à un user Dolibarr)
$rapports_today = 0;
if ($user_id) {
    $sql_rapports_today = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."mv3_rapport
                           WHERE fk_user = ".(int)$user_id." AND DATE(date_rapport) = '".$db->escape($today)."'";
    $resql_rapports = $db->query($sql_rapports_today);
    if ($resql_rapports) {
        $obj = $db->fetch_object($resql_rapports);
        if ($obj) $rapports_today = $obj->total;
    }
}

// Compter les affectations du jour (si lié à un user Dolibarr)
$events_today = 0;
if ($user_id) {
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
}

// Récupérer le prochain évènement (si lié à un user Dolibarr)
$next_event = null;
if ($user_id) {
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
            <div class="app-header-title">👋 Bonjour <?php echo dol_escape_htmltag($mobile_user->firstname); ?></div>
            <div class="app-header-subtitle">
                <?php echo dol_print_date(dol_now(), '%A %d %B %Y'); ?> • 📅 S<?php echo $week_number; ?>
            </div>
        </div>
        <div style="display:flex;gap:12px;align-items:center">
            <a href="profil/index.php" style="color:white;font-size:20px;text-decoration:none">⚙️</a>
            <button onclick="handleLogout()" style="background:rgba(255,255,255,0.2);border:none;color:white;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer">
                Déconnexion
            </button>
        </div>
    </div>

    <div class="app-container">
        <!-- Info utilisateur -->
        <div class="card" style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%)">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:48px;height:48px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px">
                    👤
                </div>
                <div style="flex:1">
                    <div style="font-size:16px;font-weight:700;color:#1e293b">
                        <?php echo dol_escape_htmltag($username); ?>
                    </div>
                    <div style="font-size:13px;color:#475569">
                        📧 <?php echo dol_escape_htmltag($mobile_user->email); ?>
                    </div>
                    <?php if ($mobile_user->phone): ?>
                    <div style="font-size:13px;color:#475569">
                        📱 <?php echo dol_escape_htmltag($mobile_user->phone); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="background:rgba(59,130,246,0.2);color:#1e40af;padding:6px 12px;border-radius:8px;font-size:11px;font-weight:700">
                    <?php echo strtoupper($mobile_user->role); ?>
                </div>
            </div>
        </div>

        <!-- Météo -->
        <div class="card" id="weatherCard">
            <div class="card-header">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="font-size:20px">☀️</div>
                    <div class="card-title">Météo</div>
                </div>
                <div id="weatherLocation" style="font-size:11px;color:var(--text-light);font-weight:600"></div>
            </div>

            <div style="background:linear-gradient(135deg,#e0f2fe 0%,#bae6fd 100%);border-radius:12px;padding:16px" id="weatherToday">
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div style="flex:1">
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
        </div>

        <!-- Statistiques -->
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
        <!-- Prochain chantier -->
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

        <!-- Actions rapides -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">🚀 Actions rapides</div>
            </div>

            <div style="display:grid;gap:8px">
                <a href="rapports/list.php" class="list-item">
                    <div class="list-item-icon" style="background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%)">
                        📋
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title">Mes rapports</div>
                        <div class="list-item-subtitle">Consulter mes rapports de chantier</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </a>

                <?php if ($user_id): ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script>
        // Fonction de déconnexion
        async function handleLogout() {
            if (!confirm('Voulez-vous vraiment vous déconnecter ?')) {
                return;
            }

            const token = localStorage.getItem('mobile_auth_token');

            try {
                await fetch('/custom/mv3pro_portail/mobile_app/api/auth.php?action=logout&token=' + token);
            } catch (error) {
                console.error('Logout error:', error);
            }

            localStorage.removeItem('mobile_auth_token');
            localStorage.removeItem('mobile_user_data');

            window.location.href = 'login_mobile.php';
        }

        // Charger la météo
        async function loadWeather() {
            if (!navigator.geolocation) return;

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

                            const weatherIcons = {
                                'Sunny': '☀️', 'Clear': '🌙', 'Partly cloudy': '⛅',
                                'Cloudy': '☁️', 'Overcast': '☁️', 'Mist': '🌫️',
                                'Fog': '🌫️', 'Light rain': '🌧️', 'Moderate rain': '🌧️',
                                'Heavy rain': '⛈️', 'Light snow': '❄️'
                            };

                            const weatherDesc = current.weatherDesc && current.weatherDesc[0] ? current.weatherDesc[0].value : '';
                            const icon = weatherIcons[weatherDesc] || '🌤️';

                            const weatherTranslations = {
                                'Sunny': 'Ensoleillé', 'Clear': 'Dégagé', 'Partly cloudy': 'Partiellement nuageux',
                                'Cloudy': 'Nuageux', 'Overcast': 'Couvert', 'Light rain': 'Pluie légère',
                                'Moderate rain': 'Pluie modérée', 'Heavy rain': 'Forte pluie'
                            };

                            const translatedDesc = weatherTranslations[weatherDesc] || weatherDesc;

                            document.getElementById('weatherLocation').textContent = area ? area.areaName[0].value : 'Ma position';
                            document.getElementById('weatherTemp').textContent = current.temp_C + '°';
                            document.getElementById('weatherDesc').textContent = translatedDesc;
                            document.getElementById('weatherFeelsLike').textContent = 'Ressenti ' + current.FeelsLikeC + '°';
                            document.getElementById('weatherHumidity').textContent = 'Humidité ' + current.humidity + '%';
                            document.getElementById('weatherIcon').textContent = icon;
                        }
                    } catch (error) {
                        console.error('Erreur météo:', error);
                    }
                },
                (error) => console.log('Géolocalisation refusée:', error),
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 600000 }
            );
        }

        window.addEventListener('load', () => {
            loadWeather();
        });
    </script>
</body>
</html>
<?php $db->close(); ?>
