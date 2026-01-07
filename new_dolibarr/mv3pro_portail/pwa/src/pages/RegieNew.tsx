import { Layout } from '../components/Layout';

export function RegieNew() {
  return (
    <Layout title="Nouvelle régie" showBack>
      <div style={{ padding: '20px' }}>
        <div className="alert alert-info" style={{ marginBottom: '20px' }}>
          ℹ️ Formulaire régie avec signature
        </div>

        <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
          <div style={{ fontSize: '48px', marginBottom: '16px' }}>🚧</div>
          <h3 style={{ fontSize: '18px', fontWeight: '600', marginBottom: '8px' }}>
            Création régie
          </h3>
          <p style={{ color: '#6b7280' }}>
            En attente endpoint API
          </p>
        </div>
      </div>
    </Layout>
  );
}
