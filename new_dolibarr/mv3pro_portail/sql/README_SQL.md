# MV3 PRO PORTAIL - SQL

Ce dossier contient tous les scripts SQL nécessaires pour l'installation et la maintenance du module MV3 PRO Portail.

## Fichiers principaux

### 1. mv3pro_portail_install.sql ⭐
**LE FICHIER À UTILISER POUR L'INSTALLATION COMPLÈTE**

- 📦 Script d'installation complet (20 tables)
- ✅ Idempotent (peut être exécuté plusieurs fois)
- 🔒 Aucun DROP TABLE (sécurité des données)
- 📝 Commentaires détaillés
- 🚀 Prêt pour production

**Tables créées:**
```
Section 1: Authentification mobile (3 tables)
  - llx_mv3_mobile_users
  - llx_mv3_mobile_sessions
  - llx_mv3_mobile_login_history

Section 2: Rapports journaliers (3 tables)
  - llx_mv3_rapport
  - llx_mv3_rapport_photo
  - llx_mv3_signalement

Section 3: Sens de pose (2 tables)
  - llx_mv3_sens_pose
  - llx_mv3_sens_pose_pieces

Section 4: Matériel (2 tables)
  - llx_mv3_materiel
  - llx_mv3_materiel_historique

Section 5: Feuilles de régie (2 tables)
  - llx_mv3_regie
  - llx_mv3_regie_ligne

Section 6: Notes de frais (1 table)
  - llx_mv3_frais

Section 7: Notifications (1 table)
  - llx_mv3_notifications

Section 8: Sous-traitants (6 tables)
  - llx_mv3_subcontractors
  - llx_mv3_subcontractor_reports
  - llx_mv3_subcontractor_photos
  - llx_mv3_subcontractor_payments
  - llx_mv3_subcontractor_sessions
  - llx_mv3_subcontractor_login_attempts
```

### 2. INSTRUCTIONS_INSTALLATION.md 📖
Guide complet d'installation avec:
- Instructions pas à pas
- Vérifications post-installation
- Tests de connexion
- Configuration sécurité
- Maintenance
- Désinstallation

### 3. verify_install.sql 🔍
Script de vérification automatique:
- Compte les tables installées
- Vérifie la structure
- Teste les colonnes critiques
- Vérifie les index et contraintes
- Statistiques

**Exécution:**
```bash
mysql -u username -p database_name < verify_install.sql
```

## Fichiers historiques (référence uniquement)

Les fichiers suivants sont conservés pour référence mais **ne doivent plus être utilisés directement**.
Utiliser `mv3pro_portail_install.sql` à la place.

- `llx_mv3_mobile_users.sql` → Intégré dans install
- `llx_mv3_rapport.sql` → Intégré dans install
- `llx_mv3_rapport_add_features.sql` → Intégré dans install
- `llx_mv3_sens_pose.sql` → Intégré dans install
- `llx_mv3_materiel.sql` → Intégré dans install
- `llx_mv3_notifications.sql` → Intégré dans install
- `llx_mv3_subcontractors.sql` → Intégré dans install
- Etc.

## Installation rapide

```bash
# 1. Télécharger le fichier
cd /path/to/dolibarr/htdocs/custom/mv3pro_portail/sql

# 2. Exécuter l'installation
mysql -u root -p dolibarr < mv3pro_portail_install.sql

# 3. Vérifier l'installation
mysql -u root -p dolibarr < verify_install.sql

# 4. Créer un utilisateur test
mysql -u root -p dolibarr -e "INSERT INTO llx_mv3_mobile_users (email, password_hash, firstname, lastname, is_active, entity) VALUES ('test@mv3pro.ch', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', 1, 1);"
```

## Vérification rapide

```sql
-- Compter les tables
SELECT COUNT(*) as nb_tables FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'llx_mv3_%';
-- Résultat attendu: 20

-- Lister les tables
SHOW TABLES LIKE 'llx_mv3_%';
```

## Maintenance

### Nettoyer les sessions expirées (cron quotidien)
```sql
DELETE FROM llx_mv3_mobile_sessions WHERE expires_at < NOW();
DELETE FROM llx_mv3_subcontractor_sessions WHERE expires_at < NOW();
```

### Réinitialiser un compte verrouillé
```sql
UPDATE llx_mv3_mobile_users 
SET login_attempts = 0, locked_until = NULL 
WHERE email = 'user@example.com';
```

### Consulter les logs de connexion
```sql
SELECT * FROM llx_mv3_mobile_login_history 
ORDER BY created_at DESC LIMIT 50;
```

## Sécurité

⚠️ **IMPORTANT:**
- Ne jamais exposer ce dossier via HTTP
- Restreindre l'accès en lecture seule
- Sauvegarder régulièrement la base de données
- Changer les mots de passe par défaut

## Support

En cas de problème:
1. Vérifier `verify_install.sql` pour diagnostiquer
2. Consulter `INSTRUCTIONS_INSTALLATION.md` pour les solutions
3. Vérifier les logs MySQL/MariaDB
4. S'assurer que InnoDB est activé

## Version

- **Version actuelle:** 1.0.0
- **Date:** 2025-01-07
- **Compatible:** Dolibarr >= 16.0, MySQL >= 5.7, MariaDB >= 10.3

---

**Prêt à installer? Utiliser `mv3pro_portail_install.sql` !**
