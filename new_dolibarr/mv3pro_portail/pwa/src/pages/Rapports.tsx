import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { api, Rapport } from '../lib/api';

export function Rapports() {
  const [rapports, setRapports] = useState<Rapport[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    api
      .rapportsList()
      .then(setRapports)
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, []);

  return (
    <Layout title="Rapports">
      <div style={{ padding: '20px' }}>
        <div style={{ display: 'flex', gap: '12px', marginBottom: '20px' }}>
          <Link to="/rapports/new" className="btn btn-primary" style={{ flex: 1 }}>
            ➕ Rapport simple
          </Link>
          <Link to="/rapports/new-pro" className="btn btn-success" style={{ flex: 1 }}>
            ⭐ Rapport PRO
          </Link>
        </div>

        {loading && <LoadingSpinner />}

        {error && <div className="alert alert-error">{error}</div>}

        {!loading && !error && rapports.length === 0 && (
          <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>📋</div>
            <div style={{ color: '#6b7280', fontSize: '16px' }}>
              Aucun rapport enregistré
            </div>
          </div>
        )}

        {!loading && rapports.length > 0 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {rapports.map((rapport) => (
              <Link
                key={rapport.rowid}
                to={`/rapports/${rapport.rowid}`}
                className="card"
                style={{ textDecoration: 'none', color: 'inherit' }}
              >
                <div style={{ display: 'flex', alignItems: 'start', gap: '12px' }}>
                  <div
                    style={{
                      width: '48px',
                      height: '48px',
                      borderRadius: '12px',
                      background: '#dbeafe',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      fontSize: '24px',
                      flexShrink: 0,
                    }}
                  >
                    📋
                  </div>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontWeight: '600', marginBottom: '4px' }}>
                      {rapport.projet_nom || `Rapport #${rapport.rowid}`}
                    </div>
                    <div style={{ fontSize: '14px', color: '#6b7280' }}>
                      {new Date(rapport.date_rapport).toLocaleDateString('fr-FR')}
                    </div>
                    {rapport.statut && (
                      <span className={`badge badge-${rapport.statut === 'valide' ? 'success' : 'warning'}`} style={{ marginTop: '8px' }}>
                        {rapport.statut}
                      </span>
                    )}
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
}
