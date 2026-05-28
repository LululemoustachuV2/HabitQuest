import { useEffect, useMemo, useState } from "react";
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

function EventCard({ event, nowTick }) {
  const percent = Math.min(100, Math.max(0, event.progressPercent || 0));
  const isDone = event.completed >= event.total && event.total > 0;

  return (
    <article className={`event-card ${isDone ? "event-card--done" : ""}`}>
      <div className="event-card-badge" aria-hidden="true">
        <span className="event-card-badge-label">Event</span>
        <span className="event-card-badge-value">#{event.eventId}</span>
      </div>

      <div className="event-card-body">
        <div className="inline-actions" style={{ marginBottom: 2 }}>
          <h3 className="event-card-title">Event global</h3>
          <span className="chip chip--xp">+{event.eventXpReward} XP</span>
          {isDone && <span className="chip chip--completed">Termine</span>}
        </div>

        <div className="event-card-meta">
          <span>
            Fin : <strong>{formatDate(event.eventEndsAt)}</strong>
          </span>
          <span>
            Temps restant : <strong>{formatDuration(Math.max(0, (event.remainingSeconds ?? 0) - nowTick))}</strong>
          </span>
          <span>
            Progression : <strong>{event.completed}/{event.total}</strong>
          </span>
        </div>

        <div className="event-progress">
          <div className="xp-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow={percent}>
            <div className="xp-bar-fill" style={{ width: `${percent}%` }} />
          </div>
          <span className="event-progress-value">{percent}%</span>
        </div>

        {event.quests?.length > 0 && (
          <ul className="event-quest-list">
            {event.quests.map((q) => (
              <li key={q.id} className={q.status === "completed" ? "is-done" : ""}>
                {q.title}
                {q.status === "completed" ? " ✓" : ""}
              </li>
            ))}
          </ul>
        )}
      </div>
    </article>
  );
}

export default function EventsPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [events, setEvents] = useState([]);
  const [nowTick, setNowTick] = useState(0);

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
    const interval = window.setInterval(() => setNowTick((v) => v + 1), 1000);
    return () => window.clearInterval(interval);
  }, []);

  const { activeEvents, completedEvents } = useMemo(() => {
    const active = [];
    const done = [];
    for (const event of events) {
      const allDone = event.total > 0 && event.completed >= event.total;
      if (allDone) {
        done.push(event);
      } else {
        active.push(event);
      }
    }
    return { activeEvents: active, completedEvents: done };
  }, [events]);

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Mes events globaux</h2>
          <p className="page-header-sub">
            Les quêtes d&apos;event sont aussi visibles depuis Mes quêtes.
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
        </div>
      ) : events.length === 0 ? (
        <div className="empty-state">
          <div className="empty-state-icon" aria-hidden="true">⚔</div>
          <p className="empty-state-title">Aucun event</p>
          <p className="mb-0">Les events apparaitront ici des leur ouverture.</p>
        </div>
      ) : (
        <>
          <h3 className="section-title">En cours</h3>
          {activeEvents.length === 0 ? (
            <p className="quest-card-desc">Aucun event actif pour le moment.</p>
          ) : (
            <div className="quest-grid mb-2">
              {activeEvents.map((event) => (
                <EventCard key={event.eventId} event={event} nowTick={nowTick} />
              ))}
            </div>
          )}

          {completedEvents.length > 0 && (
            <details className="collapsible-section" open={false}>
              <summary className="collapsible-section-summary">
                Events termines ({completedEvents.length})
              </summary>
              <div className="quest-grid mt-2">
                {completedEvents.map((event) => (
                  <EventCard key={event.eventId} event={event} nowTick={nowTick} />
                ))}
              </div>
            </details>
          )}
        </>
      )}
    </section>
  );
}

