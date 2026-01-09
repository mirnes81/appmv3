# Guide Diagnostic QA Complet - MV3 PRO Portail

## Vue d'ensemble

Le système de diagnostic QA offre **3 niveaux de tests automatisés** pour valider l'intégralité de l'application MV3 PRO Portail.

---

## Configuration initiale

### 1. Créer un utilisateur de diagnostic

Avant d'utiliser le diagnostic, créez un utilisateur mobile admin dédié aux tests :

```sql
-- Via phpMyAdmin ou MySQL CLI
INSERT INTO llx_mv3_mobile_users (
    nom, prenom, email, password_hash, role, is_active, date_creation
) VALUES (
    'Test', 'Diagnostic', 'diagnostic@test.local',
    '$2y$10$YourHashedPasswordHere',  -- Hash de "DiagTest2026!"
    'admin', 1, NOW()
);
```

**Alternative** : Créer l'utilisateur via l'interface admin :
- Aller dans **MV3 PRO > Configuration > Utilisateurs mobiles**
- Créer un nouvel utilisateur admin avec :
  - Email : `diagnostic@test.local`
  - Mot de passe : `DiagTest2026!`
  - Rôle : Admin

### 2. Configurer les credentials dans la config

Les credentials sont stockés dans la table `llx_mv3_config` :

```sql
-- Ces valeurs sont insérées automatiquement lors de l'installation
DIAGNOSTIC_USER_EMAIL = 'diagnostic@test.local'
DIAGNOSTIC_USER_PASSWORD = 'DiagTest2026!'
```

Pour modifier les credentials :
1. Aller dans **Configuration > MV3 PRO Portail > Configuration**
2. Modifier les valeurs de `DIAGNOSTIC_USER_EMAIL` et `DIAGNOSTIC_USER_PASSWORD`
3. Sauvegarder

---

## Les 3 niveaux de tests

### 🌟 Niveau 1 : Smoke Tests (Lecture uniquement)

**Objectif** : Vérifier que tout charge sans erreur

**Tests inclus** :
- ✅ **Authentification** : Login/Logout avec POST JSON réel
- ✅ **Pages PWA** : Toutes les pages frontend (#/dashboard, #/planning, etc.)
- ✅ **API Lists** : Tous les endpoints de liste (planning.php, rapports.php, etc.)
- ✅ **Base de données** : Vérification existence de toutes les tables
- ✅ **Fichiers** : Vérification structure et présence des fichiers

**Durée** : ~2-3 minutes

**Commande** :
```
Configuration > MV3 PRO > Diagnostic QA > "Niveau 1 : Smoke tests"
```

**Utilisation** :
- Exécuter **après chaque déploiement**
- Exécuter **quotidiennement** en production
- **Aucune modification** des données

---

### ⚡ Niveau 2 : Tests Fonctionnels (Boutons et formulaires)

**Objectif** : Tester toutes les actions utilisateur avec des IDs réels

**Tests inclus** :

#### 📋 Planning
- List : Récupération de la liste complète
- Detail : Affichage d'un planning avec ID réel récupéré depuis la liste
- Open attachment : Test d'ouverture d'un fichier attaché (inline)
- PWA page : Test de la sous-page `#/planning/:id`

#### 📝 Rapports (CRUD complet)
- List : Récupération de la liste
- View : Affichage avec ID réel
- **Create** : Création d'un rapport test (DEV mode only)
- **Update** : Modification du rapport créé
- **Submit** : Soumission du rapport
- **Delete** : Suppression du rapport test
- PWA page : Test de la sous-page `#/rapports/:id`

#### 🔔 Notifications
- List : Récupération de la liste
- Unread count : Comptage des non-lues
- **Create** : Création d'une notification test (DEV mode only)
- **Mark as read** : Marquage comme lue
- **Delete** : Suppression de la notification test (DEV mode only)

#### 📐 Sens de pose (CRUD complet)
- List : Récupération de la liste
- View : Affichage avec ID réel
- **Create** : Création d'un sens de pose test (DEV mode only)
- **Sign** : Ajout d'une signature
- **Generate PDF** : Génération du PDF
- **Delete** : Suppression du sens de pose test (DEV mode only)
- PWA page : Test de la sous-page `#/sens-pose/:id`

**Durée** : ~5-10 minutes

**Commande** :
```
Configuration > MV3 PRO > Diagnostic QA > "Niveau 2 : Tests fonctionnels"
```

**⚠️ Prérequis** :
- **Mode DEV activé** pour les tests CRUD (Create/Update/Delete)
- **Utilisateur admin** connecté
- Les tests de **lecture** (List/View) fonctionnent en mode PROD

**Utilisation** :
- Exécuter **en mode DEV** uniquement
- Exécuter **avant mise en production** pour valider les nouvelles fonctionnalités
- **Modifie les données** (crée et supprime des entrées test)

---

### 🔐 Niveau 3 : Tests Permissions

**Objectif** : Vérifier que les permissions et le mode DEV fonctionnent correctement

**Tests inclus** :
- ✅ Vérification du status mode DEV (ON/OFF)
- ✅ Blocage API en mode DEV pour non-admins (expect HTTP 503)
- ✅ Accès fichiers avec token valide (expect HTTP 200)
- ✅ Blocage fichiers sans token (expect HTTP 401)
- ✅ Vérification que les employés ne voient que leurs propres données

**Durée** : ~1-2 minutes

**Commande** :
```
Configuration > MV3 PRO > Diagnostic QA > "Niveau 3 : Permissions"
```

**Utilisation** :
- Exécuter **après activation/désactivation du mode DEV**
- Exécuter **après modifications de permissions**
- Aucune modification des données

---

## Lancer un diagnostic complet

### Via l'interface web

1. Se connecter à Dolibarr en tant qu'**admin**
2. Aller dans **Configuration > Modules/Applications > MV3 PRO Portail**
3. Cliquer sur l'onglet **"Diagnostic QA"**
4. Cliquer sur **"🚀 Lancer diagnostic complet (tous niveaux)"**
5. Attendre la fin des tests (~10-15 minutes)
6. Consulter les résultats

### Résultats affichés

Pour chaque test, vous verrez :

| Colonne | Description |
|---------|-------------|
| **Test** | Nom du test avec emoji et description |
| **Status** | ✅ OK / ⚠️ WARNING / ❌ ERROR |
| **HTTP** | Code HTTP retourné (200, 401, 500, etc.) |
| **Temps (ms)** | Temps de réponse en millisecondes |
| **Debug ID** | Identifiant unique si erreur (lien vers Journal d'erreurs) |
| **SQL Error** | Erreur SQL complète si erreur de BDD |

### Résumé global

En haut des résultats, vous verrez un tableau récapitulatif :

```
Total    OK      Warning    Error    Taux
150      145     3          2        96%
```

- **Total** : Nombre total de tests exécutés
- **OK** : Tests réussis (✅)
- **Warning** : Tests avec avertissement (⚠️)
- **Error** : Tests échoués (❌)
- **Taux** : Pourcentage de réussite (OK / Total)

---

## Exporter les résultats

Après avoir lancé un diagnostic, vous pouvez exporter les résultats en JSON :

1. Cliquer sur **"📥 Exporter JSON"**
2. Le fichier `diagnostic_qa_mv3pro_YYYY-MM-DD_HH-MM-SS.json` sera téléchargé

**Contenu du JSON** :
```json
{
  "date": "2026-01-09 14:30:00",
  "test_level": "all",
  "stats": {
    "total": 150,
    "ok": 145,
    "warning": 3,
    "error": 2,
    "unknown": 0
  },
  "results": {
    "level1_auth": [...],
    "level1_frontend_pages": [...],
    "level1_api_list": [...],
    "level2_planning": [...],
    "level2_rapports": [...],
    ...
  }
}
```

**Utilisation** :
- Archivage des résultats de tests
- Analyse automatisée (CI/CD)
- Comparaison avant/après déploiement
- Rapport de QA pour client/équipe

---

## Interpréter les résultats

### ✅ Status OK

**Tout fonctionne correctement**

Exemples :
- HTTP 200 pour une requête GET
- HTTP 201 pour une création (POST)
- HTTP 401 attendu pour un accès sans token (test permission)
- HTTP 503 attendu pour mode DEV (test permission)

### ⚠️ Status WARNING

**Fonctionne mais attention**

Exemples :
- Code HTTP inattendu mais pas bloquant
- Temps de réponse élevé (>2000ms)
- Données partielles retournées
- Token absent mais test non critique

**Action** : Vérifier les détails dans la colonne "SQL Error"

### ❌ Status ERROR

**Test échoué - Action requise**

Exemples :
- HTTP 500 (erreur serveur)
- HTTP 404 (endpoint non trouvé)
- Erreur SQL
- Timeout de connexion
- Table BDD manquante
- Fichier manquant

**Action** :
1. Cliquer sur le **Debug ID** pour voir l'erreur complète dans le Journal d'erreurs
2. Consulter la **SQL Error** pour les erreurs de BDD
3. Corriger le problème
4. Relancer le test

---

## Troubleshooting

### Erreur "Login failed"

**Cause** : Les credentials de diagnostic sont incorrects ou l'utilisateur n'existe pas

**Solution** :
1. Vérifier que l'utilisateur `diagnostic@test.local` existe dans `llx_mv3_mobile_users`
2. Vérifier que le mot de passe est correct
3. Vérifier que l'utilisateur est **actif** (`is_active = 1`)
4. Vérifier que l'utilisateur a le rôle **admin**

### Tests CRUD en ERROR

**Cause** : Mode DEV désactivé ou utilisateur non-admin

**Solution** :
1. Activer le mode DEV : **Configuration > MV3 PRO > Configuration > Mode DEV = ON**
2. Se connecter en tant qu'admin
3. Relancer le test Niveau 2

### Erreur "Table not found"

**Cause** : Tables SQL non créées

**Solution** :
1. Exécuter les scripts SQL d'installation : `/sql/INSTALLATION_RAPIDE.sql`
2. Vérifier dans phpMyAdmin que toutes les tables `llx_mv3_*` existent
3. Relancer le test

### Tests lents (>5000ms)

**Cause** : Serveur surchargé ou réseau lent

**Solution** :
1. Vérifier les ressources serveur (CPU, RAM, disque)
2. Optimiser les requêtes SQL lentes (voir Journal d'erreurs)
3. Ajouter des index sur les tables si nécessaire

---

## Automatisation (CI/CD)

Le diagnostic peut être intégré dans un pipeline CI/CD :

### Script Bash exemple

```bash
#!/bin/bash

# Lancer diagnostic complet via cURL
RESPONSE=$(curl -s -X GET \
  "https://votre-dolibarr.com/custom/mv3pro_portail/admin/diagnostic.php?action=run_tests&test_level=all" \
  -H "Cookie: DOLSESSID_xxx=your_session_id")

# Parser le JSON de résultats
STATS=$(echo "$RESPONSE" | jq '.stats')
ERROR_COUNT=$(echo "$STATS" | jq '.error')

# Fail si des erreurs
if [ "$ERROR_COUNT" -gt 0 ]; then
  echo "❌ Diagnostic failed with $ERROR_COUNT errors"
  exit 1
else
  echo "✅ Diagnostic passed"
  exit 0
fi
```

### Jenkins / GitLab CI

```yaml
diagnostic_qa:
  stage: test
  script:
    - curl -X GET "https://dolibarr.com/custom/mv3pro_portail/admin/diagnostic.php?action=run_tests&test_level=all"
    - # Parser résultats et fail si erreurs
  only:
    - main
```

---

## Ajouter de nouveaux tests

Pour ajouter vos propres tests au diagnostic, éditez `/admin/diagnostic.php` :

### Exemple : Test API personnalisé

```php
// Niveau 2 - Après les tests Sens de pose
$test = [
    'name' => '🔧 Mon module - Mon test',
    'url' => $full_api_url.'mon_module/mon_endpoint.php',
    'method' => 'GET',
    'requires_auth' => true
];
$result = run_http_test($test, $auth_token);
$all_results['level2_mon_module'][] = $result;
$stats['total']++;
$stats[strtolower($result['status'])]++;
```

### Exemple : Test avec ID réel

```php
// Récupérer un ID réel depuis votre table
$mon_id = get_real_id($db, 'ma_table', 'condition = 1');

if ($mon_id) {
    $test = [
        'name' => '🔧 Mon module - View (ID: '.$mon_id.')',
        'url' => $full_api_url.'mon_module/view.php?id='.$mon_id,
        'method' => 'GET',
        'requires_auth' => true
    ];
    $result = run_http_test($test, $auth_token);
    $all_results['level2_mon_module'][] = $result;
    $stats['total']++;
    $stats[strtolower($result['status'])]++;
}
```

### Afficher les résultats

```php
// Dans la section d'affichage des résultats
if (!empty($all_results['level2_mon_module'])) {
    display_test_results('🔧 NIVEAU 2 - Mon module : Tests personnalisés', $all_results['level2_mon_module'], true);
}
```

---

## Bonnes pratiques

### 1. Avant chaque déploiement

✅ Lancer **Niveau 1** (Smoke tests) pour vérifier que tout charge
✅ Lancer **Niveau 2** (Tests fonctionnels) en mode DEV pour valider les modifications
✅ Lancer **Niveau 3** (Permissions) pour vérifier la sécurité

### 2. Après un déploiement

✅ Lancer **Niveau 1** en production pour vérifier que tout fonctionne
✅ Exporter les résultats en JSON pour archivage
✅ Comparer avec les résultats pré-déploiement

### 3. Quotidiennement

✅ Lancer **Niveau 1** automatiquement (CI/CD ou cron)
✅ Recevoir une alerte si des erreurs apparaissent

### 4. Avant mise en production

✅ Lancer **tous les niveaux** en mode DEV
✅ Corriger toutes les erreurs ❌ et warnings ⚠️
✅ Viser un taux de réussite de **98%+**

---

## Résumé des commandes

| Action | Commande | Durée | Modifications |
|--------|----------|-------|---------------|
| Smoke tests | Niveau 1 | 2-3 min | Non |
| Tests fonctionnels | Niveau 2 | 5-10 min | Oui (DEV mode) |
| Tests permissions | Niveau 3 | 1-2 min | Non |
| Diagnostic complet | Tous niveaux | 10-15 min | Oui (DEV mode) |

---

## Support

En cas de problème avec le diagnostic QA :

1. Consulter le **Journal d'erreurs** : Configuration > MV3 PRO > Journal d'erreurs
2. Chercher le **Debug ID** dans le journal pour voir l'erreur complète
3. Vérifier les **prérequis** :
   - Tables SQL créées
   - Utilisateur diagnostic créé et actif
   - Mode DEV activé (pour tests CRUD)
   - Token API valide
4. Consulter la documentation : `/GUIDE_DIAGNOSTIC_QA_COMPLET.md`

---

**Date** : 2026-01-09
**Version** : 2.0.0
**Auteur** : MV3 PRO Development Team
