import { useState, useEffect } from 'react';
import { Layout } from '../components/Layout';
import { LoadingSpinner } from '../components/LoadingSpinner';
import { useAuth } from '../contexts/AuthContext';
import { storage } from '../lib/api';

export function Debug() {
  const { user } = useAuth();
  const [debugInfo, setDebugInfo] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [debugMode, setDebugMode] = useState(
    localStorage.getItem('mv3pro_debug') === 'true'
  );
  const [backendDebugMode, setBackendDebugMode] = useState(false);

  useEffect(() => {
    // Vérifier si le debug backend est actif
    fetch('/tmp/mv3pro_debug.flag', { method: 'HEAD' })
      .then(() => setBackendDebugMode(true))
      .catch(() => setBackendDebugMode(false));
  }, []);

  const fetchDebugInfo = async () => {
    setLoading(true);
    try {
      const token = storage.getToken();
      const response = await fetch('/custom/mv3pro_portail/api/v1/debug_auth.php', {
        headers: {
          Authorization: token ? `Bearer ${token}` : '',
        },
      });
      const data = await response.json();
      setDebugInfo(data);
    } catch (error: any) {
      setDebugInfo({
        error: error.message,
        stack: error.stack,
      });
    } finally {
      setLoading(false);
    }
  };

  const toggleDebugMode = () => {
    const newValue = !debugMode;
    if (newValue) {
      localStorage.setItem('mv3pro_debug', 'true');
      console.log('✅ Mode debug activé. Rechargez la page pour voir les logs.');
    } else {
      localStorage.removeItem('mv3pro_debug');
      console.log('❌ Mode debug désactivé.');
    }
    setDebugMode(newValue);
  };

  const enableBackendDebug = async () => {
    try {
      await fetch('/custom/mv3pro_portail/api/v1/debug_auth.php?enable_logs=1');
      alert('Debug backend activé. Les logs seront écrits dans /tmp/mv3pro_auth_debug.log');
      setBackendDebugMode(true);
    } catch (error: any) {
      alert('Erreur: ' + error.message);
    }
  };

  const clearToken = () => {
    storage.clearToken();
    alert('Token effacé. Vous allez être redirigé vers la page de login.');
    window.location.href = '/custom/mv3pro_portail/pwa_dist/#/login';
  };

  return (
    <Layout title="Debug">
      <div style={{ padding: '20px' }}>
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
            ⚠️ Page de debug
          </h3>
          <p style={{ fontSize: '14px', color: '#78350f', margin: 0 }}>
            Cette page expose des informations sensibles. Ne pas utiliser en production.
          </p>
        </div>

        <div className="card" style={{ marginBottom: '16px' }}>
          <h3 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '16px' }}>
            Utilisateur actuel
          </h3>
          <pre
            style={{
              background: '#f3f4f6',
              padding: '12px',
              borderRadius: '8px',
              fontSize: '12px',
              overflow: 'auto',
            }}
          >
            {JSON.stringify(user, null, 2)}
          </pre>
        </div>

        <div className="card" style={{ marginBottom: '16px' }}>
          <h3 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '16px' }}>
            LocalStorage
          </h3>
          <div style={{ fontSize: '14px', marginBottom: '8px' }}>
            <strong>Token:</strong>{' '}
            {storage.getToken()
              ? storage.getToken()!.substring(0, 30) + '...'
              : 'Aucun token'}
          </div>
          <div style={{ fontSize: '14px', marginBottom: '8px' }}>
            <strong>Mode debug:</strong> {debugMode ? '✅ Activé' : '❌ Désactivé'}
          </div>
          <div style={{ display: 'flex', gap: '8px', marginTop: '12px' }}>
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
              {debugMode ? 'Désactiver debug' : 'Activer debug'}
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
          </div>
        </div>

        <div className="card" style={{ marginBottom: '16px' }}>
          <h3 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '16px' }}>
            Debug Backend
          </h3>
          <div style={{ fontSize: '14px', marginBottom: '12px' }}>
            <strong>Status:</strong> {backendDebugMode ? '✅ Actif' : '❌ Inactif'}
          </div>
          <p style={{ fontSize: '14px', color: '#6b7280', marginBottom: '12px' }}>
            Le debug backend écrit les logs dans <code>/tmp/mv3pro_auth_debug.log</code>
          </p>
          <button
            onClick={enableBackendDebug}
            style={{
              padding: '8px 16px',
              background: '#3b82f6',
              color: 'white',
              border: 'none',
              borderRadius: '6px',
              fontSize: '14px',
              fontWeight: '600',
              cursor: 'pointer',
            }}
          >
            Activer debug backend
          </button>
        </div>

        <div className="card">
          <h3 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '16px' }}>
            Informations d'authentification complètes
          </h3>
          <button
            onClick={fetchDebugInfo}
            disabled={loading}
            style={{
              padding: '10px 20px',
              background: loading ? '#9ca3af' : '#0891b2',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: '600',
              cursor: loading ? 'not-allowed' : 'pointer',
              marginBottom: '16px',
            }}
          >
            {loading ? 'Chargement...' : 'Récupérer les infos debug'}
          </button>

          {loading && <LoadingSpinner />}

          {debugInfo && !loading && (
            <pre
              style={{
                background: '#1f2937',
                color: '#f3f4f6',
                padding: '16px',
                borderRadius: '8px',
                fontSize: '11px',
                overflow: 'auto',
                maxHeight: '600px',
              }}
            >
              {JSON.stringify(debugInfo, null, 2)}
            </pre>
          )}
        </div>

        <div
          style={{
            marginTop: '32px',
            padding: '16px',
            background: '#f9fafb',
            borderRadius: '8px',
            fontSize: '13px',
            color: '#6b7280',
          }}
        >
          <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px', color: '#374151' }}>
            Instructions
          </h4>
          <ol style={{ paddingLeft: '20px', margin: 0 }}>
            <li>Activez le mode debug frontend pour voir les logs dans la console</li>
            <li>Activez le debug backend pour enregistrer les logs serveur</li>
            <li>Cliquez sur "Récupérer les infos debug" pour voir l'état complet de l'authentification</li>
            <li>
              Consultez <code>/tmp/mv3pro_auth_debug.log</code> sur le serveur pour les logs backend
            </li>
            <li>Ouvrez la console du navigateur (F12) pour voir les logs frontend</li>
          </ol>
        </div>
      </div>
    </Layout>
  );
}
