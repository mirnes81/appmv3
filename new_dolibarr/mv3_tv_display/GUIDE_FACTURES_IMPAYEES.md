# 💰 GUIDE - FACTURES IMPAYÉES ET OBJECTIFS

## ✅ CE QUI A ÉTÉ AJOUTÉ

### 1. **Section Factures Impayées dans Mode Direction**

Un nouveau tableau affiche automatiquement:

#### 👥 Factures Clients Impayées:
- **Nombre total** de factures non payées
- **Montant total** en CHF
- **Détail par année** (nombre + montant)

#### 📦 Factures Fournisseurs Impayées:
- **Nombre total** de factures non payées
- **Montant total** en CHF
- **Détail par année** (nombre + montant)

---

## 🎯 COMMENT CONFIGURER LES OBJECTIFS

### Étape 1: Aller dans la configuration

```
URL: https://crm.mv-3pro.ch/custom/mv3_tv_display/admin/config.php
```

Ou via le menu:
```
Outils → TV Display → Configuration
```

### Étape 2: Section "🎯 Objectifs"

Tu verras 3 champs:

1. **Objectif CA Mensuel (CHF)**
   - Exemple: `300000` (pour 300'000 CHF/mois)
   - Ce montant sera comparé au CA du mois en cours

2. **Objectif m² par semaine**
   - Exemple: `500` (pour 500 m²/semaine)
   - Objectif de production pour chaque équipe

3. **Objectif Rapports par semaine**
   - Exemple: `5` (5 rapports/semaine)
   - Nombre de rapports attendus par équipe

### Étape 3: Activer/Désactiver l'affichage

Dans la section "📺 Slides à afficher":

- ✅ **Afficher les factures impayées** → ON/OFF

Si activé, la section des factures impayées apparaîtra automatiquement dans le Mode Direction.

### Étape 4: Sauvegarder

Clique sur **"Enregistrer"** en bas de page.

---

## 📊 CE QUI EST AFFICHÉ

### Dans le Mode Direction:

#### Tableau "💰 Factures Impayées"

```
┌─────────────────────────────────┐  ┌─────────────────────────────────┐
│     👥 Factures Clients         │  │   📦 Factures Fournisseurs      │
│                                 │  │                                 │
│         12 factures             │  │         8 factures              │
│      CHF 125'450                │  │      CHF 45'200                 │
│                                 │  │                                 │
│  2024: 8 factures - 85'000 CHF  │  │  2024: 5 factures - 30'000 CHF  │
│  2023: 4 factures - 40'450 CHF  │  │  2023: 3 factures - 15'200 CHF  │
└─────────────────────────────────┘  └─────────────────────────────────┘
```

**Détails affichés pour chaque année:**
- Année (2024, 2023, etc.)
- Nombre de factures impayées
- Montant total en CHF

---

## 🔍 COMMENT ÇA FONCTIONNE

### Détection automatique des factures impayées

L'API interroge la base Dolibarr:

#### Pour les Clients:
```sql
SELECT factures FROM llx_facture
WHERE fk_statut = 1  -- Validée
AND paye = 0          -- Non payée
```

#### Pour les Fournisseurs:
```sql
SELECT factures FROM llx_facture_fourn
WHERE fk_statut = 1  -- Validée
AND paye = 0          -- Non payée
```

**Groupement par année**: Les factures sont regroupées automatiquement par année de création.

---

## 💡 EXEMPLES D'UTILISATION

### Exemple 1: Configuration typique

```
Objectif CA Mensuel: 300000 CHF
Objectif m²/semaine: 500 m²
Objectif Rapports: 5 par semaine
Afficher factures impayées: ✅ OUI
```

**Résultat**:
- Le Mode Direction affichera la progression vers 300'000 CHF
- Le Mode Équipe comparera la production à 500 m²
- Les factures impayées seront visibles en bas de page Direction

---

### Exemple 2: Masquer les factures impayées

```
Afficher factures impayées: ❌ NON
```

**Résultat**:
- La section "💰 Factures Impayées" ne s'affiche pas
- Le reste du tableau fonctionne normalement

---

## 🎨 DESIGN DU TABLEAU

### Couleurs:
- **Clients**: Bordure bleue (#3b82f6)
- **Fournisseurs**: Bordure orange (#f59e0b)
- **Montants**: Rouge (#ef4444) pour attirer l'attention

### Animation:
- Effet hover: la carte se soulève légèrement
- Transition douce

### Responsive:
- 2 colonnes sur grand écran
- S'adapte automatiquement sur TV

---

## 📋 CHECKLIST POUR TESTER

### 1. Créer des factures impayées (pour tester)

Dans Dolibarr:
1. Va dans **Facturation** → **Nouvelle facture client**
2. Crée une facture
3. **Valide-la** (mais ne la marque PAS comme payée)
4. Répète pour avoir plusieurs factures

### 2. Vérifier l'affichage

1. Va sur: `https://crm.mv-3pro.ch/custom/mv3_tv_display/display/direction.php`
2. Scroll en bas de page
3. Tu devrais voir la section "💰 Factures Impayées"

### 3. Vérifier les données

- [ ] Le **nombre** de factures est correct
- [ ] Le **montant** total est correct
- [ ] Les **années** sont bien séparées
- [ ] Les factures **clients** et **fournisseurs** sont distinctes

---

## 🔧 PERSONNALISATION

### Changer les seuils d'alerte

Si tu veux qu'une alerte s'affiche quand trop de factures sont impayées, tu peux ajouter dans l'API:

```php
// Dans direction-data-real.php
if ($unpaid_invoices['total_clients']['nombre'] > 10) {
    $alerts[] = array(
        'severity' => 'critical',
        'icon' => '💰',
        'title' => 'Trop de factures clients impayées!',
        'message' => $unpaid_invoices['total_clients']['nombre'].' factures - CHF '.$unpaid_invoices['total_clients']['montant'],
        'time' => 'Maintenant'
    );
}
```

### Modifier l'affichage

Le CSS est dans `display/direction.php` sous la classe `.unpaid-card`.

Tu peux modifier:
- Les couleurs
- La taille des polices
- L'espacement
- Les animations

---

## 🐛 DÉPANNAGE

### Problème 1: "Aucune facture affichée" alors que j'en ai

**Causes possibles**:
1. Les factures ne sont pas **validées** (fk_statut != 1)
2. Les factures sont marquées comme **payées** (paye = 1)

**Solution**:
```sql
-- Vérifier l'état des factures
SELECT ref, fk_statut, paye, total_ttc
FROM llx_facture
WHERE entity = 1
ORDER BY datef DESC
LIMIT 10;

-- fk_statut = 0 : Brouillon
-- fk_statut = 1 : Validée
-- paye = 0 : Non payée
-- paye = 1 : Payée
```

### Problème 2: La section ne s'affiche pas

**Cause**: Option désactivée dans la config

**Solution**:
1. Va dans `/admin/config.php`
2. Active "Afficher les factures impayées"
3. Sauvegarde

Ou manuellement en SQL:
```sql
INSERT INTO llx_const (name, value, type, entity)
VALUES ('MV3_TV_SHOW_UNPAID', '1', 'chaine', 1)
ON DUPLICATE KEY UPDATE value = '1';
```

### Problème 3: Montants incorrects

**Cause**: Problème de requête SQL

**Solution**: Teste l'API directement:
```bash
curl https://crm.mv-3pro.ch/custom/mv3_tv_display/api/direction-data-real.php
```

Vérifie la section `unpaid_invoices` dans le JSON retourné.

---

## 📊 DONNÉES AFFICHÉES - RÉSUMÉ

| Donnée | Source | Format |
|--------|--------|--------|
| Nombre factures clients | `llx_facture` | Nombre entier |
| Montant clients | `llx_facture.total_ttc` | CHF avec séparateurs |
| Nombre factures fournisseurs | `llx_facture_fourn` | Nombre entier |
| Montant fournisseurs | `llx_facture_fourn.total_ttc` | CHF avec séparateurs |
| Détail par année | GROUP BY YEAR(datef) | Année + nombre + montant |

---

## ✅ AVANTAGES

### Pour la Direction:
- 📊 Vision immédiate des impayés
- 💰 Montants en temps réel
- 📅 Suivi par année
- ⚠️ Détection rapide des problèmes de trésorerie

### Pour la Gestion:
- 🎯 Objectifs configurables facilement
- 🔄 Mise à jour automatique toutes les 30s
- 📱 Affichage optimisé pour TV
- 💾 Données directement depuis Dolibarr

---

## 🚀 PROCHAINES ÉTAPES

Tu peux améliorer avec:

1. **Graphique évolution impayés** - Courbe sur 6 mois
2. **Alerte automatique** - Si > X factures impayées
3. **Détail par client** - Top 5 clients avec le plus d'impayés
4. **Relances automatiques** - Intégration email
5. **Prévisions trésorerie** - Basé sur dates d'échéance

---

**Version**: 2.0.0
**Date**: 15 janvier 2024
**Statut**: ✅ **PRODUCTION READY**

🎉 Les factures impayées sont maintenant visibles en temps réel sur ton écran TV!
