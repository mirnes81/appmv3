import { useState } from 'react';
import { Fingerprint, Mail, Lock, Eye, EyeOff } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

export default function LoginScreen() {
  const { login, enableBiometric } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [biometricAvailable] = useState('credentials' in navigator && 'PublicKeyCredential' in window);

  const handleLogin = async (e: React.FormEvent, useBiometric = false) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(email, password, useBiometric);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur de connexion');
    } finally {
      setLoading(false);
    }
  };

  const handleBiometricLogin = async () => {
    if (!email || !password) {
      setError('Veuillez d\'abord vous connecter avec email/mot de passe');
      return;
    }

    try {
      await handleLogin(new Event('submit') as any, true);
    } catch (err) {
      setError('Authentification biométrique échouée');
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
          <form onSubmit={handleLogin} className="space-y-4">
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
                  autoComplete="email"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Mot de passe
              </label>
              <div className="relative">
                <Lock className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="input-premium pl-12 pr-12"
                  placeholder="••••••••"
                  required
                  autoComplete="current-password"
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

            {biometricAvailable && (
              <>
                <div className="relative my-6">
                  <div className="absolute inset-0 flex items-center">
                    <div className="w-full border-t border-gray-300"></div>
                  </div>
                  <div className="relative flex justify-center text-sm">
                    <span className="px-4 bg-white text-gray-500">ou</span>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handleBiometricLogin}
                  className="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-xl
                           hover:from-blue-700 hover:to-blue-800 active:scale-95 transition-all shadow-lg
                           flex items-center justify-center space-x-2"
                  disabled={loading}
                >
                  <Fingerprint className="w-5 h-5" />
                  <span>Connexion biométrique</span>
                </button>
              </>
            )}
          </form>

          <div className="mt-6 text-center text-sm text-gray-600">
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
