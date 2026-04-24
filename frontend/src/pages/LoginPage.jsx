import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { loginUser } from "../services/authService";
import { useAuth } from "../state/AuthContext";
import { readJwtPayload } from "../utils/jwt";

export default function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const { login } = useAuth();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const redirectTo = location.state?.from || "/quests";

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setLoading(true);
    try {
      const result = await loginUser({ email, password });
      const payload = readJwtPayload(result.token);
      const rolesFromToken = Array.isArray(payload?.roles) ? payload.roles : [];
      login(result.token, { email, roles: rolesFromToken });
      navigate(redirectTo, { replace: true });
    } catch (err) {
      setError(err.payload?.message || err.message || "Connexion impossible");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="auth-shell">
      <section className="auth-card">
        <div className="auth-card-brand">
          <div className="brand-mark" aria-hidden="true">HQ</div>
          <div>
            <h1 className="auth-card-title">Bon retour, aventurier</h1>
            <p className="auth-card-sub">Connecte-toi pour poursuivre tes quetes.</p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-field">
            <label htmlFor="login-email">Email</label>
            <input
              id="login-email"
              type="email"
              required
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              autoComplete="email"
            />
          </div>

          <div className="form-field">
            <label htmlFor="login-password">Mot de passe</label>
            <input
              id="login-password"
              type="password"
              required
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              autoComplete="current-password"
            />
          </div>

          {error && (
            <div className="alert alert--error">
              <span className="alert-icon">!</span>
              <span>{error}</span>
            </div>
          )}

          <button type="submit" className="btn btn--block" disabled={loading}>
            {loading ? "Connexion..." : "Se connecter"}
          </button>
        </form>

        <p className="auth-footer">
          Pas encore de compte ? <Link to="/register">Inscription</Link>
        </p>
      </section>
    </div>
  );
}
