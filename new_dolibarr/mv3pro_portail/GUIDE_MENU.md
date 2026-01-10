# 🔧 RÉSOUDRE LE PROBLÈME DU MENU MV-3 PRO

## ⚠️ PROBLÈME
- Le dashboard est vide avec des erreurs
- Le menu de gauche ne s'affiche pas (Planning, Rapports manquants)
- Cliquer sur "MV-3 PRO" ne fait rien

## ✅ SOLUTION EN 3 ÉTAPES

### 📝 ÉTAPE 1 : Exécuter le script de régénération

1. **Ouvrir ce lien dans votre navigateur :**
   ```
   https://crm.mv-3pro.ch/custom/mv3pro_portail/REGENERER_MENUS.php
   ```

2. **Vous devez être connecté en tant qu'administrateur**

3. Le script va automatiquement :
   - ✅ Supprimer les anciens menus
   - ✅ Créer les nouveaux menus
   - ✅ Vider le cache

### 🔄 ÉTAPE 2 : Rafraîchir le navigateur

Après l'exécution du script :

1. **Faire un rafraîchissement complet** :
   - Windows/Linux : `Ctrl + F5`
   - Mac : `Cmd + Shift + R`

2. **Ou vider le cache du navigateur** :
   - Chrome : `Ctrl + Shift + Suppr`
   - Firefox : `Ctrl + Shift + Suppr`

### 🎯 ÉTAPE 3 : Tester

1. **Cliquer sur "MV-3 PRO"** dans le menu du haut

2. **Vous devriez voir** :
   - ✅ Le dashboard avec des statistiques
   - ✅ Le menu de gauche avec :
     - 📊 **Dashboard**
     - 📅 **Planning**
     - 📄 **Rapports**

---

## 🔧 SI ÇA NE FONCTIONNE TOUJOURS PAS

### Solution alternative : Réactiver le module

1. Aller dans **Configuration → Modules/Applications**

2. Chercher **"MV-3 PRO Portail"**

3. Cliquer sur **Désactiver** (bouton rouge)

4. Attendre 2 secondes

5. Cliquer sur **Activer** (bouton vert)

6. Revenir sur `/custom/mv3pro_portail/REGENERER_MENUS.php`

7. Réexécuter le script

8. Rafraîchir le navigateur (Ctrl + F5)

---

## 📋 CE QUE VOUS DEVRIEZ VOIR

### Menu du haut
```
Accueil | Tiers | ... | MV-3 PRO | ...
                        ^^^^^^^^
                     (cliquer ici)
```

### Menu de gauche (après avoir cliqué sur MV-3 PRO)
```
┌─────────────────┐
│ 📊 Dashboard    │ ← Vue d'ensemble
├─────────────────┤
│ 📅 Planning     │ ← Calendrier Dolibarr
├─────────────────┤
│ 📄 Rapports     │ ← Liste des rapports
└─────────────────┘
```

### Dashboard principal
```
┌─────────────────────────────────────────┐
│ Dashboard MV-3 PRO                      │
├─────────────────────────────────────────┤
│                                         │
│ 📅 Planning                             │
│ ┌────────┬────────┬────────┬────────┐  │
│ │ Auj.   │ Semaine│ À venir│ Total  │  │
│ │   5    │   12   │   45   │   150  │  │
│ └────────┴────────┴────────┴────────┘  │
│                                         │
│ 📄 Rapports Chantier                    │
│ ┌────────┬────────┬────────┬────────┐  │
│ │Brouill.│ Soumis │ Validés│ Total  │  │
│ │   3    │   5    │   25   │   33   │  │
│ └────────┴────────┴────────┴────────┘  │
│                                         │
│ 🎯 Actions rapides                      │
│ [➕ Nouvel événement] [📅 Planning]     │
│ [📄 Rapports] [📱 Ouvrir PWA]          │
│                                         │
│ 📋 Planning des 7 prochains jours       │
│ ... (liste des événements)              │
│                                         │
└─────────────────────────────────────────┘
```

---

## 📞 AIDE SUPPLÉMENTAIRE

Si après toutes ces étapes, le menu ne s'affiche toujours pas :

1. **Vérifier les droits utilisateur** :
   - Aller dans **Configuration → Utilisateurs**
   - Cliquer sur votre utilisateur
   - Onglet **Permissions**
   - Vérifier que **"MV-3 PRO Portail"** est coché

2. **Vérifier l'activation du module** :
   - Aller dans **Configuration → Modules**
   - Chercher **"MV-3 PRO"**
   - Doit être **ACTIVÉ** (case verte)

3. **Vérifier dans la base de données** :
   ```sql
   SELECT * FROM llx_menu WHERE module = 'mv3pro_portail';
   ```
   Devrait retourner au moins 4 lignes

---

## 🎉 C'EST BON ?

Une fois que tout fonctionne, vous pouvez supprimer ce fichier et `REGENERER_MENUS.php`
