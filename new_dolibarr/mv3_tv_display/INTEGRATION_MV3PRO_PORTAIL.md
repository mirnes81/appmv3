# 🔗 INTÉGRATION AVEC MV3PRO_PORTAIL

## ✅ CE QUI EST MAINTENANT CONNECTÉ

### 📊 MODE DIRECTION (`direction-data-real.php`)

Utilise les **VRAIES DONNÉES** de ton Dolibarr:

#### Chiffre d'Affaires
- ✅ **CA Total**: Somme de toutes les factures validées (`llx_facture`)
- ✅ **CA du Mois**: Factures du mois en cours
- ✅ **CA du Jour**: Factures d'aujourd'hui
- ✅ **Marge Globale**: Calculée automatiquement (TTC vs HT)

#### Projets
- ✅ **Projets Actifs**: Comptés depuis `llx_projet` (statut = 1)
- ✅ **Projets Dans les Temps**: Date fin > aujourd'hui
- ✅ **Projets en Retard**: Date fin < aujourd'hui
- ✅ **Progression**: Calculée depuis les m² réalisés dans `llx_mv3_rapport`

#### Production
- ✅ **m² du Mois**: Somme depuis `llx_mv3_rapport.surface_carrelee`
- ✅ **m² du Jour**: Rapports d'aujourd'hui
- ✅ **Moyenne m²/jour**: Calculée sur le mois

#### Équipes
- ✅ **Ouvriers Total**: Comptés depuis `llx_user` (statut actif)
- ✅ **Équipes Actives**: Users ayant créé un rapport aujourd'hui
- ✅ **Taux de Présence**: % équipes actives / total

#### Alertes
- ✅ **Signalements Critiques**: Depuis `llx_mv3_signalement` (priorité haute/critique, non résolus)
- ✅ **Projets en Retard**: Détection automatique des retards

#### Graphique Évolution
- ✅ **7 Derniers Jours**: CA par jour depuis les factures

---

### 👥 MODE ÉQUIPE (`equipe-data-real.php`)

Utilise les **VRAIES DONNÉES** de ton module MV3pro_portail:

#### Infos Équipe
- ✅ **Nom**: Basé sur le user Dolibarr
- ✅ **Production Semaine**: m² de la semaine depuis `llx_mv3_rapport`

#### Membres
- ✅ **Liste**: Depuis `llx_user` (users actifs)

#### Tâches du Jour
- ✅ **Rapports d'Aujourd'hui**: Depuis `llx_mv3_rapport`
- ✅ **Statut**: Basé sur `statut` et `heures_fin`
- ✅ **Localisation**: Depuis `zone_travail`

#### Objectifs
- ✅ **m² Semaine**: Progression vs objectif configuré
- ✅ **Rapports**: Nombre de rapports cette semaine
- ✅ **Qualité**: À implémenter selon ton système

#### Classement
- ✅ **Top Performers**: Classés par m² cette semaine
- ✅ **Badges Automatiques**:
  - ⚡ ≥ 200 m²
  - ⭐ ≥ 150 m²
  - 📅 ≥ 5 rapports

#### Photos
- ✅ **Photos de la Semaine**: Depuis `llx_mv3_rapport_photo`
- ✅ **Liées aux Rapports**: Avec infos projet

#### Message de Motivation
- ✅ **Dynamique**: Basé sur le % d'objectif atteint

---

## 🗄️ TABLES UTILISÉES

### Tables Dolibarr Standard:
```
llx_facture          → CA, Chiffre d'affaires
llx_projet           → Projets actifs, dates, budgets
llx_user             → Équipes, ouvriers
llx_societe          → Clients (futur)
```

### Tables MV3pro_portail:
```
llx_mv3_rapport              → Rapports journaliers, m², heures
llx_mv3_rapport_photo        → Photos des chantiers
llx_mv3_signalement          → Alertes, problèmes
llx_mv3_sens_pose            → Sens de pose (futur)
llx_mv3_materiel             → Matériel (futur)
```

---

## 🔧 CONFIGURATION

### Dans Dolibarr Admin:
`Outils` → `TV Display` → Configuration

Paramètres utilisés:
- `MV3_TV_GOAL_CA_MOIS` - Objectif CA mensuel (défaut: 300 000 CHF)
- `MV3_TV_GOAL_M2` - Objectif m² par semaine (défaut: 500 m²)
- `MV3_TV_GOAL_RAPPORTS` - Objectif rapports par semaine (défaut: 5)

---

## 📱 UTILISATION

### Mode Direction:
```
https://crm.mv-3pro.ch/custom/mv3_tv_display/display/direction.php
```

**Affiche**:
- KPIs globaux en temps réel
- Graphiques évolution CA
- Alertes critiques uniquement
- Grille projets actifs

**Rafraîchissement**: Toutes les 30 secondes (configurable)

---

### Mode Équipe:
```
https://crm.mv-3pro.ch/custom/mv3_tv_display/display/equipe.php?user_id=X
```

**Paramètres**:
- `user_id` - ID du user Dolibarr (optionnel, prend le 1er si vide)
- `equipe_id` - Pour future gestion d'équipes (non utilisé actuellement)

**Affiche**:
- Planning du jour (rapports)
- Objectifs personnalisés
- Classement interne
- Photos de la semaine
- Message de motivation

**Rafraîchissement**: Toutes les 30 secondes

---

## 🎯 DONNÉES EN TEMPS RÉEL

### Ce qui est 100% dynamique:
- ✅ CA (depuis factures)
- ✅ Projets (depuis llx_projet)
- ✅ m² (depuis rapports)
- ✅ Équipes actives (depuis rapports du jour)
- ✅ Alertes (depuis signalements)
- ✅ Photos (depuis rapport_photo)
- ✅ Classement (recalculé à chaque refresh)

### Ce qui nécessite configuration:
- ⚙️ Objectifs (CA, m², rapports) → dans config module
- ⚙️ Badges automatiques → seuils définis dans API
- ⚙️ Messages motivation → générés selon performance

---

## 🚀 AMÉLIORATIONS FUTURES POSSIBLES

### Facile à ajouter:
1. **Filtrage par projet** - Afficher stats d'un projet spécifique
2. **Historique photos** - Slider temporel des photos
3. **Notifications push** - Alertes navigateur pour signalements
4. **Export PDF** - Rapport de performance

### Nécessite développement:
1. **Système de notation** - Noter la qualité des chantiers
2. **Prédictions IA** - Estimer date fin projet
3. **Gestion d'équipes** - Créer vraies équipes dans Dolibarr
4. **Objectifs variables** - Objectifs par projet/équipe

---

## 🐛 DÉPANNAGE

### "Aucune donnée affichée"
**Causes possibles**:
1. Pas de factures validées → Créer/valider des factures
2. Pas de rapports → Créer des rapports via MV3pro_portail
3. Erreur SQL → Vérifier logs PHP

**Solution**:
```bash
# Logs Apache
tail -f /var/log/apache2/error.log

# Ou tester l'API directement:
curl https://crm.mv-3pro.ch/custom/mv3_tv_display/api/direction-data-real.php
```

### "Photos ne s'affichent pas"
**Cause**: Chemin filepath incorrect dans `llx_mv3_rapport_photo`

**Solution**: Vérifier que les photos sont accessibles:
```sql
SELECT filepath, filename FROM llx_mv3_rapport_photo LIMIT 5;
```

Le chemin doit être relatif à `/custom/mv3pro_portail/rapports/photo.php?file=XXX`

### "Certaines équipes manquent"
**Cause**: Users inactifs ou sans droits

**Solution**:
```sql
-- Vérifier users actifs
SELECT rowid, firstname, lastname, statut FROM llx_user WHERE entity = 1;

-- Activer un user
UPDATE llx_user SET statut = 1 WHERE rowid = X;
```

---

## 📊 EXEMPLE DE DONNÉES REQUISES

Pour que les écrans TV soient intéressants, il faut:

### Minimum:
- ✅ 3-5 projets actifs
- ✅ 10+ rapports ce mois
- ✅ 2-3 signalements
- ✅ 5+ photos

### Idéal:
- ✅ 10+ projets actifs
- ✅ 50+ rapports ce mois
- ✅ 5+ équipes actives
- ✅ 20+ photos cette semaine

---

## 🔗 LIENS UTILES

- **Documentation complète**: `README_COMPLET.md`
- **Installation**: `INSTALLATION_RAPIDE.md`
- **Fonctionnalités avancées**: `FONCTIONNALITES_AVANCEES.md`

---

## ✅ CHECKLIST VÉRIFICATION

- [ ] Module TV Display activé dans Dolibarr
- [ ] Module MV3pro_portail activé et fonctionnel
- [ ] Au moins 1 facture validée
- [ ] Au moins 1 projet actif
- [ ] Au moins 1 rapport créé ce mois
- [ ] Configuration module remplie (objectifs)
- [ ] Mode Direction s'affiche en plein écran
- [ ] Mode Équipe s'affiche en plein écran
- [ ] Données réelles affichées (pas 0 partout)
- [ ] Photos visibles
- [ ] Classement affiché

---

**Version**: 2.0.0
**Date**: 15 janvier 2024
**Statut**: ✅ **PRODUCTION READY avec VRAIES DONNÉES**

🎉 Tes écrans TV affichent maintenant les VRAIES données de ton Dolibarr en temps réel!
