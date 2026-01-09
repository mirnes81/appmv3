import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ProtectedRoute } from './components/ProtectedRoute';
import { Login } from './pages/Login';
import { Dashboard } from './pages/Dashboard';
import { AccountUnlinked } from './pages/AccountUnlinked';
import { Planning } from './pages/Planning';
import { PlanningDetail } from './pages/PlanningDetail';
import { Rapports } from './pages/Rapports';
import { RapportNew } from './pages/RapportNew';
import { RapportNewPro } from './pages/RapportNewPro';
import { RapportDetail } from './pages/RapportDetail';
import { Regie } from './pages/Regie';
import { RegieNew } from './pages/RegieNew';
import { SensPose } from './pages/SensPose';
import { SensPoseNew } from './pages/SensPoseNew';
import { Materiel } from './pages/Materiel';
import { Notifications } from './pages/Notifications';
import { Profil } from './pages/Profil';

function App() {
  return (
    <AuthProvider>
      <HashRouter>
        <Routes>
          <Route path="/login" element={<Login />} />

          <Route
            path="/account-unlinked"
            element={
              <ProtectedRoute>
                <AccountUnlinked />
              </ProtectedRoute>
            }
          />

          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />

          <Route
            path="/planning"
            element={
              <ProtectedRoute>
                <Planning />
              </ProtectedRoute>
            }
          />
          <Route
            path="/planning/:id"
            element={
              <ProtectedRoute>
                <PlanningDetail />
              </ProtectedRoute>
            }
          />

          <Route
            path="/rapports"
            element={
              <ProtectedRoute>
                <Rapports />
              </ProtectedRoute>
            }
          />
          <Route
            path="/rapports/new"
            element={
              <ProtectedRoute>
                <RapportNew />
              </ProtectedRoute>
            }
          />
          <Route
            path="/rapports/new-pro"
            element={
              <ProtectedRoute>
                <RapportNewPro />
              </ProtectedRoute>
            }
          />
          <Route
            path="/rapports/:id"
            element={
              <ProtectedRoute>
                <RapportDetail />
              </ProtectedRoute>
            }
          />

          <Route
            path="/regie"
            element={
              <ProtectedRoute>
                <Regie />
              </ProtectedRoute>
            }
          />
          <Route
            path="/regie/new"
            element={
              <ProtectedRoute>
                <RegieNew />
              </ProtectedRoute>
            }
          />

          <Route
            path="/sens-pose"
            element={
              <ProtectedRoute>
                <SensPose />
              </ProtectedRoute>
            }
          />
          <Route
            path="/sens-pose/new"
            element={
              <ProtectedRoute>
                <SensPoseNew />
              </ProtectedRoute>
            }
          />

          <Route
            path="/materiel"
            element={
              <ProtectedRoute>
                <Materiel />
              </ProtectedRoute>
            }
          />

          <Route
            path="/notifications"
            element={
              <ProtectedRoute>
                <Notifications />
              </ProtectedRoute>
            }
          />

          <Route
            path="/profil"
            element={
              <ProtectedRoute>
                <Profil />
              </ProtectedRoute>
            }
          />

          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </HashRouter>
    </AuthProvider>
  );
}

export default App;
