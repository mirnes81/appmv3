<?php
/**
 * Header partagé pour toutes les pages mobiles
 * Usage: require_once __DIR__.'/../shared/header.php';
 */

// Déterminer le titre de la page
$page_title = $page_title ?? 'MV3 PRO';
$show_back = $show_back ?? false;
$back_url = $back_url ?? '/custom/mv3pro_portail/mobile_app/dashboard_mobile.php';
?>
<header class="mobile-header">
    <?php if ($show_back): ?>
        <a href="<?php echo htmlspecialchars($back_url); ?>" class="header-back">
            <span class="back-icon">←</span>
        </a>
    <?php endif; ?>

    <h1 class="header-title"><?php echo htmlspecialchars($page_title); ?></h1>

    <div class="header-actions">
        <!-- Notifications badge -->
        <a href="/custom/mv3pro_portail/mobile_app/notifications/" class="header-notif" style="position: relative;">
            <span class="notif-icon">🔔</span>
            <span class="header-badge" id="header-notif-badge" style="display: none;">0</span>
        </a>
    </div>
</header>

<style>
.mobile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    color: white;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.header-back {
    color: white;
    font-size: 24px;
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 4px;
}

.back-icon {
    font-size: 28px;
}

.header-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    flex: 1;
    text-align: center;
}

.header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.header-notif {
    position: relative;
    color: white;
    text-decoration: none;
    font-size: 24px;
    padding: 4px;
}

.header-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ef4444;
    color: white;
    border-radius: 12px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
}
</style>

<script>
// Mettre à jour le badge notifications dans le header
if (typeof updateHeaderNotificationBadge === 'undefined') {
    async function updateHeaderNotificationBadge() {
        try {
            const response = await fetch('/custom/mv3pro_portail/mobile_app/api/notifications.php?action=count');
            const data = await response.json();

            if (data.success) {
                const badge = document.getElementById('header-notif-badge');
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'inline-flex' : 'none';
                }
            }
        } catch (error) {
            console.error('Erreur badge notifications:', error);
        }
    }

    document.addEventListener('DOMContentLoaded', updateHeaderNotificationBadge);
    setInterval(updateHeaderNotificationBadge, 30000);
}
</script>
