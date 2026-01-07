import { ReactNode } from 'react';
import { Header } from './Header';
import { BottomNav } from './BottomNav';
import { useOnline } from '../hooks/useOnline';

interface LayoutProps {
  children: ReactNode;
  title?: string;
  showBack?: boolean;
  onBack?: () => void;
  showBottomNav?: boolean;
}

export function Layout({
  children,
  title,
  showBack = false,
  onBack,
  showBottomNav = true,
}: LayoutProps) {
  const isOnline = useOnline();

  return (
    <div style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column' }}>
      {!isOnline && (
        <div className="offline-banner">
          Mode hors ligne - Certaines fonctionnalités sont limitées
        </div>
      )}

      <Header title={title} showBack={showBack} onBack={onBack} />

      <main style={{ flex: 1, paddingBottom: showBottomNav ? '80px' : '20px' }}>
        {children}
      </main>

      {showBottomNav && <BottomNav />}
    </div>
  );
}
