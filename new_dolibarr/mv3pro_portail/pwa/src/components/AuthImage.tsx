import { useEffect, useState } from 'react';
import { storage } from '../lib/api';

interface AuthImageProps {
  src: string;
  alt: string;
  style?: React.CSSProperties;
  className?: string;
  loading?: 'lazy' | 'eager';
  onClick?: (e: React.MouseEvent<HTMLImageElement>) => void;
}

/**
 * Composant Image qui charge les images avec authentification
 * Convertit l'image en blob URL pour contourner la limitation du navigateur
 * qui ne peut pas envoyer de headers custom avec <img src="">
 */
export function AuthImage({ src, alt, style, className, loading, onClick }: AuthImageProps) {
  const [blobUrl, setBlobUrl] = useState<string | null>(null);
  const [error, setError] = useState<boolean>(false);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    let mounted = true;
    let objectUrl: string | null = null;

    const loadImage = async () => {
      try {
        setIsLoading(true);
        setError(false);

        const token = storage.getToken();
        if (!token) {
          console.error('[AuthImage] Token manquant');
          throw new Error('Token manquant');
        }

        // Ajouter le domaine complet si l'URL est relative
        const imageUrl = src.startsWith('http')
          ? src
          : `${window.location.origin}${src}`;

        console.log('[AuthImage] Chargement:', imageUrl);
        console.log('[AuthImage] Token présent:', token.substring(0, 20) + '...');

        const response = await fetch(imageUrl, {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-Auth-Token': token
          }
        });

        console.log('[AuthImage] Réponse HTTP:', response.status, response.statusText);

        if (!response.ok) {
          const text = await response.text();
          console.error('[AuthImage] Erreur réponse:', text.substring(0, 500));
          throw new Error(`HTTP ${response.status}`);
        }

        const blob = await response.blob();
        console.log('[AuthImage] Blob reçu:', blob.size, 'bytes, type:', blob.type);

        if (!mounted) {
          return;
        }

        objectUrl = URL.createObjectURL(blob);
        setBlobUrl(objectUrl);
        setIsLoading(false);
        console.log('[AuthImage] Image chargée avec succès');
      } catch (err) {
        console.error('[AuthImage] Erreur chargement:', err, 'pour URL:', src);
        if (mounted) {
          setError(true);
          setIsLoading(false);
        }
      }
    };

    loadImage();

    // Cleanup: libérer la mémoire du blob URL
    return () => {
      mounted = false;
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
      }
    };
  }, [src]);

  if (isLoading) {
    return (
      <div
        style={{
          ...style,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#f3f4f6'
        }}
        className={className}
      >
        <div style={{
          width: '32px',
          height: '32px',
          border: '3px solid #e5e7eb',
          borderTopColor: '#3b82f6',
          borderRadius: '50%',
          animation: 'spin 0.8s linear infinite'
        }} />
      </div>
    );
  }

  if (error || !blobUrl) {
    return (
      <div
        style={{
          ...style,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#fee2e2',
          color: '#ef4444',
          fontSize: '32px'
        }}
        className={className}
      >
        ❌
      </div>
    );
  }

  return (
    <img
      src={blobUrl}
      alt={alt}
      style={style}
      className={className}
      loading={loading}
      onClick={onClick}
    />
  );
}
