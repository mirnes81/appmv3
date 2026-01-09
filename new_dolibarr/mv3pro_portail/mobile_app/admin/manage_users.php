<?php
/**
 * Administration des utilisateurs mobiles
 * Permet de créer, modifier et supprimer des comptes employés
 */

$res = 0;
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res && file_exists("../../../../../main.inc.php")) $res = @include "../../../../../main.inc.php";

if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Vérifier les permissions (admin seulement)
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$user_id = GETPOST('id', 'int');
$error = '';
$success = '';

// Fonction pour hasher le mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Traitement des actions
if ($action == 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(GETPOST('email', 'email'));
    $password = GETPOST('password', 'password');
    $firstname = trim(GETPOST('firstname', 'alpha'));
    $lastname = trim(GETPOST('lastname', 'alpha'));
    $phone = trim(GETPOST('phone', 'alpha'));
    $role = GETPOST('role', 'alpha');
    $dolibarr_user = GETPOST('dolibarr_user', 'int');

    if ($email && $password && $firstname && $lastname) {
        // VALIDATION: Lien Dolibarr obligatoire pour employee et manager
        if (in_array($role, ['employee', 'manager']) && (!$dolibarr_user || $dolibarr_user <= 0)) {
            $error = "⚠️ ERREUR: Le lien avec un utilisateur Dolibarr est OBLIGATOIRE pour les rôles 'Employé' et 'Manager'. Sans ce lien, l'utilisateur ne pourra pas utiliser l'application mobile correctement.";
        }
        // Vérifier si l'email existe déjà
        elseif ($resql_check = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_mobile_users WHERE email = '".$db->escape($email)."'")) {
            if ($db->num_rows($resql_check) > 0) {
                $error = "Un compte avec cet email existe déjà";
            }
        }

        if (!$error) {
            $password_hash = hashPassword($password);

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_mobile_users
                    (email, password_hash, firstname, lastname, phone, role, dolibarr_user_id, is_active)
                    VALUES (
                        '".$db->escape($email)."',
                        '".$db->escape($password_hash)."',
                        '".$db->escape($firstname)."',
                        '".$db->escape($lastname)."',
                        '".$db->escape($phone)."',
                        '".$db->escape($role)."',
                        ".($dolibarr_user > 0 ? (int)$dolibarr_user : "NULL").",
                        1
                    )";

            if ($db->query($sql)) {
                $success = "Utilisateur créé avec succès";
            } else {
                $error = "Erreur lors de la création : ".$db->lasterror();
            }
        }
    } else {
        $error = "Tous les champs obligatoires doivent être remplis";
    }
}

if ($action == 'update' && $_SERVER['REQUEST_METHOD'] === 'POST' && $user_id > 0) {
    $email = trim(GETPOST('email', 'email'));
    $firstname = trim(GETPOST('firstname', 'alpha'));
    $lastname = trim(GETPOST('lastname', 'alpha'));
    $phone = trim(GETPOST('phone', 'alpha'));
    $role = GETPOST('role', 'alpha');
    $dolibarr_user = GETPOST('dolibarr_user', 'int');
    $is_active = GETPOST('is_active', 'int');

    // VALIDATION: Lien Dolibarr obligatoire pour employee et manager
    if (in_array($role, ['employee', 'manager']) && (!$dolibarr_user || $dolibarr_user <= 0)) {
        $error = "⚠️ ERREUR: Le lien avec un utilisateur Dolibarr est OBLIGATOIRE pour les rôles 'Employé' et 'Manager'. Sans ce lien, l'utilisateur ne pourra pas utiliser l'application mobile correctement.";
    } else {
        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_mobile_users SET
            email = '".$db->escape($email)."',
            firstname = '".$db->escape($firstname)."',
            lastname = '".$db->escape($lastname)."',
            phone = '".$db->escape($phone)."',
            role = '".$db->escape($role)."',
            dolibarr_user_id = ".($dolibarr_user > 0 ? (int)$dolibarr_user : "NULL").",
            is_active = ".(int)$is_active."
            WHERE rowid = ".(int)$user_id;

        if ($db->query($sql)) {
            $success = "Utilisateur modifié avec succès";
        } else {
            $error = "Erreur lors de la modification : ".$db->lasterror();
        }
    }
}

if ($action == 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST' && $user_id > 0) {
    $new_password = GETPOST('new_password', 'password');

    if ($new_password) {
        $password_hash = hashPassword($new_password);

        $sql = "UPDATE ".MAIN_DB_PREFIX."mv3_mobile_users SET
                password_hash = '".$db->escape($password_hash)."',
                login_attempts = 0,
                locked_until = NULL
                WHERE rowid = ".(int)$user_id;

        if ($db->query($sql)) {
            $success = "Mot de passe réinitialisé avec succès";
        } else {
            $error = "Erreur lors de la réinitialisation : ".$db->lasterror();
        }
    }
}

if ($action == 'delete' && $user_id > 0) {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_mobile_users WHERE rowid = ".(int)$user_id;

    if ($db->query($sql)) {
        $success = "Utilisateur supprimé avec succès";
    } else {
        $error = "Erreur lors de la suppression : ".$db->lasterror();
    }
}

// Récupérer la liste des utilisateurs mobiles
$sql = "SELECT u.rowid, u.email, u.firstname, u.lastname, u.phone, u.role, u.is_active,
               u.last_login, u.created_at, u.dolibarr_user_id, u.login_attempts, u.locked_until,
               d.login as dolibarr_login
        FROM ".MAIN_DB_PREFIX."mv3_mobile_users u
        LEFT JOIN ".MAIN_DB_PREFIX."user d ON d.rowid = u.dolibarr_user_id
        ORDER BY u.created_at DESC";

$resql = $db->query($sql);

// Récupérer les utilisateurs Dolibarr pour le dropdown
$sql_dol_users = "SELECT rowid, login, firstname, lastname FROM ".MAIN_DB_PREFIX."user WHERE statut = 1 ORDER BY lastname, firstname";
$resql_dol_users = $db->query($sql_dol_users);

llxHeader('', 'Gestion des utilisateurs mobiles');
?>

<div class="fichecenter">
    <div class="fiche">
        <h1>Gestion des utilisateurs mobiles</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="tabBar">
            <table class="border centpercent">
                <tr class="liste_titre">
                    <th>Email</th>
                    <th>Nom complet</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                    <th>Utilisateur Dolibarr</th>
                    <th>Statut</th>
                    <th>Dernier login</th>
                    <th>Tentatives</th>
                    <th>Actions</th>
                </tr>

                <?php
                if ($resql && $db->num_rows($resql) > 0) {
                    while ($obj = $db->fetch_object($resql)) {
                        $status_class = $obj->is_active ? 'badge badge-status4' : 'badge badge-status8';
                        $status_label = $obj->is_active ? 'Actif' : 'Inactif';

                        $locked = ($obj->locked_until && strtotime($obj->locked_until) > time());
                        $lock_icon = $locked ? ' 🔒' : '';
                        ?>
                        <tr>
                            <td><?php echo dol_escape_htmltag($obj->email); ?></td>
                            <td><strong><?php echo dol_escape_htmltag($obj->firstname.' '.$obj->lastname); ?></strong></td>
                            <td><?php echo dol_escape_htmltag($obj->phone); ?></td>
                            <td><?php echo dol_escape_htmltag($obj->role); ?></td>
                            <td><?php echo $obj->dolibarr_login ? dol_escape_htmltag($obj->dolibarr_login) : '-'; ?></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                            <td><?php echo $obj->last_login ? dol_print_date($db->jdate($obj->last_login), 'dayhour') : '-'; ?></td>
                            <td><?php echo $obj->login_attempts.$lock_icon; ?></td>
                            <td>
                                <a href="?id=<?php echo $obj->rowid; ?>&action=edit" class="butAction">Modifier</a>
                                <a href="?id=<?php echo $obj->rowid; ?>&action=reset_pwd" class="butAction">Réinitialiser mot de passe</a>
                                <a href="?id=<?php echo $obj->rowid; ?>&action=delete" class="butActionDelete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">Supprimer</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="9" class="center">Aucun utilisateur mobile</td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>

        <br>

        <div class="tabBar" id="create-form-section">
            <h2>Créer un nouvel utilisateur mobile</h2>
            <form method="POST" action="?action=create" id="create-user-form">
                <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                <table class="border centpercent">
                    <tr>
                        <td class="fieldrequired" width="25%">Email</td>
                        <td><input type="email" name="email" class="minwidth300" required></td>
                    </tr>
                    <tr>
                        <td class="fieldrequired">Mot de passe</td>
                        <td><input type="password" name="password" class="minwidth300" required minlength="8"></td>
                    </tr>
                    <tr>
                        <td class="fieldrequired">Prénom</td>
                        <td><input type="text" name="firstname" class="minwidth300" required></td>
                    </tr>
                    <tr>
                        <td class="fieldrequired">Nom</td>
                        <td><input type="text" name="lastname" class="minwidth300" required></td>
                    </tr>
                    <tr>
                        <td>Téléphone</td>
                        <td><input type="text" name="phone" class="minwidth300"></td>
                    </tr>
                    <tr>
                        <td>Rôle</td>
                        <td>
                            <select name="role" class="minwidth200">
                                <option value="employee">Employé</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td id="dolibarr_label">Lier à un utilisateur Dolibarr</td>
                        <td>
                            <select name="dolibarr_user" id="dolibarr_user_create" class="minwidth300">
                                <option value="0">-- Aucun --</option>
                                <?php
                                if ($resql_dol_users) {
                                    while ($dol_user = $db->fetch_object($resql_dol_users)) {
                                        $fullname = $dol_user->firstname.' '.$dol_user->lastname.' ('.$dol_user->login.')';
                                        echo '<option value="'.$dol_user->rowid.'">'.dol_escape_htmltag($fullname).'</option>';
                                    }
                                }
                                ?>
                            </select>
                            <div id="dolibarr_warning_create" style="display: none; color: #d97706; background: #fef3c7; padding: 8px; margin-top: 8px; border-radius: 4px; font-weight: bold;">
                                ⚠️ OBLIGATOIRE pour les rôles Employé et Manager
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="center">
                            <button type="submit" class="butAction">Créer l'utilisateur</button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <?php if ($action == 'edit' && $user_id > 0): ?>
            <?php
            $sql_edit = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_mobile_users WHERE rowid = ".(int)$user_id;
            $resql_edit = $db->query($sql_edit);
            $user_edit = $db->fetch_object($resql_edit);
            ?>
            <div class="tabBar">
                <h2>Modifier l'utilisateur</h2>
                <form method="POST" action="?action=update&id=<?php echo $user_id; ?>">
                    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                    <table class="border centpercent">
                        <tr>
                            <td class="fieldrequired" width="25%">Email</td>
                            <td><input type="email" name="email" class="minwidth300" value="<?php echo dol_escape_htmltag($user_edit->email); ?>" required></td>
                        </tr>
                        <tr>
                            <td class="fieldrequired">Prénom</td>
                            <td><input type="text" name="firstname" class="minwidth300" value="<?php echo dol_escape_htmltag($user_edit->firstname); ?>" required></td>
                        </tr>
                        <tr>
                            <td class="fieldrequired">Nom</td>
                            <td><input type="text" name="lastname" class="minwidth300" value="<?php echo dol_escape_htmltag($user_edit->lastname); ?>" required></td>
                        </tr>
                        <tr>
                            <td>Téléphone</td>
                            <td><input type="text" name="phone" class="minwidth300" value="<?php echo dol_escape_htmltag($user_edit->phone); ?>"></td>
                        </tr>
                        <tr>
                            <td>Rôle</td>
                            <td>
                                <select name="role" class="minwidth200">
                                    <option value="employee" <?php echo $user_edit->role == 'employee' ? 'selected' : ''; ?>>Employé</option>
                                    <option value="manager" <?php echo $user_edit->role == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?php echo $user_edit->role == 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td id="dolibarr_label_edit">Lier à un utilisateur Dolibarr</td>
                            <td>
                                <select name="dolibarr_user" id="dolibarr_user_edit" class="minwidth300">
                                    <option value="0">-- Aucun --</option>
                                    <?php
                                    $resql_dol_users2 = $db->query($sql_dol_users);
                                    if ($resql_dol_users2) {
                                        while ($dol_user = $db->fetch_object($resql_dol_users2)) {
                                            $selected = ($dol_user->rowid == $user_edit->dolibarr_user_id) ? 'selected' : '';
                                            $fullname = $dol_user->firstname.' '.$dol_user->lastname.' ('.$dol_user->login.')';
                                            echo '<option value="'.$dol_user->rowid.'" '.$selected.'>'.dol_escape_htmltag($fullname).'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <div id="dolibarr_warning_edit" style="display: none; color: #d97706; background: #fef3c7; padding: 8px; margin-top: 8px; border-radius: 4px; font-weight: bold;">
                                    ⚠️ OBLIGATOIRE pour les rôles Employé et Manager
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>Statut</td>
                            <td>
                                <select name="is_active" class="minwidth200">
                                    <option value="1" <?php echo $user_edit->is_active ? 'selected' : ''; ?>>Actif</option>
                                    <option value="0" <?php echo !$user_edit->is_active ? 'selected' : ''; ?>>Inactif</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="center">
                                <button type="submit" class="butAction">Mettre à jour</button>
                                <a href="?" class="butAction">Annuler</a>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($action == 'reset_pwd' && $user_id > 0): ?>
            <div class="tabBar">
                <h2>Réinitialiser le mot de passe</h2>
                <form method="POST" action="?action=reset_password&id=<?php echo $user_id; ?>">
                    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
                    <table class="border centpercent">
                        <tr>
                            <td class="fieldrequired" width="25%">Nouveau mot de passe</td>
                            <td><input type="password" name="new_password" class="minwidth300" required minlength="8"></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="center">
                                <button type="submit" class="butAction">Réinitialiser</button>
                                <a href="?" class="butAction">Annuler</a>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Gestion du formulaire de création
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.querySelector('select[name="role"]');
    const dolibarrUserSelect = document.getElementById('dolibarr_user_create');
    const dolibarrWarning = document.getElementById('dolibarr_warning_create');
    const dolibarrLabel = document.getElementById('dolibarr_label');

    function updateDolibarrRequirement() {
        const role = roleSelect.value;
        const isRequired = role === 'employee' || role === 'manager';

        if (isRequired) {
            dolibarrWarning.style.display = 'block';
            dolibarrLabel.classList.add('fieldrequired');
        } else {
            dolibarrWarning.style.display = 'none';
            dolibarrLabel.classList.remove('fieldrequired');
        }
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', updateDolibarrRequirement);
        updateDolibarrRequirement(); // Init
    }

    // Gestion du formulaire d'édition
    const roleSelectEdit = document.querySelector('form[action*="action=update"] select[name="role"]');
    const dolibarrUserSelectEdit = document.getElementById('dolibarr_user_edit');
    const dolibarrWarningEdit = document.getElementById('dolibarr_warning_edit');
    const dolibarrLabelEdit = document.getElementById('dolibarr_label_edit');

    function updateDolibarrRequirementEdit() {
        const role = roleSelectEdit.value;
        const isRequired = role === 'employee' || role === 'manager';

        if (isRequired) {
            dolibarrWarningEdit.style.display = 'block';
            dolibarrLabelEdit.classList.add('fieldrequired');
        } else {
            dolibarrWarningEdit.style.display = 'none';
            dolibarrLabelEdit.classList.remove('fieldrequired');
        }
    }

    if (roleSelectEdit) {
        roleSelectEdit.addEventListener('change', updateDolibarrRequirementEdit);
        updateDolibarrRequirementEdit(); // Init
    }

    // Auto-scroll vers le formulaire de création si action=create dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'create') {
        const createForm = document.querySelector('form[action*="action=create"]');
        if (createForm) {
            createForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            createForm.style.border = '2px solid #4CAF50';
            createForm.style.padding = '15px';
            createForm.style.borderRadius = '8px';
            setTimeout(() => {
                createForm.style.border = '';
                createForm.style.padding = '';
            }, 3000);
        }
    }
});
</script>

<?php
llxFooter();
$db->close();
?>
