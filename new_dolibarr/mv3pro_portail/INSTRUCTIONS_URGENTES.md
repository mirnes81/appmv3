# INSTRUCTIONS URGENTES - Erreur 404 résolue

## Problème

Vous obtenez une erreur 404 lorsque vous cliquez sur un événement du planning.

## Cause

Le Service Worker de votre navigateur a mis en cache une **ancienne version** de l'application qui pointe vers des fichiers inexistants.

## Solution IMMÉDIATE

### Étape 1: Ouvrir la page de rechargement

Sur votre mobile, ouvrez cette URL:

```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/FORCE_RELOAD.html
```

### Étape 2: Suivre les 3 étapes

1. Cliquez sur **"1. Désactiver le Service Worker"**
2. Cliquez sur **"2. Vider le cache complet"**
3. Cliquez sur **"3. Effacer le token"**

### Étape 3: Rouvrir l'application

Cliquez sur **"Ouvrir l'application"** et reconnectez-vous.

---

## Alternative manuelle (si la page ci-dessus ne fonctionne pas)

### Sur iPhone (Safari)

1. Ouvrir **Réglages** → **Safari**
2. Cliquer sur **"Effacer historique et données de sites"**
3. Confirmer
4. Rouvrir l'application et se reconnecter

### Sur Android (Chrome)

1. Ouvrir **Chrome**
2. Menu (⋮) → **Paramètres**
3. **Confidentialité** → **Effacer les données de navigation**
4. Cocher **"Images et fichiers en cache"**
5. Cliquer sur **"Effacer les données"**
6. Rouvrir l'application et se reconnecter

---

## Vérification

Après avoir suivi ces étapes:

1. Ouvrez l'application: `https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/`
2. Connectez-vous
3. Allez sur **Planning**
4. Cliquez sur un événement
5. Vous devriez maintenant voir les 3 onglets: **Détails, Photos, Fichiers**

---

## URLs utiles

| Page | URL |
|------|-----|
| **Application** | https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/ |
| **Forcer rechargement** | https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/FORCE_RELOAD.html |
| **Mode Debug** | https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/DEBUG_MODE.html |
| **Aide** | https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/AIDE.html |

---

## Changements effectués (pour l'administrateur)

1. ✅ Rebuild complet de la PWA avec les dernières modifications
2. ✅ Création de la page `FORCE_RELOAD.html` pour forcer le rechargement
3. ✅ Création de la page `DEBUG_MODE.html` pour le diagnostic
4. ✅ Création de la page `AIDE.html` avec instructions complètes
5. ✅ Redirections automatiques de l'ancienne version vers la nouvelle
6. ✅ Service Worker mis à jour avec nouveau hash

---

## Pourquoi cette erreur?

Le Service Worker est une technologie qui permet aux applications web de fonctionner offline en mettant en cache les fichiers.

**Problème:** Quand on rebuild l'application, les noms de fichiers changent (ex: `index-ABC123.js` → `index-XYZ789.js`), mais le Service Worker continue de chercher les anciens noms, d'où l'erreur 404.

**Solution:** Forcer le rechargement complet supprime l'ancien Service Worker et le remplace par le nouveau qui connaît les bons noms de fichiers.

---

## Support

Si le problème persiste après avoir suivi ces instructions:

1. Vérifier la console JavaScript (F12) pour voir les erreurs détaillées
2. Activer le mode debug via `DEBUG_MODE.html`
3. Vérifier les logs Apache/PHP côté serveur
4. Contacter le support technique avec une capture d'écran

---

## Note importante

Cette procédure doit être effectuée **une seule fois** après la mise à jour. Après cela, l'application fonctionnera normalement et se mettra à jour automatiquement.
