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
}

export interface Rapport {
  rowid: number;
  fk_user: number;
  projet_nom?: string;
  date_rapport: string;
  heure_debut?: string;
  heure_fin?: string;
  description?: string;
  statut?: string;
  type?: string;
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

  async rapportsCreate(payload: RapportCreatePayload): Promise<any> {
    return apiFetch('/rapports_create.php', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  async regieList(): Promise<any[]> {
    throw new ApiError('Endpoint non disponible (à créer)', 501);
  },

  async regieCreate(_payload: any): Promise<any> {
    throw new ApiError('Endpoint non disponible (à créer)', 501);
  },

  async sensPoseList(): Promise<any[]> {
    throw new ApiError('Endpoint non disponible (à créer)', 501);
  },

  async sensPoseCreate(_payload: any): Promise<any> {
    throw new ApiError('Endpoint non disponible (à créer)', 501);
  },

  async materielList(): Promise<any[]> {
    throw new ApiError('Endpoint non disponible (à créer)', 501);
  },

  async notificationsList(): Promise<any[]> {
    throw new ApiError('Endpoint non disponible (à créer)', 501);
  },
};
