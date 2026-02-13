# 📧 Templates Email MV-3 PRO

Templates d'emails modernes et professionnels pour Dolibarr.

---

## 📋 Template : Coordonnées Bancaires

Un email élégant et moderne pour envoyer les coordonnées bancaires aux clients.

### ✨ Caractéristiques

- **Design Premium** : Gradient moderne, ombres douces, coins arrondis
- **Responsive** : S'adapte parfaitement aux mobiles et desktops
- **Mise en page claire** : Hiérarchie visuelle optimale
- **Couleurs MV-3 PRO** : Cyan (#0891b2) et bleu clair (#06b6d4)
- **Icônes modernes** : Emojis pour une meilleure lisibilité
- **IBAN mis en valeur** : Police monospace, fond coloré
- **Footer complet** : Liens de contact, adresse, disclaimer

---

## 🚀 Installation dans Dolibarr

### Méthode 1 : Utiliser directement le fichier HTML

1. **Copier le contenu** du fichier `template_coordonnees_bancaires.html`

2. **Aller dans Dolibarr** :
   - Configuration → Emails
   - Modèles d'emails
   - Créer un nouveau modèle

3. **Coller le code HTML** dans l'éditeur

4. **Remplacer les variables Dolibarr** :
   ```
   __REF__                    → Référence commande
   __REF_CLIENT__             → Référence client
   __PROJECT_NAME__           → Nom du projet
   __EXTRAFIELD_PROPRIETAIRE__ → Client final
   __EXTRAFIELD_APPARTEMENT__ → Villa/Appartement
   ```

### Méthode 2 : Créer un modèle d'email personnalisé

1. **Créer un fichier dans Dolibarr** :
   ```
   /htdocs/core/modules/mailings/mv3pro_banking.modules.php
   ```

2. **Créer une classe** qui étend `MailingTargets`

3. **Référencer le template HTML**

---

## 🎨 Variables disponibles

### Variables Dolibarr standards

```html
__REF__                    - Référence du document
__DATE__                   - Date du document
__AMOUNT_TTC__             - Montant TTC
__THIRDPARTY_NAME__        - Nom du tiers
__SIGNATURE__              - Signature de l'utilisateur
```

### Variables personnalisées (extrafields)

```html
__EXTRAFIELD_PROPRIETAIRE__  - Client final
__EXTRAFIELD_APPARTEMENT__   - Villa/Appartement
__EXTRAFIELD_XXX__           - Tout autre champ personnalisé
```

---

## 📝 Exemples d'utilisation

### 1. Email de facture avec coordonnées bancaires

Utiliser ce template pour :
- Factures clients
- Devis acceptés
- Commandes confirmées

### 2. Personnalisation

Modifier les sections selon vos besoins :

```html
<!-- Ajouter une section -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:28px;">
    <tr>
        <td style="padding:20px; background:#f1f5f9; border-radius:12px;">
            <strong>Votre nouveau contenu ici</strong>
        </td>
    </tr>
</table>
```

---

## 🎨 Personnalisation des couleurs

### Couleurs principales

```css
Cyan principal   : #0891b2
Cyan clair       : #06b6d4
Jaune alerte     : #fbbf24
Fond clair       : #f8fafc
Texte foncé      : #0f172a
Texte gris       : #64748b
```

### Modifier les couleurs

Rechercher et remplacer :
- `#0891b2` → Votre couleur primaire
- `#06b6d4` → Votre couleur secondaire

---

## 📱 Test de compatibilité

Ce template est testé et compatible avec :

✅ Gmail (Desktop & Mobile)
✅ Outlook 2016-2021
✅ Apple Mail (iOS & macOS)
✅ Thunderbird
✅ Yahoo Mail
✅ ProtonMail
✅ Mobile (iPhone, Android)

---

## 🔧 Troubleshooting

### L'email s'affiche mal sur Outlook

Outlook utilise Word pour le rendu HTML. Solutions :

1. **Utiliser des tables** au lieu de div (✅ déjà fait)
2. **Éviter les CSS complexes** (✅ déjà fait)
3. **Tester avec** [Litmus](https://litmus.com) ou [Email on Acid](https://www.emailonacid.com)

### Les images ne s'affichent pas

1. Vérifier que les emojis sont supportés
2. Héberger le logo sur un serveur externe
3. Utiliser des balises `<img>` avec chemins absolus

### Le gradient ne s'affiche pas

Certains clients email ne supportent pas les gradients. Un fallback est prévu :

```html
<!-- Gradient moderne -->
background:linear-gradient(135deg,#0891b2 0%,#06b6d4 100%);

<!-- Si gradient non supporté, couleur unie s'affiche -->
background:#0891b2;
```

---

## 📦 Structure des fichiers

```
emails/
├── README.md                               ← Ce fichier
├── template_coordonnees_bancaires.html     ← Template principal
└── preview/                                 ← Screenshots (à créer)
    ├── desktop.png
    ├── mobile.png
    └── outlook.png
```

---

## 🎯 Prochaines étapes

### Créer d'autres templates

1. **Email de bienvenue**
2. **Confirmation de commande**
3. **Notification de livraison**
4. **Rappel de paiement**
5. **Newsletter**

### Améliorer le template

1. Ajouter un logo en image
2. Créer des variations de couleurs
3. Ajouter des boutons d'action
4. Version avec/sans coordonnées bancaires

---

## 💡 Conseils d'utilisation

### ✅ À FAIRE

- Tester l'email avant envoi
- Vérifier toutes les variables
- Adapter le texte à votre audience
- Garder le message concis

### ❌ À ÉVITER

- Trop de couleurs différentes
- Texte trop long
- Images lourdes
- Liens externes nombreux

---

## 📞 Support

Pour toute question ou personnalisation :
- Email : info@mv-3pro.ch
- Tél : +41 78 684 32 24
- Web : www.mv-3pro.ch

---

## 📄 Licence

© 2024 MV-3 PRO - Tous droits réservés

Ce template est fourni pour usage interne MV-3 PRO uniquement.
