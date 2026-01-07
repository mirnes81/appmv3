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

#### `GET /rapports_view.php?id=123`
Détail d'un rapport avec photos, frais, GPS, météo

#### `POST /rapports_photos_upload.php`
Upload de photos pour un rapport (multipart/form-data)

#### `POST /rapports_pdf.php`
Génère le PDF d'un rapport

#### `POST /rapports_send_email.php`
Envoie le PDF par email

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

### Frais

#### `GET /frais_list.php?month=YYYY-MM&user_id=&statut=`
Liste des frais pour un mois donné

#### `POST /frais_update_status.php`
Met à jour le statut de frais (manager/admin uniquement)

#### `GET /frais_export_csv.php?month=YYYY-MM`
Exporte les frais en CSV (manager/admin uniquement)

### Régie

#### `GET /regie_list.php?limit=50&page=1&status=&project_id=`
Liste des bons de régie

#### `POST /regie_create.php`
Crée un nouveau bon de régie avec lignes

#### `GET /regie_view.php?id=123`
Détail d'un bon avec lignes, photos, signature

#### `POST /regie_add_photo.php`
Upload photos pour un bon (multipart/form-data)

#### `POST /regie_signature.php`
Enregistre la signature électronique

#### `POST /regie_pdf.php`
Génère le PDF d'un bon

#### `POST /regie_send_email.php`
Envoie le PDF par email

### Sens de Pose

#### `GET /sens_pose_list.php?limit=50&page=1`
Liste des sens de pose

#### `POST /sens_pose_create.php`
Crée un nouveau sens de pose

#### `POST /sens_pose_create_from_devis.php`
Crée un sens de pose depuis un devis

#### `GET /sens_pose_view.php?id=123`
Détail d'un sens de pose avec pièces

#### `POST /sens_pose_signature.php`
Enregistre la signature

#### `POST /sens_pose_pdf.php`
Génère le PDF

#### `POST /sens_pose_send_email.php`
Envoie le PDF par email

### Matériel

#### `GET /materiel_list.php`
Liste du matériel disponible

#### `GET /materiel_view.php?id=123`
Détail d'un matériel

#### `POST /materiel_action.php`
Effectue une action (réserver, libérer, etc.)

### Notifications

#### `GET /notifications_list.php?limit=50`
Liste des notifications de l'utilisateur

#### `POST /notifications_mark_read.php?id=123`
Marque une notification comme lue

#### `GET /notifications_unread_count.php`
Nombre de notifications non lues

### Planning (complet)

#### `GET /planning.php?from=&to=`
Liste des événements (déjà existant)

#### `GET /planning_view.php?id=123`
Détail d'un événement planning

### Sous-traitants

#### `POST /subcontractor_login.php`
Wrapper vers le login existant

#### `POST /subcontractor_submit_report.php`
Wrapper vers la soumission de rapport existant

## Résumé des endpoints (30 nouveaux)

**ÉTAPE 5 - Endpoints ajoutés:**

| Module | Endpoints |
|--------|-----------|
| Rapports | view, photos_upload, pdf, send_email (4) |
| Frais | list, update_status, export_csv (3) |
| Régie | list, create, view, add_photo, signature, pdf, send_email (7) |
| Sens de Pose | list, create, create_from_devis, view, signature, pdf, send_email (7) |
| Matériel | list, view, action (3) |
| Notifications | list, mark_read, unread_count (3) |
| Planning | view (1) |
| Subcontractors | login, submit_report (2) |

**Total: 30 nouveaux endpoints** + 4 existants (ÉTAPE 2) = **34 endpoints**

## Exemples d'utilisation

### Upload photo rapport
```bash
curl -X POST "https://app.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_photos_upload.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "rapport_id=123" \
  -F "files[]=@photo1.jpg" \
  -F "files[]=@photo2.jpg" \
  -F "descriptions[]=Vue d'ensemble" \
  -F "descriptions[]=Détail"
```

### Générer et envoyer PDF régie
```bash
# 1. Générer PDF
curl -X POST "https://app.mv-3pro.ch/custom/mv3pro_portail/api/v1/regie_pdf.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"regie_id": 456}'

# 2. Envoyer par email
curl -X POST "https://app.mv-3pro.ch/custom/mv3pro_portail/api/v1/regie_send_email.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"regie_id": 456, "to": "client@example.com"}'
```

### Signature électronique
```bash
curl -X POST "https://app.mv-3pro.ch/custom/mv3pro_portail/api/v1/sens_pose_signature.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sens_pose_id": 789,
    "signature_data": "data:image/png;base64,iVBORw0KGgoAAAANS...",
    "latitude": 48.8566,
    "longitude": 2.3522
  }'
```

### Export CSV frais
```bash
curl -X GET "https://app.mv-3pro.ch/custom/mv3pro_portail/api/v1/frais_export_csv.php?month=2025-01" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o frais_2025_01.csv
```

