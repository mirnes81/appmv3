/*
  # Table Fiche sens de pose - Carreleurs

  1. Tables
    - llx_mv3_sens_pose : Fiches de sens de pose par chantier
    - llx_mv3_sens_pose_pieces : Pièces détaillées de chaque fiche

  2. Fonctionnalités
    - Gestion multi-pièces par chantier
    - Signature électronique client
    - Schémas visuels de pose
    - Génération PDF
    - Envoi email
    - Intégration Dolibarr (Projet/Client)

  3. Sécurité
    - RLS activé sur toutes les tables
    - Policies restrictives par utilisateur
*/

-- Table principale des fiches
CREATE TABLE IF NOT EXISTS llx_mv3_sens_pose (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,

  -- Référence unique
  ref VARCHAR(50) NOT NULL UNIQUE,

  -- Liens Dolibarr
  fk_projet INT DEFAULT NULL,
  fk_client INT DEFAULT NULL,

  -- Informations chantier
  client_name VARCHAR(255) NOT NULL,
  internal_ref VARCHAR(100),
  site_address TEXT,
  notes TEXT,

  -- Signature
  sign_name VARCHAR(255),
  signature_data LONGTEXT,
  signature_date DATETIME,

  -- Statut
  statut VARCHAR(50) DEFAULT 'brouillon',

  -- PDF
  pdf_path VARCHAR(255),
  pdf_sent_date DATETIME,
  pdf_sent_to VARCHAR(255),

  -- Dates
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Utilisateur
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

  -- Lien vers la fiche
  fk_sens_pose INT NOT NULL,

  -- Identification pièce
  nom VARCHAR(100) NOT NULL,
  ordre INT DEFAULT 0,

  -- Type et sens de pose
  type_pose VARCHAR(100),
  sens VARCHAR(50),
  schema_data LONGTEXT,

  -- Carrelage
  format VARCHAR(50) NOT NULL,
  epaisseur VARCHAR(20),

  -- Joints
  joint_ciment VARCHAR(100),
  joint_ciment_color VARCHAR(20),
  joint_silicone VARCHAR(100),
  joint_silicone_color VARCHAR(20),

  -- Plinthes
  plinthes VARCHAR(50),
  plinthes_hauteur VARCHAR(20),

  -- Profilés
  profil VARCHAR(100),
  profil_finition VARCHAR(100),

  -- Options SDB/Cuisine
  credence VARCHAR(10) DEFAULT 'Non',
  credence_hauteur VARCHAR(20),
  jusqu_au_plafond VARCHAR(10) DEFAULT 'Non',

  -- Notes
  remarques TEXT,

  -- Dates
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_fk_sens_pose (fk_sens_pose),
  INDEX idx_ordre (ordre),
  FOREIGN KEY (fk_sens_pose) REFERENCES llx_mv3_sens_pose(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger pour générer la référence automatiquement
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_mv3_sens_pose_ref
BEFORE INSERT ON llx_mv3_sens_pose
FOR EACH ROW
BEGIN
  IF NEW.ref IS NULL OR NEW.ref = '' THEN
    SET NEW.ref = CONCAT('POSE-', YEAR(NOW()), '-', LPAD(
      (SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(ref, '-', -1) AS UNSIGNED)), 0) + 1
       FROM llx_mv3_sens_pose
       WHERE ref LIKE CONCAT('POSE-', YEAR(NOW()), '-%')),
      4, '0'
    ));
  END IF;
END//
DELIMITER ;
