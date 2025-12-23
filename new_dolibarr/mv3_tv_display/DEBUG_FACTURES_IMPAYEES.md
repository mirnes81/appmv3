# 🐛 DEBUG - FACTURES IMPAYÉES

## ❌ PROBLÈME: Les factures impayées ne s'affichent pas

### 🔍 ÉTAPE 1: TESTER LES DONNÉES

Ouvre cette URL dans ton navigateur:

```
https://crm.mv-3pro.ch/custom/mv3_tv_display/api/test-unpaid.php
```

Cette page va te montrer:
- ✅ Toutes tes factures (10 dernières)
- ✅ Quelles sont impayées
- ✅ Le statut exact (Validée? Payée?)
- ✅ Regroupement par année
- ✅ Factures clients ET fournisseurs

---

### 📋 COMPRENDRE LES STATUTS

#### Dans Dolibarr, une facture impayée doit avoir:

```
fk_statut = 1   ✅ Facture VALIDÉE (pas brouillon)
paye = 0        ❌ NON PAYÉE
```

#### Statuts possibles:

| fk_statut | Signification |
|-----------|---------------|
| 0 | Brouillon (ne compte PAS) |
| 1 | Validée (compte!) |
| 2 | Abandonnée |
| 3 | Payée |

| paye | Signification |
|------|---------------|
| 0 | Non payée ❌ |
| 1 | Payée ✅ |

---

### ✅ ÉTAPE 2: CRÉER DES FACTURES IMPAYÉES (POUR TESTER)

#### Factures Clients:
1. Va dans **Facturation** → **Nouvelle facture client**
2. Remplis les champs (client, montant)
3. Clique sur **"Valider"** ✅
4. **NE CLIQUE PAS** sur "Classer payée" ❌
5. La facture est maintenant: **Validée + Non payée** = Impayée!

#### Factures Fournisseurs:
1. Va dans **Fournisseurs** → **Nouvelle facture fournisseur**
2. Remplis les champs
3. Clique sur **"Valider"**
4. **NE CLIQUE PAS** sur "Classer payée"

---

### 🧪 ÉTAPE 3: VÉRIFIER L'API

Ouvre cette URL:
```
https://crm.mv-3pro.ch/custom/mv3_tv_display/api/direction-data-real.php
```

Cherche dans le JSON la section `"unpaid_invoices"`:

```json
"unpaid_invoices": {
    "clients": [
        {
            "annee": 2024,
            "nombre": 5,
            "montant": 12500
        }
    ],
    "fournisseurs": [],
    "total_clients": {
        "nombre": 5,
        "montant": 12500
    },
    "total_fournisseurs": {
        "nombre": 0,
        "montant": 0
    }
}
```

**Si tu vois des nombres > 0**, l'API fonctionne! ✅

---

### 📺 ÉTAPE 4: VÉRIFIER L'AFFICHAGE

Ouvre le Mode Direction:
```
https://crm.mv-3pro.ch/custom/mv3_tv_display/display/direction.php
```

#### Ouvre la Console JavaScript (F12):
Tu devrais voir:
```
Unpaid invoices: {clients: Array(1), total_clients: {...}}
Section affichée - Clients: true Fournisseurs: false
```

**Si tu vois "Section affichée"**, c'est bon! ✅

---

### 🔧 ÉTAPE 5: SOLUTIONS AUX PROBLÈMES COURANTS

#### Problème 1: "Aucune facture trouvée dans test-unpaid.php"

**Cause**: Pas de factures dans Dolibarr

**Solution**:
1. Crée au moins 1 facture client
2. Valide-la
3. Recharge test-unpaid.php

---

#### Problème 2: "Des factures existent mais sont toutes payées"

**Cause**: Toutes tes factures sont marquées comme payées (paye = 1)

**Solution SQL** (pour tester):
```sql
-- Marquer une facture comme NON payée
UPDATE llx_facture
SET paye = 0
WHERE rowid = 123;  -- Change 123 par l'ID de ta facture
```

Ou dans Dolibarr:
1. Ouvre une facture payée
2. Clique sur **"Classer impayée"** (si disponible)

---

#### Problème 3: "L'API retourne des données mais l'affichage est vide"

**Cause**: Problème JavaScript ou section cachée

**Solution**:
1. Ouvre la Console (F12)
2. Vérifie les erreurs JavaScript
3. Tape dans la console:
```javascript
document.getElementById('unpaidSection').style.display = 'block';
```

Si la section apparaît, c'est un problème de condition d'affichage.

---

#### Problème 4: "Section visible mais affiche 0 partout"

**Cause**: Données non chargées ou formatNumber() échoue

**Solution**: Dans la console, tape:
```javascript
fetch('/custom/mv3_tv_display/api/direction-data-real.php')
  .then(r => r.json())
  .then(d => console.log(d.unpaid_invoices));
```

Vérifie que les données sont bien présentes.

---

### 🗄️ ÉTAPE 6: VÉRIFICATION DIRECTE EN SQL

Connecte-toi à MySQL:
```bash
mysql -u root -p dolibarr
```

#### Test 1: Factures clients impayées
```sql
SELECT
    f.rowid,
    f.ref,
    f.fk_statut,
    f.paye,
    f.total_ttc,
    YEAR(f.datef) as annee
FROM llx_facture f
WHERE f.fk_statut = 1
AND f.paye = 0
ORDER BY f.datef DESC;
```

**Résultat attendu**: Liste de factures si tu en as.

#### Test 2: Comptage par année
```sql
SELECT
    YEAR(datef) as annee,
    COUNT(*) as nombre,
    SUM(total_ttc) as montant
FROM llx_facture
WHERE fk_statut = 1
AND paye = 0
GROUP BY YEAR(datef);
```

#### Test 3: Factures fournisseurs
```sql
SELECT COUNT(*) as nb, SUM(total_ttc) as montant
FROM llx_facture_fourn
WHERE fk_statut = 1
AND paye = 0;
```

---

### 🎯 CHECKLIST DE DIAGNOSTIC

- [ ] J'ai créé au moins 1 facture dans Dolibarr
- [ ] La facture est **VALIDÉE** (pas brouillon)
- [ ] La facture est **NON PAYÉE** (pas classée comme payée)
- [ ] `test-unpaid.php` affiche mes factures en jaune
- [ ] L'API `direction-data-real.php` contient `unpaid_invoices` avec nombre > 0
- [ ] La console JavaScript affiche "Section affichée"
- [ ] La section "💰 Factures Impayées" est visible sur la page

---

### 📸 CAPTURES D'ÉCRAN ATTENDUES

#### Dans test-unpaid.php:
```
✅ Nombre de factures impayées: 5

┌────────────┬────────┬─────────────┬────────────┐
│ Ref        │ Année  │ Montant     │ Date       │
├────────────┼────────┼─────────────┼────────────┤
│ FA2401-001 │ 2024   │ CHF 2,500   │ 2024-01-15 │
│ FA2401-002 │ 2024   │ CHF 3,200   │ 2024-01-18 │
└────────────┴────────┴─────────────┴────────────┘
```

#### Dans l'API (JSON):
```json
"unpaid_invoices": {
    "clients": [
        {"annee": 2024, "nombre": 5, "montant": 12500}
    ],
    "total_clients": {"nombre": 5, "montant": 12500}
}
```

#### Sur la page:
```
💰 Factures Impayées

┌──────────────────────┐  ┌──────────────────────┐
│ 👥 Factures Clients  │  │ 📦 Fournisseurs      │
│                      │  │                      │
│   5 factures         │  │   0 factures         │
│   CHF 12,500         │  │   CHF 0              │
│                      │  │                      │
│ 2024: 5 - 12,500 CHF │  │ Aucune facture       │
└──────────────────────┘  └──────────────────────┘
```

---

### 🆘 SI RIEN NE FONCTIONNE

Envoie-moi:
1. Capture d'écran de `test-unpaid.php`
2. Le JSON complet de `direction-data-real.php`
3. La console JavaScript (F12) avec les erreurs
4. Résultat de cette requête SQL:
```sql
SELECT COUNT(*) FROM llx_facture WHERE fk_statut = 1 AND paye = 0;
```

---

### ✅ SOLUTION RAPIDE POUR TESTER

Si tu veux juste voir le rendu, voici une astuce:

**1. Force l'affichage dans la console:**
```javascript
// Ouvre direction.php
// Appuie sur F12
// Colle ce code:

const fakeData = {
    clients: [{annee: 2024, nombre: 8, montant: 45000}],
    fournisseurs: [{annee: 2024, nombre: 3, montant: 12000}],
    total_clients: {nombre: 8, montant: 45000},
    total_fournisseurs: {nombre: 3, montant: 12000}
};

renderUnpaidInvoices(fakeData);
```

La section devrait apparaître avec des données de test!

---

**Version**: 2.0.0
**Date**: 15 janvier 2024

🔧 Si tu suis ce guide, tu trouveras le problème!
