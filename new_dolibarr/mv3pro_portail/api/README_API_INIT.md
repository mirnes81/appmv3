# API Init Helper - Protection CSRF

## 🎯 Objectif

Le fichier `_init_api.php` fournit une initialisation commune pour tous les endpoints API, désactivant automatiquement la protection CSRF de Dolibarr.

## ✅ Fichiers Déjà Corrigés

1. ✅ `mobile_app/api/auth.php` - Auth mobile (correction directe)
2. ✅ `api/v1/_bootstrap.php` - Tous les endpoints v1 (correction directe)

## 📋 Helper Créé

**Fichier:** `/api/_init_api.php`

Ce helper peut être utilisé par tous les anciens fichiers API pour éviter le bug CSRF.

## 🔧 Comment Utiliser le Helper

### Avant (avec bug CSRF):
```php
<?php
require_once __DIR__ . '/cors_config.php';
header('Content-Type: application/json');
setCorsHeaders();

require_once '../../../main.inc.php'; // ❌ CSRF activé
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
```

### Après (protégé):
```php
<?php
require_once __DIR__ . '/cors_config.php';
header('Content-Type: application/json');
setCorsHeaders();

require_once __DIR__ . '/_init_api.php'; // ✅ CSRF désactivé + Dolibarr chargé

// Plus besoin de require main.inc.php ni User class (déjà fait)
```

## 📝 Fichiers API à Mettre à Jour

Si vous utilisez les anciens endpoints dans `/api/` (pas v1), mettez à jour ces fichiers:

### Auth APIs
- `auth_login.php` 
- `auth_logout.php`
- `auth_me.php`

### Forms APIs  
- `forms_create.php`
- `forms_get.php`
- `forms_list.php`
- `forms_pdf.php`
- `forms_send_email.php`
- `forms_upload.php`

### Subcontractor APIs
- `subcontractor_login.php`
- `subcontractor_dashboard.php`
- `subcontractor_submit_report.php`
- `subcontractor_verify_session.php`
- `subcontractor_update_activity.php`

## ⚠️ Important

### API v1 (Recommandée)
- Les endpoints dans `/api/v1/` sont **DÉJÀ PROTÉGÉS**
- Ils utilisent `_bootstrap.php` qui a été corrigé
- **Utilisez l'API v1 de préférence**

### API Legacy (Ancienne)
- Les endpoints dans `/api/` (racine) peuvent avoir le bug CSRF
- Utilisez `_init_api.php` pour les corriger
- Ou migrez vers l'API v1

## 🚀 Solution Rapide

Pour corriger un fichier API:

1. Remplacez ceci:
```php
require_once '../../../main.inc.php';
```

2. Par ceci:
```php
require_once __DIR__ . '/_init_api.php';
```

C'est tout !

## 📊 Récapitulatif

| Dossier | Status | Action |
|---------|--------|--------|
| `/api/v1/*` | ✅ Protégé | Aucune (utilise _bootstrap.php) |
| `/mobile_app/api/auth.php` | ✅ Protégé | Correction directe |
| `/api/*.php` | ⚠️ À vérifier | Utiliser _init_api.php |

## 🔐 Sécurité

Le helper `_init_api.php`:
- Désactive CSRF (NOCSRFCHECK)
- Désactive vérification session (NOLOGIN)
- Charge Dolibarr en mode API
- Reste sécurisé via authentification Bearer token
