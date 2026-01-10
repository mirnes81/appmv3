# 🚀 DÉPLOIEMENT MODE DEBUG AVANCÉ

## 📦 FICHIERS À DÉPLOYER (3 FICHIERS)

### 1. Backend API Debug
```
Source      : new_dolibarr/mv3pro_portail/api/v1/rapports_debug.php
Destination : custom/mv3pro_portail/api/v1/rapports_debug.php
Taille      : ~10 Ko
Permissions : 644
```

### 2. Frontend PWA (dossier complet)
```
Source      : new_dolibarr/mv3pro_portail/pwa_dist/*
Destination : custom/mv3pro_portail/pwa_dist/*
Taille      : ~300 Ko (11 fichiers)
Permissions : 644
```

### 3. Core Functions (fix double déclaration)
```
Source      : new_dolibarr/mv3pro_portail/core/functions.php
Destination : custom/mv3pro_portail/core/functions.php
Taille      : ~5 Ko
Permissions : 644
```

---

## ⚡ DÉPLOIEMENT ULTRA RAPIDE (5 MIN)

### Via FTP (FileZilla / WinSCP)

#### Étape 1 : Connexion
```
Serveur     : ftp.mv-3pro.ch
Utilisateur : ch314761
Mot de passe: [votre mot de passe]
Chemin      : /home/ch314761/web/crm.mv-3pro.ch/public_html/
```

#### Étape 2 : Backup (IMPORTANT)
```
Naviguer vers : custom/mv3pro_portail/

1. Renommer api/v1/rapports_debug.php → rapports_debug.php.OLD
2. Renommer core/functions.php → functions.php.OLD
3. Renommer pwa_dist → pwa_dist.OLD
```

#### Étape 3 : Upload
```
1. Uploader : new_dolibarr/mv3pro_portail/api/v1/rapports_debug.php
   Vers     : custom/mv3pro_portail/api/v1/rapports_debug.php

2. Uploader : new_dolibarr/mv3pro_portail/core/functions.php
   Vers     : custom/mv3pro_portail/core/functions.php

3. Uploader : new_dolibarr/mv3pro_portail/pwa_dist/* (tout le dossier)
   Vers     : custom/mv3pro_portail/pwa_dist/
```

#### Étape 4 : Vérification
```
1. Taille rapports_debug.php : ~10 Ko
2. Taille functions.php      : ~5 Ko
3. Dossier pwa_dist          : 11 fichiers
4. Permissions               : 644 (rw-r--r--)
```

---

## 🧪 TESTS APRÈS DÉPLOIEMENT

### Test 1 : API Debug (Backend)

```bash
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/rapports_debug.php \
  -H "Cookie: DOLSESSID_mv3pro2=VOTRE_SESSION"
```

**Réponse attendue** :
```json
{
  "success": true,
  "debug_info": { ... },
  "table_structure": {
    "table_name": "llx_mv3_rapport",
    "missing_columns": ["heure_debut", "heure_fin"],
    ...
  },
  "api_test": {
    "success": false,
    "error": "Unknown column 'heure_debut' in 'field list'"
  },
  "fix_sql": [
    "ALTER TABLE llx_mv3_rapport ADD COLUMN heure_debut TIME ..."
  ],
  "diagnostic_summary": {
    "table_exists": true,
    "all_columns_present": false,
    "api_query_works": false,
    "ready_for_production": false
  }
}
```

✅ Si vous voyez `table_structure`, `api_test`, `fix_sql` → **Backend OK**

---

### Test 2 : PWA (Frontend)

1. **Ouvrir** : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. **Connexion** : `fernando@mv-3pro.ch`
3. **Aller sur** : Rapports
4. **Cliquer** : Icône 🐛 (en haut à droite)
5. **Vider cache** : Ctrl+Shift+R

**Panneau debug attendu** :
```
🎯 Résumé Diagnostic
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ Table Existe │ Colonnes OK  │ Requête OK   │ Production   │
│      ✓       │      ✗       │      ✗       │      ✗       │
└──────────────┴──────────────┴──────────────┴──────────────┘

🗄️ Structure de la Table
Table: llx_mv3_rapport
Colonnes: 12/15

❌ Colonnes Manquantes (3)
• heure_debut
• heure_fin
• duree_heures

🧪 Test Requête API
✗ Requête échouée
Erreur: Unknown column 'heure_debut' in 'field list'

🔧 Corrections SQL Suggérées
ALTER TABLE llx_mv3_rapport ADD COLUMN heure_debut TIME ...
ALTER TABLE llx_mv3_rapport ADD COLUMN heure_fin TIME ...
ALTER TABLE llx_mv3_rapport ADD COLUMN duree_heures DECIMAL(10,2) ...
```

✅ Si vous voyez ces sections → **Frontend OK**

---

### Test 3 : Correction SQL (Fix)

1. **Copier** les commandes SQL du panneau debug
2. **Ouvrir** phpMyAdmin : `https://crm.mv-3pro.ch/phpmyadmin/`
3. **Sélectionner** la base : `dolibarr`
4. **Onglet** SQL
5. **Coller** les commandes :

```sql
ALTER TABLE llx_mv3_rapport
  ADD COLUMN heure_debut TIME DEFAULT NULL AFTER date_rapport;

ALTER TABLE llx_mv3_rapport
  ADD COLUMN heure_fin TIME DEFAULT NULL AFTER heure_debut;

ALTER TABLE llx_mv3_rapport
  ADD COLUMN duree_heures DECIMAL(10,2) DEFAULT 0 AFTER heure_fin;
```

6. **Cliquer** Exécuter
7. **Vérifier** : "3 lignes affectées"

---

### Test 4 : Validation Finale

1. **Recharger** la page PWA (F5)
2. **Cliquer** icône 🐛
3. **Vérifier** panneau debug :

```
🎯 Résumé Diagnostic
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ Table Existe │ Colonnes OK  │ Requête OK   │ Production   │
│      ✓       │      ✓       │      ✓       │      ✓       │
└──────────────┴──────────────┴──────────────┴──────────────┘

❌ Colonnes Manquantes (0)
(Aucune colonne manquante)

🧪 Test Requête API
✓ Requête réussie
Lignes retournées: 0 (ou plus si rapports existants)
```

✅ **Tout en vert** = Système prêt !

---

## ✅ CHECKLIST COMPLÈTE

### Backend
- [ ] `rapports_debug.php` uploadé (~10 Ko)
- [ ] `functions.php` uploadé (~5 Ko)
- [ ] Permissions 644 vérifiées
- [ ] Test cURL → 200 OK avec `table_structure`

### Frontend
- [ ] Dossier `pwa_dist/` uploadé (11 fichiers)
- [ ] Permissions 644 vérifiées
- [ ] Cache navigateur vidé (Ctrl+Shift+R)
- [ ] Panneau debug accessible (icône 🐛)

### Diagnostic
- [ ] Section "Résumé Diagnostic" visible
- [ ] Section "Structure Table" visible
- [ ] Section "Colonnes Manquantes" visible
- [ ] Section "Test API" visible
- [ ] Section "SQL Corrections" visible

### Correction
- [ ] SQL copié du panneau debug
- [ ] SQL exécuté dans phpMyAdmin
- [ ] Aucune erreur SQL
- [ ] Page rechargée (F5)
- [ ] Tout passe au ✓ vert

### Validation
- [ ] Panneau debug : 4 cartes vertes
- [ ] Aucune colonne manquante
- [ ] Test API : ✓ Requête réussie
- [ ] Liste rapports s'affiche
- [ ] Console F12 : aucune erreur

---

## 🚨 SI PROBLÈMES

### Erreur : "Cannot redeclare function"

**Cause** : `functions.php` pas uploadé

**Solution** :
```bash
# Uploader absolument
custom/mv3pro_portail/core/functions.php

# Checksum attendu
md5sum functions.php
# 094901ba0e0c75ea91aa3c401dd2092e
```

---

### Erreur : Panneau debug manque sections

**Cause** : Ancienne version `rapports_debug.php`

**Solution** :
```bash
# Vérifier taille fichier
ls -lh rapports_debug.php
# Attendu : ~10 Ko (298 lignes)

# Si différent : re-uploader
```

---

### Erreur : PWA affiche ancien design

**Cause** : Cache navigateur ou ServiceWorker

**Solution** :
```bash
# 1. Vider cache navigateur
Ctrl+Shift+R (plusieurs fois)

# 2. Désinstaller ServiceWorker
F12 → Application → Service Workers → Unregister

# 3. Vider stockage
F12 → Application → Storage → Clear site data

# 4. Recharger page
Ctrl+F5
```

---

### Erreur SQL : "Access denied"

**Cause** : Permissions insuffisantes

**Solution** :
```sql
-- Vérifier permissions
SHOW GRANTS FOR CURRENT_USER;

-- Si pas de ALTER : contacter hébergeur
-- Ou utiliser compte root/admin MySQL
```

---

## 💾 ROLLBACK (SI BESOIN)

Si le nouveau système ne fonctionne pas :

```bash
# Restaurer versions anciennes
mv rapports_debug.php.OLD rapports_debug.php
mv functions.php.OLD functions.php
mv pwa_dist.OLD pwa_dist

# Recharger page
Ctrl+F5
```

---

## 📊 COMPARAISON AVANT/APRÈS

### AVANT (ancien debug)
```
✗ Message erreur basique : "Aucun rapport"
✗ Pas de détails sur le problème
✗ Pas de suggestion de correction
✗ Pas d'informations sur la structure
✗ Pas de test API automatique
```

### APRÈS (nouveau debug)
```
✓ Diagnostic automatique complet
✓ Détection colonnes manquantes
✓ Erreur SQL précise affichée
✓ Génération SQL automatique
✓ Test API en temps réel
✓ 4 indicateurs visuels clairs
✓ Instructions étape par étape
✓ Interface moderne et intuitive
```

---

## 🎯 BÉNÉFICES

1. **Diagnostic automatique**
   - Plus besoin de deviner le problème
   - Détection en 1 clic

2. **Correction guidée**
   - SQL généré automatiquement
   - Copier/coller suffit

3. **Gain de temps**
   - 5 minutes vs 2 heures de debug
   - Plus d'allers-retours

4. **Prévention**
   - Détecte les problèmes avant production
   - Valide la structure complète

---

## 📝 RÉSUMÉ FINAL

**Déploiement** : 3 fichiers (5 minutes)
**Impact** : MAJEUR (diagnostic automatique)
**Difficulté** : FACILE (upload FTP standard)
**Compatibilité** : 100% (pas de breaking change)
**Version PWA** : 0.17.5

---

**Status** : ✅ PRÊT À DÉPLOYER IMMÉDIATEMENT
