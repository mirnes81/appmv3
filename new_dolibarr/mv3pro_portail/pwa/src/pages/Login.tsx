import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { PWA_URLS } from '../config';

interface DebugStep {
  step: number;
  name: string;
  status: 'pending' | 'running' | 'success' | 'error';
  details?: any;
  error?: string;
}

export function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [hint, setHint] = useState('');
  const [loading, setLoading] = useState(false);
  const [debugMode, setDebugMode] = useState(() => {
    return localStorage.getItem('mv3_debug') === '1';
  });
  const [debugSteps, setDebugSteps] = useState<DebugStep[]>([]);
  const { login } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    localStorage.setItem('mv3_debug', debugMode ? '1' : '0');
  }, [debugMode]);

  const updateStep = (step: number, updates: Partial<DebugStep>) => {
    setDebugSteps(prev => {
      const newSteps = [...prev];
      const index = newSteps.findIndex(s => s.step === step);
      if (index >= 0) {
        newSteps[index] = { ...newSteps[index], ...updates };
      }
      return newSteps;
    });
  };

  const maskToken = (token: string): string => {
    if (!token || token.length < 10) return token;
    return `${token.substring(0, 6)}...${token.substring(token.length - 4)}`;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setHint('');
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
    const steps: DebugStep[] = [
      { step: 1, name: 'Connexion au serveur', status: 'pending' },
      { step: 2, name: 'Stockage du token', status: 'pending' },
      { step: 3, name: 'Test API /me.php', status: 'pending' },
      { step: 4, name: 'Redirection Dashboard', status: 'pending' },
    ];
    setDebugSteps(steps);

    const API_BASE = '/custom/mv3pro_portail';
    const LOGIN_URL = `${API_BASE}/mobile_app/api/auth.php?action=login`;
    const ME_URL = `${API_BASE}/api/v1/me.php`;

    updateStep(1, { status: 'running' });

    try {
      console.log('[DEBUG STEP 1] Login request to:', LOGIN_URL);

      const loginResponse = await fetch(LOGIN_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      console.log('[DEBUG STEP 1] Response status:', loginResponse.status);
      console.log('[DEBUG STEP 1] Response headers:', Object.fromEntries(loginResponse.headers.entries()));

      const responseText = await loginResponse.text();
      console.log('[DEBUG STEP 1] Raw response:', responseText);

      let loginData;
      try {
        loginData = JSON.parse(responseText);
      } catch (jsonError: any) {
        updateStep(1, {
          status: 'error',
          error: 'Réponse serveur invalide (pas JSON)',
          details: {
            status: loginResponse.status,
            statusText: loginResponse.statusText,
            contentType: loginResponse.headers.get('content-type'),
            responsePreview: responseText.substring(0, 500),
            parseError: jsonError?.message || String(jsonError),
          },
        });
        setError('Le serveur a retourné une réponse invalide');
        setHint('Vérifiez que le serveur PHP est accessible et retourne du JSON');
        setLoading(false);
        return;
      }

      console.log('[DEBUG STEP 1] Login response:', {
        status: loginResponse.status,
        success: loginData.success,
        hasToken: !!loginData.token,
        user: loginData.user,
      });

      if (!loginResponse.ok || !loginData.success) {
        updateStep(1, {
          status: 'error',
          error: loginData.message || `HTTP ${loginResponse.status}`,
          details: {
            status: loginResponse.status,
            response: loginData,
          },
        });
        setError(loginData.message || 'Erreur de connexion');
        setLoading(false);
        return;
      }

      updateStep(1, {
        status: 'success',
        details: {
          status: loginResponse.status,
          user_email: loginData.user?.email,
          user_name: loginData.user?.name,
          dolibarr_user_id: loginData.user?.dolibarr_user_id,
          token_received: !!loginData.token,
          token_masked: loginData.token ? maskToken(loginData.token) : null,
        },
      });

      updateStep(2, { status: 'running' });

      const token = loginData.token;
      if (!token) {
        updateStep(2, {
          status: 'error',
          error: 'Aucun token reçu du serveur',
        });
        setError('Aucun token reçu');
        setLoading(false);
        return;
      }

      localStorage.setItem('mv3pro_token', token);
      console.log('[DEBUG STEP 2] Token stored in localStorage:', maskToken(token));

      const storedToken = localStorage.getItem('mv3pro_token');
      const tokenMatches = storedToken === token;

      updateStep(2, {
        status: 'success',
        details: {
          token_masked: maskToken(token),
          token_length: token.length,
          stored_in_localStorage: !!storedToken,
          token_matches: tokenMatches,
        },
      });

      await new Promise(resolve => setTimeout(resolve, 500));

      updateStep(3, { status: 'running' });

      console.log('[DEBUG STEP 3] Testing /me.php with token:', maskToken(token));
      console.log('[DEBUG STEP 3] Headers sent:', {
        'Authorization': `Bearer ${maskToken(token)}`,
        'X-Auth-Token': maskToken(token),
        'X-MV3-Debug': '1',
      });

      const meResponse = await fetch(ME_URL, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-Auth-Token': token,
          'X-MV3-Debug': '1',
        },
      });

      console.log('[DEBUG STEP 3] /me.php response status:', meResponse.status);

      let meData;
      try {
        meData = await meResponse.json();
        console.log('[DEBUG STEP 3] /me.php response body:', meData);
      } catch (e) {
        const text = await meResponse.text();
        console.error('[DEBUG STEP 3] Failed to parse JSON, raw response:', text);
        meData = { _raw: text };
      }

      if (!meResponse.ok) {
        updateStep(3, {
          status: 'error',
          error: `HTTP ${meResponse.status}: ${meData.message || meData.error || 'Unauthorized'}`,
          details: {
            status: meResponse.status,
            statusText: meResponse.statusText,
            response: meData,
            token_sent: `Bearer ${maskToken(token)}`,
            headers_sent: {
              'Authorization': 'Present',
              'X-Auth-Token': 'Present',
              'X-MV3-Debug': '1',
            },
          },
        });
        setError(`Erreur /me.php: ${meResponse.status}`);
        setLoading(false);
        return;
      }

      if (!meData.success || !meData.user) {
        updateStep(3, {
          status: 'error',
          error: 'Réponse invalide de /me.php',
          details: {
            response: meData,
            reason: meData.reason || 'Unknown',
          },
        });
        setError('Réponse invalide');
        setLoading(false);
        return;
      }

      updateStep(3, {
        status: 'success',
        details: {
          status: meResponse.status,
          user_id: meData.user.id,
          user_email: meData.user.email,
          user_name: meData.user.name,
          is_unlinked: meData.user.is_unlinked,
          dolibarr_user_id: meData.user.dolibarr_user_id,
          rights: meData.user.rights,
        },
      });

      await new Promise(resolve => setTimeout(resolve, 500));

      updateStep(4, { status: 'running' });

      console.log('[DEBUG STEP 4] All checks passed, redirecting to dashboard');

      updateStep(4, {
        status: 'success',
        details: {
          redirect_to: '/dashboard',
          ready: true,
        },
      });

      await new Promise(resolve => setTimeout(resolve, 1000));

      console.log('[DEBUG] Authentication flow complete, reloading to dashboard');
      setLoading(false);

      // Force un reload complet pour que AuthContext recharge l'utilisateur depuis le token
      window.location.href = PWA_URLS.dashboard;

    } catch (err: any) {
      console.error('[DEBUG] Unexpected error:', err);
      const currentStep = debugSteps.findIndex(s => s.status === 'running');
      if (currentStep >= 0) {
        updateStep(currentStep + 1, {
          status: 'error',
          error: err.message,
          details: {
            name: err.name,
            message: err.message,
            stack: err.stack,
          },
        });
      }
      setError('Erreur: ' + err.message);
      setLoading(false);
    }
  };

  const getStepIcon = (status: DebugStep['status']) => {
    switch (status) {
      case 'pending': return '⏳';
      case 'running': return '⚙️';
      case 'success': return '✅';
      case 'error': return '❌';
    }
  };

  const getStepColor = (status: DebugStep['status']) => {
    switch (status) {
      case 'pending': return '#9ca3af';
      case 'running': return '#3b82f6';
      case 'success': return '#10b981';
      case 'error': return '#ef4444';
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
          maxWidth: debugMode && debugSteps.length > 0 ? '900px' : '400px',
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
            {debugMode ? '🔍 DEBUG MODE ON' : 'Mode Debug'}
          </button>
          {debugMode && (
            <div style={{
              marginTop: '8px',
              fontSize: '11px',
              color: '#f59e0b',
              fontWeight: '500'
            }}>
              Mode debug activé - Suivi étape par étape
            </div>
          )}
        </div>

        {error && !debugSteps.length && (
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

        {debugMode && debugSteps.length > 0 && (
          <div style={{
            marginTop: '24px',
            padding: '20px',
            background: '#f9fafb',
            borderRadius: '12px',
            border: '2px solid #e5e7eb',
          }}>
            <h3 style={{
              fontSize: '16px',
              fontWeight: '700',
              marginBottom: '16px',
              color: '#1f2937',
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
            }}>
              🔍 DEBUG - Suivi étape par étape
            </h3>

            {debugSteps.map((step) => (
              <div
                key={step.step}
                style={{
                  marginBottom: '16px',
                  padding: '16px',
                  background: 'white',
                  borderRadius: '8px',
                  border: `2px solid ${getStepColor(step.status)}`,
                }}
              >
                <div style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: '12px',
                  marginBottom: step.details || step.error ? '12px' : '0',
                }}>
                  <span style={{ fontSize: '24px' }}>{getStepIcon(step.status)}</span>
                  <div style={{ flex: 1 }}>
                    <div style={{
                      fontWeight: '600',
                      color: getStepColor(step.status),
                      fontSize: '14px',
                    }}>
                      ÉTAPE {step.step}: {step.name}
                    </div>
                    {step.status === 'running' && (
                      <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>
                        En cours...
                      </div>
                    )}
                  </div>
                </div>

                {step.error && (
                  <div style={{
                    padding: '12px',
                    background: '#fef2f2',
                    borderRadius: '6px',
                    fontSize: '13px',
                    color: '#dc2626',
                    fontWeight: '500',
                  }}>
                    ❌ {step.error}
                  </div>
                )}

                {step.details && (
                  <div style={{
                    padding: '12px',
                    background: '#f3f4f6',
                    borderRadius: '6px',
                    fontSize: '11px',
                    fontFamily: 'monospace',
                    maxHeight: '200px',
                    overflow: 'auto',
                  }}>
                    <pre style={{ margin: 0, whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>
                      {JSON.stringify(step.details, null, 2)}
                    </pre>
                  </div>
                )}
              </div>
            ))}

            <div style={{
              marginTop: '16px',
              padding: '12px',
              background: '#fef3c7',
              borderRadius: '8px',
              fontSize: '12px',
              color: '#92400e',
            }}>
              💡 Consultez la console du navigateur (F12) pour plus de détails
            </div>
          </div>
        )}

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
      </div>
    </div>
  );
}
