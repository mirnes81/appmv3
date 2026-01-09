import { useEffect, useState, useMemo } from 'react';
import { Link } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { api, Rapport } from '../lib/api';

export function Rapports() {
  const [rapports, setRapports] = useState<Rapport[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatut, setFilterStatut] = useState<string>('all');
  const [filterDateDebut, setFilterDateDebut] = useState('');
  const [filterDateFin, setFilterDateFin] = useState('');

  useEffect(() => {
    api
      .rapportsList()
      .then(setRapports)
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, []);

  const filteredRapports = useMemo(() => {
    return rapports.filter((rapport) => {
      const matchSearch =
        !searchQuery ||
        rapport.projet_nom?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        rapport.client?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        rapport.ref?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        rapport.zones?.toLowerCase().includes(searchQuery.toLowerCase());

      const matchStatut = filterStatut === 'all' || rapport.statut === filterStatut;

      const rapportDate = new Date(rapport.date_rapport);
      const matchDateDebut = !filterDateDebut || rapportDate >= new Date(filterDateDebut);
      const matchDateFin = !filterDateFin || rapportDate <= new Date(filterDateFin);

      return matchSearch && matchStatut && matchDateDebut && matchDateFin;
    });
  }, [rapports, searchQuery, filterStatut, filterDateDebut, filterDateFin]);

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

        {!loading && rapports.length > 0 && (
          <div className="card" style={{ marginBottom: '16px' }}>
            <div style={{ marginBottom: '12px' }}>
              <input
                type="text"
                placeholder="🔍 Rechercher (projet, client, zones...)"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="form-input"
                style={{ width: '100%' }}
              />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px' }}>
              <div>
                <label style={{ fontSize: '12px', color: '#6b7280', display: 'block', marginBottom: '4px' }}>
                  Date début
                </label>
                <input
                  type="date"
                  value={filterDateDebut}
                  onChange={(e) => setFilterDateDebut(e.target.value)}
                  className="form-input"
                  style={{ width: '100%' }}
                />
              </div>
              <div>
                <label style={{ fontSize: '12px', color: '#6b7280', display: 'block', marginBottom: '4px' }}>
                  Date fin
                </label>
                <input
                  type="date"
                  value={filterDateFin}
                  onChange={(e) => setFilterDateFin(e.target.value)}
                  className="form-input"
                  style={{ width: '100%' }}
                />
              </div>
            </div>

            <div>
              <label style={{ fontSize: '12px', color: '#6b7280', display: 'block', marginBottom: '4px' }}>
                Statut
              </label>
              <select
                value={filterStatut}
                onChange={(e) => setFilterStatut(e.target.value)}
                className="form-input"
                style={{ width: '100%' }}
              >
                <option value="all">Tous les statuts</option>
                <option value="brouillon">Brouillon</option>
                <option value="valide">Validé</option>
                <option value="soumis">Soumis</option>
              </select>
            </div>

            {(searchQuery || filterStatut !== 'all' || filterDateDebut || filterDateFin) && (
              <div style={{ marginTop: '12px', fontSize: '14px', color: '#6b7280', textAlign: 'center' }}>
                {filteredRapports.length} rapport(s) trouvé(s)
              </div>
            )}
          </div>
        )}

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

        {!loading && filteredRapports.length === 0 && rapports.length > 0 && (
          <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>🔍</div>
            <div style={{ color: '#6b7280', fontSize: '16px' }}>
              Aucun rapport ne correspond aux filtres
            </div>
          </div>
        )}

        {!loading && filteredRapports.length > 0 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            {filteredRapports.map((rapport) => (
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
                      {rapport.projet_nom || rapport.ref || `Rapport #${rapport.rowid}`}
                    </div>
                    {rapport.client && (
                      <div style={{ fontSize: '13px', color: '#9ca3af', marginBottom: '4px' }}>
                        {rapport.client}
                      </div>
                    )}
                    <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '8px' }}>
                      📅 {new Date(rapport.date_rapport).toLocaleDateString('fr-FR')}
                      {rapport.heures && <span> · ⏱️ {rapport.heures}h</span>}
                    </div>
                    {(rapport.zones || rapport.surface) && (
                      <div style={{ fontSize: '13px', color: '#6b7280', marginBottom: '8px' }}>
                        {rapport.zones && <span>📍 {rapport.zones}</span>}
                        {rapport.surface && <span> · 📐 {rapport.surface}m²</span>}
                      </div>
                    )}
                    <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center' }}>
                      {rapport.statut && (
                        <span
                          className={`badge badge-${
                            rapport.statut === 'valide'
                              ? 'success'
                              : rapport.statut === 'soumis'
                              ? 'info'
                              : 'warning'
                          }`}
                        >
                          {rapport.statut}
                        </span>
                      )}
                      {rapport.nb_photos && rapport.nb_photos > 0 && (
                        <span style={{ fontSize: '13px', color: '#6b7280' }}>
                          📷 {rapport.nb_photos}
                        </span>
                      )}
                    </div>
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
