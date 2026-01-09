# Intégration Menu Dolibarr - MV3 PRO

## Menu Dolibarr - Structure complète

Tous les liens sont maintenant accessibles directement depuis le module MV-3 PRO dans Dolibarr.

### Menu principal : MV-3 PRO

#### 1. Tableau de bord
- `/custom/mv3pro_portail/index.php`

#### 2. Rapports journaliers
- Liste des rapports
- Nouveau rapport

#### 3. Signalements
- Liste des signalements
- Nouveau signalement

#### 4. Matériel
- Liste du matériel
- Nouveau matériel

#### 5. Planning
- Vue planning
- Nouveau planning

#### 6. Notifications
- Mes notifications
- Envoyer notification (admin)
- Configuration (admin)

#### 7. Bons de régie
- Liste des bons
- Nouveau bon

#### 8. Sens de pose
- Liste des plans
- Nouveau plan
- Depuis devis

#### 9. Interface mobile
- Accès à l'app mobile

#### 10. **Administration** (NOUVEAU - Admin uniquement)
- **Configuration PWA** → `/custom/mv3pro_portail/admin/setup.php`
- **Gestion utilisateurs mobiles** → `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
- **Créer utilisateur mobile** → `/custom/mv3pro_portail/mobile_app/admin/manage_users.php?action=create`
- Configuration module (ancien lien)

---

## Page de Configuration PWA (`admin/setup.php`)

### Fonctionnalités complètes

#### 1. URLs et Accès rapides
- URL PWA publique (configurable)
- URL API base (configurable)
- Boutons d'accès rapide :
  - **Ouvrir PWA** (nouvelle fenêtre)
  - **Debug PWA** (page #/debug)
  - **Gestion utilisateurs**

#### 2. Mode Debug
- Activation/désactivation du mode debug
- Gestion automatique du fichier `debug.flag`
- Indicateur visuel du statut debug (vert/rouge)
- Pas besoin d'accès SSH pour activer/désactiver

#### 3. Paramètres de sécurité
- **Accès admin uniquement** : Restreindre la création d'utilisateurs
- **Longueur minimale mot de passe** : 6-20 caractères
- **Exiger caractères spéciaux** : Politique de mot de passe renforcée

#### 4. Paramètres d'affichage Planning
- **Admin voit tous les RDV** : Les admins voient le planning complet
- **Employé voit seulement ses RDV** : Filtre automatique par `dolibarr_user_id`

#### 5. Informations système
- Nombre d'utilisateurs mobiles actifs
- Version PWA (date du dernier build)
- Statut de l'API v1
- État des tables base de données

#### 6. Aide rapide
- Guide intégré pour :
  - Créer un utilisateur mobile
  - Réinitialiser un mot de passe
  - Activer le mode debug
  - Tester la PWA
  - Voir les logs debug

---

## Gestion des utilisateurs mobiles

### Accès direct

1. **Depuis le menu** : MV-3 PRO → Administration → Gestion utilisateurs mobiles
2. **Depuis la config PWA** : Bouton "Créer un utilisateur mobile"
3. **URL directe** : `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`

### Fonctionnalités

#### Liste des utilisateurs
- Email, Nom complet, Téléphone
- Rôle (Employé, Manager, Admin)
- Lien Dolibarr (ID utilisateur)
- Statut actif/inactif
- Date de création
- Tentatives de connexion (avec indicateur de blocage)
- Actions : Modifier, Réinitialiser mot de passe, Supprimer

#### Créer un utilisateur
- Formulaire intégré dans la même page
- Validation automatique du lien Dolibarr (obligatoire pour Employé/Manager)
- Auto-scroll et highlight quand `?action=create` dans l'URL

#### Modifier un utilisateur
- Modification de toutes les informations
- Changement de mot de passe optionnel
- Réinitialisation des tentatives de connexion

#### Réinitialiser mot de passe
- Génération automatique d'un mot de passe temporaire
- Affichage du nouveau mot de passe
- Recommandation de changement au premier login

---

## Activation du module

### Étapes pour activer les menus

1. **Activer le module** (si pas déjà fait)
   - Home → Setup → Modules/Applications
   - Chercher "MV3 PRO Portail"
   - Cliquer sur "Activer"

2. **Rafraîchir le cache** (si les menus n'apparaissent pas)
   ```bash
   # Supprimer le cache Dolibarr
   rm -rf documents/install_lock.txt
   # Ou via l'interface : Setup → Other → Purge cache
   ```

3. **Vérifier les permissions**
   - Les menus d'administration sont visibles uniquement pour les admins
   - `$user->admin` doit être = 1

---

## Configuration recommandée

### Paramètres de base

```
URL PWA publique : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
URL API base     : https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/
```

### Sécurité recommandée

```
Accès admin uniquement          : ✓ Activé
Longueur minimale mot de passe  : 8 caractères
Exiger caractères spéciaux      : ✓ Activé
```

### Planning recommandé

```
Admin voit tous les RDV              : ✓ Activé
Employé voit seulement ses RDV       : ✓ Activé
```

---

## Dépannage

### Les menus n'apparaissent pas

1. Vérifier que le module est activé
2. Vérifier les permissions utilisateur (admin requis pour Administration)
3. Vider le cache Dolibarr : Setup → Other → Purge cache
4. Vérifier le fichier `/custom/mv3pro_portail/core/modules/modMv3pro_portail.class.php`

### La page setup.php ne s'affiche pas

1. Vérifier que le fichier existe : `/custom/mv3pro_portail/admin/setup.php`
2. Vérifier les permissions du fichier (644 ou 755)
3. Vérifier les logs PHP : `/var/log/apache2/error.log` ou équivalent

### Le mode debug ne fonctionne pas

1. Vérifier que le répertoire `/custom/mv3pro_portail/` est accessible en écriture
2. Le fichier `debug.flag` doit être créé/supprimé automatiquement
3. Vérifier dans la PWA : page Debug (#/debug)

---

## Constantes Dolibarr créées

Les paramètres sont stockés dans la table `llx_const` :

```sql
MV3PRO_PWA_PUBLIC_URL                -- URL de la PWA
MV3PRO_API_BASE_URL                  -- URL de l'API
MV3PRO_DEBUG_ENABLED                 -- Mode debug (0/1)
MV3PRO_ADMIN_ONLY                    -- Admin seul (0/1)
MV3PRO_PASSWORD_MIN_LENGTH           -- Longueur min mdp
MV3PRO_PASSWORD_REQUIRE_SPECIAL      -- Caractères spéciaux (0/1)
MV3PRO_PLANNING_ADMIN_VIEW_ALL       -- Admin voit tout (0/1)
MV3PRO_PLANNING_EMPLOYEE_OWN_ONLY    -- Employé limité (0/1)
```

---

## Liens rapides

- **Configuration PWA** : Menu MV-3 PRO → Administration → Configuration PWA
- **Gestion utilisateurs** : Menu MV-3 PRO → Administration → Gestion utilisateurs mobiles
- **Créer utilisateur** : Menu MV-3 PRO → Administration → Créer utilisateur mobile
- **Ouvrir PWA** : Depuis la page de configuration
- **Debug PWA** : Depuis la page de configuration

---

## Notes importantes

1. **Permissions admin** : Tous les menus d'administration nécessitent `$user->admin = 1`
2. **Lien Dolibarr obligatoire** : Pour les rôles Employé et Manager, le lien avec un utilisateur Dolibarr est obligatoire
3. **Mode debug** : Activable/désactivable sans SSH depuis l'interface Dolibarr
4. **Cache** : Penser à vider le cache Dolibarr après modification du fichier de module

---

## Historique

- **2026-01-09** : Création de l'intégration complète dans le menu Dolibarr
  - Ajout section Administration dans le menu
  - Création page setup.php avec tous les paramètres PWA
  - Gestion mode debug sans SSH
  - Liens rapides vers PWA et Debug
  - Auto-scroll vers formulaire de création d'utilisateur
