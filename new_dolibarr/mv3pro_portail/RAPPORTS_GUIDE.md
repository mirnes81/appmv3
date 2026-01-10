# 📋 GUIDE COMPLET - SYSTÈME RAPPORTS CHANTIER

Version 3.0.0-rapports

---

## 🎯 OBJECTIF

Système complet de gestion des rapports de chantier avec PWA mobile, permettant aux techniciens de créer des rapports terrain avec photos depuis leur smartphone.

---

## 📂 STRUCTURE COMPLÈTE

```
custom/mv3pro_portail/
├── sql/                                    ← Tables SQL
│   ├── llx_mv3_report.sql                 ← Table rapports
│   ├── llx_mv3_report_line.sql            ← Table lignes tâches
│   └── llx_mv3_report_counter.sql         ← Compteur refs uniques
│
├── class/                                  ← Classes PHP
│   ├── report.class.php                   ← Classe Report
│   └── reportline.class.php               ← Classe ReportLine
│
├── lib/                                    ← Helpers
│   ├── api.lib.php                        ← Helpers JSON/auth
│   └── upload.lib.php                     ← Upload photos
│
├── api/v1/                                 ← API REST
│   ├── reports_projects.php               ← Liste projets
│   ├── reports_list.php                   ← Liste rapports
│   ├── reports_get.php                    ← Détail rapport
│   ├── reports_create.php                 ← Créer rapport
│   ├── reports_update.php                 ← Modifier rapport
│   ├── reports_submit.php                 ← Changer statut
│   ├── reports_delete.php                 ← Supprimer (admin)
│   ├── reports_upload.php                 ← Upload photo
│   └── reports_delete_file.php            ← Supprimer photo
│
├── reports/                                ← Pages backend
│   └── list.php                           ← Liste rapports (admin)
│
├── pwa/src/                                ← PWA React
│   ├── lib/reports-api.ts                 ← API client
│   ├── pages/Rapports.tsx                 ← Liste (À CRÉER)
│   ├── pages/RapportNew.tsx               ← Création (À CRÉER)
│   └── pages/RapportDetail.tsx            ← Détail (À CRÉER)
│
└── core/modules/
    └── modMv3pro_portail.class.php        ← Module descriptor (MAJ)
```

---

## 🗄️ BASE DE DONNÉES

### Table: llx_mv3_report

Stocke les rapports de chantier

| Champ | Type | Description |
|-------|------|-------------|
| rowid | INT | ID unique |
| entity | INT | Multi-entity |
| ref | VARCHAR(30) | Référence unique (RPT-2026-000001) |
| fk_project | INT | Projet Dolibarr (nullable) |
| fk_user_author | INT | Auteur du rapport |
| fk_user_assigned | INT | Utilisateur assigné (nullable) |
| date_report | DATE | Date du rapport |
| time_start | DATETIME | Heure début (nullable) |
| time_end | DATETIME | Heure fin (nullable) |
| duration_minutes | INT | Durée en minutes (nullable) |
| note_public | TEXT | Notes publiques |
| note_private | TEXT | Notes privées |
| status | INT | 0=Brouillon, 1=Soumis, 2=Validé, 9=Rejeté |
| datec, tms, fk_user_creat, fk_user_modif | - | Méta-données |

### Table: llx_mv3_report_line

Lignes de tâches optionnelles

| Champ | Type | Description |
|-------|------|-------------|
| rowid | INT | ID unique |
| entity | INT | Multi-entity |
| fk_report | INT | ID rapport parent |
| label | VARCHAR(255) | Libellé tâche |
| description | TEXT | Description (nullable) |
| qty_minutes | INT | Durée tâche en minutes (nullable) |
| note | TEXT | Notes (nullable) |
| sort_order | INT | Ordre d'affichage |

### Table: llx_mv3_report_counter

Compteur pour numérotation unique atomique

| Champ | Type | Description |
|-------|------|-------------|
| entity | INT | Entity |
| year | INT | Année |
| last_value | INT | Dernier numéro utilisé |

**PK:** (entity, year)

---

## 🔢 NUMÉROTATION UNIQUE

### Format

```
RPT-YYYY-NNNNNN
```

Exemple: `RPT-2026-000001`

### Génération

La méthode `Report::getNextNumRef()` utilise:
1. **Transaction DB** avec `BEGIN`
2. **Lock pessimiste** : `SELECT ... FOR UPDATE`
3. **Incrément atomique**
4. **COMMIT**

Garantit l'unicité même en concurrence (plusieurs techniciens créant simultanément).

---

## 🔐 DROITS

### Définis dans modMv3pro_portail.class.php

| ID | Libellé | Par défaut | Permission |
|----|---------|------------|------------|
| 510003 | Créer/modifier ses rapports | OUI | `reports_create` |
| 510004 | Voir tous les rapports | NON | `reports_readall` |
| 510005 | Valider/supprimer rapports | NON | `reports_admin` |

### Logique

**Employé** (reports_create):
- Créer rapports
- Voir SES rapports uniquement
- Modifier tant que brouillon/soumis
- Upload photos
- Soumettre

**Admin** (reports_admin):
- Voir TOUS les rapports
- Modifier même validés
- Valider
- Supprimer
- Exporter

---

## 📡 API REST

### Base URL

```
/custom/mv3pro_portail/api/v1/
```

### Authentification

Toutes les APIs nécessitent une session Dolibarr active.

### Endpoints

#### 1. Liste projets

```
GET /reports_projects.php?search=...
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "ref": "PROJ-001",
      "title": "Installation Enseigne",
      "thirdparty_name": "Carrefour SA"
    }
  ]
}
```

#### 2. Liste rapports

```
GET /reports_list.php?project_id=&date_from=&date_to=&status=&user_id=&limit=&offset=
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "reports": [
      {
        "id": 456,
        "ref": "RPT-2026-000001",
        "project_id": 123,
        "project_ref": "PROJ-001",
        "project_title": "Installation Enseigne",
        "author_id": 10,
        "author_name": "Jean Dupont",
        "date_report": 1736467200,
        "duration_minutes": 240,
        "status": 1,
        "status_label": "Soumis",
        "created_at": 1736467200
      }
    ],
    "total": 1,
    "limit": 100,
    "offset": 0
  }
}
```

#### 3. Détail rapport

```
GET /reports_get.php?id=456
```

**Réponse:** Rapport complet avec projet, auteur, lignes, photos.

#### 4. Créer rapport

```
POST /reports_create.php
Content-Type: application/json

{
  "project_id": 123,
  "date_report": "2026-01-10",
  "time_start": "2026-01-10 09:00:00",
  "time_end": "2026-01-10 13:00:00",
  "duration_minutes": 240,
  "note_public": "Installation réalisée",
  "status": 0,
  "lines": [
    {
      "label": "Pose enseigne",
      "qty_minutes": 180
    },
    {
      "label": "Câblage électrique",
      "qty_minutes": 60
    }
  ]
}
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "ref": "RPT-2026-000001",
    "status": 0
  }
}
```

#### 5. Mettre à jour rapport

```
POST /reports_update.php?id=456
Content-Type: application/json

{
  "note_public": "Installation terminée avec succès"
}
```

#### 6. Changer statut

```
POST /reports_submit.php?id=456&status=1
```

Status:
- `0` = Brouillon
- `1` = Soumis
- `2` = Validé (admin only)
- `9` = Rejeté

#### 7. Supprimer (admin only)

```
POST /reports_delete.php?id=456
```

#### 8. Upload photo

```
POST /reports_upload.php?report_id=456
Content-Type: multipart/form-data

file: [binary]
```

**Formats acceptés:** JPG, PNG, GIF, WEBP (max 10 MB)

#### 9. Supprimer photo

```
POST /reports_delete_file.php?report_id=456&filename=photo.jpg
```

---

## 📱 PWA MOBILE

### Pages à créer

#### 1. Liste Rapports (`/rapports`)

**Fonctionnalités:**
- Afficher liste avec filtres (projet, date, statut)
- Bouton "+ Nouveau"
- Carte par rapport avec:
  - Ref + projet
  - Date + durée
  - Badge statut
  - Clic → détail

**Fichier:** `pwa/src/pages/Rapports.tsx`

#### 2. Création Rapport (`/rapports/new`)

**Formulaire:**
- Recherche projet (autocomplete)
- Date (défaut: aujourd'hui)
- Heures début/fin OU durée
- Notes
- Lignes tâches (optionnel)
- Actions:
  - Enregistrer brouillon
  - Soumettre

**Fichier:** `pwa/src/pages/RapportNew.tsx`

#### 3. Détail Rapport (`/rapports/:id`)

**Affichage:**
- Ref + projet
- Date + heures + durée
- Auteur
- Notes
- Lignes tâches
- Galerie photos (grille)
  - Clic → plein écran
  - Upload depuis caméra/galerie
  - Supprimer
- Actions:
  - Modifier (si brouillon/soumis)
  - Soumettre
  - Valider (admin)
  - Supprimer (admin)
  - Exporter PDF

**Fichier:** `pwa/src/pages/RapportDetail.tsx`

### API Client

Utiliser les fonctions de `lib/reports-api.ts`:

```typescript
import { getReports, createReport, uploadReportPhoto } from '@/lib/reports-api';

// Lister
const { reports, total } = await getReports({ status: 1 });

// Créer
const result = await createReport({
  project_id: 123,
  date_report: '2026-01-10',
  duration_minutes: 240,
  note_public: 'Travaux réalisés'
});

// Upload photo
const file = ...; // File from input
await uploadReportPhoto(result.id, file);
```

---

## 📸 STOCKAGE PHOTOS

### Répertoire

```
<DOL_DATA_ROOT>/mv3pro_portail/report/<report_id>/
```

Exemple:
```
/var/www/dolibarr/documents/mv3pro_portail/report/456/
├── photo_1736467200.jpg
├── photo_1736467245.jpg
└── plan_1736467300.png
```

### URL d'accès

Via `document.php`:

```
/document.php?modulepart=mv3pro_portail&file=report/456/photo_1736467200.jpg
```

---

## 🎨 MENU DOLIBARR

```
MV-3 PRO (menu top)
│
├── Dashboard              ← widgets + stats
├── Planning               ← agenda Dolibarr
└── Rapports               ← liste rapports (redirige vers PWA pour création) ⭐ NOUVEAU
```

---

## 🚀 INSTALLATION

### 1. Copier fichiers

```bash
scp -r new_dolibarr/mv3pro_portail/* user@server:/path/to/dolibarr/custom/mv3pro_portail/
```

### 2. Permissions

```bash
chmod 644 custom/mv3pro_portail/**/*.php
chmod 755 custom/mv3pro_portail/pwa_dist/
```

### 3. Activer module

1. Dolibarr → Configuration → Modules
2. Chercher "MV-3 PRO Portail"
3. Activer

**Les tables SQL sont créées automatiquement !**

### 4. Configurer URL PWA

Setup → Modules → MV-3 PRO → URL PWA:
```
/custom/mv3pro_portail/pwa_dist/
```

### 5. Attribuer droits

Utilisateurs → Permissions:
- Employés: `reports_create`
- Managers: `reports_create` + `reports_readall`
- Admins: `reports_create` + `reports_readall` + `reports_admin`

---

## 🔄 WORKFLOW

### 1. Employé crée rapport

```
PWA → Rapports → Nouveau
↓
Saisit infos (projet, date, heures, notes)
↓
Upload photos terrain (caméra/galerie)
↓
Enregistrer brouillon (status=0)
OU
Soumettre (status=1)
```

### 2. Manager revoit

```
Dolibarr → MV-3 PRO → Rapports
OU
PWA → Rapports → Filtre par statut
↓
Voir détails
↓
Valider (status=2) ou Rejeter (status=9)
```

### 3. Export/Archivage

```
PWA → Rapport → Exporter PDF
↓
Envoi client / archivage
```

---

## ✅ VALIDATION

### Backend Dolibarr

```bash
# Vérifier tables créées
mysql -u root -p dolibarr
> SHOW TABLES LIKE 'llx_mv3_report%';
> DESC llx_mv3_report;

# Vérifier compteur
> SELECT * FROM llx_mv3_report_counter;
```

### API

```bash
# Test login
curl -X POST http://dolibarr.local/custom/mv3pro_portail/api/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"demo","password":"demo"}'

# Test liste projets
curl http://dolibarr.local/custom/mv3pro_portail/api/v1/reports_projects.php \
  -H "Cookie: DOLSESSID_..."
```

### PWA

1. Ouvrir PWA
2. Aller à `/rapports`
3. Créer nouveau rapport
4. Upload photo
5. Soumettre
6. Vérifier dans Backend → Rapports

---

## 🐛 TROUBLESHOOTING

### Erreur "Table not found"

```sql
-- Recréer manuellement
source custom/mv3pro_portail/sql/llx_mv3_report.sql;
source custom/mv3pro_portail/sql/llx_mv3_report_line.sql;
source custom/mv3pro_portail/sql/llx_mv3_report_counter.sql;
```

### Droits insuffisants

Vérifier:
```php
var_dump($user->rights->mv3pro_portail);
```

Doit afficher: `reports_create`, `reports_readall`, `reports_admin`

### Photos non uploadées

Vérifier permissions:
```bash
ls -la /var/www/dolibarr/documents/mv3pro_portail/report/
```

Doit être `www-data:www-data` avec `755` ou `775`.

### API retourne 401

Session expirée. Re-login via `/api/v1/auth/login.php`.

---

## 📊 STATISTIQUES

| Métrique | Valeur |
|----------|--------|
| **Tables SQL** | 3 |
| **Classes PHP** | 2 |
| **API Endpoints** | 9 |
| **Helpers lib/** | 2 |
| **Pages PWA** | 3 (à créer) |
| **Droits** | 3 |
| **Menus** | +1 (Rapports) |

---

## 🎯 PROCHAINES ÉTAPES

### Phase 1: Finaliser PWA

- [ ] Créer `Rapports.tsx` (liste)
- [ ] Créer `RapportNew.tsx` (création)
- [ ] Créer `RapportDetail.tsx` (détail + photos)
- [ ] Ajouter routes dans `App.tsx`
- [ ] Builder PWA: `npm run build`

### Phase 2: Tests

- [ ] Créer rapport depuis PWA
- [ ] Upload photos
- [ ] Soumettre
- [ ] Valider depuis backend
- [ ] Vérifier numérotation unique
- [ ] Tester filtres liste

### Phase 3: Production

- [ ] Déployer vers serveur
- [ ] Former utilisateurs
- [ ] Monitorer usage
- [ ] Collecter feedbacks

---

## 📞 SUPPORT

**Documentation:**
- Ce fichier: `RAPPORTS_GUIDE.md`
- Structure: `STRUCTURE_FINALE.txt`
- README: `README.md`

**Fichiers clés:**
- Module descriptor: `core/modules/modMv3pro_portail.class.php`
- Classe Report: `class/report.class.php`
- API helpers: `lib/api.lib.php`
- API client: `pwa/src/lib/reports-api.ts`

---

**MV-3 PRO Team • Version 3.0.0-rapports • Janvier 2026**
