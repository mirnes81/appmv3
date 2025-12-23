-- Table des rapports journaliers
CREATE TABLE IF NOT EXISTS llx_mv3_rapport (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  fk_user INT NOT NULL,
  fk_projet INT,
  date_rapport DATE NOT NULL,
  zone_travail VARCHAR(255),
  description TEXT,
  heures_debut TIME,
  heures_fin TIME,
  temps_total DECIMAL(5,2),
  travaux_realises TEXT,
  observations TEXT,
  statut VARCHAR(30) DEFAULT 'brouillon',
  fk_user_validation INT,
  date_validation DATETIME,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (fk_user),
  INDEX idx_projet (fk_projet),
  INDEX idx_date (date_rapport),
  INDEX idx_statut (statut),
  INDEX idx_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des photos de rapports
CREATE TABLE IF NOT EXISTS llx_mv3_rapport_photo (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_rapport INT NOT NULL,
  filepath VARCHAR(255) NOT NULL,
  filename VARCHAR(255),
  description VARCHAR(255),
  ordre INT DEFAULT 0,
  date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rapport (fk_rapport),
  FOREIGN KEY (fk_rapport) REFERENCES llx_mv3_rapport(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des signalements
CREATE TABLE IF NOT EXISTS llx_mv3_signalement (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  fk_user INT NOT NULL,
  fk_projet INT,
  type VARCHAR(50) DEFAULT 'probleme',
  priorite VARCHAR(20) DEFAULT 'normale',
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  photo VARCHAR(255),
  statut VARCHAR(30) DEFAULT 'ouvert',
  fk_user_resolu INT,
  date_resolution DATETIME,
  commentaire_resolution TEXT,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (fk_user),
  INDEX idx_projet (fk_projet),
  INDEX idx_statut (statut),
  INDEX idx_priorite (priorite),
  INDEX idx_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
