# 🔔 Guide - Créer des notifications de test

## 📋 Table des matières

1. [Via SQL direct](#via-sql-direct)
2. [Via PHP script](#via-php-script)
3. [Via Triggers Dolibarr](#via-triggers-dolibarr)
4. [Types de notifications](#types-de-notifications)

---

## 1. Via SQL direct

### Notification simple

```sql
INSERT INTO llx_mv3_notifications
(fk_user, type, titre, message, statut, date_creation, entity)
VALUES
(1, 'info', 'Notification de test', 'Ceci est une notification de test', 'non_lu', NOW(), 1);
```

### Notification liée à un rapport

```sql
INSERT INTO llx_mv3_notifications
(fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)
VALUES
(1, 'rapport_new', 'Nouveau rapport créé', 'Un nouveau rapport a été créé pour le projet ABC', 123, 'rapport', 'non_lu', NOW(), 1);
```

### Notification liée au planning

```sql
INSERT INTO llx_mv3_notifications
(fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)
VALUES
(1, 'planning_new', 'Nouvelle tâche planifiée', 'Une nouvelle tâche vous a été assignée', 456, 'planning', 'non_lu', NOW(), 1);
```

### Notification matériel

```sql
INSERT INTO llx_mv3_notifications
(fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)
VALUES
(1, 'materiel_low', 'Stock bas', 'Le matériel "Colle" est en stock bas (5 unités)', 789, 'materiel', 'non_lu', NOW(), 1);
```

### Script SQL complet - 10 notifications de test

```sql
-- 10 notifications variées pour l'utilisateur 1
INSERT INTO llx_mv3_notifications
(fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)
VALUES
-- Notification 1 : Rapport nouveau (non lu)
(1, 'rapport_new', 'Nouveau rapport créé', 'Un nouveau rapport a été créé pour le projet ABC', 100, 'rapport', 'non_lu', DATE_SUB(NOW(), INTERVAL 5 MINUTE), 1),

-- Notification 2 : Rapport validé (non lu)
(1, 'rapport_validated', 'Rapport validé', 'Votre rapport #R2026-001 a été validé par le responsable', 101, 'rapport', 'non_lu', DATE_SUB(NOW(), INTERVAL 1 HOUR), 1),

-- Notification 3 : Planning (lu)
(1, 'planning_new', 'Nouvelle tâche', 'Vous avez été assigné à une nouvelle tâche demain', 200, 'planning', 'lu', DATE_SUB(NOW(), INTERVAL 2 HOUR), 1),

-- Notification 4 : Matériel bas (non lu)
(1, 'materiel_low', 'Stock bas - Colle', 'Le stock de colle est bas (5 unités restantes)', 300, 'materiel', 'non_lu', DATE_SUB(NOW(), INTERVAL 3 HOUR), 1),

-- Notification 5 : Matériel vide (non lu)
(1, 'materiel_empty', 'Rupture de stock', 'Le matériel "Joints" est en rupture de stock', 301, 'materiel', 'non_lu', DATE_SUB(NOW(), INTERVAL 4 HOUR), 1),

-- Notification 6 : Planning modifié (lu)
(1, 'planning_updated', 'Planning modifié', 'Votre planning de demain a été modifié', 201, 'planning', 'lu', DATE_SUB(NOW(), INTERVAL 1 DAY), 1),

-- Notification 7 : Message (non lu)
(1, 'message', 'Nouveau message', 'Vous avez reçu un message de votre responsable', NULL, NULL, 'non_lu', DATE_SUB(NOW(), INTERVAL 6 HOUR), 1),

-- Notification 8 : Rapport rejeté (lu)
(1, 'rapport_rejected', 'Rapport rejeté', 'Votre rapport #R2026-002 a été rejeté. Raison: photos manquantes', 102, 'rapport', 'lu', DATE_SUB(NOW(), INTERVAL 2 DAY), 1),

-- Notification 9 : Planning annulé (lu)
(1, 'planning_cancelled', 'Tâche annulée', 'La tâche du 10/01 a été annulée', 202, 'planning', 'lu', DATE_SUB(NOW(), INTERVAL 3 DAY), 1),

-- Notification 10 : Info générale (lu)
(1, 'info', 'Mise à jour système', 'Le système a été mis à jour avec de nouvelles fonctionnalités', NULL, NULL, 'lu', DATE_SUB(NOW(), INTERVAL 7 DAY), 1);
```

---

## 2. Via PHP script

### Script standalone : `create_test_notifications.php`

```php
<?php
/**
 * Script de création de notifications de test
 * À placer dans /custom/mv3pro_portail/admin/
 */

// Inclure Dolibarr
$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Droits admin requis
if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'alpha');

if ($action === 'create') {
    // Récupérer le user_id
    $test_user_id = (int)GETPOST('user_id', 'int');

    if ($test_user_id <= 0) {
        print "Erreur : user_id invalide";
        exit;
    }

    // Créer 10 notifications de test
    $notifications = [
        [
            'type' => 'rapport_new',
            'titre' => 'Nouveau rapport créé',
            'message' => 'Un nouveau rapport a été créé pour le projet ABC',
            'fk_object' => 100,
            'object_type' => 'rapport',
            'delay' => '5 MINUTE'
        ],
        [
            'type' => 'rapport_validated',
            'titre' => 'Rapport validé',
            'message' => 'Votre rapport #R2026-001 a été validé',
            'fk_object' => 101,
            'object_type' => 'rapport',
            'delay' => '1 HOUR'
        ],
        [
            'type' => 'planning_new',
            'titre' => 'Nouvelle tâche',
            'message' => 'Vous avez été assigné à une nouvelle tâche',
            'fk_object' => 200,
            'object_type' => 'planning',
            'delay' => '2 HOUR'
        ],
        [
            'type' => 'materiel_low',
            'titre' => 'Stock bas - Colle',
            'message' => 'Le stock de colle est bas (5 unités)',
            'fk_object' => 300,
            'object_type' => 'materiel',
            'delay' => '3 HOUR'
        ],
        [
            'type' => 'message',
            'titre' => 'Nouveau message',
            'message' => 'Vous avez reçu un message de votre responsable',
            'fk_object' => null,
            'object_type' => null,
            'delay' => '6 HOUR'
        ]
    ];

    $created = 0;
    foreach ($notifications as $notif) {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_notifications";
        $sql .= " (fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)";
        $sql .= " VALUES (";
        $sql .= " ".$test_user_id.",";
        $sql .= " '".$db->escape($notif['type'])."',";
        $sql .= " '".$db->escape($notif['titre'])."',";
        $sql .= " '".$db->escape($notif['message'])."',";
        $sql .= " ".($notif['fk_object'] ? $notif['fk_object'] : "NULL").",";
        $sql .= " ".($notif['object_type'] ? "'".$db->escape($notif['object_type'])."'" : "NULL").",";
        $sql .= " 'non_lu',";
        $sql .= " DATE_SUB(NOW(), INTERVAL ".$notif['delay']."),";
        $sql .= " ".$conf->entity;
        $sql .= ")";

        if ($db->query($sql)) {
            $created++;
        }
    }

    print "✅ ".$created." notifications créées pour l'utilisateur ".$test_user_id;
    exit;
}

// Affichage du formulaire
llxHeader('', 'Créer notifications de test');

print '<div class="fiche">';
print '<div class="titre">🔔 Créer des notifications de test</div>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="create">';

print '<table class="border centpercent">';

// Liste des utilisateurs mobiles
$sql = "SELECT id, email, nom, prenom FROM ".MAIN_DB_PREFIX."mv3_mobile_users WHERE active = 1";
$resql = $db->query($sql);

print '<tr>';
print '<td width="30%">Utilisateur</td>';
print '<td>';
print '<select name="user_id" required class="flat minwidth300">';
print '<option value="">-- Sélectionner --</option>';

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $name = $obj->prenom.' '.$obj->nom.' ('.$obj->email.')';
        print '<option value="'.$obj->id.'">'.$name.'</option>';
    }
}

print '</select>';
print '</td>';
print '</tr>';

print '</table>';

print '<p><button type="submit" class="butAction">Créer 10 notifications de test</button></p>';

print '</form>';

print '<div class="info">';
print '<h3>ℹ️ Informations</h3>';
print '<p>Ce script va créer 10 notifications variées pour l\'utilisateur sélectionné :</p>';
print '<ul>';
print '<li>5 notifications non lues (récentes)</li>';
print '<li>5 notifications lues (anciennes)</li>';
print '<li>Types variés : rapport, planning, matériel, message</li>';
print '<li>Dates échelonnées (5 min à 7 jours)</li>';
print '</ul>';
print '</div>';

print '</div>';

llxFooter();
$db->close();
```

### Usage

1. Créer le fichier `/custom/mv3pro_portail/admin/create_test_notifications.php`
2. Accéder via : `https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/create_test_notifications.php`
3. Sélectionner un utilisateur
4. Cliquer sur "Créer 10 notifications de test"

---

## 3. Via Triggers Dolibarr

### Créer un trigger pour les rapports

**Fichier** : `/custom/mv3pro_portail/core/triggers/interface_99_modMv3_Notifications.class.php`

```php
<?php
/**
 * Trigger pour créer des notifications automatiques
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceMv3Notifications extends DolibarrTriggers
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "mv3";
        $this->description = "Notifications MV3 PRO";
        $this->version = '1.0';
        $this->picto = 'generic';
    }

    /**
     * Fonction appelée lors des événements Dolibarr
     */
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        global $db;

        // Rapport créé
        if ($action == 'MV3_RAPPORT_CREATE') {
            $this->createNotification(
                $object->fk_user,
                'rapport_new',
                'Nouveau rapport créé',
                'Votre rapport a été créé avec succès',
                $object->id,
                'rapport'
            );
        }

        // Rapport validé
        if ($action == 'MV3_RAPPORT_VALIDATE') {
            $this->createNotification(
                $object->fk_user,
                'rapport_validated',
                'Rapport validé',
                'Votre rapport a été validé par le responsable',
                $object->id,
                'rapport'
            );
        }

        // Planning créé
        if ($action == 'ACTION_CREATE') {
            if ($object->userownerid > 0) {
                $this->createNotification(
                    $object->userownerid,
                    'planning_new',
                    'Nouvelle tâche planifiée',
                    'Une nouvelle tâche vous a été assignée',
                    $object->id,
                    'planning'
                );
            }
        }

        return 0;
    }

    /**
     * Créer une notification
     */
    private function createNotification($user_id, $type, $titre, $message, $object_id = null, $object_type = null)
    {
        global $db, $conf;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_notifications";
        $sql .= " (fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)";
        $sql .= " VALUES (";
        $sql .= " ".(int)$user_id.",";
        $sql .= " '".$db->escape($type)."',";
        $sql .= " '".$db->escape($titre)."',";
        $sql .= " '".$db->escape($message)."',";
        $sql .= " ".($object_id ? (int)$object_id : "NULL").",";
        $sql .= " ".($object_type ? "'".$db->escape($object_type)."'" : "NULL").",";
        $sql .= " 'non_lu',";
        $sql .= " NOW(),";
        $sql .= " ".$conf->entity;
        $sql .= ")";

        $db->query($sql);
    }
}
```

---

## 4. Types de notifications

### Liste complète

| Type | Titre suggéré | Usage | Icône | Couleur |
|------|---------------|-------|-------|---------|
| `rapport_new` | Nouveau rapport créé | Rapport créé | 📄 | Bleu |
| `rapport_validated` | Rapport validé | Rapport validé | ✅ | Vert |
| `rapport_rejected` | Rapport rejeté | Rapport rejeté | ❌ | Rouge |
| `materiel_low` | Stock bas | Matériel stock bas | ⚠️ | Orange |
| `materiel_empty` | Rupture de stock | Matériel épuisé | 🔴 | Rouge |
| `planning_new` | Nouvelle tâche | Planning créé | 📅 | Bleu |
| `planning_updated` | Planning modifié | Planning modifié | 📅 | Bleu |
| `planning_cancelled` | Tâche annulée | Planning annulé | ❌ | Rouge |
| `message` | Nouveau message | Message reçu | 💬 | Bleu |
| `info` | Information | Info générale | ℹ️ | Bleu |
| `warning` | Avertissement | Warning | ⚠️ | Orange |
| `error` | Erreur | Erreur | 🔴 | Rouge |
| `success` | Succès | Opération réussie | ✅ | Vert |

### Template de notification

```sql
INSERT INTO llx_mv3_notifications
(fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)
VALUES
(
  [USER_ID],                    -- ID de l'utilisateur destinataire
  '[TYPE]',                     -- Type de notification (voir tableau ci-dessus)
  '[TITRE]',                    -- Titre court (max 255 car)
  '[MESSAGE]',                  -- Message détaillé
  [OBJECT_ID ou NULL],          -- ID de l'objet lié (rapport, planning, etc.)
  '[OBJECT_TYPE ou NULL]',      -- Type d'objet (rapport, planning, materiel, etc.)
  'non_lu',                     -- Statut (non_lu, lu, traite, reporte)
  NOW(),                        -- Date de création
  1                             -- Entity (multi-company)
);
```

---

## 📝 Exemples d'usage

### Exemple 1 : Créer 50 notifications de test

```sql
-- Boucle pour créer 50 notifications
-- (Utiliser un script PHP ou procédure stockée)

DELIMITER $$
CREATE PROCEDURE create_test_notifications(IN user_id INT)
BEGIN
    DECLARE i INT DEFAULT 1;
    WHILE i <= 50 DO
        INSERT INTO llx_mv3_notifications
        (fk_user, type, titre, message, statut, date_creation, entity)
        VALUES
        (user_id, 'info', CONCAT('Notification test ', i), CONCAT('Message de test numéro ', i),
         IF(i % 3 = 0, 'lu', 'non_lu'), DATE_SUB(NOW(), INTERVAL i MINUTE), 1);

        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

-- Appeler
CALL create_test_notifications(1);
```

### Exemple 2 : Notification avec données réelles

```sql
-- Récupérer un rapport réel
SET @rapport_id = (SELECT rowid FROM llx_mv3_rapport ORDER BY date_creation DESC LIMIT 1);
SET @user_id = (SELECT fk_user FROM llx_mv3_rapport WHERE rowid = @rapport_id);

-- Créer notification
INSERT INTO llx_mv3_notifications
(fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)
VALUES
(@user_id, 'rapport_new', 'Nouveau rapport', 'Votre rapport a été créé', @rapport_id, 'rapport', 'non_lu', NOW(), 1);
```

---

## ✅ Checklist

- [ ] Créer la table si elle n'existe pas
- [ ] Créer des notifications de test
- [ ] Vérifier dans la PWA que les notifications s'affichent
- [ ] Tester le marquage comme lu
- [ ] Tester la navigation vers les objets liés

---

**Date** : 2026-01-09
**Version** : 1.0
