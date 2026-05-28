export function rarityChipClass(rarity) {
  const value = (rarity || "common").toLowerCase();
  if (value === "rare") return "chip chip--rarity-rare";
  if (value === "epic") return "chip chip--rarity-epic";
  return "chip chip--rarity-common";
}

export function rarityLabel(rarity) {
  const labels = { common: "Commun", rare: "Rare", epic: "Épique" };
  return labels[(rarity || "common").toLowerCase()] || rarity;
}

export function computeMaxEquipSlots(equippedEntries, baseSlots = 3) {
  const bonus = (equippedEntries || []).reduce(
    (sum, entry) => sum + (entry.item?.bonusEquipSlots || 0),
    0
  );
  return baseSlots + bonus;
}

