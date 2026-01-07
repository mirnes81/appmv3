const API_BASE_URL = '/custom/mv3pro_portail/api/v1';
const AUTH_API_URL = '/custom/mv3pro_portail/mobile_app/api/auth.php';

const TOKEN_KEY = 'mv3pro_token';

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
  }

  const url = path.startsWith('http') ? path : `${API_BASE_URL}${path}`;

  try {
    const response = await fetch(url, {
      ...options,
      headers,
    });

    if (response.status === 401) {
      storage.clearToken();
      window.location.href = '/custom/mv3pro_portail/pwa_dist/#/login';
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
}

export interface User {
  id: number;
  email: string;
  firstname: string;
  lastname: string;
  dolibarr_user_id?: number;
  role?: string;
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
    try {
      const response = await fetch(`${AUTH_API_URL}?action=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });

      const data = await safeJson(response);

      if (!data) {
        throw new ApiError('Réponse vide du serveur', response.status);
      }

      if (data.success && data.token) {
        storage.setToken(data.token);
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
    return apiFetch<User>('/me.php');
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
