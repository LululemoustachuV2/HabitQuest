import { API_BASE_URL, STORAGE_KEYS } from "../config/env";

function getToken() {
  return window.localStorage.getItem(STORAGE_KEYS.token);
}

export async function httpRequest(path, options = {}) {
  const hasBody = options.body !== undefined;
  const token = getToken();

  const headers = {
    ...(hasBody ? { "Content-Type": "application/json" } : {}),
    ...(options.headers || {}),
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  });

  const rawBody = await response.text();
  let payload = null;
  try {
    payload = rawBody ? JSON.parse(rawBody) : null;
  } catch (_error) {
    const snippet = rawBody?.replace(/\s+/g, " ").slice(0, 300) || "";
    payload = {
      message: `Reponse non JSON (HTTP ${response.status})`,
      raw: snippet,
    };
  }

  if (!response.ok) {
    const error = new Error(payload?.message || `Erreur API (HTTP ${response.status})`);
    error.status = response.status;
    error.payload = payload;
    throw error;
  }

  return payload;
}
