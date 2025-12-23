-- ============================================
-- MV3 PRO PWA - SCHEMA MYSQL pour Dolibarr
-- À exécuter dans phpMyAdmin
-- ============================================

-- Table des utilisateurs mobiles
CREATE TABLE IF NOT EXISTS llx_mv3_mobile_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dolibarr_user_id INT NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(50),
  password_hash VARCHAR(255) NOT NULL,
  biometric_enabled TINYINT(1) DEFAULT 0,
  preferences JSON,
  last_sync DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_dolibarr_user (dolibarr_user_id),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sessions mobiles
CREATE TABLE IF NOT EXISTS llx_mv3_mobile_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  device_info JSON,
  ip_address VARCHAR(45),
  expires_at DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des brouillons de rapports
CREATE TABLE IF NOT EXISTS llx_mv3_report_drafts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  draft_name VARCHAR(255) NOT NULL,
  report_type VARCHAR(50) NOT NULL,
  content JSON NOT NULL,
  photos JSON,
  voice_notes JSON,
  gps_location JSON,
  auto_saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(id) ON DELETE CASCADE,
  INDEX idx_user_type (user_id, report_type),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des templates de rapports
CREATE TABLE IF NOT EXISTS llx_mv3_report_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  report_type VARCHAR(50) NOT NULL,
  template_data JSON NOT NULL,
  is_public TINYINT(1) DEFAULT 0,
  usage_count INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de la file de synchronisation
CREATE TABLE IF NOT EXISTS llx_mv3_sync_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action_type VARCHAR(50) NOT NULL,
  priority INT DEFAULT 5,
  payload JSON NOT NULL,
  status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
  retry_count INT DEFAULT 0,
  error_message TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  synced_at DATETIME,
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(id) ON DELETE CASCADE,
  INDEX idx_user_status (user_id, status),
  INDEX idx_priority (priority, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table du cache offline
CREATE TABLE IF NOT EXISTS llx_mv3_offline_cache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  cache_key VARCHAR(255) NOT NULL,
  cache_type VARCHAR(50) NOT NULL,
  data JSON NOT NULL,
  ttl INT DEFAULT 3600,
  expires_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_cache (user_id, cache_key),
  INDEX idx_user_type (user_id, cache_type),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des backups de photos
CREATE TABLE IF NOT EXISTS llx_mv3_photo_backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_size INT,
  mime_type VARCHAR(100),
  width INT,
  height INT,
  compressed TINYINT(1) DEFAULT 0,
  related_to_type VARCHAR(50),
  related_to_id VARCHAR(50),
  gps_location JSON,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_related (related_to_type, related_to_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notes vocales
CREATE TABLE IF NOT EXISTS llx_mv3_voice_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  transcription TEXT,
  audio_duration INT,
  language VARCHAR(10) DEFAULT 'fr-FR',
  confidence_score DECIMAL(3,2),
  related_to_type VARCHAR(50),
  related_to_id INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES llx_mv3_mobile_users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_related (related_to_type, related_to_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter un utilisateur de test (mot de passe: test123)
INSERT INTO llx_mv3_mobile_users (dolibarr_user_id, email, password_hash, preferences)
VALUES (
  1,
  'test@mv3pro.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  '{"theme": "auto", "notifications": true, "autoSave": true}'
)
ON DUPLICATE KEY UPDATE email=email;
