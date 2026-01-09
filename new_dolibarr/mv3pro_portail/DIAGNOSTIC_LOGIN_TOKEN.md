# Diagnostic Login & Token - MV3 PRO PWA

## Résumé du problème

L'API backend fonctionne correctement, mais les erreurs 500/510 dans la PWA viennent probablement :
- Du token non transmis par le frontend
- Ou du token expiré/invalide

## Test 1 : Script de diagnostic automatique

### Étapes

1. **Éditez le fichier** `/custom/mv3pro_portail/TEST_LOGIN_TOKEN.php`

   Modifiez les lignes 17-18 :
   ```php
   $TEST_EMAIL = 'mirnes@mv-3pro.ch'; // Votre email
   $TEST_PASSWORD = 'votre_mot_de_passe'; // Votre password
   ```

2. **Uploadez** le fichier sur votre serveur dans `/custom/mv3pro_portail/`

3. **Exécutez** via navigateur :
   ```
   https://crm.mv-3pro.ch/custom/mv3pro_portail/TEST_LOGIN_TOKEN.php
   ```

   Ou en ligne de commande SSH :
   ```bash
   cd /chemin/vers/dolibarr/htdocs/custom/mv3pro_portail
   php TEST_LOGIN_TOKEN.php
   ```

### Résultats attendus

Le script va tester :
1. ✓ Login avec votre email/password
2. ✓ Récupération du token (64 caractères hexa)
3. ✓ Test de l'API `/me.php` avec le token
4. ✓ Test de l'API `/planning.php` avec le token
5. ✓ Affichage des commandes curl pour tests manuels

**Succès** : Vous obtiendrez un token valide à utiliser pour les tests.

**Échec** : Le script affichera exactement où ça bloque (login, token, API).

---

## Test 2 : Diagnostic manuel via la PWA

### Étapes

1. **Ouvrez la PWA** : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/

2. **Ouvrez F12** (Console Développeur)

3. **Activez le mode debug** (dans la Console) :
   ```javascript
   localStorage.setItem('mv3pro_debug', 'true')
   location.reload()
   ```

4. **Connectez-vous** avec votre email/password

5. **Dans la Console**, cherchez les messages :
   ```
   [MV3PRO DEBUG] Login attempt
   [MV3PRO DEBUG] Login response
   [MV3PRO DEBUG] Token saved to localStorage
   [MV3PRO DEBUG] API Request
   ```

6. **Vérifiez le token stocké** (dans la Console) :
   ```javascript
   localStorage.getItem('mv3pro_token')
   ```
   - Doit retourner une chaîne de 64 caractères hexa
   - Si `null` → le login a échoué

7. **Allez sur l'onglet Network** (F12 → Network → Fetch/XHR)
   - Cliquez sur une requête (ex: `me.php` ou `planning.php`)
   - **Onglet Headers** :
     - Request URL doit être : `https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/...`
     - Request Headers doit contenir :
       - `Authorization: Bearer abc123...`
       - `X-Auth-Token: abc123...`

---

## Test 3 : Test curl manuel avec token

Une fois que vous avez un token valide (via Test 1 ou Test 2) :

```bash
# Remplacez YOUR_TOKEN_HERE par votre token

# Test 1 : /me.php
curl -H "X-Auth-Token: YOUR_TOKEN_HERE" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/me.php

# Test 2 : /planning.php
curl -H "X-Auth-Token: YOUR_TOKEN_HERE" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning.php

# Test 3 : /rapports.php
curl -H "X-Auth-Token: YOUR_TOKEN_HERE" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports.php
```

**Succès** : Vous obtenez `{"success":true, "user":{...}}`

**Échec** : Vous obtenez `{"success":false, "error":"Authentification requise"}`
- Vérifiez que le token est bien présent dans la commande
- Vérifiez que le token n'est pas expiré (30 jours max)

---

## Diagnostic des erreurs courantes

### Erreur : "Authentification requise" (401)

**Causes possibles** :
1. Token non fourni dans les headers
2. Token expiré (> 30 jours)
3. Token invalide (pas dans la table `llx_mv3_mobile_sessions`)
4. Compte utilisateur désactivé

**Solution** :
- Exécutez le script `TEST_LOGIN_TOKEN.php` pour obtenir un nouveau token
- Vérifiez dans MySQL :
  ```sql
  SELECT * FROM llx_mv3_mobile_sessions
  WHERE session_token = 'VOTRE_TOKEN'
  AND expires_at > NOW();
  ```

### Erreur : "Compte mobile introuvable" (lors du login)

**Causes** :
- L'email n'existe pas dans la table `llx_mv3_mobile_users`

**Solution** :
1. Créez le compte via : `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
2. Ou vérifiez dans MySQL :
   ```sql
   SELECT * FROM llx_mv3_mobile_users WHERE email = 'votre@email.ch';
   ```

### Erreur : "Mot de passe incorrect"

**Causes** :
- Password incorrect
- Hash du password invalide dans la DB

**Solution** :
- Régénérez le password via l'interface admin
- Vérifiez dans MySQL :
   ```sql
   SELECT rowid, email, password_hash, is_active
   FROM llx_mv3_mobile_users
   WHERE email = 'votre@email.ch';
   ```

### Erreur : Token présent mais API retourne 401

**Causes** :
- Token expiré
- Session supprimée de la base
- Compte utilisateur désactivé

**Solution** :
- Reconnectez-vous pour obtenir un nouveau token
- Vérifiez l'expiration dans MySQL :
  ```sql
  SELECT s.*, u.is_active
  FROM llx_mv3_mobile_sessions s
  JOIN llx_mv3_mobile_users u ON u.rowid = s.user_id
  WHERE s.session_token = 'VOTRE_TOKEN';
  ```

---

## Cas particulier : NGINX bloque Authorization header

**Symptôme** : Le frontend envoie le token mais le backend ne le reçoit pas.

**Cause** : NGINX peut bloquer le header `Authorization`.

**Solution** : Le système utilise DEUX headers :
- `Authorization: Bearer {token}` (peut être bloqué par NGINX)
- `X-Auth-Token: {token}` (fonctionne toujours)

L'API prioritise `X-Auth-Token` pour cette raison.

**Vérification** :
```php
// Dans _bootstrap.php ligne 221
if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
    $bearer = trim($_SERVER['HTTP_X_AUTH_TOKEN']);
    // ✓ Ce header fonctionne TOUJOURS
}
```

---

## Logs serveur pour debug avancé

Si les tests échouent, consultez les logs PHP :

```bash
# Logs Apache/NGINX
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log

# Logs PHP
tail -f /var/log/php/error.log
```

Cherchez les messages :
```
[MV3 AUTH] Login attempt - email_provided=yes
[MV3 AUTH] USER_FOUND email=...
[MV3 AUTH] PASSWORD_OK email=... - LOGIN SUCCESS
[MV3 API] auth_result=SUCCESS
```

---

## Récapitulatif des fichiers clés

| Fichier | Rôle |
|---------|------|
| `/mobile_app/api/auth.php` | Login, logout, verify (génère tokens) |
| `/api/v1/_bootstrap.php` | Valide tokens sur TOUTES les API v1 |
| `/api/v1/me.php` | Test simple : retourne infos user |
| `/api/v1/planning.php` | API planning (nécessite token) |
| `TEST_LOGIN_TOKEN.php` | Script de diagnostic (à exécuter) |

---

## Contact support

Si tous les tests échouent, fournissez ces informations :

1. Résultat du script `TEST_LOGIN_TOKEN.php` (copier-coller)
2. Logs de la Console F12 (avec `mv3pro_debug = true`)
3. Screenshot de F12 → Network → Headers d'une requête API
4. Résultat de cette requête SQL :
   ```sql
   SELECT COUNT(*) as nb_users FROM llx_mv3_mobile_users WHERE is_active = 1;
   SELECT COUNT(*) as nb_sessions FROM llx_mv3_mobile_sessions WHERE expires_at > NOW();
   ```
