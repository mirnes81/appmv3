# Amélioration UX - Messages d'erreur Login

## 🎯 Objectif

Améliorer l'expérience utilisateur lors des erreurs de connexion en fournissant des messages clairs et des conseils pratiques.

## ✅ Modifications Appliquées

### 1. Backend API (`mobile_app/api/auth.php`)

#### Messages d'erreur améliorés:

**Compte introuvable (HTTP 401)**
```json
{
  "success": false,
  "message": "Compte mobile introuvable ou mot de passe incorrect.",
  "hint": "Créez ou éditez l'utilisateur mobile dans Dolibarr: Accueil > MV3 PRO > Gestion Utilisateurs Mobiles"
}
```

**Compte désactivé (HTTP 403)**
```json
{
  "success": false,
  "message": "Compte désactivé. Contactez votre administrateur.",
  "hint": "Activez le compte dans Dolibarr: Gestion Utilisateurs Mobiles"
}
```

**Compte verrouillé (HTTP 403)**
```json
{
  "success": false,
  "message": "Compte verrouillé temporairement. Réessayez dans 12 minute(s).",
  "hint": "Sécurité: Compte verrouillé automatiquement après 5 tentatives échouées (15 min)"
}
```

**Mot de passe incorrect (HTTP 401)**
```json
{
  "success": false,
  "message": "Mot de passe incorrect.",
  "hint": "Il vous reste 3 tentative(s) avant verrouillage automatique (15 min)."
}
```

**5e tentative échouée → Verrouillage (HTTP 403)**
```json
{
  "success": false,
  "message": "Compte verrouillé pour 15 minutes après 5 tentatives échouées.",
  "hint": "Protection anti-brute-force activée. Réessayez dans 15 minutes ou contactez l'administrateur."
}
```

### 2. Frontend PWA

#### Fichiers modifiés:

**`pwa/src/lib/api.ts`**
- Ajout du champ `hint?: string` dans `LoginResponse`

**`pwa/src/contexts/AuthContext.tsx`**
- Propagation du `hint` dans l'erreur throwée

**`pwa/src/pages/Login.tsx`**
- Affichage du hint sous le message d'erreur
- Style avec séparateur et icône 💡
- État séparé pour gérer le hint

#### Rendu visuel:

```
┌─────────────────────────────────────────┐
│ ⚠️  Mot de passe incorrect.            │
│ ─────────────────────────────────────── │
│ 💡 Il vous reste 3 tentative(s) avant  │
│    verrouillage automatique (15 min).  │
└─────────────────────────────────────────┘
```

## 🔐 Sécurité

Les modifications sont **purement cosmétiques** et n'affectent pas la sécurité:

- ✅ Protection brute-force maintenue (5 tentatives max)
- ✅ Verrouillage 15 minutes conservé
- ✅ Hachage bcrypt inchangé
- ✅ Validation des credentials identique

## 📊 Impact UX

### Avant:
- ❌ "Email ou mot de passe incorrect" (pas de contexte)
- ❌ "Compte verrouillé. Réessayez dans X minutes" (pas d'explication)
- ❌ Utilisateur ne sait pas où créer un compte

### Après:
- ✅ Message clair + conseil actionnable
- ✅ Indication du chemin dans Dolibarr
- ✅ Compteur de tentatives restantes
- ✅ Explication du mécanisme de sécurité

## 🧪 Tests

### Test 1: Compte inexistant
```bash
curl -X POST http://dolibarr/custom/mv3pro_portail/mobile_app/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"email":"inconnu@example.com","password":"test"}'

# Attendu: HTTP 401 + hint avec chemin Gestion Utilisateurs
```

### Test 2: Mot de passe incorrect (3 fois)
```bash
# 1ère tentative
curl ... -d '{"email":"mirnes@mv-3pro.ch","password":"wrong"}'
# → "Il vous reste 4 tentative(s)..."

# 2e tentative
# → "Il vous reste 3 tentative(s)..."

# 3e tentative
# → "Il vous reste 2 tentative(s)..."
```

### Test 3: Verrouillage après 5 tentatives
```bash
# 5e tentative
curl ... -d '{"email":"mirnes@mv-3pro.ch","password":"wrong"}'
# → HTTP 403 "Compte verrouillé pour 15 minutes"
```

## 📝 Utilisateur de Test Créé

**Fichier:** `sql/create_user_mirnes.sql`

```
Email:    mirnes@mv-3pro.ch
Password: mirnes12345
Rôle:     OUVRIER
Droits:   Tous (rapports, régie, sens pose, planning, matériel)
```

**Pour exécuter:**
```bash
# Dans MySQL/MariaDB
mysql -u dolibarr -p dolibarr < sql/create_user_mirnes.sql

# Ou via phpMyAdmin
# Copier-coller le contenu de create_user_mirnes.sql
```

## 🚀 Résultat

L'utilisateur obtient maintenant:
1. Un message d'erreur **clair et explicite**
2. Un **conseil pratique** pour résoudre le problème
3. Une **indication du chemin** dans Dolibarr (si applicable)
4. Un **compteur de tentatives** avant verrouillage
5. Une **explication du mécanisme** de sécurité

Tout cela **sans compromettre la sécurité** du système.

---

**Date:** 2026-01-07  
**Version:** MV3 PRO PWA v1.0
