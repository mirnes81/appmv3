# 🚀 Installation Rapide - Module Rapports PRO

## Installation en 3 Minutes Chrono

### ✅ Étape 1: Migration Base de Données (30 secondes)

Connectez-vous à votre base de données MySQL et exécutez:

```bash
mysql -u votre_user -p votre_base < sql/llx_mv3_rapport_add_features.sql
```

**OU** depuis phpMyAdmin:
1. Ouvrez phpMyAdmin
2. Sélectionnez votre base Dolibarr
3. Onglet "SQL"
4. Copiez-collez le contenu de `sql/llx_mv3_rapport_add_features.sql`
5. Cliquez "Exécuter"

---

### ✅ Étape 2: Vérification des Fichiers (1 minute)

Tous les fichiers sont déjà en place. Vérifiez juste:

```bash
cd /chemin/vers/dolibarr/custom/mv3pro_portail/mobile_app/rapports/

# Vérifier que ces fichiers existent:
ls -la new_pro.php                    # Page principale PRO
ls -la service-worker-rapports.js     # Service worker
ls -la js/*.js                        # Tous les modules JS
ls -la api/*.php                      # APIs
```

**Structure attendue:**
```
rapports/
├── new_pro.php                       ✓
├── service-worker-rapports.js        ✓
├── js/
│   ├── offline-manager.js           ✓
│   ├── gps-manager.js               ✓
│   ├── voice-recognition.js         ✓
│   ├── templates-manager.js         ✓
│   ├── timer-manager.js             ✓
│   ├── draft-manager.js             ✓
│   ├── camera-manager.js            ✓
│   ├── validation-manager.js        ✓
│   ├── stats-manager.js             ✓
│   ├── weather-manager.js           ✓
│   └── qrcode-manager.js            ✓
└── api/
    ├── stats.php                    ✓
    └── copy-rapport.php             ✓
```

---

### ✅ Étape 3: Test (1 minute)

1. **Ouvrez votre navigateur** (Chrome recommandé)

2. **Allez sur:**
   ```
   https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/rapports/new_pro.php
   ```

3. **Vérifications rapides:**
   - [ ] La page se charge
   - [ ] Le widget des stats s'affiche en haut
   - [ ] Le widget météo apparaît
   - [ ] Les boutons "Templates", "Copier", "Scanner QR", "GPS" sont visibles
   - [ ] Le timer est présent

4. **Tests des fonctionnalités:**

   **Test GPS:**
   - Cliquez sur "📍 GPS"
   - Autorisez la géolocalisation
   - Vous devez voir vos coordonnées

   **Test Templates:**
   - Cliquez sur "📋 Templates"
   - Choisissez "🛁 Pose standard SDB"
   - Les champs se remplissent automatiquement

   **Test Timer:**
   - Cliquez "▶️ Démarrer"
   - Le timer doit s'incrémenter

   **Test Photos:**
   - Cliquez "📷 Avant"
   - Prenez une photo
   - Elle s'affiche dans la grille

---

## 🎉 C'est Tout !

**Votre module est opérationnel avec:**
- ✅ Mode hors-ligne (PWA)
- ✅ GPS
- ✅ Reconnaissance vocale
- ✅ Templates rapides
- ✅ Timer avec pauses
- ✅ Auto-sauvegarde
- ✅ Photos watermark
- ✅ Validation intelligente
- ✅ Stats temps réel
- ✅ Météo
- ✅ QR Code
- ✅ Copie rapport

---

## 🔧 Configuration Optionnelle

### HTTPS Requis (Important!)

Certaines fonctionnalités nécessitent HTTPS:
- GPS
- Caméra
- Service Worker (mode hors-ligne)

**Si vous n'avez pas HTTPS:**
1. Installez Let's Encrypt (gratuit)
2. Ou utilisez un reverse proxy (nginx)

### Permissions Utilisateur

Vérifiez que vos utilisateurs mobiles ont accès au module:

1. Dans Dolibarr: **Accueil > Configuration > Modules**
2. Recherchez "MV3 PRO Portail"
3. Vérifiez qu'il est activé
4. **Utilisateurs > [Votre utilisateur] > Droits**
5. Cochez les permissions MV3 PRO

---

## 📱 Premier Rapport

### Guide Pas à Pas

1. **Scanner le QR** (ou sélectionner projet)
2. **Cliquer "▶️ Démarrer"** le timer
3. **Sélectionner zones** de travail
4. **Travailler** normalement sur le chantier
5. **Prendre photos** avec watermark
6. **Cliquer "⏹️ Arrêter"** le timer en fin
7. **Dicter** les travaux (optionnel)
8. **Valider** et envoyer

**Temps total:** Moins de 2 minutes ! 🚀

---

## 🐛 Problèmes Courants

### Le GPS ne marche pas
**Solution:** Vérifiez que vous êtes en HTTPS

### La reconnaissance vocale ne fonctionne pas
**Solution:** Utilisez Chrome ou Safari (Firefox non supporté)

### Les stats n'apparaissent pas
**Solution:**
1. Vérifiez que `api/stats.php` existe
2. Regardez la console navigateur (F12)
3. Vérifiez les permissions fichiers

### Mode hors-ligne ne s'active pas
**Solution:**
1. Vérifiez que vous êtes en HTTPS
2. Videz le cache navigateur
3. Rechargez avec Ctrl+Shift+R

---

## 📞 Support

**Email:** support@mv3pro.ch
**Documentation complète:** Voir `README_PRO.md`

---

## 🎯 Prochaines Étapes

1. ✅ **Formation équipe** (30 minutes)
   - Montrer les fonctionnalités
   - Faire un rapport test ensemble

2. ✅ **Générer QR codes** pour les projets
   - Imprimez-les
   - Collez-les sur les chantiers

3. ✅ **Personnaliser templates**
   - Adaptez aux besoins de votre entreprise
   - Ajoutez vos formats standards

4. ✅ **Surveiller les stats**
   - Regardez l'adoption
   - Collectez les retours terrain

---

**Bon chantier avec votre nouveau module PRO ! 🚀**
