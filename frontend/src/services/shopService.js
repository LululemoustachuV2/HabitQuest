import { httpRequest } from "./httpClient";

export async function fetchShop() {
  return httpRequest("/api/shop");
}

export async function purchaseShopItem(itemId) {
  return httpRequest(`/api/shop/purchase/${itemId}`, { method: "POST" });
}

