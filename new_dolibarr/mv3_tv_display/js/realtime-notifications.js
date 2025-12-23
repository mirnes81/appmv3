class MV3RealtimeNotifications {
    constructor() {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;
        this.notifications = [];
        this.sounds = {
            info: new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBy/LXiTYJFmS57OihUBEMUKfo77RiGwU7k9fyz...'),
            success: new Audio('data:audio/wav;base64,...'),
            warning: new Audio('data:audio/wav;base64,...'),
            error: new Audio('data:audio/wav;base64,...')
        };
        this.callbacks = {
            onConnect: [],
            onDisconnect: [],
            onNotification: [],
            onError: []
        };
    }

    connect(url) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            console.log('WebSocket already connected');
            return;
        }

        try {
            this.ws = new WebSocket(url);

            this.ws.onopen = () => {
                console.log('WebSocket connected');
                this.reconnectAttempts = 0;
                this.trigger('onConnect');
                this.showConnectionStatus('connected');
            };

            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleNotification(data);
                } catch (error) {
                    console.error('Error parsing message:', error);
                }
            };

            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.trigger('onError', error);
                this.showConnectionStatus('error');
            };

            this.ws.onclose = () => {
                console.log('WebSocket disconnected');
                this.trigger('onDisconnect');
                this.showConnectionStatus('disconnected');
                this.attemptReconnect(url);
            };
        } catch (error) {
            console.error('Failed to connect WebSocket:', error);
            this.useFallbackPolling(url);
        }
    }

    useFallbackPolling(apiUrl) {
        console.log('Using fallback polling method');

        const poll = () => {
            fetch(apiUrl.replace('ws:', 'http:').replace('wss:', 'https:'))
                .then(response => response.json())
                .then(data => {
                    if (data.notifications) {
                        data.notifications.forEach(notif => this.handleNotification(notif));
                    }
                })
                .catch(error => console.error('Polling error:', error));
        };

        setInterval(poll, 5000);
        poll();
    }

    attemptReconnect(url) {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Reconnecting... Attempt ${this.reconnectAttempts}`);

            setTimeout(() => {
                this.connect(url);
            }, this.reconnectDelay * this.reconnectAttempts);
        } else {
            console.log('Max reconnect attempts reached, using polling');
            this.useFallbackPolling(url);
        }
    }

    handleNotification(data) {
        const notification = {
            id: data.id || Date.now(),
            type: data.type || 'info',
            title: data.title || 'Notification',
            message: data.message || '',
            icon: data.icon || this.getDefaultIcon(data.type),
            timestamp: data.timestamp || new Date().toISOString(),
            priority: data.priority || 'normal',
            action: data.action || null
        };

        this.notifications.unshift(notification);

        this.trigger('onNotification', notification);

        this.showNotification(notification);

        if (notification.priority === 'high' || notification.priority === 'critical') {
            this.playSound(notification.type);
            this.showAlert(notification);
        }

        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notification.title, {
                body: notification.message,
                icon: notification.icon
            });
        }
    }

    showNotification(notification) {
        const container = this.getNotificationContainer();

        const notifElement = document.createElement('div');
        notifElement.className = `notification notification-${notification.type} notification-${notification.priority}`;
        notifElement.innerHTML = `
            <div class="notification-icon">${notification.icon}</div>
            <div class="notification-content">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${this.formatTime(notification.timestamp)}</div>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">✕</button>
        `;

        if (notification.action) {
            const actionBtn = document.createElement('button');
            actionBtn.className = 'notification-action';
            actionBtn.textContent = notification.action.label;
            actionBtn.onclick = () => {
                if (typeof notification.action.callback === 'function') {
                    notification.action.callback();
                }
                notifElement.remove();
            };
            notifElement.querySelector('.notification-content').appendChild(actionBtn);
        }

        container.appendChild(notifElement);

        setTimeout(() => notifElement.classList.add('show'), 100);

        if (notification.priority !== 'high' && notification.priority !== 'critical') {
            setTimeout(() => {
                notifElement.classList.remove('show');
                setTimeout(() => notifElement.remove(), 300);
            }, 5000);
        }
    }

    showAlert(notification) {
        const alert = document.createElement('div');
        alert.className = 'notification-alert notification-alert-' + notification.type;
        alert.innerHTML = `
            <div class="alert-content">
                <div class="alert-icon-big">${notification.icon}</div>
                <div class="alert-title">${notification.title}</div>
                <div class="alert-message">${notification.message}</div>
                <button class="alert-dismiss" onclick="this.parentElement.parentElement.remove()">
                    J'ai compris
                </button>
            </div>
        `;

        document.body.appendChild(alert);

        setTimeout(() => alert.classList.add('show'), 100);
    }

    getNotificationContainer() {
        let container = document.getElementById('notifications-container');

        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications-container';
            document.body.appendChild(container);

            const style = document.createElement('style');
            style.textContent = `
                #notifications-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                    max-width: 400px;
                }

                .notification {
                    background: rgba(0, 0, 0, 0.9);
                    border-radius: 12px;
                    padding: 20px;
                    display: flex;
                    gap: 15px;
                    align-items: flex-start;
                    border-left: 4px solid;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                    transform: translateX(500px);
                    opacity: 0;
                    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                }

                .notification.show {
                    transform: translateX(0);
                    opacity: 1;
                }

                .notification-info { border-color: #3b82f6; }
                .notification-success { border-color: #10b981; }
                .notification-warning { border-color: #f59e0b; }
                .notification-error { border-color: #ef4444; }

                .notification-critical {
                    animation: pulse 1s infinite;
                }

                @keyframes pulse {
                    0%, 100% { box-shadow: 0 10px 30px rgba(239, 68, 68, 0.5); }
                    50% { box-shadow: 0 10px 50px rgba(239, 68, 68, 0.8); }
                }

                .notification-icon {
                    font-size: 32px;
                    flex-shrink: 0;
                }

                .notification-content {
                    flex: 1;
                    color: white;
                }

                .notification-title {
                    font-size: 16px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }

                .notification-message {
                    font-size: 14px;
                    opacity: 0.9;
                    margin-bottom: 5px;
                }

                .notification-time {
                    font-size: 12px;
                    opacity: 0.6;
                }

                .notification-action {
                    margin-top: 10px;
                    padding: 8px 16px;
                    background: rgba(255, 255, 255, 0.2);
                    border: none;
                    border-radius: 6px;
                    color: white;
                    cursor: pointer;
                    font-size: 14px;
                    transition: background 0.2s;
                }

                .notification-action:hover {
                    background: rgba(255, 255, 255, 0.3);
                }

                .notification-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 20px;
                    cursor: pointer;
                    opacity: 0.6;
                    transition: opacity 0.2s;
                    flex-shrink: 0;
                }

                .notification-close:hover {
                    opacity: 1;
                }

                .notification-alert {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.95);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10001;
                    opacity: 0;
                    transition: opacity 0.3s;
                }

                .notification-alert.show {
                    opacity: 1;
                }

                .alert-content {
                    background: #1e293b;
                    border-radius: 24px;
                    padding: 60px;
                    text-align: center;
                    max-width: 500px;
                    color: white;
                    transform: scale(0.8);
                    transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                }

                .notification-alert.show .alert-content {
                    transform: scale(1);
                }

                .alert-icon-big {
                    font-size: 80px;
                    margin-bottom: 20px;
                }

                .alert-title {
                    font-size: 32px;
                    font-weight: bold;
                    margin-bottom: 15px;
                }

                .alert-message {
                    font-size: 18px;
                    opacity: 0.9;
                    margin-bottom: 30px;
                }

                .alert-dismiss {
                    padding: 15px 40px;
                    background: #3b82f6;
                    border: none;
                    border-radius: 12px;
                    color: white;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: background 0.2s;
                }

                .alert-dismiss:hover {
                    background: #2563eb;
                }

                .connection-status {
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    padding: 10px 20px;
                    border-radius: 20px;
                    font-size: 14px;
                    font-weight: bold;
                    z-index: 9999;
                    transition: all 0.3s;
                }

                .connection-status.connected {
                    background: #10b981;
                    color: white;
                }

                .connection-status.disconnected {
                    background: #ef4444;
                    color: white;
                }

                .connection-status.error {
                    background: #f59e0b;
                    color: white;
                }
            `;
            document.head.appendChild(style);
        }

        return container;
    }

    showConnectionStatus(status) {
        let statusEl = document.getElementById('connection-status');

        if (!statusEl) {
            statusEl = document.createElement('div');
            statusEl.id = 'connection-status';
            statusEl.className = 'connection-status';
            document.body.appendChild(statusEl);
        }

        const messages = {
            connected: '🟢 Connecté',
            disconnected: '🔴 Déconnecté',
            error: '⚠️ Erreur de connexion'
        };

        statusEl.className = 'connection-status ' + status;
        statusEl.textContent = messages[status];

        if (status === 'connected') {
            setTimeout(() => {
                statusEl.style.opacity = '0';
                setTimeout(() => statusEl.remove(), 300);
            }, 3000);
        }
    }

    getDefaultIcon(type) {
        const icons = {
            info: 'ℹ️',
            success: '✅',
            warning: '⚠️',
            error: '❌',
            message: '💬',
            update: '🔄'
        };
        return icons[type] || '📢';
    }

    playSound(type) {
        const sound = this.sounds[type] || this.sounds.info;
        sound.play().catch(error => console.log('Could not play sound:', error));
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'À l\'instant';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' min';
        if (diff < 86400000) return Math.floor(diff / 3600000) + ' h';
        return date.toLocaleDateString('fr-FR');
    }

    on(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    }

    trigger(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(callback => callback(data));
        }
    }

    send(message) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(message));
        }
    }

    requestPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
}

const mv3Notifications = new MV3RealtimeNotifications();
