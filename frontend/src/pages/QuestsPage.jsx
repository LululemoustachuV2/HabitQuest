import { useEffect, useMemo, useState } from "react";
import {
  fetchMyProgression,
  fetchUserQuests,
  validateQuest,
} from "../services/questService";

const KIND_LABELS = {
  daily: "Quotidienne",
  weekly: "Hebdomadaire",
  progression: "Progression",
  event: "Event",
};

const TABS = [
  { id: "daily", label: "Quotidiennes" },
  { id: "weekly", label: "Hebdomadaires" },
  { id: "progression", label: "Progression" },
  { id: "eventGlobal", label: "Events globaux" },
  { id: "completed", label: "Completees" },
  { id: "expired", label: "Expirees" },
];

function formatRemaining(seconds) {
  if (seconds === null || seconds === undefined) return null;
  const safe = Math.max(0, seconds);
  const days = Math.floor(safe / 86400);
  const hours = Math.floor((safe % 86400) / 3600);
  const minutes = Math.floor((safe % 3600) / 60);
  const secs = safe % 60;
  if (days > 0) return `${days}j ${hours}h ${minutes}m ${secs}s`;
  return `${hours}h ${minutes}m ${secs}s`;
}

function QuestCard({ quest, nowTick, onValidate }) {
  const remaining =
    quest.timing?.remainingSeconds !== null && quest.timing?.remainingSeconds !== undefined
      ? Math.max(0, quest.timing.remainingSeconds - nowTick)
      : null;
  const remainingLabel = formatRemaining(remaining);

  const isCompleted = quest.status === "completed";
  const isExpired = quest.status === "expired";
  const isLocked = quest.isUnlocked === false;
  const cardClass = [
    "quest-card",
    isCompleted ? "quest-card--completed" : "",
    isExpired ? "quest-card--expired" : "",
    isLocked ? "quest-card--locked" : "",
  ]
    .filter(Boolean)
    .join(" ");

  return (
    <article className={cardClass}>
      <div className="quest-card-top">
        <h3 className="quest-card-title">
          <span className="quest-card-id">#{quest.id}</span>
          {quest.title}
        </h3>
      </div>

      <div className="quest-card-meta">
        <span className={`chip chip--${quest.kind}`}>
          <span className="chip-dot" />
          {KIND_LABELS[quest.kind] || quest.kind}
        </span>

        {quest.xpReward > 0 && (
          <span className="chip chip--xp" title="XP recompense">
            +{quest.xpReward} XP
          </span>
        )}

        {quest.requiredLevel > 1 && (
          <span className={`chip ${isLocked ? "chip--locked" : "chip--level"}`}>
            Niveau {quest.requiredLevel}
            {isLocked ? " - verrouille" : ""}
          </span>
        )}

        {isCompleted && <span className="chip chip--completed">Completee</span>}
        {isExpired && <span className="chip chip--expired">Expiree</span>}
      </div>

      {quest.description && <p className="quest-card-desc">{quest.description}</p>}

      {quest.isEvent && (
        <p className="quest-card-desc">
          Reward event : <strong>+{quest.eventXpReward ?? 0} XP</strong> (attribuee a la fin si event
          complete)
        </p>
      )}

      {remainingLabel !== null && (
        <span className="quest-card-timer">
          <span className="quest-card-timer-icon" aria-hidden="true">
            {/* horloge minimaliste */}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 7v5l3 2" />
            </svg>
          </span>
          {remainingLabel}
        </span>
      )}

      {quest.status === "in_progress" && (
        <div className="quest-card-footer">
          <button
            type="button"
            className="btn btn--accent btn--sm"
            onClick={() => onValidate(quest.id)}
            disabled={isLocked}
          >
            {isLocked ? "Niveau insuffisant" : "Valider la quete"}
          </button>
        </div>
      )}
    </article>
  );
}

function EmptyQuests({ label }) {
  return (
    <div className="empty-state">
      <div className="empty-state-icon" aria-hidden="true">★</div>
      <p className="empty-state-title">Aucune quete {label.toLowerCase()}</p>
      <p className="mb-0">Les nouvelles quetes apparaitront ici automatiquement.</p>
    </div>
  );
}

export default function QuestsPage() {
  const [data, setData] = useState({
    active: [],
    completed: [],
    expired: [],
    activeByKind: { daily: [], weekly: [], progression: [], event: [] },
    eventQuests: [],
  });
  const [progression, setProgression] = useState(null);
  const [nowTick, setNowTick] = useState(0);
  const [activeTab, setActiveTab] = useState("daily");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  async function loadProgression() {
    try {
      const result = await fetchMyProgression();
      setProgression(result);
    } catch (_error) {
      // UI reste fonctionnelle meme sans endpoint progression.
    }
  }

  async function loadQuests() {
    setLoading(true);
    setError("");
    try {
      const result = await fetchUserQuests();
      setData({
        active: result.active || [],
        completed: result.completed || [],
        expired: result.expired || [],
        activeByKind: result.activeByKind || {
          daily: [],
          weekly: [],
          progression: [],
          event: [],
        },
        eventQuests: result.eventQuests || [],
      });
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur chargement quetes");
    } finally {
      setLoading(false);
    }
  }

  async function handleValidate(id) {
    setMessage("");
    setError("");
    try {
      const result = await validateQuest(id, "Validation front React MVP");
      setMessage(result.message || "Quete validee");
      await loadQuests();
      await loadProgression();
    } catch (err) {
      setError(err.payload?.message || err.message || "Validation impossible");
    }
  }

  useEffect(() => {
    loadQuests();
    loadProgression();

    const interval = window.setInterval(() => {
      setNowTick((value) => value + 1);
    }, 1000);

    return () => window.clearInterval(interval);
  }, []);

  const tabData = useMemo(
    () => ({
      daily: data.activeByKind.daily || [],
      weekly: data.activeByKind.weekly || [],
      progression: data.activeByKind.progression || [],
      eventGlobal: data.eventQuests || [],
      completed: data.completed || [],
      expired: data.expired || [],
    }),
    [data]
  );

  const currentQuests = tabData[activeTab] || [];
  const activeTabMeta = TABS.find((tab) => tab.id === activeTab);

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Mes quetes</h2>
          <p className="page-header-sub">
            Pilote tes quetes quotidiennes, hebdomadaires et globales.
          </p>
        </div>
        <div className="page-actions">
          <button type="button" className="btn btn--ghost btn--sm" onClick={loadProgression}>
            Recharger progression
          </button>
          <button type="button" className="btn btn--sm" onClick={loadQuests}>
            Recharger
          </button>
        </div>
      </div>

      {progression && (
        <div className="xp-hero">
          <div className="xp-hero-level">
            <div className="xp-hero-level-label">Niveau</div>
            <div className="xp-hero-level-value">{progression.level}</div>
          </div>
          <div className="xp-hero-body">
            <p className="xp-hero-title">Progression de l aventurier</p>
            <div className="xp-hero-numbers">
              <span>
                XP total : <strong>{progression.xp}</strong>
              </span>
              <span>
                Vers niveau suivant : <strong>{progression.xpToNextLevel}</strong>
              </span>
            </div>
            <div
              className="xp-bar"
              role="progressbar"
              aria-valuemin="0"
              aria-valuemax={progression.xpRequiredForNextLevel}
              aria-valuenow={progression.xpIntoLevel}
            >
              <div
                className="xp-bar-fill"
                style={{
                  width: `${Math.min(100, Math.max(0, progression.progressPercent || 0))}%`,
                }}
              />
            </div>
            <p className="xp-hero-percent">{progression.progressPercent}%</p>
          </div>
        </div>
      )}

      <div className="stats-row">
        <div className="stat-card">
          <span className="stat-card-label">En cours</span>
          <span className="stat-card-value">{data.active.length}</span>
          <span className="stat-card-hint">quetes actives</span>
        </div>
        <div className="stat-card">
          <span className="stat-card-label">Completees</span>
          <span className="stat-card-value">{data.completed.length}</span>
          <span className="stat-card-hint">historique</span>
        </div>
        <div className="stat-card">
          <span className="stat-card-label">Events</span>
          <span className="stat-card-value">{(data.eventQuests || []).length}</span>
          <span className="stat-card-hint">quetes d event</span>
        </div>
        <div className="stat-card">
          <span className="stat-card-label">Expirees</span>
          <span className="stat-card-value">{data.expired.length}</span>
          <span className="stat-card-hint">manquees</span>
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
        <div className="page-header mb-2" style={{ marginBottom: 14 }}>
          <h3 className="section-title" style={{ marginBottom: 0 }}>
            Liste des quetes
          </h3>
        </div>

        <div className="tabs" role="tablist" aria-label="Filtrer les quetes">
          {TABS.map((tab) => {
            const count = (tabData[tab.id] || []).length;
            return (
              <button
                key={tab.id}
                type="button"
                role="tab"
                aria-selected={activeTab === tab.id}
                className={`tab ${activeTab === tab.id ? "is-active" : ""}`}
                onClick={() => setActiveTab(tab.id)}
              >
                {tab.label}
                <span className="tab-count">{count}</span>
              </button>
            );
          })}
        </div>

        <div className="mt-2">
          {loading ? (
            <div className="quest-grid">
              <div className="skeleton" style={{ height: 140 }} />
              <div className="skeleton" style={{ height: 140 }} />
              <div className="skeleton" style={{ height: 140 }} />
            </div>
          ) : currentQuests.length === 0 ? (
            <EmptyQuests label={activeTabMeta?.label || ""} />
          ) : (
            <div className="quest-grid">
              {currentQuests.map((quest) => (
                <QuestCard
                  key={quest.id}
                  quest={quest}
                  nowTick={nowTick}
                  onValidate={handleValidate}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
