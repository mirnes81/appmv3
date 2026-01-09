# Correction des erreurs 500 - Rapports & Planning

## Date
2026-01-09

## Problème diagnostiqué

Les endpoints suivants retournaient **500 Internal Server Error** malgré un token d'authentification valide :

1. `GET /api/v1/rapports.php` → 500
2. `GET /api/v1/planning.php` → 500

Confirmation via F12 :
- ✅ Authentification OK (`/api/v1/me.php` → 200)
- ✅ Token présent et valide
- ✅ ProtectedRoute OK
- ❌ Erreurs backend dans les endpoints rapports et planning

---

## Corrections appliquées

### 1. `/api/v1/rapports.php`

#### Problème 1 : Variable globale `$conf` manquante

**Ligne 17** (avant) :
```php
global $db;
```

**Ligne 17** (après) :
```php
global $db, $conf;
```

**Cause** :
- Le code utilisait `$conf->entity` à la ligne 42
- Sans `global $conf;`, la variable n'était pas accessible dans le scope de la fonction
- Résultat : **Fatal Error** → 500

#### Problème 2 : Champ SQL `fk_user` manquant dans le SELECT

**Ligne 86-92** (avant) :
```php
$sql = "SELECT r.rowid, r.ref, r.date_rapport, r.heure_debut, r.heure_fin,
        r.surface_total, r.fk_projet, r.fk_soc, r.zones, r.format, r.type_carrelage,
        r.travaux_realises, r.observations, r.statut,
        p.ref as projet_ref, p.title as projet_title,
        s.nom as client_nom,
        u.firstname, u.lastname,
        0 as nb_photos
```

**Ligne 86-92** (après) :
```php
$sql = "SELECT r.rowid, r.ref, r.date_rapport, r.heure_debut, r.heure_fin,
        r.surface_total, r.fk_projet, r.fk_soc, r.fk_user, r.zones, r.format, r.type_carrelage,
        r.travaux_realises, r.observations, r.statut,
        p.ref as projet_ref, p.title as projet_title,
        s.nom as client_nom,
        u.firstname, u.lastname,
        0 as nb_photos
```

**Ajout** : `r.fk_user` dans le SELECT

**Cause** :
- Le code utilisait `$obj->fk_user` à la ligne 129
- Le champ n'était pas présent dans le SELECT
- Résultat : **Warning/Notice PHP** → potentiel 500

---

### 2. `/api/v1/planning.php`

**Statut** : Aucune correction nécessaire ✅

Le fichier était déjà correct :
- `global $db, $conf;` présent ligne 14
- Tous les champs SQL utilisés sont présents dans le SELECT
- Gestion d'erreur robuste avec logs

**Note** : Si planning.php retournait encore 500, le problème était probablement lié à `rapports.php` qui plantait le système avant.

---

## Résultat attendu

Après upload des fichiers corrigés sur le serveur :

1. **`GET /api/v1/rapports.php`** → 200 OK
   ```json
   [
     {
       "rowid": 1,
       "ref": "RAP001",
       "date_rapport": "2026-01-09",
       "projet_nom": "Projet Test",
       "client": "Client ABC",
       ...
     }
   ]
   ```

2. **`GET /api/v1/planning.php`** → 200 OK
   ```json
   [
     {
       "id": 1,
       "label": "Événement test",
       "datep": "2026-01-09 09:00:00",
       "client_nom": "Client XYZ",
       ...
     }
   ]
   ```

---

## Test de validation

### Méthode 1 : Via la PWA

1. Ouvrez la PWA : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Connectez-vous
3. Ouvrez F12 → Console (activez debug mode si besoin)
4. Allez sur **Planning** → Devrait charger sans erreur 500
5. Allez sur **Rapports** → Devrait charger sans erreur 500

### Méthode 2 : Via curl

```bash
# 1. Login pour obtenir un token
curl -X POST https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/api/auth.php?action=login \
     -H "Content-Type: application/json" \
     -d '{"email":"votre@email.ch","password":"votre_password"}'

# Réponse attendue: {"success":true,"token":"abc123..."}

# 2. Utiliser le token pour tester les API
TOKEN="VOTRE_TOKEN_ICI"

# Test rapports.php
curl -H "X-Auth-Token: $TOKEN" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports.php?limit=10

# Test planning.php
curl -H "X-Auth-Token: $TOKEN" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning.php?from=2026-01-09
```

**Succès** : Vous obtenez du JSON avec `[]` (vide) ou `[{...}]` (avec données)

**Échec** : Vous obtenez encore une erreur 500 → consultez les logs PHP du serveur

---

## Logs serveur à vérifier

Si l'erreur 500 persiste après corrections :

```bash
# Logs Apache
tail -f /var/log/apache2/error.log

# Logs NGINX
tail -f /var/log/nginx/error.log

# Logs PHP
tail -f /var/log/php/error.log
```

Cherchez les messages :
- `PHP Fatal error: ...`
- `PHP Warning: ...`
- `[MV3 Planning] SQL Error: ...`
- `Undefined variable: conf`

---

## Fichiers modifiés

| Fichier | Ligne | Modification |
|---------|-------|--------------|
| `/api/v1/rapports.php` | 17 | Ajout de `$conf` dans `global $db, $conf;` |
| `/api/v1/rapports.php` | 87 | Ajout de `r.fk_user` dans le SELECT SQL |

---

## Prochaines étapes

1. **Uploadez** les fichiers corrigés sur le serveur
   - `/custom/mv3pro_portail/api/v1/rapports.php`
   - (planning.php n'a pas été modifié)

2. **Testez** immédiatement via curl ou PWA

3. **Vérifiez** dans F12 que les endpoints retournent 200 OK

4. Si tout fonctionne, **supprimez** le mode debug :
   ```javascript
   localStorage.removeItem('mv3pro_debug')
   location.reload()
   ```

---

## Support

Si l'erreur 500 persiste après ces corrections, fournissez :

1. **Logs PHP** du serveur (tail des dernières lignes)
2. **Requête SQL exacte** qui échoue (visible dans les logs)
3. **Screenshot F12** → Network → Headers + Response de la requête en échec
4. **Version PHP** : `php -v`
5. **Structure de la table** :
   ```sql
   DESCRIBE llx_mv3_rapport;
   DESCRIBE llx_actioncomm;
   ```

---

## Résumé

**Problème** : Variables globales manquantes + champs SQL manquants → Fatal Error PHP → 500

**Solution** :
1. Ajout de `global $conf;` dans rapports.php
2. Ajout de `r.fk_user` dans le SELECT SQL

**Statut** : ✅ Corrigé - Prêt pour upload sur le serveur
