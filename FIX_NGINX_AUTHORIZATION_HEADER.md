# FIX: NGINX ne transmet pas le header Authorization à PHP

## 🐛 Problème identifié

**Symptôme:**
- Le frontend envoie `Authorization: Bearer <token>` ET `X-Auth-Token: <token>`
- Le backend PHP répond 401 Unauthorized
- Mode debug montre que les headers sont "présents" côté frontend
- Mais PHP ne reçoit pas `$_SERVER['HTTP_AUTHORIZATION']`

**Cause racine:**
NGINX, par défaut, **ne transmet PAS** le header `Authorization` aux scripts PHP via FastCGI.

## ✅ Solution appliquée

### 1. Modification de `_bootstrap.php`

**Fichier:** `/new_dolibarr/mv3pro_portail/api/v1/_bootstrap.php`

**Changement:** Inversion de la priorité de lecture du token

**AVANT (ne fonctionnait pas avec NGINX):**
```php
// Lisait d'abord Authorization (bloqué par NGINX)
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    // extraire Bearer token
}
```

**APRÈS (fonctionne avec NGINX):**
```php
// PRIORITY 1: X-Auth-Token (fonctionne toujours)
if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
    $bearer = trim($_SERVER['HTTP_X_AUTH_TOKEN']);
    // logs debug
}
// PRIORITY 2: Authorization header (fallback)
elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    // extraire Bearer token
    // logs debug
}
```

### 2. Logs debug améliorés

Le code affiche maintenant clairement dans error_log:
```
[MV3 API] token_source=X-Auth-Token  (ou =Authorization)
[MV3 API] x_auth_token_present=1
[MV3 API] token_extracted=1
[MV3 API] token_mask=abc123....xyz9
```

Si le token n'est pas trouvé:
```
[MV3 API] token_not_found=1
[MV3 API] x_auth_token=PRESENT (ou =NONE)
[MV3 API] authorization=PRESENT (ou =NONE)
```

## 🎯 Résultat attendu

Avec ce fix:
1. ✅ Le frontend envoie TOUJOURS `X-Auth-Token` ET `Authorization`
2. ✅ PHP lit d'abord `X-Auth-Token` (qui passe toujours NGINX)
3. ✅ Si `X-Auth-Token` n'existe pas, fallback sur `Authorization`
4. ✅ `/api/v1/me.php` retourne 200 avec les données utilisateur
5. ✅ L'étape 3 du mode debug devient verte
6. ✅ Redirection automatique vers le dashboard

## 🔧 Pourquoi NGINX bloque Authorization?

NGINX ne transmet pas `Authorization` par défaut pour des raisons de sécurité (éviter de transmettre les credentials HTTP Basic Auth aux scripts).

**Solutions possibles côté NGINX** (non nécessaires avec notre fix):
1. Ajouter dans la config NGINX:
   ```nginx
   fastcgi_param HTTP_AUTHORIZATION $http_authorization;
   ```
2. Ou utiliser un header custom (ce qu'on fait avec `X-Auth-Token`)

**Notre choix:** Utiliser `X-Auth-Token` comme source principale = compatible avec tous les serveurs web (NGINX, Apache, etc.)

## 📋 Checklist de vérification

### Mode debug activé
- [ ] Aller sur `/custom/mv3pro_portail/pwa_dist/#/login`
- [ ] Activer "Mode Debug"
- [ ] Se connecter avec identifiants valides
- [ ] Observer l'étape 3: "Test API /me.php"

### Étape 3 devrait afficher:
```json
✅ ÉTAPE 3: Test API /me.php
{
  "status": 200,
  "user_id": null,  // ou un nombre si lié
  "user_email": "info@mv-3pro.ch",
  "user_name": "Prénom Nom",
  "is_unlinked": true,  // ou false si lié
  "dolibarr_user_id": 0,  // ou > 0 si lié
  "rights": {
    "read": true,
    "write": false,  // false si is_unlinked=true
    "worker": false
  }
}
```

### Logs backend (error_log)
```
[MV3 API] ========== AUTH START ==========
[MV3 API] path=/custom/mv3pro_portail/api/v1/me.php
[MV3 API] method=GET
[MV3 API] auth_header_present=0
[MV3 API] x_auth_token_present=1
[MV3 API] token_source=X-Auth-Token
[MV3 API] token_extracted=1
[MV3 API] token_mask=abc123....xyz9
[MV3 API] token_length=64
[MV3 API] session_found=1
[MV3 API] user_email=info@mv-3pro.ch
[MV3 API] dolibarr_user_id=0
[MV3 API] is_unlinked=1
[MV3 API] auth_result=SUCCESS
[MV3 API] auth_mode=mobile_token
[MV3 API] ========== AUTH END ==========
[MV3 API] /me.php auth successful, building response
```

## ✅ Validation finale

**Le fix fonctionne si:**
1. `/api/v1/me.php` retourne status 200
2. L'étape 3 du debug est verte ✅
3. L'étape 4 (Redirection Dashboard) s'exécute
4. Le dashboard s'affiche sans boucle de redirection

**En cas d'échec persistant:**
- Vérifier les logs backend pour voir quel header arrive réellement
- Vérifier que la session n'est pas expirée: `SELECT * FROM llx_mv3_mobile_sessions WHERE expires_at > NOW()`
- Vérifier que l'utilisateur est actif: `SELECT * FROM llx_mv3_mobile_users WHERE email = 'info@mv-3pro.ch'`

---

## 📝 Note technique

Ce fix est **rétrocompatible**:
- Les anciennes requêtes avec `Authorization` fonctionnent toujours (fallback)
- Les nouvelles requêtes avec `X-Auth-Token` fonctionnent mieux (prioritaire)
- Le code gère les deux cas gracieusement

**Recommandation:** Utiliser toujours `X-Auth-Token` pour les nouvelles implémentations.

---

Date: 2026-01-09
Status: ✅ FIX APPLIQUÉ
Fichier modifié: `/new_dolibarr/mv3pro_portail/api/v1/_bootstrap.php`
Ligne: ~214-269
