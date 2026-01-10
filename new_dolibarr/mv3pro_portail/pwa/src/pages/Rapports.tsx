import { useEffect, useState } from 'react';
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
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [hasMore, setHasMore] = useState(false);
  const limit = 20;

  const loadRapports = async (resetPage = false) => {
    setLoading(true);
    setError('');

    const currentPage = resetPage ? 1 : page;

    try {
      const response = await api.rapportsList({
        limit,
        page: currentPage,
        search: searchQuery || undefined,
        statut: filterStatut !== 'all' ? filterStatut : undefined,
        from: filterDateDebut || undefined,
        to: filterDateFin || undefined,
      });

      // Fallback robuste pour gérer différents formats de réponse
      const items = response?.data?.items ?? [];
      const totalCount = response?.data?.total ?? 0;
      const totalPages = response?.data?.total_pages ?? 0;

      setRapports(Array.isArray(items) ? items : []);
      setTotal(totalCount);
      setHasMore(currentPage < totalPages);
      if (resetPage) setPage(1);
    } catch (err: any) {
      console.error('[Rapports] Error loading rapports:', err);
      setError(err.message || 'Erreur lors du chargement des rapports');
      setRapports([]);
      setTotal(0);
      setHasMore(false);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadRapports(true);
  }, [searchQuery, filterStatut, filterDateDebut, filterDateFin]);

  const handleLoadMore = () => {
    const nextPage = page + 1;
    setPage(nextPage);
    loadRapports();
  };

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

        <div className="card" style={{ marginBottom: '16px' }}>
          <div style={{ marginBottom: '12px' }}>
            <input
              type="text"
              placeholder="🔍 Rechercher (projet, client, réf...)"
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

          {total > 0 && (
            <div style={{ marginTop: '12px', fontSize: '14px', color: '#6b7280', textAlign: 'center' }}>
              {total} rapport(s) trouvé(s)
            </div>
          )}
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

        {!loading && rapports.length === 0 && (
          <div className="card" style={{ textAlign: 'center', padding: '40px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>📋</div>
            <div style={{ color: '#6b7280', fontSize: '16px' }}>
              {searchQuery || filterStatut !== 'all' || filterDateDebut || filterDateFin
                ? 'Aucun rapport ne correspond aux filtres'
                : 'Aucun rapport enregistré'}
            </div>
          </div>
        )}

        {!loading && rapports.length > 0 && (
          <>
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
                      {rapport.client_nom || rapport.projet_title || rapport.ref}
                    </div>
                    {rapport.projet_ref && (
                      <div style={{ fontSize: '13px', color: '#9ca3af', marginBottom: '4px' }}>
                        Projet: {rapport.projet_ref}
                      </div>
                    )}
                    <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '8px' }}>
                      📅 {new Date(rapport.date_rapport).toLocaleDateString('fr-FR')}
                      {rapport.temps_total && rapport.temps_total > 0 && (
                        <span> · ⏱️ {rapport.temps_total}h</span>
                      )}
                    </div>
                    <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center' }}>
                      <span
                        className={`badge badge-${
                          rapport.statut_text === 'valide'
                            ? 'success'
                            : rapport.statut_text === 'soumis'
                            ? 'info'
                            : 'warning'
                        }`}
                      >
                        {rapport.statut_text}
                      </span>
                      {rapport.nb_photos > 0 && (
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

            {hasMore && (
              <button
                onClick={handleLoadMore}
                className="btn btn-secondary"
                style={{ width: '100%', marginTop: '16px' }}
                disabled={loading}
              >
                {loading ? 'Chargement...' : 'Charger plus'}
              </button>
            )}
          </>
        )}
      </div>
    </Layout>
  );
}
