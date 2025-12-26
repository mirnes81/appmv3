# 🧪 Configuration de test - MV3 Pro

## Configuration actuelle

### Serveur Dolibarr
```
URL: https://crm.mv-3pro.ch
API: https://crm.mv-3pro.ch/api/index.php
```

### Utilisateur de test
```
Nom: VELAGIC Mirnes
Email: info@mv-3pro.ch
Rôle: Directeur (Admin)
Téléphone: 0786843224
```

### DOLAPIKEY de test
```
04VxqqZ4fEi78j4tYVNqc18jQ0TWU1Wr
```

---

## ✅ Endpoints testés et fonctionnels

### 1. Authentification
- ✅ `/users/info` - Récupération des infos utilisateur
- **Statut** : Fonctionnel
- **Données** : Nom, email, téléphone, rôle

### 2. Clients (Tiers)
- ✅ `/thirdparties` - Liste des clients
- **Statut** : Fonctionnel
- **Données** : Nom, adresse, contact, téléphone

### 3. Projets
- ✅ `/projects` - Liste des projets
- **Statut** : Fonctionnel
- **Données** : Titre, dates, client associé

### 4. Agenda
- ✅ `/agendaevents` - Événements du planning
- **Statut** : Fonctionnel
- **Données** : Type, label, date, auteur

---

## ⚠️ Modules à activer dans Dolibarr

### 1. Module Interventions (fichinter)
**Statut** : ❌ Non activé

**Erreur rencontrée** :
```
API not found (failed to include API file)
```

**Comment activer** :
1. Connexion Dolibarr → Menu **Accueil**
2. **Configuration** → **Modules/Applications**
3. Rechercher **"Interventions"** ou **"Fichinter"**
4. Cliquer sur **"Activer"**

**Utilité** :
- Création de fiches d'intervention
- Rapports de chantier
- Suivi des interventions

### 2. Module Produits/Services
**À vérifier** : Permet de gérer les matériaux et produits

**Comment activer** :
1. Configuration → Modules/Applications
2. Rechercher **"Produits"** ou **"Products"**
3. Activer le module

---

## 🔧 Module Custom MV3PRO Portail

### Installation recommandée

Le module custom **MV3PRO Portail** est disponible dans le projet et ajoute :

- ✅ Gestion des Régies (heures de travail)
- ✅ Sens de pose (plans de pose carrelage)
- ✅ Matériel (équipement et véhicules)
- ✅ Rapports enrichis avec photos géolocalisées
- ✅ API mobile optimisée
- ✅ Gestion des sous-traitants

### Chemin d'installation
```
new_dolibarr/mv3pro_portail/
```

### Installation
1. Copier le dossier `mv3pro_portail` dans `/custom/` de Dolibarr
2. Activer le module dans Dolibarr
3. Exécuter les scripts SQL dans `sql/`
4. Configurer les permissions

**Documentation** :
- `new_dolibarr/mv3pro_portail/INSTRUCTIONS_ACTIVATION.md`
- `new_dolibarr/mv3pro_portail/GUIDE_INSTALLATION_APP_MOBILE.md`

---

## 🧪 Scénarios de test

### Test 1 : Connexion avec DOLAPIKEY
```bash
# Copier cette clé dans l'application
04VxqqZ4fEi78j4tYVNqc18jQ0TWU1Wr

# Résultat attendu :
- Connexion réussie
- Affichage du nom "VELAGIC Mirnes"
- Redirection vers le Dashboard
```

### Test 2 : Récupération des clients
```bash
# Dans l'application, créer un nouveau rapport
# Sélectionner le champ "Client"

# Résultat attendu :
- Liste déroulante avec les clients Dolibarr
- Exemple : "M.F.V CARRELAGE"
```

### Test 3 : Récupération des projets
```bash
# Sélectionner un client
# Le champ "Projet" doit se remplir

# Résultat attendu :
- Projets associés au client
- Exemple : "Boucanière Verbier"
```

### Test 4 : Diagnostic API
```bash
# Sur l'écran de connexion, cliquer sur "Diagnostic API"
# OU se connecter et aller dans Profil → Diagnostic

# Résultat attendu :
- Liste des endpoints
- Statut de chaque API (vert = OK, rouge = erreur)
- Recommandations pour les modules manquants
```

---

## 📱 Test de l'application PWA

### Sur ordinateur
```
1. Ouvrir : http://localhost:5173/pro/
2. Se connecter avec la DOLAPIKEY
3. Chrome : Cliquer sur l'icône "Installer" dans la barre d'adresse
4. Tester les fonctionnalités
```

### Sur mobile
```
1. Scanner ce QR code (si serveur accessible)
2. Ou accéder via IP locale : http://192.168.x.x:5173/pro/
3. iOS : Partager → Sur l'écran d'accueil
4. Android : Menu → Ajouter à l'écran d'accueil
```

---

## 🐛 Problèmes courants et solutions

### Erreur : "DOLAPIKEY invalide"
**Cause** : La clé est expirée ou incorrecte

**Solution** :
1. Se connecter à Dolibarr
2. Régénérer une nouvelle clé API
3. Copier-coller la nouvelle clé (sans espaces)

### Erreur : "API not found"
**Cause** : Module Dolibarr non activé

**Solution** :
1. Vérifier dans Configuration → Modules
2. Activer les modules manquants
3. Relancer le diagnostic API

### Erreur : "Network Error"
**Cause** : Serveur Dolibarr inaccessible

**Solution** :
1. Vérifier que `https://crm.mv-3pro.ch` est accessible
2. Vérifier les logs Apache/Nginx
3. Vérifier le certificat SSL

### Erreur : "CORS policy blocked"
**Cause** : Configuration CORS manquante dans Dolibarr

**Solution** :
```php
// Ajouter dans htdocs/api/index.php (en haut du fichier)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, DOLAPIKEY, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

---

## 📊 Données de test

### Clients disponibles
```
- M.F.V CARRELAGE
- MIRZA
- Autres clients dans votre Dolibarr
```

### Projets disponibles
```
- Boucanière Verbier
- Autres projets dans votre Dolibarr
```

### Utilisateurs
```
- admin (VELAGIC Mirnes)
- Autres utilisateurs selon votre configuration
```

---

## 🚀 Prochaines étapes

Après avoir testé en local :

1. ✅ Activer les modules Dolibarr manquants
2. ✅ Installer le module MV3PRO Portail (optionnel mais recommandé)
3. ✅ Tester toutes les fonctionnalités
4. ✅ Build de production : `npm run build`
5. ✅ Déploiement sur le serveur
6. ✅ Configuration Apache/Nginx
7. ✅ Tests en production
8. ✅ Installation PWA sur les appareils

---

## 📞 Support

### Logs utiles

**Console navigateur** : F12 → Console
**Logs réseau** : F12 → Network
**Logs Dolibarr** : `/var/log/apache2/error.log` ou `/htdocs/documents/dolibarr.log`

### Commandes de diagnostic

```bash
# Tester l'API directement
curl -X GET "https://crm.mv-3pro.ch/api/index.php/users/info" \
  -H "DOLAPIKEY: 04VxqqZ4fEi78j4tYVNqc18jQ0TWU1Wr"

# Vérifier les modules activés
curl -X GET "https://crm.mv-3pro.ch/api/index.php/setup/modules" \
  -H "DOLAPIKEY: 04VxqqZ4fEi78j4tYVNqc18jQ0TWU1Wr"
```

---

**Version** : 1.0.2
**Date** : 2024-12-26
**Statut** : Configuration testée ✅
