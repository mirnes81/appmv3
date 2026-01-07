import { Layout } from '../components/Layout';

export function Notifications() {
  return (
    <Layout title="Notifications">
      <div style={{ padding: '20px' }}>
        <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
          <div style={{ fontSize: '48px', marginBottom: '16px' }}>🚧</div>
          <h3 style={{ fontSize: '18px', fontWeight: '600', marginBottom: '8px' }}>
            Module Notifications
          </h3>
          <p style={{ color: '#6b7280', marginBottom: '12px' }}>
            Endpoint API non disponible
          </p>
          <div className="alert alert-info" style={{ textAlign: 'left' }}>
            <div style={{ fontWeight: '600', marginBottom: '8px' }}>
              À implémenter côté serveur:
            </div>
            <ul style={{ paddingLeft: '20px', margin: 0 }}>
              <li>GET /api/v1/notifications.php (liste)</li>
              <li>PUT /api/v1/notifications/:id/read</li>
            </ul>
          </div>
        </div>
      </div>
    </Layout>
  );
}
