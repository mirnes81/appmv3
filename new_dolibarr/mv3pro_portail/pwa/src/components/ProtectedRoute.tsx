import { useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { LoadingSpinner } from './LoadingSpinner';
import { storage } from '../lib/api';

// Cache global pour éviter les vérifications répétées
let tokenCheckCache: { token: string; valid: boolean; timestamp: number } | null = null;
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

export function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, loading: authLoading } = useAuth();
  const [checking, setChecking] = useState(true);
  const [hasValidToken, setHasValidToken] = useState(false);

  useEffect(() => {
    const token = storage.getToken();

    if (!token) {
      console.log('[ProtectedRoute] No token found');
      setHasValidToken(false);
      setChecking(false);
      return;
    }

    // Vérifier le cache
    const now = Date.now();
    if (tokenCheckCache && tokenCheckCache.token === token && (now - tokenCheckCache.timestamp) < CACHE_DURATION) {
      console.log('[ProtectedRoute] Using cached token validation');
      setHasValidToken(tokenCheckCache.valid);
      setChecking(false);
      return;
    }

    // Token existe et pas de cache valide, on vérifie avec /me.php
    const checkToken = async () => {
      try {
        console.log('[ProtectedRoute] Checking token with /me.php (no cache)');
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
          tokenCheckCache = null;
          setHasValidToken(false);
        } else if (response.ok) {
          console.log('[ProtectedRoute] Token valid');
          // Mettre en cache
          tokenCheckCache = {
            token,
            valid: true,
            timestamp: now,
          };
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
  }, []); // Pas de dépendance location.pathname pour éviter les appels répétés

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
