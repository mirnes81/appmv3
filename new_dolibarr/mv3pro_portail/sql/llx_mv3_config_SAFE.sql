-- Installation sécurisée de la table llx_mv3_config
-- Si vous avez l'erreur "Champ 'name' inconnu", utilisez ce fichier

-- MÉTHODE 1 : Création avec vérification
-- Exécutez ces requêtes une par une dans phpMyAdmin

-- 1. Vérifier si la table existe déjà
SELECT COUNT(*) as table_exists
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name = 'llx_mv3_config';

-- 2. Si table_exists = 1, voir sa structure
-- DESCRIBE llx_mv3_config;

-- 3. Si la structure est incorrecte, supprimer la table
-- ⚠️ ATTENTION : Ceci supprime toutes les données !
-- DROP TABLE llx_mv3_config;

-- 4. Créer la table avec la bonne structure
CREATE TABLE IF NOT EXISTS llx_mv3_config (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    description TEXT,
    type VARCHAR(20) DEFAULT 'string',
    date_creation DATETIME,
    date_modification DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 5. Vérifier que la table a été créée correctement
DESCRIBE llx_mv3_config;

-- 6. Insérer les valeurs par défaut UNE PAR UNE
INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('API_BASE_URL', '/custom/mv3pro_portail/api/v1/', 'URL de base de l''API', 'string', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('PWA_BASE_URL', '/custom/mv3pro_portail/pwa_dist/', 'URL de base de la PWA', 'string', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('DEV_MODE_ENABLED', '0', 'Activer le mode développement (1=ON, 0=OFF)', 'boolean', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('DEBUG_CONSOLE_ENABLED', '0', 'Activer les logs console dans la PWA', 'boolean', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('SERVICE_WORKER_CACHE_ENABLED', '1', 'Activer le cache du service worker', 'boolean', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('PLANNING_ACCESS_POLICY', 'employee_own_only', 'Politique d''accès au planning (all=tout voir, employee_own_only=seulement ses RDV)', 'select', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

INSERT INTO llx_mv3_config (name, value, description, type, date_creation)
VALUES ('ERROR_LOG_RETENTION_DAYS', '30', 'Nombre de jours de rétention des logs d''erreurs', 'number', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();

-- 7. Vérifier que les données ont été insérées
SELECT * FROM llx_mv3_config;
