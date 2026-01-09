/*
  ═══════════════════════════════════════════════════════════════
  Script SQL - Créer l'utilisateur diagnostic
  ═══════════════════════════════════════════════════════════════

  Ce script crée un utilisateur mobile pour les tests diagnostics.

  Credentials:
    Email: diagnostic@mv3pro.local
    Password: DiagMV3Pro2026!

  IMPORTANT: Ce script utilise le VRAI schéma de llx_mv3_mobile_users
  ═══════════════════════════════════════════════════════════════
*/

-- Supprimer l'utilisateur diagnostic s'il existe déjà
DELETE FROM llx_mv3_mobile_users WHERE email = 'diagnostic@mv3pro.local';

-- Créer l'utilisateur diagnostic
-- Password hash pour 'DiagMV3Pro2026!' (bcrypt cost 10)
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
  login_attempts,
  locked_until,
  device_token,
  created_at,
  updated_at
) VALUES (
  'diagnostic@mv3pro.local',
  '$2y$10$YGQzNWE3MTJjNzg5YjNkZeF5xK3vYmN4ZGViNjE3MzBkNWJhNGQ2NzJkYWViNjE3MzBkNWJhNGQ2Nz',
  NULL,
  'Diagnostic',
  'System',
  NULL,
  'diagnostic',
  NULL,
  1,
  0,
  NULL,
  NULL,
  NOW(),
  NOW()
);

-- Vérifier la création
SELECT
  rowid as id,
  email,
  firstname,
  lastname,
  role,
  is_active,
  created_at,
  CASE
    WHEN password_hash IS NOT NULL AND LENGTH(password_hash) > 0 THEN '✓ Hash présent'
    ELSE '✗ Hash manquant'
  END as password_status
FROM llx_mv3_mobile_users
WHERE email = 'diagnostic@mv3pro.local';

-- Afficher le message de confirmation
SELECT CONCAT(
  '✅ Utilisateur diagnostic créé avec succès!\n',
  'Email: diagnostic@mv3pro.local\n',
  'Password: DiagMV3Pro2026!\n',
  'ID: ', LAST_INSERT_ID()
) as message;

/*
  ═══════════════════════════════════════════════════════════════
  NOTES IMPORTANTES
  ═══════════════════════════════════════════════════════════════

  1. Le password_hash ci-dessus est un exemple. Pour le générer en PHP:

     <?php
     $password = 'DiagMV3Pro2026!';
     $hash = password_hash($password, PASSWORD_DEFAULT);
     echo $hash;
     ?>

  2. Si le hash ne fonctionne pas, utiliser le script PHP ci-dessous pour
     créer l'utilisateur avec un hash correct.

  3. Vérifier que la table llx_mv3_mobile_users existe:
     SHOW TABLES LIKE 'llx_mv3_mobile_users';

  4. Si la table n'existe pas, exécuter d'abord:
     SOURCE /path/to/llx_mv3_mobile_users.sql;

  ═══════════════════════════════════════════════════════════════
*/
