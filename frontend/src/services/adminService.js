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
