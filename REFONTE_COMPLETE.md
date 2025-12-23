# Refonte Complète - Architecture API REST Dolibarr Officielle

## Ce qui a été SUPPRIMÉ

### Système JWT + API PHP Custom (INUTILISABLE)
- `/api_php/` - SUPPRIMÉ
- `/deploy_api_php/` - SUPPRIMÉ
- Tous les fichiers PHP serveur
- Base de données MySQL externe
- Authentification email/mot de passe
- Tokens JWT
- config.php avec identifiants DB
- login.php, logout.php, verify.php

## Ce qui a été CRÉÉ

### Nouvelle Architecture (100% Sans Serveur)

#### 1. Authentification DOLAPIKEY
**Fichiers modifiés:**
- `src/utils/storage.ts` - Stockage URL Dolibarr + DOLAPIKEY
- `src/utils/api.ts` - Client API REST Dolibarr officielle
- `src/contexts/AuthContext.tsx` - Auth par DOLAPIKEY
- `src/screens/LoginScreen.tsx` - Écran connexion simplifié

**Comment ça marche:**
```typescript
// Connexion
await login('https://crm.mv-3pro.ch', 'DOLAPIKEY_XXX');

// Appel API
fetch(`${dolibarrUrl}/api/index.php/fichinter`, {
  headers: {
    'DOLAPIKEY': apiKey,
    'Accept': 'application/json'
  }
});
```

#### 2. Écran de Connexion Simplifié

**Avant (INUTILISABLE):**
- Email
- Mot de passe
- Authentification biométrique

**Maintenant:**
- URL Dolibarr: `https://crm.mv-3pro.ch`
- DOLAPIKEY: (coller la clé)
- Instructions intégrées pour obtenir la clé

#### 3. API Client Dolibarr

**Endpoints implémentés:**

| Fonction | Endpoint Dolibarr |
|----------|------------------|
| Vérifier clé | `/users/info` |
| Interventions | `/fichinter` |
| Planning | `/agendaevents` |
| Clients | `/thirdparties` |
| Projets | `/projects` |
| Documents | `/documents/upload` |

**Exemples:**
```typescript
// Récupérer interventions
const interventions = await getFichinters({ limit: 50 });

// Créer intervention
await createFichinter({
  socid: clientId,
  description: 'Travaux',
  note_private: 'Observations'
});

// Upload photo
await uploadDocument('fichinter', refId, photoFile);
```

#### 4. Gestion Offline Conservée

Le système IndexedDB local est conservé pour:
- Brouillons
- Photos non synchronisées
- Cache des données
- Fonctionnement hors-ligne

Synchronisation automatique au retour en ligne.

## Documentation Créée

### 1. ARCHITECTURE_DOLIBARR_API.md
- Explication complète de l'architecture
- Mapping des données
- Endpoints API
- Gestion offline
- Sécurité

### 2. GUIDE_UTILISATION.md
- Pour l'utilisateur final
- Pour l'administrateur
- FAQ
- Dépannage

### 3. DEPLOIEMENT_SANS_SERVEUR.md
- Étapes de déploiement
- Options d'hébergement (Netlify, Vercel, GitHub Pages)
- Configuration CORS
- Maintenance

## Comparaison

### Avant (JWT + PHP)
- Backend PHP requis
- Base MySQL externe
- config.php à configurer
- JWT_SECRET à gérer
- Accès serveur SSH + FTP obligatoire
- Maintenance serveur
- Coûts serveur

### Maintenant (API REST Dolibarr)
- Aucun backend intermédiaire
- Aucune base externe
- Aucun fichier de config serveur
- Aucun secret à gérer
- Aucun accès serveur requis
- Déploiement de fichiers statiques uniquement
- Coûts minimaux (hébergement statique gratuit)

## Tests Effectués

- Build réussi sans erreurs
- TypeScript compilation OK
- Tous les imports résolus
- Taille du bundle optimisée: 204 KB (60 KB gzip)

## Comment Obtenir la DOLAPIKEY

1. Connexion à Dolibarr: `https://crm.mv-3pro.ch`
2. Menu utilisateur (coin supérieur droit)
3. "Modifier ma fiche utilisateur"
4. Onglet "Clé API"
5. "Générer une nouvelle clé"
6. Copier et coller dans l'application

## Utilisation

1. **Déployer:**
   ```bash
   npm run build
   # Uploader le dossier dist/ sur votre hébergeur
   ```

2. **Se connecter:**
   - URL: `https://app.mv-3pro.ch/`
   - Entrer URL Dolibarr
   - Entrer DOLAPIKEY
   - Connexion automatique

3. **Utiliser:**
   - Dashboard avec stats
   - Créer rapports d'intervention
   - Synchronisés avec Dolibarr (Fichinter)
   - Photos uploadées dans ECM
   - Fonctionne offline

## Prochaines Étapes

1. **Activer module API REST dans Dolibarr**
2. **Configurer CORS** (voir DEPLOIEMENT_SANS_SERVEUR.md)
3. **Déployer l'application**
4. **Générer DOLAPIKEY pour chaque utilisateur**
5. **Communiquer les instructions de connexion**

## Support

- Documentation API REST: https://wiki.dolibarr.org/index.php/REST_API
- Tester manuellement:
  ```bash
  curl -H "DOLAPIKEY: XXX" https://crm.mv-3pro.ch/api/index.php/users/info
  ```

## Avantages de la Nouvelle Architecture

- Déploiement en 1 minute
- Aucun serveur backend à gérer
- Sécurité gérée par Dolibarr
- Évolutif automatiquement
- Coûts minimaux
- Maintenance simplifiée
- 100% conforme aux standards Dolibarr
