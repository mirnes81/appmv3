import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { WeatherWidget } from '../components/WeatherWidget';
import { useAuth } from '../contexts/AuthContext';
import { api, storage } from '../lib/api';

export function Dashboard() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [debugInfo, setDebugInfo] = useState<any>(null);

  const isDebugMode = localStorage.getItem('mv3_debug') === '1';

  useEffect(() => {
    if (user?.is_unlinked) {
      navigate('/account-unlinked', { replace: true });
      return;
    }
  }, [user, navigate]);

  useEffect(() => {
    Promise.all([
      api.rapportsList({ limit: 10 }).catch(() => ({ data: { items: [] } })),
      api.planning(new Date().toISOString().split('T')[0]).catch(() => []),
    ])
      .then(([rapportsResp, events]) => {
        const today = new Date().toISOString().split('T')[0];
        const rapports = rapportsResp.data?.items || [];
        setStats({
          rapportsToday: rapports.filter((r: any) => r.date_rapport === today).length,
          eventsToday: events.length,
          nextEvent: events[0] || null,
        });
      })
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!isDebugMode) return;

    const token = storage.getToken();
    const debugData: any = {
      token_present: !!token,
      token_masked: token ? `${token.substring(0, 10)}...${token.substring(token.length - 10)}` : 'none',
      current_route: window.location.href,
      user_id: user?.id || 'null',
      user_email: user?.email || 'null',
      timestamp: new Date().toISOString(),
    };

    fetch('/custom/mv3pro_portail/api/v1/me.php', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Auth-Token': token || '',
      },
    })
      .then(async (response) => {
        debugData.me_status = response.status;
        debugData.me_ok = response.ok;
        try {
          const data = await response.json();
          debugData.me_response = data;
          debugData.me_success = data.success;
        } catch (e) {
          debugData.me_error = 'Failed to parse JSON';
        }
      })
      .catch((error) => {
        debugData.me_error = error.message;
      })
      .finally(() => {
        setDebugInfo(debugData);
      });
  }, [isDebugMode, user]);

  const quickActions = [
    { icon: '📋', label: 'Rapports', path: '/rapports', color: '#0891b2' },
    { icon: '📅', label: 'Planning', path: '/planning', color: '#f59e0b' },
    { icon: '🔔', label: 'Notifications', path: '/notifications', color: '#ef4444' },
    { icon: '📸', label: 'Photo', path: '/rapports/new', color: '#8b5cf6' },
    { icon: '🔷', label: 'Sens pose', path: '/sens-pose', color: '#3b82f6' },
    { icon: '⚙️', label: 'Matériel', path: '/materiel', color: '#10b981' },
  ];

  return (
    <Layout title="Accueil">
      <div style={{ padding: '16px' }}>
        {isDebugMode && debugInfo && (
          <div
            style={{
              background: '#1f2937',
              color: '#f3f4f6',
              borderRadius: '8px',
              padding: '12px',
              marginBottom: '16px',
              fontSize: '11px',
              fontFamily: 'monospace',
              maxHeight: '200px',
              overflow: 'auto',
            }}
          >
            <div style={{ fontWeight: 'bold', marginBottom: '8px', color: '#fbbf24' }}>
              DEBUG MODE
            </div>
            <div style={{ display: 'grid', gap: '4px' }}>
              <div>Token: {debugInfo.token_present ? '✅' : '❌'}</div>
              <div>User: {debugInfo.user_email}</div>
              <div>Status: {debugInfo.me_status}</div>
            </div>
          </div>
        )}

        <div
          style={{
            background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
            borderRadius: '12px',
            padding: '16px',
            color: 'white',
            marginBottom: '16px',
            boxShadow: '0 2px 8px rgba(8, 145, 178, 0.2)',
          }}
        >
          <div style={{ fontSize: '16px', fontWeight: '600', marginBottom: '4px' }}>
            Bonjour {user?.firstname || 'Utilisateur'}
          </div>
          <div style={{ opacity: 0.9, fontSize: '13px' }}>
            {new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })}
          </div>
        </div>

        <WeatherWidget />

        {loading ? (
          <div className="card">
            <LoadingSpinner />
          </div>
        ) : (
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(2, 1fr)',
              gap: '12px',
              marginBottom: '16px',
            }}
          >
            <div
              className="card"
              style={{
                textAlign: 'center',
                padding: '16px',
                background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
                color: 'white',
              }}
            >
              <div style={{ fontSize: '24px', marginBottom: '4px' }}>📊</div>
              <div style={{ fontSize: '24px', fontWeight: '700' }}>{stats?.rapportsToday || 0}</div>
              <div style={{ fontSize: '12px', opacity: 0.9 }}>Rapports</div>
            </div>

            <div
              className="card"
              style={{
                textAlign: 'center',
                padding: '16px',
                background: 'linear-gradient(135deg, #10b981 0%, #34d399 100%)',
                color: 'white',
              }}
            >
              <div style={{ fontSize: '24px', marginBottom: '4px' }}>📅</div>
              <div style={{ fontSize: '24px', fontWeight: '700' }}>{stats?.eventsToday || 0}</div>
              <div style={{ fontSize: '12px', opacity: 0.9 }}>Planning</div>
            </div>
          </div>
        )}

        <div style={{ marginBottom: '16px' }}>
          <h3 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '12px', color: '#1f2937' }}>
            Actions rapides
          </h3>
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(3, 1fr)',
              gap: '10px',
            }}
          >
            {quickActions.map((action) => (
              <Link
                key={action.path}
                to={action.path}
                style={{
                  background: 'white',
                  borderRadius: '10px',
                  padding: '14px 8px',
                  textAlign: 'center',
                  boxShadow: '0 2px 6px rgba(0,0,0,0.08)',
                  transition: 'transform 150ms ease, box-shadow 150ms ease',
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: '6px',
                  textDecoration: 'none',
                  color: '#1f2937',
                  border: '1px solid #f3f4f6',
                }}
                onTouchStart={(e) => {
                  e.currentTarget.style.transform = 'scale(0.95)';
                }}
                onTouchEnd={(e) => {
                  e.currentTarget.style.transform = 'scale(1)';
                }}
              >
                <div style={{ fontSize: '28px' }}>{action.icon}</div>
                <div style={{ fontSize: '12px', fontWeight: '600', lineHeight: '1.2' }}>
                  {action.label}
                </div>
              </Link>
            ))}
          </div>
        </div>

        {stats?.nextEvent && (
          <div className="card" style={{ padding: '14px' }}>
            <h3 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '10px', color: '#1f2937' }}>
              📍 Prochain chantier
            </h3>
            <div style={{ fontSize: '14px', color: '#374151', marginBottom: '4px', fontWeight: '500' }}>
              {stats.nextEvent.label}
            </div>
            {stats.nextEvent.client_nom && (
              <div style={{ fontSize: '13px', color: '#6b7280', marginBottom: '3px' }}>
                {stats.nextEvent.client_nom}
              </div>
            )}
            {stats.nextEvent.location && (
              <div style={{ fontSize: '13px', color: '#6b7280' }}>📍 {stats.nextEvent.location}</div>
            )}
          </div>
        )}
      </div>
    </Layout>
  );
}
