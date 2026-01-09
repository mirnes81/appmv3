# Système de Diagnostic Complet - MV3 PRO

## Date de création : 2026-01-09

## Résumé

Un système de diagnostic automatique complet a été mis en place pour tester tous les endpoints API et toutes les pages PWA de l'application MV3 PRO. Ce système permet d'identifier rapidement les problèmes sans devoir tester manuellement chaque endpoint.

## Fichiers créés/modifiés

### Backend PHP

1. **`/api/v1/debug.php`** (CRÉÉ)
   - Endpoint de diagnostic backend
   - Teste automatiquement tous les endpoints API
   - Capture les erreurs 500 avec détails (fichier, ligne, stacktrace)
   - Affiche les erreurs SQL si disponibles
   - Mesure les temps de réponse
   - Vérifie la configuration système (tables, module activé, etc.)

### Frontend PWA

2. **`/pwa/src/pages/Debug.tsx`** (MODIFIÉ)
   - Interface complète de diagnostic
   - Bouton "Diagnostic Complet" qui teste backend + frontend
   - Bouton "Backend API" pour tester uniquement le backend
   - Bouton "Frontend API" pour tester depuis le navigateur
   - Affichage des résultats avec statistiques
   - Export du rapport complet en JSON
   - Interface moderne et intuitive

### Documentation

3. **`GUIDE_DIAGNOSTIC_SYSTEME.md`** (CRÉÉ)
   - Guide d'utilisation complet
   - Instructions d'accès et de sécurité
   - Interprétation des résultats
   - Résolution des problèmes courants

4. **`SYSTEME_DIAGNOSTIC_COMPLET.md`** (CE FICHIER)
   - Récapitulatif de la mise en place

### Build

5. **PWA Build**
   - Compilation réussie avec le nouveau système de diagnostic
   - Fichiers générés dans `/pwa_dist/`

## Fonctionnalités principales

### 1. Diagnostic Backend

Le fichier `debug.php` teste automatiquement :

- ✅ Auth & User (me.php)
- ✅ Planning (planning.php, planning_view.php)
- ✅ Rapports (rapports.php, rapports_view.php)
- ✅ Matériel (materiel_list.php, materiel_view.php)
- ✅ Notifications (notifications_list.php, notifications_unread_count.php)
- ✅ Régie (regie_list.php, regie_view.php)
- ✅ Sens de Pose (sens_pose_list.php, sens_pose_view.php)
- ✅ Frais (frais_list.php)

Pour chaque endpoint, le système retourne :
- Code HTTP (200, 401, 500, etc.)
- Temps de réponse en ms
- Message d'erreur détaillé
- Fichier et ligne de l'erreur PHP
- Erreur SQL si applicable
- Preview de la réponse JSON

### 2. Diagnostic Frontend

L'interface PWA permet de :
- Tester tous les endpoints depuis le navigateur
- Voir les temps de réponse réels
- Identifier les problèmes CORS
- Vérifier l'authentification
- Exporter le rapport complet

### 3. Vérifications Système

Le diagnostic vérifie automatiquement :
- Module MV3PRO activé dans Dolibarr
- Présence des tables requises (mv3_mobile_users, mv3_mobile_sessions, mv3_rapport)
- Version PHP et Dolibarr
- Configuration mémoire et temps d'exécution

## Sécurité

Le système de diagnostic est protégé par 3 méthodes d'authentification :

1. **Admin Dolibarr** : Si connecté comme admin dans Dolibarr
2. **DEBUG_KEY** : Clé secrète configurable
3. **Mode Dev** : Fichier flag `/tmp/mv3pro_debug.flag`

### Activer le mode développement

```bash
# Sur le serveur
touch /tmp/mv3pro_debug.flag
```

### Désactiver le mode développement

```bash
rm /tmp/mv3pro_debug.flag
```

## Utilisation

### Méthode 1 : Via la PWA (recommandé)

1. Connectez-vous à la PWA : `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Allez sur `/#/debug`
3. Cliquez sur **"Diagnostic Complet"**
4. Consultez les résultats
5. Exportez en JSON si nécessaire

### Méthode 2 : Via curl

```bash
# Activer le mode dev sur le serveur
touch /tmp/mv3pro_debug.flag

# Exécuter le diagnostic
curl https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php | jq .
```

### Méthode 3 : Avec un token

```bash
# Login pour obtenir un token
TOKEN=$(curl -s -X POST \
  "https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/api/auth.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{"email":"VOTRE_EMAIL","password":"VOTRE_PASSWORD"}' | jq -r .token)

# Exécuter le diagnostic
curl -H "X-Auth-Token: $TOKEN" \
  https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php | jq .
```

## Exemple de résultat

```json
{
  "success": true,
  "debug_mode": true,
  "timestamp": "2026-01-09 14:30:00",
  "stats": {
    "total": 15,
    "ok": 12,
    "warning": 2,
    "error": 1,
    "total_time_ms": 450
  },
  "config_checks": [
    {
      "name": "Module MV3PRO activé",
      "status": "OK",
      "value": "Oui"
    },
    {
      "name": "Table llx_mv3_mobile_users",
      "status": "OK",
      "value": "Existe"
    }
  ],
  "test_results": [
    {
      "name": "Me (infos utilisateur)",
      "url": "me.php",
      "status": "OK",
      "http_code": 200,
      "response_time_ms": 25,
      "response_preview": {
        "success": true,
        "user": {...}
      }
    },
    {
      "name": "Rapports - Liste",
      "url": "rapports.php",
      "status": "ERROR",
      "http_code": 500,
      "response_time_ms": 50,
      "error": "Undefined variable: conf",
      "file": "/path/to/rapports.php",
      "line": 42
    }
  ]
}
```

## Export JSON

Le bouton **"Exporter JSON"** dans la PWA télécharge un fichier contenant :

- Rapport backend complet
- Résultats des tests frontend
- Informations navigateur
- Informations session utilisateur
- Configuration système

Ce fichier peut être partagé pour analyse ou debugging.

## Corrections à apporter

Le diagnostic permet d'identifier rapidement les endpoints qui nécessitent des corrections. Une fois le rapport obtenu :

1. Identifiez les endpoints en **ERROR** (rouge)
2. Consultez le message d'erreur et le fichier/ligne
3. Vérifiez l'erreur SQL si applicable
4. Corrigez le problème
5. Relancez le diagnostic pour confirmer

## Prochaines étapes

Maintenant que le système de diagnostic est en place :

1. **Exécutez le diagnostic complet** pour identifier tous les problèmes
2. **Exportez le rapport JSON** pour avoir une trace complète
3. **Analysez les erreurs** une par une
4. **Corrigez les problèmes** identifiés
5. **Relancez le diagnostic** pour confirmer les corrections

## Avantages

- ✅ Vision complète de l'état de l'application en 1 clic
- ✅ Identification rapide des problèmes
- ✅ Détails précis (fichier, ligne, SQL)
- ✅ Mesure des performances (temps de réponse)
- ✅ Export pour analyse offline
- ✅ Pas besoin de tester manuellement chaque endpoint
- ✅ Vérification automatique de la configuration

## Notes importantes

1. **Sécurité** : Ne jamais laisser le mode dev activé en production
2. **Performance** : Le diagnostic complet prend quelques secondes (normal)
3. **Token** : Un token valide est requis pour tester les endpoints protégés
4. **404 attendus** : Certains endpoints retournent 404 si aucune donnée de test (normal)

## Support

Pour toute question sur le système de diagnostic :

1. Consultez `GUIDE_DIAGNOSTIC_SYSTEME.md`
2. Vérifiez les logs serveur PHP
3. Vérifiez la console navigateur (F12)
4. Exportez le rapport JSON pour analyse

---

**Système créé le :** 2026-01-09
**Version :** 1.0
**Statut :** ✅ Opérationnel
**Build PWA :** ✅ Compilé avec succès
