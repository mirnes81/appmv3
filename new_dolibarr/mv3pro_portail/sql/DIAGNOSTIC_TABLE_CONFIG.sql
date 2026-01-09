-- Diagnostic et correction de la table llx_mv3_config
-- Exécuter ce fichier si vous avez l'erreur "Champ 'name' inconnu"

-- ÉTAPE 1 : Voir la structure actuelle de la table
SHOW CREATE TABLE llx_mv3_config;

-- ÉTAPE 2 : Voir les colonnes actuelles
DESCRIBE llx_mv3_config;

-- ÉTAPE 3 : Si la table existe avec une mauvaise structure, la supprimer et recréer
-- ⚠️ ATTENTION : Ceci supprime toutes les données de configuration !
-- DROP TABLE IF EXISTS llx_mv3_config;

-- ÉTAPE 4 : Créer la table avec la bonne structure
CREATE TABLE IF NOT EXISTS llx_mv3_config (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    description TEXT,
    type VARCHAR(20) DEFAULT 'string',
    date_creation DATETIME,
    date_modification DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ÉTAPE 5 : Insérer les valeurs par défaut
INSERT INTO llx_mv3_config (name, value, description, type, date_creation) VALUES
('API_BASE_URL', '/custom/mv3pro_portail/api/v1/', 'URL de base de l''API', 'string', NOW()),
('PWA_BASE_URL', '/custom/mv3pro_portail/pwa_dist/', 'URL de base de la PWA', 'string', NOW()),
('DEV_MODE_ENABLED', '0', 'Activer le mode développement (1=ON, 0=OFF)', 'boolean', NOW()),
('DEBUG_CONSOLE_ENABLED', '0', 'Activer les logs console dans la PWA', 'boolean', NOW()),
('SERVICE_WORKER_CACHE_ENABLED', '1', 'Activer le cache du service worker', 'boolean', NOW()),
('PLANNING_ACCESS_POLICY', 'employee_own_only', 'Politique d''accès au planning (all=tout voir, employee_own_only=seulement ses RDV)', 'select', NOW()),
('ERROR_LOG_RETENTION_DAYS', '30', 'Nombre de jours de rétention des logs d''erreurs', 'number', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();
