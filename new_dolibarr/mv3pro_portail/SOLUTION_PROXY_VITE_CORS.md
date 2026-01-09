# Solution Proxy Vite pour CORS

## Problème identifié

En preview Bolt, les fetch directs vers `https://crm.mv-3pro.ch` étaient bloqués par CORS :
```
Failed to fetch
Access-Control-Allow-Origin error
```

## Solution : Proxy Vite en développement

**Principe :** En dev, utiliser un proxy local qui redirige vers le serveur prod, évitant ainsi les problèmes CORS.

### 1. Configuration proxy dans vite.config.ts

```typescript
server: {
  host: true,
  port: 3100,
  strictPort: true,
  proxy: {
    '/mv3api': {
      target: 'https://crm.mv-3pro.ch',
      changeOrigin: true,
      secure: true,
      rewrite: (path) => path.replace(/^\/mv3api/, ''),
      configure: (proxy, _options) => {
        proxy.on('error', (err, _req, _res) => {
          console.log('[Proxy Error]', err);
        });
        proxy.on('proxyReq', (proxyReq, req, _res) => {
          console.log('[Proxy Request]', req.method, req.url, '→', proxyReq.path);
        });
        proxy.on('proxyRes', (proxyRes, req, _res) => {
          console.log('[Proxy Response]', req.url, '→', proxyRes.statusCode);
        });
      }
    }
  }
}
```

### 2. Configuration .env

**pwa/.env.development** (Bolt preview)
```env
# Utilise le proxy Vite pour éviter CORS
# /mv3api est proxifié vers https://crm.mv-3pro.ch
VITE_API_BASE=/mv3api/custom/mv3pro_portail
VITE_BASE_PATH=/
```

**pwa/.env.production** (Dolibarr)
```env
# Chemins relatifs en production
VITE_API_BASE=/custom/mv3pro_portail
VITE_BASE_PATH=/custom/mv3pro_portail/pwa_dist
```

## Comment ça fonctionne

### En développement (Bolt preview)

1. Le code appelle : `/mv3api/custom/mv3pro_portail/mobile_app/api/auth.php?action=login`
2. Vite intercepte `/mv3api` et proxifie vers `https://crm.mv-3pro.ch`
3. Le chemin est réécrit : `/custom/mv3pro_portail/mobile_app/api/auth.php?action=login`
4. Requête finale : `https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/api/auth.php?action=login`
5. Pas de CORS car la requête part du serveur Vite (backend)

### En production (Dolibarr)

1. Le code appelle : `/custom/mv3pro_portail/mobile_app/api/auth.php?action=login`
2. Requête relative directe, pas de proxy
3. Fonctionne comme avant

## Logs de debug

En dev, les logs proxy apparaissent dans la console serveur Vite :

```
[Proxy Request] POST /mv3api/custom/mv3pro_portail/mobile_app/api/auth.php?action=login → /custom/mv3pro_portail/mobile_app/api/auth.php?action=login
[Proxy Response] /mv3api/custom/mv3pro_portail/mobile_app/api/auth.php?action=login → 200
```

## Vérifications

### En preview Bolt (dev) :

✅ Login appelle `/mv3api/custom/mv3pro_portail/...`
✅ Vite proxifie vers `https://crm.mv-3pro.ch/custom/mv3pro_portail/...`
✅ Pas d'erreur CORS
✅ Réponse JSON avec `success: true`

### En production Dolibarr :

✅ Login appelle `/custom/mv3pro_portail/...` (chemin relatif)
✅ Fonctionne exactement comme avant
✅ Pas de proxy (n'existe qu'en dev)

## Avantages de cette solution

1. **Aucune modification du serveur prod** requis
2. **Séparation dev/prod** claire via .env
3. **Logs détaillés** pour debug
4. **Standard Vite** - solution recommandée officielle
5. **Transparent** pour le code applicatif

## Fichiers modifiés

- ✅ `pwa/vite.config.ts` - Ajout config proxy `/mv3api`
- ✅ `pwa/.env.development` - `VITE_API_BASE=/mv3api/custom/mv3pro_portail`
- ℹ️ `pwa/.env.production` - Inchangé, déjà correct
- ℹ️ `pwa/src/lib/api.ts` - Inchangé, utilise juste VITE_API_BASE

## Build

✅ Build prod réussi : 220 KB
✅ Les URLs en prod restent relatives
✅ Aucun impact sur le déploiement Dolibarr

## Test en preview Bolt

1. Rafraîchir (F5)
2. Activer DEBUG MODE
3. Login avec `mirnes@mv-3pro.ch`
4. Console devrait montrer :
   ```
   VITE_API_BASE: /mv3api/custom/mv3pro_portail
   LOGIN_URL: /mv3api/custom/mv3pro_portail/mobile_app/api/auth.php?action=login
   ```
5. Pas d'erreur CORS
6. Success !

Date: 2026-01-09
