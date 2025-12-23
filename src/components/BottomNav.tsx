import { Home, FileText, Clipboard, Layers, Package, Calendar, User } from 'lucide-react';

type Screen = 'dashboard' | 'reports' | 'regie' | 'sens-pose' | 'materiel' | 'planning' | 'profile';

interface BottomNavProps {
  currentScreen: Screen;
  onNavigate: (screen: Screen) => void;
}

export default function BottomNav({ currentScreen, onNavigate }: BottomNavProps) {
  const navItems = [
    { id: 'dashboard' as Screen, icon: Home, label: 'Accueil' },
    { id: 'reports' as Screen, icon: FileText, label: 'Rapports' },
    { id: 'regie' as Screen, icon: Clipboard, label: 'Régie' },
    { id: 'sens-pose' as Screen, icon: Layers, label: 'Sens pose' },
    { id: 'profile' as Screen, icon: User, label: 'Profil' },
  ];

  return (
    <nav className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 safe-area-bottom z-50">
      <div className="grid grid-cols-5 h-16">
        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = currentScreen === item.id;

          return (
            <button
              key={item.id}
              onClick={() => onNavigate(item.id)}
              className={`bottom-nav-item ${isActive ? 'active' : ''}`}
            >
              <Icon className={`w-5 h-5 mb-1 ${isActive ? 'stroke-[2.5]' : ''}`} />
              <span className="text-[10px]">{item.label}</span>
            </button>
          );
        })}
      </div>
    </nav>
  );
}
