# 📸 Tests Photos et Documents - Planning

## ✅ Corrections appliquées

### 1. Erreur SQL "note_private" - **RÉSOLU** ✅
- Fichier: `api/v1/planning_view.php`
- Problème: Colonne `note_private` manquante dans SELECT
- Solution: Ajout de `note_private` à la requête SQL

### 2. Images avec authentification - **IMPLÉMENTÉ** ✅
- Fichier: `pwa/src/components/AuthImage.tsx` (NOUVEAU)
- Fonctionne: Charge les images avec le token Bearer
- Méthode: Conversion en Blob URL pour contourner les limitations du navigateur

### 3. Badges de comptage - **DÉJÀ PRÉSENT** ✅
- Les badges affichent le nombre de photos et documents dans les onglets
- Exemple: "📸 Photos (3)" et "📎 Fichiers (5)"

### 4. Double `/api/v1/` dans URLs - **CORRIGÉ** ✅
- Fichiers corrigés:
  - `pwa/src/pages/Regie.tsx`
  - `pwa/src/pages/RegieNew.tsx`
  - `pwa/src/pages/RegieDetail.tsx`

---

## 🧪 Comment tester

### Étape 1: Vider le cache
**URL:** https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/FORCE_RELOAD.html

Cliquez sur "Vider le cache et recharger"

### Étape 2: Se connecter
1. Allez sur: https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
2. Connectez-vous avec vos identifiants

### Étape 3: Ouvrir la console
1. Sur mobile: installez "Eruda" pour avoir une console
2. Sur desktop: F12 → Console

### Étape 4: Tester un événement du planning
1. Allez dans **Planning**
2. Cliquez sur un événement (ex: #74049)
3. Observez les logs dans la console

### Logs attendus dans la console

#### ✅ Si tout fonctionne bien:
```
[PlanningDetail] Loading event ID: 74049
[PlanningDetail] API URL: /planning_view.php?id=74049
[PlanningDetail] Event data received: {...}
[AuthImage] Chargement: https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=photo.jpg
[AuthImage] Token présent: eyJ0eXAiOiJKV1QiLCJ...
[AuthImage] Réponse HTTP: 200 OK
[AuthImage] Blob reçu: 245632 bytes, type: image/jpeg
[AuthImage] Image chargée avec succès
```

#### ❌ Si ça ne fonctionne pas:
```
[AuthImage] Erreur réponse: {"success":false,"error":"Accès refusé"}
[AuthImage] Réponse HTTP: 403 Forbidden
```

ou

```
[AuthImage] Erreur réponse: {"success":false,"error":"Fichier non trouvé"}
[AuthImage] Réponse HTTP: 404 Not Found
```

---

## 🔍 Diagnostics

### Problème: Erreur 403 (Accès refusé)
**Cause possible:**
- L'utilisateur n'est pas assigné à l'événement
- Le token est invalide

**Solution:**
1. Vérifiez que l'utilisateur connecté est bien assigné à l'événement
2. Vérifiez dans la BDD: `SELECT fk_user_action FROM llx_actioncomm WHERE id = 74049`

### Problème: Erreur 404 (Fichier non trouvé)
**Cause possible:**
- Les fichiers ne sont pas dans le bon dossier
- Le dossier n'existe pas

**Solution:**
1. Vérifiez le chemin sur le serveur:
   ```bash
   ls -la /home/xxxxx/documents/actioncomm/74049/
   ```
2. Uploadez un fichier de test via Dolibarr

### Problème: Erreur 401 (Non authentifié)
**Cause possible:**
- Token expiré
- Token manquant

**Solution:**
1. Déconnectez-vous et reconnectez-vous
2. Vérifiez dans localStorage: `mv3pro_token`

---

## 📋 Structure actuelle

### API Endpoints
- `GET /api/v1/planning_view.php?id=X` - Récupère les détails + liste des fichiers
- `GET /api/v1/planning_file.php?id=X&file=Y` - Stream un fichier sécurisé

### Authentification
L'API `planning_file.php` accepte:
- **Header:** `Authorization: Bearer {token}`
- **Header:** `X-Auth-Token: {token}`

### Structure des données retournées

```json
{
  "success": true,
  "id": 74049,
  "titre": "Finier Appartements Ingold Sol Complet",
  "fichiers": [
    {
      "name": "photo1.jpg",
      "size": 245632,
      "size_human": "240 KB",
      "mime": "image/jpeg",
      "is_image": true,
      "url": "/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=photo1.jpg"
    },
    {
      "name": "document.pdf",
      "size": 1024000,
      "size_human": "1 MB",
      "mime": "application/pdf",
      "is_image": false,
      "url": "/custom/mv3pro_portail/api/v1/planning_file.php?id=74049&file=document.pdf"
    }
  ]
}
```

---

## 📦 Fichiers modifiés

| Fichier | Description |
|---------|-------------|
| `api/v1/planning_view.php` | Fix SQL note_private |
| `pwa/src/components/AuthImage.tsx` | **NOUVEAU** - Image avec auth |
| `pwa/src/pages/PlanningDetail.tsx` | Utilise AuthImage + logs |
| `pwa/src/pages/Regie.tsx` | Fix URL double /api/v1/ |
| `pwa/src/pages/RegieNew.tsx` | Fix URL double /api/v1/ |
| `pwa/src/pages/RegieDetail.tsx` | Fix URL double /api/v1/ |

**Build:** `index-7kpJe2fd.js` (275 KB)
**Version:** 1768035868

---

## 🎯 Prochaines étapes

1. **Vider le cache** avec FORCE_RELOAD.html
2. **Se reconnecter**
3. **Ouvrir un événement du planning**
4. **Cliquer sur l'onglet "Photos"**
5. **Copier les logs de la console** et les partager

Si aucun log `[AuthImage]` n'apparaît, cela signifie que:
- L'événement n'a pas de fichiers
- L'onglet Photos n'est pas cliqué
- Il y a une erreur JavaScript (vérifier les erreurs rouges dans la console)

---

## 💡 Pour ajouter des fichiers de test

Via Dolibarr:
1. Aller dans **Agenda → Événement #74049**
2. Onglet **"Documents"**
3. Cliquer **"Ajouter un fichier"**
4. Uploader une photo

Via terminal (si accès SSH):
```bash
mkdir -p /chemin/vers/dolibarr/documents/actioncomm/74049
cp photo.jpg /chemin/vers/dolibarr/documents/actioncomm/74049/
chmod 644 /chemin/vers/dolibarr/documents/actioncomm/74049/photo.jpg
```
