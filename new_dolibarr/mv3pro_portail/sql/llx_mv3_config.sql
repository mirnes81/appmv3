-- Table de configuration du module MV3 PRO Portail
-- Stocke les paramètres configurables du module
--
-- ⚠️ SI VOUS AVEZ L'ERREUR "#1054 - Champ 'name' inconnu" :
-- Utilisez le fichier llx_mv3_config_SAFE.sql ou consultez FIX_ERREUR_1054.md

CREATE TABLE IF NOT EXISTS llx_mv3_config (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    description TEXT,
    type VARCHAR(20) DEFAULT 'string',
    date_creation DATETIME,
    date_modification DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insertion des valeurs par défaut
-- Si cette partie échoue, exécutez les INSERT un par un (voir llx_mv3_config_SAFE.sql)
INSERT INTO llx_mv3_config (name, value, description, type, date_creation) VALUES
('API_BASE_URL', '/custom/mv3pro_portail/api/v1/', 'URL de base de l''API', 'string', NOW()),
('PWA_BASE_URL', '/custom/mv3pro_portail/pwa_dist/', 'URL de base de la PWA', 'string', NOW()),
('DEV_MODE_ENABLED', '0', 'Activer le mode développement (1=ON, 0=OFF)', 'boolean', NOW()),
('DEBUG_CONSOLE_ENABLED', '0', 'Activer les logs console dans la PWA', 'boolean', NOW()),
('SERVICE_WORKER_CACHE_ENABLED', '1', 'Activer le cache du service worker', 'boolean', NOW()),
('PLANNING_ACCESS_POLICY', 'employee_own_only', 'Politique d''accès au planning (all=tout voir, employee_own_only=seulement ses RDV)', 'select', NOW()),
('ERROR_LOG_RETENTION_DAYS', '30', 'Nombre de jours de rétention des logs d''erreurs', 'number', NOW()),
('DIAGNOSTIC_USER_EMAIL', 'diagnostic@test.local', 'Email utilisateur pour tests diagnostic QA', 'string', NOW()),
('DIAGNOSTIC_USER_PASSWORD', 'DiagTest2026!', 'Mot de passe utilisateur pour tests diagnostic QA', 'string', NOW())
ON DUPLICATE KEY UPDATE date_modification=NOW();
