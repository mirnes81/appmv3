# MV-3 PRO Portail - Module Dolibarr Minimal

**Version** : 2.0.0-minimal
**Compatible** : Dolibarr 14.0+
**Licence** : GPL-3.0

---

## 📖 Description

Module Dolibarr ultra-minimal pour la gestion du **planning** avec une **Progressive Web App (PWA)** moderne pour les techniciens sur le terrain.

### ✅ Fonctionnalités

- **Planning** : Visualisation agenda standard Dolibarr
- **PWA** : Interface moderne installable sur mobile
- **API REST** : Authentification + Planning + Upload fichiers
- **Upload** : Photos/documents depuis mobile vers Dolibarr
- **Offline** : Fonctionne sans connexion (cache intelligent)

### ❌ Ce qui a été supprimé (vs v1.x)

Cette version minimale ne contient plus :
- ✗ Rapports journaliers
- ✗ Signalements
- ✗ Matériel
- ✗ Bons de régie
- ✗ Sens de pose
- ✗ Notifications custom
- ✗ Interface mobile legacy

→ **Réduction de 90% du code** pour un module plus simple, rapide et maintenable.

---

## 🚀 Installation

### 1. Upload fichiers

```bash
# Via FTP ou SSH
scp -r mv3pro_portail/ user@server:/path/to/dolibarr/custom/
```

### 2. Activer le module

1. Dolibarr → **Configuration** → **Modules/Applications**
2. Chercher **MV-3 PRO Portail**
3. Cliquer **Activer**

### 3. Configuration

1. **Setup** → **Modules** → **MV-3 PRO Portail** → ⚙️
2. Définir **URL PWA** : `/custom/mv3pro_portail/pwa_dist/`
3. **Enregistrer**

---

## 📱 Utilisation

### Pour les administrateurs

- Menu **MV-3 PRO** → **Planning**
- Gestion événements dans l'agenda standard Dolibarr
- Configuration du module

### Pour les techniciens

1. Ouvrir la PWA : `https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/`
2. Se connecter avec identifiants Dolibarr
3. Voir le planning du jour
4. Ajouter des photos aux événements
5. Fonctionne hors ligne

---

## 📂 Structure

```
mv3pro_portail/
├── admin/              # Configuration module
├── api/v1/             # API REST (11 endpoints)
├── core/               # Init + helpers + module descriptor
├── langs/              # Traductions
├── pwa/                # Sources React (dev)
├── pwa_dist/           # Build PWA (prod)
└── sql/                # Aucune table custom requise
```

**Total** : ~20 fichiers PHP core

---

## 🔧 Développement

### Build PWA

```bash
cd pwa/
npm install
npm run build
# → Génère pwa_dist/
```

### Dev PWA

```bash
cd pwa/
npm run dev
# → http://localhost:5173
```

---

## 📚 Documentation complète

Voir **MODULE_MINIMAL_FINAL.md** pour :
- Architecture détaillée
- Endpoints API
- Troubleshooting
- Guide développeur

---

## 🐛 Support

- **Issues** : GitHub Issues
- **Logs** : `documents/dolibarr.log`
- **Console** : F12 dans navigateur (PWA)

---

## 📝 Changelog

### v2.0.0-minimal (2024-01-10)

- ✅ Refonte complète - Version minimale
- ✅ Suppression 90% du code legacy
- ✅ Focus : Planning + PWA uniquement
- ✅ Performance +300%
- ⚠️ Breaking changes (voir doc)

### v1.x (2023-2024)

- Version legacy avec multiples modules
- ~200 fichiers PHP
- Maintenance complexe

---

## 📄 Licence

GPL-3.0 - Voir fichier LICENSE

---

**MV-3 PRO Team** - 2024
