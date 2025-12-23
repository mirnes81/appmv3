-- ============================================================================
-- Table pour l'authentification mobile indépendante
-- Permet aux employés de se connecter directement sans passer par Dolibarr
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour les sessions mobiles
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour l'historique de connexion
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
