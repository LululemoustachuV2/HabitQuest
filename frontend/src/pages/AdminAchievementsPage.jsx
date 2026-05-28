import { useEffect, useState } from "react";
import {
  createAdminAchievement,
  deleteAdminAchievement,
  fetchAdminAchievements,
  updateAdminAchievement,
} from "../services/adminService";

const CODE_OPTIONS = [
  { value: "first_quest_validated", label: "first_quest_validated — Première quête" },
  { value: "first_monster_kill", label: "first_monster_kill — Premier boss" },
  { value: "iron_discipline", label: "iron_discipline — 7 quêtes" },
];

const initialForm = {
  code: "first_quest_validated",
  name: "",
  description: "",
};

export default function AdminAchievementsPage() {
  const [achievements, setAchievements] = useState([]);
  const [form, setForm] = useState(initialForm);
  const [editingId, setEditingId] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  async function loadAchievements() {
    setLoading(true);
    setError("");
    try {
      const result = await fetchAdminAchievements();
      setAchievements(result.items || []);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur achievements");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadAchievements();
  }, []);

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setMessage("");
    try {
      const result = editingId
        ? await updateAdminAchievement(editingId, form)
        : await createAdminAchievement(form);
      setMessage(result.message || "Achievement enregistre");
      setForm(initialForm);
      setEditingId(null);
      await loadAchievements();
    } catch (err) {
      setError(err.payload?.message || err.message || "Enregistrement impossible");
    }
  }

  function startEdit(row) {
    setEditingId(row.id);
    setForm({
      code: row.code,
      name: row.name,
      description: row.description,
    });
  }

  function cancelEdit() {
    setEditingId(null);
    setForm(initialForm);
  }

  async function handleDelete(row) {
    if (!window.confirm(`Supprimer l'achievement "${row.name}" ?`)) return;
    setError("");
    setMessage("");
    try {
      const result = await deleteAdminAchievement(row.id);
      setMessage(result.message || "Achievement supprime");
      if (editingId === row.id) cancelEdit();
      await loadAchievements();
    } catch (err) {
      setError(err.payload?.message || err.message || "Suppression impossible");
    }
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Admin - Achievements</h2>
          <p className="page-header-sub">Catalogue des succes debloquables automatiquement.</p>
        </div>
        <button type="button" className="btn btn--ghost btn--sm" onClick={loadAchievements}>
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
        <h3 className="section-title">
          {editingId ? `Modifier #${editingId}` : "Creer un achievement"}
        </h3>
        <form onSubmit={handleSubmit} className="form-grid">
          <div className="form-field">
            <label htmlFor="ach-code">Code</label>
            <select
              id="ach-code"
              value={form.code}
              onChange={(e) => setForm((prev) => ({ ...prev, code: e.target.value }))}
              disabled={Boolean(editingId)}
            >
              {CODE_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>
                  {opt.label}
                </option>
              ))}
            </select>
          </div>
          <div className="form-field form-field--wide">
            <label htmlFor="ach-name">Nom</label>
            <input
              id="ach-name"
              value={form.name}
              onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
              required
            />
          </div>
          <div className="form-field form-field--wide">
            <label htmlFor="ach-desc">Description</label>
            <textarea
              id="ach-desc"
              value={form.description}
              onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
              rows={3}
              required
            />
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
        <h3 className="section-title">Liste</h3>
        {loading ? (
          <div className="skeleton" style={{ height: 80 }} />
        ) : achievements.length === 0 ? (
          <p className="mb-0">Aucun achievement. Lancez les migrations ou creez-en un.</p>
        ) : (
          <div className="quest-grid">
            {achievements.map((row) => (
              <article key={row.id} className="quest-card">
                <h3 className="quest-card-title">{row.name}</h3>
                <div className="quest-card-meta">
                  <span className="chip chip--outline">{row.code}</span>
                </div>
                <p className="quest-card-desc">{row.description}</p>
                <div className="quest-card-footer">
                  <button
                    type="button"
                    className="btn btn--sm btn--ghost"
                    onClick={() => startEdit(row)}
                  >
                    Modifier
                  </button>
                  <button
                    type="button"
                    className="btn btn--sm btn--danger"
                    onClick={() => handleDelete(row)}
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

