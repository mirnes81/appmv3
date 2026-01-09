# 🚨 ACTIONS IMMÉDIATES - Corriger les erreurs 500/510

## ⏱️ À faire MAINTENANT (5 minutes)

### Action 1 : Créer `.htaccess` dans `pwa_dist/`

**Via FTP (FileZilla) ou SSH** :

1. Connectez-vous à Hoststar
2. Naviguez vers : `/custom/mv3pro_portail/pwa_dist/`
3. Créez un nouveau fichier : `.htaccess`
4. Copiez le contenu du fichier `FIX_1_htaccess_pwa_dist.txt`
5. Sauvegardez

**Vérification** :
```
Permissions : -rw-r--r-- (644)
Taille : ~2 Ko
```

---

### Action 2 : Corriger `cors_config.php`

**Via FTP (FileZilla) ou SSH** :

1. Ouvrez : `/custom/mv3pro_portail/api/cors_config.php`
2. Ligne 43, remplacez :
```php
// AVANT
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Client-Info, Apikey');

// APRÈS
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-MV3-Debug, X-Client-Info, Apikey');
```
3. Sauvegardez

**Ou remplacez le fichier complet par `FIX_2_cors_config.php`**

---

### Action 3 : Vider le cache navigateur

**Sur TOUS les appareils qui utilisent l'app** :

1. **Chrome/Edge** :
   - CTRL+SHIFT+DEL
   - Cocher : Cookies, Cache, Stockage local
   - Période : Tout
   - Effacer

2. **Firefox** :
   - CTRL+SHIFT+DEL
   - Cookies et cache
   - Tout effacer

3. **Safari (iOS)** :
   - Réglages → Safari → Effacer historique et données

4. **Android (Chrome)** :
   - Paramètres → Stockage → Effacer les données du site

---

## ✅ Test immédiat

1. **Ouvrez** : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/
2. **Connectez-vous**
3. **Testez chaque page** :
   - Dashboard
   - Planning
   - Rapports
   - Profil

4. **F12 → Console**
   - Vérifiez qu'il n'y a PLUS de :
     - CORS errors
     - 500 errors
     - 404 errors

5. **F12 → Network (Réseau)**
   - Filtrez par `Fetch/XHR`
   - Toutes les requêtes API doivent être **200 OK** (ou 501 si non implémenté)

---

## 🐛 Si ça ne marche TOUJOURS PAS

### Diagnostic rapide

1. **Ouvrez** : https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug
2. **Activez** : Mode Debug
3. **Revenez** au Dashboard
4. **F12 → Console** : Copiez tous les logs `[MV3PRO DEBUG]`
5. **F12 → Network** :
   - Cliquez sur la requête en erreur (rouge)
   - Onglet "Headers" : copiez Request Headers
   - Onglet "Response" : copiez le contenu

### Informations à me transmettre

```
Page qui casse : _______________________
URL exacte : ___________________________
Code HTTP : ____________________________
Message erreur : _______________________

Console (dernières 10 lignes) :



Network Request Headers :



Network Response :



```

---

## 📞 Prochaines étapes

Une fois ces 3 actions effectuées :

1. Testez l'application pendant 10 minutes
2. Notez toutes les pages qui marchent / cassent
3. Si problème persiste, remplissez le diagnostic ci-dessus
4. Consultez le fichier `DIAGNOSTIC_HOSTSTAR.md` pour un diagnostic complet

---

## 🎯 Résultat attendu

Après ces correctifs :

✅ Login fonctionne
✅ Dashboard s'affiche
✅ Planning charge les événements
✅ Rapports charge la liste
✅ Refresh ne donne plus 404
✅ Pas d'erreurs CORS
✅ Pas d'erreurs 500/510

Les pages **Matériel, Notifications, Régie, Sens de pose** peuvent afficher "501 Non implémenté" (c'est normal, endpoints pas encore créés).
