import { User } from '../types';

const USER_KEY = 'mv3_user';
const TOKEN_KEY = 'mv3_token';
const BIOMETRIC_KEY = 'mv3_biometric';
const PREFERENCES_KEY = 'mv3_preferences';

export async function saveUser(user: User): Promise<void> {
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export async function getUser(): Promise<User | null> {
  const data = localStorage.getItem(USER_KEY);
  return data ? JSON.parse(data) : null;
}

export async function clearUser(): Promise<void> {
  localStorage.removeItem(USER_KEY);
  localStorage.removeItem(TOKEN_KEY);
}

export async function saveToken(token: string): Promise<void> {
  localStorage.setItem(TOKEN_KEY, token);
}

export async function getToken(): Promise<string | null> {
  return localStorage.getItem(TOKEN_KEY);
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
