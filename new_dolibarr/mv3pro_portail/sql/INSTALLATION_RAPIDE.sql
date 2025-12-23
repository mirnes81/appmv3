-- =====================================================
-- INSTALLATION MODULE SENS DE POSE
-- A copier/coller dans phpMyAdmin
-- Base de données : ch314761_do909
-- =====================================================

-- Table principale des fiches sens de pose
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
  INDEX idx_ref (ref),
  INDEX idx_projet (fk_projet),
  INDEX idx_client (fk_client),
  INDEX idx_statut (statut),
  INDEX idx_entity (entity),
  INDEX idx_user (fk_user_create)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des pièces
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

-- =====================================================
-- FIN - Tables créées avec succès !
-- Vous pouvez maintenant utiliser le module.
-- =====================================================
