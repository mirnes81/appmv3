# ✅ FIX - Login 401 "Identifiants invalides"

## 📊 Diagnostic actuel

```
Résumé : 79% réussite (38 OK, 30 WARNING, 7 ERROR)

❌ Auth - Login → 401 "Identifiants invalides"
⚠️ Tous les endpoints protégés → SKIP (pas de token)
```

## 🔍 Cause du problème

Le système de diagnostic QA essaie de se connecter avec ces credentials :

- **Email** : `diagnostic@test.local`
- **Password** : `DiagTest2026!`

**Ces credentials sont stockés dans la config** :
- `llx_mv3_config.DIAGNOSTIC_USER_EMAIL`
- `llx_mv3_config.DIAGNOSTIC_USER_PASSWORD`

**Mais l'utilisateur n'existe pas dans** :
- `llx_mv3_mobile_users`

## ✅ Solution : 2 options

### Option 1 : Créer l'utilisateur de test (RECOMMANDÉ)

**Via interface web** :

1. Aller sur : `https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/create_diagnostic_user.php`

2. Cliquer sur **"Créer l'utilisateur diagnostic"**

3. Relancer le diagnostic : `https://dolibarr.mirnes.ch/custom/mv3pro_portail/admin/diagnostic.php`

**Résultat attendu** :
```
✅ Auth - Login → 200 OK (token obtenu)
✅ Tous les endpoints protégés → 200 OK
Score : 95%+ (tous les tests passent)
```

---

### Option 2 : Utiliser un utilisateur existant

Si vous préférez utiliser un utilisateur déjà existant dans `llx_mv3_mobile_users` :

**1. Vérifier les utilisateurs existants** :

```sql
SELECT id, email, nom, prenom, role, active
FROM llx_mv3_mobile_users
WHERE active = 1;
```

**2. Mettre à jour la config** :

```sql
UPDATE llx_mv3_config
SET value = 'email@existant.com'
WHERE name = 'DIAGNOSTIC_USER_EMAIL';

UPDATE llx_mv3_config
SET value = 'MotDePasseReel'
WHERE name = 'DIAGNOSTIC_USER_PASSWORD';
```

**3. Relancer le diagnostic**

---

## 📁 Fichiers créés

| Fichier | Description |
|---------|-------------|
| `admin/create_diagnostic_user.php` | Script web de création utilisateur |
| `sql/create_diagnostic_user.sql` | Script SQL manuel (si besoin) |
| `FIX_LOGIN_401_CREDENTIALS.md` | Ce guide |

---

## 🎯 Résultat attendu

**Après création de l'utilisateur** :

```
NIVEAU 1 - Auth Login : ✅ 200 OK (token obtenu)
NIVEAU 1 - Smoke tests : ✅ 100% (tous les endpoints protégés OK)
NIVEAU 2 - Tests fonctionnels : ✅ 95%+ (accès complet)

Score global : 95%+
```

---

## 🔧 Dépannage

### Erreur "User exists already"

L'utilisateur existe mais le mot de passe ne correspond pas.

**Solution** : Réinitialiser le mot de passe

```sql
UPDATE llx_mv3_mobile_users
SET password_hash = '$2y$10$...' -- Voir script create_diagnostic_user.php
WHERE email = 'diagnostic@test.local';
```

### Erreur "Table doesn't exist"

La table `llx_mv3_mobile_users` n'existe pas.

**Solution** : Installer le module

```sql
SOURCE /custom/mv3pro_portail/sql/INSTALLATION_COMPLETE.sql;
```

---

## 📞 Support

Si le problème persiste après création de l'utilisateur :

1. Vérifier les logs : `admin/errors.php`
2. Tester le login manuellement :
   ```bash
   curl -X POST https://dolibarr.mirnes.ch/custom/mv3pro_portail/api/v1/auth/login.php \
     -H "Content-Type: application/json" \
     -d '{"email":"diagnostic@test.local","password":"DiagTest2026!"}'
   ```
3. Vérifier la session créée :
   ```sql
   SELECT * FROM llx_mv3_mobile_sessions
   WHERE fk_user = (SELECT id FROM llx_mv3_mobile_users WHERE email = 'diagnostic@test.local')
   ORDER BY date_creation DESC LIMIT 1;
   ```

---

**Date** : 2026-01-09
**Statut** : ✅ Solution prête
**Action** : Uploader `admin/create_diagnostic_user.php` puis exécuter
