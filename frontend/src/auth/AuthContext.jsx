import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { getAuthToken, getMe, loginUser, logoutUser, registerUser, setAuthToken } from '../api/wordpress';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  async function refreshUser() {
    const token = getAuthToken();
    if (!token) {
      setUser(null);
      setLoading(false);
      return;
    }

    try {
      const result = await getMe();
      setUser(result?.user || null);
    } catch (error) {
      setAuthToken('');
      setUser(null);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    refreshUser();
  }, []);

  async function register({ name, email, password }) {
    setLoading(true);
    const result = await registerUser({ name, email, password });
    setUser(result?.user || null);
    setLoading(false);
    return result;
  }

  async function login({ identifier, email, password }) {
    setLoading(true);
    const result = await loginUser({ identifier, email, password });
    setUser(result?.user || null);
    setLoading(false);
    return result;
  }

  async function logout() {
    await logoutUser();
    setUser(null);
  }

  const value = useMemo(
    () => ({
      user,
      loading,
      isAuthenticated: Boolean(user?.id),
      refreshUser,
      register,
      login,
      logout,
    }),
    [user, loading],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return ctx;
}

