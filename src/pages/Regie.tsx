import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';

export default function Regie() {
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
          <h2 className="text-3xl font-bold text-gray-800">Régie - Suivi des Heures</h2>
        </div>

        <div className="bg-white rounded-xl shadow p-6 mb-6">
          <div className="flex justify-between items-center mb-6">
            <h3 className="text-xl font-semibold text-gray-800">Feuilles de régie</h3>
            <button className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
              + Nouvelle feuille
            </button>
          </div>

          <div className="text-center py-12 text-gray-500">
            <div className="text-6xl mb-4">⏱️</div>
            <p className="text-lg">Aucune feuille de régie</p>
            <p className="text-sm mt-2">Commencez à suivre vos heures de travail</p>
          </div>
        </div>
      </main>
    </div>
  );
}
