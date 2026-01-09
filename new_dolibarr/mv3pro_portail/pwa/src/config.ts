/**
 * Configuration globale de l'application PWA
 * Utilise les variables d'environnement Vite pour gérer dev/prod
 */

// Variables d'environnement
const API_BASE = import.meta.env.VITE_API_BASE || '/custom/mv3pro_portail';
const BASE_PATH = import.meta.env.VITE_BASE_PATH || '/custom/mv3pro_portail/pwa_dist';

// Base path de l'application PWA (doit correspondre à vite.config.ts)
export const BASE_PWA_PATH = BASE_PATH;

// URLs complètes pour les redirections window.location
export const PWA_URLS = {
  login: `${BASE_PATH}/#/login`,
  dashboard: `${BASE_PATH}/#/dashboard`,
  planning: `${BASE_PATH}/#/planning`,
  rapports: `${BASE_PATH}/#/rapports`,
  regie: `${BASE_PATH}/#/regie`,
  sensPose: `${BASE_PATH}/#/sens-pose`,
  materiel: `${BASE_PATH}/#/materiel`,
  notifications: `${BASE_PATH}/#/notifications`,
  profil: `${BASE_PATH}/#/profil`,
};

// API endpoints
export const API_PATHS = {
  base: `${API_BASE}/api/v1`,
  auth: `${API_BASE}/mobile_app/api/auth.php`,
};
