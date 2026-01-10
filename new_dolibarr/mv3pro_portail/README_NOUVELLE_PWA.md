# Migration vers la nouvelle PWA React - TERMINÉE

## Résumé

La **nouvelle PWA React** est maintenant **prête et opérationnelle**. L'ancienne version mobile PHP a été **désactivée** avec des redirections automatiques.

---

## URLs

### Application principale (à utiliser)
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
```

### Mode Debug
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/DEBUG_MODE.html
```

### Anciennes URLs (redirigent automatiquement)
- `https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/` → Redirige vers la nouvelle PWA
- `https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/login_mobile.php` → Redirige vers la nouvelle PWA
- `https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/dashboard_mobile.php` → Redirige vers la nouvelle PWA

---

## Ce qui a été fait

### 1. Build de la PWA React
- ✅ Installation des dépendances npm
- ✅ Build de production (TypeScript + Vite)
- ✅ Génération des fichiers optimisés dans `pwa_dist/`
- ✅ Service Worker pour le mode offline
- ✅ Manifest PWA pour l'installation sur mobile

### 2. Redirections automatiques
Les fichiers suivants redirigent maintenant vers la nouvelle PWA:
- ✅ `mobile_app/index.php`
- ✅ `mobile_app/dashboard_mobile.php`
- ✅ `mobile_app/login_mobile.php`

Les **API restent accessibles** pour la PWA (pas de redirection sur `/mobile_app/api/`)

### 3. Outils de debug
- ✅ Page `DEBUG_MODE.html` pour activer les logs
- ✅ Affichage du token actuel
- ✅ Effacement du cache et token

### 4. Documentation
- ✅ `MIGRATION_PWA.md` - Guide complet de migration
- ✅ `README_NOUVELLE_PWA.md` - Ce fichier

---

## Architecture

```
mv3pro_portail/
├── pwa/                          # Code source React + TypeScript
│   ├── src/
│   │   ├── components/          # Composants React
│   │   ├── pages/               # Pages de l'application
│   │   ├── lib/                 # API client
│   │   └── contexts/            # Contextes React (Auth)
│   ├── package.json
│   └── vite.config.ts
│
├── pwa_dist/                     # Build de production (à utiliser)
│   ├── index.html
│   ├── assets/
│   ├── sw.js                    # Service Worker
│   ├── manifest.webmanifest     # Manifest PWA
│   └── DEBUG_MODE.html          # Outil de debug
│
├── api/v1/                       # API REST pour la PWA
│   ├── _bootstrap.php           # Init + Auth
│   ├── planning_view.php        # Détails événement
│   ├── rapports_create.php      # Créer rapport
│   └── ...
│
├── mobile_app/                   # ANCIEN (redirige vers pwa_dist/)
│   ├── index.php                # → Redirection 301
│   ├── dashboard_mobile.php     # → Redirection 301
│   ├── login_mobile.php         # → Redirection 301
│   └── api/                     # API toujours actives
│
└── MIGRATION_PWA.md             # Guide de migration
```

---

## Fonctionnalités de la nouvelle PWA

### Pages disponibles
1. **Login** (`/login`)
   - Authentification par email/mot de passe
   - Sauvegarde du token en localStorage
   - Validation côté client + serveur

2. **Dashboard** (`/dashboard`)
   - Vue d'ensemble
   - Raccourcis vers les fonctionnalités

3. **Planning** (`/planning`)
   - Liste des événements
   - Filtrage par date
   - Détails complets avec onglets:
     - **Détails**: Infos, dates, lieu, progression
     - **Photos**: Galerie d'images avec zoom
     - **Fichiers**: Documents PDF/Excel avec preview

4. **Rapports** (`/rapports`)
   - Liste des rapports
   - Création de nouveaux rapports
   - Upload de photos
   - Signature

5. **Notifications** (`/notifications`)
   - Alertes en temps réel
   - Marquer comme lu

6. **Profil** (`/profil`)
   - Informations du compte
   - Déconnexion

### Fonctionnalités techniques
- ✅ **Mode offline** (Service Worker)
- ✅ **Installation sur l'écran d'accueil**
- ✅ **Navigation fluide** (React Router)
- ✅ **Design responsive** (mobile-first)
- ✅ **Authentification sécurisée** (Bearer token)
- ✅ **Gestion d'état** (React Context)
- ✅ **TypeScript** (typage fort)

---

## API utilisées par la PWA

### Authentification
- `POST /mobile_app/api/auth.php?action=login`
- `POST /mobile_app/api/auth.php?action=logout`
- `GET /api/v1/me.php`

### Planning
- `GET /api/v1/planning.php?from=YYYY-MM-DD&to=YYYY-MM-DD`
- `GET /api/v1/planning_view.php?id=123`
- `GET /api/v1/planning_file.php?id=123&file=photo.jpg`

### Rapports
- `GET /api/v1/rapports.php?limit=50&page=1`
- `GET /api/v1/rapports_view.php?id=123`
- `POST /api/v1/rapports_create.php`
- `POST /api/v1/rapports_photos_upload.php`

Toutes les API utilisent l'authentification **Bearer token** ou **X-Auth-Token**.

---

## Instructions de test

### 1. Ouvrir sur mobile
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
```

### 2. Se connecter
- Email: votre@email.ch
- Mot de passe: ******

### 3. Tester les fonctionnalités
- [ ] Dashboard s'affiche
- [ ] Planning affiche les événements
- [ ] Clic sur un événement → 3 onglets (Détails, Photos, Fichiers)
- [ ] Navigation en bas fonctionne
- [ ] Mode offline fonctionne (couper internet, rafraîchir)

### 4. Si erreur 404
1. Ouvrir `DEBUG_MODE.html`
2. Cliquer "Effacer le token"
3. Fermer et rouvrir le navigateur
4. Se reconnecter

---

## Désactivation de l'ancienne version

### État actuel
- ✅ Redirections 301 en place
- ✅ Les anciennes URLs redirigent automatiquement
- ⚠️ Les API restent actives (utilisées par la nouvelle PWA)

### Pour désactiver complètement l'ancienne version

**Option 1: Renommer le dossier (sauf API)**
```bash
# Sauvegarder les API
cp -r mobile_app/api mobile_app_api_backup

# Renommer l'ancien dossier
mv mobile_app mobile_app_OLD

# Restaurer les API
mkdir mobile_app
mv mobile_app_api_backup mobile_app/api
```

**Option 2: Supprimer les anciens fichiers PHP**
```bash
cd mobile_app/
# Garder uniquement les API et includes
find . -name "*.php" -not -path "./api/*" -not -path "./includes/*" -delete
```

---

## Rebuild après modifications

Si vous modifiez le code source React:

```bash
cd /path/to/mv3pro_portail/pwa

# Installer les dépendances (première fois)
npm install

# Build de production
npm run build

# Les fichiers sont générés dans ../pwa_dist/
```

---

## Résolution des problèmes

### Erreur 404
- Vider le cache du navigateur
- Désinstaller et réinstaller la PWA
- Vérifier que les fichiers existent dans `pwa_dist/`

### Erreur 401 (Unauthorized)
- Le token a expiré → Se reconnecter
- Vérifier la session dans la BDD:
  ```sql
  SELECT * FROM llx_mv3_mobile_sessions
  WHERE expires_at > NOW()
  ORDER BY created_at DESC;
  ```

### Écran blanc
- Ouvrir la console (F12)
- Regarder les erreurs JavaScript
- Rebuild la PWA si nécessaire

### Les photos ne s'affichent pas
- Vérifier les permissions des dossiers `documents/`
- Vérifier l'URL générée dans l'API
- Tester l'URL directement dans le navigateur

---

## Support et maintenance

### Logs à surveiller
- **Apache error log**: `/var/log/apache2/error.log`
- **PHP error log**: `/var/log/php/error.log`
- **Console JavaScript**: F12 dans le navigateur

### Activer le mode debug
1. Ouvrir `DEBUG_MODE.html`
2. Cliquer "Activer le mode debug"
3. Ouvrir la console (F12)
4. Naviguer dans l'application
5. Observer les logs `[MV3PRO DEBUG]`

### Ajouter des logs API côté serveur
Modifier `api/v1/_bootstrap.php` - la variable `$is_debug` détecte le header `X-MV3-Debug: 1`

---

## Checklist finale

- [x] PWA buildée et déployée dans `pwa_dist/`
- [x] Redirections 301 en place
- [x] API accessibles et fonctionnelles
- [x] Mode debug disponible
- [x] Documentation complète
- [ ] Tests utilisateurs sur mobile
- [ ] Communication aux utilisateurs
- [ ] Surveillance des logs pendant 1 semaine
- [ ] Suppression définitive de l'ancienne version

---

## Prochaines étapes

1. **Tester sur plusieurs appareils**
   - iPhone (Safari)
   - Android (Chrome)
   - Tablette

2. **Communiquer aux utilisateurs**
   - Email avec la nouvelle URL
   - Formation si nécessaire

3. **Surveiller les logs**
   - Erreurs 404
   - Erreurs 401
   - Erreurs JavaScript

4. **Supprimer l'ancienne version après validation**
   - Après 1-2 semaines de test
   - Garder une sauvegarde

---

## Contact et support

Pour toute question ou problème:
1. Consulter `MIGRATION_PWA.md`
2. Activer le mode debug
3. Vérifier les logs serveur
4. Contacter le support technique
