# 🏗️ Module Sous-Traitants MV3 PRO

## 📋 Vue d'ensemble

Module complet de gestion des sous-traitants avec application mobile dédiée pour les rapports journaliers.

### ✨ Fonctionnalités principales

**Pour les Sous-Traitants (Mobile uniquement):**
- ✅ Connexion par code PIN (4 chiffres)
- ✅ Rapport journalier obligatoire avec:
  - Surface en m² posés
  - Horaires début/fin
  - Minimum 3 photos (avant/pendant/après)
  - Signature électronique
  - Géolocalisation GPS
- ✅ Calcul automatique des montants (m², horaire ou forfait jour)
- ✅ Historique des rapports
- ✅ Mode hors-ligne (PWA)

**Pour les Administrateurs (Dolibarr):**
- ✅ Gestion des sous-traitants (création, modification)
- ✅ Validation des rapports journaliers
- ✅ Statistiques détaillées
- ✅ Suivi en temps réel de l'activité
- ✅ Génération automatique des paiements
- ✅ Tableau de bord direction

---

## 🚀 Installation

### Étape 1: Créer les tables de base de données

Exécutez le script SQL depuis phpMyAdmin ou en ligne de commande:

```bash
mysql -u root -p nom_database < sql/llx_mv3_subcontractors.sql
```

Ou copiez-collez le contenu du fichier `sql/llx_mv3_subcontractors.sql` dans phpMyAdmin.

**Tables créées:**
- `llx_mv3_subcontractors` - Sous-traitants
- `llx_mv3_subcontractor_reports` - Rapports journaliers
- `llx_mv3_subcontractor_photos` - Photos des rapports
- `llx_mv3_subcontractor_payments` - Paiements
- `llx_mv3_subcontractor_sessions` - Sessions mobile

**Données de test incluses:**
- Jean Dupont (PIN: 1234) - Carreleur - 25€/m²
- Marie Martin (PIN: 5678) - Électricien - 45€/h

### Étape 2: Configurer les permissions

Créez le répertoire pour les photos:

```bash
mkdir -p /var/www/dolibarr/documents/mv3pro_portail/subcontractor_reports
chmod 755 /var/www/dolibarr/documents/mv3pro_portail/subcontractor_reports
chown www-data:www-data /var/www/dolibarr/documents/mv3pro_portail/subcontractor_reports
```

### Étape 3: Configurer l'application mobile

L'application mobile se trouve dans: `subcontractor_app/`

**URL d'accès mobile:**
```
https://votre-domaine.com/custom/mv3pro_portail/subcontractor_app/
```

### Étape 4: Installer comme PWA (optionnel mais recommandé)

Sur smartphone:
1. Ouvrir l'URL dans le navigateur
2. Menu > "Ajouter à l'écran d'accueil"
3. L'icône apparaît comme une vraie application

---

## 👥 Utilisation

### Pour les Sous-Traitants

1. **Connexion**
   - Ouvrir l'app mobile
   - Saisir le code PIN (4 chiffres)
   - Connexion automatique

2. **Créer un rapport journalier**
   - Cliquer sur "Nouveau Rapport Journalier"
   - Remplir:
     - Date (pré-remplie)
     - Type de travail
     - Horaires début/fin (calcul auto des heures)
     - Surface m² posés
     - Notes (optionnel)
   - Ajouter minimum 3 photos
   - Signer électroniquement
   - Soumettre

3. **Voir ses statistiques**
   - Nombre de rapports du mois
   - Total m² posés
   - Derniers rapports

### Pour les Administrateurs

1. **Gérer les sous-traitants**
   - Menu: MV3 PRO > Sous-Traitants > Liste
   - Créer un nouveau sous-traitant
   - Définir:
     - Nom, prénom, téléphone
     - Spécialité
     - Type de tarif (m², horaire, jour)
     - Montant du tarif
     - Code PIN (4 chiffres unique)

2. **Valider les rapports**
   - Menu: MV3 PRO > Sous-Traitants > Liste
   - Cliquer sur "Rapports" d'un sous-traitant
   - Voir tous les rapports
   - Cliquer "Voir" pour voir les détails + photos
   - Cliquer "Valider" pour approuver

3. **Tableau de bord direction**
   - Menu: MV3 PRO > Sous-Traitants > Tableau de bord
   - Voir activité du jour en temps réel
   - Alertes pour rapports manquants
   - Statistiques du mois
   - Top performeurs

---

## 📊 Statuts des rapports

- **Brouillon (0)**: En cours de création
- **Soumis (1)**: En attente de validation
- **Validé (2)**: Approuvé par chef d'équipe
- **Facturé (3)**: Inclus dans paiement
- **Rejeté (9)**: Refusé (photos insuffisantes, etc.)

---

## ⚙️ Configuration avancée

### Modifier le nombre minimum de photos

Dans `subcontractor_app/js/reports.js`, ligne ~163:
```javascript
if (this.photos.length < 3) {  // Changer le 3
```

### Changer la durée de validité des sessions

Dans `api/subcontractor_login.php`, ligne ~44:
```php
$expires_at = date('Y-m-d H:i:s', strtotime('+7 days')); // Changer +7 days
```

### Personnaliser les types de travail

Dans `subcontractor_app/index.php`, ligne ~165:
```html
<select id="workType" required>
    <option value="Pose carrelage sol">Pose carrelage sol</option>
    <!-- Ajouter vos types ici -->
</select>
```

---

## 🔒 Sécurité

- ✅ Code PIN unique par sous-traitant
- ✅ Sessions avec expiration automatique
- ✅ Accès mobile UNIQUEMENT pour sous-traitants
- ✅ Validation obligatoire par chef d'équipe
- ✅ Géolocalisation des rapports et photos
- ✅ Signature électronique horodatée
- ✅ Traçabilité complète (IP, user agent, GPS)

---

## 📱 Compatibilité

**Navigateurs supportés:**
- Chrome/Edge (Android/iOS)
- Safari (iOS)
- Firefox (Android)

**Fonctionnalités:**
- ✅ Mode hors-ligne (PWA)
- ✅ Capture photo
- ✅ Géolocalisation
- ✅ Signature tactile
- ✅ Notifications push (à venir)

---

## 🆘 Dépannage

### "Code PIN incorrect"
- Vérifier que le sous-traitant est actif (active=1)
- Vérifier le code PIN dans la base de données

### "Session invalide"
- La session a expiré (>7 jours)
- Se reconnecter avec le code PIN

### Photos non enregistrées
- Vérifier les permissions du dossier `documents/mv3pro_portail/`
- Taille limite PHP (upload_max_filesize dans php.ini)

### Rapport non visible
- Vérifier que `entity` correspond
- Vérifier les filtres de recherche

---

## 🔄 Mises à jour futures

- [ ] Notifications push automatiques (18h si pas de rapport)
- [ ] Export Excel des rapports
- [ ] Génération automatique factures sous-traitants
- [ ] Scan QR code projet
- [ ] Reconnaissance vocale pour notes
- [ ] Mode équipe (plusieurs sous-traitants sur 1 projet)

---

## 📞 Support

Pour toute question ou problème:
- Email: support@mv3pro.com
- Documentation: https://docs.mv3pro.com

---

**Version:** 1.0.0
**Date:** Janvier 2025
**Développé par:** MV3 PRO
