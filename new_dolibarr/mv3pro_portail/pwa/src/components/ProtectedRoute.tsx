import { useEffect, useState } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { LoadingSpinner } from './LoadingSpinner';
import { storage } from '../lib/api';

export function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, loading: authLoading } = useAuth();
  const [checking, setChecking] = useState(true);
  const [hasValidToken, setHasValidToken] = useState(false);
  const location = useLocation();

  useEffect(() => {
    const token = storage.getToken();

    if (!token) {
      console.log('[ProtectedRoute] No token found');
      setHasValidToken(false);
      setChecking(false);
      return;
    }

    // Token existe, on vérifie avec /me.php
    const checkToken = async () => {
      try {
        console.log('[ProtectedRoute] Checking token with /me.php');
        const response = await fetch('/custom/mv3pro_portail/api/v1/me.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-Auth-Token': token,
          },
        });

        console.log('[ProtectedRoute] /me.php response:', response.status);

        if (response.status === 401) {
          console.log('[ProtectedRoute] Token invalid (401), clearing');
          storage.clearToken();
          setHasValidToken(false);
        } else if (response.ok) {
          console.log('[ProtectedRoute] Token valid');
          setHasValidToken(true);
        } else {
          // Erreur serveur (500), on garde le token et on affiche l'erreur
          console.error('[ProtectedRoute] Server error:', response.status);
          setHasValidToken(true); // On laisse passer pour afficher l'erreur
        }
      } catch (error) {
        console.error('[ProtectedRoute] Error checking token:', error);
        setHasValidToken(true); // On laisse passer en cas d'erreur réseau
      } finally {
        setChecking(false);
      }
    };

    checkToken();
  }, [location.pathname]);

  const loading = authLoading || checking;

  if (loading) {
    return (
      <div style={{ padding: '40px', textAlign: 'center' }}>
        <LoadingSpinner />
        <p style={{ marginTop: '16px', color: '#6b7280' }}>Vérification...</p>
      </div>
    );
  }

  if (!hasValidToken || !isAuthenticated) {
    console.log('[ProtectedRoute] Redirecting to login', { hasValidToken, isAuthenticated });
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}
