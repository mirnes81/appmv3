-- MISE À JOUR TABLE RAPPORTS - VERSION CARRELEUR
-- Ajout des colonnes spécifiques métier carrelage + client
-- Compatible MySQL 5.5, 5.6, 5.7, 8.0+

-- IMPORTANT : Si vous avez une erreur "Duplicate column name", c'est normal !
-- Cela signifie que la colonne existe déjà. Passez à la commande suivante.

ALTER TABLE llx_mv3_rapport
ADD COLUMN fk_soc INT COMMENT 'ID du client (societe)',
ADD COLUMN surface_carrelee DECIMAL(10,2) COMMENT 'Surface en m²',
ADD COLUMN type_carrelage VARCHAR(50) COMMENT 'Intérieur/Extérieur',
ADD COLUMN zone_pose VARCHAR(50) COMMENT 'Sol/Mur/Autre',
ADD COLUMN format_carreaux VARCHAR(100) COMMENT 'Ex: 30x30, 60x60',
ADD COLUMN type_pose VARCHAR(100) COMMENT 'Droite/Diagonale/Chevron',
ADD COLUMN colle_utilisee VARCHAR(255) COMMENT 'Type de colle',
ADD COLUMN joint_utilise VARCHAR(255) COMMENT 'Type de joint',
ADD COLUMN materiel_manquant TEXT COMMENT 'Matériel à prévoir',
ADD COLUMN problemes_rencontres TEXT COMMENT 'Difficultés du jour',
ADD COLUMN avancement_pourcent INT DEFAULT 0 COMMENT '% avancement chantier';

-- Mise à jour table photos pour catégorisation
ALTER TABLE llx_mv3_rapport_photo
ADD COLUMN categorie VARCHAR(50) DEFAULT 'general' COMMENT 'avant/pendant/apres/probleme',
ADD COLUMN zone_photo VARCHAR(100) COMMENT 'Zone concernée par la photo',
ADD COLUMN legende TEXT COMMENT 'Description de la photo';
