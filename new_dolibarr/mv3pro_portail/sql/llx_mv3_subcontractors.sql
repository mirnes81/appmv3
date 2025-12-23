/*
  # Module Sous-Traitants - Gestion Complète

  ## Nouvelles tables créées

  ### 1. llx_mv3_subcontractors - Sous-Traitants
  - Gestion des sous-traitants (identité, contact, tarifs)
  - Code PIN pour connexion mobile sécurisée
  - Tarification par m², heure ou jour

  ### 2. llx_mv3_subcontractor_reports - Rapports Journaliers
  - Rapports quotidiens obligatoires
  - Suivi des m² posés et heures travaillées
  - Signature électronique + GPS
  - Validation par chef d'équipe

  ### 3. llx_mv3_subcontractor_photos - Photos des Rapports
  - Minimum 3 photos par rapport
  - Géolocalisation des photos
  - Types: avant/pendant/après/détail

  ### 4. llx_mv3_subcontractor_payments - Paiements
  - Génération automatique des paiements
  - Basé sur les rapports validés
  - Export comptabilité

  ### 5. llx_mv3_subcontractor_sessions - Sessions Mobile
  - Authentification par code PIN
  - Gestion des sessions actives
  - Sécurité renforcée
*/

-- =====================================================
-- TABLE 1: Sous-Traitants
-- =====================================================
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractors (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(50) UNIQUE NOT NULL,
  entity INTEGER DEFAULT 1,
  firstname VARCHAR(100) NOT NULL,
  lastname VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  email VARCHAR(255),
  pin_code VARCHAR(10) NOT NULL,
  specialty VARCHAR(100),
  rate_type VARCHAR(20) DEFAULT 'm2' COMMENT 'm2, hourly, daily',
  rate_amount DECIMAL(10,2) DEFAULT 0,
  active TINYINT DEFAULT 1,
  last_login DATETIME,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME,
  fk_user_author INTEGER,
  note_public TEXT,
  note_private TEXT,
  INDEX idx_active (active),
  INDEX idx_pin (pin_code),
  INDEX idx_ref (ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 2: Rapports Journaliers
-- =====================================================
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_reports (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(50) UNIQUE NOT NULL,
  entity INTEGER DEFAULT 1,
  fk_subcontractor INTEGER NOT NULL,
  fk_project INTEGER,
  fk_soc INTEGER,
  report_date DATE NOT NULL,
  work_type VARCHAR(100),
  surface_m2 DECIMAL(10,2) DEFAULT 0,
  hours_worked DECIMAL(5,2) DEFAULT 0,
  start_time TIME,
  end_time TIME,
  amount_calculated DECIMAL(10,2) DEFAULT 0,
  notes TEXT,
  latitude VARCHAR(50),
  longitude VARCHAR(50),
  signature_data LONGTEXT,
  signature_date DATETIME,
  status TINYINT DEFAULT 0 COMMENT '0=draft, 1=submitted, 2=validated, 3=invoiced, 9=rejected',
  photo_count INTEGER DEFAULT 0,
  validated_by INTEGER,
  validation_date DATETIME,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME,
  INDEX idx_subcontractor (fk_subcontractor),
  INDEX idx_date (report_date),
  INDEX idx_status (status),
  INDEX idx_project (fk_project),
  FOREIGN KEY (fk_subcontractor) REFERENCES llx_mv3_subcontractors(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 3: Photos des Rapports
-- =====================================================
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_photos (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  fk_report INTEGER NOT NULL,
  photo_type VARCHAR(20) DEFAULT 'work' COMMENT 'before, during, after, detail',
  file_path TEXT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_size INTEGER,
  latitude VARCHAR(50),
  longitude VARCHAR(50),
  photo_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  position INTEGER DEFAULT 0,
  INDEX idx_report (fk_report),
  INDEX idx_date (photo_date),
  FOREIGN KEY (fk_report) REFERENCES llx_mv3_subcontractor_reports(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 4: Paiements Sous-Traitants
-- =====================================================
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_payments (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity INTEGER DEFAULT 1,
  payment_ref VARCHAR(50) UNIQUE NOT NULL,
  fk_subcontractor INTEGER NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  total_m2 DECIMAL(10,2) DEFAULT 0,
  total_hours DECIMAL(10,2) DEFAULT 0,
  amount_ht DECIMAL(10,2) DEFAULT 0,
  amount_tva DECIMAL(10,2) DEFAULT 0,
  amount_ttc DECIMAL(10,2) DEFAULT 0,
  tva_tx DECIMAL(5,2) DEFAULT 8.1,
  status TINYINT DEFAULT 0 COMMENT '0=pending, 1=paid',
  payment_date DATE,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  fk_user_author INTEGER,
  note TEXT,
  INDEX idx_subcontractor (fk_subcontractor),
  INDEX idx_status (status),
  INDEX idx_period (period_start, period_end),
  FOREIGN KEY (fk_subcontractor) REFERENCES llx_mv3_subcontractors(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABLE 5: Sessions Mobile
-- =====================================================
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_sessions (
  session_id VARCHAR(64) PRIMARY KEY,
  fk_subcontractor INTEGER NOT NULL,
  device_info TEXT,
  ip_address VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  INDEX idx_subcontractor (fk_subcontractor),
  INDEX idx_expires (expires_at),
  FOREIGN KEY (fk_subcontractor) REFERENCES llx_mv3_subcontractors(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DONNÉES DE TEST
-- =====================================================

-- Insérer 2 sous-traitants de test
INSERT INTO llx_mv3_subcontractors (ref, firstname, lastname, phone, email, pin_code, specialty, rate_type, rate_amount, active)
VALUES
  ('ST-202501-0001', 'Jean', 'Dupont', '0612345678', 'jean.dupont@example.com', '1234', 'Carrelage', 'm2', 25.00, 1),
  ('ST-202501-0002', 'Marie', 'Martin', '0687654321', 'marie.martin@example.com', '5678', 'Électricité', 'hourly', 45.00, 1)
ON DUPLICATE KEY UPDATE active=active;

-- Insérer un rapport de test
INSERT INTO llx_mv3_subcontractor_reports (
  ref, fk_subcontractor, report_date, work_type, surface_m2, hours_worked,
  start_time, end_time, amount_calculated, notes, status, photo_count
)
SELECT
  'RST-20250121-0001',
  s.rowid,
  CURDATE(),
  'Pose carrelage sol',
  45.50,
  7.5,
  '08:00:00',
  '17:00:00',
  (45.50 * s.rate_amount),
  'Pose carrelage cuisine - Zone A',
  1,
  4
FROM llx_mv3_subcontractors s
WHERE s.ref = 'ST-202501-0001'
LIMIT 1
ON DUPLICATE KEY UPDATE status=status;
