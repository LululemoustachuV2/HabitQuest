import { useEffect, useState } from "react";
import {
  createAdminItem,
  deleteAdminItem,
  fetchAdminItems,
  updateAdminItem,
} from "../services/adminService";

const RARITY_OPTIONS = [
  { value: "common", label: "Commun" },
  { value: "rare", label: "Rare" },
  { value: "epic", label: "Epique" },
];

const STAT_OPTIONS = [
  { value: "", label: "Aucune" },
  { value: "force", label: "Force" },
  { value: "intelligence", label: "Intelligence" },
  { value: "discipline", label: "Discipline" },
  { value: "creativity", label: "Créativité" },
];

const initialForm = {
  name: "",
  description: "",
  rarity: "common",
  bonusXpPercent: 0,
  bonusGold: 0,
  bonusStat: "",
  bonusStatValue: 0,
  bonusEquipSlots: 0,
  isSellable: false,
  shopPrice: "",
};

export default function AdminItemsPage() {
  const [items, setItems] = useState([]);
  const [form, setForm] = useState(initialForm);
  const [editingId, setEditingId] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  async function loadItems() {
    setLoading(true);
    setError("");
    try {
      const result = await fetchAdminItems();
      setItems(result.items || []);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur items");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadItems();
  }, []);

  function toPayload() {
    return {
      ...form,
      bonusXpPercent: Number(form.bonusXpPercent),
      bonusGold: Number(form.bonusGold),
      bonusStatValue: Number(form.bonusStatValue),
      bonusStat: form.bonusStat || null,
      bonusEquipSlots: Number(form.bonusEquipSlots),
      isSellable: Boolean(form.isSellable),
      shopPrice: form.isSellable ? Number(form.shopPrice) : null,
    };
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setMessage("");
    try {
      const payload = toPayload();
      const result = editingId
        ? await updateAdminItem(editingId, payload)
        : await createAdminItem(payload);
      setMessage(result.message || "Item enregistre");
      setForm(initialForm);
      setEditingId(null);
      await loadItems();
    } catch (err) {
      setError(err.payload?.message || err.message || "Enregistrement impossible");
    }
  }

  function startEdit(item) {
    setEditingId(item.id);
    setForm({
      name: item.name,
      description: item.description,
      rarity: item.rarity,
      bonusXpPercent: item.bonusXpPercent,
      bonusGold: item.bonusGold,
      bonusStat: item.bonusStat || "",
      bonusStatValue: item.bonusStatValue,
      bonusEquipSlots: item.bonusEquipSlots ?? 0,
      isSellable: Boolean(item.isSellable),
      shopPrice: item.shopPrice ?? "",
    });
  }

  function cancelEdit() {
    setEditingId(null);
    setForm(initialForm);
  }

  async function handleDelete(item) {
    if (!window.confirm(`Supprimer l item "${item.name}" ?`)) return;
    setError("");
    setMessage("");
    try {
      const result = await deleteAdminItem(item.id);
      setMessage(result.message || "Item supprime");
      if (editingId === item.id) cancelEdit();
      await loadItems();
    } catch (err) {
      setError(err.payload?.message || err.message || "Suppression impossible");
    }
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Admin - Items</h2>
          <p className="page-header-sub">Catalogue d equipement et bonus passifs.</p>
        </div>
        <div className="page-actions">
          <button type="button" className="btn btn--ghost btn--sm" onClick={loadItems}>
            Recharger
          </button>
        </div>
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
        <h3 className="section-title">
          {editingId ? `Modifier item #${editingId}` : "Creer un item"}
        </h3>
        <form onSubmit={handleSubmit} className="form-grid">
          <div className="form-field form-field--wide">
            <label htmlFor="item-name">Nom</label>
            <input
              id="item-name"
              value={form.name}
              onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
              required
            />
          </div>
          <div className="form-field form-field--wide">
            <label htmlFor="item-desc">Description</label>
            <textarea
              id="item-desc"
              value={form.description}
              onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="item-rarity">Rarete</label>
            <select
              id="item-rarity"
              value={form.rarity}
              onChange={(e) => setForm((prev) => ({ ...prev, rarity: e.target.value }))}
            >
              {RARITY_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>
          <div className="form-field">
            <label htmlFor="item-bonus-xp">Bonus XP %</label>
            <input
              id="item-bonus-xp"
              type="number"
              min="0"
              max="100"
              value={form.bonusXpPercent}
              onChange={(e) => setForm((prev) => ({ ...prev, bonusXpPercent: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="item-bonus-gold">Bonus gold</label>
            <input
              id="item-bonus-gold"
              type="number"
              min="0"
              value={form.bonusGold}
              onChange={(e) => setForm((prev) => ({ ...prev, bonusGold: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="item-bonus-stat">Bonus stat</label>
            <select
              id="item-bonus-stat"
              value={form.bonusStat}
              onChange={(e) => setForm((prev) => ({ ...prev, bonusStat: e.target.value }))}
            >
              {STAT_OPTIONS.map((opt) => (
                <option key={opt.value || "none"} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>
          <div className="form-field">
            <label htmlFor="item-bonus-stat-val">Valeur bonus stat</label>
            <input
              id="item-bonus-stat-val"
              type="number"
              min="0"
              value={form.bonusStatValue}
              onChange={(e) => setForm((prev) => ({ ...prev, bonusStatValue: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="item-bonus-slots">Bonus slots équipement</label>
            <input
              id="item-bonus-slots"
              type="number"
              min="0"
              value={form.bonusEquipSlots}
              onChange={(e) => setForm((prev) => ({ ...prev, bonusEquipSlots: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="item-sellable">
              <input
                id="item-sellable"
                type="checkbox"
                checked={form.isSellable}
                onChange={(e) =>
                  setForm((prev) => ({
                    ...prev,
                    isSellable: e.target.checked,
                    shopPrice: e.target.checked ? prev.shopPrice : "",
                  }))
                }
              />{" "}
              Vendable en boutique
            </label>
          </div>
          {form.isSellable && (
            <div className="form-field">
              <label htmlFor="item-shop-price">Prix boutique (or)</label>
              <input
                id="item-shop-price"
                type="number"
                min="1"
                value={form.shopPrice}
                onChange={(e) => setForm((prev) => ({ ...prev, shopPrice: e.target.value }))}
                required
              />
            </div>
          )}
          <div className="form-actions form-field--wide">
            <button type="submit" className="btn">
              {editingId ? "Enregistrer" : "Creer"}
            </button>
            {editingId && (
              <button type="button" className="btn btn--ghost" onClick={cancelEdit}>
                Annuler
              </button>
            )}
          </div>
        </form>
      </div>

      <div className="surface">
        <h3 className="section-title">Liste des items</h3>
        {loading ? (
          <div className="skeleton" style={{ height: 80 }} />
        ) : items.length === 0 ? (
          <div className="empty-state">
            <p className="empty-state-title">Aucun item</p>
          </div>
        ) : (
          <div className="quest-grid">
            {items.map((item) => (
              <article key={item.id} className="quest-card">
                <h3 className="quest-card-title">
                  <span className="quest-card-id">#{item.id}</span>
                  {item.name}
                </h3>
                <div className="quest-card-meta">
                  <span className="chip">{item.rarity}</span>
                  {item.isSellable && (
                    <span className="chip chip--xp">Boutique {item.shopPrice} or</span>
                  )}
                  {item.bonusXpPercent > 0 && (
                    <span className="chip chip--xp">+{item.bonusXpPercent}% XP</span>
                  )}
                </div>
                {item.description && <p className="quest-card-desc">{item.description}</p>}
                <div className="quest-card-footer">
                  <button
                    type="button"
                    className="btn btn--sm btn--ghost"
                    onClick={() => startEdit(item)}
                  >
                    Modifier
                  </button>
                  <button
                    type="button"
                    className="btn btn--sm btn--danger"
                    onClick={() => handleDelete(item)}
                  >
                    Supprimer
                  </button>
                </div>
              </article>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}

