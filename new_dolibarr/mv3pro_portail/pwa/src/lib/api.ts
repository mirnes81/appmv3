import { API_PATHS, PWA_URLS } from '../config';

const API_BASE_URL = API_PATHS.base;
const AUTH_API_URL = API_PATHS.auth;

const TOKEN_KEY = 'mv3pro_token';
const DEBUG_MODE = localStorage.getItem('mv3pro_debug') === 'true';

function debugLog(message: string, data?: any) {
  if (DEBUG_MODE) {
    console.log(`[MV3PRO DEBUG] ${message}`, data || '');
  }
}

export const storage = {
  getToken: (): string | null => localStorage.getItem(TOKEN_KEY),
  setToken: (token: string) => localStorage.setItem(TOKEN_KEY, token),
  clearToken: () => localStorage.removeItem(TOKEN_KEY),
};

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public data?: any
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

/**
 * Parse JSON de manière sécurisée
 * Ne crash jamais, retourne null si la réponse n'est pas du JSON valide
 */
async function safeJson(res: Response): Promise<any> {
  const text = await res.text();

  if (!text || text.trim() === '') {
    return null;
  }

  try {
    return JSON.parse(text);
  } catch (error) {
    console.error('Erreur parsing JSON:', error);
    console.error('Réponse reçue:', text.slice(0, 500));
    throw new ApiError(
      `Réponse non-JSON du serveur (HTTP ${res.status}): ${text.slice(0, 200)}`,
      res.status,
      { rawText: text }
    );
  }
}

async function apiFetch<T = any>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const token = storage.getToken();
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(options.headers as Record<string, string>),
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
    headers['X-Auth-Token'] = token;
  }

  const url = path.startsWith('http') ? path : `${API_BASE_URL}${path}`;

  debugLog('API Request', {
    url,
    method: options.method || 'GET',
    hasToken: !!token,
    tokenPreview: token ? token.substring(0, 20) + '...' : 'none',
  });

  try {
    const response = await fetch(url, {
      ...options,
      headers,
    });

    debugLog('API Response', {
      url,
      status: response.status,
      statusText: response.statusText,
      contentType: response.headers.get('content-type'),
    });

    if (response.status === 401) {
      debugLog('401 Unauthorized - Clearing token and redirecting to login');
      storage.clearToken();
      window.location.href = PWA_URLS.login;
      throw new ApiError('Non autorisé', 401);
    }

    const contentType = response.headers.get('content-type');
    const isJson = contentType?.includes('application/json');

    if (!response.ok) {
      let errorData: any;
      try {
        errorData = isJson ? await safeJson(response) : await response.text();
      } catch (e) {
        errorData = { message: `Erreur ${response.status}` };
      }

      throw new ApiError(
        errorData?.message || `Erreur ${response.status}`,
        response.status,
        errorData
      );
    }

    if (isJson) {
      return await safeJson(response);
    }

    return (await response.text()) as any;
  } catch (error) {
    if (error instanceof ApiError) {
      throw error;
    }
    throw new ApiError('Erreur de connexion', 0, error);
  }
}

export interface LoginResponse {
  success: boolean;
  token?: string;
  user?: {
    user_rowid: number;
    email: string;
    firstname: string;
    lastname: string;
    dolibarr_user_id?: number;
  };
  message?: string;
  hint?: string;
}

export interface User {
  id: number | null;
  login?: string | null;
  name?: string;
  email: string;
  firstname?: string;
  lastname?: string;
  dolibarr_user_id?: number;
  mobile_user_id?: number;
  role?: string;
  auth_mode?: string;
  is_unlinked?: boolean;
  warning?: string;
  rights?: {
    read?: boolean;
    write?: boolean;
    validate?: boolean;
    worker?: boolean;
  };
}

export interface PlanningEvent {
  id: number;
  label: string;
  datep: string;
  datef?: string;
  location?: string;
  client_nom?: string;
  type?: string;
  status?: string;
  files_count?: number;
  photos_count?: number;
  documents_count?: number;
  last_photo_url?: string;
}

export interface Rapport {
  rowid: number;
  id: number;
  ref?: string;
  fk_user: number;
  projet_nom?: string;
  projet_ref?: string;
  projet_title?: string;
  client?: string;
  date_rapport: string;
  date?: string;
  heure_debut?: string;
  heure_fin?: string;
  heures?: number;
  description?: string;
  travaux?: string;
  observations?: string;
  statut?: string;
  type?: string;
  zones?: string;
  surface?: number;
  format?: string;
  type_carrelage?: string;
  user?: string;
  has_photos?: boolean;
  nb_photos?: number;
  url?: string;
}

export interface RapportPhoto {
  id: number;
  filename: string;
  filepath?: string;
  url?: string;
  description?: string;
  legende?: string;
  categorie?: string;
  zone?: string;
  date_ajout?: string;
  ordre?: number;
}

export interface RapportProbleme {
  id: number;
  titre: string;
  description?: string;
  photo?: string;
  photo_url?: string;
  statut?: string;
  date_creation?: string;
}

export interface RapportDetail {
  rapport: {
    id: number;
    date_rapport: string;
    zone_travail?: string;
    description?: string;
    heures_debut?: string;
    heures_fin?: string;
    temps_total?: number;
    travaux_realises?: string;
    observations?: string;
    statut?: string;
    date_creation?: string;
    date_modification?: string;
    auteur?: {
      id: number;
      login: string;
      nom: string;
    };
    projet?: {
      id: number;
      ref: string;
      title: string;
      client?: string;
    } | null;
    photos_count?: number;
    frais_count?: number;
    gps?: {
      latitude: number;
      longitude: number;
      precision: number;
    };
    meteo?: {
      temperature: number;
      condition: string;
    };
  };
  photos: RapportPhoto[];
  frais?: any[];
}

export interface RapportCreatePayload {
  projet_id?: number;
  date_rapport: string;
  heure_debut?: string;
  heure_fin?: string;
  description?: string;
  observations?: string;
  meteo?: string;
  temperature?: string;
  latitude?: string;
  longitude?: string;
  photos?: string[];
}

export const api = {
  async login(email: string, password: string): Promise<LoginResponse> {
    debugLog('Login attempt', { email });
    try {
      const response = await fetch(`${AUTH_API_URL}?action=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });

      const data = await safeJson(response);

      if (!data) {
        debugLog('Login failed: Empty response');
        throw new ApiError('Réponse vide du serveur', response.status);
      }

      debugLog('Login response', {
        success: data.success,
        hasToken: !!data.token,
        tokenPreview: data.token ? data.token.substring(0, 20) + '...' : 'none',
        user: data.user,
      });

      if (data.success && data.token) {
        storage.setToken(data.token);
        debugLog('Token saved to localStorage');
      }

      return data;
    } catch (error) {
      if (error instanceof ApiError) {
        throw error;
      }
      throw new ApiError('Erreur de connexion au serveur', 0, error);
    }
  },

  async logout(): Promise<void> {
    try {
      await fetch(`${AUTH_API_URL}?action=logout`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${storage.getToken()}`,
        },
      });
    } finally {
      storage.clearToken();
    }
  },

  async me(): Promise<User> {
    debugLog('Fetching /me.php');
    const response = await apiFetch<{ success: boolean; user: any }>('/me.php');
    const user = response.user;

    debugLog('/me.php response', {
      success: response.success,
      user: user,
      is_unlinked: user.is_unlinked,
    });

    // Extraire firstname/lastname depuis name si nécessaire
    if (!user.firstname && !user.lastname && user.name) {
      const nameParts = user.name.split(' ');
      user.firstname = nameParts[0] || '';
      user.lastname = nameParts.slice(1).join(' ') || '';
    }

    return user as User;
  },

  async planning(from?: string, to?: string): Promise<PlanningEvent[]> {
    const params = new URLSearchParams();
    if (from) params.append('from', from);
    if (to) params.append('to', to);
    const query = params.toString();
    return apiFetch<PlanningEvent[]>(`/planning.php${query ? '?' + query : ''}`);
  },

  async rapportsList(limit = 50, page = 1): Promise<Rapport[]> {
    return apiFetch<Rapport[]>(`/rapports.php?limit=${limit}&page=${page}`);
  },

  async rapportsView(id: number): Promise<RapportDetail> {
    return apiFetch<RapportDetail>(`/rapports_view.php?id=${id}`);
  },

  async rapportsCreate(payload: RapportCreatePayload): Promise<any> {
    return apiFetch('/rapports_create.php', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  async rapportsDebug(): Promise<any> {
    return apiFetch('/rapports_debug.php');
  },

  async regieList(): Promise<any[]> {
    const response = await apiFetch<{ regies: any[] }>('/regie.php');
    return response.regies || [];
  },

  async regieCreate(payload: any): Promise<any> {
    return apiFetch('/regie.php', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  async sensPoseList(): Promise<any[]> {
    return apiFetch<any[]>('/sens_pose.php');
  },

  async sensPoseCreate(payload: any): Promise<any> {
    return apiFetch('/sens_pose.php', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  async materielList(): Promise<any[]> {
    return apiFetch<any[]>('/materiel.php');
  },

  async notificationsList(): Promise<any[]> {
    return apiFetch<any[]>('/notifications.php');
  },

  /**
   * Méthode générique GET
   */
  async get<T = any>(path: string, params?: Record<string, string>): Promise<T> {
    let url = path;
    if (params) {
      const searchParams = new URLSearchParams(params);
      url += '?' + searchParams.toString();
    }
    return apiFetch<T>(url, { method: 'GET' });
  },

  /**
   * Méthode générique POST
   */
  async post<T = any>(path: string, data: any): Promise<T> {
    return apiFetch<T>(path, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  /**
   * Méthode générique PUT
   */
  async put<T = any>(path: string, data: any): Promise<T> {
    return apiFetch<T>(path, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  /**
   * Méthode générique DELETE
   */
  async delete<T = any>(path: string): Promise<T> {
    return apiFetch<T>(path, { method: 'DELETE' });
  },

  /**
   * Upload de fichier avec progression
   */
  async upload<T = any>(path: string, formData: FormData, onProgress?: (progress: number) => void): Promise<T> {
    const token = storage.getToken();
    const headers: Record<string, string> = {};

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
      headers['X-Auth-Token'] = token;
    }

    const url = path.startsWith('http') ? path : `${API_BASE_URL}${path}`;

    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();

      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable && onProgress) {
          onProgress(e.loaded / e.total);
        }
      });

      xhr.addEventListener('load', async () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const data = JSON.parse(xhr.responseText);
            resolve(data);
          } catch {
            resolve(xhr.responseText as any);
          }
        } else if (xhr.status === 401) {
          storage.clearToken();
          window.location.href = PWA_URLS.login;
          reject(new ApiError('Non autorisé', 401));
        } else {
          try {
            const errorData = JSON.parse(xhr.responseText);
            reject(new ApiError(errorData.message || `Erreur ${xhr.status}`, xhr.status, errorData));
          } catch {
            reject(new ApiError(`Erreur ${xhr.status}`, xhr.status));
          }
        }
      });

      xhr.addEventListener('error', () => {
        reject(new ApiError('Erreur de connexion', 0));
      });

      xhr.open('POST', url);
      Object.entries(headers).forEach(([key, value]) => {
        xhr.setRequestHeader(key, value);
      });
      xhr.send(formData);
    });
  },
};

/**
 * Client API générique pour les appels non couverts par l'objet `api`
 * Usage: apiClient('/planning_view.php?id=123')
 *
 * Extension de apiFetch avec les méthodes de l'objet api
 */
export const apiClient = Object.assign(apiFetch, api);
