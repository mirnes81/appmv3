/*
  # Ajout des fonctionnalités PRO au module rapports

  ## Nouveaux champs ajoutés

  ### GPS & Météo
  - `gps_latitude` (varchar 20) - Latitude GPS du lieu du rapport
  - `gps_longitude` (varchar 20) - Longitude GPS du lieu du rapport
  - `gps_accuracy` (decimal 10,2) - Précision GPS en mètres
  - `meteo_temperature` (decimal 5,2) - Température au moment du rapport
  - `meteo_condition` (varchar 100) - Condition météo (ensoleillé, pluie, etc.)

  ## Notes importantes
  - Ces champs sont optionnels (NULL autorisé)
  - Ils permettent de documenter les conditions de travail
  - GPS utile pour vérifier la présence sur site
  - Météo importante pour justifier des retards ou conditions difficiles

  ## Date de création
  - Décembre 2024

  ## Auteur
  - MV3 PRO - Module de gestion avancée
*/

-- Ajout des colonnes GPS
ALTER TABLE llx_mv3_rapport
ADD COLUMN IF NOT EXISTS gps_latitude VARCHAR(20) DEFAULT NULL COMMENT 'Latitude GPS du chantier';

ALTER TABLE llx_mv3_rapport
ADD COLUMN IF NOT EXISTS gps_longitude VARCHAR(20) DEFAULT NULL COMMENT 'Longitude GPS du chantier';

ALTER TABLE llx_mv3_rapport
ADD COLUMN IF NOT EXISTS gps_accuracy DECIMAL(10,2) DEFAULT NULL COMMENT 'Précision GPS en mètres';

-- Ajout des colonnes météo
ALTER TABLE llx_mv3_rapport
ADD COLUMN IF NOT EXISTS meteo_temperature DECIMAL(5,2) DEFAULT NULL COMMENT 'Température en °C';

ALTER TABLE llx_mv3_rapport
ADD COLUMN IF NOT EXISTS meteo_condition VARCHAR(100) DEFAULT NULL COMMENT 'Condition météo';

-- Ajout d'un index sur les coordonnées GPS pour optimisation future
CREATE INDEX IF NOT EXISTS idx_mv3_rapport_gps ON llx_mv3_rapport(gps_latitude, gps_longitude);

-- Ajout d'un index sur la date pour optimisation des stats
CREATE INDEX IF NOT EXISTS idx_mv3_rapport_date ON llx_mv3_rapport(date_rapport);

-- Ajout d'un index sur l'utilisateur et la date pour les stats personnelles
CREATE INDEX IF NOT EXISTS idx_mv3_rapport_user_date ON llx_mv3_rapport(fk_user, date_rapport);
