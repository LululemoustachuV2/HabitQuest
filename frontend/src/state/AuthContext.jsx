import { createContext, useContext, useMemo, useState } from "react";
import {
  clearSession,
  persistSession,
  readStoredToken,
  readStoredUser,
} from "../services/authService";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => readStoredToken());
  const [user, setUser] = useState(() => readStoredUser());

  const isAuthenticated = Boolean(token);
  const roles = user?.roles || [];

  const value = useMemo(
    () => ({
      token,
      user,
      roles,
      isAuthenticated,
      login(sessionToken, sessionUser) {
        persistSession(sessionToken, sessionUser);
        setToken(sessionToken);
        setUser(sessionUser);
      },
      logout() {
        clearSession();
        setToken(null);
        setUser(null);
      },
    }),
    [isAuthenticated, roles, token, user]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth doit etre utilise dans AuthProvider");
  }
  return context;
}
