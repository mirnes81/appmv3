-- ============================================================================
-- SCRIPT DE VÉRIFICATION D'INSTALLATION - MV3 PRO PORTAIL
-- ============================================================================
-- Exécuter ce script après l'installation pour vérifier que tout est OK
-- ============================================================================

SELECT '═══════════════════════════════════════════════════════════════' as '';
SELECT '   VÉRIFICATION INSTALLATION MV3 PRO PORTAIL' as '';
SELECT '═══════════════════════════════════════════════════════════════' as '';
SELECT '' as '';

-- ============================================================================
-- TEST 1: Compter les tables installées
-- ============================================================================
SELECT '━━━ TEST 1: Nombre de tables MV3 ━━━' as '';
SELECT COUNT(*) as nb_tables_mv3,
       CASE
         WHEN COUNT(*) = 20 THEN '✅ OK - 20 tables détectées'
         ELSE '❌ ERREUR - Attendu: 20 tables'
       END as status
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'llx_mv3_%';

SELECT '' as '';

-- ============================================================================
-- TEST 2: Lister toutes les tables
-- ============================================================================
SELECT '━━━ TEST 2: Liste des tables installées ━━━' as '';
SELECT TABLE_NAME as table_name,
       ROUND(DATA_LENGTH / 1024, 2) as size_kb,
       TABLE_ROWS as nb_rows
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'llx_mv3_%'
ORDER BY TABLE_NAME;

SELECT '' as '';

-- ============================================================================
-- TEST 3: Vérifier les tables critiques
-- ============================================================================
SELECT '━━━ TEST 3: Tables critiques (authentification) ━━━' as '';
SELECT
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_mobile_users')
    THEN '✅ llx_mv3_mobile_users'
    ELSE '❌ llx_mv3_mobile_users MANQUANTE'
  END as auth_table_1,
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_mobile_sessions')
    THEN '✅ llx_mv3_mobile_sessions'
    ELSE '❌ llx_mv3_mobile_sessions MANQUANTE'
  END as auth_table_2,
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_rapport')
    THEN '✅ llx_mv3_rapport'
    ELSE '❌ llx_mv3_rapport MANQUANTE'
  END as rapport_table,
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_regie')
    THEN '✅ llx_mv3_regie'
    ELSE '❌ llx_mv3_regie MANQUANTE'
  END as regie_table;

SELECT '' as '';

-- ============================================================================
-- TEST 4: Vérifier les colonnes clés de la table mobile_users
-- ============================================================================
SELECT '━━━ TEST 4: Structure table mobile_users ━━━' as '';
SELECT
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_mobile_users' AND COLUMN_NAME = 'email')
    THEN '✅ Colonne email'
    ELSE '❌ Colonne email MANQUANTE'
  END as col_email,
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_mobile_users' AND COLUMN_NAME = 'password_hash')
    THEN '✅ Colonne password_hash'
    ELSE '❌ Colonne password_hash MANQUANTE'
  END as col_password,
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_mobile_users' AND COLUMN_NAME = 'is_active')
    THEN '✅ Colonne is_active'
    ELSE '❌ Colonne is_active MANQUANTE'
  END as col_active,
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_mobile_users' AND COLUMN_NAME = 'login_attempts')
    THEN '✅ Colonne login_attempts'
    ELSE '❌ Colonne login_attempts MANQUANTE'
  END as col_attempts;

SELECT '' as '';

-- ============================================================================
-- TEST 5: Vérifier les colonnes GPS/Météo dans rapport
-- ============================================================================
SELECT '━━━ TEST 5: Colonnes GPS/Météo dans rapport ━━━' as '';
SELECT
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_rapport' AND COLUMN_NAME = 'gps_latitude')
    THEN '✅ Colonne gps_latitude'
    ELSE '❌ Colonne gps_latitude MANQUANTE'
  END as col_gps_lat,
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_rapport' AND COLUMN_NAME = 'gps_longitude')
    THEN '✅ Colonne gps_longitude'
    ELSE '❌ Colonne gps_longitude MANQUANTE'
  END as col_gps_lon,
  CASE
    WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'llx_mv3_rapport' AND COLUMN_NAME = 'meteo_temperature')
    THEN '✅ Colonne meteo_temperature'
    ELSE '❌ Colonne meteo_temperature MANQUANTE'
  END as col_meteo_temp;

SELECT '' as '';

-- ============================================================================
-- TEST 6: Vérifier les index
-- ============================================================================
SELECT '━━━ TEST 6: Index sur les tables critiques ━━━' as '';
SELECT TABLE_NAME as table_name,
       INDEX_NAME as index_name,
       COLUMN_NAME as indexed_column
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('llx_mv3_mobile_users', 'llx_mv3_mobile_sessions', 'llx_mv3_rapport')
  AND INDEX_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, INDEX_NAME;

SELECT '' as '';

-- ============================================================================
-- TEST 7: Vérifier les moteurs de tables
-- ============================================================================
SELECT '━━━ TEST 7: Moteur de stockage ━━━' as '';
SELECT ENGINE as moteur,
       COUNT(*) as nb_tables,
       CASE
         WHEN ENGINE = 'InnoDB' THEN '✅ InnoDB (recommandé)'
         ELSE '⚠️ Autre moteur détecté'
       END as status
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'llx_mv3_%'
GROUP BY ENGINE;

SELECT '' as '';

-- ============================================================================
-- TEST 8: Vérifier le charset
-- ============================================================================
SELECT '━━━ TEST 8: Charset et collation ━━━' as '';
SELECT TABLE_COLLATION as collation,
       COUNT(*) as nb_tables,
       CASE
         WHEN TABLE_COLLATION LIKE 'utf8mb4%' THEN '✅ utf8mb4 (correct)'
         ELSE '⚠️ Autre charset'
       END as status
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'llx_mv3_%'
GROUP BY TABLE_COLLATION;

SELECT '' as '';

-- ============================================================================
-- TEST 9: Statistiques utilisateurs mobiles
-- ============================================================================
SELECT '━━━ TEST 9: Utilisateurs mobiles ━━━' as '';
SELECT
  COALESCE(COUNT(*), 0) as nb_users_total,
  COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as nb_users_actifs,
  COALESCE(SUM(CASE WHEN last_login IS NOT NULL THEN 1 ELSE 0 END), 0) as nb_users_connectes_aumoinsunefois
FROM llx_mv3_mobile_users;

SELECT '' as '';

-- ============================================================================
-- TEST 10: Vérifier les contraintes de clés étrangères
-- ============================================================================
SELECT '━━━ TEST 10: Clés étrangères (échantillon) ━━━' as '';
SELECT CONSTRAINT_NAME as foreign_key,
       TABLE_NAME as from_table,
       REFERENCED_TABLE_NAME as to_table
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'llx_mv3_%'
  AND REFERENCED_TABLE_NAME IS NOT NULL
LIMIT 10;

SELECT '' as '';

-- ============================================================================
-- RÉSUMÉ FINAL
-- ============================================================================
SELECT '═══════════════════════════════════════════════════════════════' as '';
SELECT '   RÉSUMÉ DE VÉRIFICATION' as '';
SELECT '═══════════════════════════════════════════════════════════════' as '';

SELECT
  CASE
    WHEN (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'llx_mv3_%') = 20
    THEN '✅ Installation COMPLÈTE - 20/20 tables'
    ELSE CONCAT('⚠️ Installation PARTIELLE - ',
                (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'llx_mv3_%'),
                '/20 tables')
  END as status_installation;

SELECT '' as '';
SELECT 'Pour créer un utilisateur test, exécuter:' as next_step;
SELECT "INSERT INTO llx_mv3_mobile_users (email, password_hash, firstname, lastname, is_active, entity) VALUES ('test@mv3pro.ch', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', 1, 1);" as sql_command;
SELECT '' as '';
SELECT '✅ Vérification terminée!' as '';
