import { useState } from 'react';

interface RapportFormProps {
  onSubmit: (data: any) => void;
  onCancel: () => void;
}

export default function RapportForm({ onSubmit, onCancel }: RapportFormProps) {
  const [formData, setFormData] = useState({
    projet_nom: '',
    date_rapport: new Date().toISOString().split('T')[0],
    zone_travail: '',
    heures_debut: '',
    heures_fin: '',
    surface_carrelee: '',
    format_carreaux: '',
    type_pose: 'scelle',
    zone_pose: '',
    travaux_realises: '',
    observations: ''
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const calculateTempsTotal = () => {
    if (formData.heures_debut && formData.heures_fin) {
      const debut = new Date(`2000-01-01 ${formData.heures_debut}`);
      const fin = new Date(`2000-01-01 ${formData.heures_fin}`);
      const diff = (fin.getTime() - debut.getTime()) / (1000 * 60 * 60);
      return diff > 0 ? diff.toFixed(2) : '0';
    }
    return '0';
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const temps_total = calculateTempsTotal();
    onSubmit({ ...formData, temps_total });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Projet / Chantier *
          </label>
          <input
            type="text"
            name="projet_nom"
            value={formData.projet_nom}
            onChange={handleChange}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Date du rapport *
          </label>
          <input
            type="date"
            name="date_rapport"
            value={formData.date_rapport}
            onChange={handleChange}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Zone de travail
          </label>
          <input
            type="text"
            name="zone_travail"
            value={formData.zone_travail}
            onChange={handleChange}
            placeholder="Ex: Salon, Cuisine, Salle de bain..."
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Zone de pose
          </label>
          <input
            type="text"
            name="zone_pose"
            value={formData.zone_pose}
            onChange={handleChange}
            placeholder="Ex: Sol, Mur..."
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Heure de début
          </label>
          <input
            type="time"
            name="heures_debut"
            value={formData.heures_debut}
            onChange={handleChange}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Heure de fin
          </label>
          <input
            type="time"
            name="heures_fin"
            value={formData.heures_fin}
            onChange={handleChange}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Surface carrelée (m²)
          </label>
          <input
            type="number"
            step="0.01"
            name="surface_carrelee"
            value={formData.surface_carrelee}
            onChange={handleChange}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Format des carreaux
          </label>
          <input
            type="text"
            name="format_carreaux"
            value={formData.format_carreaux}
            onChange={handleChange}
            placeholder="Ex: 30x60, 60x60..."
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Type de pose
          </label>
          <select
            name="type_pose"
            value={formData.type_pose}
            onChange={handleChange}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="scelle">Scellé</option>
            <option value="colle">Collé</option>
            <option value="pose_droite">Pose droite</option>
            <option value="pose_diagonale">Pose diagonale</option>
            <option value="chevron">Chevron</option>
            <option value="autre">Autre</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Temps total
          </label>
          <input
            type="text"
            value={`${calculateTempsTotal()} heures`}
            className="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg"
            disabled
          />
        </div>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Travaux réalisés *
        </label>
        <textarea
          name="travaux_realises"
          value={formData.travaux_realises}
          onChange={handleChange}
          rows={4}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Décrivez les travaux réalisés..."
          required
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Observations
        </label>
        <textarea
          name="observations"
          value={formData.observations}
          onChange={handleChange}
          rows={3}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Observations, difficultés rencontrées, matériel manquant..."
        />
      </div>

      <div className="flex gap-4 justify-end pt-4 border-t">
        <button
          type="button"
          onClick={onCancel}
          className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
        >
          Annuler
        </button>
        <button
          type="submit"
          className="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
        >
          Enregistrer
        </button>
      </div>
    </form>
  );
}
