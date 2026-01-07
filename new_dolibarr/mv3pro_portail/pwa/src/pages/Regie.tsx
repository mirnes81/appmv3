import { Link } from 'react-router-dom';
import { Layout } from '../components/Layout';

export function Regie() {
  return (
    <Layout title="Régie">
      <div style={{ padding: '20px' }}>
        <div style={{ display: 'flex', gap: '12px', marginBottom: '20px' }}>
          <Link to="/regie/new" className="btn btn-primary btn-full">
            ➕ Nouvelle régie
          </Link>
        </div>

        <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
          <div style={{ fontSize: '48px', marginBottom: '16px' }}>🚧</div>
          <h3 style={{ fontSize: '18px', fontWeight: '600', marginBottom: '8px' }}>
            Module Régie
          </h3>
          <p style={{ color: '#6b7280', marginBottom: '12px' }}>
            Endpoint API non disponible
          </p>
          <div className="alert alert-info" style={{ textAlign: 'left' }}>
            <div style={{ fontWeight: '600', marginBottom: '8px' }}>
              À implémenter côté serveur:
            </div>
            <ul style={{ paddingLeft: '20px', margin: 0 }}>
              <li>GET /api/v1/regie.php (liste)</li>
              <li>POST /api/v1/regie_create.php</li>
              <li>GET /api/v1/regie/:id</li>
            </ul>
          </div>
        </div>
      </div>
    </Layout>
  );
}
