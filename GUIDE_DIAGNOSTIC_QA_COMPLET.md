# Guide du diagnostic QA complet - MV3 PRO Portail

## Vue d'ensemble

Un système de diagnostic automatisé en 3 niveaux pour tester l'intégralité de l'application MV3 PRO Portail.

---

## Les 3 niveaux de tests

### 🌟 Niveau 1 : Smoke Tests (Lecture)

**Objectif** : Vérifier que toutes les pages et endpoints de base fonctionnent

**Tests effectués** :
- ✅ **16 pages PWA** (login, dashboard, planning, rapports, régie, etc.)
- ✅ **7 endpoints API liste** (planning, rapports, notifications, etc.)
- ✅ **7 tables BDD** (config, error_log, mobile_users, rapport, etc.)
- ✅ **5 fichiers structure** (classes, bootstrap, PWA index, assets)

**Caractéristiques** :
- Lecture uniquement (GET)
- Aucune modification de données
- Vérification de disponibilité
- Test de connectivité BDD

**Résultats attendus** :
- Status : OK (✅)
- HTTP Code : 200
- Tables : X rows
- Fichiers : X KB

---

### ⚡ Niveau 2 : Tests Fonctionnels (Actions)

**Objectif** : Tester les boutons/formulaires avec des données réelles

**Tests effectués** :
- ✅ **Endpoints View avec IDs réels** (récupérés dynamiquement depuis les listes)
  - Planning view (ID réel du dernier événement)
  - Rapport view (ID réel du dernier rapport)
  - Matériel view (ID réel)
- ✅ **Actions POST/PUT/DELETE**
  - Marquer notification comme lue
  - Créer un rapport test
  - Modifier un matériel
  - Supprimer un rapport test

**Caractéristiques** :
- Utilise des **IDs réels** récupérés dynamiquement
- Teste les formulaires et boutons
- Vérifie les réponses JSON
- Extrait debug_id et SQL errors si erreur

**⚠️ Important** :
- Ces tests **modifient les données**
- Recommandé en **mode DEV uniquement**
- Admin uniquement (require token)

**Résultats attendus** :
- Status : OK (✅)
- HTTP Code : 200 ou 201
- Debug ID : Si erreur
- SQL Error : Si erreur BDD

---

### 🔐 Niveau 3 : Tests Permissions

**Objectif** : Vérifier les droits d'accès et la sécurité

**Tests effectués** :
- ✅ **Mode DEV status**
  - Vérifier si ON ou OFF
  - Config affichée clairement
- ✅ **Mode DEV protection**
  - API bloque non-admins (expect 503)
  - Admins gardent accès complet
- ✅ **Accès fichiers sécurisés**
  - Avec token valide : OK (200)
  - Sans token : Refusé (expect 401)
  - Fichier inexistant : 404
- ✅ **Permissions admin vs employé**
  - Admin voit tout le planning
  - Employé voit seulement ses RDV
  - RLS appliqué correctement

**Caractéristiques** :
- Teste la sécurité
- Vérifie les expected errors (401, 403, 503)
- Valide le mode DEV
- Contrôle RLS planning

**Résultats attendus** :
- Mode DEV : ON/OFF clairement indiqué
- Blocage non-admins : 503 (OK si mode DEV ON)
- Fichiers sans token : 401 (expected)
- Permissions respectées

---

## Comment utiliser le diagnostic

### Accès

1. Se connecter à Dolibarr en tant qu'admin
2. Aller dans : **Configuration > Modules/Applications > MV3 PRO Portail > Diagnostic QA**
3. URL directe : `https://crm.mv-3pro.ch/custom/mv3pro_portail/admin/diagnostic.php`

### Lancer les tests

**Option 1 : Diagnostic complet (tous niveaux)**
```
Cliquer sur : "🚀 Lancer diagnostic complet (tous niveaux)"
```
- Exécute les 3 niveaux d'un coup
- Durée : ~30 secondes
- Tests : ~40 tests

**Option 2 : Par niveau**
```
Niveau 1 : Smoke tests → Tests de lecture uniquement
Niveau 2 : Tests fonctionnels → Tests avec actions (mode DEV recommandé)
Niveau 3 : Permissions → Tests de sécurité
```

### Interpréter les résultats

#### Résumé global

```
Total : 40 tests
✅ OK : 35 (87%)
⚠️ Warning : 3 (8%)
❌ Error : 2 (5%)
Taux de réussite : 87%
```

**Taux de réussite** :
- ✅ **≥ 80%** : Très bon (vert)
- ⚠️ **60-79%** : Acceptable (orange)
- ❌ **< 60%** : Problèmes critiques (rouge)

#### Détails des tests

Chaque test affiche :

| Colonne | Description | Exemple |
|---------|-------------|---------|
| **Test** | Nom du test | 🔌 API - Planning view (ID: 74049) |
| **Status** | Résultat | ✅ OK / ⚠️ WARNING / ❌ ERROR |
| **HTTP** | Code HTTP | 200, 401, 500, etc. |
| **Temps (ms)** | Temps de réponse | 245 ms |
| **Debug ID** | ID unique si erreur | MV3-20260109-ABC12345 (cliquable) |
| **SQL Error** | Erreur SQL si BDD | Table 'llx_xxx' doesn't exist |

#### Status des tests

**✅ OK (Vert)**
- Le test a réussi
- HTTP 200 ou 201
- Ou expected error (401, 503 si attendu)

**⚠️ WARNING (Orange)**
- Le test a partiellement réussi
- HTTP 4xx (client error)
- Ressource non trouvée mais système OK

**❌ ERROR (Rouge)**
- Le test a échoué
- HTTP 5xx (server error)
- Erreur SQL
- Timeout
- Système non fonctionnel

---

## Analyse des erreurs

### Erreur avec debug_id

Si un test affiche un **debug_id**, cliquer dessus pour voir :
- Message d'erreur complet
- Erreur SQL détaillée
- Stack trace
- Request/Response data
- User agent, IP, date

**Exemple** :
```
Test : 🔌 API - Planning view (ID: 74049)
Status : ❌ ERROR
HTTP : 500
Debug ID : MV3-20260109-ABC12345 [cliquer]

→ Ouvre le Journal d'erreurs avec tous les détails
→ Erreur SQL : Table 'llx_mv3_planning_files' doesn't exist
→ Fix : Créer la table manquante
```

### Erreur sans debug_id

Si un test échoue sans debug_id :
- Vérifier le **SQL Error** affiché
- Vérifier le **HTTP Code**
- Lancer le test niveau 1 pour isoler le problème

---

## Cas d'usage pratiques

### Cas 1 : Après une mise à jour

```
1. Uploader les nouveaux fichiers
2. Lancer "Diagnostic complet"
3. Vérifier taux de réussite ≥ 80%
4. Si ERROR : Consulter debug_id pour identifier le problème
5. Corriger et relancer
```

### Cas 2 : Employé reporte un bug

```
Employé : "Le planning ne charge pas"

Admin :
1. Aller dans Diagnostic QA
2. Lancer "Niveau 1 : Smoke tests"
3. Voir : 🔌 API - Planning list → ❌ ERROR (500)
4. Cliquer sur debug_id
5. Voir erreur SQL : Table 'llx_actioncomm' doesn't exist
6. Fix : Vérifier tables Dolibarr
```

### Cas 3 : Tester le mode DEV

```
1. Activer mode DEV dans Configuration
2. Lancer "Niveau 3 : Permissions"
3. Vérifier : 🔐 Mode DEV - API bloque non-admin → ✅ OK (503)
4. Désactiver mode DEV
5. Relancer Niveau 3
6. Vérifier : API accessible à tous
```

### Cas 4 : Tester les fichiers sécurisés

```
1. Lancer "Niveau 3 : Permissions"
2. Voir :
   - 🔐 Accès fichier avec token → ✅ OK (200)
   - 🔐 Accès fichier SANS token → ✅ OK (401 expected)
3. Si les deux sont OK : Sécurité fichiers OK
4. Si ERROR : Vérifier endpoint planning_file.php
```

---

## Exporter les résultats

### Export JSON

Après avoir lancé les tests :
1. Cliquer sur **"📥 Exporter JSON"**
2. Fichier téléchargé : `diagnostic_qa_mv3pro_2026-01-09_14-30-45.json`

**Contenu JSON** :
```json
{
  "date": "2026-01-09 14:30:45",
  "test_level": "all",
  "stats": {
    "total": 40,
    "ok": 35,
    "warning": 3,
    "error": 2,
    "unknown": 0
  },
  "results": {
    "level1_frontend_pages": [...],
    "level1_api_list": [...],
    "level2_api_view": [...],
    "level3_permissions": [...]
  }
}
```

**Utilisation** :
- Archivage
- Comparaison avant/après
- Rapport pour support
- Analyse automatisée

---

## Ajouter de nouveaux tests

### Principe

Le système est **évolutif**. Pour ajouter un nouveau test, il suffit d'ajouter **1 ligne** dans `$tests_config`.

### Test page PWA (Niveau 1)

```php
$tests_config['level1_frontend_pages'][] = [
    'name' => '📱 PWA - Ma nouvelle page',
    'url' => $full_pwa_url.'#/ma-page',
    'method' => 'GET'
];
```

### Test endpoint API (Niveau 1)

```php
$tests_config['level1_api_list'][] = [
    'name' => '🔌 API - Mon endpoint',
    'url' => $full_api_url.'mon_endpoint.php',
    'method' => 'GET',
    'requires_auth' => true
];
```

### Test avec ID réel (Niveau 2)

```php
// Récupérer un ID réel
$mon_id = get_real_id($db, 'ma_table', 'condition');

// Ajouter le test
$tests_config['level2_api_view'][] = [
    'name' => '🔌 API - Mon view (ID: '.$mon_id.')',
    'url' => $full_api_url.'mon_view.php?id='.$mon_id,
    'method' => 'GET',
    'requires_auth' => true
];
```

### Test action POST (Niveau 2)

```php
$tests_config['level2_api_actions'][] = [
    'name' => '🔌 API - Créer mon objet',
    'url' => $full_api_url.'mon_create.php',
    'method' => 'POST',
    'data' => [
        'titre' => 'Test',
        'description' => 'Test automatique'
    ],
    'requires_auth' => true
];
```

### Test permission (Niveau 3)

```php
$tests_config['level3_permissions'][] = [
    'name' => '🔐 Permissions - Mon test',
    'url' => $full_api_url.'mon_endpoint.php',
    'method' => 'GET',
    'expect_403' => true // Attend une erreur 403
];
```

### Expected errors

Pour tester les erreurs attendues :

```php
'expect_401' => true  // Attend 401 Unauthorized (OK si reçu)
'expect_403' => true  // Attend 403 Forbidden (OK si reçu)
'expect_503' => true  // Attend 503 Service Unavailable (OK si reçu)
```

---

## Fonctions helper disponibles

### get_real_id()

Récupère un ID réel depuis une table :

```php
// Dernier ID de la table
$id = get_real_id($db, 'actioncomm', '1=1');

// ID avec condition
$id = get_real_id($db, 'mv3_rapport', 'statut = 1');

// ID d'une notification non lue
$id = get_real_id($db, 'mv3_notifications', 'is_read = 0');
```

### get_test_admin_token()

Récupère un token mobile admin valide :

```php
$token = get_test_admin_token($db);

// Utiliser dans les tests
$test = [
    'url' => '...',
    'requires_auth' => true
];
$result = run_http_test($test, $token);
```

### run_http_test()

Exécute un test HTTP et retourne les résultats :

```php
$test = [
    'name' => 'Mon test',
    'url' => 'https://...',
    'method' => 'GET',
    'data' => [...], // Optional pour POST/PUT
    'expect_401' => false // Optional
];

$result = run_http_test($test, $auth_token);

// Résultat :
[
    'name' => 'Mon test',
    'status' => 'OK',
    'http_code' => 200,
    'response_time' => 245.5,
    'error_message' => null,
    'debug_id' => null,
    'sql_error' => null,
    'details' => []
]
```

---

## Maintenance et évolution

### Ajouter un nouveau module

Quand vous ajoutez un nouveau module (ex: "Devis") :

1. Ajouter la page PWA :
```php
$tests_config['level1_frontend_pages'][] = [
    'name' => '📱 PWA - Devis list',
    'url' => $full_pwa_url.'#/devis',
    'method' => 'GET'
];
```

2. Ajouter l'endpoint API :
```php
$tests_config['level1_api_list'][] = [
    'name' => '🔌 API - Devis list',
    'url' => $full_api_url.'devis_list.php',
    'method' => 'GET',
    'requires_auth' => true
];
```

3. Ajouter la table BDD :
```php
$tests_config['level1_database'][] = [
    'name' => '🗄️ Table - mv3_devis',
    'table' => 'mv3_devis'
];
```

4. Ajouter le test view avec ID réel :
```php
$devis_id = get_real_id($db, 'mv3_devis', '1=1');
if ($devis_id) {
    $tests_config['level2_api_view'][] = [
        'name' => '🔌 API - Devis view (ID: '.$devis_id.')',
        'url' => $full_api_url.'devis_view.php?id='.$devis_id,
        'method' => 'GET',
        'requires_auth' => true
    ];
}
```

**C'est tout !** Le diagnostic testera automatiquement le nouveau module.

---

## Dépannage

### Problème : Aucun token admin trouvé

**Erreur** : Les tests niveau 2 et 3 échouent avec "No token"

**Solution** :
1. Vérifier qu'il existe un utilisateur mobile admin actif
2. Vérifier qu'il a une session valide (non expirée)
3. Se connecter à la PWA avec un compte admin
4. Relancer le diagnostic

### Problème : get_real_id() retourne null

**Erreur** : Tests niveau 2 ne s'exécutent pas

**Solution** :
1. Vérifier que la table contient au moins 1 ligne
2. Vérifier la condition SQL
3. Créer une donnée test si besoin

### Problème : Tous les tests échouent

**Erreur** : 100% ERROR

**Solution** :
1. Vérifier que les tables SQL sont créées
2. Vérifier que les fichiers PHP sont uploadés
3. Vérifier les permissions (644 pour PHP)
4. Vérifier les URLs dans la config

### Problème : Expected errors ne sont pas OK

**Erreur** : Test avec expect_401 est ERROR au lieu de OK

**Solution** :
1. Vérifier que le code attendu est bien reçu
2. Vérifier la logique dans run_http_test()
3. Vérifier que l'endpoint retourne bien le bon code

---

## Checklist avant mise en production

- [ ] Lancer diagnostic complet (tous niveaux)
- [ ] Taux de réussite ≥ 90%
- [ ] Aucune ERROR critique (500, SQL)
- [ ] Mode DEV désactivé
- [ ] Test permissions OK (niveau 3)
- [ ] Exporter JSON pour archivage
- [ ] Vérifier journal d'erreurs vide
- [ ] Tester avec compte employé
- [ ] Tester accès fichiers
- [ ] Vérifier planning employé (voit seulement ses RDV)

---

## Support

En cas de problème :

1. **Lancer le diagnostic complet**
2. **Identifier les tests ERROR** avec debug_id
3. **Consulter le Journal d'erreurs** pour détails SQL
4. **Vérifier les tables BDD** (niveau 1)
5. **Vérifier les fichiers** (niveau 1)
6. **Exporter JSON** pour analyse approfondie

Contact : Voir journal d'erreurs avec debug_id pour détails complets

---

**Date** : 2026-01-09
**Version** : 2.0.0
**Système** : MV3 PRO Portail
