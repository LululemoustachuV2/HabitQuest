import { httpRequest } from "./httpClient";

export async function fetchUserQuests() {
  return httpRequest("/api/quests");
}

export async function validateQuest(userQuestId, comment) {
  return httpRequest(`/api/quests/${userQuestId}/validate`, {
    method: "POST",
    body: JSON.stringify({ comment }),
  });
}

export async function fetchMyProgression() {
  return httpRequest("/api/me/progression");
}
