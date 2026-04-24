import { useEffect, useMemo, useState } from "react";
import { createEvent, fetchQuestTemplates } from "../services/adminService";

const initialState = {
  startsAt: "",
  endsAt: "",
  eventXpReward: 0,
  questTemplateIds: [],
};

function toIsoDate(value) {
  return new Date(value).toISOString();
}

export default function AdminEventsPage() {
  const [form, setForm] = useState(initialState);
  const [templates, setTemplates] = useState([]);
  const [templateFilter, setTemplateFilter] = useState("");
  const [loadingTemplates, setLoadingTemplates] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [lastResponse, setLastResponse] = useState(null);

  const filteredTemplates = useMemo(() => {
    const normalizedFilter = templateFilter.trim().toLowerCase();
    if (!normalizedFilter) return templates;
    return templates.filter((template) =>
      `${template.title} ${template.description} ${template.kind}`
        .toLowerCase()
        .includes(normalizedFilter)
    );
  }, [templateFilter, templates]);

  const selectableTemplates = useMemo(
    () => filteredTemplates.filter((template) => template.kind === "event"),
    [filteredTemplates]
  );

  async function loadTemplates() {
    setLoadingTemplates(true);
    try {
      const result = await fetchQuestTemplates();
      setTemplates(result.items || []);
    } catch (_error) {
      // On laisse l utilisateur continuer, erreur visible au submit.
    } finally {
      setLoadingTemplates(false);
    }
  }

  useEffect(() => {
    loadTemplates();
  }, []);

  function toggleTemplate(templateId) {
    setForm((prev) => {
      const isSelected = prev.questTemplateIds.includes(templateId);
      return {
        ...prev,
        questTemplateIds: isSelected
          ? prev.questTemplateIds.filter((id) => id !== templateId)
          : [...prev.questTemplateIds, templateId],
      };
    });
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setMessage("");
    setLastResponse(null);

    try {
      const payload = {
        startsAt: toIsoDate(form.startsAt),
        endsAt: toIsoDate(form.endsAt),
        eventXpReward: Number(form.eventXpReward),
        questTemplateIds: form.questTemplateIds,
      };

      const result = await createEvent(payload);
      setMessage(result.message || "Event cree");
      setLastResponse(result);
      setForm(initialState);
    } catch (err) {
      const baseMessage = err.payload?.message || err.message || "Creation event impossible";
      const fieldErrors = err.payload?.errors;
      if (fieldErrors && typeof fieldErrors === "object") {
        const details = Object.entries(fieldErrors)
          .map(([field, msg]) => `${field}: ${msg}`)
          .join(" | ");
        setError(`${baseMessage} ${details ? `(${details})` : ""}`.trim());
      } else {
        setError(baseMessage);
      }
    }
  }

  const selectedCount = form.questTemplateIds.length;

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Admin - Events globaux</h2>
          <p className="page-header-sub">
            Organise des events limites dans le temps et attribue l XP final aux joueurs.
          </p>
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

      <form onSubmit={handleSubmit} className="surface">
        <h3 className="section-title">Configuration de l event</h3>

        <div className="form-grid">
          <div className="form-field">
            <label htmlFor="startsAt">Debut</label>
            <input
              id="startsAt"
              type="datetime-local"
              value={form.startsAt}
              onChange={(e) => setForm((prev) => ({ ...prev, startsAt: e.target.value }))}
              required
            />
          </div>

          <div className="form-field">
            <label htmlFor="endsAt">Fin</label>
            <input
              id="endsAt"
              type="datetime-local"
              value={form.endsAt}
              onChange={(e) => setForm((prev) => ({ ...prev, endsAt: e.target.value }))}
              required
            />
          </div>

          <div className="form-field">
            <label htmlFor="eventXpReward">XP total de l event</label>
            <input
              id="eventXpReward"
              type="number"
              min="0"
              value={form.eventXpReward}
              onChange={(e) => setForm((prev) => ({ ...prev, eventXpReward: e.target.value }))}
              required
            />
            <p className="form-field-hint">Attribue a la fin si l event est complete.</p>
          </div>
        </div>

        <h3 className="section-title mt-2">Selection des quetes</h3>

        <div className="inline-actions mb-1">
          <span className="chip chip--outline">
            Selectionnees : <strong>&nbsp;{selectedCount}</strong>
          </span>
          {form.questTemplateIds.length > 0 && (
            <span className="text-soft" style={{ fontSize: 12 }}>
              IDs : {form.questTemplateIds.join(", ")}
            </span>
          )}
        </div>

        <div className="form-field form-field--wide">
          <label htmlFor="templateFilter">Rechercher une quete</label>
          <input
            id="templateFilter"
            value={templateFilter}
            onChange={(e) => setTemplateFilter(e.target.value)}
            placeholder="titre, kind, description..."
          />
        </div>

        {loadingTemplates ? (
          <div className="skeleton mt-2" style={{ height: 80 }} />
        ) : (
          <div className="template-selection-list mt-1">
            {selectableTemplates.map((template) => {
              const checked = form.questTemplateIds.includes(template.id);
              return (
                <label key={template.id} className="template-option">
                  <input
                    type="checkbox"
                    checked={checked}
                    onChange={() => toggleTemplate(template.id)}
                  />
                  <span className="quest-card-id">#{template.id}</span>
                  <span>{template.title}</span>
                  <span className="template-option-meta">
                    {template.isActive ? (
                      <span className="chip chip--completed">Actif</span>
                    ) : (
                      <span className="chip chip--locked">Inactif</span>
                    )}
                  </span>
                </label>
              );
            })}
            {selectableTemplates.length === 0 && (
              <p className="muted mb-0">Aucune quete selectable trouvee.</p>
            )}
          </div>
        )}
        <p className="form-field-hint mt-1">
          Seules les quetes de type <strong>event</strong> sont selectionnables.
        </p>

        <div className="form-actions mt-2">
          <button type="submit" className="btn" disabled={selectedCount === 0}>
            Creer l event
          </button>
        </div>
      </form>

      {lastResponse && (
        <div className="surface">
          <h3 className="section-title">Derniere reponse</h3>
          <pre
            style={{
              margin: 0,
              padding: 12,
              background: "var(--surface-2)",
              borderRadius: 10,
              border: "1px solid var(--border)",
              fontSize: 12,
              overflow: "auto",
              maxHeight: 260,
            }}
          >
            {JSON.stringify(lastResponse, null, 2)}
          </pre>
        </div>
      )}
    </section>
  );
}
