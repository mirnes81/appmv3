# ⚡ DÉMARRAGE RAPIDE - API Object

**Version:** 2.0
**Date:** 10 janvier 2026

---

## 🚀 EN 3 ÉTAPES

### **Étape 1 : Forcer le rechargement** 🔄

**Sur téléphone:**
```
1. Ouvrir ce lien dans le navigateur:
   https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/FORCE_RELOAD.html

2. Cliquer sur "🚀 Forcer la mise à jour"

3. Attendre 3 secondes → Rechargement automatique
```

**Sur ordinateur:**
```
1. Ouvrir la PWA
2. Appuyer: Ctrl + Shift + R (Windows) ou Cmd + Shift + R (Mac)
```

---

### **Étape 2 : Tester l'upload** 📸

```
1. Se connecter à la PWA

2. Planning → Cliquer sur un RDV

3. Onglet "Photos" → "📷 Ajouter une photo"

4. Choisir une GROSSE photo (10-20 MB)

5. Observer:
   ✅ Compression automatique
   ✅ Barre de progression
   ✅ Photo apparaît immédiatement

6. Tester:
   - Clic sur photo → Preview plein écran
   - Clic sur × → Suppression
```

---

### **Étape 3 : Vérifier dans Dolibarr** ✅

```
1. Ouvrir Dolibarr Desktop (admin)

2. Agenda → Trouver le RDV

3. Onglet "Documents"

4. Vérifier: Toutes les photos uploadées apparaissent ✅
```

---

## 📱 UTILISATION QUOTIDIENNE

### **Workflow chantier typique:**

```
1. Matin: Ouvrir PWA → Planning
   → Voir mes RDV du jour

2. Sur chantier: Cliquer sur RDV
   → Onglet "Détails": Voir lieu, note, infos
   → Onglet "Photos": Prendre photos chantier
   → Upload automatique avec compression

3. Fin journée: Vérifier que tout est synchro
   → Photos visibles dans Dolibarr Desktop ✅
```

---

## 🎯 CE QUI CHANGE

### **Avant:**
```
❌ Upload bloqué si photo > 8 MB
❌ Dernière photo ne s'affiche pas
❌ Navigation confuse
❌ Fichiers parfois perdus
```

### **Maintenant:**
```
✅ Upload jusqu'à 50 MB (compression auto)
✅ Photos triées (dernière en premier)
✅ Onglets clairs (Détails/Photos/Fichiers)
✅ Fichiers indexés dans ECM Dolibarr
✅ ExtraFields affichés automatiquement
```

---

## 🆘 PROBLÈMES FRÉQUENTS

### **"J'ai encore l'ancienne version"**
```
→ Forcer rechargement: FORCE_RELOAD.html
→ Vider cache: Ctrl + Shift + Delete
→ Sur mobile: Désinstaller + réinstaller PWA
```

### **"Erreur 401"**
```
→ Token expiré: Se reconnecter
→ Forcer rechargement
```

### **"Photo ne s'affiche pas"**
```
→ Attendre 2-3 secondes (chargement)
→ Vérifier connexion Internet
→ Rafraîchir la page
→ Forcer rechargement si toujours KO
```

### **"Upload échoue"**
```
→ Vérifier taille fichier (max 10 MB après compression)
→ Vérifier connexion Internet
→ Essayer autre photo
→ Console (F12): Regarder erreur
```

---

## 💡 ASTUCES

### **Console de debug (ordinateur):**
```
1. Appuyer F12
2. Onglet "Console"
3. Voir les logs d'upload/compression
```

**Logs typiques:**
```
[Upload] Taille originale: 15.23 MB
[Upload] Mode compression MAXIMALE (photo > 10MB)
[Compression] 15603 KB → 924 KB (94% de réduction)
[Upload] Taille finale: 0.90 MB
✅ Upload réussi!
```

### **Mode debug (activer):**
```javascript
// Dans console navigateur (F12)
localStorage.setItem('mv3pro_debug', 'true');
// Recharger la page
```

### **Vérifier token (si problème connexion):**
```javascript
// Dans console navigateur
console.log(localStorage.getItem('mv3pro_token'));
```

---

## 📊 COMPRESSION AUTOMATIQUE

| Taille photo | Compression | Exemple |
|--------------|-------------|---------|
| **> 10 MB** | **Maximale** (70%) | 15 MB → 900 KB |
| **> 5 MB** | **Forte** (75%) | 8 MB → 1.2 MB |
| **Téléphone** | **Mobile** (80%) | 5 MB → 850 KB |
| **> 300 KB** | **Normale** (85%) | 2 MB → 650 KB |
| **< 300 KB** | **Aucune** | Conservée |

**👉 Tout est automatique, vous ne faites rien !**

---

## 🎨 NOUVELLE UI

```
┌──────────────────────────────────────┐
│ ← Retour                              │
│ Installation chez M. Dupont           │
│ Réf: RDV001                           │
├──────────────────────────────────────┤
│ [Détails] [Photos (3)] [Fichiers (2)]│
├──────────────────────────────────────┤
│                                       │
│  📷 Ajouter une photo                 │
│                                       │
│  ┌─────────┐ ┌─────────┐             │
│  │ Photo 1 │ │ Photo 2 │             │
│  │  850 KB │ │  920 KB │             │
│  └─────────┘ └─────────┘             │
│                                       │
└──────────────────────────────────────┘
```

---

## 📞 BESOIN D'AIDE ?

### **Vérifications rapides:**

1. **PWA à jour ?**
   ```
   Console (F12) → Network → Voir "index-uPz3gyG1.js"
   Si autre hash → Pas à jour → Forcer rechargement
   ```

2. **Token valide ?**
   ```
   localStorage.getItem('mv3pro_token')
   Si null → Se reconnecter
   ```

3. **Fichiers dans base ?**
   ```sql
   SELECT * FROM llx_ecm_files
   WHERE src_object_type = 'actioncomm'
   AND src_object_id = 74049;
   ```

---

## ✅ CHECKLIST TEST

- [ ] FORCE_RELOAD.html ouvert
- [ ] PWA rechargée
- [ ] Connexion OK
- [ ] Planning accessible
- [ ] RDV cliquable
- [ ] Onglets visibles (Détails/Photos/Fichiers)
- [ ] Upload photo 15 MB fonctionne
- [ ] Compression visible dans console
- [ ] Photo apparaît dans liste
- [ ] Preview plein écran fonctionne
- [ ] Suppression fonctionne
- [ ] Fichier visible dans Dolibarr Desktop

**Si tout est ✅ → Vous êtes prêt ! 🎉**

---

## 🚀 COMMENCER MAINTENANT

**1. Ouvrir ce lien:**
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/FORCE_RELOAD.html
```

**2. Cliquer "Forcer la mise à jour"**

**3. Se connecter à la PWA**

**4. Planning → RDV → Uploader photo**

**5. Profiter ! 🎉**

---

**Build:** `index-uPz3gyG1.js`
**Hash:** `uPz3gyG1` 🆕
**Date:** 10 janvier 2026

**💪 VOUS AVEZ CE QU'IL FAUT !**
