# 📦 GUIDE D'INSTALLATION

Guide complet pour installer, configurer et modifier l'application MV3PRO.

---

## 🎯 PRÉREQUIS

Avant de commencer, assurez-vous d'avoir :

- **Node.js** 18+ installé ([télécharger ici](https://nodejs.org/))
- **npm** 9+ (inclus avec Node.js)
- Un éditeur de code (VS Code recommandé)
- Accès terminal/console
- Git (optionnel)

### Vérifier les versions

```bash
node --version  # doit être >= 18.0.0
npm --version   # doit être >= 9.0.0
```

---

## 📥 INSTALLATION RAPIDE

### 1. Télécharger le projet

Si vous avez le ZIP :
```bash
unzip mv3pro-chantiers.zip
cd mv3pro-chantiers
```

Si vous avez Git :
```bash
git clone <url-du-repo>
cd mv3pro-chantiers
```

### 2. Installer les dépendances

```bash
npm install
```

Cela va installer :
- React 18
- React Router v6
- Supabase client
- TanStack Query
- Lucide Icons
- React Hot Toast
- Tailwind CSS
- Vite
- TypeScript

**Durée estimée :** 1-2 minutes

### 3. Configurer les variables d'environnement

Le fichier `.env` est déjà configuré avec les valeurs Supabase :

```bash
VITE_SUPABASE_URL=https://0ec90b57d6e95fcbda19832f.supabase.co
VITE_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Note :** Ces valeurs sont également en fallback dans le code, donc l'app fonctionnera même si le `.env` est absent.

### 4. Démarrer le serveur de développement

```bash
npm run dev
```

L'application sera accessible à : **http://localhost:5173**

**Login démo :**
- Email : `demo`
- Mot de passe : `demo`

---

## 🏗️ STRUCTURE DU PROJET

```
mv3pro-chantiers/
├── src/
│   ├── components/        # Composants réutilisables
│   │   └── ui/
│   │       ├── Button.tsx
│   │       └── Card.tsx
│   ├── contexts/          # Context providers React
│   │   ├── AuthContext.tsx
│   │   ├── ThemeContext.tsx
│   │   └── OfflineContext.tsx
│   ├── hooks/             # Custom hooks
│   │   └── useOnlineStatus.ts
│   ├── lib/               # Utilitaires et configs
│   │   └── supabase.ts
│   ├── pages/             # Pages de l'application
│   │   ├── LoginPage.tsx
│   │   ├── DashboardPage.tsx
│   │   └── CreateReportPage.tsx
│   ├── routes/            # Configuration du routing
│   │   └── index.tsx
│   ├── index.css          # Styles globaux
│   └── main.tsx           # Point d'entrée
├── public/                # Assets statiques
│   └── manifest.json
├── dist/                  # Build de production (généré)
├── .env                   # Variables d'environnement
├── package.json           # Dépendances
├── tsconfig.json          # Configuration TypeScript
├── vite.config.ts         # Configuration Vite
└── tailwind.config.js     # Configuration Tailwind CSS
```

---

## 🛠️ COMMANDES DISPONIBLES

### Développement

```bash
npm run dev
```
- Lance le serveur de développement
- Hot reload activé
- Accessible sur http://localhost:5173

### Build de production

```bash
npm run build
```
- Compile l'application pour la production
- Génère le dossier `dist/`
- Optimise le code (minification, tree-shaking)
- Génère les chunks optimisés

### Preview du build

```bash
npm run preview
```
- Teste le build de production localement
- Accessible sur http://localhost:4173
- Simule l'environnement de production

### Linter

```bash
npm run lint
```
- Vérifie le code avec ESLint
- Trouve les erreurs potentielles
- Applique les règles de style

---

## ✏️ MODIFIER L'APPLICATION

### 1. Changer les couleurs

**Fichier : `tailwind.config.js`**

```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        // Changer la couleur principale
        orange: {
          50: '#fff7ed',
          // ... jusqu'à 900
          600: '#ea580c', // Couleur principale actuelle
        }
      }
    }
  }
}
```

Pour utiliser une autre couleur (ex: bleu) :
```javascript
primary: colors.blue, // Ajouter cette ligne
```

Puis remplacer `orange-600` par `primary-600` dans les composants.

### 2. Ajouter une nouvelle page

**Étape 1 :** Créer le composant de page

```tsx
// src/pages/NouvellePage.tsx
export default function NouvellePage() {
  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold">Ma Nouvelle Page</h1>
      <p>Contenu de la page...</p>
    </div>
  );
}
```

**Étape 2 :** Ajouter la route

```tsx
// src/routes/index.tsx
import NouvellePage from '../pages/NouvellePage';

// Dans le composant Routes
<Route
  path="/nouvelle-page"
  element={
    <ProtectedRoute>
      <NouvellePage />
    </ProtectedRoute>
  }
/>
```

**Étape 3 :** Ajouter un lien dans le menu

```tsx
// src/pages/DashboardPage.tsx
import { Link } from 'react-router-dom';

<Link to="/nouvelle-page" className="...">
  Nouvelle Page
</Link>
```

### 3. Modifier les styles globaux

**Fichier : `src/index.css`**

```css
/* Ajouter des styles personnalisés */
.mon-style-perso {
  /* styles */
}

/* Modifier les styles du thème sombre */
.dark {
  /* variables CSS personnalisées */
}
```

### 4. Ajouter un nouveau composant

```tsx
// src/components/MonComposant.tsx
interface MonComposantProps {
  titre: string;
  description?: string;
}

export function MonComposant({ titre, description }: MonComposantProps) {
  return (
    <div className="p-4 bg-white dark:bg-gray-800 rounded-lg">
      <h3 className="font-bold">{titre}</h3>
      {description && <p className="text-gray-600">{description}</p>}
    </div>
  );
}
```

**Utilisation :**
```tsx
import { MonComposant } from '../components/MonComposant';

<MonComposant titre="Test" description="Description test" />
```

### 5. Ajouter une nouvelle dépendance

```bash
npm install nom-du-package
```

Par exemple, pour ajouter Axios :
```bash
npm install axios
```

Puis l'importer :
```tsx
import axios from 'axios';
```

### 6. Modifier la connexion Supabase

**Fichier : `src/lib/supabase.ts`**

```typescript
// Changer l'URL et la clé
const supabaseUrl = import.meta.env.VITE_SUPABASE_URL || 'VOTRE_NOUVELLE_URL';
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || 'VOTRE_NOUVELLE_CLE';
```

Puis mettre à jour le `.env` :
```bash
VITE_SUPABASE_URL=https://votre-projet.supabase.co
VITE_SUPABASE_ANON_KEY=votre-cle-anon
```

### 7. Personnaliser le thème sombre

**Fichier : `src/contexts/ThemeContext.tsx`**

```typescript
// Activer le thème sombre par défaut
const [isDark, setIsDark] = useState(true);

// OU basé sur les préférences système
const [isDark, setIsDark] = useState(() => {
  return window.matchMedia('(prefers-color-scheme: dark)').matches;
});
```

---

## 🎨 PERSONNALISATION AVANCÉE

### Changer les icônes

L'app utilise **Lucide React**. Voir tous les icônes : https://lucide.dev/

```tsx
import { Home, Settings, User, Plus, X } from 'lucide-react';

<Home className="w-6 h-6 text-gray-600" />
```

### Modifier les animations

Tailwind CSS fournit des animations par défaut :

```tsx
<div className="animate-spin">Loading...</div>
<div className="animate-pulse">Pulsing...</div>
<div className="animate-bounce">Bouncing...</div>
```

Créer des animations personnalisées dans `tailwind.config.js` :

```javascript
module.exports = {
  theme: {
    extend: {
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        }
      }
    }
  }
}
```

### Ajouter des variables CSS personnalisées

```css
/* src/index.css */
:root {
  --primary-color: #ea580c;
  --secondary-color: #f97316;
  --border-radius: 0.5rem;
}

.dark {
  --primary-color: #fb923c;
}

/* Utiliser dans vos composants */
.mon-element {
  background-color: var(--primary-color);
  border-radius: var(--border-radius);
}
```

---

## 🔧 CONFIGURATION AVANCÉE

### Modifier le port de développement

**Fichier : `vite.config.ts`**

```typescript
export default defineConfig({
  server: {
    port: 3000, // Changer de 5173 à 3000
    host: true
  }
});
```

### Configurer le base path

Si vous déployez dans un sous-dossier :

```typescript
export default defineConfig({
  base: '/mv3pro/', // Pour https://domaine.com/mv3pro/
});
```

### Optimiser le build

```typescript
export default defineConfig({
  build: {
    minify: 'terser', // Meilleure compression
    sourcemap: false, // Désactiver les sourcemaps
    rollupOptions: {
      output: {
        manualChunks: {
          'react-vendor': ['react', 'react-dom'],
          'router': ['react-router-dom'],
        }
      }
    }
  }
});
```

---

## 📱 PWA (Progressive Web App)

L'application est prête pour PWA avec le fichier `public/manifest.json`.

### Personnaliser le manifest

```json
{
  "name": "Votre Nom d'App",
  "short_name": "VotreApp",
  "theme_color": "#votre-couleur",
  "icons": [
    {
      "src": "/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    }
  ]
}
```

### Ajouter un Service Worker

```bash
npm install vite-plugin-pwa -D
```

Dans `vite.config.ts` :
```typescript
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}']
      }
    })
  ]
});
```

---

## 🐛 DÉPANNAGE

### Erreur : Module not found

**Solution :**
```bash
rm -rf node_modules package-lock.json
npm install
```

### Erreur : Port already in use

**Solution :**
```bash
# Trouver le process
lsof -i :5173

# Tuer le process
kill -9 <PID>

# OU changer le port dans vite.config.ts
```

### Erreur : Cannot read property 'pathname' of undefined

**Solution :** Vérifier que `<BrowserRouter>` entoure bien vos routes dans `main.tsx`.

### Erreur de build Tailwind

**Solution :**
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init
```

---

## 📚 RESSOURCES UTILES

### Documentation

- [React](https://react.dev/)
- [React Router](https://reactrouter.com/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Vite](https://vitejs.dev/)
- [Supabase](https://supabase.com/docs)
- [TypeScript](https://www.typescriptlang.org/)

### Tutoriels

- [React Tutorial](https://react.dev/learn)
- [Tailwind CSS Playground](https://play.tailwindcss.com/)
- [Vite Guide](https://vitejs.dev/guide/)

### Outils

- [VS Code](https://code.visualstudio.com/)
- [React DevTools](https://react.dev/learn/react-developer-tools)
- [Tailwind CSS IntelliSense](https://marketplace.visualstudio.com/items?itemName=bradlc.vscode-tailwindcss)

---

## 🎓 APPRENDRE À MODIFIER

### Pour les débutants

1. **Commencez par les couleurs** : Changez les couleurs dans `tailwind.config.js`
2. **Modifiez les textes** : Changez les textes dans les pages
3. **Ajoutez des sections** : Copiez/collez des composants existants
4. **Testez en temps réel** : `npm run dev` et voyez les changements instantanément

### Pour les développeurs intermédiaires

1. **Créez de nouvelles pages** : Suivez le pattern des pages existantes
2. **Ajoutez des fonctionnalités** : Utilisez les contexts et hooks existants
3. **Intégrez des APIs** : Utilisez TanStack Query pour les requêtes
4. **Optimisez les performances** : Utilisez `React.memo`, `useMemo`, `useCallback`

### Pour les développeurs avancés

1. **Architecturez des features complexes** : Créez des modules réutilisables
2. **Implémentez des tests** : Ajoutez Jest et React Testing Library
3. **Optimisez le bundle** : Analysez avec `rollup-plugin-visualizer`
4. **Déployez en CI/CD** : Configurez GitHub Actions ou GitLab CI

---

## ✅ CHECKLIST AVANT DÉPLOIEMENT

- [ ] Build réussi : `npm run build`
- [ ] Preview testé : `npm run preview`
- [ ] Variables d'environnement configurées
- [ ] Pas d'erreurs console navigateur
- [ ] Tests des fonctionnalités principales
- [ ] Responsive testé (mobile, tablette, desktop)
- [ ] Thème sombre testé
- [ ] Performance vérifiée (Lighthouse)

---

## 🚀 PROCHAINES ÉTAPES

Après avoir installé et modifié l'application :

1. **Tester localement** : `npm run dev`
2. **Builder pour production** : `npm run build`
3. **Tester le build** : `npm run preview`
4. **Déployer** : Suivre le guide [DEPLOIEMENT_APPMV3.md](./DEPLOIEMENT_APPMV3.md)

---

## 📞 SUPPORT

En cas de problème :

1. Vérifier les logs console (`F12` dans le navigateur)
2. Vérifier les logs terminal
3. Consulter la documentation officielle
4. Reconstruire : `rm -rf node_modules && npm install`
5. Vérifier la compatibilité Node.js version

---

*Guide créé le : 23 Décembre 2024*
*Version : 1.0.2*
*Application : MV3PRO - Gestion de Chantiers*
