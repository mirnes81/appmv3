import { useState } from 'react';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { useAuth } from '../contexts/AuthContext';
import { api, storage } from '../lib/api';
import { API_PATHS } from '../config';

interface TestResult {
  name: string;
  status: 'OK' | 'WARNING' | 'ERROR' | 'PENDING';
  http_code?: number;
  response_time_ms?: number;
  error?: string;
  response_preview?: any;
  initial_url?: string;
  final_url?: string;
  redirect_count?: number;
}

interface BackendReport {
  debug_mode: boolean;
  timestamp: string;
  system_info: any;
  config_checks: any[];
  stats: {
    total: number;
    ok: number;
    warning: number;
    error: number;
    total_time_ms: number;
  };
  test_results: TestResult[];
}

interface PlanningDebugReport {
  success: boolean;
  user_info: {
    auth_mode: string;
    mobile_user_id: number | null;
    dolibarr_user_id: number | null;
    email: string;
    name: string;
    is_unlinked: boolean;
  };
  dolibarr_user: any;
  events_stats: any;
  events_samples: any[];
  diagnostic: any[];
}

interface RapportsDebugReport {
  success: boolean;
  debug_info: {
    user_info: any;
    entity: number;
    total_rapports: number;
    rapports_by_user: Record<number, number>;
    rapports_with_filter: number;
    filter_applied: string;
    recent_rapports: any[];
  };
  recommendation: string;
  solution?: string;
  table_structure?: {
    table_name: string;
    total_columns: number;
    existing_columns: string[];
    column_details: Record<string, any>;
    expected_columns: string[];
    missing_columns: string[];
    extra_columns: string[];
    has_issues: boolean;
  };
  api_test?: {
    success: boolean;
    error: string | null;
    sql_error: string | null;
    sql_query: string | null;
    rows_returned?: number;
  };
  fix_sql?: string[];
  diagnostic_summary?: {
    table_exists: boolean;
    all_columns_present: boolean;
    api_query_works: boolean;
    ready_for_production: boolean;
  };
}

export function Debug() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);
  const [backendReport, setBackendReport] = useState<BackendReport | null>(null);
  const [frontendTests, setFrontendTests] = useState<TestResult[]>([]);
  const [planningDebug, setPlanningDebug] = useState<PlanningDebugReport | null>(null);
  const [rapportsDebug, setRapportsDebug] = useState<RapportsDebugReport | null>(null);
  const [debugMode, setDebugMode] = useState(
    localStorage.getItem('mv3pro_debug') === 'true'
  );

  const testBackendEndpoints = async () => {
    setLoading(true);
    try {
      const token = storage.getToken();
      const response = await fetch(`${API_PATHS.base}/debug.php`, {
        headers: {
          'X-Auth-Token': token || '',
          Authorization: token ? `Bearer ${token}` : '',
        },
      });

      const data = await response.json();
      setBackendReport(data);
    } catch (error: any) {
      setBackendReport({
        debug_mode: false,
        timestamp: new Date().toISOString(),
        system_info: {},
        config_checks: [],
        stats: { total: 0, ok: 0, warning: 0, error: 1, total_time_ms: 0 },
        test_results: [
          {
            name: 'Backend Debug Endpoint',
            status: 'ERROR',
            error: error.message,
          },
        ],
      });
    } finally {
      setLoading(false);
    }
  };

  const testFrontendAPI = async () => {
    setLoading(true);
    const token = storage.getToken();
    const results: TestResult[] = [];

    const endpointsToTest = [
      { name: 'Me', url: '/me.php' },
      { name: 'Planning', url: '/planning.php?from=2026-01-09&to=2026-01-16' },
      { name: 'Rapports', url: '/rapports.php?limit=10' },
      { name: 'Matériel', url: '/materiel_list.php' },
      { name: 'Notifications', url: '/notifications_list.php' },
      { name: 'Régie', url: '/regie_list.php?limit=10' },
      { name: 'Sens Pose', url: '/sens_pose_list.php?limit=10' },
    ];

    for (const endpoint of endpointsToTest) {
      const start = performance.now();
      try {
        const response = await fetch(`${API_PATHS.base}${endpoint.url}`, {
          headers: {
            'X-Auth-Token': token || '',
            Authorization: token ? `Bearer ${token}` : '',
          },
        });

        const end = performance.now();
        const data = await response.json();

        results.push({
          name: endpoint.name,
          status: response.ok ? 'OK' : 'ERROR',
          http_code: response.status,
          response_time_ms: Math.round(end - start),
          error: response.ok ? undefined : data.error || 'Erreur inconnue',
          response_preview: data,
        });
      } catch (error: any) {
        results.push({
          name: endpoint.name,
          status: 'ERROR',
          error: error.message,
        });
      }
    }

    setFrontendTests(results);
    setLoading(false);
  };


  const testPlanningDebug = async () => {
    setLoading(true);
    const token = storage.getToken();

    try {
      const from = new Date();
      from.setDate(from.getDate() - 30);
      const to = new Date();
      to.setDate(to.getDate() + 60);

      const response = await fetch(
        `${API_PATHS.base}/planning_debug.php?from=${from.toISOString().split('T')[0]}&to=${to.toISOString().split('T')[0]}`,
        {
          headers: {
            'X-Auth-Token': token || '',
            Authorization: token ? `Bearer ${token}` : '',
          },
        }
      );

      const data = await response.json();
      setPlanningDebug(data);
    } catch (error: any) {
      setPlanningDebug({
        success: false,
        user_info: {
          auth_mode: 'error',
          mobile_user_id: null,
          dolibarr_user_id: null,
          email: '',
          name: '',
          is_unlinked: true,
        },
        dolibarr_user: null,
        events_stats: {},
        events_samples: [],
        diagnostic: [
          {
            type: 'ERROR',
            message: 'Erreur lors du diagnostic: ' + error.message,
          },
        ],
      });
    } finally {
      setLoading(false);
    }
  };

  const testRapportsDebug = async () => {
    setLoading(true);
    try {
      const data = await api.rapportsDebug();
      setRapportsDebug(data);
    } catch (error: any) {
      setRapportsDebug({
        success: false,
        debug_info: {
          user_info: {},
          entity: 0,
          total_rapports: 0,
          rapports_by_user: {},
          rapports_with_filter: 0,
          filter_applied: 'ERROR',
          recent_rapports: [],
        },
        recommendation: 'Erreur lors du diagnostic: ' + error.message,
        solution: 'Vérifier les logs serveur',
      });
    } finally {
      setLoading(false);
    }
  };

  const runFullDiagnostic = async () => {
    setLoading(true);
    await testBackendEndpoints();
    await testFrontendAPI();
    setLoading(false);
  };

  const exportReport = () => {
    const fullReport = {
      timestamp: new Date().toISOString(),
      user: user,
      backend_report: backendReport,
      frontend_tests: frontendTests,
      browser_info: {
        userAgent: navigator.userAgent,
        language: navigator.language,
        online: navigator.onLine,
        cookieEnabled: navigator.cookieEnabled,
      },
      storage_info: {
        token_present: !!storage.getToken(),
        debug_mode: debugMode,
      },
    };

    const blob = new Blob([JSON.stringify(fullReport, null, 2)], {
      type: 'application/json',
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `mv3pro-debug-report-${Date.now()}.json`;
    a.click();
    URL.revokeObjectURL(url);
  };

  const toggleDebugMode = () => {
    const newValue = !debugMode;
    if (newValue) {
      localStorage.setItem('mv3pro_debug', 'true');
    } else {
      localStorage.removeItem('mv3pro_debug');
    }
    setDebugMode(newValue);
  };

  const clearToken = () => {
    storage.clearToken();
    alert('Token effacé. Vous allez être redirigé vers la page de login.');
    window.location.href = '/#/login';
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'OK':
        return '#10b981';
      case 'WARNING':
        return '#f59e0b';
      case 'ERROR':
        return '#ef4444';
      default:
        return '#9ca3af';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'OK':
        return '✓';
      case 'WARNING':
        return '⚠';
      case 'ERROR':
        return '✗';
      default:
        return '○';
    }
  };

  return (
    <Layout title="Diagnostic Système">
      <div style={{ padding: '20px', maxWidth: '1200px', margin: '0 auto' }}>
        {/* Warning Banner */}
        <div
          style={{
            background: '#fef3c7',
            border: '2px solid #f59e0b',
            borderRadius: '12px',
            padding: '16px',
            marginBottom: '20px',
          }}
        >
          <h3 style={{ fontSize: '16px', fontWeight: '700', color: '#92400e', marginBottom: '8px' }}>
            ⚠️ Page de diagnostic système
          </h3>
          <p style={{ fontSize: '14px', color: '#78350f', margin: 0 }}>
            Cette page teste automatiquement tous les endpoints API et les routes PWA. Ne pas utiliser en
            production.
          </p>
        </div>

        {/* Actions principales */}
        <div style={{ display: 'flex', gap: '12px', marginBottom: '24px', flexWrap: 'wrap' }}>
          <button
            onClick={runFullDiagnostic}
            disabled={loading}
            style={{
              padding: '12px 24px',
              background: loading ? '#9ca3af' : '#0891b2',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: '600',
              cursor: loading ? 'not-allowed' : 'pointer',
            }}
          >
            {loading ? 'Test en cours...' : '🔍 Diagnostic Complet'}
          </button>

          <button
            onClick={testBackendEndpoints}
            disabled={loading}
            style={{
              padding: '12px 24px',
              background: loading ? '#9ca3af' : '#3b82f6',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: '600',
              cursor: loading ? 'not-allowed' : 'pointer',
            }}
          >
            Backend API
          </button>

          <button
            onClick={testFrontendAPI}
            disabled={loading}
            style={{
              padding: '12px 24px',
              background: loading ? '#9ca3af' : '#8b5cf6',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: '600',
              cursor: loading ? 'not-allowed' : 'pointer',
            }}
          >
            Frontend API
          </button>

          <button
            onClick={testPlanningDebug}
            disabled={loading}
            style={{
              padding: '12px 24px',
              background: loading ? '#9ca3af' : '#059669',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: '600',
              cursor: loading ? 'not-allowed' : 'pointer',
            }}
          >
            Diagnostic Planning
          </button>

          <button
            onClick={testRapportsDebug}
            disabled={loading}
            style={{
              padding: '12px 24px',
              background: loading ? '#9ca3af' : '#dc2626',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: '600',
              cursor: loading ? 'not-allowed' : 'pointer',
            }}
          >
            Diagnostic Rapports
          </button>

          {(backendReport || frontendTests.length > 0 || planningDebug || rapportsDebug) && (
            <button
              onClick={exportReport}
              style={{
                padding: '12px 24px',
                background: '#10b981',
                color: 'white',
                border: 'none',
                borderRadius: '8px',
                fontSize: '14px',
                fontWeight: '600',
                cursor: 'pointer',
              }}
            >
              📥 Exporter JSON
            </button>
          )}
        </div>

        {loading && (
          <div style={{ textAlign: 'center', padding: '40px' }}>
            <LoadingSpinner />
            <p style={{ marginTop: '16px', color: '#6b7280' }}>Test en cours...</p>
          </div>
        )}

        {/* Statistiques Backend */}
        {backendReport && !loading && (
          <div style={{ marginBottom: '24px' }}>
            <h3 style={{ fontSize: '18px', fontWeight: '700', marginBottom: '16px' }}>
              📊 Statistiques Backend
            </h3>
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))',
                gap: '12px',
                marginBottom: '16px',
              }}
            >
              <div
                style={{
                  background: '#f9fafb',
                  padding: '16px',
                  borderRadius: '8px',
                  textAlign: 'center',
                }}
              >
                <div style={{ fontSize: '24px', fontWeight: '700', color: '#0891b2' }}>
                  {backendReport.stats.total}
                </div>
                <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>Total</div>
              </div>
              <div
                style={{
                  background: '#f0fdf4',
                  padding: '16px',
                  borderRadius: '8px',
                  textAlign: 'center',
                }}
              >
                <div style={{ fontSize: '24px', fontWeight: '700', color: '#10b981' }}>
                  {backendReport.stats.ok}
                </div>
                <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>OK</div>
              </div>
              <div
                style={{
                  background: '#fffbeb',
                  padding: '16px',
                  borderRadius: '8px',
                  textAlign: 'center',
                }}
              >
                <div style={{ fontSize: '24px', fontWeight: '700', color: '#f59e0b' }}>
                  {backendReport.stats.warning}
                </div>
                <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>Warning</div>
              </div>
              <div
                style={{
                  background: '#fef2f2',
                  padding: '16px',
                  borderRadius: '8px',
                  textAlign: 'center',
                }}
              >
                <div style={{ fontSize: '24px', fontWeight: '700', color: '#ef4444' }}>
                  {backendReport.stats.error}
                </div>
                <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>Erreurs</div>
              </div>
              <div
                style={{
                  background: '#f9fafb',
                  padding: '16px',
                  borderRadius: '8px',
                  textAlign: 'center',
                }}
              >
                <div style={{ fontSize: '24px', fontWeight: '700', color: '#6b7280' }}>
                  {Math.round(backendReport.stats.total_time_ms)}ms
                </div>
                <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>Temps total</div>
              </div>
            </div>

            {/* Configuration Checks */}
            {backendReport.config_checks && backendReport.config_checks.length > 0 && (
              <div style={{ marginBottom: '16px' }}>
                <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                  Configuration Système
                </h4>
                <div style={{ background: 'white', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e5e7eb' }}>
                  {backendReport.config_checks.map((check, idx) => (
                    <div
                      key={idx}
                      style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        padding: '12px 16px',
                        borderBottom: idx < backendReport.config_checks.length - 1 ? '1px solid #e5e7eb' : 'none',
                      }}
                    >
                      <span style={{ fontSize: '14px' }}>{check.name}</span>
                      <span
                        style={{
                          fontSize: '14px',
                          fontWeight: '600',
                          color: getStatusColor(check.status),
                        }}
                      >
                        {getStatusIcon(check.status)} {check.value}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Test Results */}
            <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>Résultats des tests</h4>
            <div style={{ background: 'white', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e5e7eb' }}>
              {backendReport.test_results.map((result, idx) => (
                <details
                  key={idx}
                  style={{
                    borderBottom:
                      idx < backendReport.test_results.length - 1 ? '1px solid #e5e7eb' : 'none',
                  }}
                >
                  <summary
                    style={{
                      padding: '12px 16px',
                      cursor: 'pointer',
                      display: 'flex',
                      justifyContent: 'space-between',
                      alignItems: 'center',
                    }}
                  >
                    <span style={{ fontSize: '14px', fontWeight: '500' }}>
                      {getStatusIcon(result.status)} {result.name}
                    </span>
                    <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
                      {result.http_code && (
                        <span
                          style={{
                            fontSize: '12px',
                            padding: '2px 8px',
                            background: '#f3f4f6',
                            borderRadius: '4px',
                          }}
                        >
                          {result.http_code}
                        </span>
                      )}
                      {result.response_time_ms && (
                        <span style={{ fontSize: '12px', color: '#6b7280' }}>
                          {result.response_time_ms}ms
                        </span>
                      )}
                      <span
                        style={{
                          fontSize: '12px',
                          fontWeight: '600',
                          color: getStatusColor(result.status),
                        }}
                      >
                        {result.status}
                      </span>
                    </div>
                  </summary>
                  <div style={{ padding: '12px 16px', background: '#f9fafb' }}>
                    {result.initial_url && (
                      <div style={{ marginBottom: '8px' }}>
                        <strong style={{ fontSize: '12px', color: '#0891b2' }}>URL initiale:</strong>
                        <div
                          style={{
                            fontSize: '11px',
                            color: '#374151',
                            marginTop: '4px',
                            fontFamily: 'monospace',
                            wordBreak: 'break-all',
                          }}
                        >
                          {result.initial_url}
                        </div>
                      </div>
                    )}
                    {result.final_url && result.final_url !== result.initial_url && (
                      <div style={{ marginBottom: '8px' }}>
                        <strong style={{ fontSize: '12px', color: '#8b5cf6' }}>
                          URL finale {result.redirect_count ? `(${result.redirect_count} redirect${result.redirect_count > 1 ? 's' : ''})` : ''}:
                        </strong>
                        <div
                          style={{
                            fontSize: '11px',
                            color: '#374151',
                            marginTop: '4px',
                            fontFamily: 'monospace',
                            wordBreak: 'break-all',
                          }}
                        >
                          {result.final_url}
                        </div>
                      </div>
                    )}
                    {result.error && (
                      <div style={{ marginBottom: '8px' }}>
                        <strong style={{ fontSize: '12px', color: '#ef4444' }}>Erreur:</strong>
                        <div
                          style={{
                            fontSize: '12px',
                            color: '#374151',
                            marginTop: '4px',
                            fontFamily: 'monospace',
                          }}
                        >
                          {result.error}
                        </div>
                      </div>
                    )}
                    {result.response_preview && (
                      <div>
                        <strong style={{ fontSize: '12px' }}>Réponse:</strong>
                        <pre
                          style={{
                            fontSize: '11px',
                            background: '#1f2937',
                            color: '#f3f4f6',
                            padding: '8px',
                            borderRadius: '4px',
                            marginTop: '4px',
                            overflow: 'auto',
                            maxHeight: '200px',
                          }}
                        >
                          {JSON.stringify(result.response_preview, null, 2)}
                        </pre>
                      </div>
                    )}
                  </div>
                </details>
              ))}
            </div>
          </div>
        )}

        {/* Frontend Tests */}
        {frontendTests.length > 0 && !loading && (
          <div style={{ marginBottom: '24px' }}>
            <h3 style={{ fontSize: '18px', fontWeight: '700', marginBottom: '16px' }}>
              🌐 Tests Frontend
            </h3>
            <div style={{ background: 'white', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e5e7eb' }}>
              {frontendTests.map((result, idx) => (
                <div
                  key={idx}
                  style={{
                    padding: '12px 16px',
                    borderBottom: idx < frontendTests.length - 1 ? '1px solid #e5e7eb' : 'none',
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                  }}
                >
                  <span style={{ fontSize: '14px' }}>
                    {getStatusIcon(result.status)} {result.name}
                  </span>
                  <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
                    {result.http_code && (
                      <span
                        style={{
                          fontSize: '12px',
                          padding: '2px 8px',
                          background: '#f3f4f6',
                          borderRadius: '4px',
                        }}
                      >
                        {result.http_code}
                      </span>
                    )}
                    {result.response_time_ms && (
                      <span style={{ fontSize: '12px', color: '#6b7280' }}>
                        {result.response_time_ms}ms
                      </span>
                    )}
                    <span
                      style={{
                        fontSize: '12px',
                        fontWeight: '600',
                        color: getStatusColor(result.status),
                      }}
                    >
                      {result.status}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Planning Debug */}
        {planningDebug && !loading && (
          <div style={{ marginBottom: '24px' }}>
            <h3 style={{ fontSize: '18px', fontWeight: '700', marginBottom: '16px' }}>
              📅 Diagnostic Planning & Lien Utilisateur
            </h3>

            {/* User Info */}
            <div style={{ marginBottom: '16px' }}>
              <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                Utilisateur connecté
              </h4>
              <div style={{ background: 'white', borderRadius: '8px', padding: '12px', border: '1px solid #e5e7eb' }}>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>Mode auth:</strong> {planningDebug.user_info.auth_mode}
                </div>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>Email:</strong> {planningDebug.user_info.email}
                </div>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>Nom:</strong> {planningDebug.user_info.name}
                </div>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>ID Mobile:</strong> {planningDebug.user_info.mobile_user_id || 'N/A'}
                </div>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>ID Dolibarr:</strong>{' '}
                  <span
                    style={{
                      color: planningDebug.user_info.dolibarr_user_id ? '#059669' : '#ef4444',
                      fontWeight: '600',
                    }}
                  >
                    {planningDebug.user_info.dolibarr_user_id || 'NON LIÉ'}
                  </span>
                </div>
                {planningDebug.user_info.is_unlinked && (
                  <div
                    style={{
                      marginTop: '8px',
                      padding: '8px',
                      background: '#fef2f2',
                      borderRadius: '6px',
                      fontSize: '12px',
                      color: '#991b1b',
                    }}
                  >
                    ⚠️ Compte non lié à un utilisateur Dolibarr. Le planning sera vide.
                  </div>
                )}
              </div>
            </div>

            {/* Dolibarr User */}
            {planningDebug.dolibarr_user && (
              <div style={{ marginBottom: '16px' }}>
                <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                  Utilisateur Dolibarr lié
                </h4>
                <div style={{ background: 'white', borderRadius: '8px', padding: '12px', border: '1px solid #e5e7eb' }}>
                  <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                    <strong>Login:</strong> {planningDebug.dolibarr_user.login}
                  </div>
                  <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                    <strong>Nom:</strong> {planningDebug.dolibarr_user.firstname}{' '}
                    {planningDebug.dolibarr_user.lastname}
                  </div>
                  <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                    <strong>Statut:</strong>{' '}
                    <span
                      style={{
                        color: planningDebug.dolibarr_user.statut === 1 ? '#059669' : '#ef4444',
                        fontWeight: '600',
                      }}
                    >
                      {planningDebug.dolibarr_user.statut_label}
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* Events Stats */}
            {planningDebug.events_stats && (
              <div style={{ marginBottom: '16px' }}>
                <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                  Statistiques événements
                </h4>
                <div
                  style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(120px, 1fr))',
                    gap: '8px',
                  }}
                >
                  <div
                    style={{
                      background: 'white',
                      padding: '12px',
                      borderRadius: '8px',
                      border: '1px solid #e5e7eb',
                      textAlign: 'center',
                    }}
                  >
                    <div style={{ fontSize: '20px', fontWeight: '700', color: '#0891b2' }}>
                      {planningDebug.events_stats.as_author || 0}
                    </div>
                    <div style={{ fontSize: '11px', color: '#6b7280', marginTop: '4px' }}>Créés</div>
                  </div>
                  <div
                    style={{
                      background: 'white',
                      padding: '12px',
                      borderRadius: '8px',
                      border: '1px solid #e5e7eb',
                      textAlign: 'center',
                    }}
                  >
                    <div style={{ fontSize: '20px', fontWeight: '700', color: '#059669' }}>
                      {planningDebug.events_stats.as_action_user || 0}
                    </div>
                    <div style={{ fontSize: '11px', color: '#6b7280', marginTop: '4px' }}>Assignés</div>
                  </div>
                  <div
                    style={{
                      background: 'white',
                      padding: '12px',
                      borderRadius: '8px',
                      border: '1px solid #e5e7eb',
                      textAlign: 'center',
                    }}
                  >
                    <div style={{ fontSize: '20px', fontWeight: '700', color: '#8b5cf6' }}>
                      {planningDebug.events_stats.as_resource || 0}
                    </div>
                    <div style={{ fontSize: '11px', color: '#6b7280', marginTop: '4px' }}>Ressources</div>
                  </div>
                  <div
                    style={{
                      background: '#f0fdf4',
                      padding: '12px',
                      borderRadius: '8px',
                      border: '2px solid #059669',
                      textAlign: 'center',
                    }}
                  >
                    <div style={{ fontSize: '20px', fontWeight: '700', color: '#059669' }}>
                      {planningDebug.events_stats.total_in_period || 0}
                    </div>
                    <div style={{ fontSize: '11px', color: '#047857', marginTop: '4px' }}>Total période</div>
                  </div>
                </div>
              </div>
            )}

            {/* Diagnostic */}
            {planningDebug.diagnostic && planningDebug.diagnostic.length > 0 && (
              <div style={{ marginBottom: '16px' }}>
                <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>Diagnostic</h4>
                <div style={{ background: 'white', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e5e7eb' }}>
                  {planningDebug.diagnostic.map((diag, idx) => (
                    <div
                      key={idx}
                      style={{
                        padding: '12px',
                        borderBottom:
                          idx < planningDebug.diagnostic.length - 1 ? '1px solid #e5e7eb' : 'none',
                        background:
                          diag.type === 'ERROR'
                            ? '#fef2f2'
                            : diag.type === 'WARNING'
                            ? '#fffbeb'
                            : '#f0fdf4',
                      }}
                    >
                      <div
                        style={{
                          fontSize: '13px',
                          fontWeight: '600',
                          color:
                            diag.type === 'ERROR'
                              ? '#991b1b'
                              : diag.type === 'WARNING'
                              ? '#92400e'
                              : '#047857',
                          marginBottom: '4px',
                        }}
                      >
                        {getStatusIcon(diag.type)} {diag.message}
                      </div>
                      {diag.solution && (
                        <div
                          style={{
                            fontSize: '12px',
                            color: '#374151',
                            fontFamily: 'monospace',
                            marginTop: '4px',
                          }}
                        >
                          {diag.solution}
                        </div>
                      )}
                      {diag.explanation && (
                        <ul style={{ marginTop: '8px', paddingLeft: '20px', fontSize: '12px', color: '#6b7280' }}>
                          {diag.explanation.map((exp: string, i: number) => (
                            <li key={i}>{exp}</li>
                          ))}
                        </ul>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Event Samples */}
            {planningDebug.events_samples && planningDebug.events_samples.length > 0 && (
              <div>
                <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                  Exemples d'événements (max 5)
                </h4>
                <div style={{ background: 'white', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e5e7eb' }}>
                  {planningDebug.events_samples.map((event, idx) => (
                    <details
                      key={idx}
                      style={{
                        borderBottom:
                          idx < planningDebug.events_samples.length - 1 ? '1px solid #e5e7eb' : 'none',
                      }}
                    >
                      <summary
                        style={{
                          padding: '12px',
                          cursor: 'pointer',
                          fontSize: '13px',
                          fontWeight: '600',
                        }}
                      >
                        {event.label} - {event.datep}
                      </summary>
                      <div style={{ padding: '12px', background: '#f9fafb', fontSize: '12px' }}>
                        <div><strong>Client:</strong> {event.client || 'N/A'}</div>
                        <div><strong>Projet:</strong> {event.projet || 'N/A'}</div>
                        <div><strong>Auteur ID:</strong> {event.fk_user_author}</div>
                        <div><strong>Assigné ID:</strong> {event.fk_user_action}</div>
                        <div><strong>Terminé ID:</strong> {event.fk_user_done}</div>
                      </div>
                    </details>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}

        {/* Rapports Debug */}
        {rapportsDebug && !loading && (
          <div style={{ marginBottom: '24px' }}>
            <h3 style={{ fontSize: '18px', fontWeight: '700', marginBottom: '16px' }}>
              📋 Diagnostic Rapports
            </h3>

            {/* Recommendation Box */}
            {rapportsDebug.recommendation && (
              <div
                style={{
                  background: '#fef2f2',
                  border: '2px solid #ef4444',
                  borderRadius: '8px',
                  padding: '16px',
                  marginBottom: '16px',
                }}
              >
                <div style={{ fontSize: '14px', fontWeight: '700', color: '#991b1b', marginBottom: '8px' }}>
                  ⚠️ PROBLÈME DÉTECTÉ
                </div>
                <div style={{ fontSize: '14px', color: '#7f1d1d', marginBottom: '12px' }}>
                  {rapportsDebug.recommendation}
                </div>
                {rapportsDebug.solution && (
                  <div
                    style={{
                      fontSize: '13px',
                      color: '#7f1d1d',
                      background: '#fee2e2',
                      padding: '8px',
                      borderRadius: '4px',
                      fontFamily: 'monospace',
                    }}
                  >
                    <strong>Solution:</strong> {rapportsDebug.solution}
                  </div>
                )}
              </div>
            )}

            {/* User Info */}
            <div style={{ marginBottom: '16px' }}>
              <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                Utilisateur connecté
              </h4>
              <div style={{ background: 'white', borderRadius: '8px', padding: '12px', border: '1px solid #e5e7eb' }}>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>Mode auth:</strong> {rapportsDebug.debug_info.user_info?.mode || 'N/A'}
                </div>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>Email:</strong> {rapportsDebug.debug_info.user_info?.email || 'N/A'}
                </div>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>User ID Dolibarr:</strong>{' '}
                  <span
                    style={{
                      color: rapportsDebug.debug_info.user_info?.user_id ? '#059669' : '#ef4444',
                      fontWeight: '600',
                    }}
                  >
                    {rapportsDebug.debug_info.user_info?.user_id || 'NULL'}
                  </span>
                </div>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>Mobile User ID:</strong> {rapportsDebug.debug_info.user_info?.mobile_user_id || 'N/A'}
                </div>
                <div style={{ fontSize: '13px', marginBottom: '6px' }}>
                  <strong>Est non lié:</strong>{' '}
                  <span style={{ color: rapportsDebug.debug_info.user_info?.is_unlinked ? '#ef4444' : '#059669' }}>
                    {rapportsDebug.debug_info.user_info?.is_unlinked ? 'OUI' : 'NON'}
                  </span>
                </div>
              </div>
            </div>

            {/* Stats */}
            <div style={{ marginBottom: '16px' }}>
              <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                Statistiques Rapports
              </h4>
              <div
                style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))',
                  gap: '12px',
                }}
              >
                <div style={{ background: '#f0fdf4', padding: '12px', borderRadius: '8px', border: '1px solid #059669', textAlign: 'center' }}>
                  <div style={{ fontSize: '24px', fontWeight: '700', color: '#059669' }}>
                    {rapportsDebug.debug_info.total_rapports}
                  </div>
                  <div style={{ fontSize: '12px', color: '#047857', marginTop: '4px' }}>Total rapports (entité)</div>
                </div>
                <div style={{ background: rapportsDebug.debug_info.rapports_with_filter > 0 ? '#f0fdf4' : '#fef2f2', padding: '12px', borderRadius: '8px', border: `2px solid ${rapportsDebug.debug_info.rapports_with_filter > 0 ? '#059669' : '#ef4444'}`, textAlign: 'center' }}>
                  <div style={{ fontSize: '24px', fontWeight: '700', color: rapportsDebug.debug_info.rapports_with_filter > 0 ? '#059669' : '#ef4444' }}>
                    {rapportsDebug.debug_info.rapports_with_filter}
                  </div>
                  <div style={{ fontSize: '12px', color: rapportsDebug.debug_info.rapports_with_filter > 0 ? '#047857' : '#991b1b', marginTop: '4px' }}>Visibles (avec filtre)</div>
                </div>
              </div>
              <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '8px', textAlign: 'center' }}>
                <strong>Filtre appliqué:</strong> {rapportsDebug.debug_info.filter_applied}
              </div>
            </div>

            {/* Rapports par utilisateur */}
            {Object.keys(rapportsDebug.debug_info.rapports_by_user).length > 0 && (
              <div style={{ marginBottom: '16px' }}>
                <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                  Rapports par utilisateur
                </h4>
                <div style={{ background: 'white', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e5e7eb' }}>
                  {Object.entries(rapportsDebug.debug_info.rapports_by_user).map(([user_id, count], idx) => (
                    <div
                      key={user_id}
                      style={{
                        padding: '8px 12px',
                        borderBottom: idx < Object.keys(rapportsDebug.debug_info.rapports_by_user).length - 1 ? '1px solid #e5e7eb' : 'none',
                        display: 'flex',
                        justifyContent: 'space-between',
                      }}
                    >
                      <span style={{ fontSize: '13px' }}>
                        <strong>User ID {user_id}</strong>
                        {rapportsDebug.debug_info.user_info?.user_id && parseInt(user_id) === rapportsDebug.debug_info.user_info.user_id && (
                          <span style={{ color: '#059669', marginLeft: '8px' }}>(vous)</span>
                        )}
                      </span>
                      <span style={{ fontSize: '13px', fontWeight: '600', color: '#0891b2' }}>{count} rapport(s)</span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Recent rapports */}
            {rapportsDebug.debug_info.recent_rapports && rapportsDebug.debug_info.recent_rapports.length > 0 && (
              <div>
                <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
                  5 derniers rapports (sans filtre)
                </h4>
                <div style={{ background: 'white', borderRadius: '8px', overflow: 'hidden', border: '1px solid #e5e7eb' }}>
                  {rapportsDebug.debug_info.recent_rapports.map((rapport, idx) => (
                    <div
                      key={rapport.rowid}
                      style={{
                        padding: '12px',
                        borderBottom: idx < rapportsDebug.debug_info.recent_rapports.length - 1 ? '1px solid #e5e7eb' : 'none',
                      }}
                    >
                      <div style={{ fontSize: '14px', fontWeight: '600', marginBottom: '4px' }}>
                        {rapport.ref || `Rapport #${rapport.rowid}`}
                      </div>
                      <div style={{ fontSize: '12px', color: '#6b7280' }}>
                        <strong>Projet:</strong> {rapport.projet_title || 'N/A'}
                      </div>
                      <div style={{ fontSize: '12px', color: '#6b7280' }}>
                        <strong>Date:</strong> {rapport.date_rapport}
                      </div>
                      <div style={{ fontSize: '12px', color: '#6b7280' }}>
                        <strong>fk_user:</strong>{' '}
                        <span
                          style={{
                            color: rapport.fk_user === rapportsDebug.debug_info.user_info?.user_id ? '#059669' : '#6b7280',
                            fontWeight: rapport.fk_user === rapportsDebug.debug_info.user_info?.user_id ? '700' : '400',
                          }}
                        >
                          {rapport.fk_user}
                        </span>
                        {rapport.fk_user === rapportsDebug.debug_info.user_info?.user_id && (
                          <span style={{ color: '#059669', marginLeft: '4px' }}>(correspond!)</span>
                        )}
                      </div>
                      <div style={{ fontSize: '12px', color: '#6b7280' }}>
                        <strong>Ouvrier:</strong> {rapport.user_name || rapport.user_login || 'N/A'}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Diagnostic Summary */}
            {rapportsDebug.diagnostic_summary && (
              <div style={{ marginTop: '24px' }}>
                <h4 style={{ fontSize: '16px', fontWeight: '700', marginBottom: '12px' }}>🎯 Résumé Diagnostic</h4>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '12px' }}>
                  <div
                    style={{
                      background: rapportsDebug.diagnostic_summary.table_exists ? '#f0fdf4' : '#fef2f2',
                      padding: '12px',
                      borderRadius: '8px',
                      border: `2px solid ${rapportsDebug.diagnostic_summary.table_exists ? '#059669' : '#ef4444'}`,
                      textAlign: 'center',
                    }}
                  >
                    <div style={{ fontSize: '20px', marginBottom: '4px' }}>
                      {rapportsDebug.diagnostic_summary.table_exists ? '✓' : '✗'}
                    </div>
                    <div style={{ fontSize: '12px', fontWeight: '600' }}>Table Existe</div>
                  </div>
                  <div
                    style={{
                      background: rapportsDebug.diagnostic_summary.all_columns_present ? '#f0fdf4' : '#fef2f2',
                      padding: '12px',
                      borderRadius: '8px',
                      border: `2px solid ${rapportsDebug.diagnostic_summary.all_columns_present ? '#059669' : '#ef4444'}`,
                      textAlign: 'center',
                    }}
                  >
                    <div style={{ fontSize: '20px', marginBottom: '4px' }}>
                      {rapportsDebug.diagnostic_summary.all_columns_present ? '✓' : '✗'}
                    </div>
                    <div style={{ fontSize: '12px', fontWeight: '600' }}>Colonnes OK</div>
                  </div>
                  <div
                    style={{
                      background: rapportsDebug.diagnostic_summary.api_query_works ? '#f0fdf4' : '#fef2f2',
                      padding: '12px',
                      borderRadius: '8px',
                      border: `2px solid ${rapportsDebug.diagnostic_summary.api_query_works ? '#059669' : '#ef4444'}`,
                      textAlign: 'center',
                    }}
                  >
                    <div style={{ fontSize: '20px', marginBottom: '4px' }}>
                      {rapportsDebug.diagnostic_summary.api_query_works ? '✓' : '✗'}
                    </div>
                    <div style={{ fontSize: '12px', fontWeight: '600' }}>Requête API OK</div>
                  </div>
                  <div
                    style={{
                      background: rapportsDebug.diagnostic_summary.ready_for_production ? '#f0fdf4' : '#fef2f2',
                      padding: '12px',
                      borderRadius: '8px',
                      border: `2px solid ${rapportsDebug.diagnostic_summary.ready_for_production ? '#059669' : '#ef4444'}`,
                      textAlign: 'center',
                    }}
                  >
                    <div style={{ fontSize: '20px', marginBottom: '4px' }}>
                      {rapportsDebug.diagnostic_summary.ready_for_production ? '✓' : '✗'}
                    </div>
                    <div style={{ fontSize: '12px', fontWeight: '600' }}>Prêt Production</div>
                  </div>
                </div>
              </div>
            )}

            {/* Table Structure */}
            {rapportsDebug.table_structure && (
              <div style={{ marginTop: '24px' }}>
                <h4 style={{ fontSize: '16px', fontWeight: '700', marginBottom: '12px' }}>🗄️ Structure de la Table</h4>
                <div style={{ background: '#f9fafb', padding: '16px', borderRadius: '8px', border: '1px solid #e5e7eb' }}>
                  <div style={{ marginBottom: '12px' }}>
                    <strong>Table:</strong> <code style={{ background: '#e5e7eb', padding: '2px 6px', borderRadius: '4px' }}>{rapportsDebug.table_structure.table_name}</code>
                  </div>
                  <div style={{ marginBottom: '12px' }}>
                    <strong>Nombre de colonnes:</strong> {rapportsDebug.table_structure.total_columns}
                  </div>

                  {rapportsDebug.table_structure.missing_columns.length > 0 && (
                    <div style={{ background: '#fef2f2', padding: '12px', borderRadius: '8px', border: '2px solid #ef4444', marginTop: '12px' }}>
                      <div style={{ fontWeight: '700', color: '#991b1b', marginBottom: '8px' }}>
                        ❌ Colonnes Manquantes ({rapportsDebug.table_structure.missing_columns.length})
                      </div>
                      <div style={{ display: 'flex', flexWrap: 'wrap', gap: '6px' }}>
                        {rapportsDebug.table_structure.missing_columns.map((col, idx) => (
                          <code key={idx} style={{ background: '#fee2e2', color: '#991b1b', padding: '4px 8px', borderRadius: '4px', fontSize: '12px' }}>
                            {col}
                          </code>
                        ))}
                      </div>
                    </div>
                  )}

                  <details style={{ marginTop: '12px' }}>
                    <summary style={{ cursor: 'pointer', fontWeight: '600', color: '#0891b2' }}>
                      Voir toutes les colonnes existantes ({rapportsDebug.table_structure.existing_columns.length})
                    </summary>
                    <div style={{ marginTop: '8px', display: 'flex', flexWrap: 'wrap', gap: '6px' }}>
                      {rapportsDebug.table_structure.existing_columns.map((col, idx) => {
                        const isMissing = rapportsDebug.table_structure!.expected_columns.includes(col);
                        return (
                          <code key={idx} style={{ background: isMissing ? '#d1fae5' : '#e5e7eb', color: isMissing ? '#047857' : '#374151', padding: '4px 8px', borderRadius: '4px', fontSize: '12px' }}>
                            {col}
                          </code>
                        );
                      })}
                    </div>
                  </details>
                </div>
              </div>
            )}

            {/* API Test Result */}
            {rapportsDebug.api_test && (
              <div style={{ marginTop: '24px' }}>
                <h4 style={{ fontSize: '16px', fontWeight: '700', marginBottom: '12px' }}>🧪 Test Requête API</h4>
                <div
                  style={{
                    background: rapportsDebug.api_test.success ? '#f0fdf4' : '#fef2f2',
                    padding: '16px',
                    borderRadius: '8px',
                    border: `2px solid ${rapportsDebug.api_test.success ? '#059669' : '#ef4444'}`,
                  }}
                >
                  <div style={{ marginBottom: '12px' }}>
                    <strong style={{ color: rapportsDebug.api_test.success ? '#047857' : '#991b1b' }}>
                      {rapportsDebug.api_test.success ? '✓ Requête réussie' : '✗ Requête échouée'}
                    </strong>
                  </div>

                  {rapportsDebug.api_test.error && (
                    <div style={{ background: '#fee2e2', padding: '12px', borderRadius: '6px', marginBottom: '12px' }}>
                      <div style={{ fontWeight: '700', color: '#991b1b', marginBottom: '4px' }}>Erreur SQL:</div>
                      <code style={{ fontSize: '12px', color: '#7f1d1d', wordBreak: 'break-word' }}>{rapportsDebug.api_test.error}</code>
                    </div>
                  )}

                  {rapportsDebug.api_test.success && rapportsDebug.api_test.rows_returned !== undefined && (
                    <div style={{ marginBottom: '12px' }}>
                      <strong>Lignes retournées:</strong> {rapportsDebug.api_test.rows_returned}
                    </div>
                  )}

                  {rapportsDebug.api_test.sql_query && (
                    <details>
                      <summary style={{ cursor: 'pointer', fontWeight: '600', color: '#0891b2' }}>Voir la requête SQL</summary>
                      <pre style={{ background: '#1f2937', color: '#f9fafb', padding: '12px', borderRadius: '6px', fontSize: '11px', overflow: 'auto', marginTop: '8px' }}>
                        {rapportsDebug.api_test.sql_query}
                      </pre>
                    </details>
                  )}
                </div>
              </div>
            )}

            {/* Fix SQL */}
            {rapportsDebug.fix_sql && rapportsDebug.fix_sql.length > 0 && (
              <div style={{ marginTop: '24px' }}>
                <h4 style={{ fontSize: '16px', fontWeight: '700', marginBottom: '12px' }}>🔧 Corrections SQL Suggérées</h4>
                <div style={{ background: '#fef3c7', padding: '16px', borderRadius: '8px', border: '2px solid #f59e0b' }}>
                  <div style={{ marginBottom: '12px', color: '#78350f' }}>
                    <strong>⚠️ Exécuter ces commandes SQL pour corriger la structure de la table :</strong>
                  </div>
                  <pre style={{ background: '#1f2937', color: '#10b981', padding: '12px', borderRadius: '6px', fontSize: '11px', overflow: 'auto', whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
                    {rapportsDebug.fix_sql.join('\n\n')}
                  </pre>
                  <div style={{ marginTop: '12px', fontSize: '12px', color: '#92400e' }}>
                    💡 <strong>Astuce:</strong> Copiez ces commandes et exécutez-les dans phpMyAdmin ou via SSH.
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {/* Outils */}
        <div style={{ marginTop: '32px' }}>
          <h3 style={{ fontSize: '18px', fontWeight: '700', marginBottom: '16px' }}>🛠️ Outils</h3>
          <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
            <button
              onClick={toggleDebugMode}
              style={{
                padding: '8px 16px',
                background: debugMode ? '#ef4444' : '#10b981',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                fontSize: '14px',
                fontWeight: '600',
                cursor: 'pointer',
              }}
            >
              {debugMode ? 'Désactiver debug console' : 'Activer debug console'}
            </button>
            <button
              onClick={clearToken}
              style={{
                padding: '8px 16px',
                background: '#ef4444',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                fontSize: '14px',
                fontWeight: '600',
                cursor: 'pointer',
              }}
            >
              Effacer token
            </button>
            <button
              onClick={() => window.location.reload()}
              style={{
                padding: '8px 16px',
                background: '#6b7280',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                fontSize: '14px',
                fontWeight: '600',
                cursor: 'pointer',
              }}
            >
              Recharger la page
            </button>
          </div>
        </div>

        {/* Info utilisateur */}
        <div style={{ marginTop: '32px' }}>
          <h3 style={{ fontSize: '18px', fontWeight: '700', marginBottom: '16px' }}>👤 Session</h3>
          <div style={{ background: 'white', borderRadius: '8px', padding: '16px', border: '1px solid #e5e7eb' }}>
            <div style={{ fontSize: '14px', marginBottom: '8px' }}>
              <strong>Email:</strong> {user?.email || 'N/A'}
            </div>
            <div style={{ fontSize: '14px', marginBottom: '8px' }}>
              <strong>Nom:</strong> {user?.name || 'N/A'}
            </div>
            <div style={{ fontSize: '14px', marginBottom: '8px' }}>
              <strong>Token:</strong>{' '}
              {storage.getToken() ? storage.getToken()!.substring(0, 30) + '...' : 'Aucun'}
            </div>
            <div style={{ fontSize: '14px' }}>
              <strong>Debug mode:</strong> {debugMode ? '✓ Activé' : '✗ Désactivé'}
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}
