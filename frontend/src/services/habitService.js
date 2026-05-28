import { httpRequest } from "./httpClient";

export function fetchHabits() {
  return httpRequest("/api/habits");
}

export function createHabit(payload) {
  return httpRequest("/api/habits", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function logHabit(habitId, note) {
  const body = note ? JSON.stringify({ note }) : undefined;
  return httpRequest(`/api/habits/${habitId}/log`, {
    method: "POST",
    body,
  });
}

