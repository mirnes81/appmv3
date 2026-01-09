import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
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
      api.rapportsList(10).catch(() => []),
      api.planning(new Date().toISOString().split('T')[0]).catch(() => []),
    ])
      .then(([rapports, events]) => {
        setStats({
          rapportsToday: rapports.filter((r: any) =>
            r.date_rapport === new Date().toISOString().split('T')[0]
          ).length,
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
    { icon: '📋', label: 'Nouveau rapport', path: '/rapports/new', color: '#0891b2' },
    { icon: '📝', label: 'Nouvelle régie', path: '/regie/new', color: '#10b981' },
    { icon: '🔷', label: 'Sens de pose', path: '/sens-pose/new', color: '#3b82f6' },
    { icon: '📅', label: 'Planning', path: '/planning', color: '#f59e0b' },
  ];

  return (
    <Layout title="Accueil">
      <div style={{ padding: '20px' }}>
        {isDebugMode && debugInfo && (
          <div
            style={{
              background: '#1f2937',
              color: '#f3f4f6',
              borderRadius: '8px',
              padding: '16px',
              marginBottom: '20px',
              fontSize: '12px',
              fontFamily: 'monospace',
              maxHeight: '300px',
              overflow: 'auto',
            }}
          >
            <div style={{ fontWeight: 'bold', marginBottom: '12px', color: '#fbbf24' }}>
              🐛 DEBUG MODE ACTIF
            </div>
            <div style={{ display: 'grid', gap: '8px' }}>
              <div>
                <strong>Token présent:</strong> {debugInfo.token_present ? '✅ YES' : '❌ NO'}
              </div>
              <div>
                <strong>Token masqué:</strong> {debugInfo.token_masked}
              </div>
              <div>
                <strong>Route actuelle:</strong> {debugInfo.current_route}
              </div>
              <div>
                <strong>User ID:</strong> {debugInfo.user_id}
              </div>
              <div>
                <strong>User Email:</strong> {debugInfo.user_email}
              </div>
              <div style={{ borderTop: '1px solid #4b5563', paddingTop: '8px', marginTop: '8px' }}>
                <strong>Test /me.php:</strong>
              </div>
              <div>
                <strong>Status:</strong>{' '}
                <span style={{ color: debugInfo.me_ok ? '#10b981' : '#ef4444' }}>
                  {debugInfo.me_status || 'N/A'}
                </span>
              </div>
              <div>
                <strong>Success:</strong> {debugInfo.me_success ? '✅' : '❌'}
              </div>
              {debugInfo.me_error && (
                <div style={{ color: '#ef4444' }}>
                  <strong>Erreur:</strong> {debugInfo.me_error}
                </div>
              )}
              {debugInfo.me_response && (
                <div>
                  <strong>Response:</strong>
                  <pre style={{ margin: '4px 0', fontSize: '11px', whiteSpace: 'pre-wrap' }}>
                    {JSON.stringify(debugInfo.me_response, null, 2)}
                  </pre>
                </div>
              )}
            </div>
          </div>
        )}
        <div
          style={{
            background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
            borderRadius: '16px',
            padding: '24px',
            color: 'white',
            marginBottom: '24px',
            boxShadow: '0 4px 12px rgba(8, 145, 178, 0.3)',
          }}
        >
          <div style={{ fontSize: '40px', marginBottom: '12px' }}>👋</div>
          <h2 style={{ fontSize: '22px', fontWeight: '700', marginBottom: '8px' }}>
            Bonjour {user?.firstname || 'Utilisateur'} !
          </h2>
          <p style={{ opacity: 0.9, fontSize: '14px' }}>
            {new Date().toLocaleDateString('fr-FR', {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric',
            })}
          </p>
        </div>

        {loading ? (
          <div className="card">
            <LoadingSpinner />
          </div>
        ) : (
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(2, 1fr)',
              gap: '16px',
              marginBottom: '24px',
            }}
          >
            <div className="card" style={{ textAlign: 'center' }}>
              <div style={{ fontSize: '32px', marginBottom: '8px' }}>📊</div>
              <div style={{ fontSize: '28px', fontWeight: '700', color: '#0891b2' }}>
                {stats?.rapportsToday || 0}
              </div>
              <div style={{ fontSize: '14px', color: '#6b7280' }}>
                Rapports aujourd'hui
              </div>
            </div>

            <div className="card" style={{ textAlign: 'center' }}>
              <div style={{ fontSize: '32px', marginBottom: '8px' }}>📅</div>
              <div style={{ fontSize: '28px', fontWeight: '700', color: '#10b981' }}>
                {stats?.eventsToday || 0}
              </div>
              <div style={{ fontSize: '14px', color: '#6b7280' }}>
                Affectations du jour
              </div>
            </div>
          </div>
        )}

        <div style={{ marginBottom: '24px' }}>
          <h3 style={{ fontSize: '18px', fontWeight: '600', marginBottom: '16px' }}>
            Actions rapides
          </h3>
          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(2, 1fr)',
              gap: '12px',
            }}
          >
            {quickActions.map((action) => (
              <Link
                key={action.path}
                to={action.path}
                style={{
                  background: 'white',
                  borderRadius: '12px',
                  padding: '20px',
                  textAlign: 'center',
                  boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                  transition: 'all 200ms ease',
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: '8px',
                  textDecoration: 'none',
                  color: '#1f2937',
                }}
              >
                <div style={{ fontSize: '36px' }}>{action.icon}</div>
                <div style={{ fontSize: '14px', fontWeight: '600' }}>
                  {action.label}
                </div>
              </Link>
            ))}
          </div>
        </div>

        {stats?.nextEvent && (
          <div className="card">
            <h3 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '12px' }}>
              📍 Prochain évènement
            </h3>
            <div style={{ fontSize: '15px', color: '#374151', marginBottom: '4px' }}>
              {stats.nextEvent.label}
            </div>
            {stats.nextEvent.client_nom && (
              <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '4px' }}>
                Client: {stats.nextEvent.client_nom}
              </div>
            )}
            {stats.nextEvent.location && (
              <div style={{ fontSize: '14px', color: '#6b7280' }}>
                📍 {stats.nextEvent.location}
              </div>
            )}
          </div>
        )}
      </div>
    </Layout>
  );
}
