import { httpRequest } from "./httpClient";

export function fetchInventory() {
  return httpRequest("/api/inventory");
}

export function equipInventoryItem(inventoryId) {
  return httpRequest(`/api/inventory/${inventoryId}/equip`, {
    method: "POST",
  });
}

