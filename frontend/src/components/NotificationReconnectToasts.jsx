import { useEffect, useRef, useState } from "react";
import { fetchNotifications } from "../services/notificationService";

const TOAST_LIFETIME_MS = 6000;

function ToastCard({ toast, onClose }) {
  const severity = toast.severity || "info";

  return (
    <article className={`notif-toast notif-toast--${severity}`} role="status">
      <button
        type="button"
        className="notif-toast-close"
        aria-label="Fermer la notification"
        onClick={() => onClose(toast.id)}
      >
        ×
      </button>
      <h3 className="notif-toast-title">{toast.title}</h3>
      <p className="notif-toast-body">{toast.body}</p>
    </article>
  );
}

export default function NotificationReconnectToasts({ reconnectNonce }) {
  const [toasts, setToasts] = useState([]);
  const timersRef = useRef([]);

  useEffect(() => {
    if (!reconnectNonce) {
      return undefined;
    }

    let cancelled = false;

    async function loadReconnectToasts() {
      try {
        const result = await fetchNotifications();
        const pending = (result.items || []).filter(
          (item) => !item.readAt && !item.isFullscreen
        );
        if (!cancelled && pending.length > 0) {
          setToasts(pending);
        }
      } catch {
      }
    }

    loadReconnectToasts();

    return () => {
      cancelled = true;
    };
  }, [reconnectNonce]);

  useEffect(() => {
    timersRef.current.forEach(clearTimeout);
    timersRef.current = [];

    toasts.forEach((toast) => {
      const timerId = setTimeout(() => {
        setToasts((current) => current.filter((item) => item.id !== toast.id));
      }, TOAST_LIFETIME_MS);
      timersRef.current.push(timerId);
    });

    return () => {
      timersRef.current.forEach(clearTimeout);
      timersRef.current = [];
    };
  }, [toasts]);

  function dismissToast(id) {
    setToasts((current) => current.filter((item) => item.id !== id));
  }

  if (toasts.length === 0) {
    return null;
  }

  return (
    <div className="notif-toast-stack" aria-live="polite">
      {toasts.map((toast) => (
        <ToastCard key={toast.id} toast={toast} onClose={dismissToast} />
      ))}
    </div>
  );
}

