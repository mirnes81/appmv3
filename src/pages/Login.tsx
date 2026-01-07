import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

const DEMO_USERS = [
  { login: 'test', password: 'admin123456789', role: 'admin', name: 'Administrateur Test' },
  { login: 'admin', password: 'admin', role: 'admin', name: 'Admin' },
  { login: 'demo', password: 'demo', role: 'user', name: 'Utilisateur Demo' }
];

export default function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      await new Promise(resolve => setTimeout(resolve, 500));

      const user = DEMO_USERS.find(
        u => u.login === username && u.password === password
      );

      if (user) {
        const userData = {
          id: Math.random().toString(36).substr(2, 9),
          login: user.login,
          name: user.name,
          role: user.role
        };

        localStorage.setItem('auth_token', 'demo_token_' + Date.now());
        localStorage.setItem('user', JSON.stringify(userData));

        navigate('/dashboard');
      } else {
        alert('Login ou mot de passe incorrect');
      }
    } catch (error) {
      console.error('Erreur de connexion:', error);
      alert('Erreur lors de la connexion');
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


          <button
            type="submit"
            disabled={loading}
            className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {loading ? 'Connexion...' : 'Se connecter'}
          </button>
        </form>

        <div className="mt-6 p-3 bg-blue-50 rounded-lg text-sm text-gray-700">
          <p className="font-semibold mb-2">Comptes de test :</p>
          <div className="space-y-1 text-xs">
            <p><span className="font-medium">test</span> / admin123456789</p>
            <p><span className="font-medium">admin</span> / admin</p>
            <p><span className="font-medium">demo</span> / demo</p>
          </div>
        </div>
      </div>
    </div>
  );
}
