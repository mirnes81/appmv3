import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Layout } from '../components/Layout';
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

export function PlanningDetail() {
  const { id } = useParams();
  const [event, setEvent] = useState<EventDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    loadEventDetail();
  }, [id]);

  const loadEventDetail = async () => {
    try {
      setLoading(true);
      setError('');
      const data = await apiClient(`/planning_view.php?id=${id}`);
      setEvent(data);
    } catch (err: any) {
      console.error('Erreur chargement événement:', err);
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

  return (
    <Layout title={event.titre} showBack>
      <div style={{ padding: '20px', paddingBottom: '80px' }}>

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

        {/* Fichiers joints */}
        {event.fichiers && event.fichiers.length > 0 && (
          <div className="card" style={{ marginBottom: '16px', padding: '16px' }}>
            <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '12px' }}>
              📎 Fichiers joints ({event.fichiers.length})
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
              {event.fichiers.map((file, index) => (
                <div
                  key={index}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '12px',
                    padding: '12px',
                    backgroundColor: '#f9fafb',
                    borderRadius: '8px',
                    border: '1px solid #e5e7eb'
                  }}
                >
                  {/* Icône */}
                  <div style={{
                    width: '48px',
                    height: '48px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: file.is_image ? '#dbeafe' : '#f3f4f6',
                    borderRadius: '8px',
                    fontSize: '24px'
                  }}>
                    {file.is_image ? '🖼️' : file.mime === 'application/pdf' ? '📕' : '📄'}
                  </div>

                  {/* Info fichier */}
                  <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontSize: '14px', fontWeight: '500', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                      {file.name}
                    </div>
                    <div style={{ fontSize: '12px', color: '#6b7280' }}>
                      {file.size_human}
                    </div>
                  </div>

                  {/* Bouton ouvrir */}
                  <button
                    onClick={() => openFile(file.url, file.name)}
                    style={{
                      padding: '8px 16px',
                      backgroundColor: '#3b82f6',
                      color: 'white',
                      border: 'none',
                      borderRadius: '6px',
                      fontSize: '14px',
                      fontWeight: '500',
                      cursor: 'pointer',
                      whiteSpace: 'nowrap'
                    }}
                  >
                    Ouvrir
                  </button>
                </div>
              ))}
            </div>
          </div>
        )}

      </div>
    </Layout>
  );
}
