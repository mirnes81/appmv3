# Architecture MV3 Pro - API REST Dolibarr Officielle

## Architecture 100% Sans Backend Custom

L'application utilise UNIQUEMENT l'API REST officielle de Dolibarr. Aucun serveur PHP intermédiaire requis.

### Authentification

**Méthode:** DOLAPIKEY uniquement

**Écran de connexion:**
- URL Dolibarr (ex: `https://crm.mv-3pro.ch`)
- Champ DOLAPIKEY
- Stockage sécurisé en localStorage

**Comment obtenir votre DOLAPIKEY:**
1. Connectez-vous à Dolibarr
2. Cliquez sur votre nom (coin supérieur droit)
3. "Modifier ma fiche utilisateur"
4. Onglet "Clé API"
5. "Générer une nouvelle clé"
6. Copiez et collez dans l'application

### Endpoints API Utilisés

Tous les appels passent par `/api/index.php/...` avec le header `DOLAPIKEY`

```javascript
fetch(`${dolibarrUrl}/api/index.php/fichinter`, {
  headers: {
    'DOLAPIKEY': apiKey,
    'Accept': 'application/json'
  }
});
```

#### Endpoints principaux:

| Fonctionnalité | Endpoint Dolibarr | Méthode |
|----------------|-------------------|---------|
| Info utilisateur | `/users/info` | GET |
| Interventions | `/fichinter` | GET/POST |
| Agenda | `/agendaevents` | GET/POST |
| Clients | `/thirdparties` | GET |
| Projets | `/projects` | GET |
| Documents/Photos | `/documents/upload` | POST |

### Mapping des Données

#### Rapports d'intervention → Fichinter Dolibarr

```typescript
Report (App) → Fichinter (Dolibarr)
- description → description
- observations → note_private
- client_name → socid
- project_id → fk_project
- photos → ECM documents
```

#### Planning → Agenda Dolibarr

```typescript
Planning (App) → AgendaEvent (Dolibarr)
- date/time → datep/datef
- description → label
- notes → note
```

### Gestion Offline

**Technologies:**
- IndexedDB pour stockage local
- Service Worker pour cache
- Synchronisation automatique quand connexion rétablie

**Données stockées localement:**
- Rapports en brouillon
- Photos non uploadées
- Modifications en attente
- Cache des clients/projets

**Synchronisation:**
```typescript
// Auto-sync au retour online
window.addEventListener('online', () => {
  syncPendingActions();
});
```

### Gestion CORS

**Option 1: CORS activé côté Dolibarr**
- Modifier `conf.php` : `$dolibarr_main_force_https = '1'`
- Headers CORS dans `.htaccess`

**Option 2: Proxy (recommandé)**
```
https://app.mv-3pro.ch/api/ → proxy vers → https://crm.mv-3pro.ch/api/
```

Créer un simple proxy NGINX/Apache qui ajoute les headers CORS.

### Structure du Projet

```
src/
├── utils/
│   ├── api.ts          // Client API Dolibarr REST
│   ├── storage.ts      // Gestion DOLAPIKEY + cache
│   └── db.ts           // IndexedDB pour offline
├── contexts/
│   ├── AuthContext.tsx // Auth par DOLAPIKEY
│   └── OfflineContext.tsx // Sync offline
└── screens/
    ├── LoginScreen.tsx  // URL + DOLAPIKEY
    ├── Dashboard.tsx
    ├── ReportsScreen.tsx
    └── PlanningScreen.tsx
```

### Sécurité

**DOLAPIKEY:**
- Stockée en localStorage (chiffrée si possible)
- Jamais exposée dans les logs
- Révocable depuis Dolibarr

**Bonnes pratiques:**
- Utiliser HTTPS obligatoirement
- DOLAPIKEY par utilisateur (pas de clé partagée)
- Régénérer régulièrement les clés
- Activer les permissions par module dans Dolibarr

### Installation & Déploiement

**1. Build de l'application:**
```bash
npm install
npm run build
```

**2. Déployer le dossier `dist/` sur votre hébergeur**
```
https://app.mv-3pro.ch/
```

**3. Aucune configuration serveur requise**
- Pas de base de données à créer
- Pas de PHP à installer
- Pas de variables d'environnement

**4. Configuration utilisateur:**
- Chaque utilisateur entre son URL Dolibarr
- Chaque utilisateur entre sa propre DOLAPIKEY
- L'application se connecte directement à Dolibarr

### Fonctionnalités Non Implémentées

Certaines fonctionnalités nécessitent un module Dolibarr custom:

**Régie:**
- Actuellement: `throw new Error('Non implémenté')`
- Solution: Créer un module Dolibarr avec endpoint custom

**Sens de pose:**
- Actuellement: `throw new Error('Non implémenté')`
- Solution: Créer un module Dolibarr avec endpoint custom

**Pour ajouter ces fonctionnalités:**
1. Créer un module Dolibarr (`/custom/mv3_custom`)
2. Ajouter les tables SQL nécessaires
3. Créer des endpoints API dans le module
4. Utiliser le même système DOLAPIKEY

### Tests

**Tester la connexion API:**
```javascript
// Dans la console navigateur après connexion
const url = 'https://crm.mv-3pro.ch';
const key = 'VOTRE_DOLAPIKEY';

fetch(`${url}/api/index.php/users/info`, {
  headers: {
    'DOLAPIKEY': key,
    'Accept': 'application/json'
  }
}).then(r => r.json()).then(console.log);
```

**Résultat attendu:**
```json
{
  "id": 1,
  "login": "admin",
  "lastname": "Doe",
  "firstname": "John",
  "email": "admin@example.com"
}
```

### Dépannage

**Erreur: "DOLAPIKEY invalide"**
- Vérifier que la clé est correctement copiée
- Régénérer une nouvelle clé dans Dolibarr
- Vérifier que le module API REST est activé dans Dolibarr

**Erreur CORS:**
- Activer CORS dans la config Dolibarr
- Ou utiliser un proxy

**Données ne se synchronisent pas:**
- Vérifier la connexion internet
- Consulter IndexedDB dans DevTools
- Vérifier les permissions Dolibarr

### Avantages de cette Architecture

- Pas de serveur backend à maintenir
- Aucun accès serveur requis
- Déploiement simple (fichiers statiques)
- Sécurité gérée par Dolibarr
- Évolutif (suit les updates Dolibarr)
- Offline-first avec synchronisation

### Support

Pour toute question sur l'API REST Dolibarr officielle:
- Documentation: https://wiki.dolibarr.org/index.php/REST_API
- Forum: https://www.dolibarr.org/forum/
