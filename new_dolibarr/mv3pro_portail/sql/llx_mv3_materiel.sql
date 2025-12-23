/*
  # Table matériel MV-3 PRO

  1. Description
    - Gestion du matériel (outils, machines, véhicules)
    - Suivi des affectations (utilisateur, projet)
    - Gestion maintenance et état

  2. Structure principale
    - `ref` : Référence unique (MAT-001, MAT-002...)
    - `nom` : Nom du matériel
    - `type` : outil, machine, vehicule, consommable
    - `statut` : disponible, en_service, maintenance, hors_service

  3. Affectations
    - `fk_user_assigne` : Utilisateur assigné
    - `fk_projet_assigne` : Projet assigné

  4. Maintenance
    - `date_derniere_maintenance` : Dernière maintenance effectuée
    - `date_prochaine_maintenance` : Maintenance à prévoir

  5. Sécurité
    - Accessible selon droits module
    - Historique complet des modifications
*/

CREATE TABLE IF NOT EXISTS llx_mv3_materiel (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  ref VARCHAR(50) NOT NULL,
  nom VARCHAR(255) NOT NULL,
  type ENUM('outil', 'machine', 'vehicule', 'consommable') DEFAULT 'outil',
  marque VARCHAR(100),
  modele VARCHAR(100),
  numero_serie VARCHAR(100),
  qrcode VARCHAR(100),
  statut ENUM('disponible', 'en_service', 'maintenance', 'hors_service') DEFAULT 'disponible',
  fk_user_assigne INT DEFAULT NULL,
  fk_projet_assigne INT DEFAULT NULL,
  date_achat DATE,
  valeur_achat DECIMAL(10,2),
  date_derniere_maintenance DATE,
  date_prochaine_maintenance DATE,
  observations TEXT,
  photo VARCHAR(255),
  fk_user_creation INT NOT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_mv3_materiel_ref (entity, ref),
  KEY idx_mv3_materiel_type (type),
  KEY idx_mv3_materiel_statut (statut),
  KEY idx_mv3_materiel_user (fk_user_assigne),
  KEY idx_mv3_materiel_projet (fk_projet_assigne),
  KEY idx_mv3_materiel_qrcode (qrcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*
  # Table historique matériel

  1. Description
    - Historique complet des mouvements
    - Affectations / désaffectations
    - Maintenances
    - Changements de statut

  2. Types d'événements
    - creation : Création matériel
    - affectation_user : Affectation utilisateur
    - affectation_projet : Affectation projet
    - liberation : Libération
    - maintenance : Maintenance effectuée
    - changement_statut : Changement statut
    - modification : Modification infos
*/

CREATE TABLE IF NOT EXISTS llx_mv3_materiel_historique (
  rowid INT AUTO_INCREMENT PRIMARY KEY,
  entity INT NOT NULL DEFAULT 1,
  fk_materiel INT NOT NULL,
  type_evenement ENUM('creation', 'affectation_user', 'affectation_projet', 'liberation', 'maintenance', 'changement_statut', 'modification') NOT NULL,
  ancien_statut VARCHAR(50),
  nouveau_statut VARCHAR(50),
  fk_user_ancien INT,
  fk_user_nouveau INT,
  fk_projet_ancien INT,
  fk_projet_nouveau INT,
  commentaire TEXT,
  fk_user_action INT NOT NULL,
  date_evenement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mv3_materiel_hist_materiel (fk_materiel),
  KEY idx_mv3_materiel_hist_date (date_evenement),
  FOREIGN KEY (fk_materiel) REFERENCES llx_mv3_materiel(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données exemple (optionnel pour tests)
-- INSERT INTO llx_mv3_materiel (entity, ref, nom, type, statut, fk_user_creation) VALUES
-- (1, 'MAT-001', 'Perceuse sans fil Bosch', 'outil', 'disponible', 1),
-- (1, 'MAT-002', 'Scie circulaire Makita', 'outil', 'disponible', 1),
-- (1, 'MAT-003', 'Bétonnière 150L', 'machine', 'disponible', 1);
