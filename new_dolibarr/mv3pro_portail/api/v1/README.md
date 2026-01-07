# API v1 - MV3 PRO Portail

## Vue d'ensemble

API REST unifiée et centralisée pour le module MV3 PRO Portail.

**Base URL:** `/custom/mv3pro_portail/api/v1/`

## Authentification

L'API supporte **3 modes d'authentification** (coexistence):

### Mode A: Session Dolibarr (Admin/Chef)
- Utilisateur connecté via l'interface Dolibarr standard
- Vérification automatique de `$user->id`
- Aucun header spécifique requis

### Mode B: Token Mobile (Ouvriers)
- Header: `Authorization: Bearer <token>`
- Token obtenu via `/custom/mv3pro_portail/mobile_app/login_mobile.php`
- Vérifie contre `llx_mv3_mobile_sessions`
- Lien vers `dolibarr_user_id` pour accès données

### Mode C: Token API Ancien (Compatibilité)
- Header: `X-Auth-Token: <base64_token>`
- Format: `base64({user_id, api_key, expires_at})`
- Vérifie contre `llx_user.api_key`

## Endpoints

### Authentification

#### `GET /me.php`
Informations utilisateur connecté

**Réponse:**
```json
{
  "success": true,
  "user": {
    "id": 123,
    "login": "jdupont",
    "name": "Jean Dupont",
    "email": "j.dupont@example.com",
    "role": "employee",
    "auth_mode": "mobile_token",
    "rights": ["read", "write"],
    "mobile_user_id": 45
  }
}
```

### Planning

#### `GET /planning.php?from=YYYY-MM-DD&to=YYYY-MM-DD`
Liste des événements planning

**Paramètres:**
- `from` (optionnel): Date début (défaut: aujourd'hui)
- `to` (optionnel): Date fin (défaut: aujourd'hui)

**Réponse:**
```json
{
  "success": true,
  "events": [
    {
      "id": 456,
      "label": "Pose carrelage",
      "client": "SARL Martin",
      "projet": "PRO-2025-001 - Rénovation SDB",
      "location": "12 rue de la Paix",
      "date_start": "2025-01-07 08:00:00",
      "date_end": "2025-01-07 17:00:00",
      "fullday": false
    }
  ]
}
```

### Rapports

#### `GET /rapports.php?limit=20&page=1`
Liste des rapports journaliers

**Paramètres:**
- `limit` (optionnel): Nombre de résultats (défaut: 20, max: 100)
- `page` (optionnel): Page (défaut: 1)

**Réponse:**
```json
{
  "success": true,
  "total": 245,
  "page": 1,
  "limit": 20,
  "rapports": [
    {
      "id": 789,
      "ref": "RAP000123",
      "date": "2025-01-06",
      "projet": "PRO-2025-001",
      "client": "SARL Martin",
      "surface": 12.5,
      "heures": 7.5,
      "has_photos": true,
      "url": "/custom/mv3pro_portail/mobile_app/rapports/view.php?id=789"
    }
  ]
}
```

#### `POST /rapports_create.php`
Créer un nouveau rapport

**Body (multipart/form-data):**
```json
{
  "projet_id": 123,
  "date": "2025-01-07",
  "heure_debut": "08:00",
  "heure_fin": "16:00",
  "zones": ["Salle de bain", "Cuisine"],
  "surface_total": 20,
  "format": "30x60",
  "type_carrelage": "Grès cérame",
  "notes": "Travaux conformes",
  "gps_latitude": 48.8566,
  "gps_longitude": 2.3522,
  "gps_precision": 15,
  "meteo_temperature": 18,
  "meteo_condition": "Ensoleillé",
  "frais": {
    "type": "repas_midi",
    "montant": 15.00,
    "mode_paiement": "avance_ouvrier"
  }
}
```

**Réponse:**
```json
{
  "success": true,
  "rapport": {
    "id": 790,
    "ref": "RAP000124",
    "url": "/custom/mv3pro_portail/mobile_app/rapports/view.php?id=790"
  },
  "frais": {
    "id": 56,
    "ref": "FRA000056"
  }
}
```

## Codes de retour HTTP

- `200 OK`: Succès
- `400 Bad Request`: Paramètres invalides
- `401 Unauthorized`: Non authentifié
- `403 Forbidden`: Accès refusé
- `404 Not Found`: Ressource introuvable
- `500 Internal Server Error`: Erreur serveur

## Format des erreurs

```json
{
  "success": false,
  "error": "Message d'erreur",
  "code": "ERROR_CODE"
}
```

## CORS

Les headers CORS sont configurés automatiquement via `cors_config.php`.

En production, modifier `$allowed_origins` dans `/api/cors_config.php`.

## Compatibilité

Cette API v1 **coexiste** avec les anciens endpoints:
- `/api/auth_*.php` (toujours fonctionnel)
- `/mobile_app/api/*.php` (toujours fonctionnel)
- `/sens_pose/api_*.php` (toujours fonctionnel)

Aucune URL existante n'est cassée. Migration progressive recommandée.

## Sécurité

- Toutes les entrées sont validées et échappées
- Support de l'entity multi-entreprise Dolibarr
- Limitation de débit (rate limiting) recommandée en production
- HTTPS obligatoire en production
