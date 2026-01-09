import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const basePath = env.VITE_BASE_PATH || '/custom/mv3pro_portail/pwa_dist';

  return {
    plugins: [
      react(),
      VitePWA({
        registerType: 'autoUpdate',
        includeAssets: ['icon-192.png', 'icon-512.png'],
        manifest: {
          name: 'MV3 PRO Mobile',
          short_name: 'MV3 PRO',
          description: 'Application mobile pour les ouvriers MV3 Carrelage',
          theme_color: '#0891b2',
          background_color: '#f9fafb',
          display: 'standalone',
          orientation: 'portrait',
          scope: `${basePath}/`,
          start_url: `${basePath}/#/dashboard`,
        icons: [
          {
            src: 'icon-192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'icon-512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'google-fonts-cache',
              expiration: {
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 365
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          }
          ]
        }
      })
    ],
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
    },
    build: {
      outDir: '../pwa_dist',
      emptyOutDir: true,
      sourcemap: false
    },
    base: basePath
  };
});
