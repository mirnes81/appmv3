import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { AuthImage } from '../components/AuthImage';
import { apiClient } from '../lib/api';

interface EventFile {
  name: string;
  size: number;
  size_human: string;
  mime: string;
  is_image: boolean;
  url: string;
}

interface EventDetail {
  id: number;
  titre: string;
  type_code: string;
  date_debut: string;
  date_fin: string;
  all_day: number;
  lieu: string;
  description: string;
  progression: number;
  user?: {
    id: number;
    nom_complet: string;
    login: string;
  };
  societe?: {
    id: number;
    nom: string;
    type: number;
  };
  projet?: {
    id: number;
    ref: string;
    titre: string;
  };
  objet_lie?: {
    type: string;
    type_label: string;
    id: number;
    ref: string;
  };
  fichiers: EventFile[];
}

type TabType = 'details' | 'photos' | 'files';

export function PlanningDetail() {
  const { id } = useParams();
  const [event, setEvent] = useState<EventDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [activeTab, setActiveTab] = useState<TabType>('details');
  const [selectedPhoto, setSelectedPhoto] = useState<string | null>(null);

  useEffect(() => {
    loadEventDetail();
  }, [id]);

  const loadEventDetail = async () => {
    try {
      setLoading(true);
      setError('');
      console.log('[PlanningDetail] Loading event ID:', id);
      console.log('[PlanningDetail] API URL:', `/planning_view.php?id=${id}`);
      const data = await apiClient(`/planning_view.php?id=${id}`);
      console.log('[PlanningDetail] Event data received:', data);
      setEvent(data);
    } catch (err: any) {
      console.error('[PlanningDetail] Erreur chargement événement:', err);
      console.error('[PlanningDetail] Error status:', err.status);
      console.error('[PlanningDetail] Error data:', err.data);
      setError(err.message || 'Erreur de chargement');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateStr: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const openFile = async (fileUrl: string, fileName: string) => {
    try {
      const token = localStorage.getItem('mv3pro_token');
      if (!token) {
        alert('Token manquant. Veuillez vous reconnecter.');
        return;
      }

      const response = await fetch(fileUrl, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'X-Auth-Token': token
        }
      });

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}`);
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);

      const newWindow = window.open(url, '_blank');

      if (newWindow) {
        newWindow.document.title = fileName;
      }
    } catch (err: any) {
      console.error('Erreur ouverture fichier:', err);
      alert('Erreur lors de l\'ouverture du fichier: ' + (err.message || 'Erreur inconnue'));
    }
  };

  if (loading) {
    return (
      <Layout title="Chargement..." showBack>
        <div style={{ padding: '20px', textAlign: 'center' }}>
          <div className="spinner" />
        </div>
      </Layout>
    );
  }

  if (error || !event) {
    return (
      <Layout title="Erreur" showBack>
        <div style={{ padding: '20px' }}>
          <div className="card" style={{ padding: '20px', textAlign: 'center' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>⚠️</div>
            <p style={{ color: '#ef4444' }}>{error || 'Événement non trouvé'}</p>
          </div>
        </div>
      </Layout>
    );
  }

  const photos = event.fichiers.filter(f => f.is_image);
  const files = event.fichiers.filter(f => !f.is_image);

  return (
    <Layout title={event.titre} showBack>
      <div style={{ paddingBottom: '80px' }}>

        {/* Tabs Navigation */}
        <div style={{
          display: 'flex',
          backgroundColor: '#fff',
          borderBottom: '2px solid #e5e7eb',
          position: 'sticky',
          top: 0,
          zIndex: 10
        }}>
          <button
            onClick={() => setActiveTab('details')}
            style={{
              flex: 1,
              padding: '16px',
              backgroundColor: activeTab === 'details' ? '#3b82f6' : 'transparent',
              color: activeTab === 'details' ? '#fff' : '#6b7280',
              border: 'none',
              fontSize: '16px',
              fontWeight: '600',
              cursor: 'pointer',
              transition: 'all 0.2s'
            }}
          >
            📋 Détails
          </button>
          <button
            onClick={() => setActiveTab('photos')}
            style={{
              flex: 1,
              padding: '16px',
              backgroundColor: activeTab === 'photos' ? '#3b82f6' : 'transparent',
              color: activeTab === 'photos' ? '#fff' : '#6b7280',
              border: 'none',
              fontSize: '16px',
              fontWeight: '600',
              cursor: 'pointer',
              transition: 'all 0.2s',
              position: 'relative'
            }}
          >
            📸 Photos
            {photos.length > 0 && (
              <span style={{
                marginLeft: '8px',
                padding: '2px 8px',
                backgroundColor: activeTab === 'photos' ? '#1e40af' : '#3b82f6',
                color: '#fff',
                borderRadius: '12px',
                fontSize: '12px',
                fontWeight: '700'
              }}>
                {photos.length}
              </span>
            )}
          </button>
          <button
            onClick={() => setActiveTab('files')}
            style={{
              flex: 1,
              padding: '16px',
              backgroundColor: activeTab === 'files' ? '#3b82f6' : 'transparent',
              color: activeTab === 'files' ? '#fff' : '#6b7280',
              border: 'none',
              fontSize: '16px',
              fontWeight: '600',
              cursor: 'pointer',
              transition: 'all 0.2s',
              position: 'relative'
            }}
          >
            📎 Fichiers
            {files.length > 0 && (
              <span style={{
                marginLeft: '8px',
                padding: '2px 8px',
                backgroundColor: activeTab === 'files' ? '#1e40af' : '#3b82f6',
                color: '#fff',
                borderRadius: '12px',
                fontSize: '12px',
                fontWeight: '700'
              }}>
                {files.length}
              </span>
            )}
          </button>
        </div>

        {/* Tab Content */}
        <div style={{ padding: '20px' }}>

          {/* Onglet Détails */}
          {activeTab === 'details' && (
            <div>
              {/* Informations principales */}
              <div className="card" style={{ marginBottom: '16px', padding: '16px' }}>
                <h2 style={{ fontSize: '20px', fontWeight: '600', marginBottom: '16px' }}>
                  {event.titre}
                </h2>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  {/* Dates */}
                  <div>
                    <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '4px' }}>
                      📅 Date
                    </div>
                    <div style={{ fontSize: '16px' }}>
                      {formatDate(event.date_debut)}
                      {event.date_fin && event.date_fin !== event.date_debut && (
                        <> → {formatDate(event.date_fin)}</>
                      )}
                      {event.all_day === 1 && <span style={{ marginLeft: '8px', fontSize: '14px', color: '#6b7280' }}>(Journée entière)</span>}
                    </div>
                  </div>

                  {/* Lieu */}
                  {event.lieu && (
                    <div>
                      <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '4px' }}>
                        📍 Lieu
                      </div>
                      <div style={{ fontSize: '16px' }}>{event.lieu}</div>
                    </div>
                  )}

                  {/* Progression */}
                  {event.progression > 0 && (
                    <div>
                      <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '4px' }}>
                        ⏳ Progression
                      </div>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <div style={{ flex: 1, height: '8px', backgroundColor: '#e5e7eb', borderRadius: '4px', overflow: 'hidden' }}>
                          <div style={{ height: '100%', width: `${event.progression}%`, backgroundColor: '#3b82f6', transition: 'width 0.3s' }} />
                        </div>
                        <span style={{ fontSize: '14px', fontWeight: '500' }}>{event.progression}%</span>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Utilisateur assigné */}
              {event.user && (
                <div className="card" style={{ marginBottom: '16px', padding: '16px' }}>
                  <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '8px' }}>
                    👤 Assigné à
                  </div>
                  <div style={{ fontSize: '16px', fontWeight: '500' }}>
                    {event.user.nom_complet}
                  </div>
                  <div style={{ fontSize: '14px', color: '#6b7280' }}>
                    {event.user.login}
                  </div>
                </div>
              )}

              {/* Société */}
              {event.societe && (
                <div className="card" style={{ marginBottom: '16px', padding: '16px' }}>
                  <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '8px' }}>
                    🏢 Société
                  </div>
                  <div style={{ fontSize: '16px', fontWeight: '500' }}>
                    {event.societe.nom}
                  </div>
                </div>
              )}

              {/* Projet */}
              {event.projet && (
                <div className="card" style={{ marginBottom: '16px', padding: '16px' }}>
                  <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '8px' }}>
                    📁 Projet
                  </div>
                  <div style={{ fontSize: '16px', fontWeight: '500' }}>
                    {event.projet.ref}
                  </div>
                  {event.projet.titre && (
                    <div style={{ fontSize: '14px', color: '#6b7280' }}>
                      {event.projet.titre}
                    </div>
                  )}
                </div>
              )}

              {/* Objet lié */}
              {event.objet_lie && (
                <div className="card" style={{ marginBottom: '16px', padding: '16px' }}>
                  <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '8px' }}>
                    🔗 Lié à
                  </div>
                  <div style={{ fontSize: '16px', fontWeight: '500' }}>
                    {event.objet_lie.type_label} {event.objet_lie.ref}
                  </div>
                </div>
              )}

              {/* Description */}
              {event.description && (
                <div className="card" style={{ marginBottom: '16px', padding: '16px' }}>
                  <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '8px' }}>
                    📝 Description
                  </div>
                  <div style={{ fontSize: '14px', lineHeight: '1.5', whiteSpace: 'pre-wrap' }}>
                    {event.description}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Onglet Photos */}
          {activeTab === 'photos' && (
            <div>
              {photos.length === 0 ? (
                <div className="card" style={{ padding: '40px', textAlign: 'center' }}>
                  <div style={{ fontSize: '48px', marginBottom: '16px' }}>📸</div>
                  <p style={{ color: '#6b7280' }}>Aucune photo jointe à cet événement</p>
                </div>
              ) : (
                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))',
                  gap: '12px'
                }}>
                  {photos.map((photo, index) => (
                    <div
                      key={index}
                      onClick={() => setSelectedPhoto(photo.url)}
                      style={{
                        position: 'relative',
                        aspectRatio: '1',
                        backgroundColor: '#f3f4f6',
                        borderRadius: '12px',
                        overflow: 'hidden',
                        cursor: 'pointer',
                        transition: 'transform 0.2s, box-shadow 0.2s',
                        boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
                      }}
                      onMouseOver={(e) => {
                        e.currentTarget.style.transform = 'scale(1.05)';
                        e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.2)';
                      }}
                      onMouseOut={(e) => {
                        e.currentTarget.style.transform = 'scale(1)';
                        e.currentTarget.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                      }}
                    >
                      <AuthImage
                        src={photo.url}
                        alt={photo.name}
                        style={{
                          width: '100%',
                          height: '100%',
                          objectFit: 'cover'
                        }}
                        loading="lazy"
                      />
                      <div style={{
                        position: 'absolute',
                        bottom: 0,
                        left: 0,
                        right: 0,
                        padding: '8px',
                        background: 'linear-gradient(to top, rgba(0,0,0,0.7), transparent)',
                        color: '#fff',
                        fontSize: '12px',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap'
                      }}>
                        {photo.name}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Onglet Fichiers */}
          {activeTab === 'files' && (
            <div>
              {files.length === 0 ? (
                <div className="card" style={{ padding: '40px', textAlign: 'center' }}>
                  <div style={{ fontSize: '48px', marginBottom: '16px' }}>📎</div>
                  <p style={{ color: '#6b7280' }}>Aucun fichier joint à cet événement</p>
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  {files.map((file, index) => (
                    <div
                      key={index}
                      className="card"
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '12px',
                        padding: '16px',
                        transition: 'transform 0.2s, box-shadow 0.2s'
                      }}
                    >
                      {/* Icône */}
                      <div style={{
                        width: '56px',
                        height: '56px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        backgroundColor: file.mime === 'application/pdf' ? '#fee2e2' : '#f3f4f6',
                        borderRadius: '12px',
                        fontSize: '32px',
                        flexShrink: 0
                      }}>
                        {file.mime === 'application/pdf' ? '📕' :
                         file.mime.includes('word') ? '📘' :
                         file.mime.includes('excel') || file.mime.includes('spreadsheet') ? '📗' :
                         file.mime.includes('zip') ? '🗜️' : '📄'}
                      </div>

                      {/* Info fichier */}
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ fontSize: '16px', fontWeight: '500', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', marginBottom: '4px' }}>
                          {file.name}
                        </div>
                        <div style={{ fontSize: '14px', color: '#6b7280' }}>
                          {file.size_human}
                        </div>
                      </div>

                      {/* Bouton ouvrir */}
                      <button
                        onClick={() => openFile(file.url, file.name)}
                        style={{
                          padding: '10px 20px',
                          backgroundColor: '#3b82f6',
                          color: 'white',
                          border: 'none',
                          borderRadius: '8px',
                          fontSize: '14px',
                          fontWeight: '600',
                          cursor: 'pointer',
                          whiteSpace: 'nowrap',
                          transition: 'background-color 0.2s',
                          flexShrink: 0
                        }}
                        onMouseOver={(e) => e.currentTarget.style.backgroundColor = '#2563eb'}
                        onMouseOut={(e) => e.currentTarget.style.backgroundColor = '#3b82f6'}
                      >
                        Ouvrir
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

        </div>

        {/* Modal Plein écran pour les photos */}
        {selectedPhoto && (
          <div
            onClick={() => setSelectedPhoto(null)}
            style={{
              position: 'fixed',
              top: 0,
              left: 0,
              right: 0,
              bottom: 0,
              backgroundColor: 'rgba(0, 0, 0, 0.95)',
              zIndex: 1000,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              padding: '20px'
            }}
          >
            <button
              onClick={(e) => {
                e.stopPropagation();
                setSelectedPhoto(null);
              }}
              style={{
                position: 'absolute',
                top: '20px',
                right: '20px',
                width: '48px',
                height: '48px',
                backgroundColor: 'rgba(255, 255, 255, 0.2)',
                border: 'none',
                borderRadius: '50%',
                color: '#fff',
                fontSize: '24px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'background-color 0.2s'
              }}
              onMouseOver={(e) => e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.3)'}
              onMouseOut={(e) => e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.2)'}
            >
              ✕
            </button>
            <AuthImage
              src={selectedPhoto}
              alt="Photo en plein écran"
              style={{
                maxWidth: '100%',
                maxHeight: '100%',
                objectFit: 'contain',
                borderRadius: '8px'
              }}
              onClick={(e) => e.stopPropagation()}
            />
          </div>
        )}

      </div>
    </Layout>
  );
}
