import { useEffect, useState } from "react";
import {
  createAdminCategory,
  deleteAdminCategory,
  fetchAdminCategories,
  updateAdminCategory,
} from "../services/adminService";

const STAT_OPTIONS = [
  { value: "force", label: "Force" },
  { value: "intelligence", label: "Intelligence" },
  { value: "discipline", label: "Discipline" },
  { value: "creativity", label: "Créativité" },
];

const initialForm = { name: "", linkedStat: "force" };

export default function AdminCategoriesPage() {
  const [categories, setCategories] = useState([]);
  const [form, setForm] = useState(initialForm);
  const [editingId, setEditingId] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  async function loadCategories() {
    setLoading(true);
    setError("");
    try {
      const result = await fetchAdminCategories();
      setCategories(result.items || []);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur categories");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadCategories();
  }, []);

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setMessage("");
    try {
      const result = editingId
        ? await updateAdminCategory(editingId, form)
        : await createAdminCategory(form);
      setMessage(result.message || "Categorie enregistree");
      setForm(initialForm);
      setEditingId(null);
      await loadCategories();
    } catch (err) {
      setError(err.payload?.message || err.message || "Enregistrement impossible");
    }
  }

  function startEdit(category) {
    setEditingId(category.id);
    setForm({ name: category.name, linkedStat: category.linkedStat });
  }

  function cancelEdit() {
    setEditingId(null);
    setForm(initialForm);
  }

  async function handleDelete(category) {
    if (!window.confirm(`Supprimer la categorie "${category.name}" ?`)) return;
    setError("");
    setMessage("");
    try {
      const result = await deleteAdminCategory(category.id);
      setMessage(result.message || "Categorie supprimee");
      if (editingId === category.id) cancelEdit();
      await loadCategories();
    } catch (err) {
      setError(err.payload?.message || err.message || "Suppression impossible");
    }
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Admin - Categories</h2>
          <p className="page-header-sub">Gere les categories d habitudes et leur stat liee.</p>
        </div>
        <div className="page-actions">
          <button type="button" className="btn btn--ghost btn--sm" onClick={loadCategories}>
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
          {editingId ? `Modifier categorie #${editingId}` : "Creer une categorie"}
        </h3>
        <form onSubmit={handleSubmit} className="form-grid">
          <div className="form-field form-field--wide">
            <label htmlFor="cat-name">Nom</label>
            <input
              id="cat-name"
              value={form.name}
              onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
              required
            />
          </div>
          <div className="form-field">
            <label htmlFor="cat-stat">Stat liee</label>
            <select
              id="cat-stat"
              value={form.linkedStat}
              onChange={(e) => setForm((prev) => ({ ...prev, linkedStat: e.target.value }))}
            >
              {STAT_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
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
        <h3 className="section-title">Liste des categories</h3>
        {loading ? (
          <div className="skeleton" style={{ height: 80 }} />
        ) : categories.length === 0 ? (
          <div className="empty-state">
            <p className="empty-state-title">Aucune categorie</p>
          </div>
        ) : (
          <div className="quest-grid">
            {categories.map((category) => (
              <article key={category.id} className="quest-card">
                <h3 className="quest-card-title">
                  <span className="quest-card-id">#{category.id}</span>
                  {category.name}
                </h3>
                <div className="quest-card-meta">
                  <span className="chip chip--level">{category.linkedStat}</span>
                </div>
                <div className="quest-card-footer">
                  <button
                    type="button"
                    className="btn btn--sm btn--ghost"
                    onClick={() => startEdit(category)}
                  >
                    Modifier
                  </button>
                  <button
                    type="button"
                    className="btn btn--sm btn--danger"
                    onClick={() => handleDelete(category)}
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

