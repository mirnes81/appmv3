import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { api } from '../lib/api';
import { getGeolocation, capturePhoto } from '../lib/device';

export function RapportNewPro() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    date_rapport: new Date().toISOString().split('T')[0],
    heure_debut: '08:00',
    heure_fin: '17:00',
    description: '',
    observations: '',
    meteo: '',
    temperature: '',
    latitude: '',
    longitude: '',
    photos: [] as string[],
  });

  const handleGPS = async () => {
    try {
      setError('');
      const position = await getGeolocation();
      setFormData({
        ...formData,
        latitude: position.latitude.toFixed(6),
        longitude: position.longitude.toFixed(6),
      });
    } catch (err: any) {
      setError('GPS: ' + err.message);
    }
  };

  const handlePhoto = async () => {
    try {
      setError('');
      const base64 = await capturePhoto({ maxWidth: 1200, quality: 0.8 });
      setFormData({
        ...formData,
        photos: [...formData.photos, base64],
      });
    } catch (err: any) {
      setError('Photo: ' + err.message);
    }
  };

  const removePhoto = (index: number) => {
    setFormData({
      ...formData,
      photos: formData.photos.filter((_, i) => i !== index),
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await api.rapportsCreate(formData);
      navigate('/rapports', { replace: true });
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout title="Nouveau rapport PRO" showBack>
      <div style={{ padding: '20px' }}>
        <div className="alert alert-info" style={{ marginBottom: '20px' }}>
          ⭐ Rapport PRO avec GPS, photos, météo
        </div>

        {error && <div className="alert alert-error">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="date_rapport" className="form-label">
              Date du rapport
            </label>
            <input
              id="date_rapport"
              type="date"
              className="form-input"
              value={formData.date_rapport}
              onChange={(e) =>
                setFormData({ ...formData, date_rapport: e.target.value })
              }
              required
            />
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
            <div className="form-group">
              <label htmlFor="heure_debut" className="form-label">
                Début
              </label>
              <input
                id="heure_debut"
                type="time"
                className="form-input"
                value={formData.heure_debut}
                onChange={(e) =>
                  setFormData({ ...formData, heure_debut: e.target.value })
                }
              />
            </div>

            <div className="form-group">
              <label htmlFor="heure_fin" className="form-label">
                Fin
              </label>
              <input
                id="heure_fin"
                type="time"
                className="form-input"
                value={formData.heure_fin}
                onChange={(e) =>
                  setFormData({ ...formData, heure_fin: e.target.value })
                }
              />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label">📍 Position GPS</label>
            <button
              type="button"
              onClick={handleGPS}
              className="btn btn-secondary btn-full"
            >
              {formData.latitude ? '✓ Position enregistrée' : '📍 Ajouter ma position'}
            </button>
            {formData.latitude && (
              <div style={{ marginTop: '8px', fontSize: '14px', color: '#6b7280' }}>
                Lat: {formData.latitude}, Lng: {formData.longitude}
              </div>
            )}
          </div>

          <div className="form-group">
            <label className="form-label">📸 Photos ({formData.photos.length})</label>
            <button
              type="button"
              onClick={handlePhoto}
              className="btn btn-secondary btn-full"
            >
              📸 Ajouter une photo
            </button>
            {formData.photos.length > 0 && (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '8px', marginTop: '12px' }}>
                {formData.photos.map((photo, index) => (
                  <div key={index} style={{ position: 'relative' }}>
                    <img
                      src={photo}
                      alt={`Photo ${index + 1}`}
                      style={{
                        width: '100%',
                        height: '80px',
                        objectFit: 'cover',
                        borderRadius: '8px',
                      }}
                    />
                    <button
                      type="button"
                      onClick={() => removePhoto(index)}
                      style={{
                        position: 'absolute',
                        top: '4px',
                        right: '4px',
                        background: '#ef4444',
                        color: 'white',
                        border: 'none',
                        borderRadius: '50%',
                        width: '24px',
                        height: '24px',
                        fontSize: '16px',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                      }}
                    >
                      ×
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
            <div className="form-group">
              <label htmlFor="meteo" className="form-label">
                Météo
              </label>
              <select
                id="meteo"
                className="form-select"
                value={formData.meteo}
                onChange={(e) =>
                  setFormData({ ...formData, meteo: e.target.value })
                }
              >
                <option value="">-</option>
                <option value="ensoleille">☀️ Ensoleillé</option>
                <option value="nuageux">☁️ Nuageux</option>
                <option value="pluie">🌧️ Pluie</option>
                <option value="orage">⛈️ Orage</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="temperature" className="form-label">
                Température (°C)
              </label>
              <input
                id="temperature"
                type="number"
                className="form-input"
                value={formData.temperature}
                onChange={(e) =>
                  setFormData({ ...formData, temperature: e.target.value })
                }
                placeholder="20"
              />
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="description" className="form-label">
              Description
            </label>
            <textarea
              id="description"
              className="form-textarea"
              value={formData.description}
              onChange={(e) =>
                setFormData({ ...formData, description: e.target.value })
              }
              rows={4}
              placeholder="Décrivez le travail effectué..."
            />
          </div>

          <div className="form-group">
            <label htmlFor="observations" className="form-label">
              Observations
            </label>
            <textarea
              id="observations"
              className="form-textarea"
              value={formData.observations}
              onChange={(e) =>
                setFormData({ ...formData, observations: e.target.value })
              }
              rows={3}
              placeholder="Remarques supplémentaires..."
            />
          </div>

          <button type="submit" className="btn btn-success btn-full" disabled={loading}>
            {loading ? <LoadingSpinner size={20} /> : '⭐ Enregistrer rapport PRO'}
          </button>
        </form>
      </div>
    </Layout>
  );
}
