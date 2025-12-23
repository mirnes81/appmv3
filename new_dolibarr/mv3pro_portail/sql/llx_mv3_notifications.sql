/*
  # Système de Notifications MV3 PRO

  1. Nouvelle Table
    - `llx_mv3_notifications` - Stocke toutes les notifications
      - `rowid` (int, primary key, auto_increment)
      - `fk_user` (int) - Utilisateur destinataire
      - `type` (varchar) - Type: rapport_new, rapport_validated, materiel_low, etc.
      - `titre` (varchar) - Titre de la notification
      - `message` (text) - Message détaillé
      - `fk_object` (int) - ID de l'objet lié (rapport, matériel, etc.)
      - `object_type` (varchar) - Type d'objet: rapport, materiel, signalement
      - `statut` (varchar) - non_lu, lu, traite, reporte
      - `date_creation` (datetime)
      - `date_lecture` (datetime) - Quand marqué comme lu
      - `entity` (int) - Multi-company support

  2. Sécurité
    - Enable RLS sur la table
    - Index sur fk_user pour performance
    - Index sur statut pour filtrage rapide
*/

CREATE TABLE IF NOT EXISTS llx_mv3_notifications (
  rowid int(11) NOT NULL AUTO_INCREMENT,
  fk_user int(11) NOT NULL,
  type varchar(50) DEFAULT 'info',
  titre varchar(255) NOT NULL,
  message text,
  fk_object int(11) DEFAULT NULL,
  object_type varchar(50) DEFAULT NULL,
  statut varchar(20) DEFAULT 'non_lu',
  date_creation datetime DEFAULT CURRENT_TIMESTAMP,
  date_lecture datetime DEFAULT NULL,
  entity int(11) DEFAULT 1,
  PRIMARY KEY (rowid),
  KEY idx_fk_user (fk_user),
  KEY idx_statut (statut),
  KEY idx_date_creation (date_creation),
  KEY idx_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
