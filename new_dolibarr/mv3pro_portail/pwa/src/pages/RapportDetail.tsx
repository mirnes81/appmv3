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

  const { rapport: r, photos, frais } = rapport;

  return (
    <Layout title={`Rapport #${r.id}`} showBack>
      <div style={{ padding: '20px' }}>
        <div className="card" style={{ marginBottom: '16px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'start', marginBottom: '16px' }}>
            <div>
              <h2 style={{ fontSize: '20px', fontWeight: '700', marginBottom: '4px' }}>
                {r.projet?.title || `Rapport #${r.id}`}
              </h2>
              {r.projet?.client && (
                <div style={{ fontSize: '14px', color: '#6b7280' }}>{r.projet.client}</div>
              )}
            </div>
            {r.statut && (
              <span
                className={`badge badge-${
                  r.statut === 'valide' ? 'success' : r.statut === 'soumis' ? 'info' : 'warning'
                }`}
              >
                {r.statut}
              </span>
            )}
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', fontSize: '14px' }}>
            <div>
              <div style={{ color: '#6b7280', marginBottom: '2px' }}>Date</div>
              <div style={{ fontWeight: '500' }}>
                📅 {new Date(r.date_rapport).toLocaleDateString('fr-FR')}
              </div>
            </div>
            {r.heures_debut && r.heures_fin && (
              <div>
                <div style={{ color: '#6b7280', marginBottom: '2px' }}>Horaires</div>
                <div style={{ fontWeight: '500' }}>
                  ⏱️ {r.heures_debut} - {r.heures_fin}
                  {r.temps_total && ` (${r.temps_total}h)`}
                </div>
              </div>
            )}
            {r.auteur && (
              <div>
                <div style={{ color: '#6b7280', marginBottom: '2px' }}>Auteur</div>
                <div style={{ fontWeight: '500' }}>👤 {r.auteur.nom}</div>
              </div>
            )}
            {r.projet?.ref && (
              <div>
                <div style={{ color: '#6b7280', marginBottom: '2px' }}>Projet</div>
                <div style={{ fontWeight: '500' }}>🏗️ {r.projet.ref}</div>
              </div>
            )}
          </div>
        </div>

        {r.zone_travail && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '600' }}>
              ZONE DE TRAVAIL
            </div>
            <div style={{ fontSize: '14px' }}>{r.zone_travail}</div>
          </div>
        )}

        {r.travaux_realises && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '600' }}>
              TRAVAUX RÉALISÉS
            </div>
            <div style={{ fontSize: '14px', whiteSpace: 'pre-line' }}>{r.travaux_realises}</div>
          </div>
        )}

        {r.description && r.description !== r.travaux_realises && (
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
                  onClick={() => setSelectedPhoto(photo.url || '')}
                  style={{
                    aspectRatio: '1',
                    borderRadius: '8px',
                    overflow: 'hidden',
                    cursor: 'pointer',
                    border: '1px solid #e5e7eb',
                  }}
                >
                  {photo.url ? (
                    <img
                      src={photo.url}
                      alt={photo.description || photo.filename}
                      style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                    />
                  ) : (
                    <div
                      style={{
                        width: '100%',
                        height: '100%',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        background: '#f3f4f6',
                        fontSize: '24px',
                      }}
                    >
                      📷
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        )}

        {frais && frais.length > 0 && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '12px', fontWeight: '600' }}>
              FRAIS ({frais.length})
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
              {frais.map((f, idx) => (
                <div
                  key={idx}
                  style={{
                    padding: '12px',
                    background: '#f9fafb',
                    borderRadius: '8px',
                    fontSize: '14px',
                  }}
                >
                  <div style={{ fontWeight: '500', marginBottom: '4px' }}>{f.type}</div>
                  <div style={{ color: '#6b7280' }}>
                    {f.montant}€ · {f.mode_paiement}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

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
