import { User, Report, Regie, SensPose } from '../types';

const API_BASE = import.meta.env.VITE_API_BASE || 'https://app.mv-3pro.ch/pro/api';

function getAuthToken(): string | null {
  return localStorage.getItem('auth_token');
}

export function setAuthToken(token: string) {
  localStorage.setItem('auth_token', token);
}

export function clearAuthToken() {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('current_user');
}

async function fetchAPI(endpoint: string, options: RequestInit = {}) {
  const token = getAuthToken();

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['X-Auth-Token'] = token;
  }

  const url = `${API_BASE}${endpoint}`;

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (!response.ok) {
    if (response.status === 401) {
      clearAuthToken();
      throw new Error('Session expirée. Veuillez vous reconnecter.');
    }

    const error = await response.json().catch(() => ({ error: 'Erreur réseau' }));
    throw new Error(error.error || error.message || `HTTP ${response.status}`);
  }

  return response.json();
}

export async function login(email: string, password: string): Promise<{ user: User; token: string }> {
  const response = await fetchAPI('/auth_login.php', {
    method: 'POST',
    body: JSON.stringify({ login: email, password }),
  });

  if (!response.success) {
    throw new Error(response.error || 'Erreur de connexion');
  }

  setAuthToken(response.token);
  localStorage.setItem('current_user', JSON.stringify(response.user));

  return {
    user: response.user,
    token: response.token,
  };
}

export async function logout(): Promise<void> {
  try {
    await fetchAPI('/auth_logout.php', {
      method: 'POST',
    });
  } catch (error) {
    console.error('Erreur logout:', error);
  } finally {
    clearAuthToken();
  }
}

export async function getCurrentUser(): Promise<User> {
  const response = await fetchAPI('/auth_me.php');

  if (!response.success) {
    throw new Error(response.error || 'Erreur récupération utilisateur');
  }

  localStorage.setItem('current_user', JSON.stringify(response.user));
  return response.user;
}

export async function verifyApiKey(apiKey: string): Promise<User> {
  return login(apiKey, '');
}

export async function getReports(filters?: any): Promise<Report[]> {
  const params = new URLSearchParams();

  if (filters?.limit) params.append('limit', filters.limit);
  if (filters?.offset) params.append('offset', filters.offset);
  if (filters?.date_from) params.append('date_from', filters.date_from);
  if (filters?.date_to) params.append('date_to', filters.date_to);

  const query = params.toString() ? `?${params.toString()}` : '';
  const response = await fetchAPI(`/forms_list.php?type=rapport${query}`);

  if (!response.success) {
    throw new Error(response.error || 'Erreur récupération rapports');
  }

  return response.forms || [];
}

export async function createReport(data: Partial<Report>): Promise<Report> {
  const response = await fetchAPI('/forms_create.php', {
    method: 'POST',
    body: JSON.stringify({
      type: 'rapport',
      date: data.date,
      client_name: data.client_name,
      description: data.description,
      observations: data.observations,
      start_time: data.start_time,
      end_time: data.end_time,
      gps_latitude: data.gps_location?.latitude,
      gps_longitude: data.gps_location?.longitude,
      weather_temperature: data.weather?.temperature,
      weather_conditions: data.weather?.conditions,
      materials_used: data.materials_used || [],
      project_id: data.project_id,
    }),
  });

  if (!response.success) {
    throw new Error(response.error || 'Erreur création rapport');
  }

  if (data.photos && data.photos.length > 0) {
    const photosWithBase64 = [];

    for (const photo of data.photos) {
      if (photo.url && !photo.uploaded) {
        try {
          const blob = await fetch(photo.url).then(r => r.blob());
          const base64 = await new Promise<string>((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result as string);
            reader.readAsDataURL(blob);
          });

          photosWithBase64.push({
            data: base64,
            filename: photo.filename,
            description: photo.description || ''
          });
        } catch (error) {
          console.error('Erreur conversion photo:', error);
        }
      }
    }

    if (photosWithBase64.length > 0) {
      await fetchAPI('/forms_upload.php', {
        method: 'POST',
        body: JSON.stringify({
          form_id: response.form_id,
          photos: photosWithBase64
        })
      });
    }
  }

  return {
    ...data,
    id: String(response.form_id),
    status: 'synced',
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  } as Report;
}

export async function getProjects(filters?: { thirdparty_id?: number; limit?: number; status?: string }): Promise<any[]> {
  const params = new URLSearchParams();
  if (filters?.limit) params.append('limit', String(filters.limit));
  if (filters?.status) params.append('status', filters.status);

  const query = params.toString() ? `?${params.toString()}` : '';
  const response = await fetchAPI(`/mobile_get_projects.php${query}`);

  if (!response.success) {
    throw new Error(response.error || 'Erreur récupération projets');
  }

  return response.projects || [];
}

export async function getDashboardStats(): Promise<any> {
  const today = new Date();
  const startOfWeek = new Date(today);
  startOfWeek.setDate(today.getDate() - today.getDay());

  const reports = await getReports({ limit: 100 });

  const todayReports = reports.filter(r => r.date === today.toISOString().split('T')[0]);
  const weekReports = reports.filter(r => {
    const reportDate = new Date(r.date);
    return reportDate >= startOfWeek;
  });

  return {
    reports_today: todayReports.length,
    reports_week: weekReports.length,
    total_reports: reports.length,
  };
}

export async function generatePDF(reportId: string): Promise<Blob> {
  const token = getAuthToken();

  const response = await fetch(`${API_BASE}/forms_pdf.php?id=${reportId}`, {
    headers: {
      'X-Auth-Token': token || '',
    },
  });

  if (!response.ok) {
    throw new Error('Erreur génération PDF');
  }

  return response.blob();
}

export async function sendEmail(reportId: string, emailTo: string, subject?: string, message?: string): Promise<void> {
  const response = await fetchAPI('/forms_send_email.php', {
    method: 'POST',
    body: JSON.stringify({
      form_id: parseInt(reportId),
      email_to: emailTo,
      subject: subject || 'Rapport de chantier',
      message: message || ''
    })
  });

  if (!response.success) {
    throw new Error(response.error || 'Erreur envoi email');
  }
}

export async function getRegies(filters?: any): Promise<Regie[]> {
  return [];
}

export async function createRegie(data: Partial<Regie>): Promise<Regie> {
  return data as Regie;
}

export async function getSensPoses(filters?: any): Promise<SensPose[]> {
  return [];
}

export async function createSensPose(data: Partial<SensPose>): Promise<SensPose> {
  return data as SensPose;
}
