import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { LoadingSpinner } from '../components/LoadingSpinner';

interface DebugInfo {
  timestamp: string;
  requestUrl: string;
  requestMethod: string;
  requestHeaders: Record<string, string>;
  requestBody: any;
  responseStatus: number;
  responseHeaders: Record<string, string>;
  responseBody: any;
  errorDetails?: any;
}

export function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [hint, setHint] = useState('');
  const [loading, setLoading] = useState(false);
  const [debugMode, setDebugMode] = useState(false);
  const [debugInfo, setDebugInfo] = useState<DebugInfo | null>(null);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setHint('');
    setDebugInfo(null);
    setLoading(true);

    if (debugMode) {
      await handleDebugLogin();
    } else {
      try {
        await login(email, password);
        navigate('/dashboard', { replace: true });
      } catch (err: any) {
        setError(err.message || 'Erreur de connexion');
        if (err.hint) {
          setHint(err.hint);
        }
      } finally {
        setLoading(false);
      }
    }
  };

  const handleDebugLogin = async () => {
    const url = '/custom/mv3pro_portail/mobile_app/api/auth.php?action=login';
    const requestBody = { email, password };
    const requestHeaders = { 'Content-Type': 'application/json' };

    const debug: DebugInfo = {
      timestamp: new Date().toISOString(),
      requestUrl: url,
      requestMethod: 'POST',
      requestHeaders,
      requestBody: {
        email,
        password: `[${password.length} chars] ${password.slice(0, 3)}...`
      },
      responseStatus: 0,
      responseHeaders: {},
      responseBody: null,
    };

    try {
      console.log('[DEBUG] Starting login request', {
        email,
        passwordLength: password.length,
        url
      });

      const response = await fetch(url, {
        method: 'POST',
        headers: requestHeaders,
        body: JSON.stringify(requestBody),
      });

      debug.responseStatus = response.status;

      const headersObj: Record<string, string> = {};
      response.headers.forEach((value, key) => {
        headersObj[key] = value;
      });
      debug.responseHeaders = headersObj;

      const responseText = await response.text();
      console.log('[DEBUG] Response received', {
        status: response.status,
        headers: headersObj,
        bodyLength: responseText.length,
        bodyPreview: responseText.slice(0, 200)
      });

      try {
        debug.responseBody = JSON.parse(responseText);
      } catch {
        debug.responseBody = { _raw: responseText };
      }

      setDebugInfo(debug);

      if (response.ok && debug.responseBody?.success) {
        console.log('[DEBUG] Login SUCCESS', debug.responseBody);
        if (debug.responseBody.token) {
          localStorage.setItem('mv3pro_token', debug.responseBody.token);
        }
      } else {
        console.log('[DEBUG] Login FAILED', debug.responseBody);
        setError(debug.responseBody?.message || `Erreur ${response.status}`);
        if (debug.responseBody?.hint) {
          setHint(debug.responseBody.hint);
        }
      }
    } catch (err: any) {
      console.error('[DEBUG] Request ERROR', err);
      debug.errorDetails = {
        name: err.name,
        message: err.message,
        stack: err.stack,
      };
      setDebugInfo(debug);
      setError('Erreur réseau: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div
      style={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '20px',
        background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
      }}
    >
      <div
        style={{
          width: '100%',
          maxWidth: debugMode ? '900px' : '400px',
          background: 'white',
          borderRadius: '16px',
          padding: '32px',
          boxShadow: '0 10px 30px rgba(0,0,0,0.2)',
        }}
      >
        <div style={{ textAlign: 'center', marginBottom: '32px' }}>
          <div style={{ fontSize: '48px', marginBottom: '16px' }}>🏗️</div>
          <h1 style={{ fontSize: '24px', fontWeight: '700', color: '#0891b2' }}>
            MV3 PRO Mobile
          </h1>
          <p style={{ color: '#6b7280', marginTop: '8px' }}>
            Connexion espace mobile
          </p>
          <button
            type="button"
            onClick={() => setDebugMode(!debugMode)}
            style={{
              marginTop: '16px',
              padding: '8px 16px',
              fontSize: '12px',
              background: debugMode ? '#dc2626' : '#6b7280',
              color: 'white',
              border: 'none',
              borderRadius: '6px',
              cursor: 'pointer',
              fontWeight: '500',
            }}
          >
            {debugMode ? '🔍 Debug ON' : '🔍 Debug OFF'}
          </button>
        </div>

        {error && (
          <div className="alert alert-error" style={{ marginBottom: '20px' }}>
            <div style={{ fontWeight: '600', marginBottom: hint ? '8px' : '0' }}>
              {error}
            </div>
            {hint && (
              <div style={{
                fontSize: '13px',
                opacity: 0.9,
                marginTop: '8px',
                paddingTop: '8px',
                borderTop: '1px solid rgba(255,255,255,0.2)'
              }}>
                {hint}
              </div>
            )}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="email" className="form-label">
              Email
            </label>
            <input
              id="email"
              type="email"
              className="form-input"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoComplete="email"
              placeholder="votre.email@example.com"
              disabled={loading}
            />
          </div>

          <div className="form-group">
            <label htmlFor="password" className="form-label">
              Mot de passe
            </label>
            <input
              id="password"
              type="password"
              className="form-input"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              autoComplete="current-password"
              placeholder="••••••••"
              disabled={loading}
            />
          </div>

          <button
            type="submit"
            className="btn btn-primary btn-full"
            disabled={loading}
            style={{ marginTop: '24px' }}
          >
            {loading ? (
              <>
                <LoadingSpinner size={20} />
                <span>Connexion...</span>
              </>
            ) : (
              'Se connecter'
            )}
          </button>
        </form>

        <div style={{ textAlign: 'center', marginTop: '24px', paddingTop: '24px', borderTop: '1px solid #e5e7eb' }}>
          <p style={{ color: '#6b7280', fontSize: '13px', marginBottom: '12px' }}>
            Pas de compte mobile?
          </p>
          <a
            href="/custom/mv3pro_portail/mobile_app/admin/manage_users.php"
            style={{
              color: '#0891b2',
              fontSize: '13px',
              textDecoration: 'none',
              fontWeight: '500'
            }}
            target="_blank"
            rel="noopener noreferrer"
          >
            Demandez à votre administrateur de créer votre compte
          </a>
        </div>

        <div style={{ textAlign: 'center', marginTop: '16px', color: '#9ca3af', fontSize: '12px' }}>
          MV3 Carrelage - Version 1.0.0
        </div>

        {debugMode && debugInfo && (
          <div style={{
            marginTop: '24px',
            padding: '16px',
            background: '#f3f4f6',
            borderRadius: '8px',
            fontSize: '12px',
            fontFamily: 'monospace',
            maxHeight: '600px',
            overflow: 'auto',
          }}>
            <h3 style={{ marginBottom: '16px', fontWeight: '700', color: '#1f2937' }}>
              DEBUG INFO
            </h3>

            <div style={{ marginBottom: '16px' }}>
              <strong style={{ color: '#dc2626' }}>REQUEST</strong>
              <div style={{ marginTop: '8px', background: 'white', padding: '12px', borderRadius: '6px' }}>
                <div><strong>URL:</strong> {debugInfo.requestUrl}</div>
                <div><strong>Method:</strong> {debugInfo.requestMethod}</div>
                <div><strong>Headers:</strong></div>
                <pre style={{ marginLeft: '16px', marginTop: '4px', fontSize: '11px' }}>
                  {JSON.stringify(debugInfo.requestHeaders, null, 2)}
                </pre>
                <div><strong>Body:</strong></div>
                <pre style={{ marginLeft: '16px', marginTop: '4px', fontSize: '11px' }}>
                  {JSON.stringify(debugInfo.requestBody, null, 2)}
                </pre>
              </div>
            </div>

            <div style={{ marginBottom: '16px' }}>
              <strong style={{ color: debugInfo.responseStatus === 200 ? '#059669' : '#dc2626' }}>
                RESPONSE ({debugInfo.responseStatus})
              </strong>
              <div style={{ marginTop: '8px', background: 'white', padding: '12px', borderRadius: '6px' }}>
                <div><strong>Status:</strong> {debugInfo.responseStatus}</div>
                <div><strong>Headers:</strong></div>
                <pre style={{ marginLeft: '16px', marginTop: '4px', fontSize: '11px' }}>
                  {JSON.stringify(debugInfo.responseHeaders, null, 2)}
                </pre>
                <div><strong>Body:</strong></div>
                <pre style={{ marginLeft: '16px', marginTop: '4px', fontSize: '11px', color: debugInfo.responseBody?.success ? '#059669' : '#dc2626' }}>
                  {JSON.stringify(debugInfo.responseBody, null, 2)}
                </pre>
              </div>
            </div>

            {debugInfo.errorDetails && (
              <div>
                <strong style={{ color: '#dc2626' }}>ERROR DETAILS</strong>
                <div style={{ marginTop: '8px', background: 'white', padding: '12px', borderRadius: '6px' }}>
                  <pre style={{ fontSize: '11px', color: '#dc2626' }}>
                    {JSON.stringify(debugInfo.errorDetails, null, 2)}
                  </pre>
                </div>
              </div>
            )}

            <div style={{ marginTop: '16px', padding: '12px', background: '#fef3c7', borderRadius: '6px' }}>
              <strong>Ouvrez la console pour plus de détails (F12)</strong>
            </div>

            {debugInfo.responseStatus === 200 && debugInfo.responseBody?.success && (
              <div style={{ marginTop: '16px' }}>
                <button
                  onClick={() => navigate('/dashboard', { replace: true })}
                  style={{
                    width: '100%',
                    padding: '12px',
                    background: '#059669',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    fontSize: '14px',
                    fontWeight: '600',
                    cursor: 'pointer',
                  }}
                >
                  ✓ LOGIN REUSSI - Continuer vers le Dashboard
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
