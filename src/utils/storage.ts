import { User } from '../types';

const DOLIBARR_URL_KEY = 'mv3_dolibarr_url';
const DOLAPIKEY_KEY = 'mv3_dolapikey';
const USER_KEY = 'mv3_user';
const BIOMETRIC_KEY = 'mv3_biometric';
const PREFERENCES_KEY = 'mv3_preferences';

export async function saveDolibarrConfig(url: string, apiKey: string): Promise<void> {
  localStorage.setItem(DOLIBARR_URL_KEY, url);
  localStorage.setItem(DOLAPIKEY_KEY, apiKey);
}

export async function getDolibarrUrl(): Promise<string | null> {
  return localStorage.getItem(DOLIBARR_URL_KEY);
}

export async function getDolapikey(): Promise<string | null> {
  return localStorage.getItem(DOLAPIKEY_KEY);
}

export async function saveUser(user: User): Promise<void> {
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export async function getUser(): Promise<User | null> {
  const data = localStorage.getItem(USER_KEY);
  return data ? JSON.parse(data) : null;
}

export async function clearUser(): Promise<void> {
  localStorage.removeItem(USER_KEY);
  localStorage.removeItem(DOLIBARR_URL_KEY);
  localStorage.removeItem(DOLAPIKEY_KEY);
}

export async function saveBiometricPreference(enabled: boolean): Promise<void> {
  localStorage.setItem(BIOMETRIC_KEY, String(enabled));
}

export async function getBiometricPreference(): Promise<boolean> {
  return localStorage.getItem(BIOMETRIC_KEY) === 'true';
}

export async function savePreferences(preferences: any): Promise<void> {
  localStorage.setItem(PREFERENCES_KEY, JSON.stringify(preferences));
}

export async function getPreferences(): Promise<any> {
  const data = localStorage.getItem(PREFERENCES_KEY);
  return data ? JSON.parse(data) : null;
}
