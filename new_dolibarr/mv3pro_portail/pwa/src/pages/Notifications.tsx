import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { api } from '../lib/api';

interface Notification {
  id: number;
  type: string;
  titre: string;
  message: string;
  date: string;
  date_lecture: string | null;
  is_read: number;
  statut: string;
  object_id: number | null;
  object_type: string | null;
  url: string | null;
  icon: string;
  color: string;
}

interface NotificationsResponse {
  notifications: Notification[];
  count: number;
  total_unread: number;
}

export function Notifications() {
  const navigate = useNavigate();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [totalUnread, setTotalUnread] = useState(0);
  const [filter, setFilter] = useState<'all' | 'unread'>('all');

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      setError(null);

      const params: Record<string, string> = { limit: '100' };
      if (filter === 'unread') {
        params.status = 'non_lu';
      }

      const data = await api.get<NotificationsResponse>('/notifications.php', params);
      setNotifications(data.notifications);
      setTotalUnread(data.total_unread);
    } catch (err: any) {
      console.error('Error fetching notifications:', err);
      setError(err.message || 'Erreur lors du chargement des notifications');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchNotifications();
  }, [filter]);

  const markAsRead = async (id: number) => {
    try {
      await api.put(`/notifications_read.php?id=${id}`, {});

      // Mettre à jour localement
      setNotifications(prev => prev.map(notif =>
        notif.id === id ? { ...notif, is_read: 1, statut: 'lu' } : notif
      ));
      setTotalUnread(prev => Math.max(0, prev - 1));
    } catch (err: any) {
      console.error('Error marking notification as read:', err);
    }
  };

  const markAllAsRead = async () => {
    try {
      await api.put('/notifications_read.php?all=1', {});

      // Mettre à jour localement
      setNotifications(prev => prev.map(notif => ({
        ...notif,
        is_read: 1,
        statut: 'lu'
      })));
      setTotalUnread(0);
    } catch (err: any) {
      console.error('Error marking all as read:', err);
      alert('Erreur lors du marquage des notifications');
    }
  };

  const handleNotificationClick = (notification: Notification) => {
    // Marquer comme lu si non lu
    if (notification.is_read === 0) {
      markAsRead(notification.id);
    }

    // Naviguer vers l'URL si disponible
    if (notification.url) {
      navigate(notification.url);
    }
  };

  const getIconEmoji = (icon: string) => {
    const iconMap: Record<string, string> = {
      'file-text': '📄',
      'check-circle': '✅',
      'x-circle': '❌',
      'alert-triangle': '⚠️',
      'alert-circle': '🔴',
      'calendar': '📅',
      'x': '❌',
      'message-circle': '💬',
      'info': 'ℹ️',
      'bell': '🔔'
    };
    return iconMap[icon] || '🔔';
  };

  const getColorStyle = (color: string) => {
    const colorMap: Record<string, string> = {
      'blue': '#3b82f6',
      'green': '#10b981',
      'red': '#ef4444',
      'orange': '#f97316',
      'gray': '#6b7280'
    };
    return colorMap[color] || '#6b7280';
  };

  const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'À l\'instant';
    if (diffMins < 60) return `Il y a ${diffMins} min`;
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    if (diffDays < 7) return `Il y a ${diffDays}j`;

    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  };

  if (loading && notifications.length === 0) {
    return (
      <Layout title="Notifications">
        <div style={{ padding: '20px', textAlign: 'center' }}>
          <div style={{ fontSize: '24px' }}>⏳</div>
          <p>Chargement des notifications...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout title="Notifications">
      <div style={{ padding: '0' }}>
        {/* Header avec filtres */}
        <div style={{
          padding: '16px',
          backgroundColor: 'white',
          borderBottom: '1px solid #e5e7eb',
          position: 'sticky',
          top: 0,
          zIndex: 10
        }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
            <h2 style={{ fontSize: '20px', fontWeight: '600', margin: 0 }}>
              Notifications
              {totalUnread > 0 && (
                <span style={{
                  marginLeft: '8px',
                  padding: '2px 8px',
                  backgroundColor: '#ef4444',
                  color: 'white',
                  borderRadius: '12px',
                  fontSize: '14px',
                  fontWeight: '600'
                }}>
                  {totalUnread}
                </span>
              )}
            </h2>
            {totalUnread > 0 && (
              <button
                onClick={markAllAsRead}
                style={{
                  padding: '6px 12px',
                  fontSize: '14px',
                  color: '#3b82f6',
                  backgroundColor: 'transparent',
                  border: '1px solid #3b82f6',
                  borderRadius: '6px',
                  cursor: 'pointer'
                }}
              >
                Tout marquer lu
              </button>
            )}
          </div>

          {/* Filtres */}
          <div style={{ display: 'flex', gap: '8px' }}>
            <button
              onClick={() => setFilter('all')}
              style={{
                padding: '8px 16px',
                fontSize: '14px',
                fontWeight: '500',
                color: filter === 'all' ? 'white' : '#6b7280',
                backgroundColor: filter === 'all' ? '#3b82f6' : 'transparent',
                border: filter === 'all' ? 'none' : '1px solid #d1d5db',
                borderRadius: '6px',
                cursor: 'pointer'
              }}
            >
              Toutes ({notifications.length})
            </button>
            <button
              onClick={() => setFilter('unread')}
              style={{
                padding: '8px 16px',
                fontSize: '14px',
                fontWeight: '500',
                color: filter === 'unread' ? 'white' : '#6b7280',
                backgroundColor: filter === 'unread' ? '#3b82f6' : 'transparent',
                border: filter === 'unread' ? 'none' : '1px solid #d1d5db',
                borderRadius: '6px',
                cursor: 'pointer'
              }}
            >
              Non lues ({totalUnread})
            </button>
          </div>
        </div>

        {/* Liste des notifications */}
        {error && (
          <div style={{ padding: '16px' }}>
            <div className="alert alert-error">
              {error}
            </div>
          </div>
        )}

        {!error && notifications.length === 0 && (
          <div style={{ padding: '40px', textAlign: 'center' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>📭</div>
            <p style={{ color: '#6b7280' }}>
              {filter === 'unread' ? 'Aucune notification non lue' : 'Aucune notification'}
            </p>
          </div>
        )}

        {!error && notifications.length > 0 && (
          <div>
            {notifications.map((notification) => (
              <div
                key={notification.id}
                onClick={() => handleNotificationClick(notification)}
                style={{
                  padding: '16px',
                  backgroundColor: notification.is_read === 0 ? '#eff6ff' : 'white',
                  borderBottom: '1px solid #e5e7eb',
                  cursor: notification.url ? 'pointer' : 'default',
                  transition: 'background-color 0.2s'
                }}
                onMouseEnter={(e) => {
                  if (notification.url) {
                    e.currentTarget.style.backgroundColor = notification.is_read === 0 ? '#dbeafe' : '#f9fafb';
                  }
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.backgroundColor = notification.is_read === 0 ? '#eff6ff' : 'white';
                }}
              >
                <div style={{ display: 'flex', gap: '12px' }}>
                  {/* Icône */}
                  <div style={{
                    fontSize: '24px',
                    width: '40px',
                    height: '40px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: notification.is_read === 0 ? 'white' : '#f3f4f6',
                    borderRadius: '50%',
                    flexShrink: 0
                  }}>
                    {getIconEmoji(notification.icon)}
                  </div>

                  {/* Contenu */}
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'flex-start',
                      marginBottom: '4px'
                    }}>
                      <h3 style={{
                        fontSize: '15px',
                        fontWeight: notification.is_read === 0 ? '600' : '500',
                        margin: 0,
                        color: '#111827'
                      }}>
                        {notification.titre}
                      </h3>
                      <span style={{
                        fontSize: '12px',
                        color: '#9ca3af',
                        whiteSpace: 'nowrap',
                        marginLeft: '8px'
                      }}>
                        {formatDate(notification.date)}
                      </span>
                    </div>

                    <p style={{
                      fontSize: '14px',
                      color: '#6b7280',
                      margin: '4px 0 0 0',
                      overflow: 'hidden',
                      textOverflow: 'ellipsis',
                      display: '-webkit-box',
                      WebkitLineClamp: 2,
                      WebkitBoxOrient: 'vertical'
                    }}>
                      {notification.message}
                    </p>

                    {/* Badge de statut */}
                    <div style={{ marginTop: '8px', display: 'flex', gap: '8px', alignItems: 'center' }}>
                      <span style={{
                        fontSize: '11px',
                        padding: '2px 8px',
                        backgroundColor: getColorStyle(notification.color) + '20',
                        color: getColorStyle(notification.color),
                        borderRadius: '4px',
                        fontWeight: '500'
                      }}>
                        {notification.type.replace(/_/g, ' ')}
                      </span>
                      {notification.is_read === 0 && (
                        <span style={{
                          width: '8px',
                          height: '8px',
                          backgroundColor: '#3b82f6',
                          borderRadius: '50%'
                        }} />
                      )}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
}
