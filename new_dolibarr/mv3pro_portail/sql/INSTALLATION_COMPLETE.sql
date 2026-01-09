-- =====================================================
-- INSTALLATION COMPLÈTE MODULE MV3 PRO PORTAIL
-- Toutes les tables nécessaires pour le fonctionnement de la PWA
-- =====================================================

-- ============================================================================
-- 1. AUTHENTIFICATION MOBILE (llx_mv3_mobile_users et sessions)
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_mv3_mobile_users (
  rowid int AUTO_INCREMENT PRIMARY KEY,
  email varchar(255) NOT NULL UNIQUE,
  password_hash varchar(255) NOT NULL,
  dolibarr_user_id int DEFAULT NULL,
  firstname varchar(100) NOT NULL,
  lastname varchar(100) NOT NULL,
  phone varchar(50) DEFAULT NULL,
  role varchar(50) DEFAULT 'employee',
  pin_code varchar(10) DEFAULT NULL,
  is_active tinyint DEFAULT 1,
  last_login datetime DEFAULT NULL,
  login_attempts int DEFAULT 0,
  locked_until datetime DEFAULT NULL,
  device_token varchar(500) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_email (email),
  INDEX idx_dolibarr_user (dolibarr_user_id),
  INDEX idx_active (is_active),
  FOREIGN KEY (dolibarr_user_id) REFERENCES llx_user(rowid) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS llx_mv3_mobile_sessions (
  rowid int AUTO_INCREMENT PRIMARY KEY,
  user_id int NOT NULL,
  session_token varchar(255) NOT NULL UNIQUE,
  device_info TEXT DEFAULT NULL,
  ip_address varchar(50) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  expires_at datetime NOT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  last_activity datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_token (session_token),
  INDEX idx_user (user_id),
  INDEX idx_expires (expires_at),
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS llx_mv3_mobile_login_history (
  rowid int AUTO_INCREMENT PRIMARY KEY,
  user_id int DEFAULT NULL,
  email varchar(255) NOT NULL,
  success tinyint DEFAULT 0,
  ip_address varchar(50) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  error_message varchar(255) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_user (user_id),
  INDEX idx_email (email),
  INDEX idx_created (created_at),
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(rowid) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. RAPPORTS JOURNALIERS (llx_mv3_rapport)
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_mv3_rapport (
  rowid int AUTO_INCREMENT PRIMARY KEY,
  ref varchar(50) NOT NULL,
  entity int DEFAULT 1 NOT NULL,
  date_rapport date NOT NULL,
  heure_debut time DEFAULT NULL,
  heure_fin time DEFAULT NULL,
  fk_projet int DEFAULT NULL,
  fk_soc int DEFAULT NULL,
  fk_user int NOT NULL,
  zones text,
  surface_total decimal(10,2) DEFAULT 0,
  format varchar(100) DEFAULT NULL,
  type_carrelage varchar(100) DEFAULT NULL,
  travaux_realises text,
  observations text,
  statut varchar(20) DEFAULT 'brouillon',
  date_creation datetime DEFAULT CURRENT_TIMESTAMP,
  date_modification datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_ref (ref),
  INDEX idx_date (date_rapport),
  INDEX idx_user (fk_user),
  INDEX idx_projet (fk_projet),
  INDEX idx_statut (statut),
  INDEX idx_entity (entity),
  FOREIGN KEY (fk_user) REFERENCES llx_user(rowid) ON DELETE CASCADE,
  FOREIGN KEY (fk_projet) REFERENCES llx_projet(rowid) ON DELETE SET NULL,
  FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. NOTIFICATIONS (llx_mv3_notifications)
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_mv3_notifications (
  rowid int AUTO_INCREMENT PRIMARY KEY,
  fk_user int NOT NULL,
  type varchar(50) DEFAULT 'info',
  titre varchar(255) NOT NULL,
  message text,
  fk_object int DEFAULT NULL,
  object_type varchar(50) DEFAULT NULL,
  statut varchar(20) DEFAULT 'non_lu',
  date_creation datetime DEFAULT CURRENT_TIMESTAMP,
  date_lecture datetime DEFAULT NULL,
  entity int DEFAULT 1,

  INDEX idx_fk_user (fk_user),
  INDEX idx_statut (statut),
  INDEX idx_date_creation (date_creation),
  INDEX idx_entity (entity),
  FOREIGN KEY (fk_user) REFERENCES llx_user(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. SENS DE POSE (llx_mv3_sens_pose + pieces)
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_mv3_sens_pose (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  ref VARCHAR(50) NOT NULL DEFAULT '',
  fk_projet INT DEFAULT NULL,
  fk_client INT DEFAULT NULL,
  client_name VARCHAR(255) NOT NULL,
  internal_ref VARCHAR(100),
  site_address TEXT,
  notes TEXT,
  sign_name VARCHAR(255),
  signature_data LONGTEXT,
  signature_date DATETIME,
  statut VARCHAR(50) DEFAULT 'brouillon',
  pdf_path VARCHAR(255),
  pdf_sent_date DATETIME,
  pdf_sent_to VARCHAR(255),
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_create INT NOT NULL,
  fk_user_modif INT,

  UNIQUE KEY uk_ref (ref),
  INDEX idx_ref (ref),
  INDEX idx_projet (fk_projet),
  INDEX idx_client (fk_client),
  INDEX idx_statut (statut),
  INDEX idx_entity (entity),
  INDEX idx_user (fk_user_create),
  FOREIGN KEY (fk_user_create) REFERENCES llx_user(rowid) ON DELETE CASCADE,
  FOREIGN KEY (fk_projet) REFERENCES llx_projet(rowid) ON DELETE SET NULL,
  FOREIGN KEY (fk_client) REFERENCES llx_societe(rowid) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS llx_mv3_sens_pose_pieces (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  fk_sens_pose INT NOT NULL,
  nom VARCHAR(100) NOT NULL,
  ordre INT DEFAULT 0,
  type_pose VARCHAR(100),
  sens VARCHAR(50),
  schema_data LONGTEXT,
  format VARCHAR(50) NOT NULL,
  epaisseur VARCHAR(20),
  joint_ciment VARCHAR(100),
  joint_ciment_color VARCHAR(20),
  joint_silicone VARCHAR(100),
  joint_silicone_color VARCHAR(20),
  plinthes VARCHAR(50),
  plinthes_hauteur VARCHAR(20),
  profil VARCHAR(100),
  profil_finition VARCHAR(100),
  credence VARCHAR(10) DEFAULT 'Non',
  credence_hauteur VARCHAR(20),
  jusqu_au_plafond VARCHAR(10) DEFAULT 'Non',
  remarques TEXT,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_fk_sens_pose (fk_sens_pose),
  INDEX idx_ordre (ordre),
  FOREIGN KEY (fk_sens_pose) REFERENCES llx_mv3_sens_pose(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. MATÉRIEL (llx_mv3_materiel) - Optionnel
-- ============================================================================

CREATE TABLE IF NOT EXISTS llx_mv3_materiel (
  rowid int AUTO_INCREMENT PRIMARY KEY,
  entity int DEFAULT 1 NOT NULL,
  ref varchar(50) NOT NULL,
  nom varchar(255) NOT NULL,
  type varchar(50) DEFAULT NULL,
  quantite_stock int DEFAULT 0,
  seuil_alerte int DEFAULT 10,
  unite varchar(20) DEFAULT 'unité',
  emplacement varchar(100) DEFAULT NULL,
  description text,
  statut varchar(20) DEFAULT 'actif',
  date_creation datetime DEFAULT CURRENT_TIMESTAMP,
  date_modification datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_create int NOT NULL,

  UNIQUE KEY uk_ref (ref),
  INDEX idx_nom (nom),
  INDEX idx_type (type),
  INDEX idx_statut (statut),
  INDEX idx_entity (entity),
  FOREIGN KEY (fk_user_create) REFERENCES llx_user(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FIN - Installation complète réussie !
-- Toutes les tables nécessaires ont été créées.
--
-- Prochaine étape :
-- - Activer le module dans Dolibarr (Setup > Modules)
-- - Créer un utilisateur mobile via l'API
-- - Tester l'authentification
-- =====================================================
