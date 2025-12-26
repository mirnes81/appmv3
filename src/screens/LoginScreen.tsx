import { useState } from 'react';
import { Key, Eye, EyeOff } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

export default function LoginScreen() {
  const { login } = useAuth();
  const [apiKey, setApiKey] = useState('');
  const [showApiKey, setShowApiKey] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(apiKey.trim());
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur de connexion');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-600 via-blue-700 to-blue-900 flex items-center justify-center p-4 safe-area-top safe-area-bottom">
      <div className="w-full max-w-md animate-fade-in">
        <div className="text-center mb-8">
          <div className="w-20 h-20 bg-white rounded-3xl shadow-xl mx-auto mb-4 flex items-center justify-center">
            <svg className="w-12 h-12 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
            </svg>
          </div>
          <h1 className="text-3xl font-bold text-white mb-2">MV3 Pro</h1>
          <p className="text-blue-200">Gestion de chantiers mobile</p>
        </div>

        <div className="card-premium">
          <form onSubmit={handleLogin} className="space-y-5">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                DOLAPIKEY
              </label>
              <div className="relative">
                <Key className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  type={showApiKey ? 'text' : 'password'}
                  value={apiKey}
                  onChange={(e) => setApiKey(e.target.value)}
                  className="input-premium pl-12 pr-12"
                  placeholder="Votre clé API Dolibarr"
                  required
                  autoFocus
                />
                <button
                  type="button"
                  onClick={() => setShowApiKey(!showApiKey)}
                  className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                  {showApiKey ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                </button>
              </div>
              <p className="mt-1 text-xs text-gray-500">
                Disponible dans Dolibarr : Menu Utilisateur → Générer une clé API
              </p>
            </div>

            {error && (
              <div className="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm animate-fade-in">
                <div className="font-semibold mb-1">Erreur de connexion</div>
                <pre className="whitespace-pre-wrap font-sans text-xs">{error}</pre>
                <div className="mt-2 pt-2 border-t border-red-200 text-xs">
                  <a href="#diagnostic" onClick={(e) => { e.preventDefault(); window.location.hash = 'diagnostic'; window.location.reload(); }} className="text-blue-600 hover:underline">
                    Ouvrir le diagnostic API
                  </a>
                </div>
              </div>
            )}

            <button
              type="submit"
              className="btn-primary"
              disabled={loading}
            >
              {loading ? (
                <div className="flex items-center justify-center">
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                  Connexion...
                </div>
              ) : (
                'Se connecter'
              )}
            </button>
          </form>

          <div className="mt-6 p-4 bg-blue-50 border border-blue-100 rounded-xl">
            <p className="text-sm text-gray-700 font-medium mb-2">Comment obtenir votre DOLAPIKEY ?</p>
            <ol className="text-xs text-gray-600 space-y-1 list-decimal list-inside">
              <li>Connectez-vous à Dolibarr (crm.mv-3pro.ch)</li>
              <li>Cliquez sur votre nom en haut à droite</li>
              <li>Allez dans "Modifier ma fiche utilisateur"</li>
              <li>Onglet "Clé API" → "Générer une nouvelle clé"</li>
              <li>Copiez la clé et collez-la ici</li>
            </ol>
          </div>

          <div className="mt-4 text-center text-sm text-gray-600">
            Mode hors-ligne disponible après première connexion
          </div>
        </div>

        <div className="mt-6 text-center">
          <a
            href="?screen=diagnostic"
            onClick={(e) => {
              e.preventDefault();
              window.location.hash = 'diagnostic';
              window.location.reload();
            }}
            className="text-white text-sm hover:text-blue-200 underline"
          >
            Diagnostic API
          </a>
        </div>

        <div className="mt-4 text-center text-white text-xs opacity-75">
          Version 1.0.2 • © 2024 MV3 Pro
        </div>
      </div>
    </div>
  );
}
