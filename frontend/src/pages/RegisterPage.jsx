import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { registerUser } from "../services/authService";

export default function RegisterPage() {
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();
    setError("");
    setMessage("");
    setLoading(true);

    try {
      const result = await registerUser({ email, password });
      setMessage(result.message || "Compte cree");
      setTimeout(() => navigate("/login"), 700);
    } catch (err) {
      setError(err.payload?.message || err.message || "Inscription impossible");
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
            <h1 className="auth-card-title">Rejoins la guilde</h1>
            <p className="auth-card-sub">Cree ton compte et debloque tes premieres quetes.</p>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="auth-form">
          <div className="form-field">
            <label htmlFor="register-email">Email</label>
            <input
              id="register-email"
              type="email"
              required
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              autoComplete="email"
            />
          </div>

          <div className="form-field">
            <label htmlFor="register-password">Mot de passe</label>
            <input
              id="register-password"
              type="password"
              required
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              autoComplete="new-password"
            />
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

          <button type="submit" className="btn btn--block" disabled={loading}>
            {loading ? "Creation..." : "Creer mon compte"}
          </button>
        </form>

        <p className="auth-footer">
          Deja un compte ? <Link to="/login">Connexion</Link>
        </p>
      </section>
    </div>
  );
}
