# 🔍 DEBUG - Pourquoi les photos ne s'affichent pas ?

## ✅ Modifications appliquées

### 1. Logs ajoutés dans la PWA
**Fichier:** `pwa/src/pages/PlanningDetail.tsx`

Logs de débogage pour voir ce que l'API retourne:
```
[PlanningDetail] ===== FICHIERS DEBUG =====
[PlanningDetail] Fichiers array: [...]
[PlanningDetail] Nombre de fichiers: X
[PlanningDetail] Photos: X [...]
[PlanningDetail] Documents: X [...]
[PlanningDetail] ===========================
```

### 2. Logs ajoutés dans l'API Backend
**Fichier:** `api/v1/planning_view.php`

Logs détaillés du scan de fichiers:
```
===== SCAN FICHIERS PLANNING #74049 =====
DOL_DATA_ROOT: /home/xxxxx/documents
Upload dir: /home/xxxxx/documents/actioncomm/74049
Dossier existe: OUI/NON
Fichiers bruts trouvés par scandir: X fichiers
  - Analyse fichier: photo.jpg
    => AJOUTÉ: photo.jpg (image/jpeg, 240 KB, is_image=yes)
Total fichiers valides ajoutés: X
===== FIN SCAN FICHIERS =====
```

---

## 🧪 TESTS À FAIRE MAINTENANT

### Étape 1️⃣: Vider le cache
**URL:** https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/FORCE_RELOAD.html

Cliquez sur "Vider le cache et recharger"

### Étape 2️⃣: Se reconnecter
Connectez-vous normalement

### Étape 3️⃣: Activer les logs backend
**Important:** Ajoutez ce header dans vos requêtes pour activer les logs serveur:

Dans la console du navigateur, tapez:
```javascript
localStorage.setItem('debug_api', 'true');
```

### Étape 4️⃣: Ouvrir l'événement #74049
1. Aller dans **Planning**
2. Cliquer sur l'événement **"Finier Appartements Ingold Sol Complet"** (#74049)
3. Cliquer sur l'onglet **📸 Photos**

### Étape 5️⃣: Copier TOUS les logs
**IMPORTANT:** Je dois voir les logs suivants:

#### A. Logs Frontend (Console navigateur)
```
[PlanningDetail] Loading event ID: 74049
[PlanningDetail] API URL: /planning_view.php?id=74049
[PlanningDetail] Event data received: {...}
[PlanningDetail] ===== FICHIERS DEBUG =====
[PlanningDetail] Fichiers array: [...]
[PlanningDetail] Nombre de fichiers: X
```

#### B. Logs Backend (Fichier serveur)
Pour voir les logs backend, vous devez:

**Option 1: Via API debug.php**
Allez sur: https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php

Cherchez les logs qui contiennent:
- `SCAN FICHIERS PLANNING`
- `DOL_DATA_ROOT`
- `Upload dir`
- `Dossier existe`

**Option 2: Via SSH (si vous avez accès)**
```bash
tail -100 /tmp/mv3pro_debug.log | grep -A 20 "SCAN FICHIERS"
```

---

## 📋 Ce que je dois savoir

Copiez-moi les réponses à ces questions:

### ✅ Questions Frontend (Console)
1. Quel est le nombre de fichiers retourné? `Nombre de fichiers: ???`
2. Est-ce que `data.fichiers` est `[]` (vide) ou `undefined` ou contient des éléments?
3. Y a-t-il des erreurs rouges dans la console?

### ✅ Questions Backend (Logs PHP)
4. Quel est le chemin `DOL_DATA_ROOT`?
5. Quel est le chemin `Upload dir`?
6. Est-ce que le dossier existe? `Dossier existe: OUI/NON`
7. Combien de fichiers sont trouvés par `scandir`?
8. Est-ce que des fichiers sont ignorés? Pourquoi?

---

## 🎯 Scénarios possibles

### Scénario A: Le dossier n'existe pas
```
Dossier existe: NON
⚠️ DOSSIER INEXISTANT: /home/xxxxx/documents/actioncomm/74049
```

**Solution:** Uploadez un fichier de test via Dolibarr:
1. Aller dans Dolibarr → Agenda → Événement #74049
2. Onglet "Documents"
3. Ajouter un fichier

### Scénario B: Le dossier existe mais est vide
```
Dossier existe: OUI
Fichiers bruts trouvés par scandir: 2 fichiers [".", ".."]
Total fichiers valides ajoutés: 0
```

**Solution:** Même chose, uploadez un fichier de test.

### Scénario C: Des fichiers existent mais sont ignorés
```
Dossier existe: OUI
Fichiers bruts trouvés par scandir: 5 fichiers
  - Analyse fichier: photo.jpg
    => Ignoré (répertoire)  OU  => Ignoré (n'existe pas!)
```

**Solution:** Vérifier les permissions du dossier.

### Scénario D: Les fichiers sont trouvés côté backend mais pas côté frontend
```
Backend: Total fichiers valides ajoutés: 3
Frontend: Nombre de fichiers: 0
```

**Solution:** Problème dans la sérialisation JSON ou dans l'API.

---

## 📦 Nouvelle version déployée

| Info | Valeur |
|------|--------|
| Build JS | `index-BJ474G2g.js` (275.64 KB) |
| Version | 1768036383 |
| Date | 2026-01-09 18:26:23 |

**Fichiers modifiés:**
- `api/v1/planning_view.php` - Logs détaillés scan fichiers
- `pwa/src/pages/PlanningDetail.tsx` - Logs debug frontend

---

## 🚨 IMPORTANT

**Pour que je puisse vous aider, je DOIS voir:**

1. ✅ Les logs `[PlanningDetail] ===== FICHIERS DEBUG =====` dans la console
2. ✅ Les logs `===== SCAN FICHIERS PLANNING #74049 =====` dans l'API
3. ✅ La réponse complète à mes 8 questions ci-dessus

**Comment récupérer les logs backend:**
- Via web: https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php
- Via SSH: `tail -100 /tmp/mv3pro_debug.log`

---

## 💡 Test rapide: Vérifier si des fichiers existent

Via Dolibarr:
1. Menu → Agenda → Événements
2. Cherchez l'événement #74049
3. Ouvrez-le
4. Cliquez sur l'onglet **"Documents"**
5. Est-ce qu'il y a des fichiers listés? Prenez un screenshot!

Si aucun fichier n'apparaît dans Dolibarr, c'est normal que la PWA n'affiche rien!

---

**Faites tous ces tests et copiez-moi TOUS les logs. Sans les logs, je ne peux pas vous aider! 🙏**
