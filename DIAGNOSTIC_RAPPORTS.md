# Diagnostic - Aucun Rapport ne s'affiche dans la PWA

## 🔍 Problème

Les rapports existent dans Dolibarr mais ne s'affichent pas dans l'application PWA.

## 🛠️ Solution mise en place

### 1. API de diagnostic créée

**Fichier:** `/api/v1/rapports_debug.php`

Cette API analyse :
- L'utilisateur connecté (mode auth, user_id, is_unlinked)
- Le nombre total de rapports dans l'entité
- Le nombre de rapports visibles avec le filtre actuel
- La distribution des rapports par utilisateur (fk_user)
- Les 5 derniers rapports créés

### 2. Interface de diagnostic dans la PWA

**Page:** `/#/debug`
**Bouton:** "Diagnostic Rapports" (rouge)

L'interface affiche :
- ✅ Info utilisateur connecté
- ✅ Statistiques des rapports (total vs visibles)
- ✅ Filtre appliqué par l'API
- ✅ Rapports par utilisateur (avec mise en évidence)
- ✅ Liste des 5 derniers rapports avec leur fk_user
- ✅ Recommandations automatiques si problème détecté

## 📋 Comment utiliser le diagnostic

1. **Connectez-vous à la PWA** avec vos identifiants
2. **Allez sur** `/#/debug`
3. **Cliquez sur** "Diagnostic Rapports"
4. **Analysez les résultats** :

### Scénarios possibles

#### A) Compte non lié (is_unlinked = true)
**Symptôme :** user_id = NULL
**Cause :** Le compte mobile n'est pas lié à un utilisateur Dolibarr
**Solution :** Lier le compte dans `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`

#### B) Rapports créés avec un autre fk_user
**Symptôme :** total_rapports > 0 MAIS rapports_with_filter = 0
**Cause :** Les rapports ont un fk_user différent de l'utilisateur connecté
**Solution :** Modifier l'API `rapports.php` pour ne pas filtrer par user_id

#### C) Pas de rapports du tout
**Symptôme :** total_rapports = 0
**Cause :** Aucun rapport créé dans l'entité
**Solution :** Créer des rapports via Dolibarr

## 🔧 Correctif potentiel - API rapports.php

### Option 1: Afficher TOUS les rapports de l'entité (recommandé)

Modifier `/api/v1/rapports.php` lignes 48-63 pour commenter le filtre par utilisateur :

```php
// Filtrer par utilisateur (sauf si admin)
// DÉSACTIVÉ - Afficher tous les rapports de l'entité
/*
if ($filter_user_id && !empty($auth['dolibarr_user']->admin)) {
    $where[] = "r.fk_user = ".(int)$filter_user_id;
} else {
    if ($auth['user_id']) {
        $where[] = "r.fk_user = ".(int)$auth['user_id'];
    } elseif (!empty($auth['mobile_user_id'])) {
        $where[] = "1 = 0";
    } else {
        $where[] = "1 = 0";
    }
}
*/
```

### Option 2: Afficher tous si admin

```php
// Filtrer par utilisateur sauf si admin
if (!empty($auth['dolibarr_user']->admin)) {
    // Admin voit tout
} else {
    // Non-admin voit ses rapports
    if ($auth['user_id']) {
        $where[] = "r.fk_user = ".(int)$auth['user_id'];
    } else {
        $where[] = "1 = 0"; // Pas de rapports si non lié
    }
}
```

## ✅ Fichiers modifiés

1. `/api/v1/rapports_debug.php` - Nouvelle API de diagnostic
2. `/pwa/src/lib/api.ts` - Ajout méthode `rapportsDebug()`
3. `/pwa/src/pages/Debug.tsx` - Ajout section diagnostic rapports
4. `/api/v1/_bootstrap.php` - Fix fonction `log_error()` manquante

## 📸 Résultat attendu

Après le diagnostic, vous saurez EXACTEMENT pourquoi les rapports ne s'affichent pas et comment corriger le problème.

**Exemple de message :**
```
PROBLÈME DÉTECTÉ : Il y a 4 rapport(s) dans l'entité, mais 0 visible avec le filtre user_id=2.
Les rapports ne sont pas créés avec fk_user=2.
Solution: Modifier l'API pour afficher tous les rapports de l'entité (sans filtre par utilisateur)
ou créer les rapports avec le bon fk_user.
```
