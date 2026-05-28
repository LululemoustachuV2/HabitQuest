import { NavLink, Outlet } from "react-router-dom";
import NotificationReconnectToasts from "./NotificationReconnectToasts";
import { useAuth } from "../state/AuthContext";
import { APP_NAME, APP_VERSION } from "../config/env";

export default function AppLayout() {
  const { user, roles, logout, reconnectNonce } = useAuth();
  const isAdmin = roles.includes("ROLE_ADMIN");

  const navClass = ({ isActive }) => (isActive ? "is-active" : "");

  return (
    <div className="app-shell">
      <NotificationReconnectToasts reconnectNonce={reconnectNonce} />
      <header className="topbar">
        <div className="topbar-head">
          <div className="brand">
            <div className="brand-mark" aria-hidden="true">HQ</div>
            <div>
              <h1 className="brand-title">{APP_NAME}</h1>
              <p className="brand-sub">
                v{APP_VERSION} — Accomplis tes quetes, bats les boss.
              </p>
            </div>
          </div>

          <div className="inline-actions">
            <span className="user-chip" title="Utilisateur connecte">
              <span className="user-chip-dot" />
              {user?.email || "invite"}
            </span>
            <button type="button" className="btn btn--ghost btn--sm" onClick={logout}>
              Deconnexion
            </button>
          </div>
        </div>

        <nav className="nav" aria-label="Navigation principale">
          <NavLink to="/quests" className={navClass}>Mes quetes</NavLink>
          <NavLink to="/inventory" className={navClass}>Inventaire</NavLink>
          <NavLink to="/shop" className={navClass}>Boutique</NavLink>
          <NavLink to="/profile" className={navClass}>Profil</NavLink>
          <NavLink to="/events" className={navClass}>Mes events</NavLink>
          <NavLink to="/notifications" className={navClass}>Notifications</NavLink>
          {isAdmin && (
            <>
              <NavLink to="/admin/templates" className={navClass}>Admin templates</NavLink>
              <NavLink to="/admin/events" className={navClass}>Admin events</NavLink>
              <NavLink to="/admin/items" className={navClass}>Admin items</NavLink>
              <NavLink to="/admin/monsters" className={navClass}>Admin monstres</NavLink>
              <NavLink to="/admin/achievements" className={navClass}>Admin achievements</NavLink>
            </>
          )}
        </nav>
      </header>

      <main>
        <Outlet />
      </main>
    </div>
  );
}

