import { useEffect, useMemo, useState } from "react";
import {
  createAdminMonster,
  deleteAdminMonster,
  fetchAdminItems,
  fetchAdminMonsters,
  updateAdminMonster,
} from "../services/adminService";

const RARITY_OPTIONS = [
  { value: "common", label: "Commun" },
  { value: "rare", label: "Rare" },
  { value: "epic", label: "Epique" },
];

const AFFINITY_OPTIONS = [
  { value: "neutral", label: "Neutre" },
  { value: "force", label: "Force" },
  { value: "intelligence", label: "Intelligence" },
  { value: "discipline", label: "Discipline" },
  { value: "creativity", label: "Créativité" },
];

const initialForm = {
  name: "",
  baseHp: 50,
  levelMin: 1,
  levelMax: 10,
  rarity: "common",
  affinityStat: "neutral",
  lootRows: [{ itemId: "", weight: 100 }],
};

function normalizeLootRows(rows) {
  if (!Array.isArray(rows) || rows.length === 0) {
    return [{ itemId: "", weight: 100 }];
  }
  return rows.map((row) => ({
    itemId: row.itemId ? String(row.itemId) : "",
    weight: row.weight ?? 100,
  }));
}

export default function AdminMonstersPage() {
  const [monsters, setMonsters] = useState([]);
  const [catalogItems, setCatalogItems] = useState([]);
  const [form, setForm] = useState(initialForm);
  const [editingId, setEditingId] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  const itemOptions = useMemo(
    () =>
      catalogItems.map((item) => ({
        id: item.id,
        label: `#${item.id} — ${item.name}`,
      })),
    [catalogItems]
  );

  async function loadData() {
    setLoading(true);
    setError("");
    try {
      const [monstersResult, itemsResult] = await Promise.all([
        fetchAdminMonsters(),
        fetchAdminItems(),
      ]);
      setMonsters(monstersResult.monsters || []);
      setCatalogItems(itemsResult.items || []);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur monstres");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadData();
  }, []);

  function toPayload() {
    const lootTable = form.lootRows
      .filter((row) => row.itemId !== "")
      .map((row) => ({
        itemId: Number(row.itemId),
        weight: Number(row.weight),
      }));

    return {
      name: form.name,
      baseHp: Number(form.baseHp),
      levelMin: Number(form.levelMin),
      levelMax: Number(form.levelMax),
      rarity: form.rarity,
      affinityStat: form.affinityStat,
      lootTable,
      imageUrl: null,
    };
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setMessage("");
    try {
      const payload = toPayload();
      if (payload.lootTable.length === 0) {
        setError("Ajoutez au moins un item dans la table de loot.");
        return;
      }
      const result = editingId
        ? await updateAdminMonster(editingId, payload)
        : await createAdminMonster(payload);
      setMessage(result.message || "Monstre enregistre");
      setForm(initialForm);
      setEditingId(null);
      await loadData();
    } catch (err) {
      setError(err.payload?.message || err.message || "Enregistrement impossible");
    }
  }

  function startEdit(monster) {
    setEditingId(monster.id);
    setForm({
      name: monster.name,
      baseHp: monster.baseHp,
      levelMin: monster.levelMin,
      levelMax: monster.levelMax,
      rarity: monster.rarity,
      affinityStat: monster.affinityStat,
      lootRows: normalizeLootRows(monster.lootTable),
    });
  }

  function cancelEdit() {
    setEditingId(null);
    setForm(initialForm);
  }

  function updateLootRow(index, field, value) {
    setForm((prev) => {
      const lootRows = [...prev.lootRows];
      lootRows[index] = { ...lootRows[index], [field]: value };
      return { ...prev, lootRows };
    });
  }

  function addLootRow() {
    setForm((prev) => ({
      ...prev,
      lootRows: [...prev.lootRows, { itemId: "", weight: 50 }],
    }));
  }

  function removeLootRow(index) {
    setForm((prev) => ({
      ...prev,
      lootRows: prev.lootRows.filter((_, i) => i !== index),
    }));
  }

  async function handleDelete(monster) {
    if (!window.confirm(`Supprimer le monstre "${monster.name}" ?`)) return;
    setError("");
    setMessage("");
    try {
      const result = await deleteAdminMonster(monster.id);
      setMessage(result.message || "Monstre supprime");
      if (editingId === monster.id) cancelEdit();
      await loadData();
    } catch (err) {
      setError(err.payload?.message || err.message || "Suppression impossible");
    }
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Admin - Monstres</h2>
          <p className="page-header-sub">Modeles de boss et loot (selection d&apos;items).</p>
        </div>
        <div className="page-actions">
          <button type="button" className="btn btn--ghost btn--sm" onClick={loadData}>
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
          {editingId ? `Modifier monstre #${editingId}` : "Creer un monstre"}
        </h3>
        <form onSubmit={handleSubmit} className="form-grid">
          <div className="form-field form-field--wide">
            <label htmlFor="monster-name">Nom</label>
            <input
              id="monster-name"
              value={form.name}
              onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="monster-hp">PV de base</label>
            <input
              id="monster-hp"
              type="number"
              min="1"
              value={form.baseHp}
              onChange={(e) => setForm((prev) => ({ ...prev, baseHp: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="monster-level-min">Niveau min</label>
            <input
              id="monster-level-min"
              type="number"
              min="1"
              value={form.levelMin}
              onChange={(e) => setForm((prev) => ({ ...prev, levelMin: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="monster-level-max">Niveau max</label>
            <input
              id="monster-level-max"
              type="number"
              min="1"
              value={form.levelMax}
              onChange={(e) => setForm((prev) => ({ ...prev, levelMax: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="monster-rarity">Rarete</label>
            <select
              id="monster-rarity"
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
            <label htmlFor="monster-affinity">Affinite</label>
            <select
              id="monster-affinity"
              value={form.affinityStat}
              onChange={(e) => setForm((prev) => ({ ...prev, affinityStat: e.target.value }))}
            >
              {AFFINITY_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>

          <div className="form-field form-field--wide">
            <label>Table de loot</label>
            <datalist id="loot-item-options">
              {itemOptions.map((opt) => (
                <option key={opt.id} value={opt.label} />
              ))}
            </datalist>
            {form.lootRows.map((row, index) => (
              <div key={index} className="loot-row">
                <input
                  list="loot-item-options"
                  placeholder="Rechercher un item (#id — nom)"
                  value={
                    row.itemId
                      ? itemOptions.find((o) => String(o.id) === String(row.itemId))?.label || row.itemId
                      : ""
                  }
                  onChange={(e) => {
                    const match = itemOptions.find((o) => o.label === e.target.value);
                    updateLootRow(index, "itemId", match ? String(match.id) : "");
                  }}
                />
                <input
                  type="number"
                  min="1"
                  className="loot-row-weight"
                  value={row.weight}
                  onChange={(e) => updateLootRow(index, "weight", e.target.value)}
                  title="Poids"
                />
                <button
                  type="button"
                  className="btn btn--sm btn--ghost"
                  onClick={() => removeLootRow(index)}
                  disabled={form.lootRows.length <= 1}
                >
                  Retirer
                </button>
              </div>
            ))}
            <button type="button" className="btn btn--sm btn--ghost mt-1" onClick={addLootRow}>
              + Ajouter un drop
            </button>
          </div>

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
        <h3 className="section-title">Liste des monstres</h3>
        {loading ? (
          <div className="skeleton" style={{ height: 80 }} />
        ) : monsters.length === 0 ? (
          <div className="empty-state">
            <p className="empty-state-title">Aucun monstre</p>
          </div>
        ) : (
          <div className="quest-grid">
            {monsters.map((monster) => (
              <article key={monster.id} className="quest-card">
                <h3 className="quest-card-title">
                  <span className="quest-card-id">#{monster.id}</span>
                  {monster.name}
                </h3>
                <div className="quest-card-meta">
                  <span className="chip">{monster.rarity}</span>
                  <span className="chip chip--level">
                    Niv. {monster.levelMin}-{monster.levelMax}
                  </span>
                  <span className="chip">{monster.affinityStat}</span>
                  <span className="chip">{monster.baseHp} PV</span>
                </div>
                <p className="quest-card-desc">
                  Loot : {(monster.lootTable || []).length} entree(s)
                </p>
                <div className="quest-card-footer">
                  <button
                    type="button"
                    className="btn btn--sm btn--ghost"
                    onClick={() => startEdit(monster)}
                  >
                    Modifier
                  </button>
                  <button
                    type="button"
                    className="btn btn--sm btn--danger"
                    onClick={() => handleDelete(monster)}
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

