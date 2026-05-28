import { useEffect, useMemo, useState } from "react";
import { equipInventoryItem, fetchInventory } from "../services/inventoryService";
import { computeMaxEquipSlots, rarityChipClass, rarityLabel } from "../utils/rarity";

export default function InventoryPage() {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  const equipped = useMemo(() => items.filter((entry) => entry.isEquipped), [items]);
  const maxSlots = useMemo(() => computeMaxEquipSlots(equipped), [equipped]);

  async function loadInventory() {
    setLoading(true);
    setError("");
    try {
      const result = await fetchInventory();
      setItems(result.items || []);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur chargement inventaire");
    } finally {
      setLoading(false);
    }
  }

  async function handleEquip(inventoryId) {
    setMessage("");
    setError("");
    try {
      const entry = await equipInventoryItem(inventoryId);
      setMessage(entry.isEquipped ? "Item équipé" : "Item déséquipé");
      await loadInventory();
    } catch (err) {
      setError(err.payload?.message || err.message || "Équipement impossible");
    }
  }

  useEffect(() => {
    loadInventory();
  }, []);

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Mon inventaire</h2>
          <p className="page-header-sub">
            Items obtenus et équipement actif ({equipped.length} / {maxSlots} slots).
          </p>
        </div>
        <button type="button" className="btn btn--sm" onClick={loadInventory}>
          Recharger
        </button>
      </div>

      {error && (
        <div className="alert alert--error">
          <span className="alert-icon">!</span>
          <span>{error}</span>
        </div>
      )}
      {message && (
        <div className="alert alert--success">
          <span className="alert-icon">✓</span>
          <span>{message}</span>
        </div>
      )}

      <div className="surface">
        {loading ? (
          <div className="skeleton" style={{ height: 80 }} />
        ) : items.length === 0 ? (
          <p className="mb-0">
            Inventaire vide. Utilise <code>app:seed:post-mvp</code> ou bats un monstre pour du loot.
          </p>
        ) : (
          <ul className="habit-list">
            {items.map((entry) => (
              <li key={entry.id} className="habit-row">
                <div>
                  <strong>{entry.item?.name}</strong>
                  <p className="quest-card-desc mb-0">
                    <span className={rarityChipClass(entry.item?.rarity)}>
                      {rarityLabel(entry.item?.rarity)}
                    </span>
                    {" · "}+{entry.item?.bonusXpPercent ?? 0}% XP · +{entry.item?.bonusGold ?? 0} or
                    {entry.item?.bonusEquipSlots > 0 &&
                      ` · +${entry.item.bonusEquipSlots} slot${entry.item.bonusEquipSlots > 1 ? "s" : ""}`}
                    {entry.isEquipped ? " · équipé" : ""}
                  </p>
                </div>
                <button
                  type="button"
                  className={`btn btn--sm ${entry.isEquipped ? "btn--ghost" : "btn--accent"}`}
                  onClick={() => handleEquip(entry.id)}
                >
                  {entry.isEquipped ? "Déséquiper" : "Équiper"}
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </section>
  );
}

