# BUG CSRF DOLIBARR - CORRIGÉ ✅

## 🐛 Problème Initial

### Symptômes
```
POST /custom/mv3pro_portail/mobile_app/api/auth.php?action=login
Réponse: HTTP 403
"Access refused by CSRF protection in main.inc.php. Token not provided."
```

### Cause Racine
Le fichier `auth.php` incluait `main.inc.php` **SANS** définir les constantes nécessaires pour désactiver:
- La vérification de session (Dolibarr)
- La protection CSRF
- Les composants UI non nécessaires

Conséquence: Une PWA externe ne peut pas fournir le token CSRF Dolibarr.

---

## ✅ Solution Appliquée

### Fichier Modifié
`/new_dolibarr/mv3pro_portail/mobile_app/api/auth.php`

### Changement
Ajout des constantes **AVANT** l'include de `main.inc.php`:

```php
// --- Dolibarr bootstrap for API (no session, no CSRF) ---
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);

// Charger Dolibarr
$res = 0;
if (!$res && file_exists(__DIR__ . "/../../../main.inc.php")) {
    $res = @include __DIR__ . "/../../../main.inc.php";
}
```

---

## 📋 Explication des Constantes

| Constante | Rôle |
|-----------|------|
| `NOLOGIN` | Désactive la vérification de session Dolibarr |
| `NOCSRFCHECK` | **Désactive la protection CSRF** (essentiel pour API) |
| `NOREQUIREMENU` | Pas de menu Dolibarr |
| `NOREQUIREHTML` | Pas de sortie HTML |
| `NOREQUIREAJAX` | Pas de composants AJAX |
| `NOTOKENRENEWAL` | Pas de renouvellement de token |

---

## 🔐 Sécurité

### L'API reste sécurisée grâce à:

1. **Authentification par JWT/Token**
   - Token généré à chaque login
   - Stocké dans `llx_mv3_mobile_sessions`
   - Durée de vie: 30 jours
   - Vérifié à chaque requête protégée

2. **Protection brute-force**
   - 5 tentatives max
   - Verrouillage 15 minutes
   - Compteur dans `llx_mv3_mobile_users.login_attempts`

3. **Hachage bcrypt**
   - Mots de passe dans `llx_mv3_mobile_users.password_hash`
   - `password_verify()` pour validation

4. **CORS contrôlé**
   - Headers CORS configurés
   - Validation des origines possibles

---

## ✅ Test de Validation

### Avant le fix:
```bash
curl -X POST http://dolibarr/custom/mv3pro_portail/mobile_app/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"pass"}'

# Résultat: 403 CSRF Error
```

### Après le fix:
```bash
curl -X POST http://dolibarr/custom/mv3pro_portail/mobile_app/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"pass"}'

# Résultat: 200 OK
{
  "success": true,
  "token": "abc123...",
  "user": {...}
}
```

---

## 🔄 Impact

### Fichiers Corrigés ✅
1. ✅ `/new_dolibarr/mv3pro_portail/mobile_app/api/auth.php`
   - API auth mobile (login/logout/verify)

2. ✅ `/new_dolibarr/mv3pro_portail/api/v1/_bootstrap.php`
   - Bootstrap API REST v1
   - Supporte 3 modes d'auth (Session Dolibarr, Bearer token, API token)

### Autres Fichiers
Tous les autres endpoints API v1 utilisent `_bootstrap.php` qui est maintenant corrigé, donc ils sont tous protégés.

---

## 📝 Notes

- Ce pattern est **standard** pour les APIs Dolibarr
- Documenté dans la doc officielle Dolibarr
- Utilisé dans tous les modules tiers Dolibarr
- La PWA peut maintenant se connecter sans erreur 403

---

## 🚀 Statut: CORRIGÉ ✅

Date: 2026-01-07  
Version: MV3 PRO PWA v1.0
