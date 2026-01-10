# 🧹 PLAN NETTOYAGE MODULE MINIMAL - Planning + PWA uniquement

## 🎯 OBJECTIF

Module ultra-minimal avec uniquement :
- Planning Dolibarr
- PWA (pwa_dist)
- API minimum (auth + planning + upload)

---

## 📂 STRUCTURE FINALE ATTENDUE

```
custom/mv3pro_portail/
├── admin/
│   ├── setup.php (config minimale)
│   └── about.php (optionnel)
├── api/
│   └── v1/
│       ├── .htaccess
│       ├── _bootstrap.php
│       ├── auth/
│       │   ├── .htaccess
│       │   ├── login.php
│       │   ├── logout.php
│       │   └── me.php
│       ├── planning.php
│       ├── planning_view.php
│       ├── planning_file.php
│       ├── planning_upload_photo.php
│       └── planning_debug.php (optionnel)
├── class/
│   └── (uniquement classes nécessaires pour planning)
├── core/
│   ├── modules/
│   │   └── modMv3pro_portail.class.php
│   ├── init.php
│   ├── auth.php
│   ├── functions.php
│   └── permissions.php
├── langs/
│   └── fr_FR/
│       └── mv3pro_portail.lang
├── pwa/ (optionnel - sources)
├── pwa_dist/ (obligatoire - build)
├── sql/
│   └── (tables minimum si nécessaires)
└── README.md (optionnel)
```

**Total estimé : ~20 fichiers** (vs 200+ actuellement)

---

## 🗑️ DOSSIERS À SUPPRIMER COMPLÈTEMENT

### 1. Ancien système mobile
```
✗ mobile_app/
```

### 2. Modules rapports/régie/sens_pose
```
✗ rapports/
✗ regie/
✗ sens_pose/
```

### 3. Modules sous-traitants
```
✗ subcontractor_app/
✗ subcontractors/
```

---

## 🧹 FICHIERS API À SUPPRIMER

### Dans `api/v1/`

**Rapports** (14 fichiers) :
```
✗ rapports.php
✗ rapports_create.php
✗ rapports_view.php
✗ rapports_list.php
✗ rapports_debug.php
✗ rapports_pdf.php
✗ rapports_send_email.php
✗ rapports_photos_upload.php
```

**Régie** (7 fichiers) :
```
✗ regie.php
✗ regie_create.php
✗ regie_view.php
✗ regie_list.php
✗ regie_pdf.php
✗ regie_send_email.php
✗ regie_signature.php
✗ regie_add_photo.php
```

**Sens Pose** (8 fichiers) :
```
✗ sens_pose.php
✗ sens_pose_create.php
✗ sens_pose_create_from_devis.php
✗ sens_pose_view.php
✗ sens_pose_list.php
✗ sens_pose_pdf.php
✗ sens_pose_send_email.php
✗ sens_pose_signature.php
```

**Matériel** (4 fichiers) :
```
✗ materiel.php
✗ materiel_list.php
✗ materiel_view.php
✗ materiel_action.php
```

**Frais** (3 fichiers) :
```
✗ frais_list.php
✗ frais_update_status.php
✗ frais_export_csv.php
```

**Notifications** (5 fichiers) :
```
✗ notifications.php
✗ notifications_list.php
✗ notifications_mark_read.php
✗ notifications_read.php
✗ notifications_unread.php
✗ notifications_unread_count.php
```

**Sous-traitants** (3 fichiers) :
```
✗ subcontractor_login.php
✗ subcontractor_submit_report.php
```

**Debug/Test inutiles** (10+ fichiers) :
```
✗ debug.php
✗ debug_log.php
✗ debug_auth.php
✗ test_planning.php
✗ test_planning_detail.php
✗ test_upload_debug.php
✗ diagnostic_fichiers_planning.php
✗ diagnostic_upload_permissions.php
✗ fix_directories.php
✗ live_debug.php
✗ live_debug.html
✗ live_debug_session.php
```

**Autres** :
```
✗ index.php (si existe)
✗ users.php
✗ file.php (si doublon avec planning_file.php)
✗ get_debug_token.php
✗ mv3_auth.php (si doublon avec auth/)
✗ me.php (déplacer dans auth/)
```

**Total à supprimer : ~55 fichiers API**

---

## ✅ FICHIERS API À GARDER

```
✓ api/v1/.htaccess
✓ api/v1/_bootstrap.php
✓ api/v1/auth/.htaccess
✓ api/v1/auth/login.php
✓ api/v1/auth/logout.php
✓ api/v1/auth/me.php
✓ api/v1/planning.php
✓ api/v1/planning_view.php
✓ api/v1/planning_file.php
✓ api/v1/planning_upload_photo.php
✓ api/v1/planning_debug.php (optionnel mais utile)
```

**Total à garder : 11 fichiers**

---

## 🔧 FICHIERS À SIMPLIFIER

### 1. `core/modules/modMv3pro_portail.class.php`

**Menu actuel** :
```php
$this->menu[] = array(
    'fk_menu' => 'fk_mainmenu=mv3pro',
    'type' => 'left',
    'titre' => 'Planning',
    ...
);
$this->menu[] = array(
    'titre' => 'Rapports',  // ✗ SUPPRIMER
    ...
);
$this->menu[] = array(
    'titre' => 'Régie',  // ✗ SUPPRIMER
    ...
);
// etc.
```

**Menu minimal** :
```php
$this->menu[] = array(
    'fk_menu' => 'fk_mainmenu=mv3pro',
    'type' => 'left',
    'titre' => 'Planning',
    'url' => '/custom/mv3pro_portail/planning/index.php',
    'langs' => 'mv3pro_portail@mv3pro_portail',
    'perms' => '1',
    'enabled' => '1',
    'position' => 1000
);
```

### 2. `admin/setup.php`

**Config actuelle** : 20+ options

**Config minimale** :
```php
// Module activé/désactivé (géré par Dolibarr)

// URL PWA (optionnel)
$form->textwithpicto(
    $form->editfieldkey('URL PWA', 'MV3PRO_PWA_URL', '', $object, 0),
    'URL de la Progressive Web App'
);
print '<input type="text" name="MV3PRO_PWA_URL" value="'.getDolGlobalString('MV3PRO_PWA_URL').'">';
```

### 3. `class/` - Nettoyer

**Classes actuelles** :
```
✗ mv3_rapport.class.php
✗ mv3_regie.class.php
✗ mv3_sens_pose.class.php
✗ mv3_materiel.class.php
✗ mv3_subcontractor.class.php
✗ actions_mv3pro_portail.class.php (hooks non utilisés)
```

**Classes à garder** :
```
✓ mv3_config.class.php (si utilisée)
✓ mv3_error_logger.class.php (si utilisée)
✓ object_helper.class.php (si utilisée pour planning)
```

### 4. `sql/` - Nettoyer

**Tables actuelles** :
```
✗ llx_mv3_rapport.sql
✗ llx_mv3_regie.sql
✗ llx_mv3_sens_pose.sql
✗ llx_mv3_materiel.sql
✗ llx_mv3_subcontractors.sql
✗ llx_mv3_notifications.sql
✗ llx_mv3_mobile_users.sql
```

**Tables à garder** :
```
✓ llx_mv3_config.sql (si config stockée en BDD)
✓ llx_mv3_error_log.sql (si logs en BDD)
```

**Note** : Le planning utilise les tables standard Dolibarr (`llx_actioncomm`), donc pas besoin de tables custom.

---

## 🎯 MENU DOLIBARR FINAL

Dans l'interface Dolibarr, un seul menu visible :

```
MV-3 PRO
└── Planning
```

Pas de :
- ✗ Rapports
- ✗ Régie
- ✗ Sens Pose
- ✗ Matériel
- ✗ Notifications
- ✗ Configuration (déjà dans Setup)

---

## 🔐 SÉCURITÉ

### Vérifications avant suppression :

1. **Backup complet** avant toute suppression
   ```bash
   cp -r custom/mv3pro_portail custom/mv3pro_portail.BACKUP_$(date +%Y%m%d_%H%M%S)
   ```

2. **Ne pas toucher** :
   - ✓ `htdocs/` (core Dolibarr)
   - ✓ Autres modules dans `custom/`
   - ✓ Tables BDD (juste ne plus les utiliser)

3. **Vérifier erreurs 500** :
   - `require_once` au lieu de `require`
   - `function_exists()` avant déclaration
   - Chemins relatifs corrects

---

## ✅ CHECKLIST VALIDATION FINALE

### API
- [ ] `auth/login.php` fonctionne
- [ ] `auth/logout.php` fonctionne
- [ ] `auth/me.php` retourne user info
- [ ] `planning.php` retourne liste événements
- [ ] `planning_view.php?id=X` retourne détail
- [ ] `planning_upload_photo.php` upload fichier OK
- [ ] `planning_file.php?id=X` récupère fichier
- [ ] Aucune erreur 500 dans logs

### PWA
- [ ] `pwa_dist/index.html` s'affiche
- [ ] Login fonctionne
- [ ] Page Planning affiche événements
- [ ] Clic sur événement → détail
- [ ] Upload photo fonctionne
- [ ] Photo visible dans détail PWA
- [ ] Aucune erreur console F12

### Dolibarr
- [ ] Menu "MV-3 PRO → Planning" visible
- [ ] Pas d'autres menus liés au module
- [ ] Page planning Dolibarr fonctionne
- [ ] Fichiers uploadés via PWA visibles dans Dolibarr
- [ ] Module config accessible (Setup)
- [ ] Aucune erreur PHP

### Nettoyage
- [ ] Dossiers mobile_app, rapports, regie, sens_pose supprimés
- [ ] Fichiers API inutiles supprimés
- [ ] Classes PHP inutiles supprimées
- [ ] SQL inutile supprimé
- [ ] Menu simplifié
- [ ] Config simplifiée

---

## 📊 COMPARAISON AVANT/APRÈS

| Élément | Avant | Après | Réduction |
|---------|-------|-------|-----------|
| Fichiers API | 62 | 11 | -82% |
| Dossiers racine | 15 | 8 | -47% |
| Classes PHP | 10+ | 0-3 | -70%+ |
| Tables SQL | 12+ | 0-2 | -83%+ |
| Menus Dolibarr | 6+ | 1 | -83% |
| Config options | 20+ | 2 | -90% |

---

## ⏱️ ESTIMATION

- **Analyse** : 15 min
- **Backup** : 5 min
- **Suppression fichiers** : 30 min
- **Modification menu** : 15 min
- **Modification config** : 10 min
- **Tests validation** : 30 min

**Total : ~2 heures**

---

## 🚀 PROCHAINES ÉTAPES

1. Créer backup complet
2. Supprimer dossiers inutiles
3. Supprimer fichiers API inutiles
4. Simplifier menu dans modMv3pro_portail.class.php
5. Simplifier config dans admin/setup.php
6. Nettoyer classes PHP
7. Tester Planning + PWA + Upload
8. Valider aucune erreur 500
9. Documenter structure finale

---

**Status** : 📋 PLAN PRÊT - En attente validation avant exécution
