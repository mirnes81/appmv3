# 🚀 GUIDE RAPIDE - Templates Email

Guide pour utiliser les templates d'emails modernes dans Dolibarr.

---

## 📋 ÉTAPE 1 : Prévisualiser le template

1. **Ouvrir le fichier de prévisualisation** dans votre navigateur :
   ```
   /custom/mv3pro_portail/emails/preview_demo.html
   ```

2. **Vérifier le rendu** :
   - Design moderne ✅
   - Couleurs MV-3 PRO ✅
   - Responsive ✅
   - Toutes les sections présentes ✅

---

## 📧 ÉTAPE 2 : Ajouter le template dans Dolibarr

### Option A : Module Email (recommandé)

1. **Aller dans Dolibarr** :
   ```
   Configuration → Emails → Modèles d'emails
   ```

2. **Créer un nouveau modèle** :
   - Nom : `MV3PRO_Coordonnées_Bancaires`
   - Type : `Facture` ou `Commande`
   - Langue : `Français`

3. **Coller le code HTML** :
   - Copier le contenu de `template_coordonnees_bancaires.html`
   - Coller dans l'éditeur HTML
   - Sauvegarder

### Option B : Modèle d'email personnalisé (avancé)

1. **Créer un fichier PHP** dans Dolibarr :
   ```
   /htdocs/custom/mv3pro_portail/core/modules/mailings/mailing_mv3pro_banking.modules.php
   ```

2. **Code du module** :
   ```php
   <?php
   require_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';

   class mailing_mv3pro_banking extends MailingTargets
   {
       public $name = 'MV3PRO_Coordonnées_Bancaires';
       public $desc = 'Email moderne avec coordonnées bancaires';

       public function __construct($db)
       {
           $this->db = $db;
       }

       public function getHtmlContent()
       {
           // Charger le template
           $file = DOL_DOCUMENT_ROOT.'/custom/mv3pro_portail/emails/template_coordonnees_bancaires.html';
           return file_get_contents($file);
       }
   }
   ```

---

## 🔧 ÉTAPE 3 : Utiliser le template

### Envoyer un email depuis une facture

1. **Ouvrir la facture** dans Dolibarr

2. **Cliquer sur "Envoyer par email"**

3. **Sélectionner le modèle** :
   - Choisir : `MV3PRO_Coordonnées_Bancaires`

4. **Les variables sont remplacées automatiquement** :
   ```
   __REF__                    → FA-2024-001
   __REF_CLIENT__             → Client-REF-042
   __PROJECT_NAME__           → Nom du projet
   __EXTRAFIELD_PROPRIETAIRE__ → Propriétaire
   __EXTRAFIELD_APPARTEMENT__ → Villa B3
   ```

5. **Envoyer l'email** ✉️

---

## 🎨 ÉTAPE 4 : Personnaliser (optionnel)

### Changer les couleurs

Ouvrir `template_coordonnees_bancaires.html` et remplacer :

```css
#0891b2   →   VOTRE_COULEUR_1    (cyan principal)
#06b6d4   →   VOTRE_COULEUR_2    (cyan clair)
```

### Ajouter votre logo

Remplacer la section logo :

```html
<!-- Ancien : Logo texte "M" -->
<span style="font-size:36px; font-weight:800; color:#ffffff;">M</span>

<!-- Nouveau : Logo image -->
<img src="https://votre-site.ch/logo.png"
     alt="MV-3 PRO"
     width="72"
     height="72"
     style="display:block; border-radius:18px;">
```

### Modifier le texte

Éditer directement dans le HTML :

```html
<p style="margin:0; font-size:16px;">
    VOTRE NOUVEAU TEXTE ICI
</p>
```

---

## ✅ VÉRIFICATION

### Checklist avant envoi

- [ ] Template chargé dans Dolibarr
- [ ] Variables correctement remplacées
- [ ] Coordonnées bancaires à jour
- [ ] Test envoi à vous-même
- [ ] Vérification sur mobile
- [ ] Vérification sur Gmail
- [ ] Vérification sur Outlook

---

## 📱 TEST SUR DIFFÉRENTS CLIENTS

### Tester l'email

1. **Gmail** : Envoi de test → Vérifier le rendu
2. **Outlook** : Vérifier les gradients (fallback couleur unie)
3. **Mobile** : Ouvrir sur iPhone/Android
4. **Apple Mail** : Vérifier sur macOS

### Outils de test en ligne

- [Litmus](https://litmus.com) - Test professionnel
- [Email on Acid](https://www.emailonacid.com) - Test multi-clients
- [Mail Tester](https://www.mail-tester.com) - Score spam

---

## 🔍 VARIABLES DOLIBARR DISPONIBLES

### Variables standards

```
__THIRDPARTY_NAME__        - Nom du tiers
__REF__                    - Référence document
__DATE__                   - Date document
__DATE_DUE__               - Date échéance
__AMOUNT_HT__              - Montant HT
__AMOUNT_TTC__             - Montant TTC
__AMOUNT_VAT__             - Montant TVA
__SIGNATURE__              - Signature utilisateur
```

### Variables personnalisées (extrafields)

Créer des champs personnalisés dans :
```
Configuration → Dictionnaires → Champs personnalisés
```

Puis utiliser :
```
__EXTRAFIELD_NOM_DU_CHAMP__
```

Exemple :
```
__EXTRAFIELD_PROPRIETAIRE__
__EXTRAFIELD_APPARTEMENT__
__EXTRAFIELD_ETAGE__
```

---

## 🆘 DÉPANNAGE

### L'email s'affiche mal

**Problème** : Mise en page cassée

**Solution** :
1. Vérifier que le HTML est complet
2. Ne pas modifier la structure des tables
3. Tester dans l'éditeur HTML de Dolibarr

---

### Les variables ne sont pas remplacées

**Problème** : `__REF__` s'affiche tel quel

**Solution** :
1. Vérifier l'orthographe des variables
2. Utiliser des variables compatibles avec le type de document
3. Vérifier que le document a bien les champs remplis

---

### Le gradient ne s'affiche pas

**Problème** : Couleur unie au lieu du gradient

**Solution** :
- C'est normal sur Outlook (limitation)
- Le fallback couleur unie est prévu
- Aucune action nécessaire

---

### Les emojis ne s'affichent pas

**Problème** : Carrés à la place des emojis

**Solution** :
1. Vérifier l'encodage UTF-8
2. Remplacer par des images si nécessaire
3. Tester sur différents clients

---

## 📚 RESSOURCES

### Fichiers du template

```
emails/
├── template_coordonnees_bancaires.html    ← Template principal
├── preview_demo.html                       ← Aperçu avec données
├── README.md                               ← Documentation complète
└── GUIDE_RAPIDE.md                         ← Ce fichier
```

### Liens utiles

- **Documentation Dolibarr** : https://wiki.dolibarr.org/index.php/Email_templates
- **Test compatibilité** : https://www.caniemail.com
- **Générateur de gradients** : https://cssgradient.io

---

## 🎯 PROCHAINES ÉTAPES

1. ✅ Tester le template
2. ✅ Personnaliser les couleurs/logo
3. ✅ Envoyer un email de test
4. ✅ Valider sur mobile
5. ✅ Utiliser en production

---

## 💡 ASTUCES PRO

### 💾 Sauvegarder vos modèles

Créer plusieurs variantes :
- `MV3PRO_Coordonnées_Bancaires_FR`
- `MV3PRO_Coordonnées_Bancaires_EN`
- `MV3PRO_Rappel_Paiement`

### 📊 Suivre les ouvertures

Activer le tracking dans :
```
Configuration → Emails → Options → Tracking
```

### 🔐 Éviter les spams

1. Configurer SPF/DKIM
2. Ne pas utiliser trop de liens
3. Tester le score spam avant envoi
4. Éviter les mots comme "urgent", "gratuit", etc.

---

## ✨ SUCCÈS !

Votre template email moderne est prêt à être utilisé ! 🎉

Pour toute question :
📧 info@mv-3pro.ch
📞 +41 78 684 32 24
