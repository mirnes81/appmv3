# Instructions : Utiliser le Système de Diagnostic

## Ce qui a été créé

Un système de diagnostic automatique complet pour tester toute l'application.

## Fichiers à uploader sur le serveur

1. **`/new_dolibarr/mv3pro_portail/api/v1/debug.php`**
   - Endpoint backend de diagnostic
   - À placer dans : `/custom/mv3pro_portail/api/v1/debug.php`

2. **`/new_dolibarr/mv3pro_portail/pwa_dist/`** (dossier complet)
   - PWA compilée avec la nouvelle page debug
   - À placer dans : `/custom/mv3pro_portail/pwa_dist/`

## Étapes d'installation

### 1. Uploader les fichiers

```bash
# Sur votre machine locale
scp new_dolibarr/mv3pro_portail/api/v1/debug.php \
  user@serveur:/path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/

# Uploader la PWA complète
rsync -av new_dolibarr/mv3pro_portail/pwa_dist/ \
  user@serveur:/path/to/dolibarr/htdocs/custom/mv3pro_portail/pwa_dist/
```

### 2. Activer le mode développement (sur le serveur)

```bash
# Connexion SSH au serveur
ssh user@serveur

# Activer le mode dev (permet l'accès à debug.php sans admin)
touch /tmp/mv3pro_debug.flag
```

### 3. Exécuter le diagnostic

#### Méthode A : Via la PWA (recommandé)

1. Ouvrez votre navigateur
2. Allez sur : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
3. Connectez-vous avec vos credentials
4. Allez sur : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug`
5. Cliquez sur **"Diagnostic Complet"**
6. Attendez les résultats (quelques secondes)
7. Cliquez sur **"Exporter JSON"** pour sauvegarder le rapport

#### Méthode B : Via curl (ligne de commande)

```bash
# Si mode dev activé (pas besoin de credentials)
curl -s https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php | jq . > diagnostic.json

# Voir un résumé
curl -s https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php | jq '.stats'
```

## Que fait le diagnostic ?

Le système teste automatiquement **tous** les endpoints API :

- ✅ Me (infos utilisateur)
- ✅ Planning (liste et détail)
- ✅ Rapports (liste et détail)
- ✅ Matériel (liste et détail)
- ✅ Notifications (liste et compteur)
- ✅ Régie (liste et détail)
- ✅ Sens de Pose (liste et détail)
- ✅ Frais (liste)

Pour chaque endpoint, vous obtenez :

- **Code HTTP** : 200 = OK, 500 = erreur serveur, 401 = auth échouée
- **Temps de réponse** : en millisecondes
- **Statut** : OK (vert) / WARNING (orange) / ERROR (rouge)
- **Erreur détaillée** : Message exact + fichier + ligne PHP
- **Erreur SQL** : Requête SQL échouée si applicable
- **Preview** : Aperçu de la réponse JSON

## Interprétation des résultats

### Statut : OK (vert)
L'endpoint fonctionne correctement. Aucune action requise.

### Statut : WARNING (orange)
L'endpoint fonctionne mais avec avertissements. Par exemple :
- 404 sur un détail (normal si aucune donnée de test)
- Temps de réponse élevé

### Statut : ERROR (rouge)
L'endpoint est cassé. Nécessite une correction.

**Exemple d'erreur :**

```json
{
  "name": "Rapports - Liste",
  "status": "ERROR",
  "http_code": 500,
  "error": "Undefined variable: conf",
  "file": "/path/to/rapports.php",
  "line": 42,
  "sql_error": null
}
```

**Correction :**
Ouvrir le fichier `/path/to/rapports.php` ligne 42 et ajouter `global $conf;`

## Ce que vous devez faire maintenant

### Étape 1 : Installer et exécuter

1. Uploadez les fichiers sur le serveur
2. Activez le mode dev : `touch /tmp/mv3pro_debug.flag`
3. Exécutez le diagnostic via la PWA ou curl
4. Exportez le rapport JSON

### Étape 2 : M'envoyer le rapport

Envoyez-moi le fichier JSON exporté. Je vais l'analyser et vous donner :

- Liste complète des erreurs à corriger
- Priorités (critique / important / mineur)
- Plan de correction étape par étape
- Fichiers à modifier avec corrections exactes

### Étape 3 : Corrections ciblées

Une fois le rapport analysé, je pourrai corriger tous les problèmes en une seule session, au lieu de corriger "au cas par cas".

## Sécurité

**Important :**

- Le mode dev (`/tmp/mv3pro_debug.flag`) permet l'accès à `debug.php` sans authentification
- **Ne jamais laisser ce fichier en production**
- Après le diagnostic, supprimez-le : `rm /tmp/mv3pro_debug.flag`

## En cas de problème

### debug.php retourne 403 (Accès refusé)

**Cause :** Mode dev pas activé et pas connecté comme admin

**Solution :**
```bash
touch /tmp/mv3pro_debug.flag
```

### debug.php retourne 404

**Cause :** Fichier pas uploadé au bon endroit

**Solution :**
Vérifier que le fichier est bien dans :
```
/custom/mv3pro_portail/api/v1/debug.php
```

### PWA ne charge pas la page debug

**Cause :** PWA pas recompilée ou mal uploadée

**Solution :**
Réuploader le dossier `pwa_dist/` complet

## Documentation complète

Pour plus de détails, consultez :

- `GUIDE_DIAGNOSTIC_SYSTEME.md` - Guide complet d'utilisation
- `SYSTEME_DIAGNOSTIC_COMPLET.md` - Détails techniques

---

## Résumé en 3 étapes

1. **Upload** : Uploadez `debug.php` et `pwa_dist/` sur le serveur
2. **Activer** : `touch /tmp/mv3pro_debug.flag`
3. **Exécuter** : Allez sur `/#/debug` dans la PWA et cliquez "Diagnostic Complet"
4. **Exporter** : Téléchargez le rapport JSON et envoyez-le-moi

Une fois le rapport en main, je pourrai identifier et corriger tous les problèmes en une seule fois.
