# DEV MODE: Désactivation du verrouillage anti-brute-force

Date: 2026-01-09

---

## 🎯 Objectif

Pendant le développement, permettre des tests illimités sans être bloqué 15 minutes après 5 tentatives échouées.

---

## ⚙️ Configuration

**Fichier:** `/new_dolibarr/mv3pro_portail/mobile_app/api/auth.php`

```php
// Ligne 11
define('MV3_AUTH_DISABLE_LOCK', true);  // DEV MODE
```

**Valeurs possibles:**
- `true` → Mode développement (verrouillage désactivé)
- `false` → Mode production (verrouillage actif)

---

## 🔧 Comportement

### Mode DEV (MV3_AUTH_DISABLE_LOCK = true)

**Quand locked_until est présent:**
- ❌ Ne vérifie PAS locked_until
- ✅ Laisse passer la connexion
- 📝 Log: `[MV3 AUTH] DEV_MODE: Ignoring locked_until`

**Après mot de passe incorrect:**
- ✅ Incrémente login_attempts (traçabilité)
- ❌ N'écrit JAMAIS locked_until
- 📝 Log: `[MV3 AUTH] DEV_MODE: Would lock account but disabled`
- 💬 Message: `"Tentative X/5. DEV MODE: Verrouillage désactivé."`

**Après mot de passe correct:**
- ✅ Réinitialise login_attempts = 0
- ✅ Efface locked_until = NULL
- ✅ Connexion réussie

**Résultat:**
- Vous pouvez tester autant de fois que vous voulez
- Pas de blocage 15 minutes
- Le compteur de tentatives continue de s'incrémenter (pour debug)

---

### Mode PROD (MV3_AUTH_DISABLE_LOCK = false)

**Comportement normal:**
1. Après 5 mots de passe incorrects:
   - Écrit `locked_until = NOW() + 15 minutes`
   - Refuse la connexion pendant 15 minutes
   - Message: "Compte verrouillé pour 15 minutes"

2. Si locked_until actif:
   - Refuse immédiatement la connexion
   - Message: "Réessayez dans X minute(s)"

---

## 📊 Comparaison

| Scénario | DEV MODE (true) | PROD MODE (false) |
|----------|----------------|-------------------|
| Tentative 1-4 (mauvais MDP) | Incrémente compteur | Incrémente compteur |
| Tentative 5+ (mauvais MDP) | Continue, log "would lock" | Verrouille 15 min |
| locked_until présent | Ignoré | Bloque connexion |
| Message après échec | "DEV MODE: Verrouillage désactivé" | "X tentatives restantes" |
| Tests illimités | ✅ OUI | ❌ NON (max 5) |

---

## 🧪 Tests

### Test 1: Échecs multiples (DEV MODE)

```bash
# Tentative 1
curl -X POST '/custom/mv3pro_portail/mobile_app/api/auth.php?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@example.com","password":"wrong"}'
→ {"success":false,"message":"Mot de passe incorrect","hint":"Tentative 1/5. DEV MODE: Verrouillage désactivé."}

# Tentative 6+ (normalement bloqué)
curl -X POST '/custom/mv3pro_portail/mobile_app/api/auth.php?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@example.com","password":"wrong"}'
→ {"success":false,"message":"Mot de passe incorrect","hint":"Tentative 6/5. DEV MODE: Verrouillage désactivé."}

# Pas de blocage !
```

### Test 2: Succès après échecs

```bash
# Après 10 échecs en DEV MODE
curl -X POST '/custom/mv3pro_portail/mobile_app/api/auth.php?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@example.com","password":"correct"}'
→ {"success":true,"token":"abc123..."}

# login_attempts réinitialisé à 0
# locked_until = NULL
```

---

## 📝 Logs

### DEV MODE actif

**Exemple logs error_log:**

```
[MV3 AUTH] Login attempt - email_provided=yes pw_length=8
[MV3 AUTH] USER_FOUND email=test@example.com rowid=1 is_active=1
[MV3 AUTH] DEV_MODE: Ignoring locked_until for email=test@example.com
[MV3 AUTH] PASSWORD_FAIL email=test@example.com rowid=1
[MV3 AUTH] DEV_MODE: Would lock account but disabled - email=test@example.com attempts=5
```

**Pas de lock, connexion continue possible**

---

## ⚠️ IMPORTANT: Production

**AVANT de déployer en PRODUCTION:**

```php
// Ligne 11 - CHANGER À FALSE
define('MV3_AUTH_DISABLE_LOCK', false);
```

**Sinon:**
- Les comptes ne seront JAMAIS verrouillés
- Vulnérable aux attaques brute-force
- Risque sécurité critique

---

## 🔐 Alternative: Configuration Dolibarr

**Pour plus tard (optionnel):**

```php
// Lire depuis config Dolibarr
$disable_lock = !empty($conf->global->MV3_DISABLE_LOGIN_LOCK);

// Ajouter dans Admin > Modules > MV3 PRO Portail:
// Checkbox: "Désactiver verrouillage anti-brute-force (DEV ONLY)"
```

**Avantage:**
- Pas besoin de modifier le code
- Configurable depuis l'interface Dolibarr
- Traçable dans les logs

---

## 📁 Fichiers modifiés

1. `/new_dolibarr/mv3pro_portail/mobile_app/api/auth.php`
   - Ligne 11: Ajout flag `MV3_AUTH_DISABLE_LOCK`
   - Ligne 143: Condition sur vérification locked_until
   - Ligne 171: Condition sur écriture locked_until
   - Ligne 186: Condition sur réponse 403 lock
   - Ligne 196: Message personnalisé DEV MODE

---

Date: 2026-01-09
Version: 1.0
Status: ✅ Implémenté
Mode actuel: **DEV MODE ACTIF**
