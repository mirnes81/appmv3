# 🔧 MODE DEBUG - Page Rapports PWA

**Date** : 2026-01-10
**Version** : 1.0.0
**Status** : ✅ DÉPLOYÉ

---

## 🎯 Objectif

Le mode debug permet de comprendre pourquoi certains rapports sont visibles dans la liste PHP classique (`/rapports/list.php`) mais pas dans la PWA (`/#/rapports`).

Il affiche des informations détaillées sur :
- L'authentification et l'utilisateur connecté
- La différence entre ancien et nouveau système de filtrage
- Les statistiques de rapports en base de données
- Les rapports affichés dans la PWA vs ceux en BD
- Les appels API et leurs réponses

---

## 🚀 Comment activer le mode debug

### Étape 1 : Accéder à la page Rapports

```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/rapports
```

### Étape 2 : Cliquer sur le bouton "🔧 Mode Debug"

Le bouton se trouve en haut de la page, juste en dessous des boutons "Rapport simple" et "Rapport PRO".

- **Gris** : Mode debug désactivé
- **Rouge** : Mode debug activé

### Étape 3 : Cliquer sur "🔄 Rafraîchir"

Une fois le mode debug activé, cliquez sur le bouton "Rafraîchir" dans le panneau pour charger les informations de diagnostic depuis l'API `/rapports_debug.php`.

---

## 📊 Informations affichées

### 1. 👤 Informations Utilisateur

```
• Nom: Jean Dupont
• Email: jdupont@example.com
• Dolibarr User ID: 42 ✅
• Mobile User ID (OLD): 1
• Mode: dolibarr
• Admin: ✅ OUI / ❌ NON
• Compte non lié: ✅ NON / ⚠️ OUI
```

**Points clés** :
- **Dolibarr User ID** : Le vrai ID utilisateur dans Dolibarr (doit être > 0)
- **Mobile User ID (OLD)** : L'ancien système bugué (ne doit plus être utilisé)
- **Admin** : Si OUI, l'utilisateur voit tous les rapports
- **Compte non lié** : Si OUI, le compte mobile n'est pas lié à un utilisateur Dolibarr

---

### 2. 🔄 Comparaison Systèmes

```
❌ ANCIEN SYSTÈME (bugué)
auth['user_id'] = 1 → 0 rapport(s)

✅ NOUVEAU SYSTÈME (corrigé)
dolibarr_user_id = 42 → 8 rapport(s)
```

**Explication** :
- **Ancien système** : Utilisait `auth['user_id']` (mobile_user_id) pour filtrer
- **Nouveau système** : Utilise `dolibarr_user_id` (vrai ID Dolibarr)
- **Résultat** : On voit clairement que le nouveau système trouve les rapports, pas l'ancien

---

### 3. 📊 Statistiques Rapports

```
• Total dans l'entité: 15
• Visibles avec NOUVEAU filtre: 8
• Visibles avec ANCIEN filtre: 0
• Filtre appliqué: fk_user = 42 (Dolibarr ID)
```

**Points clés** :
- **Total dans l'entité** : Nombre total de rapports dans la base de données
- **Visibles avec NOUVEAU filtre** : Rapports visibles avec le dolibarr_user_id
- **Visibles avec ANCIEN filtre** : Rapports visibles avec l'ancien user_id (devrait être 0)
- **Filtre appliqué** : Le filtre SQL utilisé par l'API

---

### 4. 💡 Recommandation

```
✅ 8 rapport(s) visible(s) pour cet utilisateur.
```

ou

```
⚠️ PROBLÈME : Il y a 15 rapport(s) dans l'entité, mais 0 visible avec le filtre fk_user=42.
Les rapports ne sont pas créés avec fk_user=42. Vérifiez que les rapports ont le bon fk_user.
```

**Actions possibles** :
- Si "✅" : Tout fonctionne correctement
- Si "⚠️" : Le compte n'est pas lié ou les rapports ont un mauvais fk_user

---

### 5. 👥 Rapports par Utilisateur

```
• User ID 1: 5 rapport(s)
• User ID 42: 8 rapport(s)
• User ID 50: 2 rapport(s)
```

**Utilité** :
- Voir la répartition des rapports par utilisateur
- Identifier quel utilisateur possède combien de rapports

---

### 6. 📋 5 Derniers Rapports (BD)

```
ID: 123 | Ref: RAPPORT-123
Date: 2026-01-10
User ID: 42 | Login: jdupont
User: Jean Dupont
Projet: Projet A
```

**Utilité** :
- Voir les rapports réellement en base de données
- Vérifier le `fk_user` de chaque rapport
- Comparer avec ce qui est affiché dans la PWA

---

### 7. 🌐 Dernier Appel API

```
• Endpoint: /rapports.php
• Timestamp: 10/01/2026 14:30:15
• Params:
  {
    "limit": 20,
    "page": 1,
    "statut": "all",
    "user_id": undefined
  }
• Réponse:
  {
    "status": "success",
    "items_count": 8,
    "total": 8,
    "total_pages": 1
  }
```

**Utilité** :
- Voir exactement les paramètres envoyés à l'API
- Vérifier la réponse de l'API
- Détecter les erreurs ou incohérences

---

### 8. 📱 Rapports Affichés dans la PWA

```
Total affiché: 8 / 8

ID: 123 | Ref: RAPPORT-123
Date: 2026-01-10
Client: Client A
Projet: PROJ001
Statut: valide
Photos: 5
```

**Utilité** :
- Comparer les rapports affichés avec ceux en BD
- Vérifier si tous les rapports sont bien affichés
- Identifier les rapports manquants

---

## 🔍 Cas d'usage

### Cas 1 : L'utilisateur ne voit aucun rapport

**Symptôme** :
```
⚠️ Aucun rapport affiché
```

**Debug à vérifier** :
1. **Dolibarr User ID** : Doit être > 0
2. **Compte non lié** : Doit être "NON"
3. **Visibles avec NOUVEAU filtre** : Doit être > 0
4. **5 Derniers Rapports (BD)** : Vérifier que le `fk_user` correspond au Dolibarr User ID

**Solutions** :
- Si `Dolibarr User ID = 0` : Le compte n'est pas lié → Lier dans `/mobile_app/admin/manage_users.php`
- Si `fk_user` différent : Les rapports ont été créés avec un autre user_id → Corriger les rapports ou créer de nouveaux

---

### Cas 2 : L'utilisateur voit moins de rapports que prévu

**Symptôme** :
```
Total affiché: 3 / 8
```

**Debug à vérifier** :
1. **Dernier Appel API** : Vérifier les filtres appliqués (statut, dates, etc.)
2. **5 Derniers Rapports (BD)** : Comparer avec ceux affichés
3. **Réponse API** : Vérifier `items_count` vs `total`

**Solutions** :
- Désactiver tous les filtres (statut = "Tous", dates vides)
- Vérifier que les rapports manquants ne sont pas filtrés par statut ou date
- Actualiser la page (F5) pour recharger les données

---

### Cas 3 : Admin ne voit pas tous les rapports

**Symptôme** :
```
Admin: ✅ OUI
Total dans l'entité: 15
Visibles avec NOUVEAU filtre: 8
```

**Debug à vérifier** :
1. **Admin** : Doit être "OUI"
2. **Dernier Appel API** : Vérifier si un filtre `user_id` est appliqué
3. **Total dans l'entité** : Doit correspondre au total affiché

**Solutions** :
- Si un filtre `user_id` est appliqué : Le désactiver dans le dropdown "Employé"
- Si pas de filtre : Vérifier que l'API retourne bien tous les rapports

---

### Cas 4 : Comparaison avec la liste PHP classique

**URL PHP classique** :
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/rapports/list.php
```

**Étapes** :
1. Compter le nombre de rapports dans la liste PHP
2. Ouvrir la PWA et activer le mode debug
3. Comparer le **Total dans l'entité** avec le nombre PHP
4. Comparer les **5 Derniers Rapports (BD)** avec ceux de la liste PHP

**Si différent** :
- Vérifier que l'utilisateur est le même sur les deux pages
- Vérifier que les filtres sont identiques (statut, dates)
- Vérifier l'entité active dans Dolibarr

---

## 🛠️ API utilisée

Le mode debug utilise l'endpoint suivant :

```
GET /api/v1/rapports_debug.php
```

**Réponse** :
```json
{
  "success": true,
  "debug_info": {
    "user_info": {
      "name": "Jean Dupont",
      "email": "jdupont@example.com",
      "dolibarr_user_id": 42,
      "OLD_user_id": 1,
      "is_admin": false,
      "is_unlinked": false
    },
    "total_rapports_in_entity": 15,
    "rapports_with_NEW_filter": 8,
    "rapports_with_OLD_filter": 0,
    "filter_applied": "fk_user = 42 (Dolibarr ID)",
    "rapports_by_user": {
      "1": 5,
      "42": 8,
      "50": 2
    },
    "recent_rapports": [
      {
        "rowid": 123,
        "ref": "RAPPORT-123",
        "date_rapport": "2026-01-10",
        "fk_user": 42,
        "user_login": "jdupont",
        "user_name": "Jean Dupont",
        "projet_title": "Projet A"
      }
    ]
  },
  "comparison": {
    "old_system": "auth['user_id'] = 1 → 0 rapport(s)",
    "new_system": "dolibarr_user_id = 42 → 8 rapport(s)"
  },
  "recommendation": "✅ 8 rapport(s) visible(s) pour cet utilisateur."
}
```

---

## 📝 Fichiers modifiés

### Frontend (PWA)

**`pwa/src/pages/Rapports.tsx`** :
- Ajout du bouton "Mode Debug"
- Ajout du panneau de debug avec 8 sections
- Ajout de la fonction `loadDebugInfo()`
- Ajout de logs des appels API dans `loadRapports()`
- Ajout des états `debugMode`, `debugData`, `loadingDebug`, `lastApiCall`

**Lignes ajoutées** : ~200 lignes

**Build** :
```bash
cd pwa/
npm run build
```

**Résultat** :
```
✓ 65 modules transformed
assets/index-Bn1KP0-e.js   288.41 kB │ gzip: 80.93 kB
✓ built in 3.32s
```

---

## 🎉 Avantages du mode debug

### 1. **Diagnostic rapide**
- Identifie immédiatement le problème (compte non lié, mauvais user_id, etc.)
- Affiche des recommandations claires

### 2. **Comparaison ancien/nouveau**
- Montre la différence entre l'ancien système bugué et le nouveau
- Prouve que la correction fonctionne

### 3. **Transparence totale**
- Affiche exactement ce qui se passe en coulisse
- Montre les appels API et leurs réponses
- Permet de comparer avec la liste PHP classique

### 4. **Auto-service**
- L'utilisateur peut diagnostiquer lui-même le problème
- Pas besoin d'accès aux logs serveur ou à la base de données

### 5. **Support technique facilité**
- Le support peut demander une capture d'écran du mode debug
- Toutes les infos nécessaires sont au même endroit

---

## 🔗 URLs importantes

**PWA Rapports** :
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/rapports
```

**API Debug** :
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php
```

**Liste PHP classique** :
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/rapports/list.php
```

**Admin Gestion utilisateurs** :
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/admin/manage_users.php
```

---

## ✅ Checklist de validation

- [x] Bouton "Mode Debug" visible et fonctionnel
- [x] Panneau debug affiche les 8 sections d'informations
- [x] Appel à `/rapports_debug.php` fonctionne
- [x] Comparaison ancien/nouveau système visible
- [x] Rapports affichés dans la PWA listés avec détails
- [x] Logs des appels API enregistrés
- [x] Build PWA réussi (288 KB)
- [x] Mode debug désactivable (bouton rouge)

---

**Version** : 1.0.0
**Status** : ✅ DÉPLOYÉ
**Date** : 2026-01-10
