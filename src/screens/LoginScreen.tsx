import { useState } from 'react';
import { Server, Key, Eye, EyeOff, Mail } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { loginPHP } from '../utils/apiPHP';
import { saveUser } from '../utils/storage';

export default function LoginScreen() {
  const { login } = useAuth();
  const [mode, setMode] = useState<'dolibarr' | 'mysql'>('mysql');
  const [dolibarrUrl, setDolibarrUrl] = useState('https://crm.mv-3pro.ch');
  const [apiKey, setApiKey] = useState('');
  const [email, setEmail] = useState('test@mv3pro.com');
  const [password, setPassword] = useState('');
  const [showApiKey, setShowApiKey] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleDolibarrLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const cleanUrl = dolibarrUrl.trim().replace(/\/$/, '');
      await login(cleanUrl, apiKey.trim());
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur de connexion');
    } finally {
      setLoading(false);
    }
  };

  const handleMySQLLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await loginPHP(email.trim(), password);

      if (response.success && response.user) {
        await saveUser({
          id: String(response.user.id),
          dolibarr_user_id: response.user.dolibarr_user_id,
          email: response.user.email,
          name: response.user.email,
          phone: '',
          biometric_enabled: false,
          preferences: response.user.preferences || {
            theme: 'auto',
            notifications: true,
            autoSave: true,
            cameraQuality: 'high',
            voiceLanguage: 'fr-FR',
          },
        });

        window.location.reload();
      }
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
          <div className="flex gap-2 mb-6">
            <button
              type="button"
              onClick={() => setMode('mysql')}
              className={`flex-1 py-2 px-4 rounded-lg font-medium transition-all ${
                mode === 'mysql'
                  ? 'bg-blue-600 text-white shadow-lg'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              Email / Mot de passe
            </button>
            <button
              type="button"
              onClick={() => setMode('dolibarr')}
              className={`flex-1 py-2 px-4 rounded-lg font-medium transition-all ${
                mode === 'dolibarr'
                  ? 'bg-blue-600 text-white shadow-lg'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              API Dolibarr
            </button>
          </div>

          {mode === 'mysql' ? (
            <form onSubmit={handleMySQLLogin} className="space-y-5">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Email
                </label>
                <div className="relative">
                  <Mail className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="input-premium pl-12"
                    placeholder="votre@email.com"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Mot de passe
                </label>
                <div className="relative">
                  <Key className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    type={showPassword ? 'text' : 'password'}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    className="input-premium pl-12 pr-12"
                    placeholder="Votre mot de passe"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                  </button>
                </div>
              </div>

              {error && (
                <div className="bg-red-50 border border-red-200 rounded-xl p-3 text-red-700 text-sm animate-fade-in">
                  {error}
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

              <div className="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-xl text-sm text-gray-700">
                <strong>Compte de test :</strong><br />
                Email: test@mv3pro.com<br />
                Mot de passe: test123
              </div>
            </form>
          ) : (
            <form onSubmit={handleDolibarrLogin} className="space-y-5">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                URL Dolibarr
              </label>
              <div className="relative">
                <Server className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  type="url"
                  value={dolibarrUrl}
                  onChange={(e) => setDolibarrUrl(e.target.value)}
                  className="input-premium pl-12"
                  placeholder="https://crm.mv-3pro.ch"
                  required
                />
              </div>
              <p className="mt-1 text-xs text-gray-500">
                L'URL de votre instance Dolibarr
              </p>
            </div>

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
              <div className="bg-red-50 border border-red-200 rounded-xl p-3 text-red-700 text-sm animate-fade-in">
                {error}
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

            <div className="mt-6 p-4 bg-blue-50 border border-blue-100 rounded-xl">
              <p className="text-sm text-gray-700 font-medium mb-2">Comment obtenir votre DOLAPIKEY ?</p>
              <ol className="text-xs text-gray-600 space-y-1 list-decimal list-inside">
                <li>Connectez-vous à Dolibarr</li>
                <li>Cliquez sur votre nom en haut à droite</li>
                <li>Allez dans "Modifier ma fiche utilisateur"</li>
                <li>Onglet "Clé API" → "Générer une nouvelle clé"</li>
                <li>Copiez la clé et collez-la ici</li>
              </ol>
            </div>
          </form>
          )}

          <div className="mt-4 text-center text-sm text-gray-600">
            Mode hors-ligne disponible après première connexion
          </div>
        </div>

        <div className="mt-6 text-center text-white text-xs opacity-75">
          Version 1.0.0 • © 2024 MV3 Pro
        </div>
      </div>
    </div>
  );
}
