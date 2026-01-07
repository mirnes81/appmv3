export function LoadingSpinner({ size = 40 }: { size?: number }) {
  return (
    <div
      style={{
        border: `3px solid #e5e7eb`,
        borderTop: `3px solid #0891b2`,
        borderRadius: '50%',
        width: `${size}px`,
        height: `${size}px`,
        animation: 'spin 1s linear infinite',
        margin: '20px auto',
      }}
    />
  );
}
