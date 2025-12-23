import { useState } from 'react';
import { Package, Plus } from 'lucide-react';

export default function MaterielScreen() {
  return (
    <div className="min-h-screen bg-gray-50 safe-area-top pb-20">
      <div className="bg-white border-b border-gray-200 p-4">
        <h1 className="text-2xl font-bold text-gray-900">Matériel</h1>
      </div>

      <div className="p-4">
        <div className="card-premium text-center py-12">
          <Package className="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <p className="text-gray-500 mb-4">Aucun matériel enregistré</p>
        </div>
      </div>
    </div>
  );
}
