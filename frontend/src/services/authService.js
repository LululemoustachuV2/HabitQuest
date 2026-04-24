import { STORAGE_KEYS } from "../config/env";
import { httpRequest } from "./httpClient";

export async function registerUser(input) {
  return httpRequest("/api/auth/register", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export async function loginUser(input) {
  return httpRequest("/api/auth/login", {
    method: "POST",
    body: JSON.stringify(input),
  });
}

export function persistSession(token, user) {
  window.localStorage.setItem(STORAGE_KEYS.token, token);
  window.localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(user));
}

export function clearSession() {
  window.localStorage.removeItem(STORAGE_KEYS.token);
  window.localStorage.removeItem(STORAGE_KEYS.user);
}

export function readStoredUser() {
  const raw = window.localStorage.getItem(STORAGE_KEYS.user);
  if (!raw) {
    return null;
  }
  try {
    return JSON.parse(raw);
  } catch (_error) {
    return null;
  }
}

export function readStoredToken() {
  return window.localStorage.getItem(STORAGE_KEYS.token);
}
