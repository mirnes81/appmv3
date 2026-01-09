import { useAuth } from '../contexts/AuthContext';
import { Layout } from '../components/Layout';

export function AccountUnlinked() {
  const { user, logout } = useAuth();

  const manageUsersUrl = `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`;

  return (
    <Layout title="Compte non lié">
      <div style={{ padding: '20px' }}>
        <div
          style={{
            background: '#fef3c7',
            border: '2px solid #f59e0b',
            borderRadius: '12px',
            padding: '24px',
            marginBottom: '24px',
          }}
        >
          <div style={{ fontSize: '48px', textAlign: 'center', marginBottom: '16px' }}>
            ⚠️
          </div>
          <h2
            style={{
              fontSize: '20px',
              fontWeight: '700',
              color: '#92400e',
              textAlign: 'center',
              marginBottom: '16px',
            }}
          >
            Compte non lié à Dolibarr
          </h2>
          <div
            style={{
              fontSize: '15px',
              color: '#78350f',
              lineHeight: '1.6',
              marginBottom: '24px',
            }}
          >
            <p style={{ marginBottom: '12px' }}>
              Bonjour <strong>{user?.firstname} {user?.lastname}</strong>,
            </p>
            <p style={{ marginBottom: '12px' }}>
              Votre compte mobile (<strong>{user?.email}</strong>) n'est actuellement pas lié à un
              utilisateur Dolibarr. Sans ce lien, vous ne pouvez pas utiliser les fonctionnalités
              de l'application mobile.
            </p>
            <p style={{ fontWeight: '600' }}>
              Que faire ?
            </p>
            <ul style={{ paddingLeft: '20px', marginTop: '8px' }}>
              <li>Contactez votre administrateur</li>
              <li>
                Demandez-lui de lier votre compte mobile à votre utilisateur Dolibarr dans la page de
                gestion des utilisateurs
              </li>
            </ul>
          </div>
        </div>

        <div className="card" style={{ marginBottom: '16px' }}>
          <h3 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '12px' }}>
            Pour les administrateurs
          </h3>
          <p style={{ fontSize: '14px', color: '#6b7280', marginBottom: '16px' }}>
            Si vous êtes administrateur, ouvrez la page de gestion des utilisateurs et liez ce
            compte mobile à un utilisateur Dolibarr existant.
          </p>
          <a
            href={manageUsersUrl}
            target="_blank"
            rel="noopener noreferrer"
            style={{
              display: 'inline-block',
              padding: '10px 20px',
              background: '#0891b2',
              color: 'white',
              borderRadius: '8px',
              textDecoration: 'none',
              fontSize: '14px',
              fontWeight: '600',
            }}
          >
            Ouvrir la gestion des utilisateurs
          </a>
        </div>

        <div style={{ textAlign: 'center', marginTop: '32px' }}>
          <button
            onClick={() => logout()}
            style={{
              padding: '12px 24px',
              background: '#ef4444',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: '600',
              cursor: 'pointer',
            }}
          >
            Se déconnecter
          </button>
        </div>
      </div>
    </Layout>
  );
}
