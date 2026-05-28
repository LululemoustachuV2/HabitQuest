import { Navigate, Route, Routes } from "react-router-dom";
import AppLayout from "./components/AppLayout";
import ProtectedRoute from "./components/ProtectedRoute";
import RoleRoute from "./components/RoleRoute";
import LoginPage from "./pages/LoginPage";
import RegisterPage from "./pages/RegisterPage";
import QuestsPage from "./pages/QuestsPage";
import InventoryPage from "./pages/InventoryPage";
import ShopPage from "./pages/ShopPage";
import ProfilePage from "./pages/ProfilePage";
import NotificationsPage from "./pages/NotificationsPage";
import EventsPage from "./pages/EventsPage";
import AdminTemplatesPage from "./pages/AdminTemplatesPage";
import AdminEventsPage from "./pages/AdminEventsPage";
import AdminItemsPage from "./pages/AdminItemsPage";
import AdminMonstersPage from "./pages/AdminMonstersPage";
import AdminAchievementsPage from "./pages/AdminAchievementsPage";
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
        <Route path="habits" element={<Navigate to="/quests" replace />} />
        <Route path="inventory" element={<InventoryPage />} />
        <Route path="shop" element={<ShopPage />} />
        <Route path="profile" element={<ProfilePage />} />
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
        <Route path="admin/categories" element={<Navigate to="/admin/templates" replace />} />
        <Route
          path="admin/items"
          element={
            <RoleRoute allowedRoles={["ROLE_ADMIN"]}>
              <AdminItemsPage />
            </RoleRoute>
          }
        />
        <Route
          path="admin/monsters"
          element={
            <RoleRoute allowedRoles={["ROLE_ADMIN"]}>
              <AdminMonstersPage />
            </RoleRoute>
          }
        />
        <Route
          path="admin/achievements"
          element={
            <RoleRoute allowedRoles={["ROLE_ADMIN"]}>
              <AdminAchievementsPage />
            </RoleRoute>
          }
        />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}

