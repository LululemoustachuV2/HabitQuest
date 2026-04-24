import { useEffect, useState } from "react";
import { fetchUserQuests } from "../services/questService";

function formatDate(value) {
  if (!value) return "inconnu";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? "inconnu" : date.toLocaleString("fr-FR");
}

function formatDuration(seconds) {
  const safe = Math.max(0, Number(seconds || 0));
  const d = Math.floor(safe / 86400);
  const h = Math.floor((safe % 86400) / 3600);
  const m = Math.floor((safe % 3600) / 60);
  const s = safe % 60;
  if (d > 0) return `${d}j ${h}h ${m}m ${s}s`;
  return `${h}h ${m}m ${s}s`;
}

function EventCard({ event }) {
  const percent = Math.min(100, Math.max(0, event.progressPercent || 0));

  return (
    <article className="event-card">
      <div className="event-card-badge" aria-hidden="true">
        <span className="event-card-badge-label">Event</span>
        <span className="event-card-badge-value">#{event.eventId}</span>
      </div>

      <div className="event-card-body">
        <div className="inline-actions" style={{ marginBottom: 2 }}>
          <h3 className="event-card-title">Event global</h3>
          <span className="chip chip--xp">+{event.eventXpReward} XP</span>
        </div>

        <div className="event-card-meta">
          <span>
            Fin : <strong>{formatDate(event.eventEndsAt)}</strong>
          </span>
          <span>
            Temps restant : <strong>{formatDuration(event.remainingSeconds)}</strong>
          </span>
          <span>
            Progression : <strong>{event.completed}/{event.total}</strong>
          </span>
        </div>

        <div className="event-progress">
          <div
            className="xp-bar"
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow={percent}
          >
            <div className="xp-bar-fill" style={{ width: `${percent}%` }} />
          </div>
          <span className="event-progress-value">{percent}%</span>
        </div>
      </div>
    </article>
  );
}

export default function EventsPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [events, setEvents] = useState([]);

  async function loadEvents() {
    setLoading(true);
    setError("");

    try {
      const payload = await fetchUserQuests();
      const quests = payload.eventQuests || [];

      const byEvent = new Map();
      for (const quest of quests) {
        if (!quest.eventId) continue;

        if (!byEvent.has(quest.eventId)) {
          byEvent.set(quest.eventId, {
            eventId: quest.eventId,
            eventEndsAt: quest.eventEndsAt,
            eventXpReward: quest.eventXpReward || 0,
            remainingSeconds: quest.timing?.remainingSeconds ?? null,
            quests: [],
          });
        }

        byEvent.get(quest.eventId).quests.push(quest);
      }

      const normalized = [...byEvent.values()]
        .map((event) => {
          const total = event.quests.length;
          const completed = event.quests.filter((q) => q.status === "completed").length;
          return {
            ...event,
            total,
            completed,
            progressPercent: total > 0 ? Math.floor((completed / total) * 100) : 0,
          };
        })
        .sort((a, b) => {
          const aEnds = a.eventEndsAt ? new Date(a.eventEndsAt).getTime() : Number.MAX_SAFE_INTEGER;
          const bEnds = b.eventEndsAt ? new Date(b.eventEndsAt).getTime() : Number.MAX_SAFE_INTEGER;
          return aEnds - bEnds;
        });

      setEvents(normalized);
    } catch (err) {
      setError(err.payload?.message || err.message || "Impossible de charger les events");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadEvents();
  }, []);

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Mes events globaux</h2>
          <p className="page-header-sub">
            Participe aux events limites dans le temps pour decrocher de l XP bonus.
          </p>
        </div>
        <div className="page-actions">
          <button type="button" className="btn btn--sm" onClick={loadEvents}>
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

      {loading ? (
        <div className="quest-grid">
          <div className="skeleton" style={{ height: 140 }} />
          <div className="skeleton" style={{ height: 140 }} />
        </div>
      ) : events.length === 0 ? (
        <div className="empty-state">
          <div className="empty-state-icon" aria-hidden="true">⚔</div>
          <p className="empty-state-title">Aucun event en cours</p>
          <p className="mb-0">Les nouveaux events apparaitront ici des leur ouverture.</p>
        </div>
      ) : (
        <div className="quest-grid">
          {events.map((event) => (
            <EventCard key={event.eventId} event={event} />
          ))}
        </div>
      )}
    </section>
  );
}
