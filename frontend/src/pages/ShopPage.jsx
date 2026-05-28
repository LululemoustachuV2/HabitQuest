import { useEffect, useState } from "react";
import { fetchMyProgression } from "../services/questService";
import { fetchShop, purchaseShopItem } from "../services/shopService";
import { rarityChipClass, rarityLabel } from "../utils/rarity";

export default function ShopPage() {
  const [items, setItems] = useState([]);
  const [rotationDate, setRotationDate] = useState("");
  const [gold, setGold] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [buyingId, setBuyingId] = useState(null);

  async function loadShop() {
    setLoading(true);
    setError("");
    try {
      const [shop, progression] = await Promise.all([fetchShop(), fetchMyProgression()]);
      setItems(shop.items || []);
      setRotationDate(shop.rotationDate || "");
      setGold(progression.gold ?? 0);
    } catch (err) {
      setError(err.payload?.message || err.message || "Erreur chargement boutique");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadShop();
  }, []);

  async function handleBuy(item) {
    setBuyingId(item.id);
    setMessage("");
    setError("");
    try {
      const result = await purchaseShopItem(item.id);
      setMessage(result.message || "Achat effectué");
      if (typeof result.gold === "number") {
        setGold(result.gold);
      }
      setItems((prev) =>
        prev.map((row) =>
          row.id === item.id ? { ...row, purchased: true } : row
        )
      );
    } catch (err) {
      setError(err.payload?.message || err.message || "Achat impossible");
    } finally {
      setBuyingId(null);
    }
  }

  const canAfford = (price) => gold !== null && gold >= price;

  return (
    <section>
      <div className="page-header">
        <div>
          <h2>Boutique</h2>
          <p className="page-header-sub">
            Rotation du jour{rotationDate ? ` (${rotationDate})` : ""} — achète des objets avec ton or.
          </p>
        </div>
        <button type="button" className="btn btn--sm" onClick={loadShop}>
          Recharger
        </button>
      </div>

      <div className="stat-card mb-4">
        <span className="stat-card-label">Ton or</span>
        <span className="stat-card-value">{gold ?? "—"}</span>
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

      <div className="surface">
        {loading ? (
          <div className="skeleton" style={{ height: 80 }} />
        ) : items.length === 0 ? (
          <p className="mb-0">Aucun article en vente aujourd&apos;hui. Relance le seed post-MVP ou configure des items vendables.</p>
        ) : (
          <div className="quest-grid">
            {items.map((item) => (
              <article key={item.id} className="quest-card shop-card">
                <h3 className="quest-card-title">{item.name}</h3>
                <div className="quest-card-meta">
                  <span className={rarityChipClass(item.rarity)}>{rarityLabel(item.rarity)}</span>
                  <span className="chip chip--xp">{item.shopPrice} or</span>
                </div>
                {item.description && <p className="quest-card-desc">{item.description}</p>}
                <div className="quest-card-footer">
                  {item.purchased ? (
                    <span className="chip chip--success">Acheté</span>
                  ) : (
                    <>
                      <button
                        type="button"
                        className="btn btn--sm btn--accent"
                        disabled={buyingId === item.id || !canAfford(item.shopPrice)}
                        onClick={() => handleBuy(item)}
                      >
                        {buyingId === item.id ? "Achat…" : "Acheter"}
                      </button>
                      {!canAfford(item.shopPrice) && (
                        <span className="quest-card-hint">Or insuffisant</span>
                      )}
                    </>
                  )}
                </div>
              </article>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}

