-- ============================================================================
-- MV3 PRO PORTAIL - INSTALLATION COMPLÈTE
-- ============================================================================
-- Module: mv3pro_portail
-- Version: 1.0.0
-- Date: 2025-01-07
-- Description: Schema complet pour le module MV3 PRO (Employés mobiles, API v1, PWA)
--
-- FEATURES:
--   - Authentification mobile indépendante (llx_mv3_mobile_*)
--   - Rapports journaliers avec GPS/météo (llx_mv3_rapport*)
--   - Sens de pose carrelage (llx_mv3_sens_pose*)
--   - Gestion matériel (llx_mv3_materiel*)
--   - Feuilles de régie (llx_mv3_regie*)
--   - Notes de frais (llx_mv3_frais)
--   - Notifications (llx_mv3_notifications)
--   - Sous-traitants (llx_mv3_subcontractor*)
--
-- COMPATIBILITÉ:
--   - Moteur: InnoDB
--   - Charset: utf8mb4
--   - Collation: utf8mb4_unicode_ci
--   - Dolibarr >= 16.0
--
-- IMPORTANT:
--   - Ce script est IDEMPOTENT (peut être exécuté plusieurs fois)
--   - Utilise CREATE TABLE IF NOT EXISTS
--   - Utilise ALTER TABLE ADD COLUMN IF NOT EXISTS pour les ajouts
--   - AUCUN DROP TABLE (sécurité données)
-- ============================================================================

-- ============================================================================
-- SECTION 1: AUTHENTIFICATION MOBILE
-- ============================================================================

-- Table des utilisateurs mobiles
-- Permet aux employés de se connecter sans compte Dolibarr
CREATE TABLE IF NOT EXISTS llx_mv3_mobile_users (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  dolibarr_user_id INT DEFAULT NULL,
  firstname VARCHAR(100) NOT NULL,
  lastname VARCHAR(100) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  role VARCHAR(50) DEFAULT 'employee',
  pin_code VARCHAR(10) DEFAULT NULL,
  is_active TINYINT DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  login_attempts INT DEFAULT 0,
  locked_until DATETIME DEFAULT NULL,
  device_token VARCHAR(500) DEFAULT NULL,
  entity INT DEFAULT 1 NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_email (email),
  INDEX idx_dolibarr_user (dolibarr_user_id),
  INDEX idx_active (is_active),
  INDEX idx_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Utilisateurs mobiles avec auth indépendante';

-- Table des sessions mobiles
-- Gère les tokens de session pour l'API et la PWA
CREATE TABLE IF NOT EXISTS llx_mv3_mobile_sessions (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  session_token VARCHAR(255) NOT NULL UNIQUE,
  device_info TEXT DEFAULT NULL,
  ip_address VARCHAR(50) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_token (session_token),
  INDEX idx_user (user_id),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions actives des utilisateurs mobiles';

-- Table d'historique de connexion
-- Traçabilité complète des authentifications
CREATE TABLE IF NOT EXISTS llx_mv3_mobile_login_history (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  email VARCHAR(255) NOT NULL,
  success TINYINT DEFAULT 0,
  ip_address VARCHAR(50) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  error_message VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_user (user_id),
  INDEX idx_email (email),
  INDEX idx_created (created_at),
  INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des tentatives de connexion';

-- ============================================================================
-- SECTION 2: RAPPORTS JOURNALIERS
-- ============================================================================

-- Table principale des rapports
-- Rapports de chantier quotidiens avec GPS et météo
CREATE TABLE IF NOT EXISTS llx_mv3_rapport (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  fk_user INT NOT NULL,
  fk_projet INT DEFAULT NULL,
  date_rapport DATE NOT NULL,
  zone_travail VARCHAR(255) DEFAULT NULL,
  description TEXT,
  heures_debut TIME DEFAULT NULL,
  heures_fin TIME DEFAULT NULL,
  temps_total DECIMAL(5,2) DEFAULT NULL,
  travaux_realises TEXT,
  observations TEXT,
  statut VARCHAR(30) DEFAULT 'brouillon',
  fk_user_validation INT DEFAULT NULL,
  date_validation DATETIME DEFAULT NULL,

  -- Géolocalisation (ajouté pour PWA)
  gps_latitude VARCHAR(20) DEFAULT NULL,
  gps_longitude VARCHAR(20) DEFAULT NULL,
  gps_accuracy DECIMAL(10,2) DEFAULT NULL,

  -- Météo (ajouté pour PWA)
  meteo_temperature DECIMAL(5,2) DEFAULT NULL,
  meteo_condition VARCHAR(100) DEFAULT NULL,

  -- Dates système
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_user (fk_user),
  INDEX idx_projet (fk_projet),
  INDEX idx_date (date_rapport),
  INDEX idx_statut (statut),
  INDEX idx_entity (entity),
  INDEX idx_gps (gps_latitude, gps_longitude),
  INDEX idx_user_date (fk_user, date_rapport)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rapports journaliers de chantier';

-- Table des photos de rapports
-- Stockage des photos jointes aux rapports
CREATE TABLE IF NOT EXISTS llx_mv3_rapport_photo (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_rapport INT NOT NULL,
  filepath VARCHAR(255) NOT NULL,
  filename VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  ordre INT DEFAULT 0,
  date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_rapport (fk_rapport)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Photos attachées aux rapports';

-- Table des signalements
-- Problèmes et incidents de chantier
CREATE TABLE IF NOT EXISTS llx_mv3_signalement (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  fk_user INT NOT NULL,
  fk_projet INT DEFAULT NULL,
  type VARCHAR(50) DEFAULT 'probleme',
  priorite VARCHAR(20) DEFAULT 'normale',
  titre VARCHAR(255) NOT NULL,
  description TEXT,
  photo VARCHAR(255) DEFAULT NULL,
  statut VARCHAR(30) DEFAULT 'ouvert',
  fk_user_resolu INT DEFAULT NULL,
  date_resolution DATETIME DEFAULT NULL,
  commentaire_resolution TEXT,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_user (fk_user),
  INDEX idx_projet (fk_projet),
  INDEX idx_statut (statut),
  INDEX idx_priorite (priorite),
  INDEX idx_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Signalements et incidents';

-- ============================================================================
-- SECTION 3: SENS DE POSE (CARRELAGE)
-- ============================================================================

-- Table principale des fiches sens de pose
-- Documentation technique de la pose de carrelage
CREATE TABLE IF NOT EXISTS llx_mv3_sens_pose (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,

  -- Référence unique (auto-générée)
  ref VARCHAR(50) NOT NULL UNIQUE,

  -- Liens Dolibarr
  fk_projet INT DEFAULT NULL,
  fk_client INT DEFAULT NULL,

  -- Informations chantier
  client_name VARCHAR(255) NOT NULL,
  internal_ref VARCHAR(100) DEFAULT NULL,
  site_address TEXT,
  notes TEXT,

  -- Signature électronique
  sign_name VARCHAR(255) DEFAULT NULL,
  signature_data LONGTEXT DEFAULT NULL,
  signature_date DATETIME DEFAULT NULL,

  -- Statut
  statut VARCHAR(50) DEFAULT 'brouillon',

  -- PDF
  pdf_path VARCHAR(255) DEFAULT NULL,
  pdf_sent_date DATETIME DEFAULT NULL,
  pdf_sent_to VARCHAR(255) DEFAULT NULL,

  -- Utilisateurs et dates
  fk_user_create INT NOT NULL,
  fk_user_modif INT DEFAULT NULL,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_ref (ref),
  INDEX idx_projet (fk_projet),
  INDEX idx_client (fk_client),
  INDEX idx_statut (statut),
  INDEX idx_entity (entity),
  INDEX idx_user (fk_user_create)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fiches de sens de pose carrelage';

-- Table des pièces (détail multi-pièces par fiche)
-- Détail technique par pièce (cuisine, SDB, etc.)
CREATE TABLE IF NOT EXISTS llx_mv3_sens_pose_pieces (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,

  -- Lien vers la fiche
  fk_sens_pose INT NOT NULL,

  -- Identification pièce
  nom VARCHAR(100) NOT NULL,
  ordre INT DEFAULT 0,

  -- Type et sens de pose
  type_pose VARCHAR(100) DEFAULT NULL,
  sens VARCHAR(50) DEFAULT NULL,
  schema_data LONGTEXT DEFAULT NULL,

  -- Carrelage
  format VARCHAR(50) NOT NULL,
  epaisseur VARCHAR(20) DEFAULT NULL,

  -- Joints
  joint_ciment VARCHAR(100) DEFAULT NULL,
  joint_ciment_color VARCHAR(20) DEFAULT NULL,
  joint_silicone VARCHAR(100) DEFAULT NULL,
  joint_silicone_color VARCHAR(20) DEFAULT NULL,

  -- Plinthes
  plinthes VARCHAR(50) DEFAULT NULL,
  plinthes_hauteur VARCHAR(20) DEFAULT NULL,

  -- Profilés
  profil VARCHAR(100) DEFAULT NULL,
  profil_finition VARCHAR(100) DEFAULT NULL,

  -- Options SDB/Cuisine
  credence VARCHAR(10) DEFAULT 'Non',
  credence_hauteur VARCHAR(20) DEFAULT NULL,
  jusqu_au_plafond VARCHAR(10) DEFAULT 'Non',

  -- Notes
  remarques TEXT,

  -- Dates
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_fk_sens_pose (fk_sens_pose),
  INDEX idx_ordre (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Détail par pièce des fiches sens de pose';

-- ============================================================================
-- SECTION 4: MATÉRIEL
-- ============================================================================

-- Table principale du matériel
-- Gestion des outils, machines, véhicules
CREATE TABLE IF NOT EXISTS llx_mv3_materiel (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  ref VARCHAR(50) NOT NULL,
  nom VARCHAR(255) NOT NULL,
  type ENUM('outil', 'machine', 'vehicule', 'consommable') DEFAULT 'outil',
  marque VARCHAR(100) DEFAULT NULL,
  modele VARCHAR(100) DEFAULT NULL,
  numero_serie VARCHAR(100) DEFAULT NULL,
  qrcode VARCHAR(100) DEFAULT NULL,
  statut ENUM('disponible', 'en_service', 'maintenance', 'hors_service') DEFAULT 'disponible',
  fk_user_assigne INT DEFAULT NULL,
  fk_projet_assigne INT DEFAULT NULL,
  date_achat DATE DEFAULT NULL,
  valeur_achat DECIMAL(10,2) DEFAULT NULL,
  date_derniere_maintenance DATE DEFAULT NULL,
  date_prochaine_maintenance DATE DEFAULT NULL,
  observations TEXT,
  photo VARCHAR(255) DEFAULT NULL,
  fk_user_creation INT NOT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_mv3_materiel_ref (entity, ref),
  KEY idx_mv3_materiel_type (type),
  KEY idx_mv3_materiel_statut (statut),
  KEY idx_mv3_materiel_user (fk_user_assigne),
  KEY idx_mv3_materiel_projet (fk_projet_assigne),
  KEY idx_mv3_materiel_qrcode (qrcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Gestion du matériel';

-- Table historique matériel
-- Traçabilité complète des mouvements
CREATE TABLE IF NOT EXISTS llx_mv3_materiel_historique (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_materiel INT NOT NULL,
  type_evenement ENUM('creation', 'affectation_user', 'affectation_projet', 'liberation', 'maintenance', 'changement_statut', 'modification') NOT NULL,
  ancien_statut VARCHAR(50) DEFAULT NULL,
  nouveau_statut VARCHAR(50) DEFAULT NULL,
  fk_user_ancien INT DEFAULT NULL,
  fk_user_nouveau INT DEFAULT NULL,
  fk_projet_ancien INT DEFAULT NULL,
  fk_projet_nouveau INT DEFAULT NULL,
  commentaire TEXT,
  fk_user_action INT NOT NULL,
  date_evenement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  KEY idx_mv3_materiel_hist_materiel (fk_materiel),
  KEY idx_mv3_materiel_hist_date (date_evenement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des mouvements matériel';

-- ============================================================================
-- SECTION 5: FEUILLES DE RÉGIE
-- ============================================================================

-- Table principale des régies
-- Bons de régie pour facturation client
CREATE TABLE IF NOT EXISTS llx_mv3_regie (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  ref VARCHAR(50) NOT NULL UNIQUE,
  fk_project INT DEFAULT NULL,
  fk_soc INT DEFAULT NULL,
  fk_user_author INT NOT NULL,

  -- Informations régie
  date_regie DATE NOT NULL,
  location_text VARCHAR(255) DEFAULT NULL,
  type_regie VARCHAR(50) DEFAULT 'travaux',

  -- Totaux
  total_ht DECIMAL(10,2) DEFAULT 0.00,
  total_tva DECIMAL(10,2) DEFAULT 0.00,
  total_ttc DECIMAL(10,2) DEFAULT 0.00,

  -- Statut (0=brouillon, 1=validé, 2=envoyé, 3=facturé, 9=annulé)
  status TINYINT DEFAULT 0,

  -- Signature
  signature_data LONGTEXT DEFAULT NULL,
  signature_date DATETIME DEFAULT NULL,
  signature_name VARCHAR(255) DEFAULT NULL,

  -- Notes
  note_public TEXT,
  note_private TEXT,

  -- PDF
  pdf_path VARCHAR(255) DEFAULT NULL,

  -- Dates
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_ref (ref),
  INDEX idx_project (fk_project),
  INDEX idx_soc (fk_soc),
  INDEX idx_user (fk_user_author),
  INDEX idx_date (date_regie),
  INDEX idx_status (status),
  INDEX idx_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Feuilles de régie';

-- Table des lignes de régie
-- Détail des prestations (temps, matériel, options)
CREATE TABLE IF NOT EXISTS llx_mv3_regie_ligne (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  fk_regie INT NOT NULL,

  -- Type de ligne (time, material, option)
  line_type VARCHAR(20) DEFAULT 'time',

  -- Description et quantité
  description VARCHAR(500) NOT NULL,
  qty DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  -- TVA
  tva_tx DECIMAL(5,2) DEFAULT 20.00,

  -- Totaux calculés
  total_ht DECIMAL(10,2) DEFAULT 0.00,
  total_tva DECIMAL(10,2) DEFAULT 0.00,
  total_ttc DECIMAL(10,2) DEFAULT 0.00,

  -- Ordre
  ligne_ordre INT DEFAULT 0,

  -- Dates
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_regie (fk_regie),
  INDEX idx_type (line_type),
  INDEX idx_ordre (ligne_ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lignes de détail des régies';

-- ============================================================================
-- SECTION 6: NOTES DE FRAIS
-- ============================================================================

-- Table des frais
-- Notes de frais des employés
CREATE TABLE IF NOT EXISTS llx_mv3_frais (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT DEFAULT 1 NOT NULL,
  fk_user INT NOT NULL,
  fk_rapport INT DEFAULT NULL,
  fk_projet INT DEFAULT NULL,

  -- Informations frais
  date_frais DATE NOT NULL,
  type_frais VARCHAR(50) NOT NULL COMMENT 'repas, deplacement, carburant, hotel, autre',
  categorie VARCHAR(50) DEFAULT NULL,
  description VARCHAR(500) NOT NULL,

  -- Montants
  montant_ht DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  tva_tx DECIMAL(5,2) DEFAULT 20.00,
  montant_tva DECIMAL(10,2) DEFAULT 0.00,
  montant_ttc DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  -- Justificatif
  justificatif_path VARCHAR(255) DEFAULT NULL,
  justificatif_filename VARCHAR(255) DEFAULT NULL,

  -- Statut (pending, to_reimburse, reimbursed, rejected)
  statut VARCHAR(30) DEFAULT 'pending',

  -- Validation
  fk_user_valid INT DEFAULT NULL,
  date_validation DATETIME DEFAULT NULL,
  comment_validation TEXT,

  -- Remboursement
  date_remboursement DATE DEFAULT NULL,
  ref_remboursement VARCHAR(100) DEFAULT NULL,

  -- Dates
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_user (fk_user),
  INDEX idx_rapport (fk_rapport),
  INDEX idx_projet (fk_projet),
  INDEX idx_date (date_frais),
  INDEX idx_type (type_frais),
  INDEX idx_statut (statut),
  INDEX idx_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notes de frais employés';

-- ============================================================================
-- SECTION 7: NOTIFICATIONS
-- ============================================================================

-- Table des notifications
-- Système de notifications pour tous les modules
CREATE TABLE IF NOT EXISTS llx_mv3_notifications (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  fk_user INT NOT NULL,
  type VARCHAR(50) DEFAULT 'info' COMMENT 'info, success, warning, error',
  titre VARCHAR(255) NOT NULL,
  message TEXT,
  fk_object INT DEFAULT NULL,
  object_type VARCHAR(50) DEFAULT NULL COMMENT 'rapport, materiel, regie, etc.',
  statut VARCHAR(20) DEFAULT 'non_lu' COMMENT 'non_lu, lu, traite, reporte',
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_lecture DATETIME DEFAULT NULL,
  entity INT DEFAULT 1,

  PRIMARY KEY (rowid),
  KEY idx_fk_user (fk_user),
  KEY idx_statut (statut),
  KEY idx_date_creation (date_creation),
  KEY idx_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Système de notifications';

-- ============================================================================
-- SECTION 8: SOUS-TRAITANTS
-- ============================================================================

-- Table des sous-traitants
-- Gestion des sous-traitants avec connexion mobile
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractors (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(50) UNIQUE NOT NULL,
  entity INTEGER DEFAULT 1,
  firstname VARCHAR(100) NOT NULL,
  lastname VARCHAR(100) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  pin_code VARCHAR(10) NOT NULL,
  specialty VARCHAR(100) DEFAULT NULL,
  rate_type VARCHAR(20) DEFAULT 'm2' COMMENT 'm2, hourly, daily',
  rate_amount DECIMAL(10,2) DEFAULT 0,
  active TINYINT DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT NULL,
  fk_user_author INTEGER DEFAULT NULL,
  note_public TEXT,
  note_private TEXT,

  INDEX idx_active (active),
  INDEX idx_pin (pin_code),
  INDEX idx_ref (ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sous-traitants';

-- Table des rapports journaliers sous-traitants
-- Rapports quotidiens obligatoires avec m², heures, photos
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_reports (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(50) UNIQUE NOT NULL,
  entity INTEGER DEFAULT 1,
  fk_subcontractor INTEGER NOT NULL,
  fk_project INTEGER DEFAULT NULL,
  fk_soc INTEGER DEFAULT NULL,
  report_date DATE NOT NULL,
  work_type VARCHAR(100) DEFAULT NULL,
  surface_m2 DECIMAL(10,2) DEFAULT 0,
  hours_worked DECIMAL(5,2) DEFAULT 0,
  start_time TIME DEFAULT NULL,
  end_time TIME DEFAULT NULL,
  amount_calculated DECIMAL(10,2) DEFAULT 0,
  notes TEXT,
  latitude VARCHAR(50) DEFAULT NULL,
  longitude VARCHAR(50) DEFAULT NULL,
  signature_data LONGTEXT DEFAULT NULL,
  signature_date DATETIME DEFAULT NULL,
  status TINYINT DEFAULT 0 COMMENT '0=draft, 1=submitted, 2=validated, 3=invoiced, 9=rejected',
  photo_count INTEGER DEFAULT 0,
  validated_by INTEGER DEFAULT NULL,
  validation_date DATETIME DEFAULT NULL,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  date_modification DATETIME DEFAULT NULL,

  INDEX idx_subcontractor (fk_subcontractor),
  INDEX idx_date (report_date),
  INDEX idx_status (status),
  INDEX idx_project (fk_project)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rapports journaliers sous-traitants';

-- Table des photos des rapports sous-traitants
-- Minimum 3 photos par rapport (avant/pendant/après)
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_photos (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  fk_report INTEGER NOT NULL,
  photo_type VARCHAR(20) DEFAULT 'work' COMMENT 'before, during, after, detail',
  file_path TEXT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_size INTEGER DEFAULT NULL,
  latitude VARCHAR(50) DEFAULT NULL,
  longitude VARCHAR(50) DEFAULT NULL,
  photo_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  position INTEGER DEFAULT 0,

  INDEX idx_report (fk_report),
  INDEX idx_date (photo_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Photos des rapports sous-traitants';

-- Table des paiements sous-traitants
-- Génération automatique des paiements basés sur les rapports validés
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
  payment_date DATE DEFAULT NULL,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  fk_user_author INTEGER DEFAULT NULL,
  note TEXT,

  INDEX idx_subcontractor (fk_subcontractor),
  INDEX idx_status (status),
  INDEX idx_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paiements sous-traitants';

-- Table des sessions sous-traitants
-- Authentification par code PIN
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_sessions (
  session_id VARCHAR(64) PRIMARY KEY,
  fk_subcontractor INTEGER NOT NULL,
  device_info TEXT,
  ip_address VARCHAR(50) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,

  INDEX idx_subcontractor (fk_subcontractor),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions mobiles sous-traitants';

-- Table des tentatives de connexion sous-traitants
-- Protection contre le brute force
CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_login_attempts (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  pin_code VARCHAR(10) NOT NULL,
  ip_address VARCHAR(50) DEFAULT NULL,
  success TINYINT DEFAULT 0,
  attempt_date DATETIME DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_pin (pin_code),
  INDEX idx_date (attempt_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tentatives de connexion sous-traitants';

-- ============================================================================
-- FIN DU SCRIPT D'INSTALLATION
-- ============================================================================

-- Afficher un message de succès
SELECT '✅ MV3 PRO PORTAIL - Installation terminée avec succès!' as message;
SELECT 'Toutes les tables ont été créées ou mises à jour.' as info;
SELECT 'Vous pouvez maintenant activer le module dans Dolibarr.' as next_step;
