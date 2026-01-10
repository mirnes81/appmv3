# 🎯 CORRECTION FINALE - Photos Planning

## ❌ Problème identifié

**Dans Dolibarr:** L'événement #74049 a **1 image** visible dans l'onglet "Images (1)"
**Dans la PWA:** `Nombre de fichiers: 0` - Aucune photo affichée

### Cause racine
L'API cherchait les fichiers directement dans le dossier filesystem:
```
/documents/actioncomm/74049/
```

Mais **Dolibarr stocke les fichiers dans la table ECM** (`llx_ecm_files`), pas directement dans le dossier!

---

## ✅ Solution appliquée

### 1. API `planning_view.php` - Récupération des fichiers

**Ancienne méthode:** `scandir()` sur le dossier filesystem ❌

**Nouvelle méthode:** Requête SQL sur `llx_ecm_files` ✅

```sql
SELECT
    ecm.rowid,
    ecm.label as filename,
    ecm.filename as stored_filename,
    ecm.filepath,
    ecm.date_c as date_creation,
    ecm.filesize
FROM llx_ecm_files as ecm
WHERE ecm.src_object_type = 'actioncomm'
AND ecm.src_object_id = 74049
ORDER BY ecm.position ASC, ecm.date_c DESC
```

**Fallback:** Si aucun fichier via ECM, on scanne quand même le filesystem (compatibilité)

### 2. API `planning_file.php` - Stream des fichiers

**Ancienne méthode:** Chemin direct `DOL_DATA_ROOT/actioncomm/{id}/{file}` ❌

**Nouvelle méthode:**
1. Cherche d'abord dans ECM pour obtenir le vrai chemin (`filepath` + `stored_filename`)
2. Si non trouvé, fallback sur filesystem direct
3. Stream le fichier depuis le bon emplacement

---

## 🧪 TESTEZ L'API DIRECTEMENT

**URL:**
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php?id=74049
```

**Cherchez dans la réponse JSON:**
```json
{
  "fichiers": [ ... ]
}
```

**Si c'est vide `[]`**, vérifiez les logs backend:
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php

---

## 📦 Fichiers modifiés

- `api/v1/planning_view.php` - Utilise ECM au lieu de scandir
- `api/v1/planning_file.php` - Cherche dans ECM puis fallback filesystem
