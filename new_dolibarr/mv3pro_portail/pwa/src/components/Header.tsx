import { useNavigate } from 'react-router-dom';

interface HeaderProps {
  title?: string;
  showBack?: boolean;
  onBack?: () => void;
}

export function Header({ title = 'MV3 PRO', showBack = false, onBack }: HeaderProps) {
  const navigate = useNavigate();

  const handleBack = () => {
    if (onBack) {
      onBack();
    } else {
      navigate(-1);
    }
  };

  return (
    <header
      style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: '16px 20px',
        background: 'linear-gradient(135deg, #0891b2 0%, #06b6d4 100%)',
        color: 'white',
        position: 'sticky',
        top: 0,
        zIndex: 100,
        boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
      }}
    >
      {showBack ? (
        <button
          onClick={handleBack}
          style={{
            background: 'none',
            border: 'none',
            color: 'white',
            fontSize: '28px',
            padding: '4px',
            cursor: 'pointer',
          }}
          aria-label="Retour"
        >
          ←
        </button>
      ) : (
        <div style={{ width: '36px' }} />
      )}

      <h1
        style={{
          fontSize: '18px',
          fontWeight: '600',
          margin: 0,
          flex: 1,
          textAlign: 'center',
        }}
      >
        {title}
      </h1>

      <div style={{ width: '36px' }} />
    </header>
  );
}
