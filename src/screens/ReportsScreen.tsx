import { useState, useEffect } from 'react';
import { Plus, Search, Filter, FileText, Clock, CheckCircle, AlertCircle, Camera, Mic, Copy } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { Report } from '../types';
import * as db from '../utils/db';
import CameraCapture from '../components/CameraCapture';
import VoiceRecorder from '../components/VoiceRecorder';
import NewReportScreen from './NewReportScreen';

export default function ReportsScreen() {
  const { user } = useAuth();
  const [reports, setReports] = useState<Report[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [filter, setFilter] = useState<'all' | 'draft' | 'pending' | 'synced'>('all');
  const [showNewReport, setShowNewReport] = useState(false);
  const [showCamera, setShowCamera] = useState(false);
  const [showVoiceRecorder, setShowVoiceRecorder] = useState(false);
  const [selectedReport, setSelectedReport] = useState<Report | null>(null);

  useEffect(() => {
    loadReports();
  }, [filter]);

  const loadReports = async () => {
    if (!user) return;

    try {
      setLoading(true);
      const data = filter === 'all'
        ? await db.getReports(user.id)
        : await db.getReports(user.id, filter);
      setReports(data.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()));
    } catch (error) {
      console.error('Failed to load reports:', error);
    } finally {
      setLoading(false);
    }
  };

  const filteredReports = reports.filter(report =>
    report.client_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    report.description.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const getStatusIcon = (status: Report['status']) => {
    switch (status) {
      case 'draft':
        return <Clock className="w-4 h-4 text-orange-500" />;
      case 'pending':
        return <AlertCircle className="w-4 h-4 text-blue-500" />;
      case 'synced':
        return <CheckCircle className="w-4 h-4 text-green-500" />;
    }
  };

  const getStatusLabel = (status: Report['status']) => {
    switch (status) {
      case 'draft':
        return 'Brouillon';
      case 'pending':
        return 'En attente';
      case 'synced':
        return 'Synchronisé';
    }
  };

  if (showNewReport) {
    return <NewReportScreen onClose={() => { setShowNewReport(false); loadReports(); }} />;
  }

  return (
    <div className="min-h-screen bg-gray-50 safe-area-top pb-20">
      <div className="bg-white border-b border-gray-200 p-4">
        <h1 className="text-2xl font-bold text-gray-900 mb-4">Rapports</h1>

        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Rechercher un rapport..."
            className="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl
                     focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div className="flex space-x-2 overflow-x-auto pb-2">
          {[
            { id: 'all', label: 'Tous' },
            { id: 'draft', label: 'Brouillons' },
            { id: 'pending', label: 'En attente' },
            { id: 'synced', label: 'Synchronisés' }
          ].map(item => (
            <button
              key={item.id}
              onClick={() => setFilter(item.id as any)}
              className={`px-4 py-2 rounded-xl font-medium text-sm whitespace-nowrap transition-colors
                       ${filter === item.id
                         ? 'bg-blue-600 text-white'
                         : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                       }`}
            >
              {item.label}
            </button>
          ))}
        </div>
      </div>

      <div className="p-4 space-y-3">
        {loading ? (
          <div className="flex items-center justify-center py-12">
            <div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          </div>
        ) : filteredReports.length === 0 ? (
          <div className="text-center py-12">
            <FileText className="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <p className="text-gray-500">Aucun rapport trouvé</p>
          </div>
        ) : (
          filteredReports.map(report => (
            <div
              key={report.id}
              onClick={() => setSelectedReport(report)}
              className="card-premium cursor-pointer hover:scale-[1.02] transition-transform"
            >
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                  <h3 className="font-bold text-gray-900 mb-1">{report.client_name}</h3>
                  <p className="text-sm text-gray-600 line-clamp-2">{report.description}</p>
                </div>
                <div className="flex items-center space-x-1 ml-3">
                  {getStatusIcon(report.status)}
                  <span className="text-xs text-gray-600">{getStatusLabel(report.status)}</span>
                </div>
              </div>

              <div className="flex items-center justify-between text-xs text-gray-500">
                <span>{new Date(report.date).toLocaleDateString('fr-FR')}</span>
                <span>{report.photos.length} photo(s)</span>
              </div>

              {report.voice_notes && report.voice_notes.length > 0 && (
                <div className="mt-2 flex items-center text-xs text-blue-600">
                  <Mic className="w-3 h-3 mr-1" />
                  {report.voice_notes.length} note(s) vocale(s)
                </div>
              )}
            </div>
          ))
        )}
      </div>

      <div className="fixed bottom-24 right-4 flex flex-col space-y-3 safe-area-bottom">
        <button
          onClick={() => setShowCamera(true)}
          className="w-14 h-14 bg-purple-600 text-white rounded-full shadow-xl
                   hover:bg-purple-700 active:scale-95 transition-all flex items-center justify-center"
        >
          <Camera className="w-6 h-6" />
        </button>

        <button
          onClick={() => setShowVoiceRecorder(true)}
          className="w-14 h-14 bg-orange-600 text-white rounded-full shadow-xl
                   hover:bg-orange-700 active:scale-95 transition-all flex items-center justify-center"
        >
          <Mic className="w-6 h-6" />
        </button>

        <button
          onClick={() => setShowNewReport(true)}
          className="w-14 h-14 bg-blue-600 text-white rounded-full shadow-xl
                   hover:bg-blue-700 active:scale-95 transition-all flex items-center justify-center"
        >
          <Plus className="w-6 h-6" />
        </button>
      </div>

      {showCamera && (
        <CameraCapture
          onCapture={(photo) => {
            console.log('Photo captured:', photo);
            setShowCamera(false);
          }}
          onClose={() => setShowCamera(false)}
        />
      )}

      {showVoiceRecorder && (
        <VoiceRecorder
          onSave={(note) => {
            console.log('Voice note saved:', note);
            setShowVoiceRecorder(false);
          }}
          onClose={() => setShowVoiceRecorder(false)}
        />
      )}
    </div>
  );
}
