# INSTRUCTIONS D'INSTALLATION - MV3 PRO PORTAIL

## 1. Prérequis

- Dolibarr >= 16.0
- MySQL >= 5.7 ou MariaDB >= 10.3
- Accès phpMyAdmin ou ligne de commande MySQL
- Droits CREATE TABLE et ALTER TABLE

## 2. Installation

### Option A: Via phpMyAdmin

1. Se connecter à phpMyAdmin
2. Sélectionner la base de données Dolibarr
3. Aller dans l'onglet "SQL"
4. Copier-coller le contenu de `mv3pro_portail_install.sql`
5. Cliquer sur "Exécuter"
6. Vérifier le message de succès

### Option B: Via ligne de commande

```bash
# Se connecter à MySQL
mysql -u username -p database_name < mv3pro_portail_install.sql

# Ou en une ligne
mysql -u root -p dolibarr < /path/to/mv3pro_portail_install.sql
```

### Option C: Via Dolibarr

1. Aller dans: Accueil > Configuration > Modules/Applications
2. Chercher le module "MV3 PRO Portail"
3. Cliquer sur "Activer"
4. Le SQL sera exécuté automatiquement si configuré

## 3. Vérification de l'installation

### Vérifier que toutes les tables sont créées

```sql
SHOW TABLES LIKE 'llx_mv3_%';
```

**Résultat attendu:** 20 tables

```
llx_mv3_frais
llx_mv3_materiel
llx_mv3_materiel_historique
llx_mv3_mobile_login_history
llx_mv3_mobile_sessions
llx_mv3_mobile_users
llx_mv3_notifications
llx_mv3_rapport
llx_mv3_rapport_photo
llx_mv3_regie
llx_mv3_regie_ligne
llx_mv3_sens_pose
llx_mv3_sens_pose_pieces
llx_mv3_signalement
llx_mv3_subcontractor_login_attempts
llx_mv3_subcontractor_payments
llx_mv3_subcontractor_photos
llx_mv3_subcontractor_reports
llx_mv3_subcontractor_sessions
llx_mv3_subcontractors
```

### Vérifier la structure d'une table clé

```sql
DESCRIBE llx_mv3_mobile_users;
DESCRIBE llx_mv3_rapport;
DESCRIBE llx_mv3_regie;
```

### Compter les tables installées

```sql
SELECT COUNT(*) as nb_tables
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'llx_mv3_%';
```

**Résultat attendu:** `nb_tables = 20`

## 4. Post-installation

### Créer un utilisateur mobile test

```sql
INSERT INTO llx_mv3_mobile_users
(email, password_hash, firstname, lastname, is_active, entity)
VALUES (
  'test@mv3pro.ch',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
  'Test',
  'Utilisateur',
  1,
  1
);
```

### Vérifier les permissions

```sql
-- Vérifier que l'utilisateur MySQL a les droits
SHOW GRANTS FOR CURRENT_USER;
```

## 5. Tests de connexion

### Test login mobile (via CURL)

```bash
curl -X POST 'http://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/api/auth.php?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@mv3pro.ch","password":"password"}'
```

**Résultat attendu:**
```json
{
  "success": true,
  "token": "...",
  "user": {
    "user_rowid": 1,
    "email": "test@mv3pro.ch",
    ...
  }
}
```

### Test PWA

1. Ouvrir: `http://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/`
2. Se connecter avec: `test@mv3pro.ch` / `password`
3. Vérifier redirection vers dashboard

## 6. Sécurité

### Changer le mot de passe de test

```sql
-- Générer un nouveau hash
-- En PHP: password_hash('VotreMotDePasse', PASSWORD_DEFAULT)

UPDATE llx_mv3_mobile_users
SET password_hash = '$2y$10$VOTRE_NOUVEAU_HASH'
WHERE email = 'test@mv3pro.ch';
```

### Configurer les droits

```sql
-- Activer uniquement les utilisateurs autorisés
UPDATE llx_mv3_mobile_users
SET is_active = 1
WHERE email IN ('user1@example.com', 'user2@example.com');

-- Désactiver les autres
UPDATE llx_mv3_mobile_users
SET is_active = 0
WHERE email NOT IN ('user1@example.com', 'user2@example.com');
```

## 7. Maintenance

### Nettoyer les sessions expirées (à exécuter régulièrement)

```sql
DELETE FROM llx_mv3_mobile_sessions
WHERE expires_at < NOW();

DELETE FROM llx_mv3_subcontractor_sessions
WHERE expires_at < NOW();
```

### Réinitialiser les comptes verrouillés

```sql
UPDATE llx_mv3_mobile_users
SET login_attempts = 0, locked_until = NULL
WHERE locked_until < NOW();
```

### Consulter les logs de connexion

```sql
SELECT * FROM llx_mv3_mobile_login_history
ORDER BY created_at DESC
LIMIT 50;
```

## 8. Désinstallation (ATTENTION: PERTE DE DONNÉES)

**⚠️ ATTENTION: Cette commande supprime TOUTES les données du module!**

```sql
-- Sauvegarder d'abord
mysqldump database_name llx_mv3_% > backup_mv3pro.sql

-- Puis supprimer (IRREVERSIBLE)
DROP TABLE IF EXISTS
  llx_mv3_subcontractor_login_attempts,
  llx_mv3_subcontractor_photos,
  llx_mv3_subcontractor_sessions,
  llx_mv3_subcontractor_reports,
  llx_mv3_subcontractor_payments,
  llx_mv3_subcontractors,
  llx_mv3_regie_ligne,
  llx_mv3_regie,
  llx_mv3_materiel_historique,
  llx_mv3_materiel,
  llx_mv3_sens_pose_pieces,
  llx_mv3_sens_pose,
  llx_mv3_rapport_photo,
  llx_mv3_signalement,
  llx_mv3_rapport,
  llx_mv3_notifications,
  llx_mv3_frais,
  llx_mv3_mobile_login_history,
  llx_mv3_mobile_sessions,
  llx_mv3_mobile_users;
```

## 9. Support

En cas de problème:

1. Vérifier les logs MySQL/MariaDB
2. Vérifier les permissions utilisateur
3. Consulter les messages d'erreur dans phpMyAdmin
4. Vérifier la version de MySQL/MariaDB (>= 5.7)
5. Vérifier que InnoDB est activé

## 10. Checklist finale

- [ ] 20 tables créées
- [ ] Utilisateur test créé
- [ ] Login mobile fonctionne (HTTP 200 + token)
- [ ] PWA accessible et fonctionnelle
- [ ] Pas d'erreurs dans les logs MySQL
- [ ] Sessions expirées nettoyées (si installation précédente)
- [ ] Droits configurés correctement

---

**Installation terminée! Le module MV3 PRO Portail est prêt à être utilisé.**
