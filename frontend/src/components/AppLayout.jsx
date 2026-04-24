import { NavLink, Outlet } from "react-router-dom";
import { useAuth } from "../state/AuthContext";

export default function AppLayout() {
  const { user, roles, logout } = useAuth();
  const isAdmin = roles.includes("ROLE_ADMIN");

  const navClass = ({ isActive }) => (isActive ? "is-active" : "");

  return (
    <div className="app-shell">
      <header className="topbar">
        <div className="topbar-head">
          <div className="brand">
            <div className="brand-mark" aria-hidden="true">HQ</div>
            <div>
              <h1 className="brand-title">HabitQuest</h1>
              <p className="brand-sub">Transforme tes habitudes en quetes.</p>
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
          <NavLink to="/events" className={navClass}>Mes events</NavLink>
          <NavLink to="/notifications" className={navClass}>Notifications</NavLink>
          {isAdmin && (
            <>
              <NavLink to="/admin/templates" className={navClass}>Admin templates</NavLink>
              <NavLink to="/admin/events" className={navClass}>Admin events</NavLink>
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
