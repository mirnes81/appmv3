import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { User } from '../types';
import * as api from '../utils/api';
import * as storage from '../utils/storage';

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (url: string, apiKey: string) => Promise<void>;
  logout: () => Promise<void>;
  enableBiometric: () => Promise<boolean>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkSession();
  }, []);

  const checkSession = async () => {
    try {
      const savedUser = await storage.getUser();
      if (savedUser) {
        const isValid = await api.verifySession();
        if (isValid) {
          setUser(savedUser);
        } else {
          await storage.clearUser();
        }
      }
    } catch (error) {
      console.error('Session check failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const login = async (url: string, apiKey: string) => {
    try {
      setLoading(true);

      const userData = await api.login(url, apiKey);
      setUser(userData);
      await storage.saveUser(userData);
    } catch (error) {
      console.error('Login failed:', error);
      throw error;
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    try {
      await api.logout();
      setUser(null);
      await storage.clearUser();
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  const enableBiometric = async (): Promise<boolean> => {
    if (!('credentials' in navigator)) {
      return false;
    }

    try {
      const credential = await (navigator.credentials as any).create({
        publicKey: {
          challenge: new Uint8Array(32),
          rp: {
            name: "MV3 Pro",
            id: window.location.hostname
          },
          user: {
            id: new Uint8Array(16),
            name: user?.email || '',
            displayName: user?.name || ''
          },
          pubKeyCredParams: [
            { type: "public-key", alg: -7 }
          ],
          authenticatorSelection: {
            authenticatorAttachment: "platform",
            userVerification: "required"
          }
        }
      });

      if (credential) {
        await storage.saveBiometricPreference(true);
        return true;
      }
      return false;
    } catch (error) {
      console.error('Biometric enrollment failed:', error);
      return false;
    }
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, enableBiometric }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
