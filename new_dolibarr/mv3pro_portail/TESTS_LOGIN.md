# TESTS LOGIN PWA - Guide de test

## Prérequis

1. Tables SQL créées:
   - `llx_mv3_mobile_users`
   - `llx_mv3_mobile_sessions`

2. Au moins un utilisateur test créé:
```sql
INSERT INTO llx_mv3_mobile_users 
(email, password_hash, firstname, lastname, is_active, entity) 
VALUES (
  'test@mv3pro.ch',
  '$2y$10$abcdefghijklmnopqrstuvwxyz...', -- password_hash('test123')
  'Jean',
  'Test',
  1,
  1
);
```

## Tests manuels

### Test 1: Login réussi

**URL:** `/custom/mv3pro_portail/pwa_dist/#/login`

**Étapes:**
1. Ouvrir la PWA
2. Entrer email: `test@mv3pro.ch`
3. Entrer password: `test123`
4. Cliquer "Se connecter"

**Résultat attendu:**
- ✅ Redirection vers dashboard
- ✅ Token stocké dans localStorage
- ✅ Nom utilisateur affiché

**Vérifier console:**
```
POST /custom/mv3pro_portail/mobile_app/api/auth.php?action=login
Status: 200 OK
Response: {"success":true,"token":"...","user":{...}}
```

### Test 2: Password incorrect

**Étapes:**
1. Email: `test@mv3pro.ch`
2. Password: `mauvais123`
3. Cliquer "Se connecter"

**Résultat attendu:**
- ❌ Message d'erreur: "Email ou mot de passe incorrect"
- ❌ Pas de redirection
- ❌ Pas de token stocké

**Vérifier console:**
```
POST /custom/mv3pro_portail/mobile_app/api/auth.php?action=login
Status: 401 Unauthorized
Response: {"success":false,"message":"Email ou mot de passe incorrect"}
```

### Test 3: Email invalide

**Étapes:**
1. Email: `invalide`
2. Password: `test123`
3. Cliquer "Se connecter"

**Résultat attendu:**
- ❌ Message: "Email invalide"
- Status: 400

### Test 4: Brute force protection

**Étapes:**
1. Tenter login avec mauvais password 5 fois

**Résultat attendu après 5 tentatives:**
- 🔒 Message: "Trop de tentatives échouées. Compte verrouillé 15 minutes"
- Status: 403
- Nouvelle tentative immédiate → Toujours bloqué
- Attendre 15 min → Login fonctionne à nouveau

### Test 5: Compte désactivé

**Étapes:**
1. Désactiver compte en BDD:
```sql
UPDATE llx_mv3_mobile_users 
SET is_active = 0 
WHERE email = 'test@mv3pro.ch';
```
2. Tenter login

**Résultat attendu:**
- ❌ Message: "Compte désactivé. Contactez votre administrateur"
- Status: 403

### Test 6: Token persistance

**Étapes:**
1. Login réussi
2. Fermer onglet
3. Rouvrir PWA

**Résultat attendu:**
- ✅ Toujours connecté (pas de demande de login)
- ✅ Token toujours valide
- ✅ Données utilisateur affichées

### Test 7: Logout

**Étapes:**
1. Connecté → Cliquer "Déconnexion"

**Résultat attendu:**
- ✅ Redirection vers /login
- ✅ Token supprimé de localStorage
- ✅ Session supprimée en BDD

**Vérifier console:**
```
POST /custom/mv3pro_portail/mobile_app/api/auth.php?action=logout
Status: 200 OK
Response: {"success":true,"message":"Déconnexion réussie"}
```

### Test 8: Token expiré

**Étapes:**
1. En BDD, expirer manuellement:
```sql
UPDATE llx_mv3_mobile_sessions 
SET expires_at = NOW() - INTERVAL 1 DAY
WHERE session_token = 'xxx';
```
2. Recharger page PWA
3. Tenter une action (ex: voir planning)

**Résultat attendu:**
- 🔄 Redirection automatique vers /login
- ❌ Message: "Session expirée"

## Tests automatiques (CURL)

### Login réussi
```bash
curl -X POST 'http://localhost/custom/mv3pro_portail/mobile_app/api/auth.php?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@mv3pro.ch","password":"test123"}'
```

### Login échoué
```bash
curl -X POST 'http://localhost/custom/mv3pro_portail/mobile_app/api/auth.php?action=login' \
  -H 'Content-Type: application/json' \
  -d '{"email":"test@mv3pro.ch","password":"mauvais"}'
```

### Verify token
```bash
TOKEN="votre_token_ici"
curl 'http://localhost/custom/mv3pro_portail/mobile_app/api/auth.php?action=verify' \
  -H "Authorization: Bearer $TOKEN"
```

### Logout
```bash
curl -X POST 'http://localhost/custom/mv3pro_portail/mobile_app/api/auth.php?action=logout' \
  -H "Authorization: Bearer $TOKEN"
```

## Vérifications BDD

### Sessions actives
```sql
SELECT s.rowid, s.session_token, s.expires_at, s.last_activity,
       u.email, u.firstname, u.lastname
FROM llx_mv3_mobile_sessions s
JOIN llx_mv3_mobile_users u ON u.rowid = s.user_id
WHERE s.expires_at > NOW();
```

### Tentatives échouées
```sql
SELECT email, login_attempts, locked_until, last_login
FROM llx_mv3_mobile_users
WHERE login_attempts > 0;
```

### Réinitialiser compte verrouillé
```sql
UPDATE llx_mv3_mobile_users
SET login_attempts = 0, locked_until = NULL
WHERE email = 'test@mv3pro.ch';
```

## Checklist finale

- [ ] Login avec credentials valides fonctionne
- [ ] Erreur claire si password incorrect
- [ ] Validation email format
- [ ] Protection brute force (5 tentatives)
- [ ] Message verrouillage après 5 échecs
- [ ] Compte désactivé bloqué
- [ ] Token stocké après login réussi
- [ ] Token persiste après fermeture
- [ ] Logout supprime token
- [ ] Redirection auto si token expiré
- [ ] Réponse JSON dans tous les cas
- [ ] Pas de crash si serveur down
- [ ] Logs console clairs
- [ ] Performance acceptable (<500ms login)

## En cas d'erreur

### Réponse vide ou HTML
- ✅ Vérifié : safeJson() gère ce cas
- Console affiche le texte brut
- Message d'erreur clair à l'utilisateur

### Serveur down
- ✅ Catch à la racine
- Message: "Erreur de connexion au serveur"
- Pas de crash

### Token invalide
- ✅ Redirection auto vers /login
- Token supprimé
- Peut se reconnecter

---

**Tous les tests passent = LOGIN PROD READY ✅**
