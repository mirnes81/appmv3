import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import RapportForm from '../components/RapportForm';

interface Rapport {
  id: string;
  ref: string;
  user_id: string;
  projet_nom: string;
  date_rapport: string;
  zone_travail: string;
  heures_debut: string;
  heures_fin: string;
  temps_total: string;
  surface_carrelee: string;
  format_carreaux: string;
  type_pose: string;
  zone_pose: string;
  travaux_realises: string;
  observations: string;
  statut: string;
  created_at: string;
}

export default function Rapports() {
  const [user, setUser] = useState<any>(null);
  const [rapports, setRapports] = useState<Rapport[]>([]);
  const [showForm, setShowForm] = useState(false);
  const [selectedRapport, setSelectedRapport] = useState<Rapport | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    const userData = localStorage.getItem('user');
    if (!userData) {
      navigate('/login');
      return;
    }
    setUser(JSON.parse(userData));
    loadRapports();
  }, [navigate]);

  const loadRapports = () => {
    const stored = localStorage.getItem('rapports');
    if (stored) {
      setRapports(JSON.parse(stored));
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    navigate('/login');
  };

  const handleSubmit = (formData: any) => {
    const userData = JSON.parse(localStorage.getItem('user') || '{}');
    const newRapport: Rapport = {
      id: Math.random().toString(36).substr(2, 9),
      ref: 'RAP' + String(rapports.length + 1).padStart(6, '0'),
      user_id: userData.id,
      ...formData,
      statut: 'brouillon',
      created_at: new Date().toISOString()
    };

    const updated = [...rapports, newRapport];
    setRapports(updated);
    localStorage.setItem('rapports', JSON.stringify(updated));
    setShowForm(false);
    alert('Rapport créé avec succès !');
  };

  const handleDelete = (id: string) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce rapport ?')) {
      const updated = rapports.filter(r => r.id !== id);
      setRapports(updated);
      localStorage.setItem('rapports', JSON.stringify(updated));
    }
  };

  const handleView = (rapport: Rapport) => {
    setSelectedRapport(rapport);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR');
  };

  if (!user) return null;

  if (selectedRapport) {
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
            <button
              onClick={() => setSelectedRapport(null)}
              className="text-blue-600 hover:text-blue-800 flex items-center gap-2 mb-4"
            >
              ← Retour à la liste
            </button>
            <div className="flex justify-between items-start">
              <div>
                <h2 className="text-3xl font-bold text-gray-800">{selectedRapport.ref}</h2>
                <p className="text-gray-600 mt-1">{selectedRapport.projet_nom}</p>
              </div>
              <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                selectedRapport.statut === 'brouillon' ? 'bg-yellow-100 text-yellow-800' :
                selectedRapport.statut === 'valide' ? 'bg-green-100 text-green-800' :
                'bg-gray-100 text-gray-800'
              }`}>
                {selectedRapport.statut}
              </span>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow p-6 space-y-6">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <p className="text-sm text-gray-600">Date</p>
                <p className="font-semibold">{formatDate(selectedRapport.date_rapport)}</p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Zone de travail</p>
                <p className="font-semibold">{selectedRapport.zone_travail || '-'}</p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Horaires</p>
                <p className="font-semibold">
                  {selectedRapport.heures_debut && selectedRapport.heures_fin
                    ? `${selectedRapport.heures_debut} - ${selectedRapport.heures_fin}`
                    : '-'}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">Temps total</p>
                <p className="font-semibold">{selectedRapport.temps_total || '0'} h</p>
              </div>
            </div>

            <div className="border-t pt-4">
              <h3 className="font-semibold text-gray-800 mb-2">Détails techniques</h3>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <p className="text-sm text-gray-600">Surface carrelée</p>
                  <p className="font-semibold">{selectedRapport.surface_carrelee || '-'} m²</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Format carreaux</p>
                  <p className="font-semibold">{selectedRapport.format_carreaux || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Type de pose</p>
                  <p className="font-semibold">{selectedRapport.type_pose || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Zone de pose</p>
                  <p className="font-semibold">{selectedRapport.zone_pose || '-'}</p>
                </div>
              </div>
            </div>

            <div className="border-t pt-4">
              <h3 className="font-semibold text-gray-800 mb-2">Travaux réalisés</h3>
              <p className="text-gray-700 whitespace-pre-wrap">{selectedRapport.travaux_realises}</p>
            </div>

            {selectedRapport.observations && (
              <div className="border-t pt-4">
                <h3 className="font-semibold text-gray-800 mb-2">Observations</h3>
                <p className="text-gray-700 whitespace-pre-wrap">{selectedRapport.observations}</p>
              </div>
            )}
          </div>
        </main>
      </div>
    );
  }

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
          <h2 className="text-3xl font-bold text-gray-800">Rapports de Chantier</h2>
        </div>

        {showForm ? (
          <div className="bg-white rounded-xl shadow p-6">
            <h3 className="text-xl font-semibold text-gray-800 mb-6">Nouveau rapport</h3>
            <RapportForm
              onSubmit={handleSubmit}
              onCancel={() => setShowForm(false)}
            />
          </div>
        ) : (
          <div className="bg-white rounded-xl shadow p-6">
            <div className="flex justify-between items-center mb-6">
              <h3 className="text-xl font-semibold text-gray-800">
                Liste des rapports ({rapports.length})
              </h3>
              <button
                onClick={() => setShowForm(true)}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
              >
                + Nouveau rapport
              </button>
            </div>

            {rapports.length === 0 ? (
              <div className="text-center py-12 text-gray-500">
                <div className="text-6xl mb-4">📋</div>
                <p className="text-lg">Aucun rapport disponible</p>
                <p className="text-sm mt-2">Créez votre premier rapport de chantier</p>
              </div>
            ) : (
              <div className="space-y-4">
                {rapports.map((rapport) => (
                  <div
                    key={rapport.id}
                    className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                  >
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <h4 className="font-semibold text-lg text-gray-800">{rapport.ref}</h4>
                          <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                            rapport.statut === 'brouillon' ? 'bg-yellow-100 text-yellow-800' :
                            rapport.statut === 'valide' ? 'bg-green-100 text-green-800' :
                            'bg-gray-100 text-gray-800'
                          }`}>
                            {rapport.statut}
                          </span>
                        </div>
                        <p className="text-gray-800 font-medium">{rapport.projet_nom}</p>
                        <div className="flex gap-4 mt-2 text-sm text-gray-600">
                          <span>📅 {formatDate(rapport.date_rapport)}</span>
                          {rapport.zone_travail && <span>📍 {rapport.zone_travail}</span>}
                          {rapport.temps_total && <span>⏱️ {rapport.temps_total}h</span>}
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <button
                          onClick={() => handleView(rapport)}
                          className="px-3 py-1 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                        >
                          Voir
                        </button>
                        <button
                          onClick={() => handleDelete(rapport.id)}
                          className="px-3 py-1 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}
      </main>
    </div>
  );
}
