import { Navigate } from "react-router-dom";
import { useAuth } from "../state/AuthContext";

export default function RoleRoute({ allowedRoles, children }) {
  const { roles } = useAuth();
  const isAllowed = roles.some((role) => allowedRoles.includes(role));

  if (!isAllowed) {
    return <Navigate to="/quests" replace />;
  }

  return children;
}
