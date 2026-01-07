import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';

export default function Dashboard() {
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
    localStorage.removeItem('user');
    navigate('/login');
  };

  if (!user) return null;

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <div className="flex items-center gap-3">
            <img src="/5cmlogo.jpg" alt="Logo" className="h-10" />
            <h1 className="text-xl font-bold text-gray-800">MV3PRO</h1>
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
        <div className="bg-white rounded-xl shadow p-6 mb-6">
          <h2 className="text-2xl font-bold text-gray-800 mb-2">
            Bienvenue, {user.name}
          </h2>
          <p className="text-gray-600">
            <span className="font-medium">Login:</span> {user.login} |
            <span className="font-medium ml-3">Rôle:</span> {user.role}
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <Link to="/rapports" className="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow cursor-pointer block">
            <div className="text-blue-600 text-3xl mb-3">📋</div>
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Rapports</h3>
            <p className="text-gray-600 text-sm">Gérer les rapports de chantier</p>
          </Link>

          <Link to="/planning" className="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow cursor-pointer block">
            <div className="text-green-600 text-3xl mb-3">📅</div>
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Planning</h3>
            <p className="text-gray-600 text-sm">Consulter le planning</p>
          </Link>

          <Link to="/materiel" className="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow cursor-pointer block">
            <div className="text-yellow-600 text-3xl mb-3">🔧</div>
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Matériel</h3>
            <p className="text-gray-600 text-sm">Gérer le matériel</p>
          </Link>

          <Link to="/sens-de-pose" className="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow cursor-pointer block">
            <div className="text-orange-600 text-3xl mb-3">📐</div>
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Sens de Pose</h3>
            <p className="text-gray-600 text-sm">Plans de pose</p>
          </Link>

          <Link to="/regie" className="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow cursor-pointer block">
            <div className="text-red-600 text-3xl mb-3">⏱️</div>
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Régie</h3>
            <p className="text-gray-600 text-sm">Suivi des heures</p>
          </Link>

          <Link to="/profil" className="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow cursor-pointer block">
            <div className="text-gray-600 text-3xl mb-3">👤</div>
            <h3 className="text-lg font-semibold text-gray-800 mb-2">Profil</h3>
            <p className="text-gray-600 text-sm">Gérer votre profil</p>
          </Link>
        </div>
      </main>
    </div>
  );
}
