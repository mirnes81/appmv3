import { useAuth } from '../contexts/AuthContext';
import { User, Mail, Phone, Settings, LogOut, Fingerprint, Bell, Download } from 'lucide-react';

export default function ProfileScreen() {
  const { user, logout, enableBiometric } = useAuth();

  const handleEnableBiometric = async () => {
    const success = await enableBiometric();
    if (success) {
      alert('Authentification biométrique activée !');
    } else {
      alert('Impossible d\'activer l\'authentification biométrique');
    }
  };

  const handleLogout = async () => {
    if (confirm('Voulez-vous vraiment vous déconnecter ?')) {
      await logout();
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 safe-area-top pb-20">
      <div className="bg-gradient-to-br from-blue-600 to-blue-800 text-white p-6 rounded-b-3xl">
        <div className="flex items-center space-x-4 mb-6">
          <div className="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
            <User className="w-10 h-10" />
          </div>
          <div>
            <h1 className="text-2xl font-bold">{user?.name}</h1>
            <p className="text-blue-100 text-sm">{user?.email}</p>
          </div>
        </div>
      </div>

      <div className="p-4 space-y-3 -mt-6">
        <div className="card-premium space-y-4">
          <div className="flex items-center space-x-3">
            <Mail className="w-5 h-5 text-gray-400" />
            <div>
              <p className="text-xs text-gray-500">Email</p>
              <p className="font-medium text-gray-900">{user?.email}</p>
            </div>
          </div>

          {user?.phone && (
            <div className="flex items-center space-x-3">
              <Phone className="w-5 h-5 text-gray-400" />
              <div>
                <p className="text-xs text-gray-500">Téléphone</p>
                <p className="font-medium text-gray-900">{user.phone}</p>
              </div>
            </div>
          )}
        </div>

        <button
          onClick={handleEnableBiometric}
          className="w-full card-premium flex items-center justify-between hover:bg-gray-50
                   transition-colors cursor-pointer"
        >
          <div className="flex items-center space-x-3">
            <Fingerprint className="w-5 h-5 text-blue-600" />
            <span className="font-medium text-gray-900">Authentification biométrique</span>
          </div>
          <div className={`w-12 h-6 rounded-full transition-colors ${user?.biometric_enabled ? 'bg-green-600' : 'bg-gray-300'}`}>
            <div className={`w-5 h-5 bg-white rounded-full m-0.5 transition-transform ${user?.biometric_enabled ? 'translate-x-6' : ''}`}></div>
          </div>
        </button>

        <button className="w-full card-premium flex items-center space-x-3 hover:bg-gray-50
                         transition-colors cursor-pointer">
          <Bell className="w-5 h-5 text-gray-600" />
          <span className="font-medium text-gray-900">Notifications</span>
        </button>

        <button className="w-full card-premium flex items-center space-x-3 hover:bg-gray-50
                         transition-colors cursor-pointer">
          <Settings className="w-5 h-5 text-gray-600" />
          <span className="font-medium text-gray-900">Paramètres</span>
        </button>

        <button className="w-full card-premium flex items-center space-x-3 hover:bg-gray-50
                         transition-colors cursor-pointer">
          <Download className="w-5 h-5 text-gray-600" />
          <span className="font-medium text-gray-900">Données hors ligne</span>
        </button>

        <button
          onClick={handleLogout}
          className="w-full card-premium flex items-center space-x-3 text-red-600 hover:bg-red-50
                   transition-colors cursor-pointer"
        >
          <LogOut className="w-5 h-5" />
          <span className="font-medium">Se déconnecter</span>
        </button>

        <div className="text-center text-xs text-gray-500 pt-4">
          <p>Version 1.0.0</p>
          <p className="mt-1">© 2024 MV3 Pro</p>
        </div>
      </div>
    </div>
  );
}
