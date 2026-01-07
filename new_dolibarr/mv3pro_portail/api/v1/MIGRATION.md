# Migration vers API v1

## Mapping Anciens → Nouveaux Endpoints

Ce document liste la correspondance entre les anciens endpoints et la nouvelle API v1.

### Authentification

| Ancien | Nouveau | Notes |
|--------|---------|-------|
| `/api/auth_login.php` | ✅ **Compatible** | Token X-Auth-Token supporté par v1 |
| `/api/auth_me.php` | `/api/v1/me.php` | Format de réponse amélioré |
| `/api/auth_logout.php` | ✅ **Compatible** | Pas de changement nécessaire |

**Migration:**
```javascript
// Ancien
const response = await fetch('/custom/mv3pro_portail/api/auth_me.php', {
  headers: { 'X-Auth-Token': token }
});

// Nouveau (compatible)
const response = await fetch('/custom/mv3pro_portail/api/v1/me.php', {
  headers: { 'X-Auth-Token': token }
});
```

---

### Planning

| Ancien | Nouveau | Notes |
|--------|---------|-------|
| `/mobile_app/api/today_planning.php` | `/api/v1/planning.php?from=TODAY&to=TODAY` | Support période |
| `/mobile_app/api/get_projets.php` | **À VENIR** `/api/v1/projets.php` | Étape 3 |

**Migration:**
```javascript
// Ancien
const response = await fetch('/custom/mv3pro_portail/mobile_app/api/today_planning.php');

// Nouveau
const today = new Date().toISOString().split('T')[0];
const response = await fetch(`/custom/mv3pro_portail/api/v1/planning.php?from=${today}&to=${today}`, {
  headers: { 'Authorization': 'Bearer ' + token }
});
```

---

### Rapports

| Ancien | Nouveau | Notes |
|--------|---------|-------|
| `/mobile_app/rapports/list.php` (HTML) | `/api/v1/rapports.php` | JSON au lieu de HTML |
| `/mobile_app/rapports/new.php` (form POST) | `/api/v1/rapports_create.php` | JSON au lieu de form |
| `/mobile_app/rapports/api/stats.php` | **À VENIR** `/api/v1/rapports_stats.php` | Étape 3 |
| `/mobile_app/rapports/api/copy-rapport.php` | **À VENIR** `/api/v1/rapports_copy.php` | Étape 3 |

**Migration:**
```javascript
// Ancien (form HTML)
const form = new FormData();
form.append('projet_id', 123);
form.append('date', '2025-01-07');
// ...
await fetch('/custom/mv3pro_portail/mobile_app/rapports/new.php', {
  method: 'POST',
  body: form
});

// Nouveau (JSON)
await fetch('/custom/mv3pro_portail/api/v1/rapports_create.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: JSON.stringify({
    projet_id: 123,
    date: '2025-01-07',
    heure_debut: '08:00',
    heure_fin: '16:00',
    surface_total: 20,
    zones: ['Salle de bain'],
    format: '30x60'
  })
});
```

---

### Sens de Pose

| Ancien | Nouveau | Notes |
|--------|---------|-------|
| `/sens_pose/api_get_clients_projet.php` | **À VENIR** `/api/v1/sens_pose/clients.php` | Étape 3 |
| `/sens_pose/api_get_devis_client.php` | **À VENIR** `/api/v1/sens_pose/devis.php` | Étape 3 |
| `/sens_pose/api_get_devis_details_v2.php` | **À VENIR** `/api/v1/sens_pose/devis_details.php` | Étape 3 |
| `/sens_pose/api_search_projets.php` | **À VENIR** `/api/v1/projets_search.php` | Étape 3 |

**Status:** Ces endpoints seront migrés dans l'étape 3. Les anciens restent fonctionnels.

---

### Régie

| Ancien | Nouveau | Notes |
|--------|---------|-------|
| `/mobile_app/regie/*.php` | **À VENIR** `/api/v1/regie/*.php` | Étape 3 |

---

### Notifications

| Ancien | Nouveau | Notes |
|--------|---------|-------|
| `/mobile_app/api/notifications.php` | **À VENIR** `/api/v1/notifications.php` | Étape 3 |

---

## Stratégie de Migration

### Phase 1 (Actuelle - Étape 2)
- ✅ API v1 créée avec 4 endpoints de base
- ✅ Coexistence avec anciens endpoints
- ✅ Support 3 modes d'auth
- **Aucune URL cassée**

### Phase 2 (Étape 3)
- Migrer endpoints sens_pose
- Migrer endpoints régie
- Migrer endpoints matériel
- Migrer endpoints notifications
- **Garder compatibilité via stubs**

### Phase 3 (Étape 4)
- PWA React/Vite utilise exclusivement API v1
- Anciens endpoints PHP (HTML) marqués "legacy" mais toujours fonctionnels
- Documentation complète

### Phase 4 (Futur - optionnel)
- Dépréciation formelle des anciens endpoints
- Ajouter warnings dans les logs
- Planifier suppression (6-12 mois)

---

## Compatibilité Ascendante

### Anciens tokens supportés
✅ Les 3 modes d'auth sont supportés sans changement client:
- Session Dolibarr
- Token mobile (Bearer)
- Token API ancien (X-Auth-Token)

### Stubs de compatibilité (optionnel)
Si nécessaire, créer des stubs dans les anciens endpoints:

```php
<?php
// Ancien: /mobile_app/api/today_planning.php
// Stub qui redirige vers v1

require_once '../../api/v1/_bootstrap.php';

// Rediriger vers v1
$today = date('Y-m-d');
header('Location: /custom/mv3pro_portail/api/v1/planning.php?from='.$today.'&to='.$today);
exit;
```

---

## Avantages API v1

### ✅ Centralisation
- 1 seul point d'entrée auth
- 1 seul système de validation
- 1 seule gestion d'erreurs

### ✅ Sécurité
- Validation unifiée
- Échappement systématique
- Rate limiting prêt
- Headers sécurisés

### ✅ Maintenance
- Code organisé
- Documentation claire
- Tests facilités
- Évolutions simplifiées

### ✅ Performance
- Moins de code dupliqué
- Optimisations globales
- Cache possible

### ✅ Développement
- Expérience développeur améliorée
- Format JSON uniforme
- Codes d'erreur cohérents
- TypeScript types générables

---

## Support

Pour toute question sur la migration:
1. Consulter `/api/v1/README.md`
2. Tester avec les exemples ci-dessus
3. Vérifier les logs Dolibarr

**Les anciens endpoints restent fonctionnels. Migration progressive recommandée.**
