-- ============================================
-- CRÉER L'UTILISATEUR DIAGNOSTIC - EXÉCUTER MAINTENANT !
-- ============================================

-- 1. Supprimer l'ancien utilisateur s'il existe
DELETE FROM llx_mv3_mobile_users WHERE email = 'diagnostic@test.local';

-- 2. Créer le nouvel utilisateur
-- Email: diagnostic@test.local
-- Password: DiagTest2026!
INSERT INTO llx_mv3_mobile_users (
  email,
  password_hash,
  dolibarr_user_id,
  firstname,
  lastname,
  phone,
  role,
  pin_code,
  is_active,
  last_login,
  login_attempts,
  locked_until,
  device_token,
  created_at,
  updated_at
) VALUES (
  'diagnostic@test.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  1,
  'Diagnostic',
  'QA',
  NULL,
  'diagnostic',
  NULL,
  1,
  NULL,
  0,
  NULL,
  NULL,
  NOW(),
  NOW()
);

-- 3. Vérifier la création
SELECT
  rowid as id,
  email,
  firstname,
  lastname,
  role,
  is_active as actif,
  login_attempts as tentatives,
  created_at as cree_le,
  CASE
    WHEN password_hash IS NOT NULL AND LENGTH(password_hash) > 50 THEN '✓ Password hash OK'
    ELSE '✗ Problème hash'
  END as password_status
FROM llx_mv3_mobile_users
WHERE email = 'diagnostic@test.local';

-- ============================================
-- RÉSULTAT ATTENDU :
-- ============================================
-- id | email | firstname | lastname | role | actif | tentatives | password_status
-- 1  | diagnostic@test.local | Diagnostic | QA | diagnostic | 1 | 0 | ✓ Password hash OK
--
-- ============================================
-- CREDENTIALS POUR LE TEST :
-- ============================================
-- Email: diagnostic@test.local
-- Password: DiagTest2026!
--
-- TEST CURL :
-- curl -X POST "https://dolibarr.mirnes.ch/custom/mv3pro_portail/api/v1/auth/login.php" \
--   -H "Content-Type: application/json" \
--   -d '{"email":"diagnostic@test.local","password":"DiagTest2026!"}' | jq .
-- ============================================
