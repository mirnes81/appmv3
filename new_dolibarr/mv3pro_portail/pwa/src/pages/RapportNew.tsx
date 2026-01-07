import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { api } from '../lib/api';

export function RapportNew() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    date_rapport: new Date().toISOString().split('T')[0],
    heure_debut: '08:00',
    heure_fin: '17:00',
    description: '',
  });

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
    <Layout title="Nouveau rapport simple" showBack>
      <div style={{ padding: '20px' }}>
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
              rows={6}
              placeholder="Décrivez le travail effectué..."
            />
          </div>

          <button type="submit" className="btn btn-primary btn-full" disabled={loading}>
            {loading ? <LoadingSpinner size={20} /> : 'Enregistrer le rapport'}
          </button>
        </form>
      </div>
    </Layout>
  );
}
