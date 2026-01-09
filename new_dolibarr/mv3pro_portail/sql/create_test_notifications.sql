/*
  Script SQL - Créer 10 notifications de test

  Usage:
  1. Remplacer [USER_ID] par l'ID de l'utilisateur mobile de test
  2. Exécuter ce script dans phpMyAdmin ou en ligne de commande

  Exemple:
  mysql -u user -p dolibarr_db < create_test_notifications.sql
*/

-- IMPORTANT : Remplacer 1 par l'ID de votre utilisateur mobile
SET @user_id = 1;

-- Vérifier que l'utilisateur existe
SELECT CONCAT('Création de 10 notifications pour l\'utilisateur ', email) as message
FROM llx_mv3_mobile_users
WHERE id = @user_id;

-- Créer 10 notifications variées
INSERT INTO llx_mv3_notifications
(fk_user, type, titre, message, fk_object, object_type, statut, date_creation, entity)
VALUES
-- 1. Notification récente non lue (5 minutes)
(@user_id, 'rapport_new', 'Nouveau rapport créé', 'Un nouveau rapport a été créé pour le projet ABC', 100, 'rapport', 'non_lu', DATE_SUB(NOW(), INTERVAL 5 MINUTE), 1),

-- 2. Notification récente non lue (1 heure)
(@user_id, 'rapport_validated', 'Rapport validé', 'Votre rapport #R2026-001 a été validé par le responsable', 101, 'rapport', 'non_lu', DATE_SUB(NOW(), INTERVAL 1 HOUR), 1),

-- 3. Notification lue (2 heures)
(@user_id, 'planning_new', 'Nouvelle tâche assignée', 'Vous avez été assigné à une nouvelle tâche pour demain', 200, 'planning', 'lu', DATE_SUB(NOW(), INTERVAL 2 HOUR), 1),

-- 4. Notification non lue (3 heures) - Stock bas
(@user_id, 'materiel_low', 'Stock bas - Colle', 'Le stock de colle est bas : 5 unités restantes', 300, 'materiel', 'non_lu', DATE_SUB(NOW(), INTERVAL 3 HOUR), 1),

-- 5. Notification non lue (4 heures) - Rupture
(@user_id, 'materiel_empty', 'Rupture de stock', 'Le matériel "Joints" est en rupture de stock', 301, 'materiel', 'non_lu', DATE_SUB(NOW(), INTERVAL 4 HOUR), 1),

-- 6. Notification lue (1 jour)
(@user_id, 'planning_updated', 'Planning modifié', 'Votre planning de demain a été modifié par le responsable', 201, 'planning', 'lu', DATE_SUB(NOW(), INTERVAL 1 DAY), 1),

-- 7. Notification non lue (6 heures) - Message
(@user_id, 'message', 'Nouveau message', 'Vous avez reçu un message de votre responsable', NULL, NULL, 'non_lu', DATE_SUB(NOW(), INTERVAL 6 HOUR), 1),

-- 8. Notification lue (2 jours) - Rejet
(@user_id, 'rapport_rejected', 'Rapport rejeté', 'Votre rapport #R2026-002 a été rejeté. Raison: photos manquantes', 102, 'rapport', 'lu', DATE_SUB(NOW(), INTERVAL 2 DAY), 1),

-- 9. Notification lue (3 jours) - Annulation
(@user_id, 'planning_cancelled', 'Tâche annulée', 'La tâche planifiée pour le 10/01 a été annulée', 202, 'planning', 'lu', DATE_SUB(NOW(), INTERVAL 3 DAY), 1),

-- 10. Notification lue (7 jours) - Info
(@user_id, 'info', 'Mise à jour du système', 'Le système a été mis à jour avec de nouvelles fonctionnalités', NULL, NULL, 'lu', DATE_SUB(NOW(), INTERVAL 7 DAY), 1);

-- Afficher le résultat
SELECT CONCAT('✅ ', COUNT(*), ' notifications créées') as resultat
FROM llx_mv3_notifications
WHERE fk_user = @user_id
AND date_creation >= DATE_SUB(NOW(), INTERVAL 10 MINUTE);

-- Afficher le détail
SELECT
  rowid as id,
  type,
  titre,
  statut,
  DATE_FORMAT(date_creation, '%d/%m/%Y %H:%i') as date_creation
FROM llx_mv3_notifications
WHERE fk_user = @user_id
ORDER BY date_creation DESC
LIMIT 10;
