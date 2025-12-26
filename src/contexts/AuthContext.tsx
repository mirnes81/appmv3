import React, { createContext, useContext, useState, useEffect } from 'react';
import { User } from '../types';
import { login as apiLogin, logout as apiLogout, getCurrentUser, setAuthToken } from '../utils/api';
import { getUser, saveUser, clearUser } from '../utils/storage';

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  async function checkAuth() {
    try {
      const storedUser = await getUser();

      if (storedUser) {
        const currentUser = await getCurrentUser();
        setUser(currentUser);
        await saveUser(currentUser);
      }
    } catch (error) {
      console.error('Erreur auth check:', error);
      await clearUser();
      setUser(null);
    } finally {
      setLoading(false);
    }
  }

  async function login(email: string, password: string) {
    setLoading(true);
    try {
      const { user: loggedUser, token } = await apiLogin(email, password);
      setAuthToken(token);
      await saveUser(loggedUser);
      setUser(loggedUser);
    } finally {
      setLoading(false);
    }
  }

  async function logout() {
    setLoading(true);
    try {
      await apiLogout();
      await clearUser();
      setUser(null);
    } finally {
      setLoading(false);
    }
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
