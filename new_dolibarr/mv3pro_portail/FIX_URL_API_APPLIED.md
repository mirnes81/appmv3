# FIX URL API - Corrections appliquées

## Problème identifié
En mode preview Bolt, l'URL API appelée était incorrecte, ce qui causait des erreurs 404 "Not Found".

## Corrections appliquées

### 1. Configuration des variables d'environnement

✅ **pwa/.env.development** (pour preview Bolt)
```
VITE_API_BASE=https://crm.mv-3pro.ch/custom/mv3pro_portail
```

✅ **pwa/.env.production** (pour déploiement Dolibarr)
```
VITE_API_BASE=/custom/mv3pro_portail
```

### 2. Mode Debug amélioré dans Login.tsx

✅ Utilisation de `import.meta.env.VITE_API_BASE` au lieu de valeur hardcodée
✅ Logs console détaillés :
- VITE_API_BASE (variable d'environnement)
- API_BASE resolved (valeur finale utilisée)
- LOGIN_URL (URL complète appelée)
- ME_URL (URL complète de vérification)

✅ Affichage de l'URL dans les détails de l'étape 1

### 3. Structure centralisée déjà en place

Le fichier `pwa/src/lib/api.ts` existe déjà et gère correctement :
- L'utilisation de la config depuis `config.ts`
- Les appels API avec token
- La gestion d'erreurs

## Comment tester

### En preview Bolt :

1. Rafraîchir la page (F5)
2. Activer le mode DEBUG
3. Ouvrir la console (F12)
4. Se connecter avec : mirnes@mv-3pro.ch

**Vérifications attendues dans la console :**
```
🔧 [DEBUG MODE] Configuration:
  VITE_API_BASE: https://crm.mv-3pro.ch/custom/mv3pro_portail
  API_BASE resolved: https://crm.mv-3pro.ch/custom/mv3pro_portail
  LOGIN_URL: https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/api/auth.php?action=login
  ME_URL: https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/me.php
```

**Vérifications attendues dans l'interface :**
- ✅ ÉTAPE 1: Connexion au serveur (success)
- Détails affichent : `{ "url": "https://crm.mv-3pro.ch/..." }`
- Status HTTP: 200
- Response JSON avec success: true

### En production Dolibarr :

Les URLs seront automatiquement relatives :
```
/custom/mv3pro_portail/mobile_app/api/auth.php?action=login
```

## Critères de succès

🎯 **En preview Bolt :**
- L'URL appelée commence par `https://crm.mv-3pro.ch/custom/mv3pro_portail/...`
- Login retourne JSON avec `success: true`
- Token est stocké en localStorage
- Redirection vers Dashboard fonctionne

🎯 **En production :**
- Les URLs relatives fonctionnent
- L'authentification fonctionne comme avant

## Fichiers modifiés

- ✅ `pwa/.env.development` - Corrigé l'URL (crm.mv-3pro.ch au lieu de crm-mv-3pro.ch)
- ✅ `pwa/src/pages/Login.tsx` - Utilise `import.meta.env.VITE_API_BASE` + logs détaillés
- ℹ️ `pwa/src/lib/api.ts` - Déjà existant et correct
- ℹ️ `pwa/src/config.ts` - Déjà existant et correct

## Build

✅ Build réussi : `pwa_dist/assets/index-CT4p1pgp.js` (220 KB)

Date: 2026-01-09
