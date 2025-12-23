import { useState, useEffect } from 'react';
import {
  Camera, FileText, Clipboard, Layers, Cloud, Wifi, WifiOff, RefreshCw,
  Clock, CheckCircle, AlertCircle, TrendingUp, Calendar, MapPin
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useOffline } from '../contexts/OfflineContext';
import * as api from '../utils/api';
import { DashboardStats } from '../types';

type Screen = 'dashboard' | 'reports' | 'regie' | 'sens-pose' | 'materiel' | 'planning' | 'profile';

interface DashboardProps {
  onNavigate: (screen: Screen) => void;
}

export default function Dashboard({ onNavigate }: DashboardProps) {
  const { user } = useAuth();
  const { isOnline, isSyncing, syncProgress, pendingActions, sync } = useOffline();
  const [stats, setStats] = useState<DashboardStats>({
    reports_today: 0,
    reports_week: 0,
    reports_month: 0,
    hours_today: 0,
    hours_week: 0,
    pending_sync: 0,
    photos_count: 0
  });
  const [weather, setWeather] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    loadDashboard();
    loadWeather();

    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  const loadDashboard = async () => {
    try {
      const data = await api.getDashboardStats();
      setStats({ ...data, pending_sync: pendingActions });
    } catch (error) {
      console.error('Failed to load dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadWeather = async () => {
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          try {
            const weatherData = await api.getWeather(
              position.coords.latitude,
              position.coords.longitude
            );
            setWeather(weatherData);
          } catch (error) {
            console.error('Failed to load weather:', error);
          }
        },
        (error) => console.error('Geolocation error:', error)
      );
    }
  };

  const quickActions = [
    {
      id: 'camera',
      icon: Camera,
      label: 'Photo rapide',
      color: 'bg-blue-600',
      onClick: () => handleQuickPhoto()
    },
    {
      id: 'report',
      icon: FileText,
      label: 'Nouveau rapport',
      color: 'bg-green-600',
      onClick: () => onNavigate('reports')
    },
    {
      id: 'regie',
      icon: Clipboard,
      label: 'Nouvelle régie',
      color: 'bg-orange-600',
      onClick: () => onNavigate('regie')
    },
    {
      id: 'sens-pose',
      icon: Layers,
      label: 'Sens de pose',
      color: 'bg-purple-600',
      onClick: () => onNavigate('sens-pose')
    }
  ];

  const handleQuickPhoto = () => {
    if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.capture = 'environment' as any;
      input.onchange = (e: any) => {
        const file = e.target.files[0];
        if (file) {
          console.log('Quick photo captured:', file);
        }
      };
      input.click();
    }
  };

  const formatTime = (date: Date) => {
    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  };

  const formatDate = (date: Date) => {
    return date.toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 safe-area-top">
      <div className="bg-gradient-to-br from-blue-600 to-blue-800 text-white p-6 rounded-b-3xl shadow-xl">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-2xl font-bold">Bonjour, {user?.name}</h1>
            <p className="text-blue-100 text-sm mt-1">{formatDate(currentTime)}</p>
          </div>
          <div className="text-right">
            <div className="text-3xl font-bold">{formatTime(currentTime)}</div>
            <div className="flex items-center justify-end space-x-2 mt-1">
              {isOnline ? (
                <>
                  <Wifi className="w-4 h-4" />
                  <span className="text-xs text-blue-100">En ligne</span>
                </>
              ) : (
                <>
                  <WifiOff className="w-4 h-4" />
                  <span className="text-xs text-blue-100">Hors ligne</span>
                </>
              )}
            </div>
          </div>
        </div>

        {weather && (
          <div className="glass-effect rounded-2xl p-4 mb-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <Cloud className="w-8 h-8" />
                <div>
                  <p className="text-sm text-blue-100">Météo actuelle</p>
                  <p className="text-2xl font-bold">{weather.temperature}°C</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-sm capitalize">{weather.conditions}</p>
                <p className="text-xs text-blue-100 mt-1">
                  <MapPin className="w-3 h-3 inline mr-1" />
                  Votre position
                </p>
              </div>
            </div>
          </div>
        )}

        {pendingActions > 0 && (
          <div className="bg-orange-500 rounded-2xl p-4 flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <AlertCircle className="w-6 h-6" />
              <div>
                <p className="font-medium">{pendingActions} action(s) en attente</p>
                <p className="text-xs text-orange-100">Synchronisation nécessaire</p>
              </div>
            </div>
            <button
              onClick={sync}
              disabled={isSyncing || !isOnline}
              className="bg-white text-orange-600 px-4 py-2 rounded-xl font-medium
                       hover:bg-orange-50 active:scale-95 transition-all disabled:opacity-50"
            >
              {isSyncing ? (
                <RefreshCw className="w-5 h-5 animate-spin" />
              ) : (
                'Sync'
              )}
            </button>
          </div>
        )}
      </div>

      <div className="p-6 space-y-6">
        <div>
          <h2 className="text-lg font-bold text-gray-900 mb-4">Actions rapides</h2>
          <div className="grid grid-cols-2 gap-3">
            {quickActions.map((action) => {
              const Icon = action.icon;
              return (
                <button
                  key={action.id}
                  onClick={action.onClick}
                  className={`${action.color} text-white p-6 rounded-2xl shadow-lg
                           hover:shadow-xl active:scale-95 transition-all`}
                >
                  <Icon className="w-8 h-8 mb-3" />
                  <p className="font-medium text-sm">{action.label}</p>
                </button>
              );
            })}
          </div>
        </div>

        <div>
          <h2 className="text-lg font-bold text-gray-900 mb-4">Statistiques</h2>
          <div className="grid grid-cols-2 gap-3">
            <div className="card-premium">
              <div className="flex items-center justify-between mb-3">
                <FileText className="w-6 h-6 text-blue-600" />
                <span className="text-xs text-gray-500">Aujourd'hui</span>
              </div>
              <p className="text-3xl font-bold text-gray-900">{stats.reports_today}</p>
              <p className="text-sm text-gray-600 mt-1">Rapports</p>
            </div>

            <div className="card-premium">
              <div className="flex items-center justify-between mb-3">
                <Clock className="w-6 h-6 text-green-600" />
                <span className="text-xs text-gray-500">Aujourd'hui</span>
              </div>
              <p className="text-3xl font-bold text-gray-900">{stats.hours_today}h</p>
              <p className="text-sm text-gray-600 mt-1">Heures</p>
            </div>

            <div className="card-premium">
              <div className="flex items-center justify-between mb-3">
                <TrendingUp className="w-6 h-6 text-orange-600" />
                <span className="text-xs text-gray-500">Cette semaine</span>
              </div>
              <p className="text-3xl font-bold text-gray-900">{stats.reports_week}</p>
              <p className="text-sm text-gray-600 mt-1">Rapports</p>
            </div>

            <div className="card-premium">
              <div className="flex items-center justify-between mb-3">
                <CheckCircle className="w-6 h-6 text-purple-600" />
                <span className="text-xs text-gray-500">Ce mois</span>
              </div>
              <p className="text-3xl font-bold text-gray-900">{stats.reports_month}</p>
              <p className="text-sm text-gray-600 mt-1">Rapports</p>
            </div>
          </div>
        </div>

        <div className="card-premium">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-bold text-gray-900">Planning du jour</h3>
            <Calendar className="w-5 h-5 text-gray-400" />
          </div>
          <button
            onClick={() => onNavigate('planning')}
            className="w-full text-center py-3 bg-gray-50 text-gray-700 rounded-xl
                     hover:bg-gray-100 transition-colors font-medium"
          >
            Voir le planning complet
          </button>
        </div>
      </div>
    </div>
  );
}
