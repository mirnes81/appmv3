import { useState } from 'react';
import { Plus } from 'lucide-react';

export default function SensPoseScreen() {
  return (
    <div className="min-h-screen bg-gray-50 safe-area-top pb-20">
      <div className="bg-white border-b border-gray-200 p-4">
        <h1 className="text-2xl font-bold text-gray-900">Sens de pose</h1>
      </div>

      <div className="p-4">
        <div className="card-premium text-center py-12">
          <p className="text-gray-500 mb-4">Aucun sens de pose enregistré</p>
          <button className="btn-primary max-w-xs mx-auto flex items-center justify-center">
            <Plus className="w-5 h-5 mr-2" />
            Nouveau sens de pose
          </button>
        </div>
      </div>
    </div>
  );
}
