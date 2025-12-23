import { useState, useEffect } from 'react';
import { X, Save, Camera, Mic, MapPin, Cloud, Clock } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { Report, Photo, VoiceNote } from '../types';
import * as db from '../utils/db';
import * as api from '../utils/api';
import { useOffline } from '../contexts/OfflineContext';
import CameraCapture from '../components/CameraCapture';
import VoiceRecorder from '../components/VoiceRecorder';
import TimeTracker from '../components/TimeTracker';

interface NewReportScreenProps {
  onClose: () => void;
  draft?: Report;
}

export default function NewReportScreen({ onClose, draft }: NewReportScreenProps) {
  const { user } = useAuth();
  const { isOnline } = useOffline();
  const [saving, setSaving] = useState(false);
  const [showCamera, setShowCamera] = useState(false);
  const [showVoiceRecorder, setShowVoiceRecorder] = useState(false);
  const [totalTimeSeconds, setTotalTimeSeconds] = useState(0);

  const [formData, setFormData] = useState<Partial<Report>>({
    id: draft?.id || crypto.randomUUID(),
    user_id: user?.id,
    date: draft?.date || new Date().toISOString().split('T')[0],
    start_time: draft?.start_time || '',
    end_time: draft?.end_time || '',
    client_name: draft?.client_name || '',
    description: draft?.description || '',
    observations: draft?.observations || '',
    photos: draft?.photos || [],
    voice_notes: draft?.voice_notes || [],
    status: draft?.status || 'draft',
    created_at: draft?.created_at || new Date().toISOString(),
    updated_at: new Date().toISOString()
  });

  useEffect(() => {
    const autoSaveInterval = setInterval(() => {
      if (formData.description || formData.client_name) {
        saveDraft();
      }
    }, 10000);

    return () => clearInterval(autoSaveInterval);
  }, [formData]);

  const saveDraft = async () => {
    if (!user) return;

    try {
      await db.saveReport(formData as Report);
    } catch (error) {
      console.error('Failed to save draft:', error);
    }
  };

  const handleSave = async () => {
    if (!user || !formData.client_name || !formData.description) {
      alert('Veuillez remplir les champs obligatoires');
      return;
    }

    setSaving(true);

    try {
      const report: Report = {
        ...formData,
        id: formData.id!,
        user_id: user.id,
        status: isOnline ? 'synced' : 'pending',
        updated_at: new Date().toISOString()
      } as Report;

      await db.saveReport(report);

      if (isOnline) {
        try {
          await api.createReport(report);
        } catch (error) {
          report.status = 'pending';
          await db.saveReport(report);
          await db.addToSyncQueue({
            id: crypto.randomUUID(),
            user_id: user.id,
            action_type: 'create_report',
            priority: 5,
            payload: report,
            status: 'pending',
            retry_count: 0,
            created_at: new Date().toISOString()
          });
        }
      } else {
        await db.addToSyncQueue({
          id: crypto.randomUUID(),
          user_id: user.id,
          action_type: 'create_report',
          priority: 5,
          payload: report,
          status: 'pending',
          retry_count: 0,
          created_at: new Date().toISOString()
        });
      }

      onClose();
    } catch (error) {
      console.error('Failed to save report:', error);
      alert('Erreur lors de la sauvegarde');
    } finally {
      setSaving(false);
    }
  };

  const handlePhotoCapture = (photo: Photo) => {
    setFormData(prev => ({
      ...prev,
      photos: [...(prev.photos || []), photo]
    }));
    setShowCamera(false);
  };

  const handleVoiceNote = (note: VoiceNote) => {
    setFormData(prev => ({
      ...prev,
      voice_notes: [...(prev.voice_notes || []), note],
      description: prev.description ? `${prev.description}\n\n${note.transcription}` : note.transcription
    }));
  };

  return (
    <div className="min-h-screen bg-gray-50 safe-area-top">
      <div className="bg-white border-b border-gray-200 p-4 flex items-center justify-between sticky top-0 z-10">
        <button
          onClick={onClose}
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <X className="w-6 h-6" />
        </button>

        <h1 className="text-lg font-bold text-gray-900">Nouveau rapport</h1>

        <button
          onClick={handleSave}
          disabled={saving}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium
                   hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-50"
        >
          {saving ? 'Enregistrement...' : 'Enregistrer'}
        </button>
      </div>

      <div className="p-4 space-y-4">
        <div className="card-premium">
          <div className="flex items-center justify-between mb-4">
            <span className="text-sm text-gray-500">Sauvegarde automatique activée</span>
            <Clock className="w-4 h-4 text-green-600" />
          </div>
        </div>

        <TimeTracker
          fichinterId={formData.project_id}
          onTimeUpdate={setTotalTimeSeconds}
        />

        <div className="card-premium">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Date <span className="text-red-500">*</span>
          </label>
          <input
            type="date"
            value={formData.date}
            onChange={(e) => setFormData({ ...formData, date: e.target.value })}
            className="input-premium"
            required
          />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div className="card-premium">
            <label className="block text-sm font-medium text-gray-700 mb-2">Heure début</label>
            <input
              type="time"
              value={formData.start_time}
              onChange={(e) => setFormData({ ...formData, start_time: e.target.value })}
              className="input-premium"
            />
          </div>

          <div className="card-premium">
            <label className="block text-sm font-medium text-gray-700 mb-2">Heure fin</label>
            <input
              type="time"
              value={formData.end_time}
              onChange={(e) => setFormData({ ...formData, end_time: e.target.value })}
              className="input-premium"
            />
          </div>
        </div>

        <div className="card-premium">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Client <span className="text-red-500">*</span>
          </label>
          <input
            type="text"
            value={formData.client_name}
            onChange={(e) => setFormData({ ...formData, client_name: e.target.value })}
            placeholder="Nom du client"
            className="input-premium"
            required
          />
        </div>

        <div className="card-premium">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Description des travaux <span className="text-red-500">*</span>
          </label>
          <textarea
            value={formData.description}
            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
            placeholder="Décrivez les travaux effectués..."
            className="input-premium min-h-[120px] resize-none"
            required
          />
        </div>

        <div className="card-premium">
          <label className="block text-sm font-medium text-gray-700 mb-2">Observations</label>
          <textarea
            value={formData.observations}
            onChange={(e) => setFormData({ ...formData, observations: e.target.value })}
            placeholder="Observations complémentaires..."
            className="input-premium min-h-[80px] resize-none"
          />
        </div>

        <div className="card-premium">
          <div className="flex items-center justify-between mb-3">
            <label className="text-sm font-medium text-gray-700">Photos ({formData.photos?.length || 0})</label>
            <button
              onClick={() => setShowCamera(true)}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium
                       hover:bg-blue-700 active:scale-95 transition-all flex items-center"
            >
              <Camera className="w-4 h-4 mr-2" />
              Ajouter
            </button>
          </div>

          {formData.photos && formData.photos.length > 0 && (
            <div className="grid grid-cols-3 gap-2">
              {formData.photos.map((photo) => (
                <div key={photo.id} className="aspect-square rounded-lg overflow-hidden bg-gray-100">
                  <img src={photo.url} alt="Photo" className="w-full h-full object-cover" />
                </div>
              ))}
            </div>
          )}
        </div>

        <button
          onClick={() => setShowVoiceRecorder(true)}
          className="w-full card-premium flex items-center justify-center space-x-2 text-blue-600
                   hover:bg-blue-50 transition-colors cursor-pointer"
        >
          <Mic className="w-5 h-5" />
          <span className="font-medium">Ajouter une note vocale</span>
        </button>

        {formData.voice_notes && formData.voice_notes.length > 0 && (
          <div className="card-premium space-y-2">
            <p className="text-sm font-medium text-gray-700 mb-2">
              Notes vocales ({formData.voice_notes.length})
            </p>
            {formData.voice_notes.map((note) => (
              <div key={note.id} className="p-3 bg-blue-50 rounded-lg text-sm">
                <p className="text-gray-700">{note.transcription}</p>
                <p className="text-xs text-gray-500 mt-1">Durée: {note.duration}s</p>
              </div>
            ))}
          </div>
        )}
      </div>

      {showCamera && (
        <CameraCapture
          onCapture={handlePhotoCapture}
          onClose={() => setShowCamera(false)}
        />
      )}

      {showVoiceRecorder && (
        <VoiceRecorder
          onSave={handleVoiceNote}
          onClose={() => setShowVoiceRecorder(false)}
        />
      )}
    </div>
  );
}
