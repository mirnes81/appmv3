# 📋 Guide de référence rapide - MV3 PRO Mobile

## 🚀 Installation complète (5 minutes)

### 1. Créer les tables SQL (30 secondes)
```bash
mysql -u root -p dolibarr < new_dolibarr/mv3pro_portail/sql/INSTALLATION_RAPIDE.sql
```

### 2. Vérifier (10 secondes)
```sql
SHOW TABLES LIKE 'llx_mv3_mobile%';
-- Devrait montrer 3 tables
```

### 3. Tester (30 secondes)
```
URL: https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/
Email: admin@test.local
Mot de passe: test123
```

---

## 👥 Créer des utilisateurs

### Interface web (recommandé)
```
https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/admin/manage_users.php
```

### SQL rapide
```sql
-- Générer d'abord le hash:
-- php -r "echo password_hash('MonMotDePasse', PASSWORD_BCRYPT);"

INSERT INTO llx_mv3_mobile_users
(email, password_hash, firstname, lastname, role, is_active, entity)
VALUES
('employe@example.com', 'HASH_ICI', 'Jean', 'Dupont', 'employee', 1, 1);
```

---

## 🔧 Dépannage rapide

### Utilisateur ne peut pas se connecter

**1. Vérifier que le compte existe:**
```sql
SELECT email, is_active, login_attempts, locked_until
FROM llx_mv3_mobile_users
WHERE email = 'employe@example.com';
```

**2. Débloquer si verrouillé:**
```sql
UPDATE llx_mv3_mobile_users
SET login_attempts = 0, locked_until = NULL
WHERE email = 'employe@example.com';
```

**3. Réinitialiser le mot de passe:**
Via `manage_users.php` ou:
```sql
-- Mot de passe: test123
UPDATE llx_mv3_mobile_users
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    login_attempts = 0,
    locked_until = NULL
WHERE email = 'employe@example.com';
```

### Page blanche

**1. Vérifier mod_rewrite:**
```bash
apache2ctl -M | grep rewrite
# Si vide:
a2enmod rewrite
systemctl restart apache2
```

**2. Vérifier .htaccess:**
```bash
ls -la /var/www/html/dolibarr/htdocs/custom/mv3pro_portail/pwa_dist/.htaccess
# Doit exister
```

**3. Vérifier les logs:**
```bash
tail -f /var/log/apache2/error.log
```

### Erreur 404 sur les API

**Vérifier les chemins dans `pwa/src/lib/api.ts`:**
```typescript
const API_BASE_URL = '/custom/mv3pro_portail/api/v1';
const AUTH_API_URL = '/custom/mv3pro_portail/mobile_app/api/auth.php';
```

---

## 🔑 Mots de passe pré-hashés (tests)

| Mot de passe | Hash bcrypt |
|--------------|-------------|
| `test123` | `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi` |
| `password` | `$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm` |
| `admin123` | `$2y$10$Ysy7xTNu2LhqTdg7Qgu0ZOLNBVhGEj5wLJPmCQ6JUqCMpWX8Bb6fa` |

---

## 📊 Requêtes SQL utiles

### Lister tous les utilisateurs mobiles
```sql
SELECT
    email,
    CONCAT(firstname, ' ', lastname) as nom_complet,
    role,
    CASE WHEN is_active = 1 THEN 'Actif' ELSE 'Inactif' END as statut,
    last_login
FROM llx_mv3_mobile_users
ORDER BY created_at DESC;
```

### Voir les sessions actives
```sql
SELECT
    u.email,
    s.last_activity,
    s.expires_at,
    s.ip_address
FROM llx_mv3_mobile_sessions s
INNER JOIN llx_mv3_mobile_users u ON u.rowid = s.user_id
WHERE s.expires_at > NOW()
ORDER BY s.last_activity DESC;
```

### Historique des connexions (50 dernières)
```sql
SELECT
    created_at,
    email,
    CASE WHEN success = 1 THEN '✅ OK' ELSE '❌ Échec' END as resultat,
    error_message,
    ip_address
FROM llx_mv3_mobile_login_history
ORDER BY created_at DESC
LIMIT 50;
```

### Nettoyer les sessions expirées
```sql
DELETE FROM llx_mv3_mobile_sessions
WHERE expires_at < NOW();
```

### Compter les utilisateurs actifs
```sql
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as actifs,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactifs
FROM llx_mv3_mobile_users;
```

---

## 🔗 URLs importantes

| Page | URL |
|------|-----|
| **PWA Login** | `/custom/mv3pro_portail/pwa_dist/` |
| **Admin Utilisateurs** | `/custom/mv3pro_portail/mobile_app/admin/manage_users.php` |
| **Créer Utilisateur** | `/custom/mv3pro_portail/mobile_app/admin/create_mobile_user.php` |
| **API Auth** | `/custom/mv3pro_portail/mobile_app/api/auth.php` |
| **API v1** | `/custom/mv3pro_portail/api/v1/` |

---

## 📁 Structure des fichiers

```
custom/mv3pro_portail/
├── pwa_dist/                    ← PWA de production
│   ├── index.html
│   ├── .htaccess               ← Important pour routing
│   └── assets/
├── mobile_app/
│   ├── api/
│   │   └── auth.php            ← API d'authentification
│   └── admin/
│       ├── manage_users.php    ← Gestion utilisateurs
│       └── create_mobile_user.php
├── api/v1/                      ← API REST
└── sql/
    ├── INSTALLATION_RAPIDE.sql  ← À exécuter en premier
    ├── INSTRUCTIONS_INSTALLATION.md
    └── llx_mv3_mobile_users.sql
```

---

## 🎯 Différence Dolibarr vs Mobile

| Critère | Dolibarr | Mobile PWA |
|---------|----------|------------|
| **Table** | `llx_user` | `llx_mv3_mobile_users` |
| **Login** | Identifiant | Email |
| **Accès** | Back-office | Application mobile |
| **Obligatoire** | Pour admin Dolibarr | Pour employés mobiles |
| **Création** | Interface Dolibarr | manage_users.php |

**Important:** Les deux systèmes sont INDÉPENDANTS. Avoir un compte Dolibarr ne donne PAS accès à la PWA.

---

## 💻 Commandes de dev

### Développement local
```bash
cd new_dolibarr/mv3pro_portail/pwa
npm install
npm run dev
# Ouvre http://localhost:3100
```

### Build production
```bash
cd new_dolibarr/mv3pro_portail/pwa
npm run build
# Génère dans ../pwa_dist/
```

### Copier vers serveur
```bash
# Depuis votre machine
scp -r new_dolibarr/mv3pro_portail/pwa_dist/* \
  user@serveur:/var/www/html/dolibarr/htdocs/custom/mv3pro_portail/pwa_dist/
```

---

## 🧪 Tester l'API avec curl

### Login
```bash
curl -X POST https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.local","password":"test123"}'
```

### Vérifier le token
```bash
curl -X POST https://votre-dolibarr.com/custom/mv3pro_portail/mobile_app/api/auth.php?action=verify \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

### Me (info utilisateur)
```bash
curl https://votre-dolibarr.com/custom/mv3pro_portail/api/v1/me.php \
  -H "Authorization: Bearer VOTRE_TOKEN"
```

---

## 📱 Installation sur mobile

### iOS (Safari)
1. Ouvrir l'URL dans Safari
2. Appuyer sur le bouton "Partager" (carré avec flèche)
3. Défiler et choisir "Sur l'écran d'accueil"
4. Confirmer

### Android (Chrome)
1. Ouvrir l'URL dans Chrome
2. Appuyer sur le menu (3 points verticaux)
3. Choisir "Ajouter à l'écran d'accueil"
4. Confirmer

L'icône apparaîtra sur l'écran d'accueil comme une vraie application!

---

## 🔐 Sécurité

### Bonnes pratiques

✅ **À faire:**
- Utiliser HTTPS en production
- Changer les mots de passe par défaut
- Vérifier les permissions des fichiers (755)
- Nettoyer les sessions expirées régulièrement
- Désactiver les comptes inutilisés

❌ **À ne pas faire:**
- Utiliser `test123` en production
- Laisser les logs accessibles publiquement
- Partager les tokens JWT
- Utiliser HTTP (non sécurisé)
- Donner les droits admin à tout le monde

### Protection anti-brute-force

- **5 tentatives max** → Verrouillage 15 minutes
- **Auto-reset** après connexion réussie
- **Historique** dans `llx_mv3_mobile_login_history`

---

## 📚 Documentation complète

| Document | Description |
|----------|-------------|
| `DEMARRAGE_RAPIDE.md` | Installation en 5 minutes |
| `DIAGNOSTIC_ET_INSTALLATION.md` | Guide détaillé + dépannage |
| `README_PWA.md` | Documentation technique |
| `RECAPITULATIF_AUTH.md` | Améliorations authentification |
| `BUILD_INFO.md` | Informations de build |
| `GUIDE_REFERENCE_RAPIDE.md` | Ce document |

---

## 🆘 Support

### Ordre de vérification en cas de problème

1. **Console navigateur** (F12 > Console)
2. **Network tab** (F12 > Network)
3. **Logs Apache** (`tail -f /var/log/apache2/error.log`)
4. **Base de données** (vérifier les tables et données)
5. **Permissions fichiers** (`ls -la`)
6. **Configuration Apache** (.htaccess, mod_rewrite)

### Contact

Pour toute question, consultez d'abord:
1. Ce guide
2. `DIAGNOSTIC_ET_INSTALLATION.md`
3. Les logs systèmes

**Tout est documenté et testé!** 🎉
