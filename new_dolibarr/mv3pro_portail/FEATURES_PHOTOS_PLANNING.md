# Nouvelles Fonctionnalités - Photos Planning

## Version 2.3.0 - 10 janvier 2026

### 📋 Fonctionnalités ajoutées

#### 1. **Badges de comptage dans la liste du planning**
- Badge bleu `📷 X` pour le nombre de photos
- Badge jaune `📄 X` pour le nombre de documents
- Les badges apparaissent à côté du titre de l'événement

#### 2. **Miniature de la dernière photo**
- Affichage d'une miniature (64x64px) de la dernière photo ajoutée
- Remplace l'icône calendrier quand des photos sont disponibles
- Coins arrondis et fond gris si pas de photo

#### 3. **Upload de photos depuis la PWA**
- Nouveau bouton `📷 Ajouter une photo` dans l'onglet Photos
- Support de la capture directe depuis la caméra mobile (`capture="environment"`)
- Barre de progression animée pendant l'upload
- Affichage du pourcentage (0% → 100%)
- Rechargement automatique des photos après upload
- Validation de type de fichier (images uniquement)
- Validation de taille (max 10MB)

---

## 📁 Fichiers modifiés

### Backend (API PHP)
1. **`/api/v1/planning.php`**
   - Ajout du comptage des fichiers (photos/documents)
   - Récupération de la dernière photo uploadée
   - Génération de l'URL de la miniature

2. **`/api/v1/planning_upload_photo.php`** (NOUVEAU)
   - Endpoint POST pour uploader des photos
   - Validation du type de fichier
   - Validation de la taille (max 10MB)
   - Enregistrement dans `ecm_files`
   - Sécurité: vérification des droits d'accès

### Frontend (PWA React)
1. **`/pwa/src/lib/api.ts`**
   - Ajout des types TypeScript:
     - `files_count?: number`
     - `photos_count?: number`
     - `documents_count?: number`
     - `last_photo_url?: string`

2. **`/pwa/src/pages/Planning.tsx`**
   - Affichage des badges de comptage
   - Affichage de la miniature de la dernière photo
   - Import du composant `AuthImage`

3. **`/pwa/src/pages/PlanningDetail.tsx`**
   - Bouton d'upload de photos
   - Barre de progression animée
   - Gestion de l'upload avec FormData
   - Rechargement automatique après upload
   - États React: `uploading`, `uploadProgress`

---

## 🎨 Design

### Badges
- **Photos**: Fond bleu clair (`#dbeafe`), texte bleu foncé (`#1e40af`)
- **Documents**: Fond jaune clair (`#fef3c7`), texte marron (`#92400e`)
- Icônes: 📷 pour photos, 📄 pour documents
- Border-radius: 12px
- Font-size: 12px
- Font-weight: 600

### Bouton d'upload
- Couleur: Bleu (`#3b82f6`)
- Hover: Bleu plus foncé (`#2563eb`)
- Désactivé pendant upload: Gris (`#9ca3af`)
- Border-radius: 12px
- Padding: 16px

### Barre de progression
- Hauteur: 8px
- Couleur de fond: Gris clair (`#e5e7eb`)
- Couleur de progression: Bleu (`#3b82f6`)
- Transition smooth de 0.3s
- Affichage du pourcentage en dessous

---

## 🔒 Sécurité

### Validation côté client
- Type MIME vérifié avant upload
- Taille max 10MB
- Messages d'erreur explicites

### Validation côté serveur
- Vérification du type MIME avec `finfo_open()`
- Vérification de la taille
- Vérification des droits d'accès à l'événement
- Nom de fichier sécurisé (sanitization)
- Enregistrement dans la table `ecm_files`

### Permissions
- Seuls les utilisateurs ayant accès à l'événement peuvent uploader
- Vérification via jointure avec `actioncomm_resources`

---

## 📱 Utilisation mobile

### Capture photo
- Attribut `capture="environment"` sur l'input file
- Ouvre directement l'appareil photo sur mobile
- Fallback vers la galerie si l'appareil photo n'est pas disponible

### UX optimisée
- Bouton large et facile à taper (padding 16px)
- Barre de progression visible
- Feedback visuel immédiat
- Désactivation du bouton pendant upload
- Alert de confirmation après succès

---

## 🧪 Tests recommandés

1. **Liste du planning**
   - ✅ Vérifier l'affichage des badges
   - ✅ Vérifier l'affichage de la miniature
   - ✅ Vérifier que l'icône calendrier s'affiche quand pas de photo

2. **Upload de photos**
   - ✅ Uploader une photo depuis la galerie
   - ✅ Uploader une photo depuis l'appareil photo
   - ✅ Tester avec un fichier > 10MB (doit être rejeté)
   - ✅ Tester avec un fichier non-image (doit être rejeté)
   - ✅ Vérifier la barre de progression
   - ✅ Vérifier le rechargement automatique
   - ✅ Vérifier que la photo apparaît dans la grille

3. **Permissions**
   - ✅ Vérifier qu'un utilisateur non autorisé ne peut pas uploader
   - ✅ Vérifier que les fichiers sont bien enregistrés dans `ecm_files`

---

## 📊 Performance

- Requête SQL optimisée avec `SUM(CASE WHEN...)`
- Pas de N+1 queries
- Images lazy-loaded dans la grille
- Progression simulée pour meilleur UX
- Cache des fichiers via service worker PWA

---

## 🚀 Prochaines améliorations possibles

1. Upload multiple de photos (plusieurs à la fois)
2. Prévisualisation avant upload
3. Compression côté client avant upload
4. Rotation d'image
5. Ajout de légende/description à la photo
6. Tri des photos (date, nom, etc.)
7. Suppression de photos
8. Galerie avec zoom/swipe entre photos
