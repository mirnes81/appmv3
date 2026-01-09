# Diagnostic et correction des tables manquantes

## Tables MV3 requises par l'API

### 1. llx_mv3_rapport
**Endpoint**: `/api/v1/rapports.php`
**Script SQL**: `llx_mv3_rapport.sql`
**Statut**: À vérifier

### 2. llx_mv3_notifications
**Endpoint**: `/api/v1/notifications_list.php`
**Script SQL**: `llx_mv3_notifications.sql`
**Statut**: À vérifier

### 3. llx_mv3_sens_pose
**Endpoint**: `/api/v1/sens_pose_list.php`
**Script SQL**: `llx_mv3_sens_pose.sql`
**Statut**: À vérifier

### 4. llx_mv3_mobile_users
**Endpoint**: Auth
**Script SQL**: `llx_mv3_mobile_users.sql`
**Statut**: À vérifier

### 5. llx_mv3_mobile_sessions
**Endpoint**: Auth
**Script SQL**: `llx_mv3_mobile_users.sql`
**Statut**: À vérifier

## Problèmes identifiés

### Planning (llx_actioncomm)
**Problème**: Colonne `note_private` inexistante dans certaines versions de Dolibarr
**Solution**: Vérification dynamique de l'existence de la colonne + fallback
**Statut**: ✓ CORRIGÉ (vérification automatique)

## Installation rapide

Pour installer toutes les tables d'un coup:

```bash
mysql -u root -p dolibarr_db < sql/INSTALLATION_RAPIDE.sql
```

Ou exécuter chaque script individuellement:

```bash
mysql -u root -p dolibarr_db < sql/llx_mv3_rapport.sql
mysql -u root -p dolibarr_db < sql/llx_mv3_notifications.sql
mysql -u root -p dolibarr_db < sql/llx_mv3_sens_pose.sql
mysql -u root -p dolibarr_db < sql/llx_mv3_mobile_users.sql
```

## Vérification après installation

Lancer le diagnostic complet via:
- URL: `https://votre-domaine.com/custom/mv3pro_portail/api/v1/debug.php`
- Ou via la PWA: Menu Debug

Le diagnostic affichera maintenant les erreurs SQL complètes pour identifier les tables manquantes.
