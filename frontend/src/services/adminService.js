import { httpRequest } from "./httpClient";

export async function fetchQuestTemplates() {
  return httpRequest("/api/admin/quest-templates");
}

export async function createQuestTemplate(payload) {
  return httpRequest("/api/admin/quest-templates", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function updateQuestTemplate(templateId, payload) {
  return httpRequest(`/api/admin/quest-templates/${templateId}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function setQuestTemplateActive(templateId, isActive) {
  return httpRequest(`/api/admin/quest-templates/${templateId}/active`, {
    method: "PATCH",
    body: JSON.stringify({ isActive }),
  });
}

export async function createEvent(payload) {
  return httpRequest("/api/admin/events", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function fetchTemplateDeleteImpact(templateId) {
  return httpRequest(`/api/admin/quest-templates/${templateId}/delete-impact`);
}

export async function deleteQuestTemplate(templateId) {
  return httpRequest(`/api/admin/quest-templates/${templateId}`, {
    method: "DELETE",
  });
}

export async function fetchAdminItems() {
  return httpRequest("/api/admin/items");
}

export async function fetchAdminCategories() {
  return httpRequest("/api/admin/categories");
}

export async function createAdminCategory(payload) {
  return httpRequest("/api/admin/categories", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function updateAdminCategory(id, payload) {
  return httpRequest(`/api/admin/categories/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function deleteAdminCategory(id) {
  return httpRequest(`/api/admin/categories/${id}`, { method: "DELETE" });
}

export async function createAdminItem(payload) {
  return httpRequest("/api/admin/items", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function updateAdminItem(id, payload) {
  return httpRequest(`/api/admin/items/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function deleteAdminItem(id) {
  return httpRequest(`/api/admin/items/${id}`, { method: "DELETE" });
}

export async function fetchAdminMonsters() {
  return httpRequest("/api/admin/monsters");
}

export async function createAdminMonster(payload) {
  return httpRequest("/api/admin/monsters", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function updateAdminMonster(id, payload) {
  return httpRequest(`/api/admin/monsters/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function deleteAdminMonster(id) {
  return httpRequest(`/api/admin/monsters/${id}`, { method: "DELETE" });
}

export async function fetchAdminHabits() {
  return httpRequest("/api/admin/habits");
}

export async function fetchQuestConditions(templateId) {
  return httpRequest(`/api/admin/quest-templates/${templateId}/conditions`);
}

export async function createQuestCondition(templateId, payload) {
  return httpRequest(`/api/admin/quest-templates/${templateId}/conditions`, {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function updateQuestCondition(templateId, conditionId, payload) {
  return httpRequest(
    `/api/admin/quest-templates/${templateId}/conditions/${conditionId}`,
    {
      method: "PUT",
      body: JSON.stringify(payload),
    }
  );
}

export async function deleteQuestCondition(templateId, conditionId) {
  return httpRequest(
    `/api/admin/quest-templates/${templateId}/conditions/${conditionId}`,
    { method: "DELETE" }
  );
}

export async function fetchQuestReward(templateId) {
  return httpRequest(`/api/admin/quest-templates/${templateId}/reward`);
}

export async function upsertQuestReward(templateId, payload) {
  return httpRequest(`/api/admin/quest-templates/${templateId}/reward`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function deleteQuestReward(templateId) {
  return httpRequest(`/api/admin/quest-templates/${templateId}/reward`, {
    method: "DELETE",
  });
}

export async function fetchAdminAchievements() {
  return httpRequest("/api/admin/achievements");
}

export async function createAdminAchievement(payload) {
  return httpRequest("/api/admin/achievements", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export async function updateAdminAchievement(id, payload) {
  return httpRequest(`/api/admin/achievements/${id}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}

export async function deleteAdminAchievement(id) {
  return httpRequest(`/api/admin/achievements/${id}`, { method: "DELETE" });
}

