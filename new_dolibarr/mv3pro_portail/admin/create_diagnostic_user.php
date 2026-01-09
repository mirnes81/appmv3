<?php
/**
 * Script de création utilisateur de diagnostic QA
 *
 * Crée automatiquement l'utilisateur diagnostic@test.local
 * pour permettre les tests automatisés
 *
 * USAGE : Accéder à cette page en tant qu'admin Dolibarr
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/../class/mv3_config.class.php';

// Droits admin requis
if (!$user->admin) {
    accessforbidden();
}

$mv3_config = new Mv3Config($db);
$action = GETPOST('action', 'alpha');

// Récupération des credentials depuis la config
$diag_email = $mv3_config->get('DIAGNOSTIC_USER_EMAIL', 'diagnostic@test.local');
$diag_password = $mv3_config->get('DIAGNOSTIC_USER_PASSWORD', 'DiagTest2026!');

$result = [];
$error = '';

// ========================================
// ACTION : Créer l'utilisateur
// ========================================
if ($action === 'create') {
    $db->begin();

    try {
        // Vérifier si l'utilisateur existe déjà
        $sql = "SELECT id FROM ".MAIN_DB_PREFIX."mv3_mobile_users WHERE email = '".$db->escape($diag_email)."'";
        $resql = $db->query($sql);

        if ($resql) {
            $num = $db->num_rows($resql);

            if ($num > 0) {
                $error = "L'utilisateur {$diag_email} existe déjà !";
            } else {
                // Générer le hash du mot de passe
                $password_hash = password_hash($diag_password, PASSWORD_DEFAULT);

                // Insérer l'utilisateur
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_mobile_users (";
                $sql .= " fk_user, email, password_hash, nom, prenom, role, active, date_creation";
                $sql .= ") VALUES (";
                $sql .= " 1,"; // Admin Dolibarr
                $sql .= " '".$db->escape($diag_email)."',";
                $sql .= " '".$db->escape($password_hash)."',";
                $sql .= " 'Diagnostic',";
                $sql .= " 'QA',";
                $sql .= " 'admin',";
                $sql .= " 1,";
                $sql .= " NOW()";
                $sql .= ")";

                $resql = $db->query($sql);

                if ($resql) {
                    $db->commit();
                    $result['success'] = true;
                    $result['message'] = "Utilisateur {$diag_email} créé avec succès !";
                    $result['user_id'] = $db->last_insert_id(MAIN_DB_PREFIX."mv3_mobile_users");
                } else {
                    $db->rollback();
                    $error = "Erreur SQL lors de la création : ".$db->lasterror();
                }
            }
        } else {
            $db->rollback();
            $error = "Erreur SQL lors de la vérification : ".$db->lasterror();
        }
    } catch (Exception $e) {
        $db->rollback();
        $error = "Exception : ".$e->getMessage();
    }
}

// ========================================
// ACTION : Vérifier l'existence
// ========================================
$user_exists = false;
$user_info = null;

$sql = "SELECT id, email, nom, prenom, role, active, date_creation";
$sql .= " FROM ".MAIN_DB_PREFIX."mv3_mobile_users";
$sql .= " WHERE email = '".$db->escape($diag_email)."'";

$resql = $db->query($sql);
if ($resql) {
    if ($db->num_rows($resql) > 0) {
        $user_exists = true;
        $user_info = $db->fetch_object($resql);
    }
}

// ========================================
// AFFICHAGE
// ========================================

llxHeader('', 'Création utilisateur diagnostic');

print '<div class="fiche">';
print '<div class="titre">Création utilisateur de diagnostic QA</div>';

// Messages
if (!empty($result['success'])) {
    print '<div class="ok">'.$result['message'].'</div>';
}
if (!empty($error)) {
    print '<div class="error">'.$error.'</div>';
}

// Informations
print '<div class="info">';
print '<h3>Configuration actuelle</h3>';
print '<table class="border centpercent">';
print '<tr><td width="30%">Email (DIAGNOSTIC_USER_EMAIL)</td><td><b>'.$diag_email.'</b></td></tr>';
print '<tr><td>Password (DIAGNOSTIC_USER_PASSWORD)</td><td><code>'.str_repeat('*', strlen($diag_password)).'</code></td></tr>';
print '</table>';
print '</div>';

// Statut utilisateur
print '<div style="margin-top: 20px;">';
print '<h3>Statut utilisateur</h3>';

if ($user_exists) {
    print '<div class="ok">✅ L\'utilisateur existe dans la base de données</div>';
    print '<table class="border centpercent" style="margin-top: 10px;">';
    print '<tr><td width="30%">ID</td><td>'.$user_info->id.'</td></tr>';
    print '<tr><td>Email</td><td>'.$user_info->email.'</td></tr>';
    print '<tr><td>Nom</td><td>'.$user_info->nom.' '.$user_info->prenom.'</td></tr>';
    print '<tr><td>Rôle</td><td>'.$user_info->role.'</td></tr>';
    print '<tr><td>Actif</td><td>'.($user_info->active ? 'Oui' : 'Non').'</td></tr>';
    print '<tr><td>Date création</td><td>'.$user_info->date_creation.'</td></tr>';
    print '</table>';

    print '<p style="margin-top: 20px;">';
    print '<a class="butAction" href="diagnostic.php">Lancer le diagnostic QA</a>';
    print '</p>';
} else {
    print '<div class="warning">⚠️ L\'utilisateur n\'existe pas encore</div>';

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="create">';
    print '<p style="margin-top: 20px;">';
    print '<button type="submit" class="butAction">Créer l\'utilisateur diagnostic</button>';
    print '</p>';
    print '</form>';
}

print '</div>';

// Instructions
print '<div style="margin-top: 30px; padding: 15px; background: #f8f8f8; border-left: 4px solid #4CAF50;">';
print '<h3>ℹ️ Instructions</h3>';
print '<ol>';
print '<li>Cliquer sur "Créer l\'utilisateur diagnostic" pour créer automatiquement l\'utilisateur de test</li>';
print '<li>Une fois créé, lancer le <a href="diagnostic.php"><b>diagnostic QA complet</b></a></li>';
print '<li>Le diagnostic pourra se connecter automatiquement avec ces credentials</li>';
print '</ol>';
print '<p><b>Note</b> : Si vous souhaitez utiliser un autre utilisateur existant, modifiez les valeurs <code>DIAGNOSTIC_USER_EMAIL</code> et <code>DIAGNOSTIC_USER_PASSWORD</code> dans la <a href="config.php">configuration du module</a>.</p>';
print '</div>';

print '</div>';

llxFooter();
$db->close();
