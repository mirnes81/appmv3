-- Création utilisateur mobile: mirnes@mv-3pro.ch
-- Mot de passe: mirnes12345

-- 1. Vérifier si l'utilisateur existe déjà
SELECT rowid, email, is_active FROM llx_mv3_mobile_users WHERE email = 'mirnes@mv-3pro.ch';

-- 2. Si existe, le supprimer (optionnel, décommentez si besoin)
-- DELETE FROM llx_mv3_mobile_users WHERE email = 'mirnes@mv-3pro.ch';

-- 3. Créer l'utilisateur mobile
-- Note: Le hash bcrypt de 'mirnes12345' est généré avec password_hash()
-- Pour PHP: password_hash('mirnes12345', PASSWORD_BCRYPT)
INSERT INTO llx_mv3_mobile_users (
    email,
    password_hash,
    firstname,
    lastname,
    phone,
    role,
    is_active,
    can_create_rapport,
    can_create_regie,
    can_create_sens_pose,
    can_view_planning,
    can_manage_materiel,
    dolibarr_user_id,
    created_at,
    entity
) VALUES (
    'mirnes@mv-3pro.ch',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- mirnes12345
    'Mirnes',
    'MV3 PRO',
    '',
    'OUVRIER',
    1,
    1,
    1,
    1,
    1,
    1,
    NULL, -- Lier à un user Dolibarr si nécessaire
    NOW(),
    1
);

-- 4. Vérifier la création
SELECT 
    rowid,
    email,
    firstname,
    lastname,
    role,
    is_active,
    created_at
FROM llx_mv3_mobile_users 
WHERE email = 'mirnes@mv-3pro.ch';

-- 5. Notes
-- ============================================================================
-- Email:     mirnes@mv-3pro.ch
-- Password:  mirnes12345
-- Rôle:      OUVRIER
-- Droits:    Tous (rapports, régie, sens pose, planning, matériel)
-- ============================================================================
-- 
-- Pour tester le login:
-- 
-- curl -X POST http://your-dolibarr/custom/mv3pro_portail/mobile_app/api/auth.php?action=login \
--   -H "Content-Type: application/json" \
--   -d '{"email":"mirnes@mv-3pro.ch","password":"mirnes12345"}'
-- 
-- ============================================================================
