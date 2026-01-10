# Migration vers la nouvelle PWA React

## URLs importantes

### Nouvelle PWA React (à utiliser)
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
```

### Ancienne version mobile PHP (à désactiver)
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/
```

### Mode Debug
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/DEBUG_MODE.html
```

---

## Instructions de test

### 1. Ouvrir la PWA
Ouvrez l'URL sur votre mobile:
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
```

### 2. Se connecter
- Utilisez vos identifiants (email + mot de passe)
- Le token est automatiquement sauvegardé
- L'application fonctionne en mode offline

### 3. Tester les fonctionnalités
- **Dashboard**: Vue d'ensemble
- **Planning**: Liste des événements avec détails complets
  - Cliquez sur un événement pour voir: Détails, Photos, Fichiers
- **Rapports**: Créer et consulter les rapports de chantier
- **Notifications**: Voir les alertes
- **Profil**: Informations du compte

### 4. Vider le cache (si erreur 404)
**Android Chrome:**
1. Menu (⋮) → Paramètres
2. Confidentialité → Effacer les données de navigation
3. Cocher "Images et fichiers en cache"
4. Effacer les données

**iPhone Safari:**
1. Réglages → Safari
2. Effacer historique et données de sites
3. Confirmer

**Alternative rapide:**
- Ajoutez `?v=2` à l'URL: `...pwa_dist/?v=2`

---

## Désactiver l'ancienne version

### Option 1: Redirection automatique (recommandé)

Créez un fichier `.htaccess` dans `mobile_app/`:

```apache
# Redirection vers la nouvelle PWA React
RewriteEngine On
RewriteBase /custom/mv3pro_portail/mobile_app/

# Rediriger tout vers la nouvelle PWA sauf les API
RewriteCond %{REQUEST_URI} !^/custom/mv3pro_portail/mobile_app/api/
RewriteRule ^(.*)$ /custom/mv3pro_portail/pwa_dist/ [R=301,L]
```

### Option 2: Page d'information

Remplacez `mobile_app/index.php` par:

```php
<?php
header('Location: ../pwa_dist/', true, 301);
exit;
?>
```

### Option 3: Désactiver complètement

Renommez le dossier:
```bash
mv mobile_app mobile_app_old
```

---

## Résolution des problèmes

### Erreur 404 sur les API

**Symptôme:** La PWA affiche "Erreur 404" sur certaines pages

**Cause:** Cache du navigateur ou Service Worker obsolète

**Solution:**
1. Ouvrir le mode Debug: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/DEBUG_MODE.html`
2. Cliquer sur "Effacer le token"
3. Fermer et rouvrir le navigateur
4. Se reconnecter

### Erreur 401 Unauthorized

**Symptôme:** Message "Authentification requise"

**Cause:** Token expiré ou manquant

**Solution:**
1. Se reconnecter à l'application
2. Vérifier que le token est sauvegardé (mode Debug)
3. Vérifier que la session est valide dans la BDD:
   ```sql
   SELECT * FROM llx_mv3_mobile_sessions
   WHERE expires_at > NOW()
   ORDER BY expires_at DESC;
   ```

### Écran blanc

**Symptôme:** L'application affiche un écran blanc

**Cause:** Erreur JavaScript ou fichiers manquants

**Solution:**
1. Ouvrir la console du navigateur (F12)
2. Regarder les erreurs JavaScript
3. Rebuild la PWA:
   ```bash
   cd /path/to/mv3pro_portail/pwa
   npm run build
   ```
4. Vider le cache du navigateur

### Les photos ne s'affichent pas

**Symptôme:** Les miniatures de photos sont cassées

**Cause:** Problème de permissions ou de chemin

**Solution:**
1. Vérifier les permissions des dossiers `documents/`
2. Vérifier les logs Apache/PHP
3. Tester l'URL de l'image directement dans le navigateur

---

## Mode Debug avancé

### Activer les logs détaillés

1. Ouvrir `DEBUG_MODE.html`
2. Cliquer sur "Activer le mode debug"
3. Ouvrir la console du navigateur (F12)
4. Naviguer dans l'application
5. Observer les logs `[MV3PRO DEBUG]`

### Ajouter un header de debug aux requêtes API

Modifier `pwa/src/lib/api.ts`:

```typescript
if (token) {
  headers['Authorization'] = `Bearer ${token}`;
  headers['X-Auth-Token'] = token;
  headers['X-MV3-Debug'] = '1'; // <-- Ajouter cette ligne
}
```

Rebuild:
```bash
cd pwa && npm run build
```

Les logs API seront visibles dans les logs PHP du serveur.

---

## Checklist de migration

- [ ] Tester la nouvelle PWA sur mobile
- [ ] Vérifier que toutes les fonctionnalités marchent
  - [ ] Connexion
  - [ ] Planning avec détails
  - [ ] Création de rapports
  - [ ] Upload de photos
  - [ ] Notifications
- [ ] Communiquer l'URL aux utilisateurs
- [ ] Activer la redirection de l'ancienne version
- [ ] Vérifier les logs d'erreur pendant 1 semaine
- [ ] Désactiver complètement l'ancienne version

---

## Support

En cas de problème:
1. Vérifier ce document
2. Activer le mode Debug
3. Consulter les logs Apache/PHP
4. Vérifier la console JavaScript (F12)

## Rebuild après modifications

Si vous modifiez le code React:

```bash
cd /path/to/mv3pro_portail/pwa
npm install  # si nouvelles dépendances
npm run build
```

Les fichiers sont générés dans `pwa_dist/`
