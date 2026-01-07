import { NavLink } from 'react-router-dom';

interface NavItem {
  icon: string;
  label: string;
  path: string;
}

const navItems: NavItem[] = [
  { icon: '🏠', label: 'Accueil', path: '/dashboard' },
  { icon: '📋', label: 'Rapports', path: '/rapports' },
  { icon: '📝', label: 'Régie', path: '/regie' },
  { icon: '🔔', label: 'Notifs', path: '/notifications' },
  { icon: '👤', label: 'Profil', path: '/profil' },
];

export function BottomNav() {
  return (
    <nav
      style={{
        position: 'fixed',
        bottom: 0,
        left: 0,
        right: 0,
        background: 'white',
        display: 'flex',
        justifyContent: 'space-around',
        alignItems: 'center',
        padding: '8px 0',
        boxShadow: '0 -2px 8px rgba(0, 0, 0, 0.1)',
        zIndex: 100,
      }}
    >
      {navItems.map((item) => (
        <NavLink
          key={item.path}
          to={item.path}
          style={({ isActive }) => ({
            flex: 1,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            textDecoration: 'none',
            color: isActive ? '#0891b2' : '#6b7280',
            fontSize: '12px',
            padding: '8px 4px',
            transition: 'color 150ms ease',
          })}
        >
          <div style={{ fontSize: '24px', marginBottom: '4px' }}>{item.icon}</div>
          <div>{item.label}</div>
        </NavLink>
      ))}
    </nav>
  );
}
