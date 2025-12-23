-- Migration: Ajout des colonnes de validation et photos
-- Date: 2025-11-02
-- Description: Ajoute les colonnes manquantes pour la fonctionnalité de validation des rapports et photos

-- ========================================
-- TABLE: llx_mv3_rapport
-- ========================================

-- Ajouter fk_user_validation si elle n'existe pas
ALTER TABLE llx_mv3_rapport
ADD COLUMN IF NOT EXISTS fk_user_validation INT DEFAULT NULL AFTER statut;

-- Ajouter date_validation si elle n'existe pas
ALTER TABLE llx_mv3_rapport
ADD COLUMN IF NOT EXISTS date_validation DATETIME DEFAULT NULL AFTER fk_user_validation;

-- Ajouter un index sur fk_user_validation
ALTER TABLE llx_mv3_rapport
ADD INDEX IF NOT EXISTS idx_user_validation (fk_user_validation);

-- ========================================
-- TABLE: llx_mv3_rapport_photo
-- ========================================

-- Ajouter la colonne 'ordre' si elle n'existe pas
ALTER TABLE llx_mv3_rapport_photo
ADD COLUMN IF NOT EXISTS ordre INT DEFAULT 0 AFTER filename;

-- Ajouter la colonne 'description' si elle n'existe pas
ALTER TABLE llx_mv3_rapport_photo
ADD COLUMN IF NOT EXISTS description VARCHAR(255) AFTER filename;

-- Message de confirmation
SELECT 'Migration terminée - Toutes les colonnes ont été ajoutées avec succès' as Status;
