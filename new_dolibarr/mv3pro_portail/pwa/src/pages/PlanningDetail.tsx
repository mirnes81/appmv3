import { useParams } from 'react-router-dom';
import { Layout } from '../components/Layout';

export function PlanningDetail() {
  const { id } = useParams();

  return (
    <Layout title="Détail évènement" showBack>
      <div style={{ padding: '20px' }}>
        <div className="card">
          <div style={{ textAlign: 'center', padding: '20px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>🚧</div>
            <h3 style={{ fontSize: '18px', fontWeight: '600', marginBottom: '8px' }}>
              Détail évènement #{id}
            </h3>
            <p style={{ color: '#6b7280' }}>
              Endpoint API non disponible
            </p>
            <p style={{ color: '#6b7280', fontSize: '14px', marginTop: '8px' }}>
              À implémenter: GET /api/v1/planning/:id
            </p>
          </div>
        </div>
      </div>
    </Layout>
  );
}
