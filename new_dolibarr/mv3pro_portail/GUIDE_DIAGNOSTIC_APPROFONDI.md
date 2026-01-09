# 🔬 Guide - Diagnostic Approfondi

## 📊 Nouveau fichier créé

**Fichier** : `admin/diagnostic_deep.php`

**URL** : `https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/diagnostic_deep.php`

## 🎯 Objectif

Contrairement au diagnostic standard qui montre juste "401 - Identifiants invalides", ce diagnostic approfondi affiche:

### ✅ Ce qui est vérifié

1. **Base de données**
   - L'utilisateur existe-t-il dans `llx_mv3_mobile_users` ?
   - Le mot de passe correspond-il au hash ?
   - Le format du hash est-il correct (bcrypt) ?
   - L'utilisateur est-il actif ?
   - Les tables existent-elles ?

2. **API**
   - Codes HTTP exacts
   - Temps de réponse
   - Erreurs cURL
   - Réponse JSON complète

3. **Logs d'erreurs**
   - Fichier PHP exact qui a planté
   - Numéro de ligne précis
   - Message d'erreur complet
   - Erreur SQL détaillée
   - Stack trace complète

4. **Historique**
   - Toutes les erreurs des 60 dernières minutes
   - Pour chaque endpoint
   - Avec debug_id pour traçabilité

## 📋 Exemple de sortie

### ❌ Cas 1 : Utilisateur n'existe pas

```
🔐 Test Login : diagnostic@test.local
❌ Login ÉCHOUÉ

📊 Vérifications Base de Données
┌─────────────────────────────┬────────┐
│ user_exists                 │ ❌ Non │
│ sessions_table_exists       │ ✅ Oui │
└─────────────────────────────┴────────┘

💡 Détails
• ❌ L'utilisateur diagnostic@test.local N'EXISTE PAS dans llx_mv3_mobile_users
• ❌ API Error: Identifiants invalides

✅ SOLUTION : Créer l'utilisateur
→ admin/create_diagnostic_user.php
```

### ❌ Cas 2 : Mot de passe incorrect

```
🔐 Test Login : diagnostic@test.local
❌ Login ÉCHOUÉ

📊 Vérifications Base de Données
┌─────────────────────────────┬──────────────┐
│ user_exists                 │ ✅ Oui       │
│ user_id                     │ 42           │
│ user_active                 │ ✅ Oui       │
│ password_hash_format        │ bcrypt (OK)  │
│ password_match_local        │ ❌ Non       │
└─────────────────────────────┴──────────────┘

💡 Détails
• ❌ Le mot de passe ne correspond PAS au hash en BDD
• ❌ API Error: Identifiants invalides

✅ SOLUTION : Le password dans llx_mv3_config ne correspond pas
→ Mettre à jour DIAGNOSTIC_USER_PASSWORD
→ OU réinitialiser le hash de l'utilisateur
```

### ❌ Cas 3 : Erreur SQL

```
🔐 Test Login : diagnostic@test.local
❌ Login ÉCHOUÉ

🐛 Log d'erreur détaillé
┌─────────────┬─────────────────────────────────────────┐
│ Fichier     │ api/v1/auth/login.php:156               │
│ Type        │ SQL_ERROR                               │
│ Message     │ Failed to create session                │
│ Erreur SQL  │ Table 'llx_mv3_mobile_sessions' doesn't exist │
└─────────────┴─────────────────────────────────────────┘

✅ SOLUTION : Table manquante
→ Exécuter sql/INSTALLATION_COMPLETE.sql
```

### ✅ Cas 4 : Tout fonctionne

```
🔐 Test Login : diagnostic@test.local
✅ Login RÉUSSI

📊 Vérifications Base de Données
┌─────────────────────────────┬──────────────┐
│ user_exists                 │ ✅ Oui       │
│ user_id                     │ 42           │
│ user_active                 │ ✅ Oui       │
│ password_hash_format        │ bcrypt (OK)  │
│ password_match_local        │ ✅ Oui       │
│ sessions_table_exists       │ ✅ Oui       │
│ sessions_count              │ 15           │
└─────────────────────────────┴──────────────┘

🌐 Appel API
┌──────────────┬────────────────────────────────┐
│ URL          │ .../api/v1/auth/login.php      │
│ HTTP Code    │ 200                            │
│ Content-Type │ application/json               │
└──────────────┴────────────────────────────────┘

🌐 Tests Endpoints API
✅ ME       - HTTP 200
✅ PLANNING - HTTP 200
✅ RAPPORTS - HTTP 200
```

## 📁 Fichiers du système de diagnostic

| Fichier | Description | Usage |
|---------|-------------|-------|
| `admin/diagnostic.php` | Diagnostic standard complet | Tests automatisés NIVEAU 1-2-3 |
| `admin/diagnostic_deep.php` | **Diagnostic approfondi** | **Analyse détaillée des erreurs** |
| `admin/create_diagnostic_user.php` | Création utilisateur test | Créer l'utilisateur diagnostic |
| `admin/errors.php` | Logs d'erreurs | Historique complet des erreurs |

## 🔧 Utilisation

### Étape 1 : Uploader le fichier

```bash
# Uploader vers
/htdocs/custom/mv3pro_portail/admin/diagnostic_deep.php
```

### Étape 2 : Accéder à la page

```
https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/diagnostic_deep.php
```

### Étape 3 : Lancer le diagnostic

Cliquer sur **"Lancer le diagnostic approfondi"**

### Étape 4 : Analyser les résultats

Le diagnostic affiche:

1. **État BDD** : L'utilisateur existe ? Password match ?
2. **État API** : HTTP codes, erreurs réseau
3. **Logs détaillés** : Fichier exact, ligne, erreur SQL
4. **Historique** : Toutes les erreurs récentes

### Étape 5 : Appliquer la solution

Le diagnostic affiche la solution exacte pour chaque problème détecté.

## 🎯 Différences avec diagnostic.php

| Aspect | diagnostic.php | diagnostic_deep.php |
|--------|----------------|---------------------|
| Tests | 75 tests automatiques | Focus sur les erreurs |
| Détail | HTTP code seulement | Fichier + ligne + SQL |
| Logs | Debug ID | Stack trace complète |
| BDD | Vérifie tables | Vérifie utilisateur + password |
| Usage | QA complet | Debugging d'erreurs |

## 📞 Cas d'usage

### Utiliser diagnostic.php quand :
- Vous voulez un **score global** (79% OK)
- Vous testez **tous les endpoints**
- Vous faites du **QA systématique**

### Utiliser diagnostic_deep.php quand :
- Vous avez une **erreur 401, 500**
- Vous voulez savoir **pourquoi** ça échoue
- Vous devez voir le **fichier PHP exact**
- Vous cherchez l'**erreur SQL précise**

## 🔍 Exemple workflow

```
1. Lancer diagnostic.php
   → Résultat : 79% OK, 7 erreurs

2. Voir erreur : "Auth Login → 401"

3. Lancer diagnostic_deep.php
   → Affiche : "L'utilisateur n'existe pas dans llx_mv3_mobile_users"

4. Appliquer solution
   → Lancer admin/create_diagnostic_user.php

5. Re-lancer diagnostic.php
   → Résultat : 95% OK ✅
```

## 📊 Exemple de table des erreurs récentes

```
📋 Dernières erreurs (1h)

Date            Endpoint              Type        Message                    Fichier
────────────────────────────────────────────────────────────────────────────────────
09/01 14:32    auth/login.php        AUTH_ERROR  Invalid credentials        api/v1/auth/login.php:142
09/01 14:31    planning.php          SQL_ERROR   Table doesn't exist        api/v1/planning.php:89
09/01 14:30    rapports.php          AUTH_ERROR  Token expired              api/v1/_bootstrap.php:67
```

## ✅ Avantages

1. **Précision** : Fichier PHP exact, ligne précise
2. **Rapidité** : Diagnostic ciblé sur les erreurs
3. **Clarté** : Affiche la cause ET la solution
4. **Traçabilité** : Debug ID pour suivre les erreurs
5. **Complet** : Stack trace + SQL error + cURL details

---

**Date** : 2026-01-09
**Auteur** : Système de diagnostic MV3 PRO
**Version** : 1.0
