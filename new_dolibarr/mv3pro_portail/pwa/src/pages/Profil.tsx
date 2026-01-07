import { useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { useAuth } from '../contexts/AuthContext';

export function Profil() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  return (
    <Layout title="Profil">
      <div style={{ padding: '20px' }}>
        <div className="card" style={{ textAlign: 'center', padding: '32px' }}>
          <div style={{ fontSize: '64px', marginBottom: '16px' }}>👤</div>
          <h2 style={{ fontSize: '22px', fontWeight: '700', marginBottom: '8px' }}>
            {user?.firstname} {user?.lastname}
          </h2>
          <div style={{ color: '#6b7280', marginBottom: '24px' }}>
            {user?.email}
          </div>

          <div
            style={{
              display: 'flex',
              flexDirection: 'column',
              gap: '12px',
              marginTop: '24px',
            }}
          >
            <div className="card" style={{ background: '#f9fafb', textAlign: 'left' }}>
              <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '4px' }}>
                ID Utilisateur
              </div>
              <div style={{ fontWeight: '600' }}>#{user?.id}</div>
            </div>

            {user?.dolibarr_user_id && (
              <div className="card" style={{ background: '#f9fafb', textAlign: 'left' }}>
                <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '4px' }}>
                  ID Dolibarr
                </div>
                <div style={{ fontWeight: '600' }}>#{user.dolibarr_user_id}</div>
              </div>
            )}
          </div>
        </div>

        <div className="card">
          <h3 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '16px' }}>
            Informations application
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px', fontSize: '14px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span style={{ color: '#6b7280' }}>Version</span>
              <span style={{ fontWeight: '600' }}>1.0.0</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span style={{ color: '#6b7280' }}>PWA installée</span>
              <span style={{ fontWeight: '600' }}>
                {window.matchMedia('(display-mode: standalone)').matches ? 'Oui' : 'Non'}
              </span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span style={{ color: '#6b7280' }}>Connexion</span>
              <span style={{ fontWeight: '600' }}>
                {navigator.onLine ? '🟢 En ligne' : '🔴 Hors ligne'}
              </span>
            </div>
          </div>
        </div>

        <button
          onClick={handleLogout}
          className="btn btn-error btn-full"
          style={{ marginTop: '24px' }}
        >
          🚪 Déconnexion
        </button>

        <div style={{ textAlign: 'center', marginTop: '24px', color: '#9ca3af', fontSize: '13px' }}>
          MV3 PRO Mobile - MV3 Carrelage
        </div>
      </div>
    </Layout>
  );
}
