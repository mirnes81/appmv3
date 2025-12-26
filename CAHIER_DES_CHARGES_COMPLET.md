# 📋 CAHIER DES CHARGES COMPLET - MV3 PRO PWA

## 🎯 PRÉSENTATION DU PROJET

### Nom du projet
**MV3 Pro - Gestion de chantiers mobile**

### Objectif
Application web progressive (PWA) pour la gestion quotidienne des interventions sur chantiers, permettant aux équipes terrain de :
- Suivre leur temps de travail en temps réel
- Créer des rapports d'intervention
- Gérer les fiches de régie
- Documenter les sens de pose (carrelage)
- Prendre des photos et notes vocales
- Travailler en mode hors-ligne avec synchronisation automatique

---

## 🏗️ ARCHITECTURE TECHNIQUE

### 1. Technologies utilisées

#### Frontend
- **React 18.2.0** - Framework JavaScript
- **TypeScript 5.3.3** - Typage statique
- **Vite 5.0.11** - Build tool et dev server
- **Tailwind CSS 3.4.1** - Framework CSS
- **Lucide React 0.309.0** - Icônes

#### Gestion d'état et données
- **React Context API** - Gestion d'état global
- **IndexedDB** - Base de données locale (mode offline)
- **LocalStorage** - Stockage persistant simple

#### Routing
- **React Router DOM 6.21.2** - Navigation SPA

#### API et communication
- **Fetch API** - Appels HTTP
- **API REST Dolibarr** - Backend

### 2. Architecture globale

```
┌─────────────────────────────────────────────────────────────┐
│                    NAVIGATEUR (PWA)                         │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              MV3 Pro React App                        │  │
│  │  ┌─────────────┐  ┌──────────────┐  ┌─────────────┐  │  │
│  │  │  Components │  │   Contexts   │  │    Utils    │  │  │
│  │  │   (UI)      │  │   (State)    │  │  (API/DB)   │  │  │
│  │  └─────────────┘  └──────────────┘  └─────────────┘  │  │
│  └───────────────────────────────────────────────────────┘  │
│                           ↕                                  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │            IndexedDB (Mode Offline)                   │  │
│  │  - Reports  - Photos  - Notes  - Cache               │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                          ↕ HTTPS
┌─────────────────────────────────────────────────────────────┐
│         Apache (app.mv-3pro.ch) - Reverse Proxy             │
│                   /api/* → crm.mv-3pro.ch/api/*             │
└─────────────────────────────────────────────────────────────┘
                          ↕ HTTPS
┌─────────────────────────────────────────────────────────────┐
│              Dolibarr ERP (crm.mv-3pro.ch)                  │
│  - Interventions (Fichinter)                                │
│  - Agenda / Planning                                        │
│  - Utilisateurs                                             │
│  - Documents / ECM                                          │
│  - Projets                                                  │
└─────────────────────────────────────────────────────────────┘
```

### 3. URLs de production

| Service | URL | Description |
|---------|-----|-------------|
| **PWA Frontend** | `https://app.mv-3pro.ch/pro/` | Application React |
| **API Dolibarr** | `https://crm.mv-3pro.ch/api/` | API REST backend |
| **Proxy API** | `https://app.mv-3pro.ch/api/` | Reverse proxy vers Dolibarr |

---

## 🔑 AUTHENTIFICATION ET SÉCURITÉ

### 1. Méthode d'authentification

**Authentification par DOLAPIKEY uniquement**

❌ **Supprimé** :
- Login email/mot de passe
- JWT custom
- Session PHP
- Base MySQL externe

✅ **Implémenté** :
- DOLAPIKEY (clé API Dolibarr)
- Header HTTP : `DOLAPIKEY: votre_cle`
- Vérification via `/users/info`

### 2. Flux de connexion

```
1. Utilisateur saisit sa DOLAPIKEY
   ↓
2. Frontend appelle /api/index.php/users/info
   ↓
3. Dolibarr vérifie la clé
   ↓
4. Si valide → Récupération des infos utilisateur
   ↓
5. Stockage en localStorage :
   - DOLAPIKEY
   - User info (id, nom, email)
   ↓
6. Redirection vers Dashboard
```

### 3. Stockage des credentials

**LocalStorage** :
```javascript
{
  "dolapikey": "clé_api_utilisateur",
  "user": {
    "id": "123",
    "dolibarr_user_id": 123,
    "email": "user@example.com",
    "name": "John Doe",
    "phone": "+33612345678"
  }
}
```

### 4. Sécurité

| Élément | Implémentation |
|---------|----------------|
| **Transport** | HTTPS obligatoire (Let's Encrypt) |
| **Headers** | X-Content-Type-Options, X-Frame-Options, X-XSS-Protection |
| **CORS** | Configuré dans .htaccess |
| **API Key** | Stockée côté client (localStorage) |
| **Session** | Vérifiée à chaque chargement |

---

## 📱 FONCTIONNALITÉS

### 1. Dashboard (Écran principal)

**Fichier** : `src/screens/Dashboard.tsx`

**Fonctionnalités** :
- Affichage du nom de l'utilisateur
- Statistiques rapides (rapports, régies, sens de pose)
- Indicateur de statut réseau (online/offline)
- Navigation vers les modules

**Données affichées** :
- Nombre de rapports
- Nombre de régies
- Nombre de sens de pose
- Statut de synchronisation

### 2. Écran de connexion

**Fichier** : `src/screens/LoginScreen.tsx`

**Champs** :
- DOLAPIKEY (masquée par défaut)
- Bouton afficher/masquer la clé

**Fonctionnalités** :
- Validation de la clé via API
- Message d'erreur si clé invalide
- Instructions pour obtenir la clé
- Sauvegarde automatique en localStorage

### 3. Gestion des rapports

**Fichier** : `src/screens/NewReportScreen.tsx`

#### 3.1. Création de rapport

**Champs du formulaire** :
- Date (par défaut : aujourd'hui)
- Heure de début
- Heure de fin
- Nom du client
- Description du travail
- Observations

**Fonctionnalités avancées** :
- **Suivi du temps intégré** (TimeTracker)
  - Bouton ▶️ Démarrer
  - Bouton ⏸ Pause
  - Bouton ▶️ Reprendre
  - Bouton ⏹ Stop
  - Affichage du temps total en HH:MM:SS
  - Historique des périodes

- **Capture photo**
  - Accès à la caméra
  - Compression automatique
  - Aperçu avant ajout
  - Stockage en base64

- **Notes vocales**
  - Enregistrement audio
  - Transcription automatique (Web Speech API)
  - Insertion dans la description

- **Géolocalisation**
  - GPS automatique
  - Coordonnées stockées avec le rapport

- **Sauvegarde automatique**
  - Toutes les 10 secondes
  - Stockage en IndexedDB
  - Indicateur de sauvegarde

#### 3.2. Liste des rapports

**Fichier** : `src/screens/ReportsScreen.tsx`

**Affichage** :
- Liste des rapports par date
- Statut (brouillon, en attente, envoyé)
- Filtres par statut
- Recherche par client

**Actions** :
- Voir le détail
- Modifier
- Supprimer
- Synchroniser (si offline)

### 4. Gestion de régie

**Fichier** : `src/screens/RegieScreen.tsx`

**Fonctionnalités** :
- Création de fiche de régie
- Saisie des heures par jour
- Saisie du matériel utilisé
- Signature électronique
- Export PDF

**Champs** :
- Date
- Chantier / Projet
- Heures travaillées
- Matériel utilisé
- Description des travaux
- Signature client

### 5. Sens de pose (Carrelage)

**Fichier** : `src/screens/SensPoseScreen.tsx`

**Fonctionnalités** :
- Création depuis un devis Dolibarr
- Sélection des produits
- Schéma de pose
- Photos du chantier
- Signature client
- Envoi par email au client

**Workflow** :
1. Sélection du client
2. Sélection du devis
3. Sélection des produits concernés
4. Ajout de photos
5. Dessin du schéma de pose
6. Signature client
7. Génération et envoi PDF

### 6. Planning

**Fichier** : `src/screens/PlanningScreen.tsx`

**Fonctionnalités** :
- Affichage du planning journalier
- Affichage du planning hebdomadaire
- Liste des interventions
- Détails d'intervention

**Données affichées** :
- Événements de l'agenda Dolibarr
- Interventions planifiées
- Horaires
- Clients
- Lieux

### 7. Matériel

**Fichier** : `src/screens/MaterielScreen.tsx`

**Fonctionnalités** :
- Liste du matériel disponible
- Recherche de matériel
- Réservation de matériel
- Historique d'utilisation

### 8. Profil utilisateur

**Fichier** : `src/screens/ProfileScreen.tsx`

**Affichage** :
- Informations personnelles
- Email
- Téléphone
- Statistiques d'activité

**Actions** :
- Déconnexion
- Vider le cache
- Version de l'application

---

## ⏱️ SUIVI DES HEURES (TIME TRACKER)

### 1. Composant TimeTracker

**Fichier** : `src/components/TimeTracker.tsx`

### 2. États du tracker

| État | Description | Actions disponibles |
|------|-------------|---------------------|
| **Arrêté** | Timer à 00:00:00 | ▶️ Démarrer |
| **En cours** | Timer en marche | ⏸ Pause, ⏹ Stop |
| **En pause** | Timer stoppé temporairement | ▶️ Reprendre, ⏹ Stop |

### 3. Fonctionnement technique

```javascript
// Structure de données
interface TimeEntry {
  start: Date;      // Heure de début
  end?: Date;       // Heure de fin (optionnelle)
  duration: number; // Durée en secondes
}

// Stockage localStorage
{
  "time_tracker_<fichinter_id>": {
    "totalSeconds": 7200,    // 2 heures
    "entries": [
      {
        "start": "2024-01-15T08:00:00",
        "end": "2024-01-15T10:00:00",
        "duration": 7200
      }
    ]
  }
}
```

### 4. Calcul du temps

**En cours** :
```javascript
displayTime = totalSeconds + (now - currentStart)
```

**En pause** :
```javascript
totalSeconds = totalSeconds + (pauseStart - currentStart)
```

**Arrêté** :
```javascript
totalSeconds = totalSeconds + (stopTime - currentStart)
// Création d'une nouvelle entrée
```

### 5. Affichage

Format : `HH:MM:SS`
- Mise à jour chaque seconde
- Police tabular-nums pour alignement
- Affichage en gros (2xl)

### 6. Historique

Pour chaque période :
- Heure de début
- Heure de fin
- Durée totale

Exemple :
```
08:00:15 - 10:30:45  →  2h 30m 30s
11:15:00 - 12:00:00  →  45m 00s
```

### 7. Intégration

Le TimeTracker est intégré dans :
- Écran de création de rapport
- Écran de régie
- Lié à l'ID de l'intervention (fichinter_id)

---

## 💾 MODE OFFLINE

### 1. Technologies

- **IndexedDB** : Base de données locale
- **Service Worker** : Cache des assets
- **localStorage** : Données simples

### 2. Base de données IndexedDB

**Nom** : `MV3ProDB`

**Stores (tables)** :

| Store | Clé primaire | Index | Description |
|-------|--------------|-------|-------------|
| `reports` | id | user_id, status, date | Rapports d'intervention |
| `regie` | id | user_id, status, date | Fiches de régie |
| `sens_pose` | id | user_id, status, date | Sens de pose |
| `templates` | id | user_id, report_type | Modèles de rapports |
| `sync_queue` | id | user_id, status, priority | File d'attente de sync |
| `cache` | id | cache_key, cache_type, expires_at | Cache API |
| `photos` | id | uploaded | Photos non uploadées |

### 3. Fonctionnement offline

#### Détection du statut réseau

**Fichier** : `src/contexts/OfflineContext.tsx`

```javascript
// Écoute des événements
window.addEventListener('online', handleOnline);
window.addEventListener('offline', handleOffline);

// Test de connexion
fetch('/api/index.php/status')
  .then(() => setIsOnline(true))
  .catch(() => setIsOnline(false));
```

#### Sauvegarde en mode offline

**Workflow** :
1. Utilisateur crée un rapport
2. Vérification : `isOnline` ?
   - **Si online** : Envoi immédiat à l'API
   - **Si offline** : Sauvegarde dans IndexedDB
3. Ajout dans la file de synchronisation
4. Marque le statut : `pending_sync`

#### Synchronisation au retour en ligne

**Fichier** : `src/contexts/OfflineContext.tsx`

**Processus** :
```javascript
1. Détection du retour en ligne
   ↓
2. Récupération de la file sync_queue
   ↓
3. Pour chaque élément :
   - Tentative d'envoi à l'API
   - Si succès : suppression de la queue
   - Si échec : conservation pour retry
   ↓
4. Notification à l'utilisateur
```

#### Gestion des conflits

**Stratégie** : Last Write Wins (LWW)
- Le timestamp `updated_at` fait foi
- Pas de merge complexe
- L'utilisateur est notifié en cas de conflit

### 4. Service Worker

**Fichier** : `public/sw.js`

**Stratégies de cache** :

| Ressource | Stratégie | Description |
|-----------|-----------|-------------|
| **Assets statiques** | Cache First | JS, CSS, images |
| **API /users/info** | Network First | Vérification session |
| **API GET** | Network First, Cache Fallback | Données métier |
| **API POST/PUT** | Network Only | Création/modification |

**Durée de vie du cache** :
- Assets : 7 jours
- API : 1 heure
- Images : 30 jours

### 5. Indicateurs visuels

**Indicateur de statut** :
- 🟢 En ligne : Badge vert "En ligne"
- 🔴 Hors ligne : Badge rouge "Hors ligne"
- 🟡 Synchronisation : Badge jaune "Synchronisation..."

**Notifications** :
- "✅ Données synchronisées"
- "⚠️ Mode hors ligne - Les données seront synchronisées plus tard"
- "❌ Échec de synchronisation"

---

## 🔄 API ET INTÉGRATION DOLIBARR

### 1. Configuration

**Fichier** : `.env`
```env
VITE_API_BASE=/api/index.php
VITE_DEFAULT_DOLIBARR_URL=https://crm.mv-3pro.ch
```

### 2. Reverse Proxy Apache

**Fichier** : `public/.htaccess`

**Règle de réécriture** :
```apache
RewriteCond %{REQUEST_URI} ^/api/(.*)$
RewriteRule ^api/(.*)$ https://crm.mv-3pro.ch/api/$1 [P,L]
```

**Effet** :
```
https://app.mv-3pro.ch/api/index.php/users/info
          ↓ (proxy)
https://crm.mv-3pro.ch/api/index.php/users/info
```

### 3. Endpoints utilisés

**Fichier** : `src/utils/api.ts`

#### Authentification
```javascript
GET /users/info
Headers: { DOLAPIKEY: "clé" }
→ Retourne les infos utilisateur
```

#### Interventions (Fichinter)
```javascript
GET /interventions
GET /interventions/:id
POST /interventions
PUT /interventions/:id
DELETE /interventions/:id
```

#### Agenda
```javascript
GET /agendaevents?from=2024-01-01&to=2024-12-31
GET /agendaevents/:id
```

#### Projets
```javascript
GET /projects
GET /projects/:id
```

#### Propositions commerciales (Devis)
```javascript
GET /proposals
GET /proposals/:id
GET /proposals/:id/lines
```

#### Documents
```javascript
POST /documents/upload
GET /documents?modulepart=fichinter&ref=FI123
```

#### Utilisateurs
```javascript
GET /users
GET /users/:id
```

### 4. Fonction d'appel API

**Fichier** : `src/utils/api.ts`

```javascript
async function fetchDolibarr(endpoint: string, options: RequestInit = {}) {
  const apiKey = await getDolapikey();

  const headers = {
    'DOLAPIKEY': apiKey,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  };

  const url = `${API_BASE}${endpoint}`;

  const response = await fetch(url, {
    ...options,
    headers
  });

  if (!response.ok) {
    throw new Error(response.statusText);
  }

  return response.json();
}
```

### 5. Gestion des erreurs API

| Code HTTP | Gestion | Action |
|-----------|---------|--------|
| **401** | Non autorisé | Déconnexion + redirect login |
| **403** | Interdit | Message "Accès refusé" |
| **404** | Non trouvé | Message "Ressource non trouvée" |
| **500** | Erreur serveur | Message "Erreur serveur" |
| **Network Error** | Pas de réseau | Basculement mode offline |

---

## 📸 GESTION DES MÉDIAS

### 1. Photos

**Composant** : `src/components/CameraCapture.tsx`

#### Capture photo

**Processus** :
1. Demande d'accès à la caméra
2. Stream vidéo dans un `<video>`
3. Capture dans un `<canvas>`
4. Conversion en base64
5. Compression (qualité 0.8)
6. Stockage

**Code** :
```javascript
const stream = await navigator.mediaDevices.getUserMedia({
  video: { facingMode: 'environment' }
});

canvas.drawImage(video, 0, 0, width, height);
const base64 = canvas.toDataURL('image/jpeg', 0.8);
```

#### Stockage des photos

**En ligne** :
- Upload via `/documents/upload`
- Stockage dans ECM Dolibarr
- Lié à l'intervention

**Hors ligne** :
- Stockage dans IndexedDB (table `photos`)
- Upload lors de la synchronisation

#### Compression

- Format : JPEG
- Qualité : 0.8 (80%)
- Résolution max : 1920x1080
- Taille moyenne : ~200-500 KB

### 2. Notes vocales

**Composant** : `src/components/VoiceRecorder.tsx`

#### Enregistrement

**API utilisée** : Web Speech API

```javascript
const recognition = new webkitSpeechRecognition();
recognition.lang = 'fr-FR';
recognition.continuous = true;
recognition.interimResults = true;

recognition.onresult = (event) => {
  const transcript = event.results[0][0].transcript;
  setTranscription(transcript);
};
```

#### Fonctionnalités

- Enregistrement en temps réel
- Transcription automatique en français
- Affichage du texte pendant l'enregistrement
- Insertion dans la description du rapport

#### Stockage

**Structure** :
```javascript
interface VoiceNote {
  id: string;
  audio: string;        // base64
  transcription: string;
  duration: number;     // en secondes
  created_at: string;
}
```

### 3. Géolocalisation

**API utilisée** : Geolocation API

```javascript
navigator.geolocation.getCurrentPosition(
  (position) => {
    const { latitude, longitude } = position.coords;
    setLocation({ lat: latitude, lng: longitude });
  },
  (error) => {
    console.error('Geolocation error:', error);
  }
);
```

**Permissions** :
- Demandée au premier usage
- Stockée dans les préférences du navigateur
- Optionnelle (pas bloquante)

---

## 📦 STRUCTURE DU PROJET

```
mv3pro-chantiers/
├── public/
│   ├── .htaccess                  # Reverse proxy Apache
│   ├── manifest.json              # PWA manifest
│   ├── sw.js                      # Service Worker
│   └── assets/
│       └── icons/                 # Icônes PWA
│
├── src/
│   ├── components/                # Composants réutilisables
│   │   ├── BottomNav.tsx         # Navigation en bas
│   │   ├── CameraCapture.tsx     # Capture photo
│   │   ├── TimeTracker.tsx       # Suivi du temps
│   │   └── VoiceRecorder.tsx     # Notes vocales
│   │
│   ├── contexts/                  # Contexts React
│   │   ├── AuthContext.tsx       # Authentification
│   │   └── OfflineContext.tsx    # Gestion offline
│   │
│   ├── screens/                   # Écrans principaux
│   │   ├── Dashboard.tsx         # Tableau de bord
│   │   ├── LoginScreen.tsx       # Connexion
│   │   ├── NewReportScreen.tsx   # Nouveau rapport
│   │   ├── ReportsScreen.tsx     # Liste rapports
│   │   ├── RegieScreen.tsx       # Régie
│   │   ├── SensPoseScreen.tsx    # Sens de pose
│   │   ├── PlanningScreen.tsx    # Planning
│   │   ├── MaterielScreen.tsx    # Matériel
│   │   └── ProfileScreen.tsx     # Profil
│   │
│   ├── types/                     # Types TypeScript
│   │   └── index.ts              # Définitions types
│   │
│   ├── utils/                     # Utilitaires
│   │   ├── api.ts                # Appels API Dolibarr
│   │   ├── db.ts                 # IndexedDB
│   │   └── storage.ts            # LocalStorage
│   │
│   ├── App.tsx                    # Composant racine
│   ├── main.tsx                   # Point d'entrée
│   └── index.css                  # Styles globaux
│
├── .env                           # Variables d'environnement
├── package.json                   # Dépendances
├── tsconfig.json                  # Config TypeScript
├── vite.config.ts                 # Config Vite
├── tailwind.config.js             # Config Tailwind
│
└── Documentation/
    ├── README_DEPLOY.md           # Guide de déploiement
    ├── LISEZ_MOI_MAINTENANT.txt   # Guide rapide
    └── CAHIER_DES_CHARGES_COMPLET.md  # Ce document
```

---

## 🎨 DESIGN ET UX

### 1. Design system

**Couleurs principales** :
```css
--color-primary: #2563eb;      /* Bleu */
--color-success: #10b981;      /* Vert */
--color-warning: #f59e0b;      /* Orange */
--color-danger: #ef4444;       /* Rouge */
--color-gray: #6b7280;         /* Gris */
```

**Typographie** :
- Font principale : System UI (-apple-system, Segoe UI, Roboto)
- Tailles : 12px, 14px, 16px, 20px, 24px, 32px
- Poids : 400 (normal), 500 (medium), 600 (semibold), 700 (bold)

### 2. Composants UI

**Boutons** :
```css
.btn-primary    # Bleu, actions principales
.btn-secondary  # Gris, actions secondaires
.btn-danger     # Rouge, actions destructives
.btn-success    # Vert, validations
```

**Cards** :
```css
.card-premium   # Carte avec ombre et bordure
```

**Inputs** :
```css
.input-premium  # Champ avec bordure et focus
```

### 3. Responsive design

**Breakpoints** :
- Mobile : < 768px
- Tablet : 768px - 1024px
- Desktop : > 1024px

**Navigation** :
- Mobile : Bottom navigation (5 icônes)
- Desktop : Sidebar (optionnel, non implémenté)

### 4. Animations

**Classes Tailwind** :
```css
.animate-fade-in      # Apparition en fondu
.animate-slide-up     # Glissement vers le haut
.active:scale-95      # Réduction au clic
.hover:bg-gray-100    # Survol
```

### 5. Accessibilité

- Contraste WCAG AA respecté
- Boutons et liens focusables
- Labels sur tous les inputs
- Alt text sur toutes les images
- Taille de police min : 14px
- Zone de clic min : 44x44px

---

## 📊 TYPES DE DONNÉES

### 1. User (Utilisateur)

**Fichier** : `src/types/index.ts`

```typescript
interface User {
  id: string;                    // ID local
  dolibarr_user_id: number;      // ID Dolibarr
  email: string;
  name: string;
  phone?: string;
  biometric_enabled: boolean;
  preferences: UserPreferences;
}

interface UserPreferences {
  theme: 'light' | 'dark' | 'auto';
  notifications: boolean;
  autoSave: boolean;
  cameraQuality: 'low' | 'medium' | 'high';
  voiceLanguage: string;
}
```

### 2. Report (Rapport d'intervention)

```typescript
interface Report {
  id: string;
  user_id: string;
  date: string;                  // YYYY-MM-DD
  start_time: string;            // HH:MM
  end_time: string;              // HH:MM
  client_name: string;
  project_id?: number;           // ID Dolibarr
  description: string;
  observations?: string;
  location?: Location;
  photos: Photo[];
  voice_notes: VoiceNote[];
  status: 'draft' | 'pending' | 'sent';
  created_at: string;            // ISO 8601
  updated_at: string;            // ISO 8601
  dolibarr_id?: number;          // ID Fichinter
}
```

### 3. Photo

```typescript
interface Photo {
  id: string;
  data: string;                  // base64
  caption?: string;
  taken_at: string;              // ISO 8601
  location?: Location;
  uploaded: boolean;
  dolibarr_url?: string;
}
```

### 4. VoiceNote

```typescript
interface VoiceNote {
  id: string;
  audio: string;                 // base64
  transcription: string;
  duration: number;              // secondes
  created_at: string;            // ISO 8601
}
```

### 5. Regie (Fiche de régie)

```typescript
interface Regie {
  id: string;
  user_id: string;
  date: string;                  // YYYY-MM-DD
  project_id?: number;
  client_name: string;
  hours: number;
  materials: Material[];
  description: string;
  signature?: string;            // base64
  status: 'draft' | 'sent';
  created_at: string;
  updated_at: string;
  dolibarr_id?: number;
}

interface Material {
  id: string;
  name: string;
  quantity: number;
  unit: string;
}
```

### 6. SensPose (Sens de pose carrelage)

```typescript
interface SensPose {
  id: string;
  user_id: string;
  date: string;
  proposal_id?: number;          // ID Devis Dolibarr
  client_name: string;
  products: SensPoseProduct[];
  photos: Photo[];
  schema?: string;               // base64 du schéma
  signature?: string;            // base64
  status: 'draft' | 'sent';
  created_at: string;
  updated_at: string;
}

interface SensPoseProduct {
  id: string;
  product_id: number;            // ID Dolibarr
  product_ref: string;
  product_label: string;
  quantity: number;
  sens_pose: string;             // Description
}
```

### 7. SyncQueueItem (File de synchronisation)

```typescript
interface SyncQueueItem {
  id: string;
  user_id: string;
  entity_type: 'report' | 'regie' | 'sens_pose' | 'photo';
  entity_id: string;
  action: 'create' | 'update' | 'delete';
  payload: any;
  priority: number;              // 1-10
  status: 'pending' | 'processing' | 'completed' | 'failed';
  attempts: number;
  last_attempt?: string;         // ISO 8601
  created_at: string;
}
```

---

## 🚀 DÉPLOIEMENT

### 1. Prérequis

**Serveur** :
- Apache 2.4+
- PHP 7.4+ (pour Dolibarr)
- MySQL/MariaDB (pour Dolibarr)
- Certificat SSL (HTTPS)
- mod_rewrite activé
- mod_proxy activé
- mod_headers activé

**Accès** :
- FTP vers app.mv-3pro.ch
- Accès admin Dolibarr (crm.mv-3pro.ch)

### 2. Compilation

```bash
# Installation des dépendances
npm install

# Build de production
npm run build

# Résultat dans dist/
```

### 3. Déploiement FTP

**Structure cible** :
```
/var/www/app.mv-3pro.ch/pro/
├── index.html
├── .htaccess
├── manifest.json
├── sw.js
└── assets/
    ├── index-[hash].js
    ├── index-[hash].css
    └── ...
```

**Commandes** :
```bash
# Via FTP client (FileZilla, WinSCP, etc.)
# Copier TOUT le contenu de dist/ dans /pro/
```

### 4. Configuration Apache

**VirtualHost** :
```apache
<VirtualHost *:443>
    ServerName app.mv-3pro.ch
    DocumentRoot /var/www/app.mv-3pro.ch

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/app.mv-3pro.ch/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/app.mv-3pro.ch/privkey.pem

    <Directory /var/www/app.mv-3pro.ch>
        AllowOverride All
        Require all granted
    </Directory>

    # Proxy vers Dolibarr
    ProxyPreserveHost On
    ProxyPass /api/ https://crm.mv-3pro.ch/api/
    ProxyPassReverse /api/ https://crm.mv-3pro.ch/api/
</VirtualHost>
```

### 5. Configuration Dolibarr

**Activation de l'API REST** :
1. Menu Accueil → Configuration → Modules
2. Activer le module "API REST"
3. Menu Utilisateur → Modifier ma fiche
4. Onglet "Clé API" → Générer une clé

**Modules requis** :
- Interventions (Fichinter)
- Agenda
- Projets
- Propositions commerciales
- GED (Gestion électronique de documents)

### 6. Tests post-déploiement

**Checklist** :

- [ ] Application accessible : https://app.mv-3pro.ch/pro/
- [ ] Proxy fonctionne : `curl https://app.mv-3pro.ch/api/index.php/status`
- [ ] Connexion avec DOLAPIKEY
- [ ] Création d'un rapport
- [ ] Upload de photo
- [ ] Mode offline (désactiver réseau)
- [ ] Synchronisation (réactiver réseau)
- [ ] TimeTracker (Start/Pause/Stop)
- [ ] Service Worker installé (F12 → Application)

---

## 🔧 MAINTENANCE

### 1. Logs

**Apache** :
```bash
tail -f /var/log/apache2/access.log
tail -f /var/log/apache2/error.log
```

**Console navigateur** :
- F12 → Console
- Affiche erreurs JavaScript
- Affiche requêtes réseau

### 2. Mise à jour de l'application

```bash
# 1. Modifier le code source
# 2. Rebuild
npm run build

# 3. Déployer via FTP
# Remplacer le contenu de /pro/

# 4. Vider le cache
# Ctrl + Shift + R (navigateur)
# Ou incrémenter version dans manifest.json
```

### 3. Mise à jour de Dolibarr

**Compatibilité API** :
- L'application utilise l'API REST standard
- Compatible Dolibarr 13.0+
- Tester après chaque mise à jour Dolibarr

### 4. Sauvegarde

**À sauvegarder** :
- Code source (Git recommandé)
- Base Dolibarr (mysqldump)
- Documents Dolibarr (répertoire documents/)

**Fréquence recommandée** :
- Code : À chaque commit
- Dolibarr : Quotidienne (automatisée)

### 5. Monitoring

**Indicateurs à surveiller** :
- Disponibilité de l'application (uptime)
- Temps de réponse API (<500ms)
- Erreurs 500 dans les logs Apache
- Espace disque (photos)
- Utilisation CPU/RAM

**Outils recommandés** :
- Uptime Robot (monitoring)
- Google Analytics (usage)
- Sentry (erreurs JavaScript)

---

## 📋 CONTRAINTES ET LIMITATIONS

### 1. Contraintes techniques

| Contrainte | Détail |
|------------|--------|
| **Pas de backend custom** | Uniquement API REST Dolibarr |
| **Pas de SSH** | Déploiement FTP uniquement |
| **Pas de Node.js serveur** | Application statique uniquement |
| **Pas de Supabase** | Pas de base externe |
| **Pas de JWT custom** | DOLAPIKEY uniquement |

### 2. Limitations fonctionnelles

**Mode offline** :
- Pas de synchronisation en temps réel
- Conflits possibles (résolution manuelle)
- Photos limitées par la taille du stockage navigateur (50 MB typique)

**Compatibilité navigateur** :
- Chrome 90+ (recommandé)
- Firefox 88+
- Safari 14+
- Edge 90+
- Pas de support IE11

**Permissions requises** :
- Caméra (pour photos)
- Microphone (pour notes vocales)
- Géolocalisation (optionnelle)
- Stockage local (obligatoire)

### 3. Sécurité

**DOLAPIKEY** :
- Stockée en clair dans localStorage
- Pas de rotation automatique
- Révocable manuellement dans Dolibarr

**HTTPS** :
- Obligatoire en production
- Let's Encrypt recommandé
- Certificat à renouveler tous les 90 jours

### 4. Performance

**Taille de l'application** :
- JS : ~207 KB (gzippé : ~61 KB)
- CSS : ~26 KB (gzippé : ~5 KB)
- Total : ~233 KB

**Temps de chargement** (3G) :
- First Contentful Paint : ~2s
- Time to Interactive : ~4s

**Optimisations** :
- Code splitting (possible amélioration future)
- Lazy loading des images
- Service Worker pour cache

---

## 🎯 ÉVOLUTIONS FUTURES

### 1. Fonctionnalités prévues

**Court terme** :
- [ ] Export PDF des rapports
- [ ] Envoi par email
- [ ] Signature électronique
- [ ] Templates de rapports
- [ ] Statistiques avancées

**Moyen terme** :
- [ ] Module Dolibarr custom (mv3planning)
- [ ] Sauvegarde des heures dans Timesheet
- [ ] Notifications push
- [ ] Mode sombre
- [ ] Synchronisation multi-device

**Long terme** :
- [ ] Application mobile native (React Native)
- [ ] Mode hors-ligne avancé (conflict resolution)
- [ ] IA pour transcription vocale améliorée
- [ ] Reconnaissance d'image (OCR)
- [ ] Tableau de bord analytics

### 2. Améliorations techniques

**Performance** :
- Lazy loading des composants
- Virtual scrolling pour grandes listes
- Optimisation des images (WebP)
- CDN pour assets statiques

**Sécurité** :
- Chiffrement des données en local
- Authentification biométrique
- Rotation automatique des clés API
- Audit de sécurité

**UX/UI** :
- Mode sombre
- Thèmes personnalisables
- Animations avancées
- Accessibilité améliorée (WCAG AAA)

---

## 📚 ANNEXES

### Annexe A : Commandes utiles

```bash
# Développement
npm run dev              # Serveur de développement
npm run build            # Build de production
npm run preview          # Prévisualiser le build

# Tests
npm run lint             # Lint du code
npm run type-check       # Vérification TypeScript

# FTP
lftp -u user,pass app.mv-3pro.ch
mirror -R dist/ /pro/

# Apache
sudo a2enmod rewrite
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod headers
sudo systemctl restart apache2

# Dolibarr
php scripts/user/sync_users_ldap2dolibarr.php
mysql dolibarr < backup.sql
```

### Annexe B : Variables d'environnement

**Fichier** : `.env`

```env
# API Dolibarr
VITE_API_BASE=/api/index.php
VITE_DEFAULT_DOLIBARR_URL=https://crm.mv-3pro.ch

# Mode debug (optionnel)
VITE_DEBUG=false

# Durée cache (optionnel)
VITE_CACHE_DURATION=3600

# Upload max size (optionnel)
VITE_MAX_FILE_SIZE=10485760
```

### Annexe C : Dépendances

**Production** :
```json
{
  "@tanstack/react-query": "^5.17.19",
  "lucide-react": "^0.309.0",
  "react": "^18.2.0",
  "react-dom": "^18.2.0",
  "react-hot-toast": "^2.6.0",
  "react-router-dom": "^6.21.2"
}
```

**Développement** :
```json
{
  "@types/react": "^18.2.48",
  "@types/react-dom": "^18.2.18",
  "@vitejs/plugin-react": "^4.2.1",
  "autoprefixer": "^10.4.17",
  "postcss": "^8.4.33",
  "tailwindcss": "^3.4.1",
  "typescript": "^5.3.3",
  "vite": "^5.0.11"
}
```

### Annexe D : Support navigateurs

| Navigateur | Version min | Support PWA | Service Worker |
|------------|-------------|-------------|----------------|
| Chrome | 90+ | ✅ | ✅ |
| Firefox | 88+ | ✅ | ✅ |
| Safari | 14+ | ✅ | ✅ |
| Edge | 90+ | ✅ | ✅ |
| Opera | 76+ | ✅ | ✅ |
| Samsung Internet | 14+ | ✅ | ✅ |
| IE 11 | - | ❌ | ❌ |

### Annexe E : Licence

**Propriétaire** : MV3 Pro
**Développement** : 2024
**Licence** : Propriétaire (tous droits réservés)

---

## 📞 CONTACTS ET SUPPORT

### Support technique

**En cas de problème** :
1. Consulter les logs Apache
2. Consulter la console navigateur (F12)
3. Tester l'API directement avec curl
4. Vérifier le fichier .htaccess

### Documentation

- **README_DEPLOY.md** : Guide de déploiement complet
- **LISEZ_MOI_MAINTENANT.txt** : Guide rapide
- **Ce document** : Cahier des charges complet

---

## ✅ VALIDATION ET RECETTE

### Checklist de validation

**Authentification** :
- [ ] Connexion avec DOLAPIKEY valide
- [ ] Rejet DOLAPIKEY invalide
- [ ] Déconnexion
- [ ] Persistance de session
- [ ] Message d'erreur clair

**Rapports** :
- [ ] Création de rapport
- [ ] Modification de rapport
- [ ] Suppression de rapport
- [ ] Liste des rapports
- [ ] Sauvegarde automatique
- [ ] Photos
- [ ] Notes vocales
- [ ] Géolocalisation

**Suivi des heures** :
- [ ] Démarrer le timer
- [ ] Pause
- [ ] Reprendre
- [ ] Stop
- [ ] Affichage correct du temps
- [ ] Persistance localStorage
- [ ] Historique des périodes

**Mode offline** :
- [ ] Création en mode offline
- [ ] Stockage en IndexedDB
- [ ] Indicateur offline visible
- [ ] Synchronisation au retour en ligne
- [ ] Notification de synchronisation
- [ ] Gestion des conflits

**Performance** :
- [ ] Chargement < 3s (3G)
- [ ] Responsive mobile
- [ ] Responsive tablet
- [ ] Responsive desktop
- [ ] Pas de lag dans l'interface
- [ ] Service Worker actif

**Sécurité** :
- [ ] HTTPS actif
- [ ] CORS configuré
- [ ] Headers de sécurité
- [ ] DOLAPIKEY protégée
- [ ] Pas de fuite de données

### Tests utilisateur

**Scénarios** :
1. Connexion → Création rapport → Ajout photo → Sauvegarde
2. Connexion → Mode offline → Création rapport → Mode online → Sync
3. Connexion → TimeTracker → Start → Pause → Reprendre → Stop
4. Connexion → Planning → Voir événements
5. Connexion → Profil → Déconnexion

---

**FIN DU CAHIER DES CHARGES**

**Version** : 1.0.0
**Date** : 2024-12-26
**Statut** : Production ready ✅
