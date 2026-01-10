import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { api, Rapport } from '../lib/api';
import { useAuth } from '../contexts/AuthContext';

export function Rapports() {
  const { user } = useAuth();
  const [rapports, setRapports] = useState<Rapport[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatut, setFilterStatut] = useState<string>('all');
  const [filterDateDebut, setFilterDateDebut] = useState('');
  const [filterDateFin, setFilterDateFin] = useState('');
  const [filterUserId, setFilterUserId] = useState<number | undefined>(undefined);
  const [users, setUsers] = useState<{ id: number; name: string }[]>([]);
  const [loadingUsers, setLoadingUsers] = useState(false);
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [hasMore, setHasMore] = useState(false);
  const limit = 20;

  // Mode debug
  const [debugMode, setDebugMode] = useState(false);
  const [debugData, setDebugData] = useState<any>(null);
  const [loadingDebug, setLoadingDebug] = useState(false);
  const [lastApiCall, setLastApiCall] = useState<any>(null);

  const isAdmin = user?.admin === true;

  const loadRapports = async (resetPage = false) => {
    if (!user?.id) {
      setError('Veuillez vous connecter pour voir vos rapports');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError('');

    const currentPage = resetPage ? 1 : page;

    try {
      const params = {
        limit,
        page: currentPage,
        search: searchQuery || undefined,
        statut: filterStatut !== 'all' ? filterStatut : undefined,
        from: filterDateDebut || undefined,
        to: filterDateFin || undefined,
        user_id: filterUserId,
      };

      // Log de l'appel API pour le debug
      const apiCallInfo = {
        timestamp: new Date().toISOString(),
        endpoint: '/rapports.php',
        params,
        user: {
          id: user?.id,
          dolibarr_user_id: user?.dolibarr_user_id,
          name: user?.name,
          admin: user?.admin,
        },
      };
      setLastApiCall(apiCallInfo);

      const response = await api.rapportsList(params);

      // Fallback robuste pour gérer différents formats de réponse
      const items = response?.data?.items ?? [];
      const totalCount = response?.data?.total ?? 0;
      const totalPages = response?.data?.total_pages ?? 0;

      // Log de la réponse pour le debug
      setLastApiCall({
        ...apiCallInfo,
        response: {
          status: 'success',
          items_count: items.length,
          total: totalCount,
          total_pages: totalPages,
        },
      });

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

      // Log de l'erreur pour le debug
      setLastApiCall({
        ...lastApiCall,
        response: {
          status: 'error',
          error: err.message,
          stack: err.stack,
        },
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user?.id) {
      loadRapports(true);
    }
  }, [user?.id, searchQuery, filterStatut, filterDateDebut, filterDateFin, filterUserId]);

  // Charger la liste des utilisateurs si admin
  useEffect(() => {
    if (isAdmin && users.length === 0) {
      setLoadingUsers(true);
      api.usersList()
        .then(usersList => setUsers(usersList))
        .catch(err => console.error('Erreur chargement utilisateurs:', err))
        .finally(() => setLoadingUsers(false));
    }
  }, [isAdmin]);

  const handleLoadMore = () => {
    const nextPage = page + 1;
    setPage(nextPage);
    loadRapports();
  };

  const loadDebugInfo = async () => {
    setLoadingDebug(true);
    try {
      const debugResponse = await api.rapportsDebug();
      setDebugData(debugResponse);
    } catch (err: any) {
      console.error('Erreur chargement debug:', err);
      setDebugData({ error: err.message });
    } finally {
      setLoadingDebug(false);
    }
  };

  const toggleDebugMode = () => {
    const newDebugMode = !debugMode;
    setDebugMode(newDebugMode);
    if (newDebugMode && !debugData) {
      loadDebugInfo();
    }
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

        <button
          onClick={toggleDebugMode}
          className="btn"
          style={{
            width: '100%',
            marginBottom: '20px',
            background: debugMode ? '#ef4444' : '#6b7280',
            color: 'white',
            border: 'none',
          }}
        >
          {debugMode ? '🔴 Désactiver Debug' : '🔧 Mode Debug'}
        </button>

        {debugMode && (
          <div className="card" style={{ marginBottom: '20px', background: '#1f2937', color: '#fff', padding: '16px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
              <h3 style={{ margin: 0, fontSize: '18px', fontWeight: '600' }}>🔧 Panneau de Debug</h3>
              <button
                onClick={loadDebugInfo}
                disabled={loadingDebug}
                className="btn btn-secondary"
                style={{ padding: '6px 12px', fontSize: '14px' }}
              >
                {loadingDebug ? '⏳' : '🔄 Rafraîchir'}
              </button>
            </div>

            {loadingDebug && (
              <div style={{ textAlign: 'center', padding: '20px', color: '#9ca3af' }}>
                Chargement des infos de debug...
              </div>
            )}

            {debugData && !loadingDebug && (
              <div style={{ fontSize: '13px', fontFamily: 'monospace' }}>
                {/* Section 1: Info utilisateur */}
                <div style={{ marginBottom: '16px', padding: '12px', background: '#374151', borderRadius: '8px' }}>
                  <div style={{ fontWeight: '600', marginBottom: '8px', color: '#60a5fa' }}>👤 Informations Utilisateur</div>
                  <div style={{ display: 'grid', gap: '4px' }}>
                    <div>• Nom: {user?.name || debugData.debug_info?.user_info?.name || 'N/A'}</div>
                    <div>• Email: {user?.email || debugData.debug_info?.user_info?.email || 'N/A'}</div>
                    <div>• Dolibarr User ID: <span style={{ color: user?.id || debugData.debug_info?.user_info?.dolibarr_user_id ? '#10b981' : '#ef4444', fontWeight: '600' }}>
                      {user?.id || user?.dolibarr_user_id || debugData.debug_info?.user_info?.dolibarr_user_id || 'NON DÉFINI ❌'}
                    </span></div>
                    <div>• Mobile User ID (OLD): <span style={{ color: '#f59e0b' }}>
                      {user?.mobile_user_id || debugData.debug_info?.user_info?.OLD_user_id || 'N/A'}
                    </span></div>
                    <div>• Mode: {user?.auth_mode || debugData.debug_info?.user_info?.mode || 'N/A'}</div>
                    <div>• Admin: <span style={{ color: user?.admin || debugData.debug_info?.user_info?.is_admin ? '#10b981' : '#ef4444' }}>
                      {user?.admin || debugData.debug_info?.user_info?.is_admin ? '✅ OUI' : '❌ NON'}
                    </span></div>
                    <div>• Compte non lié: {user?.is_unlinked || debugData.debug_info?.user_info?.is_unlinked ? '⚠️ OUI' : '✅ NON'}</div>
                  </div>
                </div>

                {/* Section 2: Comparaison ancien/nouveau système */}
                <div style={{ marginBottom: '16px', padding: '12px', background: '#374151', borderRadius: '8px' }}>
                  <div style={{ fontWeight: '600', marginBottom: '8px', color: '#f59e0b' }}>🔄 Comparaison Systèmes</div>
                  <div style={{ display: 'grid', gap: '4px' }}>
                    <div style={{ padding: '8px', background: '#ef444420', borderRadius: '4px', border: '1px solid #ef4444' }}>
                      <div style={{ color: '#ef4444', fontWeight: '600' }}>❌ ANCIEN SYSTÈME (bugué)</div>
                      <div>{debugData.comparison?.old_system || 'N/A'}</div>
                    </div>
                    <div style={{ padding: '8px', background: '#10b98120', borderRadius: '4px', border: '1px solid #10b981' }}>
                      <div style={{ color: '#10b981', fontWeight: '600' }}>✅ NOUVEAU SYSTÈME (corrigé)</div>
                      <div>{debugData.comparison?.new_system || 'N/A'}</div>
                    </div>
                  </div>
                </div>

                {/* Section 3: Stats rapports */}
                <div style={{ marginBottom: '16px', padding: '12px', background: '#374151', borderRadius: '8px' }}>
                  <div style={{ fontWeight: '600', marginBottom: '8px', color: '#a78bfa' }}>📊 Statistiques Rapports</div>
                  <div style={{ display: 'grid', gap: '4px' }}>
                    <div>• Total dans l'entité: <span style={{ color: '#60a5fa', fontWeight: '600' }}>
                      {debugData.debug_info?.total_rapports_in_entity || 0}
                    </span></div>
                    <div>• Visibles avec NOUVEAU filtre: <span style={{ color: '#10b981', fontWeight: '600' }}>
                      {debugData.debug_info?.rapports_with_NEW_filter || 0}
                    </span></div>
                    <div>• Visibles avec ANCIEN filtre: <span style={{ color: '#f59e0b', fontWeight: '600' }}>
                      {debugData.debug_info?.rapports_with_OLD_filter || 0}
                    </span></div>
                    <div>• Filtre appliqué: {debugData.debug_info?.filter_applied || 'AUCUN'}</div>
                  </div>
                </div>

                {/* Section 4: Recommandation */}
                {debugData.recommendation && (
                  <div style={{ marginBottom: '16px', padding: '12px', background: '#374151', borderRadius: '8px' }}>
                    <div style={{ fontWeight: '600', marginBottom: '8px', color: '#fbbf24' }}>💡 Recommandation</div>
                    <div style={{ lineHeight: '1.6' }}>{debugData.recommendation}</div>
                  </div>
                )}

                {/* Section 5: Rapports par utilisateur */}
                {debugData.debug_info?.rapports_by_user && Object.keys(debugData.debug_info.rapports_by_user).length > 0 && (
                  <div style={{ marginBottom: '16px', padding: '12px', background: '#374151', borderRadius: '8px' }}>
                    <div style={{ fontWeight: '600', marginBottom: '8px', color: '#f472b6' }}>👥 Rapports par Utilisateur</div>
                    <div style={{ display: 'grid', gap: '4px' }}>
                      {Object.entries(debugData.debug_info.rapports_by_user).map(([userId, count]) => (
                        <div key={userId}>
                          • User ID {userId}: {count as number} rapport(s)
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Section 6: Derniers rapports */}
                {debugData.debug_info?.recent_rapports && debugData.debug_info.recent_rapports.length > 0 && (
                  <div style={{ marginBottom: '16px', padding: '12px', background: '#374151', borderRadius: '8px' }}>
                    <div style={{ fontWeight: '600', marginBottom: '8px', color: '#34d399' }}>📋 5 Derniers Rapports (BD)</div>
                    <div style={{ display: 'grid', gap: '8px' }}>
                      {debugData.debug_info.recent_rapports.map((r: any) => (
                        <div key={r.rowid} style={{ padding: '8px', background: '#4b556320', borderRadius: '4px' }}>
                          <div><span style={{ color: '#60a5fa' }}>ID:</span> {r.rowid} | <span style={{ color: '#60a5fa' }}>Ref:</span> {r.ref}</div>
                          <div><span style={{ color: '#60a5fa' }}>Date:</span> {r.date_rapport}</div>
                          <div><span style={{ color: '#60a5fa' }}>User ID:</span> {r.fk_user} | <span style={{ color: '#60a5fa' }}>Login:</span> {r.user_login}</div>
                          <div><span style={{ color: '#60a5fa' }}>User:</span> {r.user_name}</div>
                          <div><span style={{ color: '#60a5fa' }}>Projet:</span> {r.projet_title || 'N/A'}</div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Section 7: Dernier appel API */}
                <div style={{ marginBottom: '16px', padding: '12px', background: '#374151', borderRadius: '8px' }}>
                  <div style={{ fontWeight: '600', marginBottom: '8px', color: '#fb923c' }}>🌐 Dernier Appel API</div>
                  <div style={{ display: 'grid', gap: '4px' }}>
                    <div>• Endpoint: {lastApiCall?.endpoint || 'Aucun appel effectué'}</div>
                    <div>• Timestamp: {lastApiCall?.timestamp ? new Date(lastApiCall.timestamp).toLocaleString('fr-FR') : 'N/A'}</div>
                    {lastApiCall?.params && (
                      <div>• Params: <pre style={{ margin: '4px 0', padding: '8px', background: '#1f2937', borderRadius: '4px', overflow: 'auto' }}>
                        {JSON.stringify(lastApiCall.params, null, 2)}
                      </pre></div>
                    )}
                    {lastApiCall?.response && (
                      <div>• Réponse: <pre style={{ margin: '4px 0', padding: '8px', background: '#1f2937', borderRadius: '4px', overflow: 'auto' }}>
                        {JSON.stringify(lastApiCall.response, null, 2)}
                      </pre></div>
                    )}
                  </div>
                </div>

                {/* Section 8: Rapports actuels affichés dans la PWA */}
                <div style={{ padding: '12px', background: '#374151', borderRadius: '8px' }}>
                  <div style={{ fontWeight: '600', marginBottom: '8px', color: '#818cf8' }}>📱 Rapports Affichés dans la PWA</div>
                  <div style={{ marginBottom: '8px' }}>
                    Total affiché: <span style={{ color: '#10b981', fontWeight: '600' }}>{rapports.length}</span> / {total}
                  </div>
                  {rapports.length > 0 ? (
                    <div style={{ display: 'grid', gap: '8px', maxHeight: '400px', overflow: 'auto' }}>
                      {rapports.map((r) => (
                        <div key={r.rowid} style={{ padding: '8px', background: '#4b556320', borderRadius: '4px' }}>
                          <div><span style={{ color: '#60a5fa' }}>ID:</span> {r.rowid} | <span style={{ color: '#60a5fa' }}>Ref:</span> {r.ref}</div>
                          <div><span style={{ color: '#60a5fa' }}>Date:</span> {r.date_rapport}</div>
                          <div><span style={{ color: '#60a5fa' }}>Client:</span> {r.client_nom || 'N/A'}</div>
                          <div><span style={{ color: '#60a5fa' }}>Projet:</span> {r.projet_ref || 'N/A'}</div>
                          <div><span style={{ color: '#60a5fa' }}>Statut:</span> {r.statut_text}</div>
                          <div><span style={{ color: '#60a5fa' }}>Photos:</span> {r.nb_photos || 0}</div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div style={{ color: '#f59e0b', padding: '8px' }}>⚠️ Aucun rapport affiché</div>
                  )}
                </div>
              </div>
            )}

            {!debugData && !loadingDebug && (
              <div style={{ textAlign: 'center', padding: '20px', color: '#9ca3af' }}>
                Cliquez sur "Rafraîchir" pour charger les infos de debug
              </div>
            )}
          </div>
        )}

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

          <div style={{ display: 'grid', gridTemplateColumns: isAdmin ? '1fr 1fr' : '1fr', gap: '12px' }}>
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

            {isAdmin && (
              <div>
                <label style={{ fontSize: '12px', color: '#6b7280', display: 'block', marginBottom: '4px' }}>
                  👤 Employé (admin)
                </label>
                <select
                  value={filterUserId || ''}
                  onChange={(e) => setFilterUserId(e.target.value ? Number(e.target.value) : undefined)}
                  className="form-input"
                  style={{ width: '100%' }}
                  disabled={loadingUsers}
                >
                  <option value="">Tous les employés</option>
                  {users.map(u => (
                    <option key={u.id} value={u.id}>{u.name}</option>
                  ))}
                </select>
              </div>
            )}
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
