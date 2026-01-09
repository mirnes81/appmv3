# Système de Diagnostic Automatique - MV3 PRO

## En bref

J'ai créé un système qui teste **automatiquement** tous les endpoints API et toutes les pages PWA de votre application.

Au lieu de tester manuellement chaque endpoint un par un, vous cliquez sur un bouton et obtenez un rapport complet en quelques secondes.

## Ce que ça fait

- ✅ Teste 15+ endpoints API automatiquement
- ✅ Affiche les erreurs avec détails précis (fichier, ligne, message)
- ✅ Mesure les temps de réponse
- ✅ Vérifie la configuration système
- ✅ Export JSON pour analyse

## Installation (3 minutes)

### 1. Uploadez 2 fichiers sur le serveur

```bash
# Fichier 1 : debug.php
scp new_dolibarr/mv3pro_portail/api/v1/debug.php \
  user@serveur:/path/to/custom/mv3pro_portail/api/v1/

# Fichier 2 : PWA complète
rsync -av new_dolibarr/mv3pro_portail/pwa_dist/ \
  user@serveur:/path/to/custom/mv3pro_portail/pwa_dist/
```

### 2. Activez le mode dev (sur le serveur)

```bash
ssh user@serveur
touch /tmp/mv3pro_debug.flag
```

### 3. Lancez le diagnostic

**Option A : Via l'interface web (le plus simple)**

1. Ouvrez : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Connectez-vous
3. Allez sur `/#/debug`
4. Cliquez sur **"Diagnostic Complet"**
5. Attendez 5 secondes
6. Cliquez sur **"Exporter JSON"**

**Option B : Via ligne de commande**

```bash
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php | jq . > rapport.json
```

## Ce que vous obtenez

Un rapport détaillé comme celui-ci :

```
📊 Statistiques
  Total : 15 tests
  OK    : 12 ✓
  Erreur: 3  ✗

📋 Résultats détaillés

✓ Me (infos utilisateur)          200  25ms  OK
✓ Planning - Liste                200  45ms  OK
✗ Rapports - Liste                500  50ms  ERROR
  → Erreur : Undefined variable: conf
  → Fichier : rapports.php
  → Ligne : 42

✓ Matériel - Liste                200  30ms  OK
...
```

## Ensuite ?

Une fois le rapport généré, **envoyez-moi le fichier JSON**.

Je vais :
1. Identifier tous les problèmes
2. Prioriser les corrections
3. Corriger tout en une seule fois
4. Vous fournir les fichiers corrigés

## Désactivation

Après utilisation, désactivez le mode dev :

```bash
ssh user@serveur
rm /tmp/mv3pro_debug.flag
```

## Besoin d'aide ?

Consultez :
- `INSTRUCTIONS_DIAGNOSTIC.md` - Instructions détaillées
- `GUIDE_DIAGNOSTIC_SYSTEME.md` - Guide complet
- `COMMANDES_UPLOAD.sh` - Script automatique

---

**Temps d'installation :** 3 minutes
**Temps d'exécution :** 5 secondes
**Avantage :** Vision complète au lieu de tester manuellement pendant des heures
