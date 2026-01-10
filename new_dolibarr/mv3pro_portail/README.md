# MV-3 PRO Portail - Module Dolibarr

**Version** : 2.0.0-minimal
**Compatible** : Dolibarr 14.0+

---

## 📖 Description

Module Dolibarr minimal avec Dashboard + Planning + PWA mobile.

### ✅ Fonctionnalités

- **Dashboard** : Vue d'ensemble avec statistiques et widgets Planning
- **Planning** : Visualisation agenda standard Dolibarr
- **PWA** : Application mobile installable pour techniciens
- **API REST** : Authentification + Planning + Upload fichiers
- **Upload** : Photos/documents depuis mobile vers Dolibarr

---

## 🚀 Installation

1. **Upload** : `mv3pro_portail/` → `custom/mv3pro_portail/`
2. **Activer** : Configuration → Modules → MV-3 PRO Portail
3. **Configurer** : Setup → URL PWA : `/custom/mv3pro_portail/pwa_dist/`

---

## 📱 Utilisation

### Menu Dolibarr

```
MV-3 PRO
├── Dashboard    (Statistiques + widgets)
└── Planning     (Agenda Dolibarr)
```

### PWA Techniciens

1. Ouvrir : `https://votre-dolibarr.com/custom/mv3pro_portail/pwa_dist/`
2. Se connecter avec identifiants Dolibarr
3. Voir planning + ajouter photos

---

## 📂 Structure

```
mv3pro_portail/
├── dashboard/       # Dashboard avec widgets
├── admin/           # Configuration
├── api/v1/          # API REST (11 endpoints)
├── core/            # Init + module descriptor
├── pwa_dist/        # PWA build
└── pwa/             # Sources React (dev)
```

---

## 🎯 Dashboard

Le dashboard affiche :
- **Statistiques** : Aujourd'hui, Cette semaine, À venir, Total
- **Activité** : Liste des techniciens avec nombre d'événements
- **Planning 7 jours** : Prochains événements détaillés
- **Actions rapides** : Nouvel événement, Voir planning, Ouvrir PWA

---

## 🔧 Développement PWA

```bash
cd pwa/
npm install
npm run dev      # Dev : http://localhost:5173
npm run build    # Prod : génère pwa_dist/
```

---

## 📝 Changelog

### v2.0.0 (2024-01-10)
- ✅ Dashboard avec widgets statistiques
- ✅ Nettoyage complet (92% code supprimé)
- ✅ Focus : Dashboard + Planning + PWA
- ✅ Suppression mv3_tv_display

---

**MV-3 PRO Team** - 2024
