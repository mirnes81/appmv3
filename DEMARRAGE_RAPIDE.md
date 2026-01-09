# 🚀 Comment se connecter - GUIDE SIMPLE

## ⚠️ IMPORTANT À SAVOIR

**La PWA mobile N'utilise PAS vos identifiants Dolibarr!**

Vous devez d'abord créer un "compte mobile" séparé.

---

## 📍 ÉTAPE 1: Créer votre compte mobile

### Allez sur cette page EXACTEMENT:

```
https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/admin/manage_users.php
```

**Remplacez `votre-dolibarr.com` par votre vrai domaine!**

Exemples:
- `https://erp.monentreprise.com/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
- `https://192.168.1.100/dolibarr/htdocs/custom/mv3pro_portail/mobile_app/admin/manage_users.php`

### Sur cette page:

1. **Connectez-vous d'abord avec votre compte admin Dolibarr** (compte habituel)

2. **Faites défiler EN BAS de la page**

3. **Remplissez le formulaire "Créer un nouvel utilisateur mobile":**
   - Email: `votre.email@exemple.com` (NOTEZ-LE!)
   - Mot de passe: `ChoisissezUnMotDePasse` (NOTEZ-LE!)
   - Prénom: `Votre prénom`
   - Nom: `Votre nom`
   - Rôle: Choisissez (employee/manager/admin)

4. **Cliquez sur le bouton "Créer l'utilisateur"**

5. **Vous devriez voir: "Utilisateur créé avec succès"**

**🔴 NOTEZ bien votre email et mot de passe quelque part!**

---

## 📍 ÉTAPE 2: Se connecter à la PWA mobile

### Allez sur l'application EXACTEMENT:

```
https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/
```

**Remplacez `votre-dolibarr.com` par votre vrai domaine!**

Exemples:
- `https://erp.monentreprise.com/custom/mv3pro_portail/pwa_dist/`
- `https://192.168.1.100/dolibarr/htdocs/custom/mv3pro_portail/pwa_dist/`

### Sur la page de connexion:

1. **Entrez l'EMAIL que vous avez créé à l'étape 1**
   (PAS votre login Dolibarr, mais l'email du compte mobile!)

2. **Entrez le MOT DE PASSE que vous avez créé à l'étape 1**
   (PAS votre mot de passe Dolibarr, mais celui du compte mobile!)

3. **Cliquez sur "Se connecter"**

4. **Vous devriez voir le dashboard!**

---

## 🎉 Si ça marche:

**Bravo! Vous êtes connecté!**

Sur mobile:
- **iPhone:** Safari > Partager > "Sur l'écran d'accueil"
- **Android:** Chrome > Menu (3 points) > "Ajouter à l'écran d'accueil"

---

## 🆘 Ça ne marche PAS? Voici les solutions:

### ❌ Erreur: "Compte mobile introuvable"

**Vous voyez ce message?** Votre compte n'existe pas encore!

**Solution:**
1. Retournez à l'ÉTAPE 1 ci-dessus
2. Créez votre compte sur `manage_users.php`
3. Réessayez de vous connecter

---

### ❌ Erreur: "Email ou mot de passe incorrect"

**3 causes possibles:**

#### Cause 1: Mauvais mot de passe

**Solution:** Réinitialisez votre mot de passe:
1. Allez sur: `https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
2. Trouvez votre utilisateur dans la liste
3. Cliquez sur "Réinitialiser mot de passe"
4. Entrez un nouveau mot de passe
5. Réessayez de vous connecter

#### Cause 2: Compte désactivé

Vérifiez sur `manage_users.php` que votre compte est marqué "Actif" (pas "Inactif").

#### Cause 3: Compte verrouillé (trop de tentatives)

Après 5 tentatives échouées, le compte est bloqué 15 minutes.

**Solution rapide:** Sur `manage_users.php`, cliquez sur "Réinitialiser mot de passe" pour débloquer.

---

### ❌ Page blanche (rien ne s'affiche)

**Cause:** Apache mod_rewrite désactivé

**Solution:**
```bash
a2enmod rewrite
systemctl restart apache2
```

---

### ❌ Erreur 404 (page non trouvée)

**Cause:** Les fichiers ne sont pas au bon endroit

**Vérifiez:**
```bash
ls /var/www/html/dolibarr/htdocs/custom/mv3pro_portail/pwa_dist/index.html
```

Si ce fichier n'existe pas, les fichiers ne sont pas installés.

---

### ❌ Je n'arrive pas à accéder à manage_users.php

**Causes possibles:**

1. **Vous n'êtes pas admin Dolibarr**
   - Solution: Demandez à un administrateur de créer votre compte mobile

2. **URL incorrecte**
   - Vérifiez bien: `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
   - Pas de `/htdocs/` dans l'URL du navigateur!

3. **Module pas installé**
   - Vérifiez que le dossier existe sur le serveur

---

## 📋 RÉCAP: Les 2 URLs importantes

**Pour CRÉER votre compte:**
```
https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/admin/manage_users.php
```

**Pour vous CONNECTER:**
```
https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/
```

---

## 💡 À RETENIR

```
┌─────────────────────────────────────────────┐
│                                             │
│  ❌ Identifiants Dolibarr                  │
│     ≠                                       │
│  ✅ Identifiants PWA mobile                │
│                                             │
│  Ce sont 2 systèmes différents!            │
│                                             │
└─────────────────────────────────────────────┘
```

**Dolibarr:**
- Pour: Back-office (admin)
- Table: `llx_user`
- Login avec: Identifiant Dolibarr

**PWA Mobile:**
- Pour: Application mobile (employés)
- Table: `llx_mv3_mobile_users`
- Login avec: Email du compte mobile

---

## 🎯 Checklist de vérification

Avant de dire "ça ne marche pas", vérifiez:

- [ ] J'ai créé un compte mobile (pas juste Dolibarr)
- [ ] J'utilise l'email du compte mobile (pas le login Dolibarr)
- [ ] J'utilise le mot de passe du compte mobile
- [ ] J'ai bien les bonnes URLs (avec `/custom/mv3pro_portail/`)
- [ ] Mon compte est actif sur `manage_users.php`
- [ ] J'ai essayé de réinitialiser le mot de passe
- [ ] J'ai vidé le cache (Ctrl+F5)
- [ ] J'ai essayé dans un autre navigateur

---

## 🔗 Identifiants de test

Si vous avez exécuté le fichier `sql/INSTALLATION_RAPIDE.sql`, un compte test existe:

```
Email: admin@test.local
Mot de passe: test123
```

**Testez avec ce compte d'abord pour vérifier que tout fonctionne!**

---

## 🎨 Personnalisation (optionnel)

### Changer les couleurs

Éditez: `pwa/src/index.css`

```css
/* Cherchez ces valeurs et modifiez-les */
--primary: #0891b2;    /* Couleur principale */
--secondary: #06b6d4;  /* Couleur secondaire */
```

Puis rebuilder:
```bash
cd new_dolibarr/mv3pro_portail/pwa
npm run build
```

### Changer le nom de l'app

Éditez: `pwa_dist/manifest.webmanifest`

```json
{
  "name": "Votre Entreprise Mobile",
  "short_name": "VotreApp"
}
```

---

## 📚 Documentation complète

- **Installation détaillée:** `new_dolibarr/mv3pro_portail/README_PWA.md`
- **Diagnostic complet:** `DIAGNOSTIC_ET_INSTALLATION.md`
- **Installation dans pwa_dist:** `new_dolibarr/mv3pro_portail/pwa_dist/INSTALLATION.md`

---

## ✅ Checklist complète

- [ ] Tables SQL créées
- [ ] Utilisateur de test créé
- [ ] Fichiers copiés sur le serveur
- [ ] Permissions configurées (755)
- [ ] mod_rewrite activé
- [ ] Test de connexion réussi
- [ ] Installation sur mobile réussie
- [ ] Déconnexion/reconnexion fonctionne

---

## �� Commandes utiles

```bash
# Voir les logs Apache en temps réel
tail -f /var/log/apache2/error.log

# Voir les erreurs PHP
tail -f /var/log/apache2/error.log | grep PHP

# Vérifier les tables
mysql -u root -p dolibarr -e "SELECT email, firstname, lastname, is_active FROM llx_mv3_mobile_users;"

# Réinitialiser le mot de passe d'un utilisateur
# Mot de passe: nouveau123
mysql -u root -p dolibarr -e "UPDATE llx_mv3_mobile_users SET password_hash='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email='admin@test.com';"
```

---

## 🆘 Besoin d'aide?

Si après avoir suivi ce guide vous avez toujours des problèmes:

1. Ouvrez F12 dans votre navigateur
2. Allez dans Console
3. Copiez l'erreur exacte
4. Consultez `DIAGNOSTIC_ET_INSTALLATION.md`
5. Contactez le support avec les détails

**L'application est 100% fonctionnelle si toutes les étapes sont suivies correctement!**
