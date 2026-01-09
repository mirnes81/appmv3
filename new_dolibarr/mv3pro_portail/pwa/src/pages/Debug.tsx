import { useState } from 'react';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { useAuth } from '../contexts/AuthContext';
import { storage } from '../lib/api';
import { API_PATHS } from '../config';

interface TestResult {
  name: string;
  status: 'OK' | 'WARNING' | 'ERROR' | 'PENDING';
  http_code?: number;
  response_time_ms?: number;
  error?: string;
  response_preview?: any;
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

export function Debug() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);
  const [backendReport, setBackendReport] = useState<BackendReport | null>(null);
  const [frontendTests, setFrontendTests] = useState<TestResult[]>([]);
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

          {(backendReport || frontendTests.length > 0) && (
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
