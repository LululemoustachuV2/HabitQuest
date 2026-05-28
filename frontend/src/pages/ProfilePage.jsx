import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import MonsterCard from "../components/MonsterCard";
import { fetchProfileData } from "../services/profileService";
import { computeMaxEquipSlots, rarityChipClass, rarityLabel } from "../utils/rarity";

const STAT_LABELS = {
  force: "Force",
  intelligence: "Intelligence",
  discipline: "Discipline",
  creativity: "Créativité",
};

function EquippedItemRow({ entry }) {
  const item = entry.item || {};
  return (
    <li className="profile-equip-row">
      <div>
        <strong>{item.name}</strong>
        <p className="quest-card-desc mb-0">
          <span className={rarityChipClass(item.rarity)}>{rarityLabel(item.rarity)}</span>
          {item.bonusEquipSlots > 0 && (
            <span className="chip chip--outline"> +{item.bonusEquipSlots} slot</span>
          )}
        </p>
      </div>
      <span className="chip chip--completed">Équipé</span>
    </li>
  );
}

export default function ProfilePage() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const loadProfile = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const profile = await fetchProfileData();
      setData(profile);
    } catch (err) {
      setData(null);
      setError(err.payload?.message || err.message || "Impossible de charger le profil");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadProfile();
  }, [loadProfile]);

  const progression = data?.progression;
  const maxSlots = data ? computeMaxEquipSlots(data.equipped) : 3;
  const unlockedAchievements = (data?.achievements || []).filter((a) => a.unlocked);

  return (
    <section className="profile-page">
      <div className="page-header">
        <div>
          <h2>Mon profil</h2>
          <p className="page-header-sub">Progression, stats, boss actif et équipement.</p>
        </div>
        <button type="button" className="btn btn--sm" onClick={loadProfile} disabled={loading}>
          Recharger
        </button>
      </div>

      {error && (
        <div className="alert alert--error">
          <span className="alert-icon">!</span>
          <span>{error}</span>
        </div>
      )}

      {loading && !data ? (
        <div className="surface">
          <div className="skeleton" style={{ height: 120 }} />
        </div>
      ) : data ? (
        <>
          {progression && (
            <div className="xp-hero">
              <div className="xp-hero-level">
                <div className="xp-hero-level-label">Niveau</div>
                <div className="xp-hero-level-value">{progression.level}</div>
              </div>
              <div className="xp-hero-body">
                <p className="xp-hero-title">Progression</p>
                <div className="xp-hero-numbers">
                  <span>
                    XP total : <strong>{progression.xp}</strong>
                  </span>
                  <span>
                    Vers niveau suivant : <strong>{progression.xpToNextLevel}</strong>
                  </span>
                </div>
                <div
                  className="xp-bar"
                  role="progressbar"
                  aria-valuemin="0"
                  aria-valuemax={progression.xpRequiredForNextLevel}
                  aria-valuenow={progression.xpIntoLevel}
                >
                  <div
                    className="xp-bar-fill"
                    style={{
                      width: `${Math.min(100, Math.max(0, progression.progressPercent || 0))}%`,
                    }}
                  />
                </div>
                <p className="xp-hero-percent">{progression.progressPercent}%</p>
              </div>
            </div>
          )}

          <div className="stats-row">
            <div className="stat-card">
              <span className="stat-card-label">Or</span>
              <span className="stat-card-value">{data.gold}</span>
              <span className="stat-card-hint">boutique à venir</span>
            </div>
            <div className="stat-card">
              <span className="stat-card-label">Série actuelle</span>
              <span className="stat-card-value">{data.currentStreak}</span>
              <span className="stat-card-hint">jours</span>
            </div>
            <div className="stat-card">
              <span className="stat-card-label">Record série</span>
              <span className="stat-card-value">{data.longestStreak}</span>
              <span className="stat-card-hint">jours</span>
            </div>
            <div className="stat-card">
              <span className="stat-card-label">Inventaire</span>
              <span className="stat-card-value">{data.inventoryCount}</span>
              <span className="stat-card-hint">items possédés</span>
            </div>
          </div>

          <div className="surface profile-stats-block">
            <h3 className="section-title">Stats RPG</h3>
            <div className="profile-stats-grid">
              {Object.entries(STAT_LABELS).map(([key, label]) => (
                <div key={key} className="stat-card">
                  <span className="stat-card-label">{label}</span>
                  <span className="stat-card-value">{data.stats[key] ?? 0}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="profile-monster-wrap">
            <h3 className="section-title">Boss actif</h3>
            <MonsterCard />
          </div>

          <div className="surface">
            <div className="profile-section-head">
              <h3 className="section-title mb-0">Équipement</h3>
              <span className="chip chip--level">
                {data.equipped.length} / {maxSlots} slots
              </span>
            </div>
            {data.equipped.length === 0 ? (
              <p className="mb-0">
                Aucun item équipé.{" "}
                <Link to="/inventory">Gérer l&apos;inventaire</Link>
              </p>
            ) : (
              <ul className="habit-list profile-equip-list">
                {data.equipped.map((entry) => (
                  <EquippedItemRow key={entry.id} entry={entry} />
                ))}
              </ul>
            )}
          </div>

          <div className="surface">
            <h3 className="section-title">
              Succès ({unlockedAchievements.length} / {data.achievements.length})
            </h3>
            {data.achievements.length === 0 ? (
              <p className="mb-0">Aucun succès configuré.</p>
            ) : (
              <ul className="habit-list profile-achievement-list">
                {data.achievements.map((achievement) => (
                  <li
                    key={achievement.id}
                    className={`habit-row ${achievement.unlocked ? "" : "profile-achievement--locked"}`}
                  >
                    <div>
                      <strong>{achievement.name}</strong>
                      <p className="quest-card-desc mb-0">{achievement.description}</p>
                    </div>
                    <span
                      className={
                        achievement.unlocked ? "chip chip--completed" : "chip chip--locked"
                      }
                    >
                      {achievement.unlocked ? "Débloqué" : "Verrouillé"}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </>
      ) : null}
    </section>
  );
}

