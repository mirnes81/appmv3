import { useState, useEffect } from 'react';
import { Mic, MicOff, Check, X } from 'lucide-react';
import { VoiceNote } from '../types';

interface VoiceRecorderProps {
  onSave: (note: VoiceNote) => void;
  onClose: () => void;
}

export default function VoiceRecorder({ onSave, onClose }: VoiceRecorderProps) {
  const [isRecording, setIsRecording] = useState(false);
  const [transcription, setTranscription] = useState('');
  const [duration, setDuration] = useState(0);
  const [recognition, setRecognition] = useState<any>(null);

  useEffect(() => {
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
      const SpeechRecognition = (window as any).webkitSpeechRecognition || (window as any).SpeechRecognition;
      const recognitionInstance = new SpeechRecognition();
      recognitionInstance.continuous = true;
      recognitionInstance.interimResults = true;
      recognitionInstance.lang = 'fr-FR';

      recognitionInstance.onresult = (event: any) => {
        let finalTranscript = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
          const transcript = event.results[i][0].transcript;
          if (event.results[i].isFinal) {
            finalTranscript += transcript + ' ';
          }
        }
        if (finalTranscript) {
          setTranscription(prev => prev + finalTranscript);
        }
      };

      recognitionInstance.onerror = (event: any) => {
        console.error('Speech recognition error:', event.error);
      };

      setRecognition(recognitionInstance);
    }

    return () => {
      if (recognition) {
        recognition.stop();
      }
    };
  }, []);

  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isRecording) {
      interval = setInterval(() => {
        setDuration(prev => prev + 1);
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [isRecording]);

  const startRecording = () => {
    if (recognition) {
      recognition.start();
      setIsRecording(true);
      setDuration(0);
      setTranscription('');
    }
  };

  const stopRecording = () => {
    if (recognition) {
      recognition.stop();
      setIsRecording(false);
    }
  };

  const handleSave = () => {
    if (transcription.trim()) {
      const voiceNote: VoiceNote = {
        id: crypto.randomUUID(),
        transcription: transcription.trim(),
        duration,
        confidence: 0.85,
        language: 'fr-FR',
        created_at: new Date().toISOString()
      };
      onSave(voiceNote);
      onClose();
    }
  };

  const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-end animate-fade-in">
      <div className="w-full bg-white rounded-t-3xl p-6 safe-area-bottom animate-slide-up">
        <div className="text-center mb-6">
          <h3 className="text-xl font-bold text-gray-900 mb-2">Note vocale</h3>
          <p className="text-gray-600 text-sm">
            {isRecording ? 'Enregistrement en cours...' : 'Appuyez pour commencer'}
          </p>
        </div>

        <div className="mb-6">
          <div className="flex items-center justify-center mb-4">
            <button
              onClick={isRecording ? stopRecording : startRecording}
              className={`w-24 h-24 rounded-full flex items-center justify-center transition-all
                       ${isRecording
                         ? 'bg-red-600 animate-pulse'
                         : 'bg-blue-600 hover:bg-blue-700'
                       }`}
            >
              {isRecording ? (
                <MicOff className="w-12 h-12 text-white" />
              ) : (
                <Mic className="w-12 h-12 text-white" />
              )}
            </button>
          </div>

          <div className="text-center">
            <p className="text-3xl font-bold text-gray-900 mb-2">
              {formatDuration(duration)}
            </p>
          </div>
        </div>

        {transcription && (
          <div className="mb-6 p-4 bg-gray-50 rounded-xl">
            <p className="text-sm text-gray-600 mb-2 font-medium">Transcription :</p>
            <p className="text-gray-900">{transcription}</p>
          </div>
        )}

        <div className="flex space-x-3">
          <button
            onClick={onClose}
            className="flex-1 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium
                     hover:bg-gray-300 active:scale-95 transition-all flex items-center justify-center"
          >
            <X className="w-5 h-5 mr-2" />
            Annuler
          </button>

          <button
            onClick={handleSave}
            disabled={!transcription.trim()}
            className="flex-1 py-3 bg-blue-600 text-white rounded-xl font-medium
                     hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed
                     flex items-center justify-center"
          >
            <Check className="w-5 h-5 mr-2" />
            Enregistrer
          </button>
        </div>

        {!recognition && (
          <p className="text-center text-sm text-orange-600 mt-4">
            La reconnaissance vocale n'est pas disponible sur cet appareil
          </p>
        )}
      </div>
    </div>
  );
}
