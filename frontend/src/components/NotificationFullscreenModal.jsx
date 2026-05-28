import { markNotificationAsRead } from "../services/notificationService";

const SEVERITY_LABELS = {
  info: "Information",
  warning: "Attention",
  urgent: "Urgent",
};

export default function NotificationFullscreenModal({ notification, onDismiss }) {
  if (!notification) {
    return null;
  }

  const severity = notification.severity || "info";

  async function handleMarkRead() {
    try {
      await markNotificationAsRead(notification.id);
      onDismiss(true);
    } catch {
      onDismiss(false);
    }
  }

  return (
    <div className="notif-fs-overlay" role="dialog" aria-modal="true" aria-labelledby="notif-fs-title">
      <div className={`notif-fs-panel notif-fs-panel--${severity}`}>
        <p className="notif-fs-kicker">{SEVERITY_LABELS[severity] || "Notification"}</p>
        <h2 id="notif-fs-title" className="notif-fs-title">
          {notification.title}
        </h2>
        <p className="notif-fs-body">{notification.body}</p>
        <div className="notif-fs-actions">
          <button type="button" className="btn" onClick={handleMarkRead}>
            Compris
          </button>
        </div>
      </div>
    </div>
  );
}

