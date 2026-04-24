import { useEffect, useState } from "react";
import {
  createQuestTemplate,
  deleteQuestTemplate,
  fetchTemplateDeleteImpact,
  fetchQuestTemplates,
  setQuestTemplateActive,
  updateQuestTemplate,
} from "../services/adminService";

const initialForm = {
  kind: "daily",
  title: "",
  description: "",
  xpReward: 100,
  requiredLevel: 1,
  isActive: true,
};

const KIND_LABELS = {
  daily: "Quotidienne",
  weekly: "Hebdomadaire",
  progression: "Progression",
  event: "Event",
};

export default function AdminTemplatesPage() {
  const [templates, setTemplates] = useState([]);
  const [form, setForm] = useState(initialForm);
  const [editingTemplateId, setEditingTemplateId] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  async function loadTemplates() {
    setLoading(true);
    setError("");
    try {
      const result = await fetchQuestTemplates();
      setTemplates(result.items || []);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur templates");
    } finally {
      setLoading(false);
    }
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setMessage("");
    try {
      const payload = {
        ...form,
        xpReward: Number(form.xpReward),
        requiredLevel: Number(form.requiredLevel),
      };
      const result = editingTemplateId
        ? await updateQuestTemplate(editingTemplateId, payload)
        : await createQuestTemplate(payload);
      setMessage(result.message || "Template cree");
      setForm(initialForm);
      setEditingTemplateId(null);
      await loadTemplates();
    } catch (err) {
      setError(err.payload?.message || err.message || "Creation impossible");
    }
  }

  useEffect(() => {
    loadTemplates();
  }, []);

  function startEdit(template) {
    setEditingTemplateId(template.id);
    setForm({
      kind: template.kind,
      title: template.title,
      description: template.description,
      xpReward: template.xpReward,
      requiredLevel: template.requiredLevel ?? 1,
      isActive: template.isActive,
    });
  }

  function cancelEdit() {
    setEditingTemplateId(null);
    setForm(initialForm);
  }

  async function toggleActive(template) {
    setError("");
    setMessage("");
    try {
      const result = await setQuestTemplateActive(template.id, !template.isActive);
      setMessage(result.message || "Etat mis a jour");
      await loadTemplates();
    } catch (err) {
      setError(err.payload?.message || err.message || "Mise a jour impossible");
    }
  }

  async function handleDelete(template) {
    setError("");
    setMessage("");

    try {
      const impact = await fetchTemplateDeleteImpact(template.id);
      const impactedEvents = impact.impact?.eventsToDelete || [];
      const eventLines =
        impactedEvents.length === 0
          ? "Aucun event ne sera supprime."
          : impactedEvents
              .map((event) => `- Event #${event.id} (${event.startsAt} -> ${event.endsAt})`)
              .join("\n");

      const confirmationMessage = [
        `Voulez-vous supprimer la quete "${template.title}" (id ${template.id}) ?`,
        "",
        `Impact:`,
        `- ${impact.impact?.userQuestsToDeleteCount || 0} user quest(s) supprimee(s)`,
        `- ${impact.impact?.eventsToDeleteCount || 0} event(s) supprime(s)`,
        eventLines,
      ].join("\n");

      if (!window.confirm(confirmationMessage)) return;

      const result = await deleteQuestTemplate(template.id);
      setMessage(result.message || "Template supprime");
      if (editingTemplateId === template.id) cancelEdit();
      await loadTemplates();
    } catch (err) {
      setError(err.payload?.message || err.message || "Suppression impossible");
    }
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Admin - Templates de quetes</h2>
          <p className="page-header-sub">
            Cree, modifie et active les modeles de quetes proposes aux joueurs.
          </p>
        </div>
        <div className="page-actions">
          <button type="button" className="btn btn--ghost btn--sm" onClick={loadTemplates}>
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
          {editingTemplateId ? `Modifier template #${editingTemplateId}` : "Creer un template"}
        </h3>

        <form onSubmit={handleSubmit} className="form-grid">
          <div className="form-field">
            <label htmlFor="kind">Type de quete</label>
            <select
              id="kind"
              value={form.kind}
              onChange={(e) =>
                setForm((prev) => ({
                  ...prev,
                  kind: e.target.value,
                  xpReward: e.target.value === "event" ? 0 : prev.xpReward,
                }))
              }
            >
              {Object.entries(KIND_LABELS).map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </div>

          <div className="form-field">
            <label htmlFor="xpReward">XP reward</label>
            <input
              id="xpReward"
              type="number"
              min="0"
              value={form.xpReward}
              onChange={(e) => setForm((prev) => ({ ...prev, xpReward: e.target.value }))}
              disabled={form.kind === "event"}
              required
            />
            {form.kind === "event" && (
              <p className="form-field-hint">
                Les quetes event n attribuent pas d XP direct.
              </p>
            )}
          </div>

          <div className="form-field">
            <label htmlFor="requiredLevel">Niveau requis</label>
            <input
              id="requiredLevel"
              type="number"
              min="1"
              value={form.requiredLevel}
              onChange={(e) => setForm((prev) => ({ ...prev, requiredLevel: e.target.value }))}
              required
            />
            <p className="form-field-hint">
              Surtout utile pour les quetes progression / histoire.
            </p>
          </div>

          <div className="form-field">
            <label htmlFor="isActive">Statut</label>
            <label className="form-checkbox" htmlFor="isActive">
              <input
                id="isActive"
                type="checkbox"
                checked={form.isActive}
                onChange={(e) => setForm((prev) => ({ ...prev, isActive: e.target.checked }))}
              />
              Template actif
            </label>
          </div>

          <div className="form-field form-field--wide">
            <label htmlFor="title">Titre</label>
            <input
              id="title"
              value={form.title}
              onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
              required
            />
          </div>

          <div className="form-field form-field--wide">
            <label htmlFor="description">Description</label>
            <textarea
              id="description"
              value={form.description}
              onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
              required
            />
          </div>

          <div className="form-actions form-field--wide">
            <button type="submit" className="btn">
              {editingTemplateId ? "Enregistrer" : "Creer"}
            </button>
            {editingTemplateId && (
              <button type="button" className="btn btn--ghost" onClick={cancelEdit}>
                Annuler
              </button>
            )}
          </div>
        </form>
      </div>

      <div className="surface">
        <h3 className="section-title">Liste des templates</h3>

        {loading ? (
          <div className="quest-grid">
            <div className="skeleton" style={{ height: 120 }} />
            <div className="skeleton" style={{ height: 120 }} />
            <div className="skeleton" style={{ height: 120 }} />
          </div>
        ) : templates.length === 0 ? (
          <div className="empty-state">
            <div className="empty-state-icon" aria-hidden="true">✎</div>
            <p className="empty-state-title">Aucun template</p>
            <p className="mb-0">Cree ton premier modele de quete via le formulaire ci-dessus.</p>
          </div>
        ) : (
          <div className="quest-grid">
            {templates.map((template) => (
              <article key={template.id} className="quest-card">
                <div className="quest-card-top">
                  <h3 className="quest-card-title">
                    <span className="quest-card-id">#{template.id}</span>
                    {template.title}
                  </h3>
                </div>
                <div className="quest-card-meta">
                  <span className={`chip chip--${template.kind}`}>
                    <span className="chip-dot" />
                    {KIND_LABELS[template.kind] || template.kind}
                  </span>
                  {template.xpReward > 0 && (
                    <span className="chip chip--xp">+{template.xpReward} XP</span>
                  )}
                  <span className="chip chip--level">
                    Niveau {template.requiredLevel ?? 1}
                  </span>
                  <span
                    className={`chip ${template.isActive ? "chip--completed" : "chip--locked"}`}
                  >
                    {template.isActive ? "Actif" : "Inactif"}
                  </span>
                </div>
                {template.description && (
                  <p className="quest-card-desc">{template.description}</p>
                )}
                <div className="quest-card-footer">
                  <button
                    type="button"
                    className="btn btn--sm btn--ghost"
                    onClick={() => startEdit(template)}
                  >
                    Modifier
                  </button>
                  <button
                    type="button"
                    className="btn btn--sm btn--soft"
                    onClick={() => toggleActive(template)}
                  >
                    {template.isActive ? "Desactiver" : "Activer"}
                  </button>
                  <button
                    type="button"
                    className="btn btn--sm btn--danger"
                    onClick={() => handleDelete(template)}
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
