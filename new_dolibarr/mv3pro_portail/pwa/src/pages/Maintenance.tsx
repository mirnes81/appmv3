export function Maintenance() {
  return (
    <div style={{
      minHeight: '100vh',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      padding: '40px 20px',
      background: 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)',
      textAlign: 'center'
    }}>
      <div style={{
        background: 'white',
        borderRadius: '16px',
        padding: '40px',
        maxWidth: '500px',
        width: '100%',
        boxShadow: '0 10px 30px rgba(0,0,0,0.2)'
      }}>
        <div style={{ fontSize: '80px', marginBottom: '24px' }}>
          🚧
        </div>

        <h1 style={{
          fontSize: '28px',
          fontWeight: '700',
          color: '#92400e',
          marginBottom: '16px'
        }}>
          Application en maintenance
        </h1>

        <p style={{
          fontSize: '16px',
          color: '#78350f',
          lineHeight: '1.6',
          marginBottom: '24px'
        }}>
          L'application est actuellement en cours de mise à jour pour vous offrir de nouvelles fonctionnalités et améliorations.
        </p>

        <div style={{
          background: '#fef3c7',
          border: '2px solid #fbbf24',
          borderRadius: '8px',
          padding: '16px',
          marginBottom: '24px'
        }}>
          <p style={{
            fontSize: '14px',
            color: '#92400e',
            margin: 0
          }}>
            Veuillez réessayer dans quelques instants. Si le problème persiste, contactez votre responsable.
          </p>
        </div>

        <button
          onClick={() => window.location.reload()}
          style={{
            width: '100%',
            padding: '14px',
            backgroundColor: '#f59e0b',
            color: 'white',
            border: 'none',
            borderRadius: '8px',
            fontSize: '16px',
            fontWeight: '600',
            cursor: 'pointer',
            transition: 'background-color 0.2s'
          }}
          onMouseOver={(e) => e.currentTarget.style.backgroundColor = '#d97706'}
          onMouseOut={(e) => e.currentTarget.style.backgroundColor = '#f59e0b'}
        >
          Réessayer
        </button>
      </div>
    </div>
  );
}
