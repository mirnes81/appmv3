import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

export default function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showConfig, setShowConfig] = useState(false);
  const [apiUrl, setApiUrl] = useState('');
  const navigate = useNavigate();

  useEffect(() => {
    const savedUrl = localStorage.getItem('dolibarr_api_url');
    if (savedUrl) {
      setApiUrl(savedUrl);
    } else {
      setApiUrl('https://crm.mv-3pro.ch/custom/mv3pro_portail/api');
      setShowConfig(true);
    }
  }, []);

  const saveConfig = () => {
    if (!apiUrl) {
      alert('Veuillez entrer une URL API');
      return;
    }
    localStorage.setItem('dolibarr_api_url', apiUrl);
    setShowConfig(false);
    alert('Configuration sauvegardée');
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const baseUrl = localStorage.getItem('dolibarr_api_url') || apiUrl;
      const loginUrl = `${baseUrl}/auth_login.php`;

      const response = await fetch(loginUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ login: username, password }),
      });

      const data = await response.json();

      if (data.success) {
        localStorage.setItem('auth_token', data.token);
        localStorage.setItem('api_key', data.api_key);
        localStorage.setItem('user', JSON.stringify(data.user));
        navigate('/dashboard');
      } else {
        alert(data.error || 'Erreur de connexion');
      }
    } catch (error) {
      console.error('Erreur:', error);
      alert('Erreur de connexion au serveur');
    } finally {
      setLoading(false);
    }
  };

  if (showConfig) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
          <div className="text-center mb-8">
            <h1 className="text-2xl font-bold text-gray-800">Configuration</h1>
            <p className="text-gray-600 mt-2">Paramètres de connexion Dolibarr</p>
          </div>

          <div className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                URL API Dolibarr
              </label>
              <input
                type="text"
                value={apiUrl}
                onChange={(e) => setApiUrl(e.target.value)}
                placeholder="https://crm.mv-3pro.ch/custom/mv3pro_portail/api"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <p className="text-xs text-gray-500 mt-2">
                URL de base de votre API Dolibarr (sans /auth_login.php)
              </p>
            </div>

            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h3 className="font-semibold text-blue-900 mb-2">Exemple :</h3>
              <code className="text-sm text-blue-800 break-all">
                https://votre-dolibarr.com/custom/mv3pro_portail/api
              </code>
            </div>

            <div className="space-y-3">
              <button
                onClick={saveConfig}
                className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors"
              >
                Sauvegarder la configuration
              </button>

              {localStorage.getItem('dolibarr_api_url') && (
                <button
                  onClick={() => setShowConfig(false)}
                  className="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition-colors"
                >
                  Annuler
                </button>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
        <div className="text-center mb-8">
          <img src="/5cmlogo.jpg" alt="Logo" className="h-16 mx-auto mb-4" />
          <h1 className="text-2xl font-bold text-gray-800">MV3PRO</h1>
          <p className="text-gray-600">Gestion de Chantiers</p>
        </div>

        <form onSubmit={handleLogin} className="space-y-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Login ou Email
            </label>
            <input
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              placeholder="info@mv-3pro.ch"
              className="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Mot de passe
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white"
              required
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {loading ? 'Connexion...' : 'Se connecter'}
          </button>

          <button
            type="button"
            onClick={() => setShowConfig(true)}
            className="w-full text-sm text-blue-600 hover:text-blue-700 font-medium py-2"
          >
            ⚙️ Configurer l'API Dolibarr
          </button>
        </form>

        <div className="mt-6 p-4 bg-gray-50 rounded-lg">
          <p className="text-xs text-gray-600">
            <strong>API configurée :</strong>
            <br />
            <span className="text-xs text-gray-500 break-all">
              {localStorage.getItem('dolibarr_api_url') || 'Non configurée'}
            </span>
          </p>
        </div>
      </div>
    </div>
  );
}
