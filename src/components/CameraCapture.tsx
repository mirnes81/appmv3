import { useState, useRef } from 'react';
import { Camera, X, RotateCw, Check } from 'lucide-react';
import { Photo } from '../types';

interface CameraCaptureProps {
  onCapture: (photo: Photo) => void;
  onClose: () => void;
}

export default function CameraCapture({ onCapture, onClose }: CameraCaptureProps) {
  const [photo, setPhoto] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleCapture = () => {
    if (fileInputRef.current) {
      fileInputRef.current.click();
    }
  };

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      setPhoto(event.target?.result as string);
    };
    reader.readAsDataURL(file);
  };

  const handleConfirm = async () => {
    if (!photo) return;

    const photoData: Photo = {
      id: crypto.randomUUID(),
      filename: `photo_${Date.now()}.jpg`,
      url: photo,
      size: photo.length,
      taken_at: new Date().toISOString(),
      uploaded: false
    };

    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          photoData.gps_location = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            timestamp: new Date().toISOString()
          };
          onCapture(photoData);
        },
        () => {
          onCapture(photoData);
        }
      );
    } else {
      onCapture(photoData);
    }
  };

  const handleRetake = () => {
    setPhoto(null);
  };

  return (
    <div className="fixed inset-0 bg-black z-50 flex flex-col">
      <div className="flex-1 relative bg-gray-900">
        {photo ? (
          <img src={photo} alt="Captured" className="w-full h-full object-contain" />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-white">
            <div className="text-center">
              <Camera className="w-20 h-20 mx-auto mb-4 opacity-50" />
              <p className="text-lg">Appuyez pour prendre une photo</p>
            </div>
          </div>
        )}
      </div>

      <input
        ref={fileInputRef}
        type="file"
        accept="image/*"
        capture="environment"
        onChange={handleFileChange}
        className="hidden"
      />

      <div className="bg-black p-6 safe-area-bottom">
        {photo ? (
          <div className="flex items-center justify-around">
            <button
              onClick={handleRetake}
              className="flex flex-col items-center text-white"
            >
              <div className="w-16 h-16 rounded-full bg-gray-800 flex items-center justify-center mb-2">
                <RotateCw className="w-8 h-8" />
              </div>
              <span className="text-sm">Reprendre</span>
            </button>

            <button
              onClick={handleConfirm}
              className="flex flex-col items-center text-green-500"
            >
              <div className="w-16 h-16 rounded-full bg-green-600 flex items-center justify-center mb-2">
                <Check className="w-8 h-8 text-white" />
              </div>
              <span className="text-sm text-white">Confirmer</span>
            </button>

            <button
              onClick={onClose}
              className="flex flex-col items-center text-white"
            >
              <div className="w-16 h-16 rounded-full bg-gray-800 flex items-center justify-center mb-2">
                <X className="w-8 h-8" />
              </div>
              <span className="text-sm">Annuler</span>
            </button>
          </div>
        ) : (
          <div className="flex items-center justify-around">
            <button
              onClick={onClose}
              className="flex flex-col items-center text-white"
            >
              <div className="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center mb-2">
                <X className="w-6 h-6" />
              </div>
              <span className="text-xs">Annuler</span>
            </button>

            <button
              onClick={handleCapture}
              className="flex flex-col items-center"
            >
              <div className="w-20 h-20 rounded-full bg-white flex items-center justify-center mb-2
                           ring-4 ring-white/30 active:scale-95 transition-transform">
                <Camera className="w-10 h-10 text-gray-900" />
              </div>
            </button>

            <div className="w-12"></div>
          </div>
        )}
      </div>
    </div>
  );
}
