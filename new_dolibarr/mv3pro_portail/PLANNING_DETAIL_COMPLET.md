# Planning - Détail complet des rendez-vous avec pièces jointes

## Vue d'ensemble

Le système de visualisation des rendez-vous a été complété avec :
- Affichage détaillé de tous les champs d'un événement
- Affichage du tiers, du projet, et de l'objet lié
- Gestion complète des pièces jointes (photos, PDF, etc.)
- Téléchargement sécurisé des fichiers

## Backend - Endpoints créés

### 1. Endpoint détail événement

**Fichier** : `/api/v1/planning_view.php`

**URL** : `GET /api/v1/planning_view.php?id=74049`

**Description** : Retourne toutes les informations d'un événement de planning (actioncomm)

**Réponse JSON** :
```json
{
  "id": 74049,
  "titre": "Installation carrelage",
  "type_code": "AC_RDV",
  "date_debut": "2026-01-15 09:00:00",
  "date_fin": "2026-01-15 17:00:00",
  "all_day": 0,
  "lieu": "Rue du Commerce 25, Genève",
  "description": "Installation du carrelage dans la cuisine",
  "progression": 50,

  "user": {
    "id": 15,
    "nom_complet": "Fernando Silva",
    "login": "fernando"
  },

  "societe": {
    "id": 1234,
    "nom": "MV-3 PRO SA",
    "type": 1
  },

  "projet": {
    "id": 789,
    "ref": "PRJ2024-001",
    "titre": "Rénovation cuisine"
  },

  "objet_lie": {
    "type": "commande",
    "type_label": "Commande",
    "id": 5678,
    "ref": "CMD2024-123"
  },

  "fichiers": [
    {
      "name": "photo_chantier_1.jpg",
      "size": 2458634,
      "size_human": "2.34 MB",
      "mime": "image/jpeg",
      "is_image": true,
      "url": "/custom/mv3pro_portail/api/v1/file.php?module=actioncomm&id=74049&file=photo_chantier_1.jpg"
    },
    {
      "name": "plan_installation.pdf",
      "size": 512843,
      "size_human": "500.82 KB",
      "mime": "application/pdf",
      "is_image": false,
      "url": "/custom/mv3pro_portail/api/v1/file.php?module=actioncomm&id=74049&file=plan_installation.pdf"
    }
  ]
}
```

**Champs retournés** :
- `id` : Identifiant de l'événement
- `titre` : Libellé de l'événement
- `type_code` : Code du type d'événement
- `date_debut` : Date/heure de début
- `date_fin` : Date/heure de fin
- `all_day` : 1 si journée entière, 0 sinon
- `lieu` : Lieu de l'événement
- `description` : Description/notes privées
- `progression` : Pourcentage de progression (0-100)
- `user` : Utilisateur assigné (si défini)
- `societe` : Société/tiers lié (si défini)
- `projet` : Projet lié (si défini)
- `objet_lie` : Objet lié (commande, facture, propal, etc.) (si défini)
- `fichiers` : Liste des fichiers joints

**Sécurité** :
- Token obligatoire (authentification)
- Vérification des permissions via `getEntity('agenda')`
- Seuls les événements de l'entité de l'utilisateur sont accessibles

---

### 2. Endpoint téléchargement fichiers

**Fichier** : `/api/v1/file.php`

**URL** : `GET /api/v1/file.php?module=actioncomm&id=74049&file=document.pdf`

**Description** : Sert les fichiers de manière sécurisée

**Paramètres** :
- `module` : Type de module (actioncomm, rapport, regie, sens_pose)
- `id` : ID de l'objet
- `file` : Nom du fichier

**Sécurité** :
- Token obligatoire
- Vérification que l'objet existe
- Nettoyage du nom de fichier (protection contre path traversal)
- Vérification que le fichier est dans le bon répertoire
- Logs d'accès

**Réponse** :
- Headers HTTP appropriés (Content-Type, Content-Length, etc.)
- Fichier en streaming

**Modules supportés** :
- `actioncomm` : Fichiers des événements de planning
- `rapport` : Fichiers des rapports journaliers
- `regie` : Fichiers des bons de régie
- `sens_pose` : Fichiers des plans de pose

---

## Frontend - Page détail

**Fichier** : `/pwa/src/pages/PlanningDetail.tsx`

**Route** : `/#/planning/:id`

### Sections affichées

#### 1. Informations principales
- Titre de l'événement
- Date de début et de fin
- Journée entière (si applicable)
- Lieu
- Barre de progression (si > 0%)

#### 2. Utilisateur assigné
- Nom complet
- Login

#### 3. Société
- Nom de la société

#### 4. Projet
- Référence du projet
- Titre du projet

#### 5. Objet lié
- Type d'objet (Commande, Facture, etc.)
- Référence

#### 6. Description
- Notes privées de l'événement
- Affichage en texte préformaté (conserve les sauts de ligne)

#### 7. Fichiers joints
- Liste avec miniatures pour les images
- Icône générique pour les autres fichiers
- Nom du fichier
- Taille formatée (KB, MB, GB)
- Bouton "Ouvrir" pour chaque fichier

### Design

- Interface responsive mobile-first
- Cards avec padding et margins cohérents
- Icônes emoji pour chaque section
- Miniatures 48x48px pour les fichiers
- Boutons bleus pour les actions
- Gestion des états de chargement et d'erreur

---

## Utilisation

### Côté utilisateur (Fernando)

1. **Accéder au planning**
   - Ouvrir la PWA
   - Aller dans l'onglet "Planning"

2. **Voir un rendez-vous**
   - Cliquer sur un événement dans la liste
   - La page de détail s'affiche automatiquement

3. **Consulter les informations**
   - Toutes les infos sont affichées (dates, lieu, projet, etc.)
   - La description est visible
   - Les fichiers joints sont listés en bas

4. **Ouvrir un fichier**
   - Cliquer sur le bouton "Ouvrir" à côté du fichier
   - Le fichier s'ouvre dans un nouvel onglet
   - Les images s'affichent directement
   - Les PDF peuvent être téléchargés ou affichés

### Côté administrateur (Dolibarr)

1. **Créer un événement avec fichiers**
   - Dans Dolibarr : Agenda → Nouvel événement
   - Remplir les informations (titre, dates, projet, etc.)
   - Joindre des fichiers (photos, PDF, etc.)
   - Sauvegarder

2. **Les fichiers sont automatiquement disponibles**
   - Dolibarr stocke les fichiers dans : `documents/actioncomm/{id}/`
   - L'API les liste automatiquement
   - La PWA les affiche dans la section "Fichiers joints"

---

## Chemins des fichiers

### Backend

```
/custom/mv3pro_portail/
├── api/v1/
│   ├── planning_view.php    ← Détail événement
│   └── file.php              ← Téléchargement fichiers
```

### Frontend

```
/custom/mv3pro_portail/pwa/
├── src/
│   ├── pages/
│   │   └── PlanningDetail.tsx    ← Page détail
│   └── lib/
│       └── api.ts                ← Client API (ajout apiClient)
```

### Stockage des fichiers (Dolibarr)

```
DOL_DATA_ROOT/
├── actioncomm/
│   └── {id}/
│       ├── photo1.jpg
│       ├── photo2.jpg
│       └── document.pdf
├── mv3pro_portail/
│   ├── rapports/{id}/...
│   ├── regie/{id}/...
│   └── sens_pose/{id}/...
```

---

## Sécurité

### Backend

1. **Authentification** :
   - Token JWT obligatoire
   - Vérification via `require_auth()`

2. **Autorisation** :
   - Vérification de l'entité Dolibarr
   - Seuls les événements de l'utilisateur/entité sont accessibles

3. **Fichiers** :
   - Nettoyage du nom de fichier (`basename()`)
   - Protection contre path traversal (`..`, `/`, `\`)
   - Vérification que le fichier est dans le bon répertoire (`realpath()`)
   - Vérification de l'existence du fichier

4. **Logs** :
   - Chaque téléchargement est loggé
   - User ID + module + ID + nom de fichier

### Frontend

1. **Authentification** :
   - Token stocké dans localStorage
   - Envoyé dans chaque requête (header Authorization)

2. **Gestion des erreurs** :
   - 401 → Redirection vers login
   - 404 → Message "Événement non trouvé"
   - Autres → Message d'erreur générique

3. **Ouverture des fichiers** :
   - `window.open()` avec `_blank`
   - Pas de téléchargement automatique
   - L'utilisateur contrôle l'ouverture

---

## Tests

### Test endpoint planning_view.php

```bash
# Avec curl
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php?id=74049"
```

### Test endpoint file.php

```bash
# Télécharger une image
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/file.php?module=actioncomm&id=74049&file=photo.jpg" \
     --output photo.jpg
```

### Test depuis la PWA

1. Se connecter avec Fernando
2. Aller dans Planning
3. Cliquer sur un événement
4. Vérifier que tout s'affiche :
   - Titre, dates, lieu
   - Projet
   - Société
   - Description
   - Fichiers
5. Cliquer sur "Ouvrir" sur un fichier
6. Vérifier que le fichier s'ouvre/télécharge

---

## Dépannage

### Événement non trouvé

**Symptôme** : Message "Événement non trouvé"

**Causes possibles** :
- L'ID n'existe pas
- L'événement est dans une autre entité
- L'utilisateur n'a pas les permissions

**Solution** :
- Vérifier l'ID dans Dolibarr
- Vérifier l'entité de l'événement
- Vérifier les permissions de l'utilisateur

### Fichiers non affichés

**Symptôme** : Section "Fichiers joints" vide

**Causes possibles** :
- Aucun fichier joint dans Dolibarr
- Mauvais chemin de stockage
- Permissions fichiers

**Solution** :
```bash
# Vérifier que les fichiers existent
ls -la /path/to/dolibarr/documents/actioncomm/74049/

# Vérifier les permissions
chmod 644 /path/to/dolibarr/documents/actioncomm/74049/*
chmod 755 /path/to/dolibarr/documents/actioncomm/74049/
```

### Erreur téléchargement fichier

**Symptôme** : Erreur "Fichier non trouvé" ou "Accès refusé"

**Causes possibles** :
- Fichier supprimé
- Mauvaises permissions
- Path traversal détecté

**Solution** :
- Vérifier que le fichier existe
- Vérifier les permissions (644)
- Vérifier que le nom ne contient pas `..` ou `/`

### Mode debug

Pour activer les logs détaillés :

1. Dans Dolibarr : MV-3 PRO → Administration → Configuration PWA
2. Cocher "Activer le mode debug"
3. Sauvegarder
4. Les logs s'affichent dans la console navigateur et dans les fichiers PHP

---

## Améliorations futures possibles

### Court terme
- [ ] Ajouter la possibilité d'ajouter des fichiers depuis la PWA
- [ ] Afficher les participants (autres utilisateurs invités)
- [ ] Bouton "Appeler" si le contact a un téléphone
- [ ] Bouton "Itinéraire" pour le lieu (Google Maps)

### Moyen terme
- [ ] Modifier l'événement depuis la PWA
- [ ] Marquer comme terminé (progression 100%)
- [ ] Commenter l'événement
- [ ] Partager les fichiers par email

### Long terme
- [ ] Upload de photos depuis la caméra mobile
- [ ] Signature du client pour validation
- [ ] Création de rapports directement depuis l'événement
- [ ] Temps passé (timer intégré)

---

## Récapitulatif

### Ce qui fonctionne maintenant

- Affichage complet des détails d'un événement de planning
- Toutes les informations sont visibles (projet, tiers, description, etc.)
- Les fichiers joints sont listés avec miniatures
- Téléchargement sécurisé des fichiers
- Interface responsive et intuitive
- Gestion des erreurs et des états de chargement

### Utilisation typique

1. Fernando ouvre la PWA sur son mobile
2. Va dans Planning
3. Clique sur son rendez-vous du jour
4. Voit toutes les infos : client, projet, lieu, description
5. Ouvre les photos du chantier pour voir les détails
6. Consulte le PDF du plan d'installation
7. Se rend sur place avec toutes les infos nécessaires

---

## Fichiers modifiés/créés

### Backend
- ✅ `/api/v1/planning_view.php` (créé) - Détail événement
- ✅ `/api/v1/file.php` (créé) - Téléchargement fichiers

### Frontend
- ✅ `/pwa/src/pages/PlanningDetail.tsx` (modifié) - Page détail complète
- ✅ `/pwa/src/lib/api.ts` (modifié) - Ajout export `apiClient`

### Documentation
- ✅ `/PLANNING_DETAIL_COMPLET.md` (créé) - Ce fichier

### Build
- ✅ PWA compilée avec succès
- ✅ Taille : 238.36 KB (69.54 KB gzippé)
- ✅ Tous les modules transformés sans erreur

---

**Date de création** : 2026-01-09

**Status** : ✅ Complet et fonctionnel

**Prêt pour production** : Oui
