import { httpRequest } from "./httpClient";

export async function fetchActiveMonster() {
  const payload = await httpRequest("/api/monster/active");
  return payload?.monster ?? null;
}

