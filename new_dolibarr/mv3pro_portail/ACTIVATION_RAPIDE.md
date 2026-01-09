# Checklist d'activation rapide - MV3 PRO

## Étape 1 : Activer le module Dolibarr

1. **Connexion Dolibarr en tant qu'admin**
   - Se connecter avec un compte admin

2. **Activer le module**
   - Home → Setup → Modules/Applications
   - Rechercher "MV3 PRO" ou "MV-3 PRO Portail"
   - Cliquer sur le bouton "Activer" (ON/OFF)
   - Attendre la confirmation

3. **Vider le cache** (si nécessaire)
   - Home → Setup → Other → Purge cache
   - Ou supprimer : `documents/install_lock.txt`

4. **Vérifier le menu**
   - Le menu "MV-3 PRO" doit apparaître en haut
   - Cliquer dessus pour voir le menu de gauche
   - La section "Administration" doit être visible (admin uniquement)

---

## Étape 2 : Configurer la PWA

1. **Accéder à la configuration**
   - Menu : MV-3 PRO → Administration → Configuration PWA
   - Ou URL directe : `/custom/mv3pro_portail/admin/setup.php`

2. **Configurer les URLs**
   ```
   URL PWA publique : https://votre-domaine.com/custom/mv3pro_portail/pwa_dist/
   URL API base     : https://votre-domaine.com/custom/mv3pro_portail/api/v1/
   ```

3. **Configurer la sécurité** (recommandé)
   - ☑ Accès admin uniquement
   - Longueur minimale mot de passe : 8
   - ☑ Exiger caractères spéciaux

4. **Configurer le planning** (recommandé)
   - ☑ Admin voit tous les rendez-vous
   - ☑ Employé voit seulement ses RDV

5. **Sauvegarder**
   - Cliquer sur "Enregistrer la configuration"

---

## Étape 3 : Créer le premier utilisateur mobile

### Option A : Depuis le menu
1. Menu : MV-3 PRO → Administration → Créer utilisateur mobile
2. Le formulaire apparaît et scroll automatiquement

### Option B : Depuis la configuration PWA
1. Rester sur la page Configuration PWA
2. Cliquer sur "Créer un utilisateur mobile" en bas

### Remplir le formulaire

```
Email     : fernando@mv-3pro.ch
Mot de passe : VotreMotDePasse123!
Prénom    : Fernando
Nom       : Silva
Téléphone : +41 xx xxx xx xx
Rôle      : Employé
Utilisateur Dolibarr : Fernando Silva (fernando)  ⚠️ OBLIGATOIRE
```

**Important** : Pour les rôles "Employé" et "Manager", le lien avec un utilisateur Dolibarr est obligatoire.

---

## Étape 4 : Tester la PWA

1. **Ouvrir la PWA**
   - Depuis Configuration PWA → Cliquer sur "Ouvrir PWA"
   - Ou aller directement sur : `https://votre-domaine.com/custom/mv3pro_portail/pwa_dist/`

2. **Se connecter**
   - Email : fernando@mv-3pro.ch
   - Mot de passe : VotreMotDePasse123!

3. **Vérifier l'accès**
   - Le dashboard doit s'afficher
   - Les menus en bas doivent être visibles
   - Tester la navigation

---

## Étape 5 : Activer le mode debug (optionnel)

1. **Retour à la configuration**
   - Menu : MV-3 PRO → Administration → Configuration PWA

2. **Activer le debug**
   - Cocher "Activer le mode debug"
   - Sauvegarder
   - L'indicateur doit passer au vert : ● Fichier debug.flag : ACTIF

3. **Voir les logs**
   - Cliquer sur "Debug PWA"
   - Ou aller sur : `https://votre-domaine.com/custom/mv3pro_portail/pwa_dist/#/debug`
   - Consulter les informations de diagnostic

---

## Vérifications finales

### ✅ Checklist de validation

- [ ] Module MV-3 PRO activé dans Dolibarr
- [ ] Menu "MV-3 PRO" visible en haut
- [ ] Section "Administration" visible (admin uniquement)
- [ ] URLs PWA et API configurées
- [ ] Au moins 1 utilisateur mobile créé
- [ ] Lien avec utilisateur Dolibarr configuré
- [ ] Connexion à la PWA réussie
- [ ] Navigation dans la PWA fonctionnelle
- [ ] Mode debug activé (si souhaité)
- [ ] Page Debug accessible

---

## Résolution de problèmes courants

### Le menu n'apparaît pas
- Vider le cache Dolibarr : Setup → Other → Purge cache
- Vérifier que vous êtes connecté en admin
- Désactiver/réactiver le module

### La page setup.php affiche une erreur
- Vérifier les permissions du fichier (644 ou 755)
- Vérifier que le répertoire `/custom/mv3pro_portail/admin/` existe
- Consulter les logs Apache/Nginx

### Erreur "Tous les champs obligatoires..."
- Vérifier que tous les champs sont remplis
- Pour Employé/Manager : sélectionner un utilisateur Dolibarr
- Le mot de passe doit faire au moins 8 caractères

### La PWA ne se connecte pas
- Vérifier l'URL de l'API dans la configuration
- Vérifier que le mode debug est activé
- Consulter la page Debug (#/debug)
- Vérifier les logs réseau du navigateur (F12)

### Le planning est vide
- Vérifier que l'utilisateur Dolibarr est lié
- Vérifier qu'il y a des rendez-vous dans Dolibarr
- Consulter les paramètres de planning dans la configuration

---

## Support

### Documentation complète
- `INTEGRATION_MENU_DOLIBARR.md` - Documentation détaillée
- `MENU_STRUCTURE.txt` - Structure visuelle du menu
- `ACTIVATION_RAPIDE.md` - Ce fichier

### Fichiers importants
```
/custom/mv3pro_portail/
├── core/modules/modMv3pro_portail.class.php  (Descripteur module)
├── admin/
│   ├── setup.php                              (Configuration PWA)
│   └── config.php                             (Redirige vers setup.php)
├── mobile_app/admin/
│   └── manage_users.php                       (Gestion utilisateurs)
├── pwa_dist/                                  (PWA compilée)
├── api/v1/                                    (API)
└── debug.flag                                 (Fichier debug auto-géré)
```

### Logs utiles
```bash
# Logs Apache
tail -f /var/log/apache2/error.log

# Logs Nginx
tail -f /var/log/nginx/error.log

# Logs PHP
tail -f /var/log/php/error.log
```

---

## Aide rapide

### Créer un utilisateur
```
Menu → Administration → Créer utilisateur mobile
```

### Réinitialiser un mot de passe
```
Menu → Administration → Gestion utilisateurs mobiles
→ Cliquer sur "Réinitialiser mot de passe"
```

### Activer le mode debug
```
Menu → Administration → Configuration PWA
→ Cocher "Activer le mode debug"
→ Sauvegarder
```

### Ouvrir la PWA
```
Menu → Administration → Configuration PWA
→ Cliquer sur "Ouvrir PWA"
```

### Voir les logs debug
```
Menu → Administration → Configuration PWA
→ Cliquer sur "Debug PWA"
```

---

## Temps estimé

- Activation du module : 2 minutes
- Configuration PWA : 3 minutes
- Création utilisateur : 2 minutes
- Tests : 5 minutes

**Total : ~12 minutes** pour une installation complète fonctionnelle.

---

## Prochaines étapes

Une fois l'activation terminée :

1. **Créer les autres utilisateurs mobiles**
   - Un par employé qui doit utiliser l'app

2. **Configurer les permissions Dolibarr**
   - Définir qui peut accéder à quels modules

3. **Former les utilisateurs**
   - Montrer comment se connecter
   - Expliquer les fonctionnalités principales

4. **Surveiller les logs**
   - Activer le mode debug temporairement
   - Vérifier qu'il n'y a pas d'erreurs

5. **Désactiver le mode debug**
   - Une fois tout validé
   - Pour de meilleures performances

---

✅ **Félicitations ! Votre module MV-3 PRO est maintenant opérationnel.**

Toute la gestion se fait maintenant directement depuis Dolibarr, sans besoin de taper des URLs manuellement.
