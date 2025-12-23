# Guide d'Utilisation - MV3 Pro Mobile

## Pour l'Utilisateur Final

### Première Connexion

1. **Ouvrez l'application** : `https://app.mv-3pro.ch/pro/`

2. **Obtenez votre DOLAPIKEY dans Dolibarr:**
   - Connectez-vous à `https://crm.mv-3pro.ch`
   - Cliquez sur votre nom en haut à droite
   - "Modifier ma fiche utilisateur"
   - Onglet "Clé API"
   - Cliquez sur "Générer une nouvelle clé"
   - Copiez la clé générée

3. **Connectez-vous à l'application mobile:**
   - **URL Dolibarr** : `https://crm.mv-3pro.ch`
   - **DOLAPIKEY** : Collez votre clé
   - Cliquez sur "Se connecter"

### Utilisation Quotidienne

#### Dashboard
- Vue d'ensemble des statistiques
- Rapports du jour/semaine/mois
- Météo actuelle
- Accès rapide aux fonctions

#### Créer un Rapport d'Intervention
1. Onglet "Rapports"
2. Bouton "+" (Nouveau rapport)
3. Remplissez les champs
4. Ajoutez des photos
5. Ajoutez une note vocale (optionnel)
6. Sauvegardez

#### Mode Hors-ligne
- L'application fonctionne sans connexion
- Les rapports sont sauvegardés localement
- Synchronisation automatique au retour en ligne
- Icône dans le header indique l'état de connexion

#### Planning
- Voir vos interventions planifiées
- Ajouter de nouveaux événements
- Synchronisé avec l'agenda Dolibarr

### Questions Fréquentes

**Q: Puis-je utiliser l'application sans connexion ?**
R: Oui, après votre première connexion, l'application fonctionne en mode hors-ligne. Les données se synchroniseront automatiquement.

**Q: Où sont stockées mes photos ?**
R: Les photos sont stockées localement jusqu'à synchronisation, puis envoyées dans l'ECM de Dolibarr.

**Q: Ma DOLAPIKEY est-elle sécurisée ?**
R: Oui, elle est stockée localement sur votre appareil uniquement. Vous pouvez la révoquer à tout moment depuis Dolibarr.

**Q: Comment savoir si mes données sont synchronisées ?**
R: L'icône de synchronisation dans le header indique l'état. Les rapports affichent un badge de statut (Brouillon, En attente, Synchronisé).

**Q: Puis-je utiliser plusieurs appareils ?**
R: Oui, utilisez la même DOLAPIKEY sur tous vos appareils. Les données Dolibarr seront synchronisées.

## Pour l'Administrateur

### Installation Initiale

**Prérequis:**
- Instance Dolibarr opérationnelle
- Module API REST activé dans Dolibarr
- HTTPS configuré sur Dolibarr

**Étapes:**

1. **Activer le module API REST dans Dolibarr:**
   - Configuration → Modules
   - Chercher "API REST"
   - Activer

2. **Déployer l'application:**
   ```bash
   # Build
   npm install
   npm run build
   
   # Déployer le dossier dist/
   # Sur votre serveur web (Apache/NGINX)
   ```

3. **Configurer CORS (si nécessaire):**
   
   Option A - Dans Dolibarr `.htaccess`:
   ```apache
   Header set Access-Control-Allow-Origin "*"
   Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
   Header set Access-Control-Allow-Headers "DOLAPIKEY, Accept, Content-Type"
   ```
   
   Option B - Proxy NGINX:
   ```nginx
   location /api/ {
       proxy_pass https://crm.mv-3pro.ch/api/;
       add_header Access-Control-Allow-Origin *;
       add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
       add_header Access-Control-Allow-Headers "DOLAPIKEY, Accept, Content-Type";
   }
   ```

### Gestion des Utilisateurs

1. **Créer un utilisateur dans Dolibarr**
2. **Générer sa DOLAPIKEY:**
   - Fiche utilisateur → Clé API → Générer
3. **Communiquer la clé à l'utilisateur**
4. **Configurer les permissions du module Fichinter**

### Modules Dolibarr Requis

- **API REST** : Obligatoire
- **Fichinter** : Pour les rapports d'intervention
- **Agenda** : Pour le planning
- **Tiers** : Pour les clients
- **Projets** : Pour associer interventions aux projets
- **ECM** : Pour stocker les photos/documents

### Monitoring

**Logs API Dolibarr:**
- `/htdocs/logs/dolibarr_api.log`

**Vérifier les appels API:**
```bash
tail -f /var/www/dolibarr/htdocs/logs/dolibarr_api.log
```

### Sauvegardes

Les données sont stockées dans Dolibarr. Assurez-vous de:
- Sauvegarder la base MySQL régulièrement
- Sauvegarder le dossier ECM (documents/photos)
- Tester les restaurations

### Performances

**Optimisations recommandées:**
- Activer le cache Dolibarr
- Index MySQL sur tables fichinter
- CDN pour les fichiers statiques de l'app
- Compression GZIP activée

### Sécurité

**Checklist de sécurité:**
- [ ] HTTPS activé sur Dolibarr
- [ ] Module API REST limité aux utilisateurs autorisés
- [ ] DOLAPIKEY par utilisateur (pas de clé partagée)
- [ ] Permissions Dolibarr configurées par module
- [ ] Firewall limitant l'accès à l'API
- [ ] Logs API activés et surveillés
- [ ] Renouvellement régulier des DOLAPIKEY

### Support Technique

**Problème de connexion:**
1. Vérifier que l'URL Dolibarr est accessible
2. Tester la DOLAPIKEY manuellement:
   ```bash
   curl -H "DOLAPIKEY: XXX" https://crm.mv-3pro.ch/api/index.php/users/info
   ```
3. Vérifier les logs Dolibarr
4. Vérifier CORS si erreur réseau

**Problème de synchronisation:**
1. Vérifier IndexedDB dans DevTools navigateur
2. Vérifier la connexion internet de l'utilisateur
3. Vérifier les permissions Dolibarr de l'utilisateur

### Mise à Jour

**Application:**
```bash
git pull
npm install
npm run build
# Déployer dist/
```

**Pas de migration de base de données nécessaire** (données dans Dolibarr)

### Personnalisation

Pour ajouter des fonctionnalités custom:

1. **Créer un module Dolibarr:**
   ```
   /custom/mon_module/
   ├── core/
   │   └── modules/
   │       └── modMonModule.class.php
   ├── api/
   │   └── mon_endpoint.php
   └── sql/
       └── llx_mon_table.sql
   ```

2. **Exposer l'endpoint via API REST**

3. **Utiliser dans l'app:**
   ```typescript
   fetchDolibarr('/mon_module/mon_endpoint')
   ```

### Contact

- Documentation API: https://wiki.dolibarr.org/index.php/REST_API
- Forum Dolibarr: https://www.dolibarr.org/forum/
