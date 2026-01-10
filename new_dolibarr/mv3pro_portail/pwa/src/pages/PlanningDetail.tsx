import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Layout } from '../components/Layout';
import { AuthImage } from '../components/AuthImage';
import { apiClient } from '../lib/api';

interface FileInfo {
  name: string;
  path: string;
  size: number;
  date: string;
  type: 'image' | 'document' | 'other';
  is_image: boolean;
  url: string;
}

interface ObjectDetail {
  id: number;
  ref: string;
  label: string;
  type: string;
  extrafields: Record<string, {
    label: string;
    value: any;
    type: string;
  }>;
  files: FileInfo[];
  files_count: number;
  photos_count: number;
  datep?: string;
  datef?: string;
  location?: string;
  note?: string;
}

type TabType = 'details' | 'photos' | 'fichiers';

export default function PlanningDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [object, setObject] = useState<ObjectDetail | null>(null);
  const [activeTab, setActiveTab] = useState<TabType>('details');
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [selectedImage, setSelectedImage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    loadObject();
  }, [id]);

  const loadObject = async () => {
    if (!id) return;

    try {
      setLoading(true);
      setError(null);

      const response = await apiClient.get(`/object/get.php?type=actioncomm&id=${id}`);
      setObject(response);
    } catch (err: any) {
      console.error('[PlanningDetail] Erreur chargement:', err);
      setError(err.message || 'Erreur lors du chargement');
    } finally {
      setLoading(false);
    }
  };

  const compressImage = async (file: File, maxWidth: number = 1920, maxHeight: number = 1920, quality: number = 0.85): Promise<File> => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = (event) => {
        const img = new Image();
        img.src = event.target?.result as string;
        img.onload = () => {
          let width = img.width;
          let height = img.height;

          if (width > maxWidth || height > maxHeight) {
            if (width > height) {
              height = Math.round((height * maxWidth) / width);
              width = maxWidth;
            } else {
              width = Math.round((width * maxHeight) / height);
              height = maxHeight;
            }
          }

          const canvas = document.createElement('canvas');
          canvas.width = width;
          canvas.height = height;
          const ctx = canvas.getContext('2d');
          ctx?.drawImage(img, 0, 0, width, height);

          canvas.toBlob(
            (blob) => {
              if (blob) {
                const compressedFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                  type: 'image/jpeg',
                  lastModified: Date.now(),
                });
                console.log(`[Compression] ${(file.size / 1024).toFixed(0)} KB → ${(compressedFile.size / 1024).toFixed(0)} KB (${Math.round(100 - (compressedFile.size / file.size) * 100)}% réduction)`);
                resolve(compressedFile);
              } else {
                reject(new Error('Erreur compression'));
              }
            },
            'image/jpeg',
            quality
          );
        };
        img.onerror = () => reject(new Error('Erreur chargement image'));
      };
      reader.onerror = () => reject(new Error('Erreur lecture fichier'));
    });
  };

  const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file || !object) return;

    setUploading(true);
    setUploadProgress(0);

    try {
      console.log('[Upload] Fichier:', file.name, '- Taille:', (file.size / 1024 / 1024).toFixed(2), 'MB');

      let fileToUpload = file;

      if (file.type.startsWith('image/')) {
        const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        const shouldCompress = isMobile || file.size > 300 * 1024;

        if (shouldCompress) {
          console.log('[Upload] Compression... (Mobile:', isMobile, ')');
          setUploadProgress(10);

          let maxSize = 1920;
          let quality = 0.85;

          if (file.size > 10 * 1024 * 1024) {
            maxSize = 1600;
            quality = 0.70;
            console.log('[Upload] Mode MAXIMALE (>10MB)');
          } else if (file.size > 5 * 1024 * 1024) {
            maxSize = 1600;
            quality = 0.75;
            console.log('[Upload] Mode FORTE (>5MB)');
          } else if (isMobile) {
            maxSize = 1600;
            quality = 0.80;
            console.log('[Upload] Mode MOBILE');
          }

          fileToUpload = await compressImage(file, maxSize, maxSize, quality);
          console.log('[Upload] Taille finale:', (fileToUpload.size / 1024 / 1024).toFixed(2), 'MB');
        }
      }

      const formData = new FormData();
      formData.append('file', fileToUpload);
      formData.append('type', 'actioncomm');
      formData.append('id', object.id.toString());

      setUploadProgress(30);

      await apiClient.upload('/object/upload.php', formData, (progress) => {
        setUploadProgress(30 + (progress * 0.7));
      });

      setUploadProgress(100);
      console.log('[Upload] ✅ Succès!');

      await loadObject();

      if (fileToUpload.type.startsWith('image/')) {
        setActiveTab('photos');
      } else {
        setActiveTab('fichiers');
      }

    } catch (err: any) {
      console.error('[Upload] Erreur:', err);
      alert('Erreur lors de l\'upload: ' + (err.message || 'Erreur inconnue'));
    } finally {
      setUploading(false);
      setUploadProgress(0);
      if (e.target) {
        e.target.value = '';
      }
    }
  };

  const handleFileDelete = async (filename: string) => {
    if (!object) return;
    if (!confirm(`Supprimer "${filename}" ?`)) return;

    try {
      await apiClient.delete(`/object/file.php?type=actioncomm&id=${object.id}&filename=${encodeURIComponent(filename)}`);
      await loadObject();
    } catch (err: any) {
      console.error('[Delete] Erreur:', err);
      alert('Erreur lors de la suppression: ' + (err.message || 'Erreur inconnue'));
    }
  };

  const formatDate = (date: string | undefined) => {
    if (!date) return '';
    try {
      return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch {
      return date;
    }
  };

  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  if (loading) {
    return (
      <Layout title="Chargement...">
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
      </Layout>
    );
  }

  if (error || !object) {
    return (
      <Layout title="Erreur">
        <div className="p-4">
          <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
            <p className="text-red-800">{error || 'Objet non trouvé'}</p>
          </div>
          <button
            onClick={() => navigate('/planning')}
            className="text-blue-600 hover:underline"
          >
            ← Retour au planning
          </button>
        </div>
      </Layout>
    );
  }

  const photos = object.files.filter(f => f.is_image);
  const documents = object.files.filter(f => !f.is_image);

  return (
    <Layout title={object.label}>
      <div className="flex flex-col h-full">
        {/* Header avec bouton retour */}
        <div className="bg-white border-b px-4 py-3">
          <button
            onClick={() => navigate('/planning')}
            className="text-blue-600 hover:underline text-sm mb-2"
          >
            ← Retour
          </button>
          <h1 className="text-xl font-bold text-gray-900">{object.label}</h1>
          {object.ref && (
            <p className="text-sm text-gray-600">Réf: {object.ref}</p>
          )}
        </div>

        {/* Onglets */}
        <div className="bg-white border-b">
          <div className="flex">
            <button
              onClick={() => setActiveTab('details')}
              className={`flex-1 py-3 text-center font-medium transition-colors ${
                activeTab === 'details'
                  ? 'text-blue-600 border-b-2 border-blue-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              Détails
            </button>
            <button
              onClick={() => setActiveTab('photos')}
              className={`flex-1 py-3 text-center font-medium transition-colors ${
                activeTab === 'photos'
                  ? 'text-blue-600 border-b-2 border-blue-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              Photos ({photos.length})
            </button>
            <button
              onClick={() => setActiveTab('fichiers')}
              className={`flex-1 py-3 text-center font-medium transition-colors ${
                activeTab === 'fichiers'
                  ? 'text-blue-600 border-b-2 border-blue-600'
                  : 'text-gray-600 hover:text-gray-900'
              }`}
            >
              Fichiers ({documents.length})
            </button>
          </div>
        </div>

        {/* Contenu des onglets */}
        <div className="flex-1 overflow-y-auto bg-gray-50">
          {/* Onglet Détails */}
          {activeTab === 'details' && (
            <div className="p-4 space-y-4">
              {object.datep && (
                <div className="bg-white rounded-lg p-4">
                  <h3 className="font-semibold text-gray-700 mb-2">Date et heure</h3>
                  <p className="text-sm">
                    <span className="text-gray-600">Début:</span> {formatDate(object.datep)}
                  </p>
                  {object.datef && (
                    <p className="text-sm mt-1">
                      <span className="text-gray-600">Fin:</span> {formatDate(object.datef)}
                    </p>
                  )}
                </div>
              )}

              {object.location && (
                <div className="bg-white rounded-lg p-4">
                  <h3 className="font-semibold text-gray-700 mb-2">Lieu</h3>
                  <p className="text-sm text-gray-900">{object.location}</p>
                </div>
              )}

              {object.note && (
                <div className="bg-white rounded-lg p-4">
                  <h3 className="font-semibold text-gray-700 mb-2">Note</h3>
                  <p className="text-sm text-gray-900 whitespace-pre-wrap">{object.note}</p>
                </div>
              )}

              {Object.keys(object.extrafields).length > 0 && (
                <div className="bg-white rounded-lg p-4">
                  <h3 className="font-semibold text-gray-700 mb-3">Informations complémentaires</h3>
                  <div className="space-y-2">
                    {Object.entries(object.extrafields).map(([key, field]) => (
                      <div key={key} className="border-b border-gray-100 pb-2">
                        <p className="text-xs text-gray-600">{field.label}</p>
                        <p className="text-sm text-gray-900">{field.value || '-'}</p>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <div className="bg-blue-50 rounded-lg p-4">
                <p className="text-sm text-blue-800">
                  <strong>{object.files_count}</strong> fichiers dont <strong>{object.photos_count}</strong> photos
                </p>
              </div>
            </div>
          )}

          {/* Onglet Photos */}
          {activeTab === 'photos' && (
            <div className="p-4">
              <label className="block w-full mb-4">
                <input
                  type="file"
                  accept="image/*"
                  onChange={handleFileUpload}
                  disabled={uploading}
                  className="hidden"
                />
                <div className="bg-blue-600 text-white rounded-lg p-4 text-center cursor-pointer hover:bg-blue-700 transition-colors">
                  {uploading ? (
                    <span>📤 Upload en cours... {Math.round(uploadProgress)}%</span>
                  ) : (
                    <span>📷 Ajouter une photo</span>
                  )}
                </div>
              </label>

              {uploading && (
                <div className="mb-4 bg-white rounded-lg p-4">
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                      style={{ width: `${uploadProgress}%` }}
                    ></div>
                  </div>
                </div>
              )}

              {photos.length === 0 ? (
                <div className="bg-white rounded-lg p-8 text-center text-gray-500">
                  Aucune photo
                </div>
              ) : (
                <div className="grid grid-cols-2 gap-2">
                  {photos.map((photo, index) => (
                    <div key={index} className="relative bg-white rounded-lg overflow-hidden shadow">
                      <AuthImage
                        src={photo.url}
                        alt={photo.name}
                        className="w-full h-40 object-cover cursor-pointer"
                        onClick={() => setSelectedImage(photo.url)}
                      />
                      <button
                        onClick={() => handleFileDelete(photo.name)}
                        className="absolute top-2 right-2 bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-700 transition-colors"
                      >
                        ×
                      </button>
                      <div className="p-2">
                        <p className="text-xs text-gray-600 truncate">{photo.name}</p>
                        <p className="text-xs text-gray-500">{formatFileSize(photo.size)}</p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Onglet Fichiers */}
          {activeTab === 'fichiers' && (
            <div className="p-4">
              <label className="block w-full mb-4">
                <input
                  type="file"
                  onChange={handleFileUpload}
                  disabled={uploading}
                  className="hidden"
                />
                <div className="bg-blue-600 text-white rounded-lg p-4 text-center cursor-pointer hover:bg-blue-700 transition-colors">
                  {uploading ? (
                    <span>📤 Upload en cours... {Math.round(uploadProgress)}%</span>
                  ) : (
                    <span>📎 Ajouter un fichier</span>
                  )}
                </div>
              </label>

              {uploading && (
                <div className="mb-4 bg-white rounded-lg p-4">
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                      style={{ width: `${uploadProgress}%` }}
                    ></div>
                  </div>
                </div>
              )}

              {documents.length === 0 ? (
                <div className="bg-white rounded-lg p-8 text-center text-gray-500">
                  Aucun fichier
                </div>
              ) : (
                <div className="space-y-2">
                  {documents.map((doc, index) => (
                    <div key={index} className="bg-white rounded-lg p-4 flex items-center justify-between">
                      <div className="flex-1">
                        <p className="text-sm font-medium text-gray-900">{doc.name}</p>
                        <p className="text-xs text-gray-500">{formatFileSize(doc.size)}</p>
                      </div>
                      <div className="flex gap-2">
                        <a
                          href={doc.url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-blue-600 hover:text-blue-700 text-sm px-3 py-1 border border-blue-600 rounded"
                        >
                          Ouvrir
                        </a>
                        <button
                          onClick={() => handleFileDelete(doc.name)}
                          className="text-red-600 hover:text-red-700 text-sm px-3 py-1 border border-red-600 rounded"
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>

        {/* Modal preview image plein écran */}
        {selectedImage && (
          <div
            className="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4"
            onClick={() => setSelectedImage(null)}
          >
            <button
              className="absolute top-4 right-4 text-white text-4xl font-bold hover:text-gray-300"
              onClick={() => setSelectedImage(null)}
            >
              ×
            </button>
            <AuthImage
              src={selectedImage}
              alt="Preview"
              className="max-w-full max-h-full object-contain"
            />
          </div>
        )}
      </div>
    </Layout>
  );
}
