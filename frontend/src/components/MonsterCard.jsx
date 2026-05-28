import { useCallback, useEffect, useState } from "react";
import { fetchActiveMonster } from "../services/monsterService";

const AFFINITY_LABELS = {
  force: "Force",
  intelligence: "Intelligence",
  discipline: "Discipline",
  creativity: "Créativité",
};

function computeHpPercent(currentHp, maxHp) {
  if (!maxHp || maxHp <= 0) return 0;
  const ratio = (currentHp / maxHp) * 100;
  return Math.min(100, Math.max(0, Math.round(ratio)));
}

export default function MonsterCard({ refreshSignal = 0, className = "" }) {
  const [monster, setMonster] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const loadMonster = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const data = await fetchActiveMonster();
      setMonster(data);
    } catch (err) {
      setMonster(null);
      setError(err.payload?.message || err.message || "Impossible de charger le monstre");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadMonster();
  }, [loadMonster, refreshSignal]);

  const rootClass = ["monster-card", className].filter(Boolean).join(" ");

  if (loading) {
    return (
      <div className={rootClass} aria-busy="true" aria-label="Chargement du monstre actif">
        <div className="monster-card-skeleton">
          <div className="skeleton" style={{ height: 22, width: "45%" }} />
          <div className="skeleton" style={{ height: 14, width: "30%", marginTop: 10 }} />
          <div className="skeleton" style={{ height: 12, width: "100%", marginTop: 16 }} />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className={rootClass} role="alert">
        <p className="monster-card-error-title">Monstre actif</p>
        <p className="monster-card-error">{error}</p>
        <button type="button" className="btn btn--ghost btn--sm" onClick={loadMonster}>
          Réessayer
        </button>
      </div>
    );
  }

  if (!monster) {
    return (
      <div className={rootClass}>
        <p className="monster-card-empty">Aucun monstre actif pour le moment.</p>
      </div>
    );
  }

  const hpPercent = computeHpPercent(monster.currentHp, monster.maxHp);
  const hpLabel = `${monster.currentHp} sur ${monster.maxHp} points de vie (${hpPercent} pour cent)`;
  const affinityLabel = monster.affinityStat
    ? AFFINITY_LABELS[monster.affinityStat] || monster.affinityStat
    : null;

  return (
    <article className={rootClass} aria-labelledby="monster-card-title">
      <div className="monster-card-head">
        <div>
          <h3 id="monster-card-title" className="monster-card-name">
            {monster.name}
          </h3>
          <p className="monster-card-meta">
            Niveau <strong>{monster.level}</strong>
            {affinityLabel && (
              <>
                {" "}
                · Affinité <strong>{affinityLabel}</strong>
              </>
            )}
          </p>
        </div>
        <span className="monster-card-badge" aria-hidden="true">
          Boss
        </span>
      </div>

      <div className="monster-card-hp">
        <div className="monster-card-hp-labels">
          <span>Points de vie</span>
          <span className="monster-card-hp-values">
            {monster.currentHp} / {monster.maxHp}
          </span>
        </div>
        <div
          className="hp-bar"
          role="progressbar"
          aria-valuemin={0}
          aria-valuemax={monster.maxHp}
          aria-valuenow={monster.currentHp}
          aria-label={hpLabel}
        >
          <div className="hp-bar-fill" style={{ width: `${hpPercent}%` }} />
        </div>
        <p className="monster-card-hp-percent">{hpPercent}%</p>
      </div>
    </article>
  );
}

