# 📡 API RAPPORTS - TESTS ET EXEMPLES

Guide de test des 9 endpoints API Rapports avec exemples curl.

---

## 🔐 AUTHENTIFICATION

Les APIs utilisent la session Dolibarr. Vous devez d'abord vous connecter.

### Login

```bash
curl -X POST http://localhost/custom/mv3pro_portail/api/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin"
  }' \
  -c cookies.txt
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "login": "admin",
    "lastname": "Admin",
    "firstname": "Super"
  }
}
```

Enregistre le cookie dans `cookies.txt` pour les requêtes suivantes.

---

## 📋 1. LISTE PROJETS

Récupérer les projets pour associer à un rapport.

### Tous les projets

```bash
curl http://localhost/custom/mv3pro_portail/api/v1/reports_projects.php \
  -b cookies.txt
```

### Recherche

```bash
curl "http://localhost/custom/mv3pro_portail/api/v1/reports_projects.php?search=enseigne" \
  -b cookies.txt
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "ref": "PROJ-001",
      "title": "Installation Enseigne Carrefour",
      "thirdparty_name": "Carrefour SA"
    },
    {
      "id": 2,
      "ref": "PROJ-002",
      "title": "Maintenance Enseigne Leclerc",
      "thirdparty_name": "Leclerc"
    }
  ]
}
```

---

## 📝 2. CRÉER RAPPORT

Créer un nouveau rapport de chantier.

### Rapport minimal

```bash
curl -X POST http://localhost/custom/mv3pro_portail/api/v1/reports_create.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "date_report": "2026-01-10"
  }'
```

### Rapport complet avec lignes

```bash
curl -X POST http://localhost/custom/mv3pro_portail/api/v1/reports_create.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "project_id": 1,
    "date_report": "2026-01-10",
    "time_start": "2026-01-10 09:00:00",
    "time_end": "2026-01-10 13:00:00",
    "duration_minutes": 240,
    "note_public": "Installation enseigne réalisée avec succès. Client satisfait.",
    "note_private": "Attention: refaire passage câble",
    "status": 0,
    "lines": [
      {
        "label": "Pose structure",
        "description": "Fixation murale + scellement",
        "qty_minutes": 120
      },
      {
        "label": "Câblage électrique",
        "description": "Raccordement alimentation",
        "qty_minutes": 60
      },
      {
        "label": "Tests et mise en service",
        "qty_minutes": 60
      }
    ]
  }'
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ref": "RPT-2026-000001",
    "status": 0
  }
}
```

---

## 📋 3. LISTE RAPPORTS

Récupérer la liste des rapports avec filtres.

### Tous les rapports

```bash
curl http://localhost/custom/mv3pro_portail/api/v1/reports_list.php \
  -b cookies.txt
```

### Avec filtres

```bash
# Rapports d'un projet
curl "http://localhost/custom/mv3pro_portail/api/v1/reports_list.php?project_id=1" \
  -b cookies.txt

# Rapports soumis
curl "http://localhost/custom/mv3pro_portail/api/v1/reports_list.php?status=1" \
  -b cookies.txt

# Rapports d'une période
curl "http://localhost/custom/mv3pro_portail/api/v1/reports_list.php?date_from=2026-01-01&date_to=2026-01-31" \
  -b cookies.txt

# Rapports d'un utilisateur
curl "http://localhost/custom/mv3pro_portail/api/v1/reports_list.php?user_id=5" \
  -b cookies.txt

# Avec pagination
curl "http://localhost/custom/mv3pro_portail/api/v1/reports_list.php?limit=10&offset=0" \
  -b cookies.txt
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "reports": [
      {
        "id": 1,
        "ref": "RPT-2026-000001",
        "project_id": 1,
        "project_ref": "PROJ-001",
        "project_title": "Installation Enseigne",
        "author_id": 1,
        "author_name": "Jean Dupont",
        "date_report": 1736467200,
        "duration_minutes": 240,
        "status": 0,
        "status_label": "Brouillon",
        "created_at": 1736467200
      }
    ],
    "total": 1,
    "limit": 100,
    "offset": 0
  }
}
```

---

## 🔍 4. DÉTAIL RAPPORT

Récupérer un rapport complet avec lignes et photos.

```bash
curl "http://localhost/custom/mv3pro_portail/api/v1/reports_get.php?id=1" \
  -b cookies.txt
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ref": "RPT-2026-000001",
    "project": {
      "id": 1,
      "ref": "PROJ-001",
      "title": "Installation Enseigne"
    },
    "author": {
      "id": 1,
      "name": "Jean Dupont",
      "login": "jdupont"
    },
    "date_report": 1736467200,
    "time_start": 1736499600,
    "time_end": 1736514000,
    "duration_minutes": 240,
    "note_public": "Installation réalisée",
    "note_private": "RAS",
    "status": 0,
    "status_label": "Brouillon",
    "lines": [
      {
        "id": 1,
        "label": "Pose structure",
        "description": "Fixation murale",
        "qty_minutes": 120,
        "note": "",
        "sort_order": 0
      }
    ],
    "files": [
      {
        "name": "photo_1736467300.jpg",
        "size": 2048576,
        "date": 1736467300,
        "url": "/document.php?modulepart=mv3pro_portail&file=report/1/photo_1736467300.jpg"
      }
    ],
    "created_at": 1736467200,
    "updated_at": 1736467200
  }
}
```

---

## ✏️ 5. MODIFIER RAPPORT

Mettre à jour un rapport existant.

```bash
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_update.php?id=1" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "duration_minutes": 300,
    "note_public": "Installation terminée. Client très satisfait."
  }'
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ref": "RPT-2026-000001",
    "status": 0
  }
}
```

---

## 🚀 6. SOUMETTRE / CHANGER STATUT

Changer le statut d'un rapport.

### Soumettre (brouillon → soumis)

```bash
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_submit.php?id=1&status=1" \
  -b cookies.txt
```

### Valider (soumis → validé) - Admin seulement

```bash
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_submit.php?id=1&status=2" \
  -b cookies.txt
```

### Rejeter

```bash
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_submit.php?id=1&status=9" \
  -b cookies.txt
```

**Statuts:**
- `0` = Brouillon
- `1` = Soumis
- `2` = Validé
- `9` = Rejeté

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "ref": "RPT-2026-000001",
    "status": 1,
    "status_label": "Soumis"
  }
}
```

---

## 📸 7. UPLOAD PHOTO

Uploader une photo pour un rapport.

```bash
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_upload.php?report_id=1" \
  -b cookies.txt \
  -F "file=@/path/to/photo.jpg"
```

**Formats acceptés:** JPG, PNG, GIF, WEBP
**Taille max:** 10 MB

**Réponse:**
```json
{
  "success": true,
  "data": {
    "filename": "photo_1736467300.jpg",
    "url": "/document.php?modulepart=mv3pro_portail&file=report/1/photo_1736467300.jpg"
  }
}
```

---

## 🗑️ 8. SUPPRIMER PHOTO

Supprimer une photo d'un rapport.

```bash
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_delete_file.php?report_id=1&filename=photo_1736467300.jpg" \
  -b cookies.txt
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "deleted": true
  }
}
```

---

## 🗑️ 9. SUPPRIMER RAPPORT

Supprimer un rapport (admin seulement).

```bash
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_delete.php?id=1" \
  -b cookies.txt
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "deleted": true
  }
}
```

---

## ❌ GESTION ERREURS

Toutes les APIs retournent des erreurs JSON standardisées.

### Exemples

**401 - Non authentifié:**
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Non authentifié"
  }
}
```

**403 - Droits insuffisants:**
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Accès refusé"
  }
}
```

**404 - Rapport introuvable:**
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Rapport introuvable"
  }
}
```

**400 - Champs manquants:**
```json
{
  "success": false,
  "error": {
    "code": "MISSING_FIELDS",
    "message": "Champs requis manquants : date_report"
  }
}
```

**500 - Erreur serveur:**
```json
{
  "success": false,
  "error": {
    "code": "DB_ERROR",
    "message": "Table 'llx_mv3_report' doesn't exist"
  }
}
```

---

## 🧪 SCÉNARIO DE TEST COMPLET

Test du workflow complet création → modification → soumission → validation.

```bash
# 1. Login
curl -X POST http://localhost/custom/mv3pro_portail/api/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}' \
  -c cookies.txt

# 2. Lister projets
curl http://localhost/custom/mv3pro_portail/api/v1/reports_projects.php \
  -b cookies.txt

# 3. Créer rapport (brouillon)
curl -X POST http://localhost/custom/mv3pro_portail/api/v1/reports_create.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "project_id": 1,
    "date_report": "2026-01-10",
    "duration_minutes": 240,
    "note_public": "Travaux en cours",
    "status": 0
  }'

# Retient l'ID du rapport créé (ex: 1)
REPORT_ID=1

# 4. Upload 2 photos
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_upload.php?report_id=$REPORT_ID" \
  -b cookies.txt \
  -F "file=@photo1.jpg"

curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_upload.php?report_id=$REPORT_ID" \
  -b cookies.txt \
  -F "file=@photo2.jpg"

# 5. Modifier rapport
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_update.php?id=$REPORT_ID" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "note_public": "Travaux terminés"
  }'

# 6. Soumettre
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_submit.php?id=$REPORT_ID&status=1" \
  -b cookies.txt

# 7. Voir détail complet
curl "http://localhost/custom/mv3pro_portail/api/v1/reports_get.php?id=$REPORT_ID" \
  -b cookies.txt

# 8. Valider (admin)
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_submit.php?id=$REPORT_ID&status=2" \
  -b cookies.txt

# 9. Liste tous les rapports
curl http://localhost/custom/mv3pro_portail/api/v1/reports_list.php \
  -b cookies.txt

# 10. Supprimer une photo
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_delete_file.php?report_id=$REPORT_ID&filename=photo1.jpg" \
  -b cookies.txt

# 11. Supprimer rapport (admin)
curl -X POST "http://localhost/custom/mv3pro_portail/api/v1/reports_delete.php?id=$REPORT_ID" \
  -b cookies.txt
```

---

## 🔒 SÉCURITÉ

### Vérifications automatiques

Toutes les APIs vérifient:

1. **Authentification** : Session Dolibarr active
2. **Droits** : Permissions utilisateur
3. **Propriété** : Non-admin ne voit que ses rapports
4. **Statut** : Empêche modification rapport validé (sauf admin)
5. **Validation** : Champs requis, types, formats
6. **Upload** : Type fichier (images), taille max (10 MB)
7. **SQL Injection** : Paramètres échappés
8. **Entity** : Multi-entity support

---

## 📊 CODES HTTP

| Code | Signification | Quand |
|------|---------------|-------|
| 200 | OK | Succès GET/POST |
| 201 | Created | Création réussie |
| 400 | Bad Request | Paramètres invalides |
| 401 | Unauthorized | Non authentifié |
| 403 | Forbidden | Droits insuffisants |
| 404 | Not Found | Ressource introuvable |
| 500 | Server Error | Erreur DB/serveur |

---

## 🛠️ OUTILS

### Postman Collection

Importer collection Postman pour tests graphiques:

```json
{
  "info": {
    "name": "MV3 PRO - API Rapports",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "1. Login",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/api/v1/auth/login.php",
        "body": {
          "mode": "raw",
          "raw": "{\"username\":\"admin\",\"password\":\"admin\"}"
        }
      }
    },
    {
      "name": "2. Liste Projets",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/api/v1/reports_projects.php"
      }
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost/custom/mv3pro_portail"
    }
  ]
}
```

### Script Bash complet

```bash
#!/bin/bash
# test-api-rapports.sh

BASE_URL="http://localhost/custom/mv3pro_portail/api/v1"
COOKIES="cookies.txt"

# Login
echo "=== LOGIN ==="
curl -X POST $BASE_URL/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}' \
  -c $COOKIES

# Créer rapport
echo -e "\n=== CRÉER RAPPORT ==="
REPORT=$(curl -X POST $BASE_URL/reports_create.php \
  -H "Content-Type: application/json" \
  -b $COOKIES \
  -d '{"date_report":"2026-01-10","duration_minutes":240}')

REPORT_ID=$(echo $REPORT | jq -r '.data.id')
echo "Rapport créé : ID=$REPORT_ID"

# Lister
echo -e "\n=== LISTE ==="
curl $BASE_URL/reports_list.php -b $COOKIES | jq

# Détail
echo -e "\n=== DÉTAIL ==="
curl "$BASE_URL/reports_get.php?id=$REPORT_ID" -b $COOKIES | jq

echo -e "\n=== TESTS TERMINÉS ==="
```

---

**MV-3 PRO Team • API Tests v3.0.0**
