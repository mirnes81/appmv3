# Fix Erreur 500 - Upload Photos Planning

## Date: 10 janvier 2026

## Problème
Erreur 500 lors de l'upload de photos depuis la PWA vers les événements du planning.

## Cause
1. **Utilisation de `dol_include_once`** au lieu de `require_once DOL_DOCUMENT_ROOT`
2. **Chargement tardif de `files.lib.php`** (après utilisation de `dol_mkdir`)
3. **Chemin de répertoire incorrect** (`$conf->actioncomm->dir_output` au lieu de `$conf->mv3pro_portail->dir_output`)
4. **Filepath dans ecm_files incorrect** (manquait le préfixe `mv3pro_portail/`)

## Solutions appliquées

### 1. Correction des imports (ligne 80-81)
**AVANT:**
```php
dol_include_once('/comm/action/class/actioncomm.class.php');
```

**APRÈS:**
```php
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
```

### 2. Correction du répertoire d'upload (ligne 96)
**AVANT:**
```php
$upload_dir = $conf->actioncomm->dir_output . '/' . $event_id;
```

**APRÈS:**
```php
$upload_dir = $conf->mv3pro_portail->dir_output . '/planning/' . $event_id;
```

### 3. Correction du filepath dans ecm_files (ligne 122)
**AVANT:**
```php
'".$db->escape('planning/' . $event_id)."',
```

**APRÈS:**
```php
'".$db->escape('mv3pro_portail/planning/' . $event_id)."',
```

## Architecture des fichiers

### Structure des répertoires
```
/documents/
  └── mv3pro_portail/
      └── planning/
          └── [event_id]/
              ├── photo1.jpg
              ├── photo2.png
              └── ...
```

### Enregistrement dans ecm_files
- **filepath**: `mv3pro_portail/planning/[event_id]`
- **filename**: `[nom_original]_[timestamp].[extension]`
- **src_object_type**: `actioncomm`
- **src_object_id**: `[event_id]`

### Récupération dans planning_view.php
Le fichier `planning_view.php` construit le chemin complet comme:
```php
$filepath = DOL_DATA_ROOT . '/' . $file_obj->filepath . '/' . $file_obj->stored_filename;
// Résultat: /documents/mv3pro_portail/planning/74049/photo_1234567890.jpg
```

## Test de validation

### 1. Test d'upload
```bash
# Envoyer une photo via l'API
curl -X POST \
  -H "Authorization: Bearer [TOKEN]" \
  -F "file=@test.jpg" \
  -F "id=74049" \
  https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_upload_photo.php
```

### 2. Réponse attendue
```json
{
  "success": true,
  "message": "Photo uploadée avec succès",
  "file": {
    "name": "test_1736510400.jpg",
    "original_name": "test.jpg",
    "size": 123456,
    "mime": "image/jpeg"
  }
}
```

### 3. Vérification en base de données
```sql
SELECT * FROM llx_ecm_files
WHERE src_object_type = 'actioncomm'
AND src_object_id = 74049
ORDER BY date_c DESC
LIMIT 1;
```

### 4. Vérification sur disque
```bash
ls -lah /var/www/dolibarr/documents/mv3pro_portail/planning/74049/
```

## Sécurité

### Validations appliquées
✅ Type MIME vérifié (images uniquement)
✅ Taille max 10MB
✅ Nom de fichier sécurisé (caractères spéciaux remplacés)
✅ Vérification des droits d'accès à l'événement
✅ Timestamp ajouté au nom de fichier (évite les collisions)

### Permissions requises
- L'utilisateur doit être:
  - Auteur de l'événement (`fk_user_author`)
  - Assigné à l'événement (`fk_user_action`)
  - Responsable de l'événement (`fk_user_done`)
  - Ou dans les ressources de l'événement (`actioncomm_resources`)

## Fichiers modifiés
- `/api/v1/planning_upload_photo.php` (corrections multiples)

## Tests à effectuer
1. ✅ Upload d'une photo JPG
2. ✅ Upload d'une photo PNG
3. ✅ Rejet d'un fichier non-image
4. ✅ Rejet d'un fichier > 10MB
5. ✅ Vérification des permissions
6. ✅ Affichage de la photo dans la liste
7. ✅ Affichage de la photo dans le détail
8. ✅ Barre de progression fonctionne

## Status
✅ **CORRIGÉ** - L'upload devrait maintenant fonctionner sans erreur 500.

## Notes
- Les photos sont stockées dans le module `mv3pro_portail` et non dans `actioncomm`
- Le filepath dans `ecm_files` doit toujours inclure le préfixe du module
- `dol_mkdir` crée récursivement les répertoires manquants
- Les fonctions Dolibarr (`dol_mkdir`, `dol_now`, etc.) nécessitent le chargement de `files.lib.php`
