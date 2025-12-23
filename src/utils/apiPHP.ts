/**
 * API Client pour l'API PHP MySQL
 */

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost/dolibarr/custom/mv3pro_portail/api_pwa';

let authToken: string | null = null;

export function setAuthToken(token: string) {
  authToken = token;
  localStorage.setItem('auth_token', token);
}

export function getAuthToken(): string | null {
  if (!authToken) {
    authToken = localStorage.getItem('auth_token');
  }
  return authToken;
}

export function clearAuthToken() {
  authToken = null;
  localStorage.removeItem('auth_token');
}

async function fetchAPI(endpoint: string, options: RequestInit = {}) {
  const token = getAuthToken();

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: 'Erreur réseau' }));
    throw new Error(error.error || `HTTP ${response.status}`);
  }

  return response.json();
}

export async function loginPHP(email: string, password: string) {
  const response = await fetchAPI('/auth.php?action=login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

  if (response.token) {
    setAuthToken(response.token);
  }

  return response;
}

export async function logoutPHP() {
  try {
    await fetchAPI('/auth.php?action=logout', {
      method: 'POST',
    });
  } finally {
    clearAuthToken();
  }
}

export async function verifySessionPHP() {
  try {
    const response = await fetchAPI('/auth.php?action=verify');
    return response.success;
  } catch {
    return false;
  }
}

export async function getReportsPHP() {
  return fetchAPI('/reports.php?action=list');
}

export async function createReportPHP(data: any) {
  return fetchAPI('/reports.php?action=create', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function getDraftsPHP() {
  return fetchAPI('/reports.php?action=drafts');
}

export async function saveDraftPHP(data: any) {
  return fetchAPI('/reports.php?action=save_draft', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function deleteDraftPHP(id: number) {
  return fetchAPI(`/reports.php?action=delete_draft&id=${id}`, {
    method: 'DELETE',
  });
}

export async function getMaterielPHP() {
  return fetchAPI('/materiel.php?action=list');
}

export async function updateMaterielStatusPHP(id: number, status: string, notes: string) {
  return fetchAPI('/materiel.php?action=update_status', {
    method: 'POST',
    body: JSON.stringify({ id, status, notes }),
  });
}
