# MV3 PRO Mobile - Application PWA pour Dolibarr

Application mobile Progressive Web App (PWA) pour la gestion des rapports de chantier, planning et matériel.

**Version:** 1.0.1 | **Date:** 2026-01-09

---

## 🚀 Démarrage rapide

**Nouveau sur ce projet?** Commencez par ici:

1. **Installation rapide (5 min):** `DEMARRAGE_RAPIDE.md`
2. **Guide de référence:** `GUIDE_REFERENCE_RAPIDE.md`
3. **Problèmes?** `DIAGNOSTIC_ET_INSTALLATION.md`

---

## 📁 Structure du Projet

```
project/
├── DEMARRAGE_RAPIDE.md               ← ⭐ COMMENCEZ ICI
├── GUIDE_REFERENCE_RAPIDE.md         ← Référence rapide
├── DIAGNOSTIC_ET_INSTALLATION.md     ← Dépannage complet
├── RECAPITULATIF_AUTH.md            ← Améliorations auth (2026-01-09)
├── BUILD_INFO.md                     ← Infos de build
│
└── new_dolibarr/
    └── mv3pro_portail/
        ├── README_PWA.md             ← Documentation technique
        ├── pwa/                      ← Code source React/TypeScript
        │   ├── src/
        │   ├── package.json
        │   └── vite.config.ts
        ├── pwa_dist/                 ← ⭐ BUILD DE PRODUCTION
        │   ├── index.html
        │   ├── .htaccess
        │   └── assets/
        ├── mobile_app/
        │   ├── api/
        │   │   └── auth.php          ← API authentification
        │   └── admin/
        │       └── manage_users.php  ← ⭐ Gestion utilisateurs
        ├── api/v1/                   ← API REST
        └── sql/
            ├── INSTALLATION_RAPIDE.sql       ← ⭐ À exécuter en premier
            └── INSTRUCTIONS_INSTALLATION.md
```

## 💻 Développement

### Installation des dépendances

```bash
cd new_dolibarr/mv3pro_portail/pwa
npm install
```

### Développement local

```bash
npm run dev
```

Ouvre l'application sur `http://localhost:3100`

### Build de production

```bash
npm run build
```

Compile l'application dans `../pwa_dist/`

## 📦 Installation (Production)

### Étape 1: Créer les tables SQL (30 secondes)

```bash
mysql -u root -p dolibarr < new_dolibarr/mv3pro_portail/sql/INSTALLATION_RAPIDE.sql
```

### Étape 2: Copier les fichiers (2 minutes)

Copiez `new_dolibarr/mv3pro_portail/` vers votre serveur Dolibarr:

```bash
/var/www/html/dolibarr/htdocs/custom/mv3pro_portail/
├── pwa_dist/          ← Application PWA buildée
├── mobile_app/        ← API backend
├── api/              ← API REST v1
└── sql/              ← Scripts SQL
```

### Étape 3: Permissions (30 secondes)

```bash
chmod -R 755 /var/www/html/dolibarr/htdocs/custom/mv3pro_portail/pwa_dist/
chown -R www-data:www-data /var/www/html/dolibarr/htdocs/custom/mv3pro_portail/
```

### Étape 4: Activer mod_rewrite (30 secondes)

```bash
a2enmod rewrite
systemctl restart apache2
```

### Étape 5: Tester

```
URL: https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/
Email: admin@test.local
Mot de passe: test123
```

## 🔗 URLs importantes

- **PWA Login:** `/custom/mv3pro_portail/pwa_dist/`
- **Admin Utilisateurs:** `/custom/mv3pro_portail/mobile_app/admin/manage_users.php`
- **API Auth:** `/custom/mv3pro_portail/mobile_app/api/auth.php`
- **API REST v1:** `/custom/mv3pro_portail/api/v1/`

## ✨ Fonctionnalités

- ✅ **Authentification mobile indépendante** (table dédiée)
- ✅ **Dashboard** avec vue d'ensemble
- ✅ **Rapports de chantier** avec photos
- ✅ **Gestion du matériel**
- ✅ **Planning** des interventions
- ✅ **Sens de pose** carrelage
- ✅ **Feuilles de régie**
- ✅ **Notifications** en temps réel
- ✅ **Mode PWA** installable sur mobile
- ✅ **Mode hors-ligne** avec Service Worker
- ✅ **Protection anti-brute-force** (5 tentatives max)

## 🛠️ Technologies

- **Frontend:** React 18 + TypeScript + Vite
- **Styling:** CSS moderne (pas de Tailwind)
- **Backend:** PHP 7.4+
- **Base de données:** MySQL/MariaDB
- **PWA:** Workbox (Service Worker)
- **Authentification:** JWT + bcrypt
- **Build:** Vite 5 (201 KB → 61 KB gzippé)

## 📚 Documentation complète

**Par ordre de priorité:**

1. **`DEMARRAGE_RAPIDE.md`** - Installation en 5 minutes
2. **`GUIDE_REFERENCE_RAPIDE.md`** - Commandes et SQL utiles
3. **`new_dolibarr/mv3pro_portail/README_PWA.md`** - Documentation technique
4. **`DIAGNOSTIC_ET_INSTALLATION.md`** - Dépannage détaillé
5. **`RECAPITULATIF_AUTH.md`** - Améliorations authentification
6. **`BUILD_INFO.md`** - Informations de build

**Guides SQL:**

- `sql/INSTALLATION_RAPIDE.sql` - Crée tables + utilisateur test
- `sql/INSTRUCTIONS_INSTALLATION.md` - Guide SQL complet

---

## 🔐 Authentification

**Important:** La PWA utilise une authentification mobile indépendante.

| Dolibarr standard | Mobile PWA |
|-------------------|------------|
| Table: `llx_user` | Table: `llx_mv3_mobile_users` |
| Login: Identifiant | Login: Email |
| Accès: Back-office | Accès: Application mobile |

**Les identifiants Dolibarr ne fonctionnent PAS pour la PWA.**

Pour créer un compte mobile:
- Interface: `/mobile_app/admin/manage_users.php`
- SQL: `sql/INSTALLATION_RAPIDE.sql`

---

## 🆘 Problèmes fréquents

### "Compte mobile introuvable"

Créez un utilisateur mobile sur `manage_users.php` ou exécutez `INSTALLATION_RAPIDE.sql`.

### Page blanche

Vérifiez mod_rewrite: `a2enmod rewrite && systemctl restart apache2`

### Erreur 404 sur les API

Vérifiez que les fichiers sont bien copiés dans `/custom/mv3pro_portail/`.

**Pour plus de détails:** Consultez `DIAGNOSTIC_ET_INSTALLATION.md`

---

## 📱 Installation sur mobile

1. Ouvrez l'URL dans Chrome/Safari mobile
2. **Chrome:** Menu > "Ajouter à l'écran d'accueil"
3. **Safari:** Partager > "Sur l'écran d'accueil"
4. L'icône apparaît comme une vraie app!

---

## 🎯 Checklist installation

- [ ] Tables SQL créées
- [ ] Utilisateur de test créé
- [ ] Fichiers copiés sur le serveur
- [ ] Permissions configurées (755)
- [ ] mod_rewrite activé
- [ ] Test de connexion OK
- [ ] Installation sur mobile testée

---

## 🔄 Dernière mise à jour (2026-01-09)

- ✅ Messages d'erreur améliorés (401 plus clair)
- ✅ Lien vers administration sur page de login
- ✅ Guide SQL d'installation rapide
- ✅ Documentation complète
- ✅ Build optimisé (61 KB gzippé)

---

## 💡 Support

**Avant de demander de l'aide:**

1. Consultez `GUIDE_REFERENCE_RAPIDE.md`
2. Vérifiez les logs: `tail -f /var/log/apache2/error.log`
3. Testez l'API avec curl
4. Vérifiez la console navigateur (F12)

**Tout est documenté et testé!** 🚀

---

Développé pour MV3 Carrelage - Gestion de chantiers mobiles
