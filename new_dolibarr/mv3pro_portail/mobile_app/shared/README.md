# Dossier Shared - Composants Partagés

## Vue d'ensemble

Ce dossier contient tous les composants, styles et helpers partagés entre les différentes pages de l'application mobile MV3 PRO.

## Structure

```
shared/
├── README.md           ← Ce fichier
├── header.php          ← Header mobile avec notifications
├── bottom_nav.php      ← Navigation bottom avec badges
├── api_client.php      ← Helper pour appeler API v1
└── css/
    └── styles.css      ← Design system unifié
```

## Utilisation

### 1. Header

```php
<?php
$page_title = 'Mon Titre';
$show_back = true;
$back_url = '/url/precedente';
require_once __DIR__.'/../shared/header.php';
?>
```

**Variables disponibles:**
- `$page_title` (string) - Titre de la page (défaut: 'MV3 PRO')
- `$show_back` (bool) - Afficher bouton retour (défaut: false)
- `$back_url` (string) - URL du bouton retour (défaut: dashboard)

### 2. Bottom Navigation

```php
<?php require_once __DIR__.'/../shared/bottom_nav.php'; ?>
```

Navigation automatique avec:
- Détection page active
- Badge notifications
- Liens vers toutes les sections

### 3. Styles CSS

```html
<link rel="stylesheet" href="/custom/mv3pro_portail/mobile_app/shared/css/styles.css">
```

**Design System disponible:**
- Variables CSS (couleurs, spacing, shadows, etc.)
- Composants (cards, buttons, forms, badges, alerts)
- Utilities classes (spacing, flex, text-align)
- Responsive breakpoints

**Exemples:**

```html
<!-- Card -->
<div class="card">
    <div class="card-header">Titre</div>
    <div class="card-body">Contenu</div>
</div>

<!-- Button -->
<button class="btn btn-primary btn-full">Action</button>

<!-- Badge -->
<span class="badge badge-success">Validé</span>

<!-- Alert -->
<div class="alert alert-info">Information importante</div>
```

### 4. API Client

```php
<?php
require_once __DIR__.'/../shared/api_client.php';

// Construire URL API
$url = api_url('/me.php');

// Vérifier disponibilité
if (api_is_available()) {
    // API v1 disponible
}
?>
```

**Pour JavaScript:**

```php
<?php echo api_client_js_snippet(); ?>
```

Puis dans votre JS:

```javascript
// GET
const user = await apiGet('/me.php');

// POST
const result = await apiPost('/rapports_create.php', {
    projet_id: 123,
    date: '2025-01-07'
});
```

## Migration vers Shared

Les anciens fichiers ont été convertis en stubs pour compatibilité:

| Ancien | Nouveau | Statut |
|--------|---------|--------|
| `includes/bottom_nav.php` | `shared/bottom_nav.php` | ✅ Stub créé |
| `includes/session.php` | `includes/session_mobile.php` | ✅ Stub créé |
| `dashboard.php` | `dashboard_mobile.php` | ✅ Stub créé |

**Les anciennes URLs continuent de fonctionner.**

## Bonnes Pratiques

### 1. Toujours utiliser les composants shared

```php
// ✅ BON
require_once __DIR__.'/../shared/header.php';
require_once __DIR__.'/../shared/bottom_nav.php';

// ❌ ÉVITER - Ne pas dupliquer le code
```

### 2. Utiliser les variables CSS

```css
/* ✅ BON */
.ma-classe {
    color: var(--color-primary);
    padding: var(--space-2);
    border-radius: var(--radius-md);
}

/* ❌ ÉVITER - Valeurs en dur */
.ma-classe {
    color: #0891b2;
    padding: 16px;
    border-radius: 8px;
}
```

### 3. Utiliser les classes utilities

```html
<!-- ✅ BON -->
<div class="card mt-2 p-2">
    <h2 class="text-center mb-2">Titre</h2>
</div>

<!-- ❌ ÉVITER - Styles inline -->
<div style="margin-top: 16px; padding: 16px;">
    <h2 style="text-align: center; margin-bottom: 16px;">Titre</h2>
</div>
```

## Évolution Future (PWA React)

Ces composants servent de base pour la future PWA React/Vite qui:
- Réutilisera le design system (variables CSS)
- Appellera l'API v1 via `api_client.php` (version JS)
- Reprendra la même structure de navigation

## Support

Pour toute question sur l'utilisation des composants shared:
1. Consulter ce README
2. Voir exemples dans les pages existantes
3. Consulter `/config/app_config.php` pour configuration

