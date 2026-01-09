/**
 * Création utilisateur de test pour diagnostic QA
 *
 * Email : diagnostic@test.local
 * Password : DiagTest2026!
 *
 * À exécuter une seule fois sur la base de données Dolibarr
 */

-- Vérifier si l'utilisateur existe déjà
SELECT COUNT(*) as count
FROM llx_mv3_mobile_users
WHERE email = 'diagnostic@test.local';

-- Si count = 0, exécuter l'INSERT ci-dessous :

INSERT INTO llx_mv3_mobile_users (
    fk_user,
    email,
    password_hash,
    nom,
    prenom,
    role,
    active,
    date_creation
) VALUES (
    1, -- Admin Dolibarr (ID 1)
    'diagnostic@test.local',
    '$2y$10$YourHashedPasswordHere', -- Voir ci-dessous comment générer
    'Diagnostic',
    'QA',
    'admin',
    1,
    NOW()
);

/**
 * IMPORTANT : Générer le hash du mot de passe
 *
 * Méthode 1 : PHP en ligne de commande
 * ----------------------------------
 * php -r "echo password_hash('DiagTest2026!', PASSWORD_DEFAULT);"
 *
 * Méthode 2 : Script PHP temporaire
 * ----------------------------------
 * Créer un fichier hash_password.php dans /custom/mv3pro_portail/ :
 *
 * <?php
 * echo password_hash('DiagTest2026!', PASSWORD_DEFAULT);
 * ?>
 *
 * Puis visiter : https://dolibarr.mirnes.ch/custom/mv3pro_portail/hash_password.php
 * Copier le hash généré et remplacer dans l'INSERT ci-dessus
 * SUPPRIMER le fichier hash_password.php après utilisation !
 *
 * Méthode 3 : Utiliser un utilisateur existant
 * --------------------------------------------
 * Si vous préférez utiliser un utilisateur existant, mettez à jour la config :
 *
 * UPDATE llx_mv3_config
 * SET value = 'email@existant.com'
 * WHERE name = 'DIAGNOSTIC_USER_EMAIL';
 *
 * UPDATE llx_mv3_config
 * SET value = 'MotDePasseExistant'
 * WHERE name = 'DIAGNOSTIC_USER_PASSWORD';
 */

-- Vérifier la création
SELECT id, email, nom, prenom, role, active, date_creation
FROM llx_mv3_mobile_users
WHERE email = 'diagnostic@test.local';
