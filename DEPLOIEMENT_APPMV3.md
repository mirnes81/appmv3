# 🚀 DÉPLOIEMENT DANS APPMV3

Guide complet pour intégrer et déployer cette application dans votre projet appmv3.

---

## 📋 OPTION 1 : DÉPLOIEMENT EN TANT QUE MODULE

### Étape 1 : Copier les fichiers

```bash
# Depuis le répertoire de cette application
cp -r dist/* /chemin/vers/appmv3/public/mv3pro/

# OU créer un sous-dossier spécifique
mkdir -p /chemin/vers/appmv3/public/modules/mv3pro
cp -r dist/* /chemin/vers/appmv3/public/modules/mv3pro/
```

### Étape 2 : Configurer le routing

Dans votre application appmv3, ajoutez une route pour accéder au module :

**Exemple avec Express.js :**
```javascript
// server.js ou app.js
app.use('/mv3pro', express.static('public/modules/mv3pro'));
```

**Exemple avec Nginx :**
```nginx
location /mv3pro/ {
    alias /var/www/appmv3/public/modules/mv3pro/;
    try_files $uri $uri/ /mv3pro/index.html;
}
```

### Étape 3 : Accéder au module

L'application sera accessible à :
```
https://votre-domaine.com/mv3pro/
```

---

## 📋 OPTION 2 : INTÉGRATION COMPLÈTE DANS APPMV3

### Étape 1 : Copier les fichiers sources

```bash
# Copier tous les fichiers sources
cp -r src/* /chemin/vers/appmv3/src/modules/mv3pro/
cp -r public/* /chemin/vers/appmv3/public/
```

### Étape 2 : Installer les dépendances

```bash
cd /chemin/vers/appmv3
npm install @supabase/supabase-js @tanstack/react-query lucide-react react-hot-toast
```

### Étape 3 : Configurer les variables d'environnement

Ajoutez dans `/chemin/vers/appmv3/.env` :
```bash
VITE_SUPABASE_URL=https://0ec90b57d6e95fcbda19832f.supabase.co
VITE_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJib2x0IiwicmVmIjoiMGVjOTBiNTdkNmU5NWZjYmRhMTk4MzJmIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTg4ODE1NzQsImV4cCI6MTc1ODg4MTU3NH0.9I8-U0x86Ak8t2DGaIk0HfvTSLsAyzdnz-Nw00mMkKw
```

### Étape 4 : Ajouter les routes dans appmv3

Dans votre fichier de routes principal (ex: `src/routes/index.tsx`) :

```tsx
import { Routes, Route } from 'react-router-dom';
import MV3ProApp from './modules/mv3pro/MV3ProApp';

function AppRoutes() {
  return (
    <Routes>
      {/* Vos routes existantes */}
      <Route path="/mv3pro/*" element={<MV3ProApp />} />
    </Routes>
  );
}
```

### Étape 5 : Créer le composant wrapper

Créez `/chemin/vers/appmv3/src/modules/mv3pro/MV3ProApp.tsx` :

```tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import { OfflineProvider } from './contexts/OfflineContext';
import AppRoutes from './routes';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
      staleTime: 5 * 60 * 1000,
    },
  },
});

export default function MV3ProApp() {
  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider>
        <AuthProvider>
          <OfflineProvider>
            <AppRoutes />
            <Toaster position="top-right" />
          </OfflineProvider>
        </AuthProvider>
      </ThemeProvider>
    </QueryClientProvider>
  );
}
```

### Étape 6 : Rebuild appmv3

```bash
cd /chemin/vers/appmv3
npm run build
```

---

## 📋 OPTION 3 : SOUS-DOMAINE SÉPARÉ

### Étape 1 : Déployer sur un sous-domaine

```bash
# Déployer le dossier dist sur un serveur séparé
scp -r dist/* user@server:/var/www/mv3pro.votre-domaine.com/
```

### Étape 2 : Configurer Nginx pour le sous-domaine

```nginx
server {
    listen 80;
    server_name mv3pro.votre-domaine.com;

    root /var/www/mv3pro.votre-domaine.com;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache des assets statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Étape 3 : Configurer le HTTPS (Let's Encrypt)

```bash
sudo certbot --nginx -d mv3pro.votre-domaine.com
```

L'application sera accessible à :
```
https://mv3pro.votre-domaine.com
```

---

## 📋 OPTION 4 : DOCKER (RECOMMANDÉ)

### Étape 1 : Créer le Dockerfile

```dockerfile
# Dockerfile dans le répertoire de cette application
FROM nginx:alpine

# Copier les fichiers buildés
COPY dist/ /usr/share/nginx/html/

# Copier la configuration Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

### Étape 2 : Créer nginx.conf

```nginx
server {
    listen 80;
    server_name _;

    root /usr/share/nginx/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml+rss text/javascript;
}
```

### Étape 3 : Build et run Docker

```bash
# Build l'image
docker build -t mv3pro-app .

# Run le conteneur
docker run -d -p 8080:80 --name mv3pro mv3pro-app
```

### Étape 4 : Intégrer dans docker-compose.yml d'appmv3

```yaml
version: '3.8'

services:
  # Vos services existants...

  mv3pro:
    build: ./modules/mv3pro
    ports:
      - "8080:80"
    restart: unless-stopped
    networks:
      - appmv3-network

networks:
  appmv3-network:
    driver: bridge
```

---

## 📁 STRUCTURE RECOMMANDÉE DANS APPMV3

```
appmv3/
├── src/
│   ├── modules/
│   │   └── mv3pro/
│   │       ├── components/
│   │       ├── contexts/
│   │       ├── hooks/
│   │       ├── lib/
│   │       ├── pages/
│   │       ├── routes/
│   │       └── MV3ProApp.tsx
│   ├── routes/
│   │   └── index.tsx (importe MV3ProApp)
│   └── main.tsx
├── public/
│   └── mv3pro/ (assets si déploiement module)
└── package.json
```

---

## 🔧 CONFIGURATION REQUISE

### Variables d'environnement obligatoires

```bash
VITE_SUPABASE_URL=https://0ec90b57d6e95fcbda19832f.supabase.co
VITE_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Note :** Ces valeurs sont déjà incluses en fallback dans le code, donc l'app fonctionnera même sans `.env`.

### Dépendances npm

```json
{
  "dependencies": {
    "@supabase/supabase-js": "^2.39.3",
    "@tanstack/react-query": "^5.17.19",
    "lucide-react": "^0.309.0",
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-hot-toast": "^2.6.0",
    "react-router-dom": "^6.21.2"
  }
}
```

---

## ✅ VÉRIFICATION POST-DÉPLOIEMENT

### 1. Tester l'accès

```bash
# Selon votre option de déploiement
curl http://localhost:8080
# OU
curl https://votre-domaine.com/mv3pro/
```

### 2. Vérifier les logs

```bash
# Docker
docker logs mv3pro

# Nginx
tail -f /var/log/nginx/error.log
```

### 3. Tester les fonctionnalités

- [ ] Page de login s'affiche
- [ ] Connexion demo/demo fonctionne
- [ ] Dashboard accessible
- [ ] Navigation entre pages OK
- [ ] Thème sombre/clair fonctionne
- [ ] Responsive mobile OK
- [ ] Aucune erreur console

---

## 🔒 SÉCURITÉ

### 1. Configuration CORS (si API séparée)

```javascript
// Dans votre backend appmv3
app.use(cors({
  origin: ['https://votre-domaine.com', 'https://mv3pro.votre-domaine.com'],
  credentials: true
}));
```

### 2. Headers de sécurité Nginx

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' https://0ec90b57d6e95fcbda19832f.supabase.co" always;
```

---

## 🐛 DÉPANNAGE

### Problème : Page blanche

**Solution :** Vérifier que les variables Supabase sont correctes dans `.env` ou que les fallbacks sont présents dans `src/lib/supabase.ts`.

### Problème : Routes ne fonctionnent pas

**Solution :** Configurer `try_files $uri $uri/ /index.html` dans Nginx pour le routing client-side.

### Problème : Assets 404

**Solution :** Vérifier le `base` dans `vite.config.ts` et les chemins dans la configuration serveur.

---

## 📊 MONITORING

### Logs Nginx

```bash
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### Logs Docker

```bash
docker logs -f mv3pro
```

### Métriques

Intégrer avec votre solution de monitoring existante (Prometheus, Grafana, etc.)

---

## 🎯 RECOMMANDATION

**Pour appmv3, nous recommandons l'OPTION 4 (Docker)** car elle offre :
- ✅ Isolation complète
- ✅ Facilité de déploiement
- ✅ Scalabilité
- ✅ Rollback facile
- ✅ Intégration CI/CD simple

---

## 📞 SUPPORT

En cas de problème, vérifiez :
1. Les logs du serveur
2. La console navigateur
3. Les variables d'environnement
4. La configuration Nginx/Apache
5. Les permissions des fichiers

---

*Guide créé le : 23 Décembre 2024*
*Version : 1.0.2*
