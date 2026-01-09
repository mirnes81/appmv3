import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { apiClient } from '../lib/api';
import { LoadingSpinner } from '../components/LoadingSpinner';

interface Regie {
  id: number;
  ref: string;
  status: number;
  status_label: string;
  date_regie: string;
  project: {
    id: number;
    ref: string;
    title: string;
  };
  client: {
    id: number;
    name: string;
  } | null;
  location_text: string;
  total_ht: number;
  total_ttc: number;
}

export function Regie() {
  const [regies, setRegies] = useState<Regie[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [statusFilter, setStatusFilter] = useState<string>('');

  useEffect(() => {
    loadRegies();
  }, [statusFilter]);

  async function loadRegies() {
    try {
      setLoading(true);
      setError(null);

      const params = new URLSearchParams();
      if (statusFilter) {
        params.append('search_status', statusFilter);
      }

      const response = await apiClient<{ regies: Regie[] }>(
        `/api/v1/regie.php?${params.toString()}`
      );

      setRegies(response.regies);
    } catch (err: any) {
      setError(err.message || 'Erreur lors du chargement des régies');
    } finally {
      setLoading(false);
    }
  }

  const getStatusColor = (status: number) => {
    const colors: Record<number, string> = {
      0: '#fbbf24', // Brouillon - yellow
      1: '#3b82f6', // Validé - blue
      2: '#8b5cf6', // Envoyé - purple
      3: '#10b981', // Signé - green
      4: '#6b7280', // Facturé - gray
    };
    return colors[status] || '#9ca3af';
  };

  if (loading) return <LoadingSpinner />;

  return (
    <Layout title="Bons de régie">
      <div style={{ padding: '20px' }}>
        <div style={{ display: 'flex', gap: '12px', marginBottom: '20px' }}>
          <Link to="/regie/new" className="btn btn-primary btn-full">
            ➕ Nouvelle régie
          </Link>
        </div>

        <div style={{ marginBottom: '20px' }}>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            style={{
              width: '100%',
              padding: '12px',
              border: '1px solid #e5e7eb',
              borderRadius: '8px',
              fontSize: '16px',
            }}
          >
            <option value="">Tous les statuts</option>
            <option value="0">Brouillon</option>
            <option value="1">Validé</option>
            <option value="2">Envoyé</option>
            <option value="3">Signé</option>
            <option value="4">Facturé</option>
          </select>
        </div>

        {error && (
          <div className="alert alert-error" style={{ marginBottom: '16px' }}>
            {error}
          </div>
        )}

        {regies.length === 0 ? (
          <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>📋</div>
            <p style={{ color: '#6b7280' }}>Aucun bon de régie</p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {regies.map((regie) => (
              <Link
                key={regie.id}
                to={`/regie/${regie.id}`}
                className="card"
                style={{ textDecoration: 'none', color: 'inherit' }}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '12px' }}>
                  <div style={{ fontWeight: '700', fontSize: '16px' }}>
                    {regie.ref}
                  </div>
                  <div
                    style={{
                      padding: '4px 12px',
                      borderRadius: '12px',
                      fontSize: '12px',
                      fontWeight: '600',
                      backgroundColor: getStatusColor(regie.status) + '20',
                      color: getStatusColor(regie.status),
                    }}
                  >
                    {regie.status_label}
                  </div>
                </div>

                <div style={{ fontSize: '14px', color: '#6b7280', lineHeight: '1.6' }}>
                  <div><strong>Projet:</strong> {regie.project.ref}</div>
                  <div><strong>Date:</strong> {new Date(regie.date_regie).toLocaleDateString('fr-FR')}</div>
                  {regie.location_text && (
                    <div><strong>Lieu:</strong> {regie.location_text}</div>
                  )}
                </div>

                <div
                  style={{
                    marginTop: '12px',
                    paddingTop: '12px',
                    borderTop: '1px solid #e5e7eb',
                    textAlign: 'right',
                    fontWeight: '700',
                    fontSize: '18px',
                    color: '#10b981',
                  }}
                >
                  {regie.total_ttc.toFixed(2)} CHF
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
}
