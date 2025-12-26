# 🚀 GUIDE DE PREVIEW LOCAL - MV3 PRO PWA

## ✅ Configuration effectuée

### 1. Proxy de développement configuré
Le fichier `vite.config.ts` a été mis à jour avec un proxy qui redirige automatiquement :
```
http://localhost:5173/api/* → https://crm.mv-3pro.ch/api/*
```

### 2. Variables d'environnement configurées
Le fichier `.env` contient :
```env
VITE_DOLIBARR_URL=https://crm.mv-3pro.ch
VITE_API_BASE=/api/index.php
VITE_DEBUG=true
```

---

## 🎯 Comment tester l'application

### Étape 1 : Le serveur de développement est déjà lancé

L'application est accessible à l'adresse suivante :
```
http://localhost:5173/pro/
```

### Étape 2 : Obtenir une clé API Dolibarr (DOLAPIKEY)

**Si vous avez déjà accès à Dolibarr :**

1. Connectez-vous à votre Dolibarr : `https://crm.mv-3pro.ch`
2. Cliquez sur votre nom d'utilisateur (en haut à droite)
3. Cliquez sur "Modifier ma fiche"
4. Allez dans l'onglet "API" ou "Clés d'API"
5. Cliquez sur "Générer une nouvelle clé"
6. Copiez la clé générée (ex: `abc123def456...`)

**Si vous n'avez pas encore de Dolibarr :**
Vous devez d'abord installer Dolibarr sur votre serveur.

### Étape 3 : Se connecter à l'application

1. Ouvrez : `http://localhost:5173/pro/`
2. Vous verrez l'écran de connexion
3. Collez votre DOLAPIKEY dans le champ
4. Cliquez sur "Se connecter"

---

## 🧪 Fonctionnalités à tester

### ✅ Authentification
- [ ] Connexion avec DOLAPIKEY valide
- [ ] Message d'erreur si clé invalide
- [ ] Persistance de la session (rechargez la page)
- [ ] Déconnexion

### ✅ Dashboard
- [ ] Affichage du nom d'utilisateur
- [ ] Statistiques (rapports, heures)
- [ ] Navigation vers les différents modules

### ✅ Rapports d'intervention
- [ ] Créer un nouveau rapport
- [ ] Remplir les champs (date, heure, client, description)
- [ ] Démarrer le TimeTracker
- [ ] Mettre en pause
- [ ] Reprendre
- [ ] Arrêter
- [ ] Vérifier que le temps est bien calculé
- [ ] Sauvegarder le rapport

### ✅ Capture photo
- [ ] Cliquer sur "Ajouter une photo"
- [ ] Autoriser l'accès à la caméra
- [ ] Prendre une photo
- [ ] Voir l'aperçu
- [ ] Ajouter au rapport

### ✅ Note vocale
- [ ] Cliquer sur "Note vocale"
- [ ] Autoriser l'accès au microphone
- [ ] Parler en français
- [ ] Voir la transcription
- [ ] Insérer dans la description

### ✅ Mode offline
- [ ] Créer un rapport
- [ ] Ouvrir F12 → Network → Cocher "Offline"
- [ ] Essayer de sauvegarder
- [ ] Voir l'indicateur "Hors ligne"
- [ ] Décocher "Offline"
- [ ] Voir la synchronisation automatique

### ✅ Planning
- [ ] Accéder à l'onglet Planning
- [ ] Voir les événements du jour
- [ ] Voir les interventions planifiées

### ✅ Profil
- [ ] Accéder à l'onglet Profil
- [ ] Voir les informations personnelles
- [ ] Se déconnecter

---

## 🛠️ Personnalisation de la configuration

### Changer l'URL du serveur Dolibarr

Si votre Dolibarr est sur une autre URL, modifiez le fichier `.env` :

```env
VITE_DOLIBARR_URL=https://votre-dolibarr.com
```

### Activer/Désactiver le mode debug

Pour voir plus de logs dans la console :

```env
VITE_DEBUG=true
```

Pour désactiver les logs :

```env
VITE_DEBUG=false
```

### Tester avec un Dolibarr local

Si vous avez un Dolibarr en local (ex: http://localhost:8080), modifiez `.env` :

```env
VITE_DOLIBARR_URL=http://localhost:8080
```

---

## 🔍 Déboguer les problèmes

### L'application ne charge pas
1. Vérifiez que le serveur de développement est lancé
2. Ouvrez `http://localhost:5173/pro/` dans votre navigateur
3. Ouvrez F12 pour voir les erreurs

### Erreur "DOLAPIKEY invalide"
1. Vérifiez que la clé est correcte (pas d'espace avant/après)
2. Vérifiez que l'API REST est activée dans Dolibarr
3. Vérifiez que votre utilisateur a les droits API

**Comment activer l'API REST dans Dolibarr :**
1. Menu Accueil → Configuration → Modules
2. Recherchez "API REST"
3. Cliquez sur "Activer"

### Erreur "Network Error" ou "CORS"
Cela signifie que le proxy ne fonctionne pas correctement.

**Solution :**
1. Vérifiez que `.env` contient la bonne URL
2. Relancez le serveur de développement
3. Vérifiez que Dolibarr est accessible depuis votre navigateur

### Les photos ne s'affichent pas
1. Autorisez l'accès à la caméra dans votre navigateur
2. Si vous êtes sur HTTPS, vérifiez le certificat
3. Sur mobile, testez avec l'appareil photo arrière

### Le TimeTracker ne fonctionne pas
1. Vérifiez dans F12 → Console s'il y a des erreurs
2. Vérifiez que localStorage est activé dans votre navigateur
3. Essayez de vider le cache : Paramètres → Vider le cache

---

## 📱 Tester en tant que PWA

### Sur ordinateur (Chrome)

1. Ouvrez l'application : `http://localhost:5173/pro/`
2. Cliquez sur l'icône "Installer" dans la barre d'adresse (à droite)
3. Cliquez sur "Installer"
4. L'application s'ouvre dans une fenêtre séparée

### Sur mobile

1. Ouvrez l'application dans votre navigateur mobile
2. **iOS (Safari)** :
   - Cliquez sur le bouton Partager
   - Choisissez "Sur l'écran d'accueil"
3. **Android (Chrome)** :
   - Menu → "Ajouter à l'écran d'accueil"

### Tester le mode offline

1. Installez l'application comme PWA
2. Ouvrez l'application
3. Activez le mode Avion sur votre appareil
4. Essayez de créer un rapport
5. Désactivez le mode Avion
6. Vérifiez que le rapport est synchronisé

---

## 🎨 Aperçu des écrans

### 1. Écran de connexion
```
┌─────────────────────────┐
│   MV3 Pro - Chantiers   │
│                         │
│  ┌──────────────────┐  │
│  │  DOLAPIKEY       │  │
│  │  [Votre clé API] │  │
│  └──────────────────┘  │
│                         │
│  [  Se connecter  ]    │
│                         │
│  Comment obtenir ma clé?│
└─────────────────────────┘
```

### 2. Dashboard
```
┌─────────────────────────┐
│  Bonjour, [Nom]    🟢  │
│                         │
│  📊 Rapports aujourd'hui│
│      5 interventions    │
│                         │
│  ⏱️ Heures cette semaine│
│      32h 15m            │
│                         │
│  🔄 En attente de sync  │
│      2 éléments         │
│                         │
│ ┌────┬────┬────┬────┐ │
│ │📝 │📅 │📦 │👤 │ │
│ │Rap│Plan│Mat│Pro │ │
│ └────┴────┴────┴────┘ │
└─────────────────────────┘
```

### 3. Nouveau rapport
```
┌─────────────────────────┐
│ ← Nouveau rapport       │
│                         │
│ Date: [15/01/2024]     │
│                         │
│ ⏱️ TimeTracker          │
│    00:00:00            │
│    [▶️ Démarrer]       │
│                         │
│ Client: [___________]  │
│                         │
│ Description:           │
│ [________________]     │
│                         │
│ 📸 [Photo] 🎤 [Audio]  │
│                         │
│ [ 💾 Enregistrer ]     │
└─────────────────────────┘
```

---

## 🔐 Sécurité

### Données stockées localement

L'application stocke ces données dans votre navigateur :

- **localStorage** :
  - DOLAPIKEY (votre clé API)
  - Informations utilisateur
  - Temps de travail (TimeTracker)

- **IndexedDB** :
  - Rapports en attente de synchronisation
  - Photos non envoyées
  - Cache des données

### Effacer les données

Pour effacer toutes les données :

1. Menu Profil → "Vider le cache"
2. OU F12 → Application → Storage → Clear all

---

## 📞 Support

### Problème avec l'application

1. Vérifiez la console (F12)
2. Vérifiez les logs réseau (F12 → Network)
3. Consultez le cahier des charges : `CAHIER_DES_CHARGES_COMPLET.md`

### Problème avec Dolibarr

1. Vérifiez que Dolibarr est accessible
2. Vérifiez que l'API REST est activée
3. Vérifiez les logs Apache de Dolibarr

---

## ✅ Checklist avant déploiement

Avant de déployer en production, vérifiez que :

- [ ] L'authentification fonctionne
- [ ] Les rapports sont créés dans Dolibarr
- [ ] Les photos sont uploadées
- [ ] Le TimeTracker fonctionne correctement
- [ ] Le mode offline fonctionne
- [ ] La synchronisation fonctionne
- [ ] L'application fonctionne sur mobile
- [ ] L'application peut être installée comme PWA
- [ ] Les permissions (caméra, micro) sont demandées
- [ ] Pas d'erreur dans la console

---

## 🚀 Prochaines étapes

Une fois que tout fonctionne en local :

1. ✅ **Build de production** : `npm run build`
2. 📤 **Déploiement FTP** : Copier le contenu de `dist/` vers votre serveur
3. 🔧 **Configuration Apache** : Configurer le reverse proxy
4. 🧪 **Tests en production** : Tester sur `https://app.mv-3pro.ch/pro/`
5. 📱 **Installation PWA** : Installer sur les appareils des utilisateurs

Consultez le fichier `README_DEPLOY.md` pour le guide de déploiement complet.

---

**Version** : 1.0.0
**Date** : 2024-12-26
**Statut** : Prêt pour les tests ✅
