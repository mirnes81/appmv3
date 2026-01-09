/**
 * Configuration globale de l'application PWA
 */

// Base path de l'application PWA (doit correspondre à vite.config.ts)
export const BASE_PWA_PATH = '/custom/mv3pro_portail/pwa_dist';

// URLs complètes pour les redirections window.location
export const PWA_URLS = {
  login: `${BASE_PWA_PATH}/#/login`,
  dashboard: `${BASE_PWA_PATH}/#/dashboard`,
  planning: `${BASE_PWA_PATH}/#/planning`,
  rapports: `${BASE_PWA_PATH}/#/rapports`,
  regie: `${BASE_PWA_PATH}/#/regie`,
  sensPose: `${BASE_PWA_PATH}/#/sens-pose`,
  materiel: `${BASE_PWA_PATH}/#/materiel`,
  notifications: `${BASE_PWA_PATH}/#/notifications`,
  profil: `${BASE_PWA_PATH}/#/profil`,
};

// API endpoints
export const API_PATHS = {
  base: '/custom/mv3pro_portail/api/v1',
  auth: '/custom/mv3pro_portail/mobile_app/api/auth.php',
};
