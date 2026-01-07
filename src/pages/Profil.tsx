import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';

export default function Profil() {
  const [user, setUser] = useState<any>(null);
  const navigate = useNavigate();

  useEffect(() => {
    const userData = localStorage.getItem('user');
    if (!userData) {
      navigate('/login');
      return;
    }
    setUser(JSON.parse(userData));
  }, [navigate]);

  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    navigate('/login');
  };

  if (!user) return null;

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <div className="flex items-center gap-3">
            <Link to="/dashboard" className="flex items-center gap-3 hover:opacity-80">
              <img src="/5cmlogo.jpg" alt="Logo" className="h-10" />
              <h1 className="text-xl font-bold text-gray-800">MV3PRO</h1>
            </Link>
          </div>
          <button
            onClick={handleLogout}
            className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
          >
            Déconnexion
          </button>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        <div className="mb-6">
          <Link
            to="/dashboard"
            className="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4"
          >
            ← Retour au tableau de bord
          </Link>
          <h2 className="text-3xl font-bold text-gray-800">Mon Profil</h2>
        </div>

        <div className="bg-white rounded-xl shadow p-6">
          <div className="flex items-center gap-6 mb-8">
            <div className="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center text-4xl">
              👤
            </div>
            <div>
              <h3 className="text-2xl font-bold text-gray-800">{user.name}</h3>
              <p className="text-gray-600 mt-1">@{user.login}</p>
            </div>
          </div>

          <div className="space-y-4">
            <div className="border-t pt-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Nom complet
              </label>
              <input
                type="text"
                value={user.name}
                className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg"
                disabled
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Login
              </label>
              <input
                type="text"
                value={user.login}
                className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg"
                disabled
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Rôle
              </label>
              <input
                type="text"
                value={user.role}
                className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg"
                disabled
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                ID
              </label>
              <input
                type="text"
                value={user.id}
                className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg"
                disabled
              />
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
