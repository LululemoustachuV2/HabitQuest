import { useCallback, useEffect, useMemo, useState } from "react";
import {
  createQuestCondition,
  deleteQuestCondition,
  fetchQuestConditions,
  updateQuestCondition,
} from "../services/adminService";

const CONDITION_KINDS = {
  quests_validated_count: {
    label: "Quetes validees",
    summary: (p) => `${p.count} quete(s) validee(s)`,
    fields: [{ key: "count", label: "Nombre requis", type: "number", required: true, min: 1 }],
  },
  xp_gained: {
    label: "XP gagnes",
    summary: (p) => `${p.amount} XP cumules`,
    fields: [{ key: "amount", label: "Montant XP", type: "number", required: true, min: 1 }],
  },
  gold_gained: {
    label: "Or gagne",
    summary: (p) => `${p.amount} or cumule`,
    fields: [{ key: "amount", label: "Montant or", type: "number", required: true, min: 1 }],
  },
  streak_days: {
    label: "Serie de jours (streak joueur)",
    summary: (p) => `${p.days} jour(s) consecutifs`,
    fields: [{ key: "days", label: "Jours consecutifs", type: "number", required: true, min: 1 }],
  },
};

const EMPTY_DRAFT = { kind: "quests_validated_count", params: { count: 1 } };

function buildParamsFromDraft(kind, rawParams) {
  const config = CONDITION_KINDS[kind];
  if (!config) return {};

  const params = {};
  for (const field of config.fields) {
    const value = rawParams[field.key];
    if (value === "" || value === undefined || value === null) {
      if (field.required) {
        return null;
      }
      continue;
    }
    const num = Number(value);
    if (Number.isNaN(num) || num <= 0) {
      return null;
    }
    params[field.key] = num;
  }
  return params;
}

function paramsToDraft(kind, params) {
  const draft = {};
  const config = CONDITION_KINDS[kind];
  if (!config) return draft;
  for (const field of config.fields) {
    if (params[field.key] !== undefined) {
      draft[field.key] = params[field.key];
    }
  }
  return draft;
}

export default function QuestConditionEditor({ templateId, onError, onMessage }) {
  const [conditions, setConditions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [draft, setDraft] = useState(EMPTY_DRAFT);
  const [editingId, setEditingId] = useState(null);

  const kindConfig = useMemo(
    () => CONDITION_KINDS[draft.kind] || CONDITION_KINDS.quests_validated_count,
    [draft.kind]
  );

  const loadConditions = useCallback(async () => {
    setLoading(true);
    onError?.("");
    try {
      const condResult = await fetchQuestConditions(templateId);
      setConditions(condResult.items || []);
    } catch (err) {
      onError?.(err.payload?.message || err.message || "Impossible de charger les conditions");
    } finally {
      setLoading(false);
    }
  }, [templateId, onError]);

  useEffect(() => {
    setDraft(EMPTY_DRAFT);
    setEditingId(null);
    loadConditions();
  }, [loadConditions]);

  function handleKindChange(kind) {
    const defaults = {};
    for (const field of CONDITION_KINDS[kind]?.fields || []) {
      if (field.type === "number") {
        defaults[field.key] = field.key === "count" || field.key === "days" || field.key === "amount" ? 1 : "";
      } else {
        defaults[field.key] = "";
      }
    }
    setDraft({ kind, params: defaults });
  }

  function handleParamChange(key, value) {
    setDraft((prev) => ({
      ...prev,
      params: { ...prev.params, [key]: value },
    }));
  }

  function startEdit(condition) {
    setEditingId(condition.id);
    setDraft({
      kind: condition.kind,
      params: paramsToDraft(condition.kind, condition.params || {}),
    });
  }

  function cancelEdit() {
    setEditingId(null);
    setDraft(EMPTY_DRAFT);
  }

  async function handleSave(event) {
    event.preventDefault();
    onError?.("");
    onMessage?.("");

    const params = buildParamsFromDraft(draft.kind, draft.params);
    if (params === null) {
      onError?.("Verifiez les parametres de la condition (entiers positifs requis).");
      return;
    }

    setSaving(true);
    try {
      const payload = { kind: draft.kind, params };
      const result = editingId
        ? await updateQuestCondition(templateId, editingId, payload)
        : await createQuestCondition(templateId, payload);
      onMessage?.(result.message || "Condition enregistree");
      cancelEdit();
      await loadConditions();
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

  async function handleDelete(condition) {
    if (!window.confirm(`Supprimer la condition #${condition.id} ?`)) return;
    onError?.("");
    onMessage?.("");
    try {
      const result = await deleteQuestCondition(templateId, condition.id);
      onMessage?.(result.message || "Condition supprimee");
      if (editingId === condition.id) cancelEdit();
      await loadConditions();
    } catch (err) {
      onError?.(err.payload?.message || err.message || "Suppression impossible");
    }
  }

  function renderField(field) {
    const value = draft.params[field.key] ?? "";

    return (
      <input
        id={`cond-${field.key}`}
        type="number"
        min={field.min ?? 1}
        value={value}
        onChange={(e) => handleParamChange(field.key, e.target.value)}
        required={field.required}
      />
    );
  }

  return (
    <div className="surface quest-advanced-block">
      <h3 className="section-title">Conditions avancees</h3>
      <p className="form-field-hint mb-1">
        Optionnel — une quete peut rester sans condition (validation MVP manuelle).
      </p>

      {loading ? (
        <div className="skeleton" style={{ height: 72 }} />
      ) : (
        <>
          {conditions.length === 0 ? (
            <p className="muted mb-1">Aucune condition configuree.</p>
          ) : (
            <ul className="condition-list mb-1">
              {conditions.map((condition) => {
                const meta = CONDITION_KINDS[condition.kind];
                const summary = meta
                  ? meta.summary(condition.params || {})
                  : JSON.stringify(condition.params);
                return (
                  <li key={condition.id} className="condition-row">
                    <div className="condition-row-main">
                      <span className="chip chip--outline">{meta?.label || condition.kind}</span>
                      <span className="condition-row-summary">{summary}</span>
                      <span className="quest-card-id">#{condition.id}</span>
                    </div>
                    <div className="condition-row-actions">
                      <button
                        type="button"
                        className="btn btn--sm btn--ghost"
                        onClick={() => startEdit(condition)}
                      >
                        Modifier
                      </button>
                      <button
                        type="button"
                        className="btn btn--sm btn--danger"
                        onClick={() => handleDelete(condition)}
                      >
                        Supprimer
                      </button>
                    </div>
                  </li>
                );
              })}
            </ul>
          )}

          <form onSubmit={handleSave} className="condition-form">
            <h4 className="subsection-title">
              {editingId ? `Modifier la condition #${editingId}` : "Ajouter une condition"}
            </h4>
            <div className="form-grid">
              <div className="form-field">
                <label htmlFor="cond-kind">Type</label>
                <select
                  id="cond-kind"
                  value={draft.kind}
                  onChange={(e) => handleKindChange(e.target.value)}
                >
                  {Object.entries(CONDITION_KINDS).map(([value, { label }]) => (
                    <option key={value} value={value}>
                      {label}
                    </option>
                  ))}
                </select>
              </div>
              {kindConfig.fields.map((field) => (
                <div key={field.key} className="form-field">
                  <label htmlFor={`cond-${field.key}`}>{field.label}</label>
                  {renderField(field)}
                </div>
              ))}
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn--sm" disabled={saving}>
                {editingId ? "Mettre a jour" : "Ajouter"}
              </button>
              {editingId && (
                <button type="button" className="btn btn--sm btn--ghost" onClick={cancelEdit}>
                  Annuler
                </button>
              )}
            </div>
          </form>
        </>
      )}
    </div>
  );
}

