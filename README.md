# 🚀 MV-3 PRO - Démo Build Locale

## 📦 Contenu

Ce build contient :
- ✅ **Module Dolibarr** complet (new_dolibarr/mv3pro_portail/)
- ✅ **PWA** buildée et prête à l'emploi (pwa_dist/)
- ✅ **Serveur de démo** pour tester localement
- ✅ **Dashboard demo** avec widgets

---

## 🚀 Démarrage rapide (1 commande)

```bash
npm run dev
```

Le serveur démarre sur **http://localhost:3000**

---

## 🌐 URLs disponibles

| Page | URL | Description |
|------|-----|-------------|
| 🏠 **Accueil** | http://localhost:3000 | Page d'accueil avec liens |
| 📱 **PWA** | http://localhost:3000/pwa | Application mobile React |
| 📊 **Dashboard Demo** | http://localhost:3000/dashboard-demo | Aperçu du dashboard |

---

## 📱 PWA Mobile

### Identifiants par défaut (demo)
- **Login** : `demo`
- **Mot de passe** : `demo`

> ⚠️ Ces identifiants sont pour la démo uniquement. En production, utilisez les vrais identifiants Dolibarr.

### Fonctionnalités PWA
- ✅ Planning interactif
- ✅ Vue détail événement
- ✅ Upload photos
- ✅ Fonctionne offline (service worker)
- ✅ Installable sur mobile

---

## 📊 Dashboard Demo

Le dashboard affiche :
- **4 widgets** : Aujourd'hui, Cette semaine, À venir, Total
- **Activité techniciens** : Top 5 avec nombre d'événements
- **Planning 7 jours** : Tableau détaillé des prochains événements
- **Actions rapides** : Liens vers planning et PWA

---

## 📂 Structure

```
project/
├── new_dolibarr/
│   └── mv3pro_portail/          ← Module à déployer
│       ├── dashboard/           ← Dashboard PHP
│       ├── admin/               ← Config
│       ├── api/v1/              ← API REST
│       ├── core/                ← Core PHP
│       ├── pwa_dist/            ← PWA build
│       └── pwa/                 ← Sources React
│
├── public/                      ← Pages démo
│   ├── index.html              ← Accueil
│   └── dashboard-demo.html     ← Dashboard demo
│
├── server.js                    ← Serveur Express
└── package.json                 ← Dépendances

```

---

## 🚀 Déploiement en production

### 1. Vers Dolibarr

```bash
# Upload le dossier module vers Dolibarr
scp -r new_dolibarr/mv3pro_portail/* user@server:/path/to/dolibarr/custom/mv3pro_portail/
```

### 2. Activer dans Dolibarr

1. Configuration → Modules
2. Chercher **MV-3 PRO Portail**
3. Activer

### 3. Configurer URL PWA

1. Setup → Modules → MV-3 PRO
2. URL PWA : `/custom/mv3pro_portail/pwa_dist/`
3. Enregistrer

---

## 🔧 Développement

### Modifier la PWA

```bash
cd new_dolibarr/mv3pro_portail/pwa
npm install
npm run dev       # Dev : http://localhost:5173
npm run build     # Prod : génère ../pwa_dist/
```

### Configuration PWA

Fichier : `pwa/src/config.ts`

```typescript
export const API_BASE_URL = 'http://votre-dolibarr.com/custom/mv3pro_portail/api/v1';
```

---

## 📊 Fichiers du module

**17 fichiers PHP** :
- `dashboard/index.php` - Dashboard avec widgets
- `admin/setup.php` - Configuration
- `api/v1/*.php` - 11 endpoints API
- `core/*.php` - Init + Auth + Functions

---

## ✅ Validation

### Tests à effectuer

1. **PWA**
   - [ ] Login fonctionne
   - [ ] Planning s'affiche
   - [ ] Vue détail accessible
   - [ ] Upload photo OK

2. **Dashboard Demo**
   - [ ] Widgets affichent les données
   - [ ] Liste techniciens visible
   - [ ] Tableau planning affiché
   - [ ] Boutons fonctionnels

---

## 🐛 Troubleshooting

### Le serveur ne démarre pas

```bash
# Réinstaller les dépendances
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### La PWA ne se connecte pas

1. Vérifier l'URL API dans `pwa/src/config.ts`
2. En démo locale, utiliser : `demo` / `demo`

### Erreur de build PWA

```bash
cd new_dolibarr/mv3pro_portail/pwa
rm -rf node_modules package-lock.json
npm install
npm run build
```

---

## 📈 Statistiques

| Métrique | Valeur |
|----------|--------|
| **Code PHP** | 17 fichiers |
| **API Endpoints** | 11 |
| **Menus Dolibarr** | 3 |
| **Réduction code** | -92% |
| **Taille module** | ~5 MB |

---

## 📝 Changelog

### v2.0.0-minimal (2024-01-10)
- ✅ Dashboard avec widgets statistiques
- ✅ Nettoyage complet (92% code supprimé)
- ✅ Focus : Dashboard + Planning + PWA
- ✅ Suppression mv3_tv_display
- ✅ Build démo locale prête

---

## 🎯 Prochaines étapes

1. Tester la démo locale
2. Déployer vers Dolibarr production
3. Former les utilisateurs
4. Collecter les feedbacks

---

**MV-3 PRO Team** • 2024
