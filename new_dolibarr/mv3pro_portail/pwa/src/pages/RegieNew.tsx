import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { apiClient } from '../lib/api';

export function RegieNew() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [formData, setFormData] = useState({
    fk_project: '',
    date_regie: new Date().toISOString().split('T')[0],
    location_text: '',
    type_regie: '',
    note_public: '',
    note_private: '',
  });

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    if (!formData.fk_project) {
      setError('Le projet est obligatoire');
      return;
    }

    try {
      setLoading(true);
      setError(null);

      const response = await apiClient<{ regie_id: number; ref: string }>('/api/v1/regie_create.php', {
        method: 'POST',
        body: JSON.stringify({
          fk_project: parseInt(formData.fk_project),
          date_regie: formData.date_regie,
          location_text: formData.location_text,
          type_regie: formData.type_regie,
          note_public: formData.note_public,
          note_private: formData.note_private,
        }),
      });

      navigate(`/regie/${response.regie_id}`);
    } catch (err: any) {
      setError(err.message || 'Erreur lors de la création de la régie');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Layout title="Nouvelle régie" showBack>
      <div style={{ padding: '20px' }}>
        {error && (
          <div className="alert alert-error" style={{ marginBottom: '16px' }}>
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '600' }}>
                Projet * <span style={{ fontSize: '12px', color: '#6b7280' }}>(ID)</span>
              </label>
              <input
                type="number"
                required
                value={formData.fk_project}
                onChange={(e) => setFormData({ ...formData, fk_project: e.target.value })}
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  fontSize: '16px',
                }}
                placeholder="Ex: 123"
              />
              <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>
                Entrez l'ID du projet Dolibarr
              </div>
            </div>

            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '600' }}>
                Date
              </label>
              <input
                type="date"
                value={formData.date_regie}
                onChange={(e) => setFormData({ ...formData, date_regie: e.target.value })}
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  fontSize: '16px',
                }}
              />
            </div>

            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '600' }}>
                Lieu
              </label>
              <input
                type="text"
                value={formData.location_text}
                onChange={(e) => setFormData({ ...formData, location_text: e.target.value })}
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  fontSize: '16px',
                }}
                placeholder="Ex: Chantier A"
              />
            </div>

            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '600' }}>
                Type de régie
              </label>
              <input
                type="text"
                value={formData.type_regie}
                onChange={(e) => setFormData({ ...formData, type_regie: e.target.value })}
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  fontSize: '16px',
                }}
                placeholder="Ex: Installation"
              />
            </div>

            <div style={{ marginBottom: '16px' }}>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '600' }}>
                Note publique
              </label>
              <textarea
                value={formData.note_public}
                onChange={(e) => setFormData({ ...formData, note_public: e.target.value })}
                rows={3}
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  fontSize: '16px',
                  fontFamily: 'inherit',
                }}
                placeholder="Note visible par le client"
              />
            </div>

            <div style={{ marginBottom: '0' }}>
              <label style={{ display: 'block', marginBottom: '8px', fontWeight: '600' }}>
                Note privée
              </label>
              <textarea
                value={formData.note_private}
                onChange={(e) => setFormData({ ...formData, note_private: e.target.value })}
                rows={3}
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  fontSize: '16px',
                  fontFamily: 'inherit',
                }}
                placeholder="Note interne"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="btn btn-primary btn-full"
          >
            {loading ? 'Création...' : '✅ Créer la régie'}
          </button>
        </form>
      </div>
    </Layout>
  );
}
