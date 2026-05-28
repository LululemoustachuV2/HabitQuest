import { useCallback, useEffect, useState } from "react";
import {
  deleteQuestReward,
  fetchAdminItems,
  fetchQuestReward,
  upsertQuestReward,
} from "../services/adminService";

const EMPTY_REWARD = { xp: 0, gold: 0, itemId: "" };

export default function QuestRewardEditor({ templateId, onError, onMessage }) {
  const [items, setItems] = useState([]);
  const [form, setForm] = useState(EMPTY_REWARD);
  const [hasReward, setHasReward] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const loadReward = useCallback(async () => {
    setLoading(true);
    onError?.("");
    try {
      const [rewardResult, itemsResult] = await Promise.all([
        fetchQuestReward(templateId),
        fetchAdminItems(),
      ]);
      setItems(itemsResult.items || []);
      const reward = rewardResult.reward;
      if (reward) {
        setHasReward(true);
        setForm({
          xp: reward.xp ?? 0,
          gold: reward.gold ?? 0,
          itemId: reward.itemId ? String(reward.itemId) : "",
        });
      } else {
        setHasReward(false);
        setForm(EMPTY_REWARD);
      }
    } catch (err) {
      onError?.(err.payload?.message || err.message || "Impossible de charger la recompense");
    } finally {
      setLoading(false);
    }
  }, [templateId, onError]);

  useEffect(() => {
    loadReward();
  }, [loadReward]);

  async function handleSubmit(event) {
    event.preventDefault();
    onError?.("");
    onMessage?.("");

    const xp = Number(form.xp);
    const gold = Number(form.gold);
    if (Number.isNaN(xp) || xp < 0 || Number.isNaN(gold) || gold < 0) {
      onError?.("XP et or doivent etre des entiers positifs ou nuls.");
      return;
    }

    const payload = { xp, gold };
    if (form.itemId !== "") {
      const itemId = Number(form.itemId);
      if (Number.isNaN(itemId) || itemId <= 0) {
        onError?.("Item invalide.");
        return;
      }
      payload.itemId = itemId;
    }

    setSaving(true);
    try {
      const result = await upsertQuestReward(templateId, payload);
      onMessage?.(result.message || "Recompense enregistree");
      setHasReward(true);
      await loadReward();
    } catch (err) {
      const detail = err.payload?.errors
        ? Object.values(err.payload.errors).join(" — ")
        : "";
      onError?.(
        [err.payload?.message || err.message || "Enregistrement impossible", detail]
          .filter(Boolean)
          .join(" ")
      );
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!hasReward) return;
    if (!window.confirm("Supprimer la recompense composee de ce template ?")) return;
    onError?.("");
    onMessage?.("");
    try {
      const result = await deleteQuestReward(templateId);
      onMessage?.(result.message || "Recompense supprimee");
      setHasReward(false);
      setForm(EMPTY_REWARD);
      await loadReward();
    } catch (err) {
      onError?.(err.payload?.message || err.message || "Suppression impossible");
    }
  }

  return (
    <div className="surface quest-advanced-block">
      <h3 className="section-title">Recompense composee</h3>
      <p className="form-field-hint mb-1">
        Optionnel — complementaire a l&apos;XP du template (MVP). XP, or et item lootable.
      </p>

      {loading ? (
        <div className="skeleton" style={{ height: 100 }} />
      ) : (
        <form onSubmit={handleSubmit} className="form-grid">
          <div className="form-field">
            <label htmlFor="reward-xp">XP bonus</label>
            <input
              id="reward-xp"
              type="number"
              min="0"
              value={form.xp}
              onChange={(e) => setForm((prev) => ({ ...prev, xp: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="reward-gold">Or</label>
            <input
              id="reward-gold"
              type="number"
              min="0"
              value={form.gold}
              onChange={(e) => setForm((prev) => ({ ...prev, gold: e.target.value }))}
              required
            />
          </div>
          <div className="form-field form-field--wide">
            <label htmlFor="reward-item">Item (optionnel)</label>
            <select
              id="reward-item"
              value={form.itemId}
              onChange={(e) => setForm((prev) => ({ ...prev, itemId: e.target.value }))}
            >
              <option value="">Aucun item</option>
              {items.map((item) => (
                <option key={item.id} value={item.id}>
                  #{item.id} — {item.name} ({item.rarity})
                </option>
              ))}
            </select>
            {items.length === 0 && (
              <p className="form-field-hint">Aucun item admin — creez-en via l&apos;API admin.</p>
            )}
          </div>
          <div className="form-actions form-field--wide">
            <button type="submit" className="btn btn--sm" disabled={saving}>
              {hasReward ? "Enregistrer" : "Creer la recompense"}
            </button>
            {hasReward && (
              <button type="button" className="btn btn--sm btn--danger" onClick={handleDelete}>
                Supprimer
              </button>
            )}
          </div>
        </form>
      )}
    </div>
  );
}

