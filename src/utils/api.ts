import { User, Report, Regie, SensPose } from '../types';
import { getDolapikey } from './storage';

const API_BASE = import.meta.env.VITE_API_BASE || '/api/index.php';

async function fetchDolibarr(endpoint: string, options: RequestInit = {}) {
  const apiKey = await getDolapikey();

  if (!apiKey) {
    throw new Error('DOLAPIKEY manquante. Veuillez vous reconnecter.');
  }

  const headers: HeadersInit = {
    'DOLAPIKEY': apiKey,
    'Accept': 'application/json',
    ...options.headers,
  };

  if (options.body && typeof options.body === 'string') {
    headers['Content-Type'] = 'application/json';
  }

  const url = `${API_BASE}${endpoint}`;

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: { message: 'Erreur réseau' } }));
    throw new Error(error.error?.message || `HTTP ${response.status}`);
  }

  return response.json();
}

export async function verifyApiKey(apiKey: string): Promise<User> {
  const headers: HeadersInit = {
    'DOLAPIKEY': apiKey,
    'Accept': 'application/json',
  };

  const url = `${API_BASE}/users/info`;

  try {
    const response = await fetch(url, {
      headers,
    });

    if (!response.ok) {
      const errorText = await response.text().catch(() => '');
      console.error('API Error:', response.status, errorText);

      if (response.status === 401 || response.status === 403) {
        throw new Error('DOLAPIKEY invalide ou expirée');
      }

      throw new Error(`Erreur API (${response.status}): ${errorText || 'Vérifiez votre connexion'}`);
    }

    const userData = await response.json();

    return {
      id: String(userData.id),
      dolibarr_user_id: userData.id,
      email: userData.email || userData.login,
      name: `${userData.firstname || ''} ${userData.lastname || ''}`.trim(),
      phone: userData.user_mobile || userData.phone,
      biometric_enabled: false,
      preferences: {
        theme: 'auto',
        notifications: true,
        autoSave: true,
        cameraQuality: 'high',
        voiceLanguage: 'fr-FR',
      },
    };
  } catch (error) {
    if (error instanceof TypeError && error.message.includes('fetch')) {
      throw new Error(`Impossible de contacter le serveur.\n\nVérifiez:\n• Votre connexion Internet\n• Configuration CORS sur ${API_BASE}\n\nErreur technique: ${error.message}`);
    }
    throw error;
  }
}

export async function login(apiKey: string): Promise<User> {
  const user = await verifyApiKey(apiKey);
  const { saveDolapikey, saveUser } = await import('./storage');
  await saveDolapikey(apiKey);
  await saveUser(user);
  return user;
}

export async function logout(): Promise<void> {
  const { clearUser } = await import('./storage');
  await clearUser();
}

export async function verifySession(): Promise<boolean> {
  try {
    await fetchDolibarr('/users/info');
    return true;
  } catch {
    return false;
  }
}

export async function getFichinters(filters?: { limit?: number; sortfield?: string; sortorder?: string }): Promise<any[]> {
  const params = new URLSearchParams();
  if (filters?.limit) params.append('limit', String(filters.limit));
  if (filters?.sortfield) params.append('sortfield', filters.sortfield);
  if (filters?.sortorder) params.append('sortorder', filters.sortorder);

  const query = params.toString() ? `?${params.toString()}` : '';
  return fetchDolibarr(`/fichinter${query}`);
}

export async function createFichinter(data: any): Promise<any> {
  return fetchDolibarr('/fichinter', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function getAgendaEvents(filters?: { limit?: number }): Promise<any[]> {
  const params = new URLSearchParams();
  if (filters?.limit) params.append('limit', String(filters.limit));

  const query = params.toString() ? `?${params.toString()}` : '';
  return fetchDolibarr(`/agendaevents${query}`);
}

export async function createAgendaEvent(data: any): Promise<any> {
  return fetchDolibarr('/agendaevents', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function getThirdparties(filters?: { limit?: number }): Promise<any[]> {
  const params = new URLSearchParams();
  if (filters?.limit) params.append('limit', String(filters.limit));

  const query = params.toString() ? `?${params.toString()}` : '';
  return fetchDolibarr(`/thirdparties${query}`);
}

export async function getProjects(filters?: { thirdparty_id?: number; limit?: number }): Promise<any[]> {
  const params = new URLSearchParams();
  if (filters?.thirdparty_id) params.append('thirdparty_ids', String(filters.thirdparty_id));
  if (filters?.limit) params.append('limit', String(filters.limit));

  const query = params.toString() ? `?${params.toString()}` : '';
  return fetchDolibarr(`/projects${query}`);
}

export async function uploadDocument(modulepart: string, ref: string, file: File): Promise<any> {
  const apiKey = await getDolapikey();

  if (!apiKey) {
    throw new Error('DOLAPIKEY manquante');
  }

  const formData = new FormData();
  formData.append('filename', file.name);
  formData.append('modulepart', modulepart);
  formData.append('ref', ref);
  formData.append('filecontent', file);

  const response = await fetch(`${API_BASE}/documents/upload`, {
    method: 'POST',
    headers: {
      'DOLAPIKEY': apiKey,
    },
    body: formData,
  });

  if (!response.ok) {
    throw new Error('Échec de l\'envoi du document');
  }

  return response.json();
}

export async function getDocuments(modulepart: string, ref: string): Promise<any[]> {
  return fetchDolibarr(`/documents?modulepart=${modulepart}&ref=${ref}`);
}

export async function getReports(filters?: any): Promise<Report[]> {
  const fichinters = await getFichinters(filters);

  return fichinters.map((fich: any) => ({
    id: String(fich.id),
    user_id: String(fich.fk_user_author || ''),
    project_id: fich.fk_project,
    client_name: fich.socid ? fich.ref_client || '' : '',
    date: new Date(Number(fich.date_creation) * 1000).toISOString().split('T')[0],
    start_time: '',
    end_time: '',
    description: fich.description || '',
    observations: fich.note_private || '',
    materials_used: [],
    photos: [],
    voice_notes: [],
    status: fich.statut === '1' ? 'synced' : 'pending',
    created_at: new Date(Number(fich.date_creation) * 1000).toISOString(),
    updated_at: new Date(Number(fich.date_modification) * 1000).toISOString(),
  }));
}

export async function createReport(data: Partial<Report>): Promise<Report> {
  const fichinterData = {
    socid: data.client_name,
    description: data.description,
    note_private: data.observations,
    fk_project: data.project_id,
  };

  const created = await createFichinter(fichinterData);

  if (data.photos && data.photos.length > 0) {
    for (const photo of data.photos) {
      if (!photo.uploaded) {
        const blob = await fetch(photo.url).then(r => r.blob());
        const file = new File([blob], photo.filename, { type: 'image/jpeg' });
        await uploadDocument('fichinter', String(created.id), file);
      }
    }
  }

  return {
    ...data,
    id: String(created.id),
    status: 'synced',
  } as Report;
}

export async function getDashboardStats(): Promise<any> {
  const today = new Date();
  const startOfDay = Math.floor(today.setHours(0, 0, 0, 0) / 1000);

  try {
    const fichinters = await getFichinters({ limit: 100 });

    const todayFichinters = fichinters.filter((f: any) => Number(f.date_creation) >= startOfDay);

    return {
      reports_today: todayFichinters.length,
      reports_week: fichinters.length,
      reports_month: fichinters.length,
      hours_today: 0,
      hours_week: 0,
      pending_sync: 0,
      photos_count: 0,
    };
  } catch (error) {
    return {
      reports_today: 0,
      reports_week: 0,
      reports_month: 0,
      hours_today: 0,
      hours_week: 0,
      pending_sync: 0,
      photos_count: 0,
    };
  }
}

export async function getClients(): Promise<any[]> {
  return getThirdparties({ limit: 100 });
}

export async function getMaterials(): Promise<any[]> {
  return [];
}

export async function getWeather(lat: number, lon: number): Promise<any> {
  try {
    const response = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true`);
    const data = await response.json();

    return {
      temperature: data.current_weather?.temperature || 0,
      conditions: getWeatherCondition(data.current_weather?.weathercode || 0),
      icon: getWeatherIcon(data.current_weather?.weathercode || 0),
      humidity: 0,
      wind_speed: data.current_weather?.windspeed || 0,
    };
  } catch (error) {
    return null;
  }
}

function getWeatherCondition(code: number): string {
  if (code === 0) return 'Ensoleillé';
  if (code <= 3) return 'Nuageux';
  if (code <= 67) return 'Pluie';
  if (code <= 77) return 'Neige';
  return 'Orage';
}

function getWeatherIcon(code: number): string {
  if (code === 0) return '☀️';
  if (code <= 3) return '⛅';
  if (code <= 67) return '🌧️';
  if (code <= 77) return '❄️';
  return '⛈️';
}

export async function uploadPhoto(data: { file: File; type: string; related_id: string }): Promise<any> {
  return uploadDocument(data.type, data.related_id, data.file);
}

export async function createRegie(data: Partial<Regie>): Promise<Regie> {
  throw new Error('Non implémenté - à faire via module Dolibarr custom');
}

export async function getRegies(filters?: any): Promise<Regie[]> {
  return [];
}

export async function createSensPose(data: Partial<SensPose>): Promise<SensPose> {
  throw new Error('Non implémenté - à faire via module Dolibarr custom');
}

export async function getSensPoses(filters?: any): Promise<SensPose[]> {
  return [];
}
