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
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mv3_mobile_users WHERE email = '".$db->escape($diag_email)."'";
        $resql = $db->query($sql);

        if ($resql) {
            $num = $db->num_rows($resql);

            if ($num > 0) {
                $error = "L'utilisateur {$diag_email} existe déjà !";
            } else {
                // Générer le hash du mot de passe
                $password_hash = password_hash($diag_password, PASSWORD_DEFAULT);

                // Insérer l'utilisateur avec le VRAI schéma
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_mobile_users (";
                $sql .= " email, password_hash, dolibarr_user_id, firstname, lastname, role, is_active, login_attempts, created_at, updated_at";
                $sql .= ") VALUES (";
                $sql .= " '".$db->escape($diag_email)."',";
                $sql .= " '".$db->escape($password_hash)."',";
                $sql .= " 1,"; // Admin Dolibarr
                $sql .= " 'Diagnostic',";
                $sql .= " 'QA',";
                $sql .= " 'diagnostic',";
                $sql .= " 1,";
                $sql .= " 0,";
                $sql .= " NOW(),";
                $sql .= " NOW()";
                $sql .= ")";

                $resql = $db->query($sql);

                if ($resql) {
                    $db->commit();
                    $result['success'] = true;
                    $result['message'] = "Utilisateur {$diag_email} créé avec succès !";
                    $result['user_id'] = $db->last_insert_id(MAIN_DB_PREFIX."mv3_mobile_users");

                    // Test immédiat du hash
                    $test_verify = password_verify($diag_password, $password_hash);
                    $result['password_verify_test'] = $test_verify ? 'OK' : 'FAIL';
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

$sql = "SELECT rowid, email, firstname, lastname, role, is_active, login_attempts, locked_until, created_at";
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
    if (isset($result['password_verify_test'])) {
        if ($result['password_verify_test'] === 'OK') {
            print '<div class="ok">✅ Test password_verify: OK (le hash est valide)</div>';
        } else {
            print '<div class="error">❌ Test password_verify: FAIL (problème avec le hash)</div>';
        }
    }
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
    print '<tr><td width="30%">ID (rowid)</td><td>'.$user_info->rowid.'</td></tr>';
    print '<tr><td>Email</td><td>'.$user_info->email.'</td></tr>';
    print '<tr><td>Nom</td><td>'.$user_info->firstname.' '.$user_info->lastname.'</td></tr>';
    print '<tr><td>Rôle</td><td>'.$user_info->role.'</td></tr>';
    print '<tr><td>Actif (is_active)</td><td>'.($user_info->is_active ? '✅ Oui' : '❌ Non').'</td></tr>';
    print '<tr><td>Tentatives login</td><td>'.$user_info->login_attempts.($user_info->login_attempts > 0 ? ' ⚠️' : '').'</td></tr>';
    print '<tr><td>Verrouillé jusqu\'à</td><td>'.($user_info->locked_until ? '🔒 '.$user_info->locked_until : '-').'</td></tr>';
    print '<tr><td>Date création</td><td>'.$user_info->created_at.'</td></tr>';
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
