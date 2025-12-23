<?php
/**
 * Profil utilisateur - Mobile
 */

$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";

if (!isset($_SESSION['dol_login']) || empty($user->id)) {
    header('Location: ../index.php');
    exit;
}

$user_id = $user->id;

if (GETPOST('action', 'alpha') === 'logout') {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$sql = "SELECT u.*,
               (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."mv3_rapport WHERE fk_user = u.rowid) as nb_rapports,
               (SELECT COUNT(DISTINCT a.id) FROM ".MAIN_DB_PREFIX."actioncomm a
                LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm ac ON ac.id = a.fk_action
                LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources ar ON ar.fk_actioncomm = a.id
                WHERE (a.fk_user_author = u.rowid OR a.fk_user_action = u.rowid OR a.fk_user_done = u.rowid
                       OR (ar.element_type = 'user' AND ar.fk_element = u.rowid))
                AND ac.code IN ('AC_POS', 'AC_plan')) as nb_events
        FROM ".MAIN_DB_PREFIX."user u
        WHERE u.rowid = ".(int)$user_id;

$resql = $db->query($sql);
$user_data = null;
if ($resql) {
    $user_data = $db->fetch_object($resql);
}

// Si erreur SQL, utiliser l'objet user existant avec valeurs par défaut
if (!$user_data) {
    $user_data = (object)[
        'rowid' => $user->id,
        'login' => $user->login,
        'firstname' => $user->firstname,
        'lastname' => $user->lastname,
        'email' => $user->email,
        'office_phone' => $user->office_phone,
        'statut' => $user->statut,
        'nb_rapports' => 0,
        'nb_events' => 0
    ];
}

$rapports_month = 0;
$sql_this_month = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."mv3_rapport
                   WHERE fk_user = ".(int)$user_id."
                   AND MONTH(date_rapport) = MONTH(NOW())
                   AND YEAR(date_rapport) = YEAR(NOW())";
$resql_month = $db->query($sql_this_month);
if ($resql_month) {
    $obj = $db->fetch_object($resql_month);
    if ($obj) $rapports_month = $obj->total;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0891b2">
    <title>Profil - MV3 PRO Mobile</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
</head>
<body>
    <div class="app-header">
        <div>
            <div class="app-header-title">👤 Mon Profil</div>
            <div class="app-header-subtitle">Informations et statistiques</div>
        </div>
        <a href="../dashboard.php" style="color:white;font-size:24px;text-decoration:none">✕</a>
    </div>

    <div class="app-container">
        <div class="card" style="text-align:center;background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);color:white">
            <div style="width:80px;height:80px;border-radius:50%;background:white;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:40px;margin:0 auto 16px">
                👤
            </div>
            <div style="font-size:24px;font-weight:700;margin-bottom:4px">
                <?php echo dol_escape_htmltag($user_data->firstname.' '.$user_data->lastname); ?>
            </div>
            <div style="font-size:14px;opacity:0.9">
                <?php echo dol_escape_htmltag($user_data->login); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title">📊 Mes statistiques</div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
                <div style="text-align:center;padding:20px;background:var(--bg);border-radius:12px">
                    <div style="font-size:32px;font-weight:700;color:var(--primary)"><?php echo $user_data->nb_rapports; ?></div>
                    <div style="font-size:13px;color:var(--text-light);margin-top:4px">Rapports total</div>
                </div>

                <div style="text-align:center;padding:20px;background:var(--bg);border-radius:12px">
                    <div style="font-size:32px;font-weight:700;color:var(--secondary)"><?php echo $rapports_month; ?></div>
                    <div style="font-size:13px;color:var(--text-light);margin-top:4px">Ce mois-ci</div>
                </div>

                <div style="text-align:center;padding:20px;background:var(--bg);border-radius:12px">
                    <div style="font-size:32px;font-weight:700;color:var(--warning)"><?php echo $user_data->nb_events; ?></div>
                    <div style="font-size:13px;color:var(--text-light);margin-top:4px">Affectations</div>
                </div>

                <div style="text-align:center;padding:20px;background:var(--bg);border-radius:12px">
                    <div style="font-size:32px;font-weight:700;color:var(--danger)">
                        <?php echo $user_data->statut == 1 ? '✓' : '✕'; ?>
                    </div>
                    <div style="font-size:13px;color:var(--text-light);margin-top:4px">Compte actif</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">ℹ️ Informations</div>

            <div style="display:grid;gap:12px;margin-top:16px">
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">LOGIN</div>
                    <div style="font-size:16px;font-weight:700"><?php echo dol_escape_htmltag($user_data->login); ?></div>
                </div>

                <?php if (!empty($user_data->email)): ?>
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">EMAIL</div>
                    <div style="font-size:16px;font-weight:700"><?php echo dol_escape_htmltag($user_data->email); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($user_data->office_phone)): ?>
                <div>
                    <div style="font-size:12px;color:var(--text-light);font-weight:600;margin-bottom:4px">TÉLÉPHONE</div>
                    <div style="font-size:16px;font-weight:700"><?php echo dol_escape_htmltag($user_data->office_phone); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title">⚙️ Paramètres</div>

            <div style="display:grid;gap:8px;margin-top:16px">
                <button class="list-item" onclick="toggleNotifications()" style="border:none;width:100%;cursor:pointer">
                    <div class="list-item-icon">🔔</div>
                    <div class="list-item-content">
                        <div class="list-item-title">Notifications</div>
                        <div class="list-item-subtitle" id="notifStatus">Vérification...</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </button>

                <button class="list-item" onclick="checkForUpdates()" style="border:none;width:100%;cursor:pointer" id="updateBtn">
                    <div class="list-item-icon">🔄</div>
                    <div class="list-item-content">
                        <div class="list-item-title">Mise à jour</div>
                        <div class="list-item-subtitle" id="updateStatus">Vérifier les mises à jour</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </button>

                <button class="list-item" onclick="installApp()" style="border:none;width:100%;cursor:pointer;display:none" id="installBtn">
                    <div class="list-item-icon">📱</div>
                    <div class="list-item-content">
                        <div class="list-item-title">Installer l'application</div>
                        <div class="list-item-subtitle">Ajouter à l'écran d'accueil</div>
                    </div>
                    <div style="color:var(--text-light)">→</div>
                </button>
            </div>
        </div>

        <a href="?action=logout" class="btn" style="background:var(--danger);color:white">
            <span>🚪 Déconnexion</span>
        </a>

        <div class="card" style="text-align:center">
            <div style="font-size:13px;color:var(--text-light);margin-bottom:8px">MV3 PRO Portail Mobile</div>
            <div id="appVersion" style="font-size:24px;font-weight:700;color:var(--primary);margin-bottom:8px">v3.1.0</div>
            <div id="buildInfo" style="font-size:11px;color:var(--text-light);margin-bottom:16px">Build 20250107001</div>
            <button onclick="showChangelog()" class="btn" style="background:var(--card);color:var(--text);border:1px solid var(--border);width:100%">
                📋 Notes de version
            </button>
        </div>

        <div style="text-align:center;padding:20px;color:var(--text-light);font-size:12px">
            <?php echo dol_print_date(dol_now(), '%Y'); ?> - Tous droits réservés
        </div>

        <!-- Modal Changelog -->
        <div id="changelogModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:10000;align-items:center;justify-content:center" onclick="if(event.target===this) closeChangelog()">
            <div style="background:white;width:90%;max-width:500px;max-height:80vh;border-radius:16px;overflow:hidden">
                <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;display:flex;justify-content:space-between;align-items:center">
                    <div style="font-size:18px;font-weight:700">📋 Historique des versions</div>
                    <button onclick="closeChangelog()" style="background:rgba(255,255,255,0.2);border:none;color:white;font-size:24px;width:36px;height:36px;border-radius:50%;cursor:pointer">&times;</button>
                </div>
                <div id="changelogContent" style="padding:20px;overflow-y:auto;max-height:60vh"></div>
            </div>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script src="../js/app.js"></script>
    <script>
        let deferredPrompt = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('installBtn').style.display = 'flex';
        });

        async function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    document.getElementById('installBtn').style.display = 'none';
                }
                deferredPrompt = null;
            } else {
                alert('Application déjà installée ou non disponible');
            }
        }

        function checkNotificationStatus() {
            const status = document.getElementById('notifStatus');
            if ('Notification' in window) {
                if (Notification.permission === 'granted') {
                    status.textContent = 'Activées ✓';
                    status.style.color = 'var(--secondary)';
                } else if (Notification.permission === 'denied') {
                    status.textContent = 'Refusées ✗';
                    status.style.color = 'var(--danger)';
                } else {
                    status.textContent = 'Non configurées';
                }
            } else {
                status.textContent = 'Non supportées';
            }
        }

        async function toggleNotifications() {
            if ('Notification' in window && Notification.permission === 'default') {
                const permission = await Notification.requestPermission();
                checkNotificationStatus();
                if (permission === 'granted') {
                    new Notification('MV3 PRO Mobile', {
                        body: 'Les notifications sont activées !',
                        icon: '/custom/mv3pro_portail/mobile_app/icon-192.png'
                    });
                }
            }
        }

        checkNotificationStatus();

        // Vérifier les mises à jour
        async function checkForUpdates() {
            const updateStatus = document.getElementById('updateStatus');
            const updateIcon = document.querySelector('#updateBtn .list-item-icon');

            updateStatus.textContent = 'Vérification en cours...';
            updateIcon.textContent = '⏳';

            try {
                const registration = await navigator.serviceWorker.getRegistration();

                if (registration) {
                    // Forcer la vérification de mise à jour
                    await registration.update();

                    // Attendre un peu pour voir si une mise à jour est disponible
                    setTimeout(() => {
                        if (registration.waiting) {
                            updateStatus.textContent = 'Nouvelle version disponible !';
                            updateIcon.textContent = '✨';

                            // Proposer d'installer immédiatement
                            if (confirm('Une nouvelle version est disponible ! Voulez-vous l\'installer maintenant ?')) {
                                registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                                window.location.reload();
                            }
                        } else if (registration.installing) {
                            updateStatus.textContent = 'Installation en cours...';
                            updateIcon.textContent = '⏳';

                            registration.installing.addEventListener('statechange', (e) => {
                                if (e.target.state === 'activated') {
                                    updateStatus.textContent = 'Mise à jour installée !';
                                    updateIcon.textContent = '✅';
                                    setTimeout(() => window.location.reload(), 1000);
                                }
                            });
                        } else {
                            updateStatus.textContent = 'Application à jour ✓';
                            updateIcon.textContent = '✅';

                            // Rafraîchir le cache quand même
                            if (registration.active) {
                                registration.active.postMessage({ type: 'CHECK_UPDATE' });
                            }

                            // Réinitialiser après 3 secondes
                            setTimeout(() => {
                                updateStatus.textContent = 'Vérifier les mises à jour';
                                updateIcon.textContent = '🔄';
                            }, 3000);
                        }
                    }, 1000);
                } else {
                    updateStatus.textContent = 'Service worker non disponible';
                    updateIcon.textContent = '❌';
                    setTimeout(() => {
                        updateStatus.textContent = 'Vérifier les mises à jour';
                        updateIcon.textContent = '🔄';
                    }, 3000);
                }
            } catch (error) {
                console.error('Erreur lors de la vérification:', error);
                updateStatus.textContent = 'Erreur lors de la vérification';
                updateIcon.textContent = '❌';
                setTimeout(() => {
                    updateStatus.textContent = 'Vérifier les mises à jour';
                    updateIcon.textContent = '🔄';
                }, 3000);
            }
        }

        // Vérification automatique au chargement
        window.addEventListener('load', () => {
            setTimeout(() => {
                navigator.serviceWorker?.getRegistration().then(reg => {
                    if (reg?.waiting) {
                        document.getElementById('updateStatus').textContent = 'Mise à jour disponible !';
                        document.querySelector('#updateBtn .list-item-icon').textContent = '✨';
                    }
                });
            }, 2000);

            // Charger les infos de version
            loadVersionInfo();
        });

        // Charger les informations de version
        async function loadVersionInfo() {
            try {
                const response = await fetch('../version.json');
                const data = await response.json();

                document.getElementById('appVersion').textContent = 'v' + data.version;
                document.getElementById('buildInfo').textContent = 'Build ' + data.build + ' • ' + data.date;
            } catch (error) {
                console.error('Erreur chargement version:', error);
            }
        }

        // Afficher le changelog
        async function showChangelog() {
            try {
                const response = await fetch('../version.json');
                const data = await response.json();

                let html = '';
                data.changelog.forEach((version, index) => {
                    html += '<div style="margin-bottom:24px;padding-bottom:24px;' + (index < data.changelog.length - 1 ? 'border-bottom:1px solid #e5e7eb' : '') + '">';
                    html += '<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">';
                    html += '<div style="font-size:18px;font-weight:700;color:#1f2937">v' + version.version + '</div>';
                    if (index === 0) {
                        html += '<span style="background:#10b981;color:white;padding:4px 8px;border-radius:12px;font-size:11px;font-weight:700">ACTUELLE</span>';
                    }
                    html += '</div>';
                    html += '<div style="font-size:12px;color:#6b7280;margin-bottom:12px">📅 ' + version.date + '</div>';
                    html += '<ul style="margin:0;padding-left:20px;list-style:none">';
                    version.changes.forEach(change => {
                        html += '<li style="margin-bottom:8px;font-size:14px;color:#374151">' + change + '</li>';
                    });
                    html += '</ul>';
                    html += '</div>';
                });

                document.getElementById('changelogContent').innerHTML = html;
                document.getElementById('changelogModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('Erreur chargement changelog:', error);
                alert('Impossible de charger l\'historique des versions');
            }
        }

        function closeChangelog() {
            document.getElementById('changelogModal').style.display = 'none';
            document.body.style.overflow = '';
        }
    </script>
</body>
</html>
<?php $db->close(); ?>
