import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { api, RapportDetail as RapportDetailType } from '../lib/api';

export function RapportDetail() {
  const { id } = useParams();
  const [rapport, setRapport] = useState<RapportDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selectedPhoto, setSelectedPhoto] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;
    api
      .rapportsView(parseInt(id))
      .then(setRapport)
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [id]);

  if (loading) {
    return (
      <Layout title="Détail rapport" showBack>
        <div style={{ padding: '20px' }}>
          <LoadingSpinner />
        </div>
      </Layout>
    );
  }

  if (error || !rapport) {
    return (
      <Layout title="Détail rapport" showBack>
        <div style={{ padding: '20px' }}>
          <div className="alert alert-error">{error || 'Rapport introuvable'}</div>
        </div>
      </Layout>
    );
  }

  const { rapport: r, photos, pdf_url } = rapport.data;

  return (
    <Layout title={r.ref} showBack>
      <div style={{ padding: '20px' }}>
        <div className="card" style={{ marginBottom: '16px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '16px' }}>
            <div>
              <h2 style={{ fontSize: '20px', fontWeight: '700', marginBottom: '4px' }}>
                {r.ref}
              </h2>
              {r.client?.nom && (
                <div style={{ fontSize: '14px', color: '#6b7280' }}>🏢 {r.client.nom}</div>
              )}
              {r.projet?.title && (
                <div style={{ fontSize: '14px', color: '#9ca3af' }}>📁 {r.projet.ref} - {r.projet.title}</div>
              )}
            </div>
            <span
              className={`badge badge-${
                r.statut_text === 'valide' ? 'success' : r.statut_text === 'soumis' ? 'info' : 'warning'
              }`}
            >
              {r.statut_text}
            </span>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', fontSize: '14px' }}>
            <div>
              <div style={{ color: '#6b7280', marginBottom: '2px' }}>Date</div>
              <div style={{ fontWeight: '500' }}>
                📅 {new Date(r.date_rapport).toLocaleDateString('fr-FR')}
              </div>
            </div>
            {r.temps_total && r.temps_total > 0 && (
              <div>
                <div style={{ color: '#6b7280', marginBottom: '2px' }}>Temps total</div>
                <div style={{ fontWeight: '500' }}>
                  ⏱️ {r.temps_total}h
                </div>
              </div>
            )}
            {r.auteur && (
              <div>
                <div style={{ color: '#6b7280', marginBottom: '2px' }}>Auteur</div>
                <div style={{ fontWeight: '500' }}>👤 {r.auteur.nom}</div>
              </div>
            )}
          </div>
        </div>

        {r.travaux_realises && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '600' }}>
              TRAVAUX RÉALISÉS
            </div>
            <div style={{ fontSize: '14px', whiteSpace: 'pre-line' }}>{r.travaux_realises}</div>
          </div>
        )}

        {r.description && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '600' }}>
              DESCRIPTION
            </div>
            <div style={{ fontSize: '14px', whiteSpace: 'pre-line' }}>{r.description}</div>
          </div>
        )}

        {r.observations && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '600' }}>
              OBSERVATIONS
            </div>
            <div style={{ fontSize: '14px', whiteSpace: 'pre-line' }}>{r.observations}</div>
          </div>
        )}

        {r.gps && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '600' }}>
              LOCALISATION GPS
            </div>
            <div style={{ fontSize: '14px' }}>
              📍 {r.gps.latitude.toFixed(6)}, {r.gps.longitude.toFixed(6)}
              {r.gps.precision && <span style={{ color: '#6b7280' }}> (±{r.gps.precision}m)</span>}
            </div>
          </div>
        )}

        {r.meteo && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '600' }}>
              MÉTÉO
            </div>
            <div style={{ fontSize: '14px' }}>
              🌡️ {r.meteo.temperature}°C · {r.meteo.condition}
            </div>
          </div>
        )}

        {photos && photos.length > 0 && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div
              style={{
                fontSize: '12px',
                color: '#6b7280',
                marginBottom: '12px',
                fontWeight: '600',
                display: 'flex',
                justifyContent: 'space-between',
                alignItems: 'center',
              }}
            >
              <span>PHOTOS ({photos.length})</span>
            </div>
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))',
                gap: '12px',
              }}
            >
              {photos.map((photo) => (
                <div
                  key={photo.id}
                  onClick={() => setSelectedPhoto(photo.url)}
                  style={{
                    position: 'relative',
                    aspectRatio: '1',
                    borderRadius: '8px',
                    overflow: 'hidden',
                    cursor: 'pointer',
                    border: '1px solid #e5e7eb',
                  }}
                >
                  <img
                    src={photo.url}
                    alt={photo.filename}
                    style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                  />
                  {photo.categorie_label && (
                    <div
                      style={{
                        position: 'absolute',
                        top: '4px',
                        right: '4px',
                        background: 'rgba(0,0,0,0.7)',
                        color: 'white',
                        padding: '2px 6px',
                        borderRadius: '4px',
                        fontSize: '10px',
                        fontWeight: '500',
                      }}
                    >
                      {photo.categorie_label}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        <a
          href={pdf_url}
          target="_blank"
          rel="noopener noreferrer"
          className="btn btn-primary"
          style={{ width: '100%', marginBottom: '16px', textAlign: 'center', textDecoration: 'none' }}
        >
          📄 Télécharger PDF
        </a>

        {selectedPhoto && (
          <div
            onClick={() => setSelectedPhoto(null)}
            style={{
              position: 'fixed',
              top: 0,
              left: 0,
              right: 0,
              bottom: 0,
              background: 'rgba(0,0,0,0.9)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              zIndex: 9999,
              padding: '20px',
            }}
          >
            <img
              src={selectedPhoto}
              alt="Photo agrandie"
              style={{
                maxWidth: '100%',
                maxHeight: '100%',
                objectFit: 'contain',
              }}
            />
          </div>
        )}
      </div>
    </Layout>
  );
}
