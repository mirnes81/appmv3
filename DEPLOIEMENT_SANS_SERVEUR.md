# Déploiement MV3 Pro - Sans Accès Serveur

## Architecture

Application PWA statique qui se connecte directement à l'API REST Dolibarr officielle via DOLAPIKEY.

**Aucun backend intermédiaire - Aucun accès serveur requis**

## Prérequis

1. Instance Dolibarr accessible (ex: `https://crm.mv-3pro.ch`)
2. Module API REST activé dans Dolibarr
3. Hébergement pour fichiers statiques (Netlify, Vercel, GitHub Pages, Apache, etc.)

## Étapes de Déploiement

### 1. Build de l'Application

```bash
npm install
npm run build
```

Résultat: Le dossier `dist/` contient l'application prête à déployer.

### 2. Déployer sur Hébergement Statique

#### Option A: Netlify (Recommandé)

```bash
# Installation CLI
npm install -g netlify-cli

# Déploiement
netlify deploy --prod --dir=dist
```

Ou via l'interface web: glisser-déposer le dossier `dist/`

#### Option B: Vercel

```bash
npm install -g vercel
vercel --prod
```

#### Option C: GitHub Pages

```bash
# Dans .github/workflows/deploy.yml
name: Deploy
on:
  push:
    branches: [ main ]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
      - run: npm install
      - run: npm run build
      - uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: ./dist
```

#### Option D: Serveur Web Classique

Copiez simplement le contenu de `dist/` dans votre dossier web:

```bash
scp -r dist/* user@server:/var/www/html/app/
```

### 3. Configuration CORS

L'application appelle directement l'API Dolibarr. Vous devez activer CORS.

#### Solution A: CORS dans Dolibarr

Ajoutez dans `/var/www/dolibarr/htdocs/.htaccess`:

```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "DOLAPIKEY, Accept, Content-Type"
    Header set Access-Control-Max-Age "3600"
</IfModule>
```

#### Solution B: Proxy (Recommandé)

Créez un proxy qui ajoute les headers CORS:

**NGINX:**
```nginx
server {
    listen 443 ssl;
    server_name app.mv-3pro.ch;

    location /api/ {
        proxy_pass https://crm.mv-3pro.ch/api/;
        
        add_header Access-Control-Allow-Origin * always;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
        add_header Access-Control-Allow-Headers "DOLAPIKEY, Accept, Content-Type" always;
        
        if ($request_method = 'OPTIONS') {
            return 204;
        }
    }
    
    location / {
        root /var/www/app;
        try_files $uri $uri/ /index.html;
    }
}
```

**Apache:**
```apache
<VirtualHost *:443>
    ServerName app.mv-3pro.ch
    
    # Proxy API
    ProxyPass /api/ https://crm.mv-3pro.ch/api/
    ProxyPassReverse /api/ https://crm.mv-3pro.ch/api/
    
    # CORS
    <Location /api/>
        Header set Access-Control-Allow-Origin "*"
        Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
        Header set Access-Control-Allow-Headers "DOLAPIKEY, Accept, Content-Type"
    </Location>
    
    # Application statique
    DocumentRoot /var/www/app
</VirtualHost>
```

### 4. Utilisation

1. **Ouvrir l'application:** `https://app.mv-3pro.ch/`

2. **Première connexion:**
   - URL Dolibarr: `https://crm.mv-3pro.ch`
   - DOLAPIKEY: (à générer dans Dolibarr)

3. **L'application est prête**

## Aucune Configuration Serveur

Contrairement à l'ancienne architecture, vous n'avez PLUS besoin de:

- Créer de base de données
- Installer PHP
- Configurer `config.php`
- Gérer des secrets JWT
- Créer des endpoints API custom
- Avoir un accès SSH
- Avoir un accès MySQL

## Maintenance

### Mise à Jour

```bash
git pull
npm install
npm run build
# Redéployer dist/
```

### Monitoring

Aucun serveur backend = Rien à monitorer côté app

Surveiller uniquement:
- Disponibilité de Dolibarr
- Logs API Dolibarr

### Sauvegardes

Toutes les données sont dans Dolibarr. Sauvegardez:
- Base MySQL Dolibarr
- Dossier ECM Dolibarr

## Sécurité

### DOLAPIKEY

- Une clé par utilisateur
- Révocable depuis Dolibarr
- Stockée uniquement sur l'appareil de l'utilisateur
- Jamais transmise au serveur de l'app (direct vers Dolibarr)

### HTTPS Obligatoire

- Application: HTTPS
- Dolibarr: HTTPS
- DOLAPIKEY ne doit JAMAIS transiter en HTTP

### Permissions Dolibarr

Configurez les permissions par utilisateur:
- Module Fichinter: Lecture/Écriture
- Module Agenda: Lecture/Écriture
- Module Tiers: Lecture
- Module Projets: Lecture
- Module ECM: Lecture/Écriture

## Avantages

- Déploiement instantané
- Pas de serveur à maintenir
- Pas de vulnérabilités backend
- Évolutif automatiquement
- Coûts d'hébergement minimaux
- Compatible tous hébergeurs
- Fonctionne offline

## Support

**Problème de connexion:**

Test manuel de la DOLAPIKEY:
```bash
curl -H "DOLAPIKEY: VOTRE_CLE" \
     https://crm.mv-3pro.ch/api/index.php/users/info
```

Résultat attendu:
```json
{
  "id": 1,
  "login": "admin",
  "firstname": "John",
  "lastname": "Doe"
}
```

**Erreur CORS:**

Vérifiez les headers de réponse:
```bash
curl -I -H "DOLAPIKEY: XXX" \
     https://crm.mv-3pro.ch/api/index.php/users/info
```

Doit contenir:
```
Access-Control-Allow-Origin: *
```

## Comparaison Architectures

| Critère | Ancienne (JWT + PHP) | Nouvelle (DOLAPIKEY) |
|---------|---------------------|---------------------|
| Backend requis | Oui (PHP + MySQL) | Non |
| Accès serveur | SSH + MySQL | Aucun |
| Maintenance | Serveur + App | App uniquement |
| Sécurité | JWT à gérer | Géré par Dolibarr |
| Déploiement | Complexe | Simple |
| Coûts | Serveur requis | Hébergement statique |
| Évolutivité | Limitée | Illimitée |

## Conclusion

Cette architecture élimine complètement le besoin d'un backend intermédiaire, simplifie le déploiement et la maintenance, tout en conservant toutes les fonctionnalités.

L'application est désormais un simple client de l'API REST Dolibarr officielle.
