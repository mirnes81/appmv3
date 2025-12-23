import { useState, useEffect } from 'react';
import { AuthProvider } from './contexts/AuthContext';
import { OfflineProvider } from './contexts/OfflineContext';
import LoginScreen from './screens/LoginScreen';
import Dashboard from './screens/Dashboard';
import ReportsScreen from './screens/ReportsScreen';
import RegieScreen from './screens/RegieScreen';
import SensPoseScreen from './screens/SensPoseScreen';
import MaterielScreen from './screens/MaterielScreen';
import PlanningScreen from './screens/PlanningScreen';
import ProfileScreen from './screens/ProfileScreen';
import BottomNav from './components/BottomNav';
import { useAuth } from './contexts/AuthContext';

type Screen = 'dashboard' | 'reports' | 'regie' | 'sens-pose' | 'materiel' | 'planning' | 'profile';

function AppContent() {
  const { user, loading } = useAuth();
  const [currentScreen, setCurrentScreen] = useState<Screen>('dashboard');

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-blue-100">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-gray-600 font-medium">Chargement...</p>
        </div>
      </div>
    );
  }

  if (!user) {
    return <LoginScreen />;
  }

  const renderScreen = () => {
    switch (currentScreen) {
      case 'dashboard':
        return <Dashboard onNavigate={setCurrentScreen} />;
      case 'reports':
        return <ReportsScreen />;
      case 'regie':
        return <RegieScreen />;
      case 'sens-pose':
        return <SensPoseScreen />;
      case 'materiel':
        return <MaterielScreen />;
      case 'planning':
        return <PlanningScreen />;
      case 'profile':
        return <ProfileScreen />;
      default:
        return <Dashboard onNavigate={setCurrentScreen} />;
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 pb-20 safe-area-bottom">
      {renderScreen()}
      <BottomNav currentScreen={currentScreen} onNavigate={setCurrentScreen} />
    </div>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <OfflineProvider>
        <AppContent />
      </OfflineProvider>
    </AuthProvider>
  );
}
