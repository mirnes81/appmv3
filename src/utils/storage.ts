import { User } from '../types';

const AUTH_TOKEN_KEY = 'auth_token';
const USER_KEY = 'current_user';

export async function getDolapikey(): Promise<string | null> {
  return localStorage.getItem(AUTH_TOKEN_KEY);
}

export async function saveDolapikey(token: string): Promise<void> {
  localStorage.setItem(AUTH_TOKEN_KEY, token);
}

export async function clearDolapikey(): Promise<void> {
  localStorage.removeItem(AUTH_TOKEN_KEY);
}

export async function getUser(): Promise<User | null> {
  const userStr = localStorage.getItem(USER_KEY);
  if (!userStr) return null;

  try {
    return JSON.parse(userStr);
  } catch {
    return null;
  }
}

export async function saveUser(user: User): Promise<void> {
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export async function clearUser(): Promise<void> {
  localStorage.removeItem(USER_KEY);
  localStorage.removeItem(AUTH_TOKEN_KEY);
}
