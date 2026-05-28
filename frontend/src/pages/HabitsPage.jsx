import { useEffect, useState } from "react";
import MonsterCard from "../components/MonsterCard";
import { createHabit, fetchHabits, logHabit } from "../services/habitService";

export default function HabitsPage() {
  const [habits, setHabits] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [monsterRefresh, setMonsterRefresh] = useState(0);
  const [form, setForm] = useState({ name: "", xpReward: 20, goldReward: 5 });

  async function loadHabits() {
    setLoading(true);
    setError("");
    try {
      const result = await fetchHabits();
      setHabits(result.items || []);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur chargement habitudes");
    } finally {
      setLoading(false);
    }
  }

  async function handleCreate(event) {
    event.preventDefault();
    setMessage("");
    setError("");
    try {
      const result = await createHabit({
        name: form.name.trim(),
        xpReward: Number(form.xpReward) || 0,
        goldReward: Number(form.goldReward) || 0,
        isActive: true,
      });
      setMessage(result.message || "Habitude créée");
      setForm({ name: "", xpReward: 20, goldReward: 5 });
      await loadHabits();
    } catch (err) {
      setError(err.payload?.message || err.message || "Création impossible");
    }
  }

  async function handleLog(habitId) {
    setMessage("");
    setError("");
    try {
      const result = await logHabit(habitId);
      setMessage(
        `Log OK — +${result.xpEarned ?? 0} XP, +${result.goldEarned ?? 0} or${
          result.monsterDied ? " — monstre vaincu !" : ""
        }`,
      );
      setMonsterRefresh((value) => value + 1);
    } catch (err) {
      setError(err.payload?.message || err.message || "Log impossible");
    }
  }

  useEffect(() => {
    loadHabits();
  }, []);

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Mes habitudes</h2>
          <p className="page-header-sub">Crée une habitude et enregistre une exécution.</p>
        </div>
        <button type="button" className="btn btn--sm" onClick={loadHabits}>
          Recharger
        </button>
      </div>

      <MonsterCard refreshSignal={monsterRefresh} />

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

      <div className="surface mb-2">
        <h3 className="section-title">Nouvelle habitude</h3>
        <form className="form-grid" onSubmit={handleCreate}>
          <label>
            Nom
            <input
              type="text"
              required
              minLength={2}
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
            />
          </label>
          <label>
            XP
            <input
              type="number"
              min={0}
              value={form.xpReward}
              onChange={(e) => setForm({ ...form, xpReward: e.target.value })}
            />
          </label>
          <label>
            Or
            <input
              type="number"
              min={0}
              value={form.goldReward}
              onChange={(e) => setForm({ ...form, goldReward: e.target.value })}
            />
          </label>
          <div className="form-actions">
            <button type="submit" className="btn btn--accent">
              Créer
            </button>
          </div>
        </form>
      </div>

      <div className="surface">
        <h3 className="section-title">Liste</h3>
        {loading ? (
          <div className="skeleton" style={{ height: 80 }} />
        ) : habits.length === 0 ? (
          <p className="mb-0">Aucune habitude. Lance le seed post-MVP ou crée-en une.</p>
        ) : (
          <ul className="habit-list">
            {habits.map((habit) => (
              <li key={habit.id} className="habit-row">
                <div>
                  <strong>{habit.name}</strong>
                  <p className="quest-card-desc mb-0">
                    +{habit.xpReward} XP · +{habit.goldReward} or
                    {habit.category?.name ? ` · ${habit.category.name}` : ""}
                  </p>
                </div>
                <button
                  type="button"
                  className="btn btn--accent btn--sm"
                  disabled={!habit.isActive}
                  onClick={() => handleLog(habit.id)}
                >
                  Logger
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </section>
  );
}

