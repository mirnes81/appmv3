import { useState } from 'react';
import { Calendar } from 'lucide-react';

export default function PlanningScreen() {
  const today = new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });

  return (
    <div className="min-h-screen bg-gray-50 safe-area-top pb-20">
      <div className="bg-white border-b border-gray-200 p-4">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Planning</h1>
        <p className="text-gray-600 text-sm capitalize">{today}</p>
      </div>

      <div className="p-4">
        <div className="card-premium text-center py-12">
          <Calendar className="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <p className="text-gray-500">Aucune tâche planifiée aujourd'hui</p>
        </div>
      </div>
    </div>
  );
}
