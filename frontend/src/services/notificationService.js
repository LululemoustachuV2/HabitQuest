import { httpRequest } from "./httpClient";

export async function fetchNotifications() {
  return httpRequest("/api/notifications");
}

export async function markNotificationAsRead(notificationId) {
  return httpRequest(`/api/notifications/${notificationId}/read`, {
    method: "POST",
  });
}
