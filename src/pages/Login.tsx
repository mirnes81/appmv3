import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

export default function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [apiUrl, setApiUrl] = useState('https://crm.mv-3pro.ch/custom/mv3pro_portail/api');
  const [loading, setLoading] = useState(false);
  const [showAdvanced, setShowAdvanced] = useState(false);
  const navigate = useNavigate();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const loginUrl = `${apiUrl}/auth_login.php`;

      console.log('Tentative de connexion à:', loginUrl);
      console.log('Login:', username);

      const response = await fetch(loginUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          login: username,
          password: password
        }),
      });

      const data = await response.json();
      console.log('Réponse:', data);

      if (data.success) {
        localStorage.setItem('auth_token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        navigate('/dashboard');
      } else {
        alert(data.error || data.message || 'Erreur de connexion');
      }
    } catch (error) {
      console.error('Erreur de connexion:', error);
      alert('Impossible de se connecter au serveur. Vérifiez l\'URL de l\'API.');
    } finally {
      setLoading(false);
    }
  };

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
              placeholder="admin ou info@mv-3pro.ch"
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

          <div className="border-t border-gray-200 pt-4">
            <button
              type="button"
              onClick={() => setShowAdvanced(!showAdvanced)}
              className="text-sm text-gray-600 hover:text-gray-800 flex items-center gap-2 mb-2"
            >
              <span>{showAdvanced ? '▼' : '►'}</span>
              <span>Paramètres avancés</span>
            </button>

            {showAdvanced && (
              <div className="mt-3">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  URL API Dolibarr
                </label>
                <input
                  type="text"
                  value={apiUrl}
                  onChange={(e) => setApiUrl(e.target.value)}
                  placeholder="https://votre-dolibarr.com/custom/mv3pro_portail/api"
                  className="w-full px-4 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p className="text-xs text-gray-500 mt-1">
                  URL de base de votre API (sans /auth_login.php)
                </p>
              </div>
            )}
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {loading ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>

        <div className="mt-6 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
          <p className="font-medium mb-1">Serveur :</p>
          <p className="text-gray-500 break-all">{apiUrl}</p>
        </div>
      </div>
    </div>
  );
}
