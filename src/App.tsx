import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Rapports from './pages/Rapports';
import Planning from './pages/Planning';
import Materiel from './pages/Materiel';
import SensDePose from './pages/SensDePose';
import Regie from './pages/Regie';
import Profil from './pages/Profil';

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<Navigate to="/login" replace />} />
        <Route path="/login" element={<Login />} />
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/rapports" element={<Rapports />} />
        <Route path="/planning" element={<Planning />} />
        <Route path="/materiel" element={<Materiel />} />
        <Route path="/sens-de-pose" element={<SensDePose />} />
        <Route path="/regie" element={<Regie />} />
        <Route path="/profil" element={<Profil />} />
      </Routes>
    </Router>
  );
}

export default App;
