<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un utilisateur mobile - MV3 PRO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #0891b2;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #0891b2;
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #0891b2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #0e7490;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
        }
        .btn:active {
            transform: translateY(0);
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .alert strong {
            display: block;
            margin-bottom: 5px;
        }
        .info-box {
            background: #f3f4f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #4b5563;
        }
        .info-box strong {
            color: #1f2937;
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🏗️ Créer un utilisateur mobile</h1>
    <p class="subtitle">MV3 PRO - Gestion des accès mobiles</p>

    <?php
    // Chargement Dolibarr (bootstrap file, not a namespaced class)
    $res = 0;
    if (!$res && file_exists(__DIR__ . "/../../../../main.inc.php")) {
        $res = @include_once __DIR__ . "/../../../../main.inc.php";
    }
    if (!$res && file_exists(__DIR__ . "/../../../../../main.inc.php")) {
        $res = @include_once __DIR__ . "/../../../../../main.inc.php";
    }

    if (!$res) {
        echo '<div class="alert alert-error"><strong>Erreur</strong> Impossible de charger Dolibarr</div>';
        exit;
    }

    global $db, $user;

    // Vérifier que l'utilisateur Dolibarr est connecté et admin
    if (!$user || !$user->admin) {
        echo '<div class="alert alert-error"><strong>Accès refusé</strong> Vous devez être administrateur Dolibarr pour accéder à cette page.</div>';
        exit;
    }

    // Traitement du formulaire
    $success = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = trim($_POST['role'] ?? 'employee');

        // Validation
        if (empty($email) || empty($password) || empty($firstname) || empty($lastname)) {
            $error = 'Tous les champs obligatoires doivent être remplis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } elseif (strlen($password) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } else {
            // Vérifier si l'email existe déjà
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_mobile_users";
            $sql .= " WHERE email = '".$db->escape($email)."'";
            $resql = $db->query($sql);

            if ($resql && $db->num_rows($resql) > 0) {
                $error = "Cet email est déjà utilisé.";
            } else {
                // Créer le hash du mot de passe
                $hash = password_hash($password, PASSWORD_BCRYPT);

                // Insérer l'utilisateur
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_mobile_users";
                $sql .= " (email, password_hash, firstname, lastname, phone, role, is_active)";
                $sql .= " VALUES (";
                $sql .= " '".$db->escape($email)."',";
                $sql .= " '".$db->escape($hash)."',";
                $sql .= " '".$db->escape($firstname)."',";
                $sql .= " '".$db->escape($lastname)."',";
                $sql .= " '".$db->escape($phone)."',";
                $sql .= " '".$db->escape($role)."',";
                $sql .= " 1";
                $sql .= ")";

                if ($db->query($sql)) {
                    $success = "✅ Utilisateur créé avec succès!<br>Email: <strong>$email</strong><br>Mot de passe: <strong>$password</strong>";
                    // Reset form
                    $_POST = [];
                } else {
                    $error = "Erreur lors de la création: " . $db->lasterror();
                }
            }
        }
    }
    ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><strong>Erreur</strong> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="info-box">
        <strong>ℹ️ Important</strong>
        Les utilisateurs créés ici pourront se connecter à l'application mobile PWA.
        Ils n'auront PAS accès au Dolibarr principal.
    </div>

    <form method="POST">
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   placeholder="utilisateur@example.com">
        </div>

        <div class="form-group">
            <label for="password">Mot de passe *</label>
            <input type="text" id="password" name="password" required
                   minlength="6"
                   placeholder="Minimum 6 caractères">
            <small style="color: #6b7280; font-size: 12px;">Conseil: Générez un mot de passe fort</small>
        </div>

        <div class="form-group">
            <label for="firstname">Prénom *</label>
            <input type="text" id="firstname" name="firstname" required
                   value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>"
                   placeholder="Jean">
        </div>

        <div class="form-group">
            <label for="lastname">Nom *</label>
            <input type="text" id="lastname" name="lastname" required
                   value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>"
                   placeholder="Dupont">
        </div>

        <div class="form-group">
            <label for="phone">Téléphone</label>
            <input type="tel" id="phone" name="phone"
                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                   placeholder="+33 6 12 34 56 78">
        </div>

        <div class="form-group">
            <label for="role">Rôle</label>
            <select id="role" name="role">
                <option value="employee">Employé</option>
                <option value="supervisor">Superviseur</option>
                <option value="manager">Manager</option>
            </select>
        </div>

        <button type="submit" class="btn">Créer l'utilisateur</button>
    </form>

    <div style="margin-top: 30px; text-align: center;">
        <a href="manage_users.php" style="color: #0891b2; text-decoration: none; font-size: 14px;">
            ← Retour à la gestion des utilisateurs
        </a>
    </div>
</div>

</body>
</html>
