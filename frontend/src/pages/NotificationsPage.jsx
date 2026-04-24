import { useEffect, useMemo, useState } from "react";
import {
  fetchNotifications,
  markNotificationAsRead,
} from "../services/notificationService";

function formatReadAt(value) {
  if (!value) return "non lue";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? "date invalide" : date.toLocaleString("fr-FR");
}

export default function NotificationsPage() {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  async function loadNotifications() {
    setLoading(true);
    setError("");
    try {
      const result = await fetchNotifications();
      setNotifications(result.items || []);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur chargement notifications");
    } finally {
      setLoading(false);
    }
  }

  async function handleRead(notificationId) {
    setMessage("");
    setError("");
    try {
      const result = await markNotificationAsRead(notificationId);
      setMessage(result.message || "Notification marquee lue");
      await loadNotifications();
    } catch (err) {
      setError(err.payload?.message || err.message || "Action impossible");
    }
  }

  useEffect(() => {
    loadNotifications();
  }, []);

  const unreadCount = useMemo(
    () => notifications.filter((n) => !n.readAt).length,
    [notifications]
  );

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Notifications</h2>
          <p className="page-header-sub">
            {unreadCount > 0
              ? `${unreadCount} notification${unreadCount > 1 ? "s" : ""} non lue${unreadCount > 1 ? "s" : ""}`
              : "Tu es a jour, bravo."}
          </p>
        </div>
        <div className="page-actions">
          <button type="button" className="btn btn--sm" onClick={loadNotifications}>
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

      {loading ? (
        <div className="notif-list">
          <div className="skeleton" style={{ height: 70 }} />
          <div className="skeleton" style={{ height: 70 }} />
        </div>
      ) : notifications.length === 0 ? (
        <div className="empty-state">
          <div className="empty-state-icon" aria-hidden="true">🔔</div>
          <p className="empty-state-title">Aucune notification</p>
          <p className="mb-0">Tu recevras ici tes recompenses et alertes d events.</p>
        </div>
      ) : (
        <div className="notif-list">
          {notifications.map((notification) => {
            const isUnread = !notification.readAt;
            return (
              <article
                key={notification.id}
                className={`notif-card ${isUnread ? "notif-card--unread" : ""}`}
              >
                <span className={`notif-dot ${isUnread ? "" : "notif-dot--read"}`} />
                <div className="notif-body">
                  <h3 className="notif-title">
                    <span className="quest-card-id">#{notification.id}</span>
                    {notification.title}
                  </h3>
                  <p className="notif-text">{notification.body}</p>
                  <p className="notif-meta">Statut : {formatReadAt(notification.readAt)}</p>
                </div>
                {isUnread && (
                  <button
                    type="button"
                    className="btn btn--sm btn--soft"
                    onClick={() => handleRead(notification.id)}
                  >
                    Marquer comme lue
                  </button>
                )}
              </article>
            );
          })}
        </div>
      )}
    </section>
  );
}
