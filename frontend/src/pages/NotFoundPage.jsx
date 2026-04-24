import { Link } from "react-router-dom";

export default function NotFoundPage() {
  return (
    <section className="notfound">
      <div>
        <p className="notfound-code">404</p>
        <h2>Cette quete est introuvable</h2>
        <p className="muted">La page demandee n existe pas ou a ete deplacee.</p>
        <div className="inline-actions" style={{ justifyContent: "center", marginTop: 16 }}>
          <Link to="/quests" className="btn">
            Retour a mes quetes
          </Link>
        </div>
      </div>
    </section>
  );
}
