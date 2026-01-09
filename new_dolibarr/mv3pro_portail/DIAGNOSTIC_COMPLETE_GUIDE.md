# 🔬 Guide Complet - Système de Diagnostic MV3 PRO

## 📊 Vue d'ensemble

Le système de diagnostic MV3 PRO comprend **2 outils complémentaires** :

| Outil | Usage | Quand l'utiliser |
|-------|-------|------------------|
| `diagnostic.php` | **QA complet automatisé** | Tests systématiques, score global |
| `diagnostic_deep.php` | **Analyse approfondie d'erreurs** | Trouver la source exacte d'un problème |

## 🎯 Workflow recommandé

```
1. diagnostic.php
   → Affiche : 79% OK, 7 erreurs, 30 warnings
   ↓
2. Cliquer sur "Lancer le diagnostic approfondi"
   ↓
3. diagnostic_deep.php
   → Affiche : Fichier login.php:142 - "User not found in llx_mv3_mobile_users"
   ↓
4. Appliquer la solution
   → create_diagnostic_user.php
   ↓
5. Re-lancer diagnostic.php
   → 95% OK ✅
```

---

## 📋 1. Diagnostic Standard (diagnostic.php)

### Objectif
Tests automatisés complets sur 3 niveaux

### URL
```
https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/diagnostic.php
```

### Tests inclus

#### NIVEAU 1 - Smoke Tests (lecture seule)
- ✅ Login/Logout réel avec token
- ✅ 16 pages PWA (index, planning, rapports, régie, etc.)
- ✅ 7 endpoints API (listes)
- ✅ 8 tables BDD
- ✅ 5 fichiers structure

#### NIVEAU 2 - Tests fonctionnels (avec IDs réels)
- Planning : List + Detail + Attachments + PWA pages
- Rapports : CRUD complet + PDF + PWA pages
- Notifications : Create + Mark Read + Delete
- Sens de pose : Create + Sign + PDF + Delete

#### NIVEAU 3 - Tests permissions
- Mode DEV (admin vs non-admin)
- Admin vs Employé
- Accès fichiers

### Sortie

```
📊 Résumé global
┌───────┬────────┬─────────┬───────┬──────┐
│ Total │ ✅ OK  │ ⚠️ Warn │ ❌ Err│ Taux │
├───────┼────────┼─────────┼───────┼──────┤
│  75   │   38   │   30    │   7   │ 79%  │
└───────┴────────┴─────────┴───────┴──────┘

🔐 NIVEAU 1 - Authentification
┌────────────────────────────┬────────┬──────┬──────┐
│ Test                       │ Status │ HTTP │ Temps│
├────────────────────────────┼────────┼──────┼──────┤
│ Auth - Login               │ ❌ ERR │ 401  │ 0 ms │
└────────────────────────────┴────────┴──────┴──────┘
```

### Avantages
- Vision globale complète
- Score de santé du système
- Tests automatisés répétables
- Historique des résultats

### Limites
- N'affiche que HTTP codes
- Pas de détail sur la source de l'erreur
- Nécessite diagnostic_deep pour debug

---

## 🔬 2. Diagnostic Approfondi (diagnostic_deep.php)

### Objectif
Analyser en profondeur **une erreur spécifique**

### URL
```
https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/diagnostic_deep.php
```

### Informations affichées

#### Pour le LOGIN
1. **Vérifications BDD**
   ```
   user_exists              : ❌ Non
   user_id                  : -
   user_active              : -
   password_hash_format     : -
   password_match_local     : -
   sessions_table_exists    : ✅ Oui
   sessions_count           : 15
   ```

2. **Appel API détaillé**
   ```
   URL          : .../api/v1/auth/login.php
   HTTP Code    : 401
   Content-Type : application/json
   Response Time: 0.045 s
   cURL Error   : -
   ```

3. **Réponse JSON complète**
   ```json
   {
     "success": false,
     "error": "Identifiants invalides",
     "debug_id": "mv3_20260109_143252_abc123"
   }
   ```

4. **Log d'erreur détaillé** (si disponible)
   ```
   Fichier     : api/v1/auth/login.php:142
   Type        : AUTH_ERROR
   Message     : User not found
   SQL Error   : -
   Stack Trace : ...
   ```

5. **Solution proposée**
   ```
   ✅ SOLUTION
   L'utilisateur diagnostic@test.local n'existe pas
   → Créer l'utilisateur avec admin/create_diagnostic_user.php
   ```

#### Pour les ENDPOINTS API
- URL testée
- HTTP code
- Temps de réponse
- Headers
- Réponse complète
- Debug ID si erreur
- Log d'erreur avec fichier + ligne

#### Historique des erreurs
```
📋 Dernières erreurs (60 min)
┌──────────┬─────────────────┬───────────┬─────────────────────┬──────────────────┐
│ Date     │ Endpoint        │ Type      │ Message             │ Fichier          │
├──────────┼─────────────────┼───────────┼─────────────────────┼──────────────────┤
│ 14:32:15 │ auth/login.php  │ AUTH_ERR  │ Invalid credentials │ login.php:142    │
│ 14:31:08 │ planning.php    │ SQL_ERR   │ Table doesn't exist │ planning.php:89  │
│ 14:30:42 │ rapports.php    │ AUTH_ERR  │ Token expired       │ _bootstrap.php:67│
└──────────┴─────────────────┴───────────┴─────────────────────┴──────────────────┘
```

### Avantages
- Fichier PHP exact + ligne précise
- Erreur SQL complète
- Stack trace complète
- Vérifications BDD détaillées
- Test local du password hash
- Solution proposée

### Quand l'utiliser
- Erreur 401, 500, 403
- Login qui échoue
- Endpoint qui plante
- Erreur SQL
- Debug d'un problème spécifique

---

## 🛠️ 3. Outil de création utilisateur (create_diagnostic_user.php)

### Objectif
Créer automatiquement l'utilisateur de test pour le diagnostic

### URL
```
https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/create_diagnostic_user.php
```

### Fonctionnalités
1. **Vérifier** si l'utilisateur existe
2. **Créer** l'utilisateur avec les credentials de config
3. **Afficher** les informations de l'utilisateur
4. **Lien** direct vers le diagnostic

### Credentials utilisés
```
Email    : llx_mv3_config.DIAGNOSTIC_USER_EMAIL
Password : llx_mv3_config.DIAGNOSTIC_USER_PASSWORD

Par défaut :
Email    : diagnostic@test.local
Password : DiagTest2026!
```

### Processus
```
1. Lire les credentials depuis llx_mv3_config
2. Vérifier si l'utilisateur existe déjà
3. Si non : Créer avec password_hash(PASSWORD_DEFAULT)
4. Insérer dans llx_mv3_mobile_users
5. Afficher le succès + lien vers diagnostic
```

---

## 📁 4. Logs d'erreurs (errors.php)

### Objectif
Historique complet de toutes les erreurs du système

### URL
```
https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/errors.php
```

### Informations
- Date/heure de chaque erreur
- Type d'erreur (AUTH, SQL, API, etc.)
- Message complet
- Fichier + ligne
- Debug ID
- Stack trace
- Endpoint concerné
- User agent

### Filtres
- Par date (7j, 30j, tout)
- Par type d'erreur
- Par endpoint
- Recherche par debug_id

---

## 📊 Comparaison des outils

| Aspect | diagnostic.php | diagnostic_deep.php | create_diagnostic_user.php | errors.php |
|--------|----------------|---------------------|----------------------------|------------|
| **Usage** | QA complet | Debug erreur | Créer user test | Historique |
| **Tests** | 75 automatiques | 1 approfondi | - | - |
| **Détail** | HTTP code | Fichier + ligne | - | Tous logs |
| **Temps** | ~30 sec | ~5 sec | Instantané | Instantané |
| **Quand** | Test régulier | Erreur détectée | Setup initial | Audit |

---

## 🎯 Cas d'usage pratiques

### Cas 1 : Premier déploiement

```
1. create_diagnostic_user.php
   → Créer l'utilisateur de test

2. diagnostic.php (NIVEAU 1)
   → Vérifier que tout charge

3. Si OK : diagnostic.php (NIVEAU 2)
   → Tester les fonctionnalités

4. Si OK : diagnostic.php (NIVEAU 3)
   → Tester les permissions
```

### Cas 2 : Login échoue (401)

```
1. diagnostic.php
   → Constate : Auth Login → 401

2. Cliquer sur "Diagnostic approfondi"
   ↓
3. diagnostic_deep.php
   → Affiche : "user_exists: ❌ Non"
   → Solution : Créer l'utilisateur

4. create_diagnostic_user.php
   → Créer l'utilisateur

5. diagnostic.php
   → ✅ Auth Login → 200 OK
```

### Cas 3 : Endpoint plante (500)

```
1. diagnostic.php
   → Constate : Planning → 500 (debug_id: abc123)

2. diagnostic_deep.php
   → Affiche :
     Fichier: api/v1/planning.php:89
     SQL Error: Table 'llx_actioncomm' doesn't exist

3. Appliquer solution
   → Créer la table manquante

4. diagnostic.php
   → ✅ Planning → 200 OK
```

### Cas 4 : Audit de santé régulier

```
1. diagnostic.php (tous les lundis)
   → Score : 95% OK
   → Archiver le résultat

2. Si score < 90%
   → diagnostic_deep.php
   → errors.php
   → Analyser les erreurs
   → Appliquer les corrections
```

---

## 📋 Checklist de maintenance

### Quotidien
- [ ] Vérifier errors.php (erreurs nouvelles ?)

### Hebdomadaire
- [ ] Lancer diagnostic.php NIVEAU 1
- [ ] Score > 90% ?
- [ ] Si non : diagnostic_deep.php

### Mensuel
- [ ] Lancer diagnostic.php NIVEAU 1-2-3 complet
- [ ] Archiver les résultats
- [ ] Comparer avec mois précédent

### Après chaque déploiement
- [ ] Lancer diagnostic.php NIVEAU 1
- [ ] Toutes les pages chargent ?
- [ ] Toutes les tables existent ?
- [ ] Login fonctionne ?

---

## 🔗 Liens rapides

| Outil | URL |
|-------|-----|
| Diagnostic standard | `admin/diagnostic.php` |
| Diagnostic approfondi | `admin/diagnostic_deep.php` |
| Créer user test | `admin/create_diagnostic_user.php` |
| Logs d'erreurs | `admin/errors.php` |
| Configuration | `admin/config.php` |

---

## 📞 Support

### Erreurs fréquentes

**401 - Identifiants invalides**
→ diagnostic_deep.php → create_diagnostic_user.php

**500 - Erreur serveur**
→ diagnostic_deep.php → Voir fichier + ligne + SQL error

**403 - Accès refusé**
→ diagnostic.php NIVEAU 3 → Vérifier permissions

**Table doesn't exist**
→ sql/INSTALLATION_COMPLETE.sql

---

**Date** : 2026-01-09
**Version** : 1.0
**Système** : MV3 PRO Portail Diagnostic
