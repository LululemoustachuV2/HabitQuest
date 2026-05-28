import { httpRequest } from "./httpClient";
import { fetchMyProgression } from "./questService";
import { fetchActiveMonster } from "./monsterService";
import { fetchInventory } from "./inventoryService";

export { fetchMyProgression };

export async function fetchUserStats() {
  return httpRequest("/api/user/stats");
}

export async function fetchAchievements() {
  return httpRequest("/api/achievements");
}

export { fetchActiveMonster, fetchInventory };

export async function fetchProfileData() {
  const [progression, statsPayload, achievementsPayload, monster, inventoryPayload] =
    await Promise.all([
      fetchMyProgression(),
      fetchUserStats(),
      fetchAchievements(),
      fetchActiveMonster(),
      fetchInventory(),
    ]);

  const items = inventoryPayload?.items || [];
  const equipped = items.filter((entry) => entry.isEquipped);

  return {
    progression,
    stats: statsPayload?.stats || {},
    gold: progression?.gold ?? statsPayload?.gold ?? 0,
    achievements: achievementsPayload?.achievements || [],
    currentStreak: achievementsPayload?.currentStreak ?? 0,
    longestStreak: achievementsPayload?.longestStreak ?? 0,
    monster,
    equipped,
    inventoryCount: items.length,
  };
}

