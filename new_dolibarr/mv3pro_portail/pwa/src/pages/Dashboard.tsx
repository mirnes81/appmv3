import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { useAuth } from '../contexts/AuthContext';
import { api } from '../lib/api';

export function Dashboard() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);

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

  const quickActions = [
    { icon: '📋', label: 'Nouveau rapport', path: '/rapports/new', color: '#0891b2' },
    { icon: '📝', label: 'Nouvelle régie', path: '/regie/new', color: '#10b981' },
    { icon: '🔷', label: 'Sens de pose', path: '/sens-pose/new', color: '#3b82f6' },
    { icon: '📅', label: 'Planning', path: '/planning', color: '#f59e0b' },
  ];

  return (
    <Layout title="Accueil">
      <div style={{ padding: '20px' }}>
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
