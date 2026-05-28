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
  const [reconnectNonce, setReconnectNonce] = useState(0);

  const isAuthenticated = Boolean(token);
  const roles = user?.roles || [];

  const value = useMemo(
    () => ({
      token,
      user,
      roles,
      isAuthenticated,
      reconnectNonce,
      login(sessionToken, sessionUser, options = {}) {
        persistSession(sessionToken, sessionUser);
        setToken(sessionToken);
        setUser(sessionUser);
        if (options.reconnect) {
          setReconnectNonce((current) => current + 1);
        }
      },
      logout() {
        clearSession();
        setToken(null);
        setUser(null);
        setReconnectNonce(0);
      },
    }),
    [isAuthenticated, reconnectNonce, roles, token, user]
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

