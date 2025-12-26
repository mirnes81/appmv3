import React, { useState, useEffect } from 'react';
import { CheckCircle2, XCircle, AlertCircle, RefreshCw } from 'lucide-react';
import { getDolapikey } from '../utils/storage';

interface EndpointStatus {
  name: string;
  endpoint: string;
  status: 'success' | 'error' | 'pending';
  message?: string;
  data?: any;
}

export default function DiagnosticScreen() {
  const [endpoints, setEndpoints] = useState<EndpointStatus[]>([
    { name: 'Utilisateur', endpoint: '/users/info', status: 'pending' },
    { name: 'Clients', endpoint: '/thirdparties?limit=5', status: 'pending' },
    { name: 'Projets', endpoint: '/projects?limit=5', status: 'pending' },
    { name: 'Interventions', endpoint: '/fichinter?limit=5', status: 'pending' },
    { name: 'Agenda', endpoint: '/agendaevents?limit=5', status: 'pending' },
    { name: 'Produits', endpoint: '/products?limit=5', status: 'pending' },
  ]);
  const [testing, setTesting] = useState(false);

  const testEndpoint = async (endpoint: EndpointStatus): Promise<EndpointStatus> => {
    try {
      const apiKey = await getDolapikey();
      const apiBase = import.meta.env.VITE_API_BASE || '/api/index.php';

      const response = await fetch(`${apiBase}${endpoint.endpoint}`, {
        headers: {
          'DOLAPIKEY': apiKey || '',
          'Accept': 'application/json',
        },
      });

      if (response.ok) {
        const data = await response.json();
        return {
          ...endpoint,
          status: 'success',
          message: Array.isArray(data) ? `${data.length} éléments` : 'OK',
          data: data,
        };
      } else {
        const error = await response.text();
        return {
          ...endpoint,
          status: 'error',
          message: error.substring(0, 100),
        };
      }
    } catch (error: any) {
      return {
        ...endpoint,
        status: 'error',
        message: error.message,
      };
    }
  };

  const runDiagnostic = async () => {
    setTesting(true);
    const results: EndpointStatus[] = [];

    for (const endpoint of endpoints) {
      const result = await testEndpoint(endpoint);
      results.push(result);
      setEndpoints([...results, ...endpoints.slice(results.length)]);
    }

    setTesting(false);
  };

  useEffect(() => {
    runDiagnostic();
  }, []);

  const getIcon = (status: string) => {
    switch (status) {
      case 'success':
        return <CheckCircle2 className="w-5 h-5 text-green-500" />;
      case 'error':
        return <XCircle className="w-5 h-5 text-red-500" />;
      default:
        return <AlertCircle className="w-5 h-5 text-yellow-500 animate-pulse" />;
    }
  };

  const successCount = endpoints.filter(e => e.status === 'success').length;
  const errorCount = endpoints.filter(e => e.status === 'error').length;

  return (
    <div className="min-h-screen bg-gray-50 p-4">
      <div className="max-w-4xl mx-auto">
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="flex items-center justify-between mb-6">
            <h1 className="text-2xl font-bold text-gray-900">
              Diagnostic API Dolibarr
            </h1>
            <button
              onClick={runDiagnostic}
              disabled={testing}
              className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              <RefreshCw className={`w-4 h-4 ${testing ? 'animate-spin' : ''}`} />
              Relancer
            </button>
          </div>

          <div className="grid grid-cols-3 gap-4 mb-6">
            <div className="bg-green-50 rounded-lg p-4">
              <div className="text-3xl font-bold text-green-600">{successCount}</div>
              <div className="text-sm text-green-700">Fonctionnels</div>
            </div>
            <div className="bg-red-50 rounded-lg p-4">
              <div className="text-3xl font-bold text-red-600">{errorCount}</div>
              <div className="text-sm text-red-700">En erreur</div>
            </div>
            <div className="bg-yellow-50 rounded-lg p-4">
              <div className="text-3xl font-bold text-yellow-600">
                {endpoints.length - successCount - errorCount}
              </div>
              <div className="text-sm text-yellow-700">En attente</div>
            </div>
          </div>

          <div className="space-y-2">
            {endpoints.map((endpoint, index) => (
              <div
                key={index}
                className="flex items-center gap-4 p-4 bg-gray-50 rounded-lg"
              >
                <div>{getIcon(endpoint.status)}</div>
                <div className="flex-1">
                  <div className="font-semibold text-gray-900">{endpoint.name}</div>
                  <div className="text-sm text-gray-500">{endpoint.endpoint}</div>
                  {endpoint.message && (
                    <div className={`text-sm mt-1 ${
                      endpoint.status === 'error' ? 'text-red-600' : 'text-green-600'
                    }`}>
                      {endpoint.message}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="bg-blue-50 border border-blue-200 rounded-lg p-6">
          <h2 className="text-lg font-semibold text-blue-900 mb-3">
            Modules manquants dans Dolibarr
          </h2>
          <p className="text-blue-800 mb-4">
            Si certains endpoints sont en erreur, vous devez activer les modules correspondants dans Dolibarr :
          </p>
          <ol className="list-decimal list-inside space-y-2 text-blue-900">
            <li>Connectez-vous à Dolibarr en tant qu'administrateur</li>
            <li>Menu <strong>Accueil → Configuration → Modules/Applications</strong></li>
            <li>Recherchez et activez les modules suivants :
              <ul className="list-disc list-inside ml-6 mt-2 space-y-1">
                <li><strong>Interventions</strong> (fichinter) - pour les rapports d'intervention</li>
                <li><strong>Agenda</strong> - pour le planning</li>
                <li><strong>Produits/Services</strong> - pour la gestion des matériaux</li>
                <li><strong>API REST</strong> - si pas déjà activé</li>
              </ul>
            </li>
            <li>Actualisez cette page après activation</li>
          </ol>
        </div>

        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mt-6">
          <h2 className="text-lg font-semibold text-yellow-900 mb-3">
            Module MV3PRO Portail recommandé
          </h2>
          <p className="text-yellow-800 mb-4">
            Pour utiliser toutes les fonctionnalités (Régies, Sens de pose, Matériel), installez le module custom <strong>MV3PRO Portail</strong> disponible dans :
          </p>
          <code className="block bg-yellow-100 p-3 rounded text-sm text-yellow-900">
            new_dolibarr/mv3pro_portail/
          </code>
          <p className="text-yellow-800 mt-4">
            Ce module ajoute des tables et API spécifiques pour votre métier.
          </p>
        </div>
      </div>
    </div>
  );
}
