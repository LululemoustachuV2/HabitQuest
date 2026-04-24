import { Navigate, Route, Routes } from "react-router-dom";
import AppLayout from "./components/AppLayout";
import ProtectedRoute from "./components/ProtectedRoute";
import RoleRoute from "./components/RoleRoute";
import LoginPage from "./pages/LoginPage";
import RegisterPage from "./pages/RegisterPage";
import QuestsPage from "./pages/QuestsPage";
import NotificationsPage from "./pages/NotificationsPage";
import EventsPage from "./pages/EventsPage";
import AdminTemplatesPage from "./pages/AdminTemplatesPage";
import AdminEventsPage from "./pages/AdminEventsPage";
import NotFoundPage from "./pages/NotFoundPage";

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />

      <Route
        path="/"
        element={
          <ProtectedRoute>
            <AppLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/quests" replace />} />
        <Route path="quests" element={<QuestsPage />} />
        <Route path="events" element={<EventsPage />} />
        <Route path="notifications" element={<NotificationsPage />} />

        <Route
          path="admin/templates"
          element={
            <RoleRoute allowedRoles={["ROLE_ADMIN"]}>
              <AdminTemplatesPage />
            </RoleRoute>
          }
        />
        <Route
          path="admin/events"
          element={
            <RoleRoute allowedRoles={["ROLE_ADMIN"]}>
              <AdminEventsPage />
            </RoleRoute>
          }
        />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
