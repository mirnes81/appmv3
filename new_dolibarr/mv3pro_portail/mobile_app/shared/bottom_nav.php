<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

function isActive($page, $dir = null) {
    global $current_page, $current_dir;
    if ($dir && $current_dir === $dir) return 'active';
    if ($current_page === $page) return 'active';
    return '';
}
?>
<nav class="bottom-nav">
    <a href="/custom/mv3pro_portail/mobile_app/dashboard.php" class="bottom-nav-item <?php echo isActive('dashboard'); ?>">
        <div class="bottom-nav-icon">🏠</div>
        <div>Accueil</div>
    </a>
    <a href="/custom/mv3pro_portail/mobile_app/regie/list.php" class="bottom-nav-item <?php echo isActive('', 'regie'); ?>">
        <div class="bottom-nav-icon">📝</div>
        <div>Régie</div>
    </a>
    <a href="/custom/mv3pro_portail/mobile_app/rapports/list.php" class="bottom-nav-item <?php echo isActive('', 'rapports'); ?>">
        <div class="bottom-nav-icon">📋</div>
        <div>Rapports</div>
    </a>
    <a href="/custom/mv3pro_portail/mobile_app/notifications/" class="bottom-nav-item <?php echo isActive('', 'notifications'); ?>" style="position: relative;">
        <div class="bottom-nav-icon">🔔</div>
        <div>Notifs</div>
        <span class="nav-badge" id="notif-badge" style="
            position: absolute;
            top: 4px;
            right: 50%;
            transform: translateX(20px);
            background: #ef4444;
            color: white;
            border-radius: 12px;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            display: none;
        ">0</span>
    </a>
    <a href="/custom/mv3pro_portail/mobile_app/profil/index.php" class="bottom-nav-item <?php echo isActive('', 'profil'); ?>">
        <div class="bottom-nav-icon">👤</div>
        <div>Profil</div>
    </a>
</nav>

<script>
// Mettre à jour le badge des notifications
if (typeof updateNotificationBadge === 'undefined') {
    async function updateNotificationBadge() {
        try {
            const response = await fetch('/custom/mv3pro_portail/mobile_app/api/notifications.php?action=count');
            const data = await response.json();

            if (data.success) {
                const badge = document.getElementById('notif-badge');
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'flex' : 'none';
                }
            }
        } catch (error) {
            console.error('Erreur badge notifications:', error);
        }
    }

    // Mettre à jour au chargement
    document.addEventListener('DOMContentLoaded', updateNotificationBadge);

    // Rafraîchir toutes les 30 secondes
    setInterval(updateNotificationBadge, 30000);
}
</script>
