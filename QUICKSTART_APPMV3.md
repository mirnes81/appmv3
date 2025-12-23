# ⚡ QUICKSTART - INTÉGRATION DANS APPMV3

Guide rapide pour intégrer MV3PRO dans votre application appmv3 en 5 minutes.

---

## 🎯 OPTION RECOMMANDÉE : COPIE DU BUILD

### Étape 1 : Copier les fichiers (30 secondes)

```bash
# Depuis le répertoire de cette application
cp -r dist/* /chemin/vers/appmv3/public/mv3pro/
```

### Étape 2 : Configurer le routing (1 minute)

**Si appmv3 utilise Express.js :**

```javascript
// Dans server.js ou app.js
app.use('/mv3pro', express.static('public/mv3pro'));
```

**Si appmv3 utilise React Router :**

```tsx
// Dans votre fichier de routes
<Route path="/mv3pro/*" element={
  <iframe
    src="/mv3pro/index.html"
    style={{ width: '100%', height: '100vh', border: 'none' }}
  />
} />
```

**Si appmv3 utilise Nginx :**

```nginx
location /mv3pro/ {
    alias /var/www/appmv3/public/mv3pro/;
    try_files $uri $uri/ /mv3pro/index.html;
}
```

### Étape 3 : Redémarrer appmv3 (10 secondes)

```bash
# Si Node.js
npm restart

# Si Nginx
sudo nginx -s reload

# Si Docker
docker-compose restart
```

### Étape 4 : Tester (30 secondes)

Ouvrir : **http://localhost:3000/mv3pro/** (ou votre port)

Login : `demo` / `demo`

---

## ✅ C'EST TOUT !

L'application est maintenant accessible dans appmv3 à l'URL `/mv3pro/`.

---

## 🔗 AJOUTER UN LIEN DANS LE MENU APPMV3

```tsx
// Dans votre composant de navigation
<a href="/mv3pro/" className="menu-link">
  Gestion Chantiers
</a>

// OU avec React Router
<Link to="/mv3pro/">Gestion Chantiers</Link>
```

---

## 🎨 PERSONNALISER LES COULEURS AVANT INTÉGRATION

Si vous voulez adapter les couleurs au thème d'appmv3 :

### 1. Modifier les couleurs

**Fichier : `tailwind.config.js`**

```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        // Remplacer orange par la couleur de votre choix
        primary: {
          50: '#f0f9ff',   // Bleu clair
          600: '#2563eb',  // Bleu principal
          700: '#1d4ed8',  // Bleu foncé
        }
      }
    }
  }
}
```

### 2. Remplacer dans les fichiers

Rechercher et remplacer `orange-600` par `primary-600` dans :
- `src/pages/LoginPage.tsx`
- `src/pages/DashboardPage.tsx`
- `src/pages/CreateReportPage.tsx`

### 3. Rebuild

```bash
npm run build
```

### 4. Recopier

```bash
cp -r dist/* /chemin/vers/appmv3/public/mv3pro/
```

---

## 🐛 PROBLÈMES COURANTS

### Le module ne charge pas

**Vérifier que le chemin est correct :**
```bash
ls /chemin/vers/appmv3/public/mv3pro/index.html
```

**Vérifier les permissions :**
```bash
chmod -R 755 /chemin/vers/appmv3/public/mv3pro/
```

### Erreur 404 sur les routes

**Configurer try_files dans Nginx :**
```nginx
try_files $uri $uri/ /mv3pro/index.html;
```

**OU ajouter dans Express :**
```javascript
app.get('/mv3pro/*', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/mv3pro/index.html'));
});
```

### Assets ne chargent pas

**Vérifier la config Vite :**

Dans `vite.config.ts`, ajouter :
```typescript
base: '/mv3pro/'
```

Puis rebuild :
```bash
npm run build
```

---

## 📦 FICHIERS DÉPLOYÉS

Après la copie, vous devriez avoir dans `appmv3/public/mv3pro/` :

```
mv3pro/
├── index.html (0.80 KB)
├── manifest.json
└── assets/
    ├── index-Da0WjhEt.css (3.83 KB gzippé)
    ├── index-CDjosH0N.js (19.78 KB gzippé)
    ├── vendor-Ciw1Bj1E.js (52.26 KB gzippé)
    └── supabase-CRHRt2Ih.js (44.20 KB gzippé)
```

**Total : ~120 KB gzippé**

---

## 🚀 DÉPLOIEMENT EN PRODUCTION

### Option 1 : Même serveur qu'appmv3

Déjà fait ! Les fichiers sont dans `public/mv3pro/`.

### Option 2 : Serveur séparé

```bash
scp -r dist/* user@serveur:/var/www/mv3pro/
```

Puis configurer Nginx :
```nginx
server {
    listen 80;
    server_name mv3pro.votre-domaine.com;
    root /var/www/mv3pro;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

### Option 3 : Docker (recommandé)

```bash
# Build l'image
docker build -t mv3pro .

# Run le conteneur
docker run -d -p 8080:80 mv3pro
```

---

## 📊 STRUCTURE DANS APPMV3

```
appmv3/
├── public/
│   ├── mv3pro/              ← L'application est ici
│   │   ├── index.html
│   │   └── assets/
│   ├── images/
│   └── ...
├── src/
│   ├── routes/
│   │   └── index.tsx        ← Ajouter route /mv3pro
│   └── ...
└── package.json
```

---

## 🔐 SÉCURITÉ

### Authentification partagée (optionnel)

Si vous voulez partager la session entre appmv3 et mv3pro :

1. **Utiliser le même Supabase** dans les deux apps
2. **Partager les cookies** (même domaine)
3. **Synchroniser les tokens JWT**

**Exemple :**
```typescript
// Dans mv3pro/src/lib/supabase.ts
// Utiliser les mêmes variables que appmv3
const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY;
```

---

## 🎯 TESTER L'INTÉGRATION

### Checklist rapide

- [ ] URL accessible : `http://localhost:3000/mv3pro/`
- [ ] Page de login s'affiche
- [ ] Connexion demo/demo fonctionne
- [ ] Dashboard accessible
- [ ] Navigation fonctionne
- [ ] Pas d'erreurs console (F12)
- [ ] Responsive mobile OK

### Test des routes

```bash
# Tester l'accès
curl http://localhost:3000/mv3pro/

# Doit retourner du HTML avec <title>MV3PRO</title>
```

---

## 📱 AJOUTER AU MENU MOBILE APPMV3

```tsx
// Exemple avec un menu hamburger
const menuItems = [
  { title: 'Dashboard', path: '/' },
  { title: 'Projets', path: '/projets' },
  { title: 'Chantiers', path: '/mv3pro/' }, // ← Ajouter ici
  { title: 'Paramètres', path: '/settings' },
];
```

---

## 🎨 ADAPTER LE STYLE À APPMV3

### Méthode 1 : CSS Override

Créer `appmv3/public/mv3pro/custom.css` :
```css
/* Override des couleurs */
:root {
  --primary-color: #votre-couleur;
}

/* Ajuster le header si besoin */
body {
  margin-top: 60px; /* Si header fixe dans appmv3 */
}
```

Puis l'inclure dans `index.html` :
```html
<link rel="stylesheet" href="custom.css">
```

### Méthode 2 : Rebuild avec nouvelles couleurs

Voir section "Personnaliser les couleurs" ci-dessus.

---

## 🔧 CONFIGURATION AVANCÉE

### Base Path personnalisé

Si vous voulez un chemin différent (ex: `/modules/chantiers/`) :

1. **Modifier vite.config.ts :**
```typescript
base: '/modules/chantiers/'
```

2. **Rebuild :**
```bash
npm run build
```

3. **Copier au bon endroit :**
```bash
cp -r dist/* /chemin/vers/appmv3/public/modules/chantiers/
```

4. **Ajuster la route :**
```javascript
app.use('/modules/chantiers', express.static('public/modules/chantiers'));
```

---

## 📖 DOCUMENTATION COMPLÈTE

Pour plus de détails, consultez :

- **[INSTALLATION.md](./INSTALLATION.md)** - Guide d'installation complet
- **[DEPLOIEMENT_APPMV3.md](./DEPLOIEMENT_APPMV3.md)** - Toutes les options de déploiement
- **[BUILD_COMPLETE.md](./BUILD_COMPLETE.md)** - Informations sur le build

---

## ⏱️ TEMPS ESTIMÉ PAR MÉTHODE

| Méthode | Temps | Difficulté |
|---------|-------|------------|
| **Copie simple** | 5 min | Facile |
| Avec personnalisation | 15 min | Facile |
| Intégration source | 30 min | Moyenne |
| Docker | 20 min | Moyenne |
| Sous-domaine séparé | 30 min | Avancé |

---

## 🎉 RÉSUMÉ

**En 3 commandes :**

```bash
# 1. Copier les fichiers
cp -r dist/* /chemin/vers/appmv3/public/mv3pro/

# 2. Configurer la route (dans appmv3)
# app.use('/mv3pro', express.static('public/mv3pro'));

# 3. Redémarrer
npm restart
```

**Résultat :**
✅ Application accessible à `http://localhost:3000/mv3pro/`
✅ Login démo fonctionnel : `demo` / `demo`
✅ Complètement intégrée dans appmv3

---

## 🚀 PROCHAINES ÉTAPES

1. **Tester** : Ouvrir `/mv3pro/` et se connecter
2. **Personnaliser** : Adapter les couleurs si besoin
3. **Déployer** : Pousser appmv3 en production
4. **Monitorer** : Vérifier les logs et performances

---

*Guide créé le : 23 Décembre 2024*
*Version : 1.0.2*
*⚡ Intégration rapide en 5 minutes !*
