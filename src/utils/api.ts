import { User, Report, Regie, SensPose } from '../types';
import { getToken } from './storage';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'https://crm.mv-3pro.ch/custom/mv3pro_portail/api';

async function fetchAPI(endpoint: string, options: RequestInit = {}) {
  const token = await getToken();

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'Network error' }));
    throw new Error(error.message || `HTTP ${response.status}`);
  }

  return response.json();
}

export async function login(email: string, password: string): Promise<User> {
  const response = await fetchAPI('/auth/login.php', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

  if (response.token) {
    const { saveToken } = await import('./storage');
    await saveToken(response.token);
  }

  return response.user;
}

export async function logout(): Promise<void> {
  await fetchAPI('/auth/logout.php', { method: 'POST' });
}

export async function verifySession(): Promise<boolean> {
  try {
    await fetchAPI('/auth/verify.php');
    return true;
  } catch {
    return false;
  }
}

export async function createReport(data: Partial<Report>): Promise<Report> {
  return fetchAPI('/reports/create.php', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function getReports(filters?: any): Promise<Report[]> {
  const query = filters ? `?${new URLSearchParams(filters)}` : '';
  return fetchAPI(`/reports/list.php${query}`);
}

export async function createRegie(data: Partial<Regie>): Promise<Regie> {
  return fetchAPI('/regie/create.php', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function getRegies(filters?: any): Promise<Regie[]> {
  const query = filters ? `?${new URLSearchParams(filters)}` : '';
  return fetchAPI(`/regie/list.php${query}`);
}

export async function createSensPose(data: Partial<SensPose>): Promise<SensPose> {
  return fetchAPI('/sens_pose/create.php', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function getSensPoses(filters?: any): Promise<SensPose[]> {
  const query = filters ? `?${new URLSearchParams(filters)}` : '';
  return fetchAPI(`/sens_pose/list.php${query}`);
}

export async function uploadPhoto(data: { file: File; type: string; related_id: string }): Promise<any> {
  const token = await getToken();
  const formData = new FormData();
  formData.append('file', data.file);
  formData.append('type', data.type);
  formData.append('related_id', data.related_id);

  const response = await fetch(`${API_BASE_URL}/photos/upload.php`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token || ''}`,
    },
    body: formData,
  });

  if (!response.ok) {
    throw new Error('Photo upload failed');
  }

  return response.json();
}

export async function getClients(): Promise<any[]> {
  return fetchAPI('/clients/list.php');
}

export async function getProjects(clientId?: number): Promise<any[]> {
  const query = clientId ? `?client_id=${clientId}` : '';
  return fetchAPI(`/projects/list.php${query}`);
}

export async function getMaterials(): Promise<any[]> {
  return fetchAPI('/materials/list.php');
}

export async function getWeather(lat: number, lon: number): Promise<any> {
  return fetchAPI(`/weather/current.php?lat=${lat}&lon=${lon}`);
}

export async function getDashboardStats(): Promise<any> {
  return fetchAPI('/dashboard/stats.php');
}
