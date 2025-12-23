<?php
/**
 * Page Notifications Mobile App
 */

$res = 0;
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";

if (!$res) die("Error: Cannot load main.inc.php");

// Vérifier permissions
if (!$user->rights->mv3pro_portail->read) accessforbidden();

$title = "Notifications";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - MV3 PRO</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        .notifications-container {
            padding: 16px;
            padding-bottom: 80px;
            max-width: 800px;
            margin: 0 auto;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding: 16px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .notifications-header h1 {
            font-size: 24px;
            margin: 0;
            color: #1e293b;
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .btn-mark-all {
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-mark-all:hover {
            background: #2563eb;
        }

        .btn-mark-all:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab.active {
            background: #3b82f6;
            color: white;
        }

        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .notification-item.unread {
            border-left: 4px solid #3b82f6;
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            top: 20px;
            right: 20px;
            width: 10px;
            height: 10px;
            background: #3b82f6;
            border-radius: 50%;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .notification-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .type-rapport_new {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-rapport_validated {
            background: #d1fae5;
            color: #065f46;
        }

        .type-materiel_low {
            background: #fef3c7;
            color: #92400e;
        }

        .type-info {
            background: #f1f5f9;
            color: #475569;
        }

        .notification-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .notification-message {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .notification-time {
            font-size: 12px;
            color: #94a3b8;
        }

        .notification-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }

        .btn-notification {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-view {
            background: #3b82f6;
            color: white;
        }

        .btn-delete {
            background: #f1f5f9;
            color: #64748b;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 18px;
            color: #64748b;
            margin: 0;
        }

        .loading {
            text-align: center;
            padding: 40px;
        }

        .spinner {
            border: 3px solid #f1f5f9;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="notifications-container">
        <div class="notifications-header">
            <h1>🔔 Notifications</h1>
            <div class="header-actions">
                <button class="btn-mark-all" id="markAllBtn" onclick="markAllAsRead()">
                    Tout marquer lu
                </button>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" data-status="non_lu" onclick="switchTab('non_lu')">
                Non lues (<span id="count-non_lu">0</span>)
            </button>
            <button class="tab" data-status="lu" onclick="switchTab('lu')">
                Lues
            </button>
            <button class="tab" data-status="all" onclick="switchTab('all')">
                Toutes
            </button>
        </div>

        <div id="notificationsList">
            <div class="loading">
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        let currentStatus = 'non_lu';

        // Charger les notifications au démarrage
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
            updateUnreadCount();

            // Rafraîchir toutes les 30 secondes
            setInterval(() => {
                loadNotifications();
                updateUnreadCount();
            }, 30000);
        });

        // Changer d'onglet
        function switchTab(status) {
            currentStatus = status;

            // Mettre à jour les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[data-status="${status}"]`).classList.add('active');

            loadNotifications();
        }

        // Charger les notifications
        async function loadNotifications() {
            const container = document.getElementById('notificationsList');

            try {
                const response = await fetch(`../api/notifications.php?statut=${currentStatus}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error);
                }

                if (data.notifications.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <h3>Aucune notification</h3>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = data.notifications.map(notif => renderNotification(notif)).join('');

            } catch (error) {
                console.error('Erreur:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <h3>Erreur de chargement</h3>
                    </div>
                `;
            }
        }

        // Afficher une notification
        function renderNotification(notif) {
            const isUnread = notif.statut === 'non_lu';
            const typeClass = `type-${notif.type}`;

            return `
                <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notif.id}">
                    <div class="notification-header">
                        <span class="notification-type ${typeClass}">${getTypeLabel(notif.type)}</span>
                    </div>
                    <div class="notification-title">${notif.titre}</div>
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-time">${notif.time_ago}</div>
                    <div class="notification-actions">
                        ${notif.fk_object ? `
                            <button class="btn-notification btn-view" onclick="viewObject('${notif.object_type}', ${notif.fk_object}, ${notif.id})">
                                Voir
                            </button>
                        ` : ''}
                        ${isUnread ? `
                            <button class="btn-notification btn-view" onclick="markAsRead(${notif.id})">
                                Marquer lu
                            </button>
                        ` : ''}
                        <button class="btn-notification btn-delete" onclick="deleteNotification(${notif.id})">
                            Supprimer
                        </button>
                    </div>
                </div>
            `;
        }

        // Labels des types
        function getTypeLabel(type) {
            const labels = {
                'rapport_new': 'Nouveau rapport',
                'rapport_validated': 'Rapport validé',
                'materiel_low': 'Matériel faible',
                'info': 'Information'
            };
            return labels[type] || 'Notification';
        }

        // Voir l'objet lié
        function viewObject(type, id, notifId) {
            markAsRead(notifId, false);

            const urls = {
                'rapport': `../rapports/view.php?id=${id}`,
                'materiel': `../materiel/view.php?id=${id}`,
                'signalement': `../signalements/view.php?id=${id}`
            };

            window.location.href = urls[type] || '../';
        }

        // Marquer comme lu
        async function markAsRead(id, reload = true) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('id', id);

                const response = await fetch('../api/notifications.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success && reload) {
                    loadNotifications();
                    updateUnreadCount();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Tout marquer comme lu
        async function markAllAsRead() {
            const btn = document.getElementById('markAllBtn');
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('action', 'mark_all_read');

                const response = await fetch('../api/notifications.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    loadNotifications();
                    updateUnreadCount();
                }
            } catch (error) {
                console.error('Erreur:', error);
            } finally {
                btn.disabled = false;
            }
        }

        // Supprimer notification
        async function deleteNotification(id) {
            if (!confirm('Supprimer cette notification ?')) return;

            try {
                const response = await fetch(`../api/notifications.php?id=${id}`, {
                    method: 'DELETE'
                });

                const data = await response.json();

                if (data.success) {
                    loadNotifications();
                    updateUnreadCount();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Mettre à jour le compteur
        async function updateUnreadCount() {
            try {
                const response = await fetch('../api/notifications.php?action=count');
                const data = await response.json();

                if (data.success) {
                    document.getElementById('count-non_lu').textContent = data.count;

                    // Mettre à jour le badge dans la nav
                    const badge = document.querySelector('.nav-badge');
                    if (badge) {
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
    </script>
</body>
</html>
