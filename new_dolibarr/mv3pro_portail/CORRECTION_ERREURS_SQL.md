# Corrections appliquées - Erreurs SQL et Redirections

## Date: 2026-01-09

## 1. Corrections du système de diagnostic

### A) debug.php - Suivi des redirections HTTP
**Problème**: Les tests backend retournaient 301 (redirections nginx) sans atteindre les scripts PHP.

**Solution appliquée**:
- ✓ Force HTTPS par défaut au lieu de HTTP
- ✓ Active `CURLOPT_FOLLOWLOCATION` pour suivre automatiquement les redirections
- ✓ Capture et affiche l'URL initiale, l'URL finale et le nombre de redirections
- ✓ Désactive la vérification SSL pour les environnements de développement

### B) Affichage des erreurs SQL complètes
**Problème**: Les endpoints affichaient "Erreur BDD" sans détails.

**Solution appliquée**:
Les 4 endpoints suivants affichent maintenant l'erreur SQL complète + la requête :
- ✓ `planning.php`
- ✓ `rapports.php`
- ✓ `notifications_list.php`
- ✓ `sens_pose_list.php`

Format d'erreur:
```
Erreur lors de la récupération des XXX: [MESSAGE] | SQL: [REQUÊTE COMPLÈTE]
```

## 2. Corrections des requêtes SQL

### A) Planning - Colonne `note_private` inexistante
**Problème**: `Unknown column 'a.note_private'` sur certaines versions de Dolibarr.

**Solution**:
```php
// Vérification dynamique de l'existence de la colonne
$columns_check = $db->query("SHOW COLUMNS FROM llx_actioncomm LIKE 'note_private'");
$has_note_private = ($columns_check && $db->num_rows($columns_check) > 0);

// Utilisation conditionnelle dans la requête
$sql = "SELECT ... ".($has_note_private ? "a.note_private" : "'' as note_private")." ...";
```

### B) Notifications - Alignement avec la structure de table
**Problème**: Le code cherchait `date_notif` et `is_read`, mais la table a `date_creation` et `statut`.

**Solution**:
- ✓ Requête alignée avec les colonnes réelles
- ✓ Mapping `statut` → `is_read` (non_lu = 0, autre = 1)
- ✓ Vérification de l'existence de la table avant requête
- ✓ Retourne liste vide si table absente (évite l'erreur 500)

### C) Sens de Pose - Alignement avec la structure de table
**Problème**: Colonnes incorrectes (`fk_soc`, `fk_user`, `date_pose`, `status`).

**Solution**:
- ✓ Colonnes corrigées: `fk_client`, `fk_user_create`, `date_creation`, `statut`
- ✓ Vérification de l'existence de la table avant requête
- ✓ Retourne liste vide si table absente

### D) Rapports - Vérification de table
**Solution**:
- ✓ Vérification de l'existence de la table avant requête
- ✓ Retourne liste vide si table absente

## 3. Installation des tables manquantes

### Script SQL complet créé
**Fichier**: `/sql/INSTALLATION_COMPLETE.sql`

**Contenu**:
1. Tables d'authentification mobile (llx_mv3_mobile_users, sessions, login_history)
2. Table des rapports (llx_mv3_rapport)
3. Table des notifications (llx_mv3_notifications)
4. Tables sens de pose (llx_mv3_sens_pose, llx_mv3_sens_pose_pieces)
5. Table matériel (llx_mv3_materiel) - optionnel

### Installation
```bash
# Via ligne de commande MySQL
mysql -u root -p nom_base_dolibarr < /chemin/vers/INSTALLATION_COMPLETE.sql

# Ou via phpMyAdmin
# 1. Ouvrir phpMyAdmin
# 2. Sélectionner la base Dolibarr
# 3. Onglet "SQL"
# 4. Copier/coller le contenu de INSTALLATION_COMPLETE.sql
# 5. Exécuter
```

## 4. Tests après correction

### Relancer le diagnostic
1. Accéder à la PWA
2. Menu Debug (page diagnostic)
3. Cliquer sur "Diagnostic Complet"

### Résultats attendus
- **Backend API**: Les tests doivent maintenant suivre les redirections et afficher :
  - URL initiale (HTTP)
  - URL finale (HTTPS après redirection)
  - Nombre de redirections
  - Code HTTP final (200/401/404/500)
  - Message d'erreur SQL exact si erreur

- **Planning**:
  - ✓ OK si aucun événement (retourne tableau vide)
  - ✓ OK avec données si événements présents
  - La colonne `note_private` est gérée automatiquement

- **Rapports/Notifications/Sens de Pose**:
  - ✓ OK avec tableau vide si tables inexistantes
  - ✓ OK avec données si tables créées et données présentes

## 5. Module MV3PRO

### État actuel
Le diagnostic indique: "Module MV3PRO activé : Non"

### Action requise
**Option 1**: Activer via l'interface Dolibarr
```
1. Se connecter en administrateur Dolibarr
2. Menu: Accueil > Configuration > Modules/Applications
3. Rechercher "MV3PRO" ou "Portail"
4. Cliquer sur "Activer"
```

**Option 2**: Activer via la base de données
```sql
INSERT INTO llx_const (name, value, type, visible, entity)
VALUES ('MAIN_MODULE_MV3PRO_PORTAIL', '1', 'chaine', 0, 1)
ON DUPLICATE KEY UPDATE value = '1';
```

## 6. Prochaines étapes

1. **Installer les tables SQL** (si pas déjà fait)
   ```bash
   mysql -u root -p base_dolibarr < sql/INSTALLATION_COMPLETE.sql
   ```

2. **Activer le module MV3PRO** dans Dolibarr

3. **Créer un utilisateur mobile de test**
   ```sql
   INSERT INTO llx_mv3_mobile_users
   (email, password_hash, firstname, lastname, role, is_active)
   VALUES
   ('test@mv3pro.com', '$2y$10$...', 'Test', 'User', 'employee', 1);
   ```

4. **Relancer le diagnostic** pour vérifier que tout fonctionne

5. **Tester l'authentification** via la PWA

## Fichiers modifiés

1. `/api/v1/debug.php` - Suivi des redirections + erreurs détaillées
2. `/api/v1/planning.php` - Gestion colonne note_private + erreurs détaillées
3. `/api/v1/rapports.php` - Vérification table + erreurs détaillées
4. `/api/v1/notifications_list.php` - Alignement colonnes + vérification table
5. `/api/v1/sens_pose_list.php` - Alignement colonnes + vérification table
6. `/pwa/src/pages/Debug.tsx` - Affichage redirections dans l'interface

## Fichiers créés

1. `/sql/INSTALLATION_COMPLETE.sql` - Script SQL complet
2. `/sql/DIAGNOSTIC_TABLES_MANQUANTES.md` - Documentation
3. `CORRECTION_ERREURS_SQL.md` - Ce fichier

## Résumé

✓ Diagnostic fonctionnel avec suivi des redirections
✓ Erreurs SQL détaillées dans tous les endpoints
✓ Requêtes SQL alignées avec la structure des tables
✓ Script d'installation SQL complet fourni
✓ Gestion gracieuse des tables manquantes (retourne tableau vide au lieu d'erreur 500)
✓ Build PWA réussi

**Action immédiate requise**: Installer les tables SQL et activer le module MV3PRO.
