import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { apiClient } from '../lib/api';
import { LoadingSpinner } from '../components/LoadingSpinner';

interface RegieDetail {
  id: number;
  ref: string;
  status: number;
  status_label: string;
  date_regie: string;
  location_text: string;
  type_regie: string;
  total_ht: number;
  total_tva: number;
  total_ttc: number;
  note_public: string;
  note_private: string;
  project: {
    id: number;
    ref: string;
    title: string;
  } | null;
  client: {
    id: number;
    name: string;
  } | null;
  author: {
    id: number;
    name: string;
  };
}

interface RegieLine {
  id: number;
  line_type: string;
  description: string;
  qty: number;
  unit: string;
  price_unit: number;
  total_ht: number;
  total_ttc: number;
}

export function RegieDetail() {
  const { id } = useParams<{ id: string }>();
  const [regie, setRegie] = useState<RegieDetail | null>(null);
  const [lines, setLines] = useState<RegieLine[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadRegie();
  }, [id]);

  async function loadRegie() {
    try {
      setLoading(true);
      setError(null);

      const response = await apiClient<{
        regie: RegieDetail;
        lines: RegieLine[];
        photos: any[];
      }>(`/regie_view.php?id=${id}`);

      setRegie(response.regie);
      setLines(response.lines);
    } catch (err: any) {
      setError(err.message || 'Erreur lors du chargement de la régie');
    } finally {
      setLoading(false);
    }
  }

  const getStatusColor = (status: number) => {
    const colors: Record<number, string> = {
      0: '#fbbf24',
      1: '#3b82f6',
      2: '#8b5cf6',
      3: '#10b981',
      4: '#6b7280',
    };
    return colors[status] || '#9ca3af';
  };

  if (loading) return <LoadingSpinner />;

  if (error || !regie) {
    return (
      <Layout title="Erreur" showBack>
        <div style={{ padding: '20px' }}>
          <div className="alert alert-error">
            {error || 'Régie non trouvée'}
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout title={regie.ref} showBack>
      <div style={{ padding: '20px' }}>
        <div className="card" style={{ marginBottom: '16px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <h2 style={{ fontSize: '20px', fontWeight: '700', margin: 0 }}>
              {regie.ref}
            </h2>
            <div
              style={{
                padding: '6px 16px',
                borderRadius: '12px',
                fontSize: '14px',
                fontWeight: '600',
                backgroundColor: getStatusColor(regie.status) + '20',
                color: getStatusColor(regie.status),
              }}
            >
              {regie.status_label}
            </div>
          </div>

          {regie.project && (
            <div style={{ marginBottom: '12px', fontSize: '14px' }}>
              <strong>Projet:</strong> {regie.project.ref} - {regie.project.title}
            </div>
          )}

          <div style={{ fontSize: '14px', color: '#6b7280', lineHeight: '1.8' }}>
            <div><strong>Date:</strong> {new Date(regie.date_regie).toLocaleDateString('fr-FR')}</div>

            {regie.location_text && (
              <div><strong>Lieu:</strong> {regie.location_text}</div>
            )}

            {regie.type_regie && (
              <div><strong>Type:</strong> {regie.type_regie}</div>
            )}

            {regie.author && (
              <div><strong>Auteur:</strong> {regie.author.name}</div>
            )}

            {regie.client && (
              <div><strong>Client:</strong> {regie.client.name}</div>
            )}
          </div>

          {regie.note_public && (
            <div style={{ marginTop: '16px', padding: '12px', backgroundColor: '#f3f4f6', borderRadius: '8px' }}>
              <div style={{ fontWeight: '600', marginBottom: '4px', fontSize: '14px' }}>Note publique:</div>
              <div style={{ fontSize: '14px', color: '#4b5563', whiteSpace: 'pre-wrap' }}>
                {regie.note_public}
              </div>
            </div>
          )}

          <div
            style={{
              marginTop: '16px',
              paddingTop: '16px',
              borderTop: '1px solid #e5e7eb',
            }}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
              <span style={{ color: '#6b7280' }}>Total HT:</span>
              <span style={{ fontWeight: '600' }}>{regie.total_ht.toFixed(2)} CHF</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
              <span style={{ color: '#6b7280' }}>TVA:</span>
              <span style={{ fontWeight: '600' }}>{regie.total_tva.toFixed(2)} CHF</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', paddingTop: '8px', borderTop: '1px solid #e5e7eb' }}>
              <span style={{ fontWeight: '700' }}>Total TTC:</span>
              <span style={{ fontWeight: '700', fontSize: '18px', color: '#10b981' }}>
                {regie.total_ttc.toFixed(2)} CHF
              </span>
            </div>
          </div>
        </div>

        {lines.length > 0 && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <h3 style={{ fontSize: '16px', fontWeight: '700', marginBottom: '12px' }}>
              Lignes ({lines.length})
            </h3>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {lines.map((line) => (
                <div
                  key={line.id}
                  style={{
                    padding: '12px',
                    backgroundColor: '#f9fafb',
                    borderRadius: '8px',
                    borderLeft: '3px solid #3b82f6',
                  }}
                >
                  <div style={{ fontWeight: '600', marginBottom: '4px', fontSize: '14px' }}>
                    {line.description}
                  </div>
                  <div style={{ fontSize: '13px', color: '#6b7280' }}>
                    {line.qty} {line.unit} × {line.price_unit.toFixed(2)} CHF
                  </div>
                  <div style={{ fontSize: '14px', fontWeight: '600', color: '#10b981', marginTop: '4px' }}>
                    {line.total_ttc.toFixed(2)} CHF
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </Layout>
  );
}
