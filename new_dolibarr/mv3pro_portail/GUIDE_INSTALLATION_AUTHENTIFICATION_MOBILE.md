# Guide d'installation - Authentification Mobile Indépendante

## Vue d'ensemble

Ce système permet aux employés de se connecter à l'application mobile MV3 PRO directement, sans passer par Dolibarr. Les employés utilisent leur email et mot de passe personnel.

## Avantages

- Les employés n'ont pas besoin de compte Dolibarr
- Authentification sécurisée avec hashage bcrypt
- Protection contre les tentatives de connexion multiples
- Verrouillage automatique après 5 tentatives échouées
- Sessions sécurisées avec expiration automatique
- Historique complet des connexions
- Possibilité de lier un employé à un utilisateur Dolibarr existant

## Installation

### Étape 1 : Créer les tables

Exécutez le fichier SQL pour créer les tables nécessaires :

```sql
-- Fichier : sql/llx_mv3_mobile_users.sql
```

Depuis phpMyAdmin ou ligne de commande MySQL :
```bash
mysql -u votreuser -p votrebasededonnees < sql/llx_mv3_mobile_users.sql
```

Cela créera 3 tables :
- `llx_mv3_mobile_users` : Comptes utilisateurs mobiles
- `llx_mv3_mobile_sessions` : Sessions actives
- `llx_mv3_mobile_login_history` : Historique des connexions

### Étape 2 : Créer le premier utilisateur

1. Accédez à l'interface d'administration :
   ```
   https://votre-domaine.com/custom/mv3pro_portail/mobile_app/admin/manage_users.php
   ```

2. Utilisez le formulaire "Créer un nouvel utilisateur mobile" en bas de la page

3. Remplissez les informations :
   - **Email** : email@exemple.com (obligatoire)
   - **Mot de passe** : minimum 8 caractères (obligatoire)
   - **Prénom** : Jean (obligatoire)
   - **Nom** : Dupont (obligatoire)
   - **Téléphone** : 0612345678 (optionnel)
   - **Rôle** : employee / manager / admin
   - **Lier à un utilisateur Dolibarr** : optionnel

4. Cliquez sur "Créer l'utilisateur"

### Étape 3 : Tester la connexion

1. Ouvrez l'application mobile :
   ```
   https://votre-domaine.com/custom/mv3pro_portail/mobile_app/login_mobile.php
   ```

2. Connectez-vous avec les identifiants créés

3. Vous serez redirigé vers le dashboard mobile : `dashboard_mobile.php`

## Utilisation

### Page de connexion

- URL : `/custom/mv3pro_portail/mobile_app/login_mobile.php`
- Champs : Email + Mot de passe
- Option "Se souvenir de moi" pour sauvegarder l'email
- Installation PWA possible directement depuis cette page

### Dashboard mobile

- URL : `/custom/mv3pro_portail/mobile_app/dashboard_mobile.php`
- Affiche les informations personnalisées de l'employé
- Bouton de déconnexion dans l'en-tête
- Accès aux fonctionnalités selon le rôle

### Gestion des utilisateurs (Administrateurs)

- URL : `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
- Liste tous les utilisateurs mobiles
- Création, modification, suppression
- Réinitialisation de mot de passe
- Activation/désactivation de comptes
- Vue de l'historique de connexion

## Sécurité

### Hashage des mots de passe

- Utilise `password_hash()` avec bcrypt
- Coût de 12 pour une sécurité renforcée
- Impossible de récupérer le mot de passe original

### Protection contre le brute force

- Maximum 5 tentatives de connexion
- Verrouillage du compte pendant 15 minutes après 5 échecs
- Compteur remis à zéro après connexion réussie

### Sessions sécurisées

- Token unique généré avec `random_bytes(32)`
- Expiration automatique après 30 jours d'inactivité
- Stockage sécurisé dans la base de données
- Nettoyage automatique des sessions expirées

### Historique de connexion

- Toutes les tentatives sont enregistrées (succès et échecs)
- Stockage de l'IP et du User-Agent
- Permet de détecter les tentatives suspectes

## API d'authentification

### Endpoints disponibles

#### 1. Login (POST)
```
POST /custom/mv3pro_portail/mobile_app/api/auth.php?action=login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "motdepasse"
}
```

**Réponse succès :**
```json
{
  "success": true,
  "token": "abc123...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstname": "Jean",
    "lastname": "Dupont",
    "phone": "0612345678",
    "role": "employee",
    "dolibarr_user_id": 5
  }
}
```

#### 2. Vérifier une session (GET)
```
GET /custom/mv3pro_portail/mobile_app/api/auth.php?action=verify&token=abc123...
```

**Réponse succès :**
```json
{
  "success": true,
  "user": { ... }
}
```

#### 3. Déconnexion (GET/POST)
```
GET /custom/mv3pro_portail/mobile_app/api/auth.php?action=logout&token=abc123...
```

**Réponse succès :**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

## Flux d'authentification

### 1. Connexion initiale

```
Utilisateur → login_mobile.php
  ↓
Entre email + mot de passe
  ↓
API auth.php?action=login
  ↓
Vérification identifiants
  ↓
Création session + token
  ↓
Stockage token dans localStorage
  ↓
Redirection → dashboard_mobile.php
```

### 2. Accès aux pages

```
dashboard_mobile.php
  ↓
Inclut session_mobile.php
  ↓
checkMobileAuth()
  ↓
Vérifie token dans DB
  ↓
Si valide : Accès autorisé
Si invalide : Redirect login_mobile.php
```

### 3. Déconnexion

```
Clic sur bouton déconnexion
  ↓
API auth.php?action=logout
  ↓
Suppression session en DB
  ↓
Suppression localStorage
  ↓
Redirection → login_mobile.php
```

## Lier un utilisateur mobile à Dolibarr

### Pourquoi lier ?

Si un employé a déjà un compte Dolibarr, vous pouvez lier son compte mobile pour :
- Accéder aux rapports Dolibarr existants
- Voir le planning/affectations Dolibarr
- Synchroniser les données entre les deux systèmes

### Comment lier ?

1. Dans l'administration : `manage_users.php`
2. Modifier l'utilisateur
3. Sélectionner l'utilisateur Dolibarr dans le dropdown
4. Sauvegarder

Le système utilisera automatiquement le `dolibarr_user_id` pour récupérer les données liées.

## Migration depuis l'ancien système

Si vous aviez déjà des utilisateurs connectés via Dolibarr :

1. Créez un compte mobile pour chaque employé
2. Liez-le à leur compte Dolibarr existant
3. Communiquez les nouveaux identifiants
4. Les employés devront se reconnecter avec le nouveau système

## Maintenance

### Nettoyer les sessions expirées

Les sessions sont automatiquement nettoyées à chaque login, mais vous pouvez forcer un nettoyage :

```sql
DELETE FROM llx_mv3_mobile_sessions WHERE expires_at < NOW();
```

### Déverrouiller un compte

Si un employé est bloqué :

```sql
UPDATE llx_mv3_mobile_users
SET login_attempts = 0, locked_until = NULL
WHERE email = 'user@example.com';
```

Ou utilisez le bouton "Réinitialiser mot de passe" dans l'administration.

### Voir l'historique de connexion

```sql
SELECT * FROM llx_mv3_mobile_login_history
WHERE email = 'user@example.com'
ORDER BY created_at DESC
LIMIT 20;
```

## Dépannage

### "Compte verrouillé"

- Attendez 15 minutes OU
- Utilisez l'admin pour débloquer le compte

### "Session invalide ou expirée"

- L'utilisateur doit se reconnecter
- Les sessions expirent après 30 jours

### "Erreur de connexion au serveur"

- Vérifiez que `api/auth.php` est accessible
- Vérifiez les permissions des fichiers
- Vérifiez les logs PHP/Apache

## Support

Pour toute question ou problème :
1. Consultez les logs : `llx_mv3_mobile_login_history`
2. Vérifiez les permissions SQL
3. Testez l'API directement avec curl/Postman
4. Contactez votre administrateur système
