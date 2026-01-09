# Activation du Mode Debug sans SSH (via FTP uniquement)

## Problème résolu
Sur Hoststar, pas d'accès SSH disponible. Le mode debug nécessite maintenant uniquement un fichier flag dans le dossier web accessible via FTP.

---

## 1. STRUCTURE DES FICHIERS SUR HOSTSTAR

```
httpdocs/                          <- Racine web Hoststar
└── custom/
    └── mv3pro_portail/            <- Module Dolibarr
        ├── debug.flag             <- Fichier d'activation (à créer via FTP)
        ├── api/
        │   └── v1/
        │       └── debug.php      <- API de diagnostic
        └── pwa_dist/              <- PWA compilée
            └── index.html
```

---

## 2. ACTIVATION DU MODE DEBUG (via FTP)

### Étape 1: Créer le fichier d'activation

Via votre client FTP (FileZilla, WinSCP, etc.) :

1. Connectez-vous au serveur Hoststar
2. Naviguez vers : `httpdocs/custom/mv3pro_portail/`
3. Créez un **fichier vide** nommé : `debug.flag`

**Options pour créer le fichier vide :**
- **FileZilla** : Clic droit > "Créer un nouveau fichier" > Nommez-le `debug.flag`
- **WinSCP** : Fichier > Nouveau > Fichier > Nommez-le `debug.flag`
- **Ou** : Créez un fichier texte vide sur votre ordinateur et uploadez-le

**Important** : Le fichier doit être **complètement vide** (0 octets)

### Étape 2: Vérifier l'activation

Le mode debug est maintenant actif. Vous pouvez vérifier en accédant à :

```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php
```

---

## 3. CHEMINS SERVEUR EXACTS POUR HOSTSTAR

### Upload des fichiers

| Fichier local | Chemin serveur Hoststar |
|---------------|-------------------------|
| `debug.flag` | `httpdocs/custom/mv3pro_portail/debug.flag` |
| `api/v1/debug.php` | `httpdocs/custom/mv3pro_portail/api/v1/debug.php` |
| `pwa_dist/*` | `httpdocs/custom/mv3pro_portail/pwa_dist/*` |

### Vérification de l'arborescence

Après upload, votre structure doit être :

```
httpdocs/
└── custom/
    └── mv3pro_portail/
        ├── debug.flag                    ✓ Fichier vide (0 octets)
        ├── api/
        │   └── v1/
        │       └── debug.php             ✓ API de diagnostic
        └── pwa_dist/
            ├── index.html
            ├── manifest.webmanifest
            ├── sw.js
            └── assets/
```

---

## 4. TEST DE L'API DEBUG.PHP

### Test 1: Sans authentification

```bash
curl -I https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php
```

**Codes attendus :**
- ✅ `200 OK` : Le fichier debug.flag a été détecté, le diagnostic s'exécute
- ❌ `403 Forbidden` : Le fichier debug.flag n'existe pas ou n'est pas au bon endroit
- ❌ `404 Not Found` : Le fichier debug.php n'existe pas à cet emplacement

### Test 2: Avec authentification

```bash
curl -H "X-Auth-Token: VOTRE_TOKEN" \
     https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php
```

**Codes attendus :**
- ✅ `200 OK` : Authentification valide + fichier debug.flag présent
- ⚠️ `403 Forbidden` : Token invalide ou fichier debug.flag absent

### Test 3: Depuis le navigateur

Ouvrez directement dans votre navigateur :

```
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php
```

**Résultat attendu :**
- Si `debug.flag` existe : JSON avec le rapport de diagnostic complet
- Sinon : Message d'erreur JSON avec code 403

---

## 5. ACCÈS À LA PAGE /#/DEBUG DANS LA PWA

### Problème : Cache du Service Worker

La PWA utilise un Service Worker qui met en cache toutes les routes. Si vous avez des problèmes pour accéder à `/#/debug` :

### Solution 1: Vider le cache du navigateur (recommandé)

**Chrome / Edge :**
1. Ouvrez la PWA
2. Appuyez sur `F12` pour ouvrir les DevTools
3. Cliquez sur l'onglet **"Application"**
4. Dans le menu de gauche, section **"Storage"** :
   - Cliquez sur **"Clear site data"**
   - Cochez toutes les cases
   - Cliquez sur **"Clear site data"**
5. Fermez les DevTools
6. Rechargez la page (`Ctrl+Shift+R` ou `Cmd+Shift+R`)

**Firefox :**
1. Ouvrez la PWA
2. Appuyez sur `F12`
3. Onglet **"Storage"** (ou "Stockage")
4. Cliquez droit sur le domaine > **"Delete All"**
5. Rechargez (`Ctrl+Shift+R`)

**Safari :**
1. Menu Safari > Préférences > Avancées
2. Cochez "Afficher le menu Développement"
3. Menu Développement > Vider les caches
4. Rechargez la page

### Solution 2: Mode Incognito

Ouvrez la PWA dans une fenêtre de navigation privée :
- Chrome/Edge : `Ctrl+Shift+N` (Windows) ou `Cmd+Shift+N` (Mac)
- Firefox : `Ctrl+Shift+P`
- Safari : `Cmd+Shift+N`

Puis accédez à :
```
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug
```

### Solution 3: Désinscrire le Service Worker manuellement

**Chrome DevTools :**
1. `F12` > Onglet **"Application"**
2. Section **"Service Workers"** (menu de gauche)
3. Cliquez sur **"Unregister"** pour chaque Service Worker listé
4. Rechargez la page

---

## 6. VÉRIFICATION COMPLÈTE

### Checklist avant de tester

- [ ] Fichier `debug.flag` uploadé dans `httpdocs/custom/mv3pro_portail/`
- [ ] Fichier `debug.php` uploadé dans `httpdocs/custom/mv3pro_portail/api/v1/`
- [ ] Dossier `pwa_dist` uploadé dans `httpdocs/custom/mv3pro_portail/`
- [ ] Cache du navigateur vidé
- [ ] Service Worker désactivé (ou mode incognito)

### Test final

1. **Test de l'API directement** :
   ```
   https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php
   ```
   → Devrait retourner un JSON avec le rapport complet

2. **Test de la PWA** :
   ```
   https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/login
   ```
   → Login puis accéder à :
   ```
   https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug
   ```

---

## 7. DÉSACTIVATION DU MODE DEBUG

Pour désactiver le mode debug :

**Via FTP :**
1. Connectez-vous au serveur
2. Supprimez le fichier : `httpdocs/custom/mv3pro_portail/debug.flag`

C'est tout ! Le mode debug sera immédiatement désactivé.

---

## 8. DÉPANNAGE

### Problème : 403 Forbidden sur debug.php

**Causes possibles :**
1. Le fichier `debug.flag` n'existe pas
2. Le fichier `debug.flag` n'est pas au bon endroit
3. Les permissions du fichier sont incorrectes

**Solution :**
```bash
# Via FTP, vérifiez que le fichier existe à :
httpdocs/custom/mv3pro_portail/debug.flag

# Permissions recommandées (si votre hébergeur permet de les modifier) :
644 (rw-r--r--)
```

### Problème : 404 Not Found sur debug.php

**Causes possibles :**
1. Le fichier `debug.php` n'a pas été uploadé
2. Le chemin est incorrect

**Solution :**
Vérifiez que le fichier existe à :
```
httpdocs/custom/mv3pro_portail/api/v1/debug.php
```

### Problème : Page /#/debug ne charge pas

**Causes :**
- Service Worker en cache

**Solution :**
1. Videz complètement le cache (voir section 5)
2. Utilisez le mode incognito
3. Vérifiez que la PWA est bien à jour

### Problème : JSON avec "success: false"

**Causes :**
- Erreur dans le code PHP
- Base de données inaccessible
- Tables manquantes

**Solution :**
1. Consultez le JSON retourné pour voir l'erreur exacte
2. Vérifiez les logs PHP du serveur si disponibles
3. Exportez le rapport JSON et partagez-le pour analyse

---

## 9. RÉSUMÉ RAPIDE

### Pour activer le debug :
```bash
# Via FTP, créez un fichier vide :
httpdocs/custom/mv3pro_portail/debug.flag
```

### Pour tester :
```bash
# URL directe :
https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php

# Dans la PWA (après login) :
https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug
```

### Pour désactiver :
```bash
# Via FTP, supprimez le fichier :
httpdocs/custom/mv3pro_portail/debug.flag
```

---

## Support

Si vous rencontrez des problèmes :
1. Exportez le rapport JSON depuis la page /#/debug
2. Partagez le rapport pour analyse
3. Vérifiez les logs d'erreur PHP si disponibles sur Hoststar
